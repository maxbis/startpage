<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Require authentication
requireAuth($pdo);

// Check if user is admin (user_id = 1)
$currentUserId = getCurrentUserId();
if ($currentUserId !== 1) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_user') {
            // Create new user
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($username) || empty($password)) {
                $error = 'Username and password are required';
            } elseif (strlen($username) < 3 || strlen($username) > 50) {
                $error = 'Username must be between 3 and 50 characters';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match';
            } else {
                try {
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = 'Username already exists';
                    } else {
                        // Create new user
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                        $stmt->execute([$username, $passwordHash]);
                        
                        $newUserId = $pdo->lastInsertId();
                        
                        // Create default page for the new user
                        $stmt = $pdo->prepare("INSERT INTO pages (user_id, name, sort_order) VALUES (?, ?, ?)");
                        $stmt->execute([$newUserId, 'My Startpage', 0]);
                        $defaultPageId = $pdo->lastInsertId();
                        
                        // Create default categories for the new user
                        $defaultCategories = [
                            ['Work', 0],
                            ['Personal', 1],
                            ['Tools', 2]
                        ];
                        
                        foreach ($defaultCategories as $category) {
                            $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, page_id, sort_order) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$newUserId, $category[0], $defaultPageId, $category[1]]);
                        }
                        
                        $message = "User '$username' created successfully with default page and categories!";
                        
                        // Clear form
                        $username = '';
                        $password = '';
                        $confirmPassword = '';
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'reset_password') {
            // Reset user password
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if ($userId === 1) {
                $error = 'Cannot reset admin password from this interface';
            } elseif (empty($newPassword)) {
                $error = 'New password is required';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password must be at least 6 characters';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match';
            } else {
                try {
                    // Check if user exists
                    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user) {
                        $error = 'User not found';
                    } else {
                        // Update password
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$passwordHash, $userId]);
                        
                        $message = "Password for user '{$user['username']}' has been reset successfully!";
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'delete_user') {
            // Delete user
            $userId = (int)($_POST['user_id'] ?? 0);
            
            if ($userId === 1) {
                $error = 'Cannot delete admin user';
            } else {
                try {
                    // Check if user exists
                    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user) {
                        $error = 'User not found';
                    } else {
                        // Delete user (this will also delete their data due to CASCADE)
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        $message = "User '{$user['username']}' has been deleted successfully!";
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get list of existing users (excluding admin)
$stmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE id != 1 ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link href="../warm-paper/warm-paper.css?v=<?= filemtime(__DIR__ . '/../warm-paper/warm-paper.css') ?>" rel="stylesheet">
    <link href="../assets/css/warm-paper.css?v=<?= filemtime(__DIR__ . '/../assets/css/warm-paper.css') ?>" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= filemtime(__DIR__ . '/../assets/css/main.css') ?>" rel="stylesheet">
</head>
<body class="wp-theme warm-paper-page">
    <main class="wp-page-shell wp-page-shell--admin">
        <div class="wp-page-stack">
            <!-- Header -->
            <div class="wp-panel wp-panel--section">
                <div class="wp-page-header__row">
                    <h1 class="wp-page-title">Admin Panel</h1>
                    <a href="index.php" class="wp-button wp-button--secondary">
                        ← Back to Startpage
                    </a>
                </div>
                <p class="wp-page-lead">Create new users for the startpage application</p>
            </div>

            <!-- Create User Form -->
            <div class="wp-panel wp-panel--section">
                <h2 class="wp-section-title">Create New User</h2>
                
                <?php if ($message): ?>
                    <div class="wp-alert wp-alert--success" role="status">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="wp-alert wp-alert--error" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="wp-stack">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="wp-field">
                        <label for="username" class="wp-label">
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?= htmlspecialchars($username ?? '') ?>"
                            class="wp-input"
                            placeholder="Enter username"
                            required
                        >
                    </div>
                    
                    <div class="wp-field">
                        <label for="password" class="wp-label">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="wp-input"
                            placeholder="Enter password"
                            required
                        >
                    </div>
                    
                    <div class="wp-field">
                        <label for="confirm_password" class="wp-label">
                            Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="wp-input"
                            placeholder="Confirm password"
                            required
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="wp-button wp-button--primary wp-button--block"
                    >
                        Create User
                    </button>
                </form>
            </div>

            <!-- Reset Password Form -->
            <div class="wp-panel wp-panel--section">
                <h2 class="wp-section-title">Reset User Password</h2>
                
                <form method="POST" class="wp-stack">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="wp-field">
                        <label for="user_id" class="wp-label">
                            Select User
                        </label>
                        <select 
                            id="user_id" 
                            name="user_id" 
                            class="wp-select"
                            required
                        >
                            <option value="">Select a user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['username']) ?> (ID: <?= $user['id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="wp-field">
                        <label for="new_password" class="wp-label">
                            New Password
                        </label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="wp-input"
                            placeholder="Enter new password"
                            required
                        >
                    </div>
                    
                    <div class="wp-field">
                        <label for="confirm_password_reset" class="wp-label">
                            Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password_reset" 
                            name="confirm_password" 
                            class="wp-input"
                            placeholder="Confirm new password"
                            required
                        >
                    </div>
                    
                    <div class="wp-alert wp-alert--warning" role="alert">
                        <p>
                            <strong>⚠️ Warning:</strong> This will immediately change the user's password. 
                            They will need to use the new password to log in.
                        </p>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="wp-button wp-button--warning wp-button--block"
                    >
                        Reset Password
                    </button>
                </form>
            </div>

            <!-- Existing Users List -->
            <div class="wp-panel wp-panel--section">
                <h2 class="wp-section-title">Existing Users</h2>
                
                <?php if (empty($users)): ?>
                    <p class="wp-empty-state">No users created yet.</p>
                <?php else: ?>
                    <div class="wp-table-wrap">
                        <table class="wp-table">
                            <thead>
                                <tr>
                                    <th>
                                        Username
                                    </th>
                                    <th>
                                        Created
                                    </th>
                                    <th>
                                        User ID
                                    </th>
                                    <th>
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($user['username']) ?>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?= $user['id'] ?>
                                        </td>
                                        <td>
                                            <div class="wp-button-row">
                                                <!-- Password Reset Modal Trigger -->
                                                <button 
                                                    onclick="openPasswordResetModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                    class="wp-button wp-button--quiet wp-button--compact"
                                                >
                                                    Reset Password
                                                </button>
                                                
                                                <!-- Delete User Modal Trigger -->
                                                <button 
                                                    onclick="openDeleteUserModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                    class="wp-button wp-button--danger-subtle wp-button--compact"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Password Reset Modal -->
            <div id="passwordResetModal" class="wp-dialog-backdrop modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="passwordResetModalTitle" aria-hidden="true" data-dialog-backdrop-dismiss="false">
                    <div class="wp-dialog wp-dialog--compact modal-panel">
                        <div class="wp-dialog__header dialog-header">
                            <h3 id="passwordResetModalTitle" class="wp-dialog__title dialog-title">Reset Password</h3>
                            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" onclick="closePasswordResetModal()" aria-label="Close reset password dialog">&times;</button>
                        </div>
                        <form id="passwordResetForm" method="POST" class="wp-dialog__body wp-stack dialog-form">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" id="resetUserId">

                            <p class="wp-supporting-text">Reset password for user: <strong id="resetUsername"></strong></p>
                            
                            <div class="wp-field">
                                <label for="modal_new_password" class="wp-label">
                                    New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="modal_new_password" 
                                    name="new_password" 
                                    class="wp-input"
                                    placeholder="Enter new password"
                                    required
                                >
                            </div>
                            
                            <div class="wp-field">
                                <label for="modal_confirm_password" class="wp-label">
                                    Confirm New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="modal_confirm_password" 
                                    name="confirm_password" 
                                    class="wp-input"
                                    placeholder="Confirm new password"
                                    required
                                >
                            </div>
                            
                            <div class="wp-dialog__actions dialog-actions">
                                <span class="dialog-action-spacer"></span>
                                <button type="button" onclick="closePasswordResetModal()" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                                <button type="submit" class="wp-button wp-button--primary dialog-button dialog-button-primary">Reset Password</button>
                            </div>
                        </form>
                    </div>
            </div>
            
            <!-- Delete User Modal -->
            <div id="deleteUserModal" class="wp-dialog-backdrop modal-backdrop" role="alertdialog" aria-modal="true" aria-labelledby="deleteUserModalTitle" aria-hidden="true" data-dialog-backdrop-dismiss="true">
                    <div class="wp-dialog wp-dialog--compact modal-panel">
                        <div class="wp-dialog__header dialog-header">
                            <h3 id="deleteUserModalTitle" class="wp-dialog__title dialog-title">Delete User</h3>
                            <button type="button" class="wp-icon-button wp-dialog__close dialog-close-button" onclick="closeDeleteUserModal()" aria-label="Close delete user dialog">&times;</button>
                        </div>
                        <form id="deleteUserForm" method="POST" class="wp-dialog__body wp-stack dialog-form">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" id="deleteUserId">

                        <p class="wp-supporting-text">
                            Are you sure you want to delete user: <strong id="deleteUsername" class="wp-danger-text"></strong>?
                        </p>
                        <p class="wp-supporting-text wp-danger-text">
                            <strong>Warning:</strong> This will permanently delete the user and all their data (pages, categories, bookmarks).
                        </p>

                            <div class="wp-dialog__actions dialog-actions">
                                <span class="dialog-action-spacer"></span>
                                <button type="button" onclick="closeDeleteUserModal()" class="wp-button wp-button--secondary dialog-button dialog-button-secondary">Cancel</button>
                                <button type="submit" class="wp-button wp-button--danger dialog-button dialog-button-danger">Delete User</button>
                            </div>
                        </form>
                    </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/modules/ui-state.js?v=<?= filemtime(__DIR__ . '/../assets/js/modules/ui-state.js') ?>"></script>
    <script>
        const adminDialogReturnFocus = new WeakMap();
        const adminDialogFocusableSelector = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled]):not([type="hidden"])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ].join(',');

        function getAdminDialogFocusables(dialog) {
            return Array.from(dialog.querySelectorAll(adminDialogFocusableSelector)).filter(element =>
                element.getAttribute('aria-hidden') !== 'true' && element.getClientRects().length > 0
            );
        }

        function openAdminDialog(dialog, initialFocus) {
            adminDialogReturnFocus.set(dialog, document.activeElement);
            window.wpUiState.openDialog(dialog);
            (initialFocus || getAdminDialogFocusables(dialog)[0])?.focus();
        }

        function closeAdminDialog(dialog) {
            window.wpUiState.closeDialog(dialog);
            const returnFocus = adminDialogReturnFocus.get(dialog);
            adminDialogReturnFocus.delete(dialog);
            if (returnFocus?.isConnected && !returnFocus.closest('[hidden], [aria-hidden="true"]')) {
                returnFocus.focus();
            }
        }

        function openPasswordResetModal(userId, username) {
            document.getElementById('passwordResetForm').reset();
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            openAdminDialog(
                document.getElementById('passwordResetModal'),
                document.getElementById('modal_new_password')
            );
        }
        
        function closePasswordResetModal() {
            const dialog = document.getElementById('passwordResetModal');
            closeAdminDialog(dialog);
            document.getElementById('passwordResetForm').reset();
        }
        
        function openDeleteUserModal(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            openAdminDialog(
                document.getElementById('deleteUserModal'),
                document.querySelector('#deleteUserForm .dialog-button-secondary')
            );
        }
        
        function closeDeleteUserModal() {
            closeAdminDialog(document.getElementById('deleteUserModal'));
            document.getElementById('deleteUserForm').reset();
        }
        
        // Close dialogs when clicking their backdrop.
        document.addEventListener('click', function(event) {
            const dialog = event.target.closest('.modal-backdrop');
            if (event.target !== dialog || dialog?.dataset.dialogBackdropDismiss !== 'true') return;
            if (dialog.id === 'deleteUserModal') closeDeleteUserModal();
        });

        document.addEventListener('keydown', function(event) {
            const visibleDialogs = Array.from(document.querySelectorAll('.modal-backdrop.is-open'));
            const dialog = visibleDialogs.at(-1);
            if (!dialog) return;

            if (event.key === 'Escape') {
                event.preventDefault();
                if (dialog.id === 'deleteUserModal') closeDeleteUserModal();
                if (dialog.id === 'passwordResetModal') closePasswordResetModal();
                return;
            }

            if (event.key !== 'Tab') return;
            const focusableElements = getAdminDialogFocusables(dialog);
            if (!focusableElements.length) return;
            const firstElement = focusableElements[0];
            const lastElement = focusableElements.at(-1);
            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            } else if (!dialog.contains(document.activeElement)) {
                event.preventDefault();
                firstElement.focus();
            }
        });
    </script>
</body>
</html>
