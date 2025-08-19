<?php
session_start();
require_once 'includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

$error = '';
$success = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update basic profile info
    if (isset($_POST['update_profile'])) {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');

        if (empty($name) || empty($email) || empty($phone)) {
            $error = 'Please fill in all fields';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address';
        } elseif (!validatePhone($phone)) {
            $error = 'Please enter a valid phone number';
        } else {
            // Check for unique email if changed
            if (strtolower($email) !== strtolower($user['email'])) {
                $existingByEmail = getUserByEmail($email);
                if ($existingByEmail && (int)$existingByEmail['id'] !== (int)$userId) {
                    $error = 'Email is already in use by another account';
                }
            }

            // Check for unique phone if changed
            if (!$error && $phone !== $user['phone']) {
                $existingByPhone = getUserByPhone($phone);
                if ($existingByPhone && (int)$existingByPhone['id'] !== (int)$userId) {
                    $error = 'Phone number is already in use by another account';
                }
            }

            if (!$error) {
                $updateData = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                ];
                if (updateUser($userId, $updateData)) {
                    $success = 'Profile updated successfully';
                    $user = getUserById($userId); // refresh data
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        }
    }

    // Change password
    if (isset($_POST['change_password']) && !$error) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please fill in all password fields';
        } elseif (!password_verify($currentPassword, $user['password'] ?? '')) {
            $error = 'Current password is incorrect';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            if (updateUser($userId, ['password' => $hashed])) {
                $success = 'Password updated successfully';
                $user = getUserById($userId);
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Agun Riderzz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .profile-card .form-label { font-weight: 600; }
    </style>
    <script>
        // Password toggle buttons (eye / eye-slash)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-password').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const input = document.querySelector(btn.getAttribute('data-target'));
                    if (!input) return;
                    const icon = btn.querySelector('i');
                    const hidden = input.type === 'password';
                    input.type = hidden ? 'text' : 'password';
                    if (icon) {
                        icon.classList.toggle('fa-eye', !hidden);
                        icon.classList.toggle('fa-eye-slash', hidden);
                    }
                    btn.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
                    btn.title = hidden ? 'Hide password' : 'Show password';
                });
            });
        });
    </script>
    <?php /* Simple inline safeguards for older browsers to avoid FOUC */ ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-motorcycle me-2"></i>Agun Riderzz
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tours.php">Tours</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">Expenses</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Heading -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-user-circle me-2"></i>My Profile
                </h2>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Info -->
            <div class="col-lg-7 mb-4">
                <div class="card profile-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-id-badge me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <input type="hidden" name="update_profile" value="1">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required
                                       placeholder="01XXXXXXXXX"
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <input type="hidden" name="change_password" value="1">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#current_password" aria-label="Show password" title="Show password"><i class="far fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#new_password" aria-label="Show password" title="Show password"><i class="far fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#confirm_password" aria-label="Show password" title="Show password"><i class="far fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-unlock-alt me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2024 Agun Riderzz. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>


