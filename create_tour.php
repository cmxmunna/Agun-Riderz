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

// Create tour function
function createTour($data) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO tours (title, description, destination, start_date, end_date, budget, max_members, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $data['title'],
        $data['description'],
        $data['destination'],
        $data['start_date'],
        $data['end_date'],
        $data['budget'],
        $data['max_members'],
        $data['created_by']
    ]);
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
            'created_by' => $user_id
        ];
        
        // Attempt to create tour
        if (createTour($tour_data)) {
            $success = 'Tour created successfully!';
            // Clear form data
            $_POST = array();
        } else {
            $error = 'Failed to create tour. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tour - Agun Riderzz</title>
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
                        <a class="nav-link" href="tours.php">Tours</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php">Members</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>Admin
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-plus me-2"></i>Create New Tour
                    </h2>
                    <a href="tours.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Tours
                    </a>
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

                <!-- Create Tour Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-route me-2"></i>Tour Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading me-2"></i>Tour Title *
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Please provide a tour title.
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="destination" class="form-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Destination *
                                    </label>
                                    <input type="text" class="form-control" id="destination" name="destination" 
                                           value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Please provide a destination.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Describe the tour details, itinerary, and highlights..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">
                                        <i class="fas fa-calendar-plus me-2"></i>Start Date *
                                    </label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : ''; ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">
                                        Please select a start date.
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">
                                        <i class="fas fa-calendar-minus me-2"></i>End Date *
                                    </label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">
                                        Please select an end date.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="budget" class="form-label">
                                        <i class="fas fa-money-bill-wave me-2"></i>Budget (৳)
                                    </label>
                                    <input type="number" class="form-control" id="budget" name="budget" 
                                           value="<?php echo isset($_POST['budget']) ? $_POST['budget'] : '0'; ?>" 
                                           min="0" step="0.01">
                                    <div class="form-text">Estimated total cost for the tour</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="max_members" class="form-label">
                                        <i class="fas fa-users me-2"></i>Maximum Members
                                    </label>
                                    <input type="number" class="form-control" id="max_members" name="max_members" 
                                           value="<?php echo isset($_POST['max_members']) ? $_POST['max_members'] : '20'; ?>" 
                                           min="1" max="100">
                                    <div class="form-text">Maximum number of participants</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-toggle-on me-2"></i>Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <div class="form-text">Set tour status</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="tours.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Tour
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-question-circle me-2"></i>Tips for Creating Tours
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Provide a clear and descriptive title for the tour
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Include detailed description with itinerary and highlights
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Set realistic budget and maximum member limits
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Choose appropriate dates with enough planning time
                            </li>
                            <li>
                                <i class="fas fa-check text-success me-2"></i>
                                Start with "Draft" status and change to "Active" when ready
                            </li>
                        </ul>
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
    
    <script>
        // Date validation
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_date');
            
            if (startDate) {
                endDateInput.min = startDate;
                if (endDateInput.value && endDateInput.value <= startDate) {
                    endDateInput.value = '';
                }
            }
        });
    </script>
</body>
</html> 