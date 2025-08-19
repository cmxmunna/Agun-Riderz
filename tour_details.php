<?php
session_start();
require_once 'includes/functions.php';

requireLogin();

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

// Handle tour actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['join_tour'])) {
        $joinCheck = canJoinTour($tour_id, $user_id);
        if ($joinCheck['can_join']) {
            if (joinTour($tour_id, $user_id)) {
                if ($user_role == 'admin') {
                    $success = 'Successfully joined the tour!';
                } else {
                    $success = 'Join request sent successfully! Waiting for admin approval.';
                }
            } else {
                $error = 'Failed to join tour. Please try again.';
            }
        } else {
            $error = 'Cannot join tour: ' . $joinCheck['reason'];
        }
    } elseif (isset($_POST['leave_tour'])) {
        if (leaveTour($tour_id, $user_id)) {
            $success = 'Successfully left the tour.';
        } else {
            $error = 'Failed to leave tour. You may not be a member of this tour.';
        }
    } elseif (isset($_POST['approve_request']) && $user_role == 'admin') {
        $request_user_id = (int)($_POST['user_id'] ?? 0);
        if (approveJoinRequest($tour_id, $request_user_id, $user_id)) {
            $success = 'Join request approved successfully!';
        } else {
            $error = 'Failed to approve join request.';
        }
    } elseif (isset($_POST['reject_request']) && $user_role == 'admin') {
        $request_user_id = (int)($_POST['user_id'] ?? 0);
        if (rejectJoinRequest($tour_id, $request_user_id, $user_id)) {
            $success = 'Join request rejected successfully!';
        } else {
            $error = 'Failed to reject join request.';
        }
    } elseif (isset($_POST['delete_tour']) && $user_role == 'admin') {
        if (deleteTour($tour_id)) {
            header('Location: tours.php');
            exit();
        } else {
            $error = 'Failed to delete tour. The tour may have confirmed members or does not exist.';
        }
    }
    
    // Refresh tour data after actions
    if ($success || $error) {
        $tour = getTourById($tour_id);
        $tour_members = getTourMembers($tour_id);
    }
}

// Get tour members
$tour_members = getTourMembers($tour_id);

// Get tour expenses
$tour_expenses = getAllExpenses($tour_id);

// Calculate totals
$total_expenses = 0;
$approved_expenses = 0;
$pending_expenses = 0;

foreach ($tour_expenses as $expense) {
    $total_expenses += $expense['amount'];
    if ($expense['status'] == 'approved') {
        $approved_expenses += $expense['amount'];
    } elseif ($expense['status'] == 'pending') {
        $pending_expenses += $expense['amount'];
    }
}

// Check if user is a member of this tour
$user_is_member = false;
$user_member_status = '';
foreach ($tour_members as $member) {
    if ($member['id'] == $user_id) {
        $user_is_member = true;
        $user_member_status = $member['status'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tour['title']); ?> - Agun Riderzz</title>
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
                        <i class="fas fa-route me-2"></i><?php echo htmlspecialchars($tour['title']); ?>
                    </h2>
                    <div>
                        <?php if ($user_role == 'admin' && ($tour['status'] == 'active' || $tour['status'] == 'completed')): ?>
                            <a href="edit_tour.php?id=<?php echo $tour_id; ?>" class="btn btn-warning me-2">
                                <i class="fas fa-edit me-2"></i>Edit Tour
                            </a>
                        <?php endif; ?>
                        <a href="tours.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Tours
                        </a>
                    </div>
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

        <div class="row">
            <!-- Tour Details -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Tour Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Destination:</strong><br>
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <?php echo htmlspecialchars($tour['destination']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?php echo $tour['status'] == 'active' ? 'success' : ($tour['status'] == 'completed' ? 'info' : ($tour['status'] == 'cancelled' ? 'danger' : 'secondary')); ?>">
                                    <?php echo ucfirst($tour['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Start Date:</strong><br>
                                <i class="fas fa-calendar text-primary me-2"></i>
                                <?php echo formatDate($tour['start_date']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>End Date:</strong><br>
                                <i class="fas fa-calendar text-primary me-2"></i>
                                <?php echo formatDate($tour['end_date']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Duration:</strong><br>
                                <i class="fas fa-clock text-primary me-2"></i>
                                <?php echo date_diff(date_create($tour['start_date']), date_create($tour['end_date']))->days + 1; ?> days
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Budget:</strong><br>
                                <i class="fas fa-money-bill-wave text-primary me-2"></i>
                                ৳<?php echo number_format($tour['budget'], 2); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Members:</strong><br>
                                <i class="fas fa-users text-primary me-2"></i>
                                <?php echo $tour['member_count']; ?>/<?php echo $tour['max_members']; ?>
                                <?php 
                                $pending_count = count(getPendingJoinRequests($tour_id));
                                if ($pending_count > 0): 
                                ?>
                                <br><small class="text-warning">
                                    <i class="fas fa-clock me-1"></i><?php echo $pending_count; ?> pending approval
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Created by:</strong><br>
                                <i class="fas fa-user text-primary me-2"></i>
                                <?php echo htmlspecialchars($tour['creator_name']); ?>
                            </div>
                        </div>

                        <?php if ($tour['description']): ?>
                            <hr>
                            <div class="mb-3">
                                <strong>Description:</strong><br>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($tour['description'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Join/Leave Actions -->
                        <hr>
                        <?php if ($tour['status'] == 'active'): ?>
                            <?php if ($user_role == 'admin'): ?>
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Admin Note:</strong> As an admin, you can join tours directly without needing approval.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Member Note:</strong> Your join request will be reviewed by an admin before approval.
                                </div>
                            <?php endif; ?>
                        <?php elseif ($tour['status'] == 'cancelled'): ?>
                            <div class="alert alert-secondary mb-3">
                                <i class="fas fa-ban me-2"></i>
                                <strong>Tour Status:</strong> This tour is cancelled. No new members can join and the tour cannot be modified.
                            </div>
                        <?php elseif ($tour['status'] == 'completed'): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Tour Status:</strong> This tour is completed. No new members can join, but admins can still modify tour details.
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($user_is_member): ?>
                                    <?php if ($user_role == 'admin'): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-crown me-1"></i>Admin Member (Confirmed)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Member (<?php echo ucfirst($user_member_status); ?>)
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not a member</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php 
                                if (!$user_is_member && $tour['status'] == 'active') {
                                    $joinCheck = canJoinTour($tour_id, $user_id);
                                    if ($joinCheck['can_join']) { 
                                        if ($user_role == 'admin') { ?>
                                            <form method="POST" style="display: inline;">
                                                <button type="submit" name="join_tour" class="btn btn-success">
                                                    <i class="fas fa-plus me-2"></i>Join Tour Directly
                                                </button>
                                            </form>
                                        <?php } else { ?>
                                            <form method="POST" style="display: inline;">
                                                <button type="submit" name="join_tour" class="btn btn-success">
                                                    <i class="fas fa-paper-plane me-2"></i>Send Join Request
                                                </button>
                                            </form>
                                        <?php }
                                    } else { ?>
                                        <span class="text-muted"><?php echo $joinCheck['reason']; ?></span>
                                    <?php }
                                } elseif ($user_is_member && $tour['status'] == 'active') { ?>
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="leave_tour" class="btn btn-outline-danger" 
                                                onclick="return confirm('Are you sure you want to leave this tour?')">
                                            <i class="fas fa-sign-out-alt me-2"></i>Leave Tour
                                        </button>
                                    </form>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tour Members -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Tour Members (<?php echo count($tour_members); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tour_members)): ?>
                            <p class="text-muted text-center">No members have joined this tour yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <?php if ($user_role == 'admin'): ?>
                                            <th>Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tour_members as $member): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($member['name']); ?>
                                            </td>
                                            <td>
                                                <?php if ($member['role'] == 'admin'): ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-crown me-1"></i>Admin (Confirmed)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-<?php echo $member['status'] == 'confirmed' ? 'success' : ($member['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($member['status']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo formatDate($member['joined_at']); ?>
                                                </small>
                                            </td>
                                            <?php if ($user_role == 'admin' && $member['status'] == 'pending'): ?>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                        <button type="submit" name="approve_request" class="btn btn-success btn-sm" 
                                                                onclick="return confirm('Approve this join request?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                        <button type="submit" name="reject_request" class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Reject this join request?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <?php elseif ($user_role == 'admin'): ?>
                                            <td>-</td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Join Requests (Admin Only) -->
                <?php if ($user_role == 'admin'): ?>
                <?php 
                $pending_requests = getPendingJoinRequests($tour_id);
                if (!empty($pending_requests)): 
                ?>
                <div class="card mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Pending Join Requests (<?php echo count($pending_requests); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Requested</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($request['user_name']); ?>
                                        </td>
                                        <td>
                                            <small>
                                                <div><?php echo htmlspecialchars($request['email']); ?></div>
                                                <div class="text-muted"><?php echo htmlspecialchars($request['phone']); ?></div>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo formatDate($request['joined_at']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                                    <button type="submit" name="approve_request" class="btn btn-success btn-sm" 
                                                            onclick="return confirm('Approve <?php echo htmlspecialchars($request['user_name']); ?>\'s join request?')">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                                    <button type="submit" name="reject_request" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Reject <?php echo htmlspecialchars($request['user_id']); ?>\'s join request?')">
                                                        <i class="fas fa-times me-1"></i>Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Tour Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Tour Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border-end">
                                    <h4 class="text-primary"><?php echo $tour['member_count']; ?></h4>
                                    <small class="text-muted">Confirmed</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-success"><?php echo $tour['max_members'] - $tour['member_count']; ?></h4>
                                <small class="text-muted">Available</small>
                            </div>
                        </div>
                        <?php 
                        $pending_count = count(getPendingJoinRequests($tour_id));
                        if ($pending_count > 0): 
                        ?>
                        <div class="row text-center mt-2">
                            <div class="col-12">
                                <h6 class="text-warning">
                                    <i class="fas fa-clock me-1"></i><?php echo $pending_count; ?> Pending
                                </h6>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo ($tour['member_count'] / $tour['max_members']) * 100; ?>%">
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo round(($tour['member_count'] / $tour['max_members']) * 100); ?>% filled
                        </small>
                    </div>
                </div>

                <!-- Tour Expenses Summary -->
                <?php if (!empty($tour_expenses)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>Expenses Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Total Expenses:</small><br>
                            <strong class="text-primary">৳<?php echo number_format($total_expenses, 2); ?></strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Approved:</small><br>
                            <strong class="text-success">৳<?php echo number_format($approved_expenses, 2); ?></strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Pending:</small><br>
                            <strong class="text-warning">৳<?php echo number_format($pending_expenses, 2); ?></strong>
                        </div>
                        <div class="mt-3">
                            <a href="expenses.php?tour_id=<?php echo $tour_id; ?>" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-eye me-2"></i>View All Expenses
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Admin Actions -->
                <?php if ($user_role == 'admin'): ?>
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-tools me-2"></i>Admin Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($tour['status'] == 'active' || $tour['status'] == 'completed'): ?>
                                <a href="edit_tour.php?id=<?php echo $tour_id; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit me-2"></i>Edit Tour
                                </a>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="delete_tour" class="btn btn-danger btn-sm w-100" 
                                            onclick="return confirm('Are you sure you want to delete this tour? This action cannot be undone.')">
                                        <i class="fas fa-trash me-2"></i>Delete Tour
                                    </button>
                                </form>
                            <?php elseif ($tour['status'] == 'cancelled'): ?>
                                <span class="text-muted small text-center">
                                    <i class="fas fa-ban me-1"></i>
                                    Cancelled tours cannot be modified
                                </span>
                            <?php endif; ?>
                            
                            <?php 
                            $total_pending = count(getPendingJoinRequests());
                            if ($total_pending > 0): 
                            ?>
                            <a href="tours.php?show_pending=1" class="btn btn-info btn-sm">
                                <i class="fas fa-clock me-2"></i>View All Pending (<?php echo $total_pending; ?>)
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
