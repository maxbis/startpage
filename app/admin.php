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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-800">Admin Panel</h1>
                    <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        ← Back to Startpage
                    </a>
                </div>
                <p class="text-gray-600 mt-2">Create new users for the startpage application</p>
            </div>

            <!-- Create User Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New User</h2>
                
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?= htmlspecialchars($username ?? '') ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter username"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter password"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Confirm password"
                            required
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md transition-colors"
                    >
                        Create User
                    </button>
                </form>
            </div>

            <!-- Reset Password Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Reset User Password</h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Select User
                        </label>
                        <select 
                            id="user_id" 
                            name="user_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                            New Password
                        </label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter new password"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="confirm_password_reset" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password_reset" 
                            name="confirm_password" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Confirm new password"
                            required
                        >
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                        <p class="text-sm text-yellow-800">
                            <strong>⚠️ Warning:</strong> This will immediately change the user's password. 
                            They will need to use the new password to log in.
                        </p>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-md transition-colors"
                    >
                        Reset Password
                    </button>
                </form>
            </div>

            <!-- Existing Users List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Existing Users</h2>
                
                <?php if (empty($users)): ?>
                    <p class="text-gray-500 text-center py-4">No users created yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Username
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($user['username']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $user['id'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex space-x-2">
                                                <!-- Password Reset Modal Trigger -->
                                                <button 
                                                    onclick="openPasswordResetModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                    class="text-blue-600 hover:text-blue-900 text-xs bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded"
                                                >
                                                    Reset Password
                                                </button>
                                                
                                                <!-- Delete User Modal Trigger -->
                                                <button 
                                                    onclick="openDeleteUserModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                    class="text-red-600 hover:text-red-900 text-xs bg-red-50 hover:bg-red-100 px-2 py-1 rounded"
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
            <div id="passwordResetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Reset Password</h3>
                        <p class="text-sm text-gray-600 mb-4">Reset password for user: <span id="resetUsername" class="font-medium"></span></p>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" id="resetUserId">
                            
                            <div>
                                <label for="modal_new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="modal_new_password" 
                                    name="new_password" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter new password"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="modal_confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="modal_confirm_password" 
                                    name="confirm_password" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Confirm new password"
                                    required
                                >
                            </div>
                            
                            <div class="flex space-x-3">
                                <button 
                                    type="submit" 
                                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-md transition-colors"
                                >
                                    Reset Password
                                </button>
                                <button 
                                    type="button" 
                                    onclick="closePasswordResetModal()"
                                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Delete User Modal -->
            <div id="deleteUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 class="text-lg font-semibold text-red-800 mb-4">Delete User</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Are you sure you want to delete user: <span id="deleteUsername" class="font-medium text-red-600"></span>?
                        </p>
                        <p class="text-sm text-red-600 mb-4">
                            <strong>Warning:</strong> This will permanently delete the user and all their data (pages, categories, bookmarks).
                        </p>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" id="deleteUserId">
                            
                            <div class="flex space-x-3">
                                <button 
                                    type="submit" 
                                    class="flex-1 bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-md transition-colors"
                                >
                                    Delete User
                                </button>
                                <button 
                                    type="button" 
                                    onclick="closeDeleteUserModal()"
                                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openPasswordResetModal(userId, username) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            document.getElementById('passwordResetModal').classList.remove('hidden');
        }
        
        function closePasswordResetModal() {
            document.getElementById('passwordResetModal').classList.add('hidden');
        }
        
        function openDeleteUserModal(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteUserModal').classList.remove('hidden');
        }
        
        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('fixed')) {
                event.target.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 