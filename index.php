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

// Get upcoming tours
function getUpcomingTours() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT t.*, u.name as creator_name,
               (SELECT COUNT(*) FROM tour_members WHERE tour_id = t.id AND status = 'confirmed') as member_count
        FROM tours t 
        LEFT JOIN users u ON t.created_by = u.id 
        WHERE t.start_date >= CURDATE() AND t.status = 'active'
        ORDER BY t.start_date ASC
        LIMIT 6
    ");
    $stmt->execute();
    return $stmt->fetchAll();
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

// Get recent expenses
function getRecentExpenses($limit = 5) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as user_name, t.title as tour_title
        FROM expenses e 
        LEFT JOIN users u ON e.user_id = u.id 
        LEFT JOIN tours t ON e.tour_id = t.id 
        ORDER BY e.date DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
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

// Get total members count
function getTotalMembers() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
    $result = $stmt->fetch();
    return $result['count'];
}

// Get total tours count
function getTotalTours() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tours");
    $result = $stmt->fetch();
    return $result['count'];
}

// Get total expenses count
function getTotalExpenses() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM expenses");
    $result = $stmt->fetch();
    return $result['count'];
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

// Get dashboard data
$upcoming_tours = getUpcomingTours();
$user_tours = getUserTours($user_id);
$recent_expenses = getRecentExpenses(5);
$user_total_expenses = getUserTotalExpenses($user_id);

// Get statistics for admin
if ($user_role == 'admin') {
    $total_members = getTotalMembers();
    $total_tours = getTotalTours();
    $total_expenses = getTotalExpenses();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agun Riderzz - Tour Management</title>
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
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tours.php">Tours</a>
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body">
                        <h2 class="card-title">
                            <i class="fas fa-motorcycle me-2"></i>Welcome, <?php echo htmlspecialchars($user['name']); ?>!
                        </h2>
                        <p class="card-text">Manage your tours and track expenses with Agun Riderzz</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-route fa-2x text-primary mb-2"></i>
                        <h5 class="card-title"><?php echo count($user_tours); ?></h5>
                        <p class="card-text">My Tours</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt fa-2x text-success mb-2"></i>
                        <h5 class="card-title"><?php echo count($upcoming_tours); ?></h5>
                        <p class="card-text">Upcoming Tours</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-money-bill-wave fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">৳<?php echo number_format($user_total_expenses); ?></h5>
                        <p class="card-text">Total Expenses</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-info mb-2"></i>
                        <h5 class="card-title"><?php echo getTotalMembers(); ?></h5>
                        <p class="card-text">Total Members</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- Upcoming Tours -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-route me-2"></i>Upcoming Tours
                        </h5>
                        <?php if ($user_role == 'admin'): ?>
                        <a href="create_tour.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Create Tour
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_tours)): ?>
                            <p class="text-muted text-center">No upcoming tours</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($upcoming_tours as $tour): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 border-primary">
                                        <div class="card-body">
                                            <h6 class="card-title text-primary"><?php echo htmlspecialchars($tour['title']); ?></h6>
                                            <p class="card-text small">
                                                <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($tour['start_date'])); ?><br>
                                                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($tour['destination']); ?><br>
                                                <i class="fas fa-users me-1"></i><?php echo $tour['member_count']; ?> members
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?php echo $tour['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($tour['status']); ?>
                                                </span>
                                                <a href="tour_details.php?id=<?php echo $tour['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Recent Expenses -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>Recent Expenses
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_expenses)): ?>
                            <p class="text-muted small">No recent expenses</p>
                        <?php else: ?>
                            <?php foreach ($recent_expenses as $expense): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <small class="text-muted"><?php echo htmlspecialchars($expense['description']); ?></small><br>
                                    <small class="text-muted"><?php echo date('M d', strtotime($expense['date'])); ?></small>
                                </div>
                                <span class="text-primary">৳<?php echo number_format($expense['amount']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Announcements -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bullhorn me-2"></i>Announcements
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($announcements)): ?>
                            <p class="text-muted small">No announcements</p>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                            <div class="mb-3">
                                <h6 class="text-primary"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                <p class="small text-muted"><?php echo htmlspecialchars($announcement['content']); ?></p>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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