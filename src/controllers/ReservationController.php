<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Reservation.php';
include_once '../models/Book.php';

class ReservationController {
    private $db;
    private $reservation;
    private $book;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->reservation = new Reservation($this->db);
        $this->book = new Book($this->db);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_segments = explode('/', trim($path, '/'));

        // Assuming the base path for reservations is /api/reservations
        $action = isset($path_segments[2]) ? $path_segments[2] : null;
        $id = isset($path_segments[3]) ? $path_segments[3] : null;

        switch ($method) {
            case 'GET':
                if ($action === 'user' && $id) {
                    $this->getPendingReservationsByUserId($id);
                } elseif ($id) {
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
                    echo json_encode(array("message" => "Bad Request: Reservation ID required for update."));
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->delete($id);
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Bad Request: Reservation ID required for delete."));
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
            !empty($data->user_id)
        ) {
            // Check if book exists
            $this->book->id = $data->book_id;
            if (!$this->book->readOne()) {
                http_response_code(404);
                echo json_encode(array("message" => "Book not found."));
                return;
            }

            // Check if user has already reserved this book
            $this->reservation->book_id = $data->book_id;
            $this->reservation->user_id = $data->user_id;
            if ($this->reservation->readOne()) {
                http_response_code(409);
                echo json_encode(array("message" => "You have already reserved this book."));
                return;
            }

            $this->reservation->reservation_date = date('Y-m-d H:i:s');
            $this->reservation->status = 'pending';

            if ($this->reservation->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Book reserved successfully."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create reservation."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to create reservation. Data is incomplete."));
        }
    }

    private function read() {
        $stmt = $this->reservation->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $reservations_arr = array();
            $reservations_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $reservation_item = array(
                    "id" => $id,
                    "book_title" => $title,
                    "member_name" => $full_name,
                    "reservation_date" => $reservation_date,
                    "status" => $status
                );

                array_push($reservations_arr["records"], $reservation_item);
            }

            http_response_code(200);
            echo json_encode($reservations_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No reservations found."));
        }
    }

    private function readOne($id) {
        $this->reservation->id = $id;
        if ($this->reservation->readOne()) {
            $reservation_arr = array(
                "id" => $this->reservation->id,
                "book_id" => $this->reservation->book_id,
                "user_id" => $this->reservation->user_id,
                "reservation_date" => $this->reservation->reservation_date,
                "status" => $this->reservation->status
            );

            http_response_code(200);
            echo json_encode($reservation_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Reservation not found."));
        }
    }

    private function update($id) {
        $data = json_decode(file_get_contents("php://input"));

        $this->reservation->id = $id;

        if (
            !empty($data->book_id) &&
            !empty($data->user_id) &&
            !empty($data->status)
        ) {
            $this->reservation->book_id = $data->book_id;
            $this->reservation->user_id = $data->user_id;
            $this->reservation->reservation_date = $data->reservation_date ?? date('Y-m-d H:i:s');
            $this->reservation->status = $data->status;

            if ($this->reservation->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Reservation was updated."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to update reservation."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to update reservation. Data is incomplete."));
        }
    }

    private function delete($id) {
        $this->reservation->id = $id;

        if ($this->reservation->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Reservation was deleted."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete reservation."));
        }
    }

    private function getPendingReservationsByUserId($user_id) {
        $stmt = $this->reservation->getPendingReservationsByUserId($user_id);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $reservations_arr = array();
            $reservations_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $reservation_item = array(
                    "id" => $id,
                    "book_title" => $title,
                    "book_author" => $author,
                    "reservation_date" => $reservation_date,
                    "status" => $status
                );

                array_push($reservations_arr["records"], $reservation_item);
            }

            http_response_code(200);
            echo json_encode($reservations_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No pending reservations found for this user."));
        }
    }
}
