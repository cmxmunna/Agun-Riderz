<?php
require_once 'config/database.php';

// User Functions
function getUserById($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getUserByEmail($email) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function getUserByPhone($phone) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetch();
}

function createUser($name, $email, $phone, $password = null, $facebook_id = null) {
    $pdo = getDBConnection();
    
    $hashed_password = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, password, facebook_id, role) 
        VALUES (?, ?, ?, ?, ?, 'member')
    ");
    
    return $stmt->execute([$name, $email, $phone, $hashed_password, $facebook_id]);
}

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

function getAllUsers() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function getTotalMembers() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
    $result = $stmt->fetch();
    return $result['count'];
}

// Tour Functions
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

function joinTour($tour_id, $user_id) {
    $pdo = getDBConnection();
    
    // Check if already joined
    $stmt = $pdo->prepare("SELECT id FROM tour_members WHERE tour_id = ? AND user_id = ?");
    $stmt->execute([$tour_id, $user_id]);
    
    if ($stmt->fetch()) {
        return false; // Already joined
    }
    
    $stmt = $pdo->prepare("INSERT INTO tour_members (tour_id, user_id, status) VALUES (?, ?, 'confirmed')");
    return $stmt->execute([$tour_id, $user_id]);
}

function leaveTour($tour_id, $user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM tour_members WHERE tour_id = ? AND user_id = ?");
    return $stmt->execute([$tour_id, $user_id]);
}

function getTourMembers($tour_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, tm.status, tm.joined_at
        FROM tour_members tm 
        INNER JOIN users u ON tm.user_id = u.id 
        WHERE tm.tour_id = ?
        ORDER BY tm.joined_at ASC
    ");
    $stmt->execute([$tour_id]);
    return $stmt->fetchAll();
}

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

function deleteTour($tour_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM tours WHERE id = ?");
    return $stmt->execute([$tour_id]);
}

// Expense Functions
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

function getExpenseById($expense_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as user_name, t.title as tour_title
        FROM expenses e 
        LEFT JOIN users u ON e.user_id = u.id 
        LEFT JOIN tours t ON e.tour_id = t.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$expense_id]);
    return $stmt->fetch();
}

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

function approveExpense($expense_id, $approved_by) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE expenses 
        SET status = 'approved', approved_by = ? 
        WHERE id = ?
    ");
    return $stmt->execute([$approved_by, $expense_id]);
}

function rejectExpense($expense_id, $approved_by) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE expenses 
        SET status = 'rejected', approved_by = ? 
        WHERE id = ?
    ");
    return $stmt->execute([$approved_by, $expense_id]);
}

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

function deleteExpense($expense_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    return $stmt->execute([$expense_id]);
}

// Announcement Functions
function createAnnouncement($title, $content, $created_by) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO announcements (title, content, created_by) 
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$title, $content, $created_by]);
}

function getAnnouncements($limit = 5) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as creator_name
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id 
        ORDER BY a.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getAllAnnouncements() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, u.name as creator_name
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id 
        ORDER BY a.created_at DESC
    ");
    return $stmt->fetchAll();
}

function deleteAnnouncement($announcement_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    return $stmt->execute([$announcement_id]);
}

function deleteUser($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$user_id]);
}

// Utility Functions
function formatCurrency($amount) {
    return '৳' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

function uploadImage($file, $directory = 'uploads/') {
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $target_path = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    
    return false;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^(\+880|880|0)?1[3456789]\d{8}$/', $phone);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Authentication Functions
function loginUser($email, $password) {
    $user = getUserByEmail($email);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

function loginWithPhone($phone, $password) {
    $user = getUserByPhone($phone);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?> 