<?php
session_start();

// Define PDO connection directly in admin.php
$host = 'localhost';
$dbname = 'expense_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin' || !isset($_SESSION['company_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get company info
$stmt = $pdo->prepare("SELECT * FROM companies WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
$company = $stmt->fetch();

// Get all users
$stmt = $pdo->prepare("SELECT * FROM users WHERE company_id = ? ORDER BY role, name");
$stmt->execute([$_SESSION['company_id']]);
$users = $stmt->fetchAll();

// Get approval workflows
$stmt = $pdo->prepare("SELECT * FROM approval_workflows WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
$workflows = $stmt->fetchAll();

// Get all expenses
$stmt = $pdo->prepare("
    SELECT e.*, u.name as employee_name 
    FROM expenses e 
    JOIN users u ON e.employee_id = u.id 
    WHERE u.company_id = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$_SESSION['company_id']]);
$expenses = $stmt->fetchAll();

// Get expense statistics
$totalExpenses = count($expenses);
$pendingExpenses = count(array_filter($expenses, fn($e) => $e['status'] === 'pending'));
$approvedExpenses = count(array_filter($expenses, fn($e) => $e['status'] === 'approved'));
$rejectedExpenses = count(array_filter($expenses, fn($e) => $e['status'] === 'rejected'));
$totalAmount = array_sum(array_column($expenses, 'converted_amount'));

// Get approvers for the workflow modal
$approvers = array_filter($users, function($u) {
    return in_array($u['role'], ['manager', 'admin']);
});
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Expense Management</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Welcome, <?php echo $_SESSION['name']; ?> (Admin)
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Alert Container -->
        <div id="alertContainer">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#dashboard" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="#users" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-users me-2"></i>User Management
                    </a>
                    <a href="#workflows" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-sitemap me-2"></i>Approval Workflows
                    </a>
                    <a href="#expenses" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-receipt me-2"></i>All Expenses
                    </a>
                    <a href="#company" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-building me-2"></i>Company Settings
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-3">Dashboard Overview</h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">Total Users</h5>
                                                <h3><?php echo count($users); ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-users fa-2x"></i>
                                            </div>
                                        </div>
                                        <small>Employees: <?php echo count(array_filter($users, fn($u) => $u['role'] === 'employee')); ?> | Managers: <?php echo count(array_filter($users, fn($u) => $u['role'] === 'manager')); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">Pending Expenses</h5>
                                                <h3><?php echo $pendingExpenses; ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                        <small>Awaiting approval</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">Total Expenses</h5>
                                                <h3><?php echo $totalExpenses; ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-receipt fa-2x"></i>
                                            </div>
                                        </div>
                                        <small>Approved: <?php echo $approvedExpenses; ?> | Rejected: <?php echo $rejectedExpenses; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">Total Amount</h5>
                                                <h3><?php echo number_format($totalAmount, 2); ?></h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-dollar-sign fa-2x"></i>
                                            </div>
                                        </div>
                                        <small><?php echo $company['currency']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activities -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Recent Expenses</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($expenses) > 0): ?>
                                            <div class="list-group list-group-flush">
                                                <?php foreach (array_slice($expenses, 0, 5) as $expense): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($expense['employee_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($expense['category']); ?> - <?php echo $expense['amount']; ?> <?php echo $expense['currency']; ?></small>
                                                    </div>
                                                    <span class="badge bg-<?php echo $expense['status'] === 'approved' ? 'success' : ($expense['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($expense['status']); ?>
                                                    </span>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No expenses found.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Approval Workflows</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($workflows) > 0): ?>
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($workflows as $workflow): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($workflow['name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo ucfirst($workflow['type']); ?> workflow</small>
                                                    </div>
                                                    <span class="badge bg-info">Active</span>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No workflows created yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Management Tab -->
                    <div class="tab-pane fade" id="users">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>User Management</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-1"></i>Add User
                            </button>
                        </div>
                        
                        <!-- Search and Filter -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="roleFilter">
                                    <option value="">All Roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="employee">Employee</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Organization</th>
                                        <th>Country</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                    <tr id="user-row-<?php echo $user['id']; ?>">
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user['role'] === 'admin' ? 'danger' : 
                                                     ($user['role'] === 'manager' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['organization']); ?></td>
                                        <td><?php echo htmlspecialchars($user['country']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-user" 
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                    data-user-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-user-role="<?php echo $user['role']; ?>"
                                                    data-user-organization="<?php echo htmlspecialchars($user['organization']); ?>"
                                                    data-user-country="<?php echo htmlspecialchars($user['country']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-outline-danger delete-user" 
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Approval Workflows Tab -->
                    <div class="tab-pane fade" id="workflows">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Approval Workflows</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWorkflowModal">
                                <i class="fas fa-plus me-1"></i>Create Workflow
                            </button>
                        </div>

                        <?php foreach($workflows as $workflow): ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($workflow['name']); ?></h5>
                                <span class="badge bg-info"><?php echo ucfirst($workflow['type']); ?></span>
                            </div>
                            <div class="card-body">
                                <p><strong>Rule:</strong> <?php echo $workflow['approval_rule'] ?: 'Sequential'; ?></p>
                                <?php if($workflow['approval_rule']): ?>
                                <p><strong>Value:</strong> <?php echo htmlspecialchars($workflow['approval_value']); ?></p>
                                <?php endif; ?>
                                
                                <!-- Show workflow steps -->
                                <?php 
                                $stepStmt = $pdo->prepare("
                                    SELECT ws.*, u.name as approver_name 
                                    FROM workflow_steps ws 
                                    JOIN users u ON ws.approver_id = u.id 
                                    WHERE ws.workflow_id = ? 
                                    ORDER BY ws.step_order
                                ");
                                $stepStmt->execute([$workflow['id']]);
                                $steps = $stepStmt->fetchAll();
                                ?>
                                
                                <h6>Approval Steps:</h6>
                                <ol>
                                    <?php foreach($steps as $step): ?>
                                    <li><?php echo htmlspecialchars($step['approver_name']); ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- All Expenses Tab -->
                    <div class="tab-pane fade" id="expenses">
                        <h4>All Expenses</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Amount</th>
                                        <th>Converted Amount</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($expense['employee_name']); ?></td>
                                        <td><?php echo $expense['amount'] . ' ' . $expense['currency']; ?></td>
                                        <td><?php echo $expense['converted_amount'] . ' ' . $company['currency']; ?></td>
                                        <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                        <td><?php echo $expense['expense_date']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $expense['status'] === 'approved' ? 'success' : 
                                                     ($expense['status'] === 'rejected' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($expense['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info view-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($expense['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success approve-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger reject-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Company Settings Tab -->
                    <div class="tab-pane fade" id="company">
                        <h4>Company Settings</h4>
                        <div class="card">
                            <div class="card-body">
                                <form id="companySettingsForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Company Name</label>
                                                <input type="text" class="form-control" name="name" 
                                                       value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Country</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($company['country']); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Default Currency</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($company['currency']); ?>" readonly>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Company</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_user.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control" required>
                                <option value="employee">Employee</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Organization</label>
                            <input type="text" name="organization" class="form-control" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($company['country']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-control" required>
                                <option value="employee">Employee</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Organization</label>
                            <input type="text" name="organization" id="edit_organization" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" id="edit_country" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user <strong id="delete_user_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteUser">Delete User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Expense Modal -->
    <div class="modal fade" id="viewExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Expense Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="expenseDetails">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Workflow Modal -->
    <div class="modal fade" id="addWorkflowModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Approval Workflow</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_workflow.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Workflow Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Workflow Type</label>
                            <select name="type" class="form-control" id="workflowType" required>
                                <option value="sequential">Sequential Approval</option>
                                <option value="conditional">Conditional Approval</option>
                            </select>
                        </div>
                        
                        <!-- Conditional rules (shown only when conditional is selected) -->
                        <div id="conditionalRules" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Approval Rule</label>
                                <select name="approval_rule" class="form-control">
                                    <option value="percentage">Percentage Rule</option>
                                    <option value="specific_approver">Specific Approver Rule</option>
                                    <option value="hybrid">Hybrid Rule</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Approval Value</label>
                                <input type="text" name="approval_value" class="form-control" 
                                       placeholder="e.g., 60 for percentage, CFO for specific approver, 60|CFO for hybrid">
                            </div>
                        </div>

                        <!-- Approval steps -->
                        <div class="mb-3">
                            <label class="form-label">Approval Steps</label>
                            <div id="approvalSteps">
                                <div class="step mb-2">
                                    <select name="approvers[]" class="form-control" required>
                                        <option value="">Select Approver</option>
                                        <?php foreach($approvers as $approver): ?>
                                        <option value="<?php echo $approver['id']; ?>"><?php echo htmlspecialchars($approver['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addStep">
                                <i class="fas fa-plus me-1"></i>Add Step
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Workflow</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const triggerTabList = [].slice.call(document.querySelectorAll('.list-group-item[data-bs-toggle="tab"]'));
            triggerTabList.forEach(function (triggerEl) {
                triggerEl.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = this.getAttribute('href');
                    const tabPanes = document.querySelectorAll('.tab-pane');
                    tabPanes.forEach(pane => pane.classList.remove('show', 'active'));
                    document.querySelector(target).classList.add('show', 'active');
                    
                    // Update active state in sidebar
                    triggerTabList.forEach(item => item.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Workflow type toggle
            document.getElementById('workflowType').addEventListener('change', function() {
                const conditionalRules = document.getElementById('conditionalRules');
                conditionalRules.style.display = this.value === 'conditional' ? 'block' : 'none';
            });

            // Add approval step
            document.getElementById('addStep').addEventListener('click', function() {
                const stepsContainer = document.getElementById('approvalSteps');
                const newStep = document.createElement('div');
                newStep.className = 'step mb-2 d-flex align-items-center';
                newStep.innerHTML = `
                    <select name="approvers[]" class="form-control me-2" required>
                        <option value="">Select Approver</option>
                        <?php foreach($approvers as $approver): ?>
                        <option value="<?php echo $approver['id']; ?>"><?php echo htmlspecialchars($approver['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-step">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                stepsContainer.appendChild(newStep);
            });

            // Remove approval step
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-step')) {
                    e.target.closest('.step').remove();
                }
            });

            // Edit user functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-user')) {
                    const btn = e.target.closest('.edit-user');
                    document.getElementById('edit_user_id').value = btn.dataset.userId;
                    document.getElementById('edit_name').value = btn.dataset.userName;
                    document.getElementById('edit_email').value = btn.dataset.userEmail;
                    document.getElementById('edit_role').value = btn.dataset.userRole;
                    document.getElementById('edit_organization').value = btn.dataset.userOrganization;
                    document.getElementById('edit_country').value = btn.dataset.userCountry;
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                }
            });

            // Delete user functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-user')) {
                    const btn = e.target.closest('.delete-user');
                    document.getElementById('delete_user_name').textContent = btn.dataset.userName;
                    document.getElementById('confirmDeleteUser').dataset.userId = btn.dataset.userId;
                    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
                }
            });

            // Handle edit user form submission
            document.getElementById('editUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating user: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the user.');
                });
            });

            // Handle delete user confirmation
            document.getElementById('confirmDeleteUser').addEventListener('click', function() {
                const userId = this.dataset.userId;
                
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({user_id: userId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`user-row-${userId}`).remove();
                        bootstrap.Modal.getInstance(document.getElementById('deleteUserModal')).hide();
                        showAlert('User deleted successfully', 'success');
                    } else {
                        alert('Error deleting user: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the user.');
                });
            });

            // Handle expense actions
            document.addEventListener('click', function(e) {
                if (e.target.closest('.approve-expense')) {
                    const btn = e.target.closest('.approve-expense');
                    const expenseId = btn.dataset.expenseId;
                    updateExpenseStatus(expenseId, 'approved');
                } else if (e.target.closest('.reject-expense')) {
                    const btn = e.target.closest('.reject-expense');
                    const expenseId = btn.dataset.expenseId;
                    updateExpenseStatus(expenseId, 'rejected');
                } else if (e.target.closest('.view-expense')) {
                    const btn = e.target.closest('.view-expense');
                    const expenseId = btn.dataset.expenseId;
                    viewExpenseDetails(expenseId);
                }
            });

            // Handle company settings form
            document.getElementById('companySettingsForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('update_company.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Company settings updated successfully', 'success');
                    } else {
                        showAlert('Error updating company: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while updating company settings.', 'danger');
                });
            });

            // Search and filter functionality
            document.getElementById('userSearch').addEventListener('input', filterUsers);
            document.getElementById('roleFilter').addEventListener('change', filterUsers);
        });

        // Helper function to update expense status
        function updateExpenseStatus(expenseId, status) {
            fetch('update_expense.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({expense_id: expenseId, status: status})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating expense: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the expense.');
            });
        }

        // Helper function to show alerts
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Helper function to view expense details
        function viewExpenseDetails(expenseId) {
            fetch(`get_expense_details.php?id=${expenseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const expense = data.expense;
                        document.getElementById('expenseDetails').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Employee Information</h6>
                                    <p><strong>Name:</strong> ${expense.employee_name}</p>
                                    <p><strong>Email:</strong> ${expense.employee_email || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Expense Information</h6>
                                    <p><strong>Amount:</strong> ${expense.amount} ${expense.currency}</p>
                                    <p><strong>Converted:</strong> ${expense.converted_amount} ${expense.company_currency}</p>
                                    <p><strong>Category:</strong> ${expense.category}</p>
                                    <p><strong>Date:</strong> ${expense.expense_date}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-${expense.status === 'approved' ? 'success' : expense.status === 'rejected' ? 'danger' : 'warning'}">
                                            ${expense.status}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Description</h6>
                                    <p>${expense.description || 'No description provided'}</p>
                                </div>
                            </div>
                        `;
                        new bootstrap.Modal(document.getElementById('viewExpenseModal')).show();
                    } else {
                        alert('Error loading expense details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading expense details.');
                });
        }

        // Helper function to filter users
        function filterUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const rows = document.querySelectorAll('#users tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const role = row.cells[3].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesRole = !roleFilter || role.includes(roleFilter);
                
                if (matchesSearch && matchesRole) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Helper function to clear filters
        function clearFilters() {
            document.getElementById('userSearch').value = '';
            document.getElementById('roleFilter').value = '';
            filterUsers();
        }
    </script>
</body>
</html>
