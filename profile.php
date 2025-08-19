<?php
ob_start();
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}



// Get user by ID
function getUserById($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Update user function
function updateUser($user_id, $data) {
    $pdo = getDBConnection();
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    $values[] = $user_id;
    
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute($values);
}

// Get user tours
function getUserTours($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT t.*, tm.status as member_status, u.name as creator_name
        FROM tours t 
        INNER JOIN tour_members tm ON t.id = tm.tour_id
        LEFT JOIN users u ON t.created_by = u.id 
        WHERE tm.user_id = ?
        ORDER BY t.start_date DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Get user expenses
function getUserExpenses($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.*, t.title as tour_title
        FROM expenses e 
        LEFT JOIN tours t ON e.tour_id = t.id 
        WHERE e.user_id = ?
        ORDER BY e.date DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Get user total expenses
function getUserTotalExpenses($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total 
        FROM expenses 
        WHERE user_id = ? AND status = 'approved'
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format date function
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format currency function
function formatCurrency($amount) {
    return '৳' . number_format($amount, 2);
}

// Controller logic starts here
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$user_role = $user['role'];

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required fields.';
        } else {
            $user_data = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ];
            
            if (updateUser($user_id, $user_data)) {
                $success = 'Profile updated successfully!';
                // Refresh user data
                $user = getUserById($user_id);
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirmation do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            if (updateUser($user_id, ['password' => $hashed_password])) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
}

// Get user's tours
$user_tours = getUserTours($user_id);

// Get user's expenses
$user_expenses = getUserExpenses($user_id);

// Get user's total expenses
$total_expenses = getUserTotalExpenses($user_id);
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


