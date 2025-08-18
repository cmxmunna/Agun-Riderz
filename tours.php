<?php
session_start();
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$user_role = $user['role'];

// Handle tour actions
if ($_POST) {
    if (isset($_POST['join_tour'])) {
        $tour_id = (int)$_POST['tour_id'];
        if (joinTour($tour_id, $user_id)) {
            $success = 'Successfully joined the tour!';
        } else {
            $error = 'Failed to join tour or already joined.';
        }
    } elseif (isset($_POST['leave_tour'])) {
        $tour_id = (int)$_POST['tour_id'];
        if (leaveTour($tour_id, $user_id)) {
            $success = 'Successfully left the tour.';
        } else {
            $error = 'Failed to leave tour.';
        }
    } elseif (isset($_POST['delete_tour']) && $user_role == 'admin') {
        $tour_id = (int)$_POST['tour_id'];
        if (deleteTour($tour_id)) {
            $success = 'Tour deleted successfully.';
        } else {
            $error = 'Failed to delete tour.';
        }
    }
}

// Get tours based on user role
if ($user_role == 'admin') {
    $tours = getAllTours();
} else {
    $tours = getAllTours(); // Members can see all tours but with limited actions
}

$user_tours = getUserTours($user_id);
$user_tour_ids = array_column($user_tours, 'id');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tours - Agun Riderzz</title>
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
                    <?php if ($user_role == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php">Members</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                    <?php endif; ?>
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
                        <i class="fas fa-route me-2"></i>Tours
                    </h2>
                    <?php if ($user_role == 'admin'): ?>
                    <a href="create_tour.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create New Tour
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tours Grid -->
        <div class="row">
            <?php if (empty($tours)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-route fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No tours available</h5>
                            <p class="text-muted">Check back later for new tours!</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($tours as $tour): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($tour['title']); ?></h5>
                                <span class="badge bg-<?php echo $tour['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($tour['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <p class="card-text text-muted mb-3">
                                <?php echo htmlspecialchars($tour['description']); ?>
                            </p>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>Destination
                                    </small>
                                    <p class="mb-0"><?php echo htmlspecialchars($tour['destination']); ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Duration
                                    </small>
                                    <p class="mb-0">
                                        <?php echo formatDate($tour['start_date']); ?> - <?php echo formatDate($tour['end_date']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>Members
                                    </small>
                                    <p class="mb-0"><?php echo $tour['member_count']; ?>/<?php echo $tour['max_members']; ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-money-bill-wave me-1"></i>Budget
                                    </small>
                                    <p class="mb-0">৳<?php echo number_format($tour['budget']); ?></p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>Created by
                                </small>
                                <p class="mb-0"><?php echo htmlspecialchars($tour['creator_name']); ?></p>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="tour_details.php?id=<?php echo $tour['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                
                                <div class="btn-group">
                                    <?php if (in_array($tour['id'], $user_tour_ids)): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tour_id" value="<?php echo $tour['id']; ?>">
                                            <button type="submit" name="leave_tour" class="btn btn-outline-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to leave this tour?')">
                                                <i class="fas fa-sign-out-alt me-1"></i>Leave
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tour_id" value="<?php echo $tour['id']; ?>">
                                            <button type="submit" name="join_tour" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-sign-in-alt me-1"></i>Join
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($user_role == 'admin'): ?>
                                        <a href="edit_tour.php?id=<?php echo $tour['id']; ?>" class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tour_id" value="<?php echo $tour['id']; ?>">
                                            <button type="submit" name="delete_tour" class="btn btn-outline-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this tour? This action cannot be undone.')">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- My Tours Section -->
        <?php if (!empty($user_tours)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">
                    <i class="fas fa-user-check me-2"></i>My Tours
                </h3>
                
                <div class="row">
                    <?php foreach ($user_tours as $tour): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card h-100 border-success">
                            <div class="card-header bg-success text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($tour['title']); ?></h6>
                                    <span class="badge bg-light text-success">
                                        <?php echo ucfirst($tour['member_status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <p class="card-text text-muted mb-3">
                                    <?php echo htmlspecialchars($tour['description']); ?>
                                </p>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>Destination
                                        </small>
                                        <p class="mb-0"><?php echo htmlspecialchars($tour['destination']); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>Duration
                                        </small>
                                        <p class="mb-0">
                                            <?php echo formatDate($tour['start_date']); ?> - <?php echo formatDate($tour['end_date']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <a href="tour_details.php?id=<?php echo $tour['id']; ?>" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <a href="expenses.php?tour_id=<?php echo $tour['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-money-bill-wave me-1"></i>Expenses
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
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