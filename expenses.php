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

// Get all tours
function getAllTours() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT t.*, u.name as creator_name,
               (SELECT COUNT(*) FROM tour_members WHERE tour_id = t.id AND status = 'confirmed') as member_count
        FROM tours t 
        LEFT JOIN users u ON t.created_by = u.id 
        ORDER BY t.start_date DESC
    ");
    return $stmt->fetchAll();
}

// Get all expenses
function getAllExpenses($tour_id = null) {
    $pdo = getDBConnection();
    
    if ($tour_id) {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as user_name, t.title as tour_title
            FROM expenses e 
            LEFT JOIN users u ON e.user_id = u.id 
            LEFT JOIN tours t ON e.tour_id = t.id 
            WHERE e.tour_id = ?
            ORDER BY e.date DESC
        ");
        $stmt->execute([$tour_id]);
    } else {
        $stmt = $pdo->query("
            SELECT e.*, u.name as user_name, t.title as tour_title
            FROM expenses e 
            LEFT JOIN users u ON e.user_id = u.id 
            LEFT JOIN tours t ON e.tour_id = t.id 
            ORDER BY e.date DESC
        ");
    }
    
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

// Create expense function
function createExpense($data) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO expenses (tour_id, user_id, category, description, amount, date, receipt_image) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $data['tour_id'],
        $data['user_id'],
        $data['category'],
        $data['description'],
        $data['amount'],
        $data['date'],
        $data['receipt_image'] ?? null
    ]);
}

// Update expense function
function updateExpense($expense_id, $data) {
    $pdo = getDBConnection();
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    $values[] = $expense_id;
    
    $sql = "UPDATE expenses SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute($values);
}

// Delete expense function
function deleteExpense($expense_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    return $stmt->execute([$expense_id]);
}

// Approve expense function
function approveExpense($expense_id, $approved_by) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE expenses 
        SET status = 'approved', approved_by = ? 
        WHERE id = ?
    ");
    return $stmt->execute([$approved_by, $expense_id]);
}

// Reject expense function
function rejectExpense($expense_id, $approved_by) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE expenses 
        SET status = 'rejected', approved_by = ? 
        WHERE id = ?
    ");
    return $stmt->execute([$approved_by, $expense_id]);
}

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format currency function
function formatCurrency($amount) {
    return '৳' . number_format($amount, 2);
}

// Format date function
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Controller logic starts here
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$user_role = $user['role'];

$error = '';
$success = '';

// Get tour filter
$tour_filter = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : null;

// Handle expense actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_expense'])) {
        // Handle expense creation
        $tour_id = (int)($_POST['tour_id'] ?? 0);
        $category = sanitizeInput($_POST['category'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if (empty($category) || empty($description) || $amount <= 0) {
            $error = 'Please fill in all required fields with valid values.';
        } else {
            $expense_data = [
                'tour_id' => $tour_id,
                'user_id' => $user_id,
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'date' => $date
            ];
            
            if (createExpense($expense_data)) {
                $success = 'Expense added successfully!';
            } else {
                $error = 'Failed to add expense. Please try again.';
            }
        }
    } elseif (isset($_POST['update_expense'])) {
        // Handle expense update
        $expense_id = (int)($_POST['expense_id'] ?? 0);
        $category = sanitizeInput($_POST['category'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if (empty($category) || empty($description) || $amount <= 0) {
            $error = 'Please fill in all required fields with valid values.';
        } else {
            $expense_data = [
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'date' => $date
            ];
            
            if (updateExpense($expense_id, $expense_data)) {
                $success = 'Expense updated successfully!';
            } else {
                $error = 'Failed to update expense. Please try again.';
            }
        }
    } elseif (isset($_POST['delete_expense'])) {
        // Handle expense deletion
        $expense_id = (int)($_POST['expense_id'] ?? 0);
        
        if (deleteExpense($expense_id)) {
            $success = 'Expense deleted successfully!';
        } else {
            $error = 'Failed to delete expense. Please try again.';
        }
    } elseif (isset($_POST['approve_expense']) && $user_role == 'admin') {
        // Handle expense approval
        $expense_id = (int)($_POST['expense_id'] ?? 0);
        
        if (approveExpense($expense_id, $user_id)) {
            $success = 'Expense approved successfully!';
        } else {
            $error = 'Failed to approve expense. Please try again.';
        }
    } elseif (isset($_POST['reject_expense']) && $user_role == 'admin') {
        // Handle expense rejection
        $expense_id = (int)($_POST['expense_id'] ?? 0);
        
        if (rejectExpense($expense_id, $user_id)) {
            $success = 'Expense rejected successfully!';
        } else {
            $error = 'Failed to reject expense. Please try again.';
        }
    }
}

// Get expenses based on user role and filters
if ($user_role == 'admin') {
    $expenses = getAllExpenses($tour_filter);
} else {
    $expenses = getUserExpenses($user_id);
}

// Get all tours for filter dropdown
$tours = getAllTours();
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
                        <?php if ($tour_filter): ?>
                            - <?php echo htmlspecialchars($tours[$tour_filter - 1]['title']); ?>
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
                                    <?php foreach ($tours as $t): ?>
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
                        <input type="hidden" name="create_expense" value="1">
                        
                        <div class="mb-3">
                            <label for="tour_id" class="form-label">Tour (Optional)</label>
                            <select class="form-select" id="tour_id" name="tour_id">
                                <option value="">General Expense</option>
                                <?php foreach ($tours as $tour): ?>
                                    <option value="<?php echo $tour['id']; ?>" <?php echo $tour_filter == $tour['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tour['title']); ?>
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