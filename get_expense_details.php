<?php
session_start();
include('../config/db.php');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $expense_id = $_GET['id'];
    
    // Validate input
    if (empty($expense_id) || !is_numeric($expense_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
        exit();
    }
    
    // Get expense details with employee and company information
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            u.name as employee_name,
            u.email as employee_email,
            c.currency as company_currency
        FROM expenses e 
        JOIN users u ON e.employee_id = u.id 
        JOIN companies c ON u.company_id = c.company_id
        WHERE e.id = ? AND u.company_id = ?
    ");
    $stmt->bind_param("ii", $expense_id, $_SESSION['company_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $expense = $result->fetch_assoc();
        echo json_encode(['success' => true, 'expense' => $expense]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Expense not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
