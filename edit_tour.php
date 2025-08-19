<?php
ob_start();
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}



// Get user by ID
function getUserById($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Get tour by ID
function getTourById($tour_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT t.*, u.name as creator_name, 
               (SELECT COUNT(*) FROM tour_members WHERE tour_id = t.id AND status = 'confirmed') as member_count
        FROM tours t 
        LEFT JOIN users u ON t.created_by = u.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$tour_id]);
    return $stmt->fetch();
}

// Update tour function
function updateTour($tour_id, $data) {
    $pdo = getDBConnection();
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    $values[] = $tour_id;
    
    $sql = "UPDATE tours SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute($values);
}

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Controller logic starts here
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$user_role = $user['role'];

$error = '';
$success = '';
$tour = null;

// Get tour ID from URL
$tour_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tour_id) {
    header('Location: tours.php');
    exit();
}

// Get tour data
$tour = getTourById($tour_id);

if (!$tour) {
    header('Location: tours.php');
    exit();
}

// Check if user is the creator or admin
if ($tour['created_by'] != $user_id && $user_role !== 'admin') {
    header('Location: tours.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $destination = sanitizeInput($_POST['destination'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $budget = (float)($_POST['budget'] ?? 0);
    $max_members = (int)($_POST['max_members'] ?? 1);
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($title) || empty($destination) || empty($start_date) || empty($end_date)) {
        $error = 'Please fill in all required fields.';
    } elseif ($start_date >= $end_date) {
        $error = 'End date must be after start date.';
    } elseif ($budget < 0) {
        $error = 'Budget cannot be negative.';
    } elseif ($max_members < 1) {
        $error = 'Maximum members must be at least 1.';
    } else {
        // Create tour data array
        $tour_data = [
            'title' => $title,
            'description' => $description,
            'destination' => $destination,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'budget' => $budget,
            'max_members' => $max_members,
            'status' => $status
        ];
        
        // Attempt to update tour
        if (updateTour($tour_id, $tour_data)) {
            $success = 'Tour updated successfully!';
            // Refresh tour data
            $tour = getTourById($tour_id);
        } else {
            $error = 'Failed to update tour. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tour - Agun Riderzz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
                        <a class="nav-link active" href="tours.php">Tours</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php">Members</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>
                        <i class="fas fa-edit me-2"></i>Edit Tour
                    </h2>
                    <a href="tours.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Tours
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
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

        <!-- Edit Form -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-route me-2"></i>Tour Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="title" class="form-label">Tour Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($tour['title']); ?>" 
                                           maxlength="200" required>
                                    <div class="form-text">Maximum 200 characters</div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="4" placeholder="Describe the tour..."><?php echo htmlspecialchars($tour['description']); ?></textarea>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="destination" class="form-label">Destination *</label>
                                    <input type="text" class="form-control" id="destination" name="destination" 
                                           value="<?php echo htmlspecialchars($tour['destination']); ?>" 
                                           maxlength="200" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" <?php echo $tour['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="active" <?php echo $tour['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="completed" <?php echo $tour['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $tour['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $tour['start_date']; ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date *</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $tour['end_date']; ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="budget" class="form-label">Budget (৳)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" 
                                           value="<?php echo $tour['budget']; ?>" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="max_members" class="form-label">Maximum Members</label>
                                    <input type="number" class="form-control" id="max_members" name="max_members" 
                                           value="<?php echo $tour['max_members']; ?>" 
                                           min="<?php echo $tour['member_count']; ?>" max="100">
                                    <div class="form-text">
                                        Current members: <?php echo $tour['member_count']; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="tour_details.php?id=<?php echo $tour_id; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i>View Tour
                                </a>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Tour
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tour Info Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Tour Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Created by:</strong> <?php echo htmlspecialchars($tour['creator_name']); ?></p>
                                <p><strong>Created on:</strong> <?php echo formatDateTime($tour['created_at']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current members:</strong> <?php echo $tour['member_count']; ?>/<?php echo $tour['max_members']; ?></p>
                                <p><strong>Duration:</strong> <?php echo date_diff(date_create($tour['start_date']), date_create($tour['end_date']))->days + 1; ?> days</p>
                            </div>
                        </div>
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
