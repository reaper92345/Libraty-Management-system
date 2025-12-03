<?php

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/Transaction.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../models/BookCopy.php';
include_once __DIR__ . '/../models/Book.php';

// Initialize database and models
$database = new Database();
$db = $database->getConnection();

$transaction = new Transaction($db);
$user = new User($db);
$book = new Book($db);
$bookCopy = new BookCopy($db);

// Log file for notifications
$log_file = __DIR__ . '/notifications.log';

function log_notification($message) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . ": " . $message . "\n", FILE_APPEND);
}

log_notification("Starting notification script...");

// --- Check for books due in 2 days ---
$two_days_from_now = date('Y-m-d', strtotime('+2 days'));
$query_due_soon = "SELECT t.id, t.due_date, b.title, u.email, u.full_name, bc.barcode ".
                  "FROM transactions t ".
                  "LEFT JOIN book_copies bc ON t.book_copy_id = bc.id ".
                  "LEFT JOIN books b ON bc.book_id = b.id ".
                  "LEFT JOIN users u ON t.user_id = u.id ".
                  "WHERE t.status = 'borrowed' AND t.due_date = :two_days_from_now";

$stmt_due_soon = $db->prepare($query_due_soon);
$stmt_due_soon->bindParam(':two_days_from_now', $two_days_from_now);
$stmt_due_soon->execute();

while ($row = $stmt_due_soon->fetch(PDO::FETCH_ASSOC)) {
    $message = "Reminder: Book '{$row['title']}' (Barcode: {$row['barcode']}) is due on {$row['due_date']}. Please return it soon, {$row['full_name']}.";
    log_notification("DUE_SOON | User: {$row['email']} | Book: {$row['title']} | Message: " . $message);
    // In a real system, you would send an email here.
}

// --- Check for overdue books ---
$query_overdue = "SELECT t.id, t.due_date, b.title, u.email, u.full_name, bc.barcode, DATEDIFF(CURRENT_DATE(), t.due_date) as overdue_days ".
                 "FROM transactions t ".
                 "LEFT JOIN book_copies bc ON t.book_copy_id = bc.id ".
                 "LEFT JOIN books b ON bc.book_id = b.id ".
                 "LEFT JOIN users u ON t.user_id = u.id ".
                 "WHERE t.status = 'borrowed' AND t.due_date < CURRENT_DATE()";

$stmt_overdue = $db->prepare($query_overdue);
$stmt_overdue->execute();

while ($row = $stmt_overdue->fetch(PDO::FETCH_ASSOC)) {
    // Optionally update transaction status to 'overdue' if not already
    // This logic might be better handled during return, but for notification purposes it's fine.
    $update_transaction_status_query = "UPDATE transactions SET status = 'overdue' WHERE id = :id AND status = 'borrowed'";
    $update_stmt = $db->prepare($update_transaction_status_query);
    $update_stmt->bindParam(':id', $row['id']);
    $update_stmt->execute();

    $message = "Action Required: Book '{$row['title']}' (Barcode: {$row['barcode']}) was due on {$row['due_date']} and is {$row['overdue_days']} days overdue. Fines may apply, {$row['full_name']}.";
    log_notification("OVERDUE | User: {$row['email']} | Book: {$row['title']} | Message: " . $message);
    // In a real system, you would send an email here.
}

log_notification("Notification script finished.");

?>
