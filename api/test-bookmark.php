<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/favicon/favicon-cache.php';
require_once '../includes/favicon/favicon-config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
    ]);
    exit;
}

if (!isAuthenticated($pdo)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated. Please log in again.',
    ]);
    exit;
}

/**
 * Return a compact, normalized page description suitable for bookmark storage.
 */
function extractPageDescription(string $html): string
{
    if ($html === '' || !class_exists('DOMDocument')) {
        return '';
    }

    $previousErrors = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadHTML(
        '<?xml encoding="utf-8" ?>' . $html,
        LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    if (!$loaded) {
        return '';
    }

    $description = '';
    foreach ($document->getElementsByTagName('meta') as $meta) {
        $name = strtolower(trim($meta->getAttribute('name')));
        $property = strtolower(trim($meta->getAttribute('property')));
        if (
            $name === 'description'
            || $name === 'twitter:description'
            || $property === 'og:description'
        ) {
            $description = trim($meta->getAttribute('content'));
            if ($description !== '') {
                break;
            }
        }
    }

    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = preg_replace('/\s+/u', ' ', $description) ?? $description;
    $description = trim($description);

    return function_exists('mb_substr')
        ? mb_substr($description, 0, 200, 'UTF-8')
        : substr($description, 0, 200);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !isset($input['id'])) {
        throw new InvalidArgumentException('Bookmark ID is required');
    }

    $bookmarkId = (int)$input['id'];
    if ($bookmarkId <= 0) {
        throw new InvalidArgumentException('A valid bookmark ID is required');
    }
    $currentUserId = getCurrentUserId();

    $stmt = $pdo->prepare('
        SELECT b.id, b.url, b.description, b.favicon_url
        FROM bookmarks b
        INNER JOIN categories c
            ON c.id = b.category_id
            AND c.user_id = b.user_id
            AND c.deleted_at IS NULL
        WHERE b.id = ? AND b.user_id = ?
    ');
    $stmt->execute([$bookmarkId, $currentUserId]);
    $bookmark = $stmt->fetch();

    if (!$bookmark) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Bookmark not found or access denied',
        ]);
        exit;
    }

    $url = trim((string)$bookmark['url']);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'status' => 0,
            'message' => 'The bookmark URL is invalid.',
            'description_updated' => false,
            'favicon_refreshed' => false,
        ]);
        exit;
    }

    $body = '';
    $bodyLimit = 1024 * 1024;
    $bodyTruncated = false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 6,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MyStartPage-LinkChecker/1.0)',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,nl;q=0.8',
            'Cache-Control: no-cache',
        ],
        CURLOPT_AUTOREFERER => true,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, $bodyLimit, &$bodyTruncated): int {
            $remaining = $bodyLimit - strlen($body);
            if ($remaining <= 0) {
                $bodyTruncated = true;
                return 0;
            }

            if (strlen($chunk) > $remaining) {
                $body .= substr($chunk, 0, $remaining);
                $bodyTruncated = true;
                return 0;
            }

            $body .= $chunk;
            return strlen($chunk);
        },
    ]);

    $curlResult = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = strtolower((string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: ''));
    $finalUrl = (string)(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url);
    $curlErrorNumber = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    $transportSucceeded = $curlResult !== false || $bodyTruncated;
    $definitelyMissing = in_array($status, [404, 410], true)
        || ($status === 0 && $curlErrorNumber === CURLE_COULDNT_RESOLVE_HOST);

    if ($definitelyMissing) {
        $message = $status > 0
            ? "The link returned HTTP {$status} and appears not to exist."
            : 'The link host could not be found.';
        echo json_encode([
            'success' => true,
            'exists' => false,
            'status' => $status,
            'final_url' => $finalUrl,
            'message' => $message,
            'description_updated' => false,
            'favicon_refreshed' => false,
        ]);
        exit;
    }

    if (!$transportSucceeded || $status >= 500 || $status === 0) {
        $detail = $status >= 500
            ? "HTTP {$status}"
            : ($curlError !== '' ? $curlError : 'no response');
        echo json_encode([
            'success' => true,
            'exists' => null,
            'status' => $status,
            'final_url' => $finalUrl,
            'message' => "The link could not be verified ({$detail}). Try again later.",
            'description_updated' => false,
            'favicon_refreshed' => false,
        ]);
        exit;
    }

    $description = '';
    $canRefreshMetadata = $status >= 200 && $status < 400;
    if (
        $canRefreshMetadata
        && (strpos($contentType, 'text/html') !== false || stripos(substr($body, 0, 300), '<html') !== false)
    ) {
        $description = extractPageDescription($body);
    }

    $currentDescription = trim((string)($bookmark['description'] ?? ''));
    $descriptionUpdated = $currentDescription === '' && $description !== '';
    $currentFaviconUrl = FaviconConfig::normalizeStoredFaviconUrl($bookmark['favicon_url'] ?? '');
    $faviconUrl = $currentFaviconUrl;
    $faviconRefreshed = false;

    if ($canRefreshMetadata) {
        try {
            // A zero cache lifetime revalidates the icon without deleting the existing
            // cached file first, so a failed refresh cannot break a working favicon.
            $faviconCache = new FaviconCache(__DIR__ . '/../cache/favicons/', 0, true);
            $faviconResult = $faviconCache->resolveForUrl($url, false);
            $resolvedFaviconUrl = FaviconConfig::normalizeStoredFaviconUrl($faviconResult['favicon_url'] ?? '');
            $faviconRefreshed = ($faviconResult['failure_reason'] ?? null) === null
                && ($faviconResult['source'] ?? '') !== 'generated'
                && ($faviconResult['source'] ?? '') !== 'external-fallback'
                && $resolvedFaviconUrl !== '';

            if ($faviconRefreshed) {
                $faviconUrl = $resolvedFaviconUrl;
            }
        } catch (Throwable $faviconError) {
            error_log(
                "Link test favicon refresh failed for bookmark {$bookmarkId}: "
                . $faviconError->getMessage()
            );
        }
    }

    if ($descriptionUpdated || ($faviconRefreshed && $faviconUrl !== $currentFaviconUrl)) {
        $stmt = $pdo->prepare('
            UPDATE bookmarks
            SET description = ?, favicon_url = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([
            $descriptionUpdated ? $description : $currentDescription,
            $faviconUrl,
            $bookmarkId,
            $currentUserId,
        ]);
    }

    $message = $descriptionUpdated
        ? 'The link works and its description was updated.'
        : ($currentDescription !== ''
            ? 'The link works. Its existing description was kept.'
            : 'The link works. No page description was available.');
    if ($faviconRefreshed) {
        $message .= ' Its favicon was refreshed.';
    }

    echo json_encode([
        'success' => true,
        'exists' => true,
        'status' => $status,
        'final_url' => $finalUrl,
        'message' => $message,
        'description_updated' => $descriptionUpdated,
        'description' => $descriptionUpdated ? $description : $currentDescription,
        'favicon_refreshed' => $faviconRefreshed,
        'favicon_url' => $faviconUrl,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
