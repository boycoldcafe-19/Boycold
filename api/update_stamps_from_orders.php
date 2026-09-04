<?php
/**
 * Update Stamp Beans from Completed Orders
 * 
 * This script sets each customer's loyalty_stamps equal to their total number
 * of completed orders (excluding Walk-in Customer).
 * Sets loyalty_beans to 0 since we're using direct stamp counting.
 */

require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

try {
    // Start transaction for data integrity
    $connect->begin_transaction();

    // Get all users with their completed order counts
    $query = "
        SELECT 
            u.id,
            u.user_name,
            u.loyalty_beans as current_beans,
            u.loyalty_stamps as current_stamps,
            u.card_no,
            COUNT(o.id) as completed_orders
        FROM users u
        LEFT JOIN orders o ON u.user_name = o.user_name AND o.status = 'completed'
        WHERE u.user_name != 'Walk-in Customer'
        GROUP BY u.id, u.user_name, u.loyalty_beans, u.loyalty_stamps, u.card_no
        HAVING completed_orders > 0
    ";
    
    $result = $connect->query($query);
    $usersToUpdate = [];
    
    while ($row = $result->fetch_assoc()) {
        $usersToUpdate[] = $row;
    }
    
    $updatedCount = 0;
    $errors = [];
    
    foreach ($usersToUpdate as $user) {
        $newStamps = min(10, max(0, (int) $user['completed_orders']));
        $newBeans = 0; // Reset beans to 0 since we're using direct stamp counting
        
        // Calculate previous and new balances
        $previousBalance = (int) $user['current_beans'] + ((int) $user['current_stamps'] * 10);
        $newBalance = $newBeans + ($newStamps * 10);
        
        // Update user's loyalty values
        $updateStmt = $connect->prepare(
            "UPDATE users SET loyalty_beans = ?, loyalty_stamps = ? WHERE id = ?"
        );
        $updateStmt->bind_param("iii", $newBeans, $newStamps, $user['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Record transaction in loyalty_transactions table
        // Use 0 for branch_id, device_id, and employee_id since this is a system update
        $transactionStmt = $connect->prepare(
            "INSERT INTO loyalty_transactions (user_id, card_no, branch_id, device_id, employee_id, transaction_type, points_awarded, previous_balance, new_balance)
             VALUES (?, ?, 0, 0, 0, 'bean_award', ?, ?, ?)"
        );
        $pointsAwarded = $newBalance - $previousBalance;
        $transactionStmt->bind_param(
            'isiii', 
            $user['id'], 
            $user['card_no'], 
            $pointsAwarded, 
            $previousBalance, 
            $newBalance
        );
        $transactionStmt->execute();
        $transactionStmt->close();
        
        $updatedCount++;
    }
    
    // Commit transaction
    $connect->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Updated $updatedCount users' stamps based on completed orders",
        'updated_count' => $updatedCount,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($connect) && $connect instanceof mysqli) {
        $connect->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
