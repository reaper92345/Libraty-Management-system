<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Transaction.php';
include_once '../models/Book.php';
include_once '../models/User.php';

class ReportController {
    private $db;
    private $transaction;
    private $book;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->transaction = new Transaction($this->db);
        $this->book = new Book($this->db);
        $this->user = new User($this->db);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_segments = explode('/', trim($path, '/'));

        // Assuming /api/reports
        $report_type = isset($path_segments[2]) ? $path_segments[2] : null;

        if ($method === 'GET') {
            switch ($report_type) {
                case 'overdue':
                    $this->getOverdueBooks();
                    break;
                case 'popular-books':
                    $this->getPopularBooks();
                    break;
                case 'total-members':
                    $this->getTotalMembers();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(array("message" => "Report type not found."));
                    break;
            }
        } else {
            http_response_code(405);
            echo json_encode(array("message" => "Method Not Allowed."));
        }
    }

    private function getOverdueBooks() {
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

    private function getPopularBooks() {
        // This logic needs to be added to the Book model
        $stmt = $this->book->readPopularBooks();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $popular_books_arr = array();
            $popular_books_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $book_item = array(
                    "id" => $id,
                    "title" => $title,
                    "author" => $author,
                    "isbn" => $isbn,
                    "borrow_count" => $borrow_count
                );
                array_push($popular_books_arr["records"], $book_item);
            }
            http_response_code(200);
            echo json_encode($popular_books_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No popular books found."));
        }
    }

    private function getTotalMembers() {
        // This logic needs to be added to the User model
        $count = $this->user->getTotalUsers();

        if ($count > 0) {
            http_response_code(200);
            echo json_encode(array("total_members" => $count));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No members found."));
        }
    }
}
