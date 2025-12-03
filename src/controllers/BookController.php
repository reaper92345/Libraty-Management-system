<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Book.php';

class BookController {
    private $db;
    private $book;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->book = new Book($this->db);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_segments = explode('/', trim($path, '/'));

        // Assuming the base path for books is /books or /api/books
        // We need to adjust this based on the actual routing setup in index.php
        if (in_array('books', $path_segments)) {
            $id_index = array_search('books', $path_segments) + 1;
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
                    echo json_encode(array("message" => "Bad Request: Book ID required for update."));
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->delete($id);
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Bad Request: Book ID required for delete."));
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
            !empty($data->title) &&
            !empty($data->author) &&
            !empty($data->isbn) &&
            !empty($data->total_copies)
        ) {
            $this->book->title = $data->title;
            $this->book->author = $data->author;
            $this->book->isbn = $data->isbn;
            $this->book->publication_year = $data->publication_year ?? null;
            $this->book->category = $data->category ?? null;
            $this->book->total_copies = $data->total_copies;
            $this->book->available_copies = $data->total_copies; // Initially all copies are available

            if ($this->book->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Book was created."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create book."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to create book. Data is incomplete."));
        }
    }

    private function read() {
        $stmt = $this->book->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $books_arr = array();
            $books_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $book_item = array(
                    "id" => $id,
                    "title" => $title,
                    "author" => $author,
                    "isbn" => $isbn,
                    "publication_year" => $publication_year,
                    "category" => $category,
                    "total_copies" => $total_copies,
                    "available_copies" => $available_copies
                );

                array_push($books_arr["records"], $book_item);
            }

            http_response_code(200);
            echo json_encode($books_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No books found."));
        }
    }

    private function readOne($id) {
        $this->book->id = $id;
        if ($this->book->readOne()) {
            $book_arr = array(
                "id" => $this->book->id,
                "title" => $this->book->title,
                "author" => $this->book->author,
                "isbn" => $this->book->isbn,
                "publication_year" => $this->book->publication_year,
                "category" => $this->book->category,
                "total_copies" => $this->book->total_copies,
                "available_copies" => $this->book->available_copies
            );

            http_response_code(200);
            echo json_encode($book_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Book not found."));
        }
    }

    private function update($id) {
        $data = json_decode(file_get_contents("php://input"));

        $this->book->id = $id;

        if (
            !empty($data->title) &&
            !empty($data->author) &&
            !empty($data->isbn) &&
            !empty($data->total_copies)
        ) {
            $this->book->title = $data->title;
            $this->book->author = $data->author;
            $this->book->isbn = $data->isbn;
            $this->book->publication_year = $data->publication_year ?? null;
            $this->book->category = $data->category ?? null;
            $this->book->total_copies = $data->total_copies;
            $this->book->available_copies = $data->available_copies; // Assuming available copies can be updated manually or by system actions

            if ($this->book->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Book was updated."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to update book."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to update book. Data is incomplete."));
        }
    }

    private function delete($id) {
        $this->book->id = $id;

        if ($this->book->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Book was deleted."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete book."));
        }
    }

    public function search() {
        $keywords = isset($_GET['s']) ? $_GET['s'] : "";

        $stmt = $this->book->search($keywords);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $books_arr = array();
            $books_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $book_item = array(
                    "id" => $id,
                    "title" => $title,
                    "author" => $author,
                    "isbn" => $isbn,
                    "publication_year" => $publication_year,
                    "category" => $category,
                    "total_copies" => $total_copies,
                    "available_copies" => $available_copies
                );

                array_push($books_arr["records"], $book_item);
            }

            http_response_code(200);
            echo json_encode($books_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No books found matching your search."));
        }
    }
}
