<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Transaction.php';
include_once '../models/BookCopy.php';
include_once '../models/Book.php';

class TransactionController {
    private $db;
    private $transaction;
    private $bookCopy;
    private $book;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->transaction = new Transaction($this->db);
        $this->bookCopy = new BookCopy($this->db);
        $this->book = new Book($this->db);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_segments = explode('/', trim($path, '/'));

        // Assuming /api/transactions
        $action = isset($path_segments[2]) ? $path_segments[2] : null;
        $id = isset($path_segments[3]) ? $path_segments[3] : null;

        switch ($method) {
            case 'GET':
                if ($action === 'overdue') {
                    $this->readOverdue();
                } elseif ($action === 'user' && $id) {
                    $this->readByUserId($id);
                } elseif ($id) {
                    $this->readOne($id);
                } else {
                    $this->read();
                }
                break;
            case 'POST':
                if ($action === 'borrow') {
                    $this->borrowBook();
                } else if ($action === 'return') {
                    $this->returnBook();
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Invalid transaction action."));
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(array("message" => "Method Not Allowed."));
                break;
        }
    }

    private function borrowBook() {
        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->book_copy_barcode) &&
            !empty($data->user_id)
        ) {
            // Check if book copy exists and is available
            $this->bookCopy->barcode = $data->book_copy_barcode;
            if (!$this->bookCopy->readByBarcode()) {
                http_response_code(404);
                echo json_encode(array("message" => "Book copy not found."));
                return;
            }

            if ($this->bookCopy->status !== 'available') {
                http_response_code(409);
                echo json_encode(array("message" => "Book copy is not available for borrowing."));
                return;
            }

            // Set transaction properties
            $this->transaction->book_copy_id = $this->bookCopy->id;
            $this->transaction->user_id = $data->user_id;
            $this->transaction->borrow_date = date('Y-m-d');
            $this->transaction->due_date = date('Y-m-d', strtotime($this->transaction->borrow_date . ' +14 days'));
            $this->transaction->status = 'borrowed';

            // Begin transaction
            $this->db->beginTransaction();

            try {
                if ($this->transaction->borrow()) {
                    // Update book copy status
                    $this->bookCopy->status = 'on_loan';
                    if (!$this->bookCopy->update()) {
                        throw new Exception("Unable to update book copy status.");
                    }

                    // Update available copies in books table
                    if (!$this->book->updateAvailableCopies($this->bookCopy->book_id, -1)) {
                        throw new Exception("Unable to update book available copies.");
                    }

                    $this->db->commit();
                    http_response_code(201);
                    echo json_encode(array("message" => "Book was successfully borrowed."));
                } else {
                    throw new Exception("Unable to create borrow transaction.");
                }
            } catch (Exception $e) {
                $this->db->rollBack();
                http_response_code(503);
                echo json_encode(array("message" => "Transaction failed: " . $e->getMessage()));
            }

        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to borrow book. Data is incomplete."));
        }
    }

    private function returnBook() {
        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->transaction_id)
        ) {
            $this->transaction->id = $data->transaction_id;
            if (!$this->transaction->readOne()) {
                http_response_code(404);
                echo json_encode(array("message" => "Transaction not found."));
                return;
            }

            if ($this->transaction->status === 'returned') {
                http_response_code(409);
                echo json_encode(array("message" => "Book already returned."));
                return;
            }

            // Calculate fine
            $return_date = date('Y-m-d');
            $due_date = strtotime($this->transaction->due_date);
            $current_date = strtotime($return_date);
            $fine_amount = 0;

            if ($current_date > $due_date) {
                $diff = abs($current_date - $due_date);
                $days_overdue = floor($diff / (60 * 60 * 24));
                $fine_amount = $days_overdue * 0.50; // Example: $0.50 per day overdue
                $this->transaction->status = 'overdue'; // Mark as overdue if returned late
            } else {
                $this->transaction->status = 'returned';
            }

            $this->transaction->return_date = $return_date;
            $this->transaction->fine_amount = $fine_amount;

            // Begin transaction
            $this->db->beginTransaction();

            try {
                if ($this->transaction->returnBook()) {
                    // Update book copy status
                    $this->bookCopy->id = $this->transaction->book_copy_id;
                    $this->bookCopy->readOne(); // Get book_id for updating available copies
                    $this->bookCopy->status = 'available';
                    if (!$this->bookCopy->update()) {
                        throw new Exception("Unable to update book copy status.");
                    }

                    // Update available copies in books table
                    if (!$this->book->updateAvailableCopies($this->bookCopy->book_id, 1)) {
                        throw new Exception("Unable to update book available copies.");
                    }

                    $this->db->commit();
                    http_response_code(200);
                    echo json_encode(array("message" => "Book was successfully returned.", "fine_amount" => $fine_amount));
                } else {
                    throw new Exception("Unable to update return transaction.");
                }
            } catch (Exception $e) {
                $this->db->rollBack();
                http_response_code(503);
                echo json_encode(array("message" => "Transaction failed: " . $e->getMessage()));
            }

        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to return book. Transaction ID is incomplete."));
        }
    }

    private function read() {
        $stmt = $this->transaction->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $transactions_arr = array();
            $transactions_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $transaction_item = array(
                    "id" => $id,
                    "barcode" => $barcode,
                    "book_title" => $title,
                    "member_name" => $full_name,
                    "borrow_date" => $borrow_date,
                    "due_date" => $due_date,
                    "return_date" => $return_date,
                    "fine_amount" => $fine_amount,
                    "status" => $status
                );

                array_push($transactions_arr["records"], $transaction_item);
            }

            http_response_code(200);
            echo json_encode($transactions_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No transactions found."));
        }
    }

    private function readOne($id) {
        $this->transaction->id = $id;
        if ($this->transaction->readOne()) {
            $transaction_arr = array(
                "id" => $this->transaction->id,
                "book_copy_id" => $this->transaction->book_copy_id,
                "user_id" => $this->transaction->user_id,
                "borrow_date" => $this->transaction->borrow_date,
                "due_date" => $this->transaction->due_date,
                "return_date" => $this->transaction->return_date,
                "fine_amount" => $this->transaction->fine_amount,
                "status" => $this->transaction->status
            );

            http_response_code(200);
            echo json_encode($transaction_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Transaction not found."));
        }
    }

    private function readByUserId($user_id) {
        $stmt = $this->transaction->readByUserId($user_id);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $transactions_arr = array();
            $transactions_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $transaction_item = array(
                    "id" => $id,
                    "borrow_date" => $borrow_date,
                    "due_date" => $due_date,
                    "return_date" => $return_date,
                    "fine_amount" => $fine_amount,
                    "status" => $status,
                    "barcode" => $barcode,
                    "book_title" => $title,
                    "book_author" => $author
                );

                array_push($transactions_arr["records"], $transaction_item);
            }

            http_response_code(200);
            echo json_encode($transactions_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No transactions found for this user."));
        }
    }

    private function readOverdue() {
        $stmt = $this->transaction->readOverdue();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $overdue_books_arr = array();
            $overdue_books_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $overdue_item = array(
                    "id" => $id,
                    "borrow_date" => $borrow_date,
                    "due_date" => $due_date,
                    "fine_amount" => $fine_amount,
                    "barcode" => $barcode,
                    "book_title" => $title,
                    "member_name" => $full_name,
                    "overdue_days" => $overdue_days
                );

                array_push($overdue_books_arr["records"], $overdue_item);
            }

            http_response_code(200);
            echo json_encode($overdue_books_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No overdue books found."));
        }
    }
}
