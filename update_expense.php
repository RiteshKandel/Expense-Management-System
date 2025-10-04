<?php
session_start();
include('../config/db.php');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $expense_id = $input['expense_id'];
    $status = $input['status'];
    
    // Validate input
    if (empty($expense_id) || !in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit();
    }
    
    // Check if expense exists and belongs to the same company
    $checkStmt = $conn->prepare("
        SELECT e.id 
        FROM expenses e 
        JOIN users u ON e.employee_id = u.id 
        WHERE e.id = ? AND u.company_id = ?
    ");
    $checkStmt->bind_param("ii", $expense_id, $_SESSION['company_id']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Expense not found']);
        exit();
    }
    
    // Update expense status
    $stmt = $conn->prepare("UPDATE expenses SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $expense_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating expense: ' . $stmt->error]);
    }
    
    $stmt->close();
    $checkStmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
