<?php
/**
 * Bookmark Color Management
 * 
 * This file provides centralized color mapping logic for bookmarks.
 * All color definitions and mappings derive from a single source of truth.
 */

/**
 * Get the mapping of color integers to CSS token strings
 * @return array<int, string> e.g. [0 => 'none', 1 => 'pink', ...]
 */
function bookmarkColorTokens(): array {
    return [
        0 => 'none',
        1 => 'pink',
        2 => 'green',
        3 => 'blue',
        4 => 'yellow',
        5 => 'purple',
    ];
}

/**
 * Get a color token by integer ID
 * @param int|null $colorId The color integer from database
 * @return string The CSS token string, defaults to 'none' if invalid
 */
function bookmarkColorToken(?int $colorId): string {
    $tokens = bookmarkColorTokens();
    return $tokens[$colorId] ?? 'none';
}

/**
 * Get the CSS class name for a color token
 * @param string $token The color token (e.g., 'pink', 'none')
 * @return string The CSS class name (e.g., 'bookmark-bg-pink')
 */
function bookmarkBgClassFromToken(string $token): string {
    return 'bookmark-bg-' . $token;
}

/**
 * Get the CSS class name for a color integer
 * @param int|null $colorId The color integer from database
 * @return string The CSS class name (e.g., 'bookmark-bg-pink')
 */
function bookmarkBgClass(?int $colorId): string {
    $token = bookmarkColorToken($colorId);
    return bookmarkBgClassFromToken($token);
}

/**
 * Get the mapping of color integers to tokens for JavaScript
 * @return array<int, string> e.g. [0 => 'none', 1 => 'pink', ...]
 */
function getBookmarkColorMapping(): array {
    return bookmarkColorTokens();
}

/**
 * Get the human-readable labels for color tokens
 * @return array<string, string> e.g. ['none' => 'None', 'pink' => 'Pink', ...]
 */
function getBookmarkColorLabels(): array {
    return [
        'none' => 'None',
        'pink' => 'Pink',
        'green' => 'Green',
        'blue' => 'Blue',
        'yellow' => 'Yellow',
        'purple' => 'Purple',
    ];
}

/**
 * Get the reverse mapping of tokens to integers for JavaScript
 * @return array<string, int> e.g. ['none' => 0, 'pink' => 1, ...]
 */
function getBookmarkColorTokenToInt(): array {
    return array_flip(bookmarkColorTokens());
}

/**
 * Get all bookmark background CSS classes
 * @return array<string> e.g. ['bookmark-bg-none', 'bookmark-bg-pink', ...]
 */
function getBookmarkBgClasses(): array {
    $tokens = bookmarkColorTokens();
    $classes = [];
    foreach ($tokens as $token) {
        $classes[] = 'bookmark-bg-' . $token;
    }
    return $classes;
}
?>
