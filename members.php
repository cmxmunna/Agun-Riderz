<?php
session_start();
require_once 'includes/functions.php';

requireAdmin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle member actions
if ($_POST) {
    if (isset($_POST['delete_member'])) {
        $member_id = (int)$_POST['member_id'];
        if ($member_id != $user_id) { // Prevent admin from deleting themselves
            if (deleteUser($member_id)) {
                $success = 'Member deleted successfully.';
            } else {
                $error = 'Failed to delete member.';
            }
        } else {
            $error = 'You cannot delete your own account.';
        }
    } elseif (isset($_POST['change_role'])) {
        $member_id = (int)$_POST['member_id'];
        $new_role = $_POST['new_role'];
        
        if ($member_id != $user_id) { // Prevent admin from changing their own role
            if (updateUser($member_id, ['role' => $new_role])) {
                $success = 'Member role updated successfully.';
            } else {
                $error = 'Failed to update member role.';
            }
        } else {
            $error = 'You cannot change your own role.';
        }
    }
}

// Get all members
$members = getAllUsers();
$total_members = count($members);
$admin_count = 0;
$member_count = 0;

foreach ($members as $member) {
    if ($member['role'] == 'admin') {
        $admin_count++;
    } else {
        $member_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - Agun Riderzz</title>
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
                        <a class="nav-link active" href="members.php">Members</a>
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
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>
                        <i class="fas fa-users me-2"></i>Members Management
                    </h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="exportMembers()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <button class="btn btn-primary" onclick="printMembers()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h5 class="card-title"><?php echo $total_members; ?></h5>
                        <p class="card-text">Total Members</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-user-shield fa-2x text-success mb-2"></i>
                        <h5 class="card-title"><?php echo $admin_count; ?></h5>
                        <p class="card-text">Admins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-user fa-2x text-info mb-2"></i>
                        <h5 class="card-title"><?php echo $member_count; ?></h5>
                        <p class="card-text">Regular Members</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="searchInput" class="form-label">Search Members</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, or phone...">
                    </div>
                    <div class="col-md-3">
                        <label for="roleFilter" class="form-label">Filter by Role</label>
                        <select class="form-select" id="roleFilter">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="member">Member</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Members List
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="membersTable">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                            <tr class="searchable-item" data-role="<?php echo $member['role']; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($member['profile_image']): ?>
                                            <img src="uploads/profiles/<?php echo $member['profile_image']; ?>" 
                                                 alt="Profile" class="rounded-circle me-2" width="40" height="40">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($member['name']); ?></strong>
                                            <?php if ($member['id'] == $user_id): ?>
                                                <span class="badge bg-primary ms-2">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($member['email']): ?>
                                            <div><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($member['email']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($member['phone']): ?>
                                            <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($member['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $member['role'] == 'admin' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($member['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo formatDate($member['created_at']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" data-bs-toggle="modal" 
                                                data-bs-target="#memberDetailsModal" 
                                                onclick="showMemberDetails(<?php echo htmlspecialchars(json_encode($member)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($member['id'] != $user_id): ?>
                                            <button class="btn btn-outline-warning" data-bs-toggle="modal" 
                                                    data-bs-target="#changeRoleModal" 
                                                    onclick="prepareRoleChange(<?php echo $member['id']; ?>, '<?php echo $member['role']; ?>', '<?php echo htmlspecialchars($member['name']); ?>')">
                                                <i class="fas fa-user-edit"></i>
                                            </button>
                                            
                                            <button class="btn btn-outline-danger" 
                                                    onclick="confirmDeleteMember(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Details Modal -->
    <div class="modal fade" id="memberDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Member Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="memberDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div class="modal fade" id="changeRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Change Member Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="member_id" id="roleChangeMemberId">
                        <input type="hidden" name="change_role" value="1">
                        
                        <p>Change role for: <strong id="roleChangeMemberName"></strong></p>
                        
                        <div class="mb-3">
                            <label for="new_role" class="form-label">New Role</label>
                            <select class="form-select" id="new_role" name="new_role" required>
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Changing a member's role will affect their permissions immediately.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteMemberName"></strong>?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. All member data will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="member_id" id="deleteMemberId">
                        <input type="hidden" name="delete_member" value="1">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Member
                        </button>
                    </form>
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
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#membersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Role filter
        document.getElementById('roleFilter').addEventListener('change', function() {
            const selectedRole = this.value;
            const rows = document.querySelectorAll('#membersTable tbody tr');
            
            rows.forEach(row => {
                const role = row.getAttribute('data-role');
                if (!selectedRole || role === selectedRole) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('roleFilter').value = '';
            
            const rows = document.querySelectorAll('#membersTable tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }

        function showMemberDetails(member) {
            const content = document.getElementById('memberDetailsContent');
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        ${member.profile_image ? 
                            `<img src="uploads/profiles/${member.profile_image}" alt="Profile" class="img-fluid rounded mb-3" style="max-width: 200px;">` :
                            `<div class="bg-secondary rounded d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 200px; height: 200px;">
                                <i class="fas fa-user fa-4x text-white"></i>
                            </div>`
                        }
                    </div>
                    <div class="col-md-8">
                        <h5>${member.name}</h5>
                        <p class="text-muted">Member since ${new Date(member.created_at).toLocaleDateString()}</p>
                        
                        <div class="row">
                            <div class="col-6">
                                <strong>Role:</strong><br>
                                <span class="badge bg-${member.role === 'admin' ? 'success' : 'secondary'}">${member.role}</span>
                            </div>
                            <div class="col-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-6">
                                <strong>Email:</strong><br>
                                ${member.email || 'Not provided'}
                            </div>
                            <div class="col-6">
                                <strong>Phone:</strong><br>
                                ${member.phone || 'Not provided'}
                            </div>
                        </div>
                        
                        ${member.facebook_id ? `
                        <div class="mt-3">
                            <strong>Facebook ID:</strong><br>
                            ${member.facebook_id}
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function prepareRoleChange(memberId, currentRole, memberName) {
            document.getElementById('roleChangeMemberId').value = memberId;
            document.getElementById('roleChangeMemberName').textContent = memberName;
            document.getElementById('new_role').value = currentRole === 'admin' ? 'member' : 'admin';
        }

        function confirmDeleteMember(memberId, memberName) {
            document.getElementById('deleteMemberId').value = memberId;
            document.getElementById('deleteMemberName').textContent = memberName;
            new bootstrap.Modal(document.getElementById('deleteMemberModal')).show();
        }

        function exportMembers() {
            const members = <?php echo json_encode($members); ?>;
            const csvData = members.map(member => ({
                Name: member.name,
                Email: member.email || '',
                Phone: member.phone || '',
                Role: member.role,
                'Joined Date': new Date(member.created_at).toLocaleDateString()
            }));
            
            exportToCSV(csvData, 'agun_riderzz_members.csv');
        }

        function printMembers() {
            printElement('membersTable');
        }
    </script>
</body>
</html> 