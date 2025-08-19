<?php
ob_start();
session_start();
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}



// Get user by email
function getUserByEmail($email) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

// Get user by phone
function getUserByPhone($phone) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetch();
}

// Create user function
function createUser($name, $email, $phone, $password = null, $facebook_id = null) {
    $pdo = getDBConnection();
    
    $hashed_password = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, password, facebook_id, role) 
        VALUES (?, ?, ?, ?, ?, 'member')
    ");
    
    return $stmt->execute([$name, $email, $phone, $hashed_password, $facebook_id]);
}

// Validate email function
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone function
function validatePhone($phone) {
    return preg_match('/^(\+880|880|0)?1[3456789]\d{8}$/', $phone);
}

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Controller logic starts here
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        // Handle login
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $user = getUserByEmail($email);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        }
    } elseif (isset($_POST['register'])) {
        // Handle registration
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!validatePhone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email already exists
            $existing_user = getUserByEmail($email);
            if ($existing_user) {
                $error = 'Email is already registered. Please use a different email or login.';
            } else {
                // Check if phone already exists
                $existing_phone = getUserByPhone($phone);
                if ($existing_phone) {
                    $error = 'Phone number is already registered. Please use a different phone number.';
                } else {
                    // Create user
                    if (createUser($name, $email, $phone, $password)) {
                        $success = 'Registration successful! Please login with your credentials.';
                        // Clear form data
                        $_POST = array();
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
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
    <title>Login - Agun Riderzz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <!-- Logo and Title -->
                <div class="text-center mb-4">
                    <i class="fas fa-motorcycle fa-3x text-primary mb-3"></i>
                    <h2 class="text-primary">Agun Riderzz</h2>
                    <p class="text-muted">Tour Management System</p>
                </div>

                <!-- Login/Register Tabs -->
                <div class="card shadow">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="authTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                    <i class="fas fa-user-plus me-2"></i>Register
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <!-- Error/Success Messages -->
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

                        <div class="tab-content" id="authTabsContent">
                            <!-- Login Tab -->
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="identifier" class="form-label">
                                            <i class="fas fa-envelope me-2"></i>Email or Phone
                                        </label>
                                        <input type="text" class="form-control" id="identifier" name="identifier" 
                                               value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-2"></i>Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password" aria-label="Show password" title="Show password"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login
                                        </button>
                                    </div>
                                </form>
                                
                                <!-- Facebook Login -->
                                <div class="text-center mt-3">
                                    <p class="text-muted">Or login with</p>
                                    <button class="btn btn-outline-primary" onclick="loginWithFacebook()">
                                        <i class="fab fa-facebook me-2"></i>Facebook
                                    </button>
                                </div>
                            </div>

                            <!-- Register Tab -->
                            <div class="tab-pane fade" id="register" role="tabpanel">
                                <form method="POST" action="">
                                    <input type="hidden" name="register" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="reg_name" class="form-label">
                                            <i class="fas fa-user me-2"></i>Full Name
                                        </label>
                                        <input type="text" class="form-control" id="reg_name" name="name" 
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_email" class="form-label">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                        <input type="email" class="form-control" id="reg_email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_phone" class="form-label">
                                            <i class="fas fa-phone me-2"></i>Phone Number
                                        </label>
                                        <input type="tel" class="form-control" id="reg_phone" name="phone" 
                                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                               placeholder="01XXXXXXXXX" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_password" class="form-label">
                                            <i class="fas fa-lock me-2"></i>Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="reg_password" name="password" 
                                                   minlength="6" required>
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#reg_password" aria-label="Show password" title="Show password"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_confirm_password" class="form-label">
                                            <i class="fas fa-lock me-2"></i>Confirm Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="reg_confirm_password" name="confirm_password" 
                                                   minlength="6" required>
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#reg_confirm_password" aria-label="Show password" title="Show password"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-user-plus me-2"></i>Register
                                        </button>
                                    </div>
                                </form>
                                
                                <!-- Facebook Registration -->
                                <div class="text-center mt-3">
                                    <p class="text-muted">Or register with</p>
                                    <button class="btn btn-outline-primary" onclick="registerWithFacebook()">
                                        <i class="fab fa-facebook me-2"></i>Facebook
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <p class="text-muted small">
                        &copy; 2024 Agun Riderzz. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    
    <!-- Facebook SDK -->
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
    <script>
        window.fbAsyncInit = function() {
            FB.init({
                appId: 'YOUR_FACEBOOK_APP_ID', // Replace with your Facebook App ID
                cookie: true,
                xfbml: true,
                version: 'v18.0'
            });
        };

        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));

        function loginWithFacebook() {
            FB.login(function(response) {
                if (response.authResponse) {
                    // Send to server for processing
                    window.location.href = 'facebook_login.php?access_token=' + response.authResponse.accessToken;
                }
            }, {scope: 'email,public_profile'});
        }

        function registerWithFacebook() {
            FB.login(function(response) {
                if (response.authResponse) {
                    // Send to server for processing
                    window.location.href = 'facebook_register.php?access_token=' + response.authResponse.accessToken;
                }
            }, {scope: 'email,public_profile'});
        }
    </script>
</body>
</html> 