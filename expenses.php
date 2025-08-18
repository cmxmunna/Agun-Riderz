<?php
session_start();
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$user_role = $user['role'];

// Get tour filter
$tour_filter = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : null;

// Handle expense actions
if ($_POST) {
    if (isset($_POST['add_expense'])) {
        $expense_data = [
            'tour_id' => !empty($_POST['tour_id']) ? (int)$_POST['tour_id'] : null,
            'user_id' => $user_id,
            'category' => sanitizeInput($_POST['category']),
            'description' => sanitizeInput($_POST['description']),
            'amount' => (float)$_POST['amount'],
            'date' => $_POST['date']
        ];
        
        // Handle file upload
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
            $uploaded_file = uploadImage($_FILES['receipt'], 'uploads/receipts/');
            if ($uploaded_file) {
                $expense_data['receipt_image'] = $uploaded_file;
            }
        }
        
        if (createExpense($expense_data)) {
            $success = 'Expense added successfully!';
        } else {
            $error = 'Failed to add expense.';
        }
    } elseif (isset($_POST['approve_expense']) && $user_role == 'admin') {
        $expense_id = (int)$_POST['expense_id'];
        if (approveExpense($expense_id, $user_id)) {
            $success = 'Expense approved successfully!';
        } else {
            $error = 'Failed to approve expense.';
        }
    } elseif (isset($_POST['reject_expense']) && $user_role == 'admin') {
        $expense_id = (int)$_POST['expense_id'];
        if (rejectExpense($expense_id, $user_id)) {
            $success = 'Expense rejected successfully!';
        } else {
            $error = 'Failed to reject expense.';
        }
    } elseif (isset($_POST['delete_expense'])) {
        $expense_id = (int)$_POST['expense_id'];
        $expense = getExpenseById($expense_id);
        
        // Only allow deletion if user owns the expense or is admin
        if ($expense && ($expense['user_id'] == $user_id || $user_role == 'admin')) {
            if (deleteExpense($expense_id)) {
                $success = 'Expense deleted successfully!';
            } else {
                $error = 'Failed to delete expense.';
            }
        } else {
            $error = 'You are not authorized to delete this expense.';
        }
    }
}

// Get expenses based on filters
if ($tour_filter) {
    $expenses = getAllExpenses($tour_filter);
    $tour = getTourById($tour_filter);
} else {
    $expenses = getAllExpenses();
    $tour = null;
}

// Get user's tours for the add expense form
$user_tours = getUserTours($user_id);
$all_tours = getAllTours(); // For admin to see all tours

// Calculate totals
$total_expenses = 0;
$approved_expenses = 0;
$pending_expenses = 0;

foreach ($expenses as $expense) {
    $total_expenses += $expense['amount'];
    if ($expense['status'] == 'approved') {
        $approved_expenses += $expense['amount'];
    } elseif ($expense['status'] == 'pending') {
        $pending_expenses += $expense['amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Agun Riderzz</title>
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
                        <a class="nav-link active" href="expenses.php">Expenses</a>
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
                        <i class="fas fa-money-bill-wave me-2"></i>Expenses
                        <?php if ($tour): ?>
                            - <?php echo htmlspecialchars($tour['title']); ?>
                        <?php endif; ?>
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="fas fa-plus me-2"></i>Add Expense
                    </button>
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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-money-bill-wave fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">৳<?php echo number_format($total_expenses); ?></h5>
                        <p class="card-text">Total Expenses</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h5 class="card-title">৳<?php echo number_format($approved_expenses); ?></h5>
                        <p class="card-text">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">৳<?php echo number_format($pending_expenses); ?></h5>
                        <p class="card-text">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-list fa-2x text-info mb-2"></i>
                        <h5 class="card-title"><?php echo count($expenses); ?></h5>
                        <p class="card-text">Total Items</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="tour_filter" class="form-label">Filter by Tour</label>
                                <select class="form-select" id="tour_filter" name="tour_id" onchange="this.form.submit()">
                                    <option value="">All Tours</option>
                                    <?php foreach ($all_tours as $t): ?>
                                        <option value="<?php echo $t['id']; ?>" <?php echo $tour_filter == $t['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <a href="expenses.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Clear Filter
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Expense List
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($expenses)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No expenses found</h5>
                        <p class="text-muted">Add your first expense to get started!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Tour</th>
                                    <th>Added By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo formatDate($expense['date']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($expense['description']); ?>
                                        <?php if ($expense['receipt_image']): ?>
                                            <a href="uploads/receipts/<?php echo $expense['receipt_image']; ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-info ms-2">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($expense['category']); ?></span>
                                    </td>
                                    <td class="fw-bold">৳<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td>
                                        <?php if ($expense['tour_title']): ?>
                                            <a href="tour_details.php?id=<?php echo $expense['tour_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($expense['tour_title']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['user_name']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($expense['status']) {
                                            case 'approved':
                                                $status_class = 'success';
                                                $status_text = 'Approved';
                                                break;
                                            case 'rejected':
                                                $status_class = 'danger';
                                                $status_text = 'Rejected';
                                                break;
                                            default:
                                                $status_class = 'warning';
                                                $status_text = 'Pending';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user_role == 'admin' && $expense['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                                    <button type="submit" name="approve_expense" class="btn btn-outline-success" 
                                                            onclick="return confirm('Approve this expense?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                                    <button type="submit" name="reject_expense" class="btn btn-outline-danger" 
                                                            onclick="return confirm('Reject this expense?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($expense['user_id'] == $user_id || $user_role == 'admin'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                                    <button type="submit" name="delete_expense" class="btn btn-outline-danger" 
                                                            onclick="return confirm('Delete this expense? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Expense
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="add_expense" value="1">
                        
                        <div class="mb-3">
                            <label for="tour_id" class="form-label">Tour (Optional)</label>
                            <select class="form-select" id="tour_id" name="tour_id">
                                <option value="">General Expense</option>
                                <?php foreach ($user_tours as $user_tour): ?>
                                    <option value="<?php echo $user_tour['id']; ?>" <?php echo $tour_filter == $user_tour['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user_tour['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Fuel">Fuel</option>
                                <option value="Food">Food</option>
                                <option value="Accommodation">Accommodation</option>
                                <option value="Transport">Transport</option>
                                <option value="Entertainment">Entertainment</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required 
                                      placeholder="Describe the expense..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (৳)</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="receipt" class="form-label">Receipt (Optional)</label>
                            <input type="file" class="form-control" id="receipt" name="receipt" 
                                   accept="image/*">
                            <div class="form-text">Upload a photo of the receipt</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Expense
                        </button>
                    </div>
                </form>
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