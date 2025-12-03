<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/BookCopy.php';
include_once '../models/Book.php';

class BookCopyController {
    private $db;
    private $bookCopy;
    private $book;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->bookCopy = new BookCopy($this->db);
        $this->book = new Book($this->db);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_segments = explode('/', trim($path, '/'));

        // Assuming the base path for book copies is /api/bookcopies
        if (in_array('bookcopies', $path_segments)) {
            $id_index = array_search('bookcopies', $path_segments) + 1;
            $id = isset($path_segments[$id_index]) ? $path_segments[$id_index] : null;
        } else {
            $id = null;
        }

        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->readOne($id);
                } else {
                    $this->read();
                }
                break;
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                if ($id) {
                    $this->update($id);
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Bad Request: Book Copy ID required for update."));
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->delete($id);
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Bad Request: Book Copy ID required for delete."));
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(array("message" => "Method Not Allowed."));
                break;
        }
    }

    private function create() {
        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->book_id) &&
            !empty($data->barcode)
        ) {
            $this->bookCopy->book_id = $data->book_id;
            $this->bookCopy->barcode = $data->barcode;
            $this->bookCopy->status = $data->status ?? 'available';

            // Begin transaction
            $this->db->beginTransaction();

            try {
                if ($this->bookCopy->create()) {
                    // Increment total_copies and available_copies in the books table
                    if (!$this->book->updateAvailableCopies($data->book_id, 1)) {
                         throw new Exception("Unable to update book available copies.");
                    }
                    $this->book->id = $data->book_id;
                    $this->book->readOne();

                    $this->db->commit();
                    http_response_code(201);
                    echo json_encode(array("message" => "Book copy was created."));
                } else {
                    throw new Exception("Unable to create book copy.");
                }
            } catch (Exception $e) {
                $this->db->rollBack();
                http_response_code(503);
                echo json_encode(array("message" => "Transaction failed: " . $e->getMessage()));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to create book copy. Data is incomplete."));
        }
    }

    private function read() {
        $stmt = $this->bookCopy->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $book_copies_arr = array();
            $book_copies_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $book_copy_item = array(
                    "id" => $id,
                    "book_id" => $book_id,
                    "barcode" => $barcode,
                    "status" => $status
                );

                array_push($book_copies_arr["records"], $book_copy_item);
            }

            http_response_code(200);
            echo json_encode($book_copies_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No book copies found."));
        }
    }

    private function readOne($id) {
        $this->bookCopy->id = $id;
        if ($this->bookCopy->readOne()) {
            $book_copy_arr = array(
                "id" => $this->bookCopy->id,
                "book_id" => $this->bookCopy->book_id,
                "barcode" => $this->bookCopy->barcode,
                "status" => $this->bookCopy->status
            );

            http_response_code(200);
            echo json_encode($book_copy_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Book copy not found."));
        }
    }

    private function update($id) {
        $data = json_decode(file_get_contents("php://input"));

        $this->bookCopy->id = $id;

        if (
            !empty($data->book_id) &&
            !empty($data->barcode) &&
            !empty($data->status)
        ) {
            // Read existing book copy to compare status for available_copies update
            $old_book_copy = new BookCopy($this->db);
            $old_book_copy->id = $id;
            $old_book_copy->readOne();

            $this->bookCopy->book_id = $data->book_id;
            $this->bookCopy->barcode = $data->barcode;
            $this->bookCopy->status = $data->status;

            // Begin transaction
            $this->db->beginTransaction();

            try {
                if ($this->bookCopy->update()) {
                    // Adjust available_copies based on status change
                    if ($old_book_copy->status === 'available' && $this->bookCopy->status !== 'available') {
                        if (!$this->book->updateAvailableCopies($this->bookCopy->book_id, -1)) {
                             throw new Exception("Unable to decrement book available copies.");
                        }
                    } elseif ($old_book_copy->status !== 'available' && $this->bookCopy->status === 'available') {
                        if (!$this->book->updateAvailableCopies($this->bookCopy->book_id, 1)) {
                             throw new Exception("Unable to increment book available copies.");
                        }
                    }

                    $this->db->commit();
                    http_response_code(200);
                    echo json_encode(array("message" => "Book copy was updated."));
                } else {
                    throw new Exception("Unable to update book copy.");
                }
            } catch (Exception $e) {
                $this->db->rollBack();
                http_response_code(503);
                echo json_encode(array("message" => "Transaction failed: " . $e->getMessage()));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to update book copy. Data is incomplete."));
        }
    }

    private function delete($id) {
        $this->bookCopy->id = $id;

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Read book copy to decrement available_copies if it was available
            if (!$this->bookCopy->readOne()) {
                throw new Exception("Book copy not found for deletion.");
            }

            if ($this->bookCopy->delete()) {
                if ($this->bookCopy->status === 'available') {
                    if (!$this->book->updateAvailableCopies($this->bookCopy->book_id, -1)) {
                        throw new Exception("Unable to decrement book available copies on deletion.");
                    }
                }

                $this->db->commit();
                http_response_code(200);
                echo json_encode(array("message" => "Book copy was deleted."));
            } else {
                throw new Exception("Unable to delete book copy.");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(503);
            echo json_encode(array("message" => "Transaction failed: " . $e->getMessage()));
        }
    }
}
