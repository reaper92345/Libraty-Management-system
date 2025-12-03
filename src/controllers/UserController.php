<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/User.php';

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_segments = explode('/', trim($path, '/'));

        // Assuming the base path for users is /api/users
        if (in_array('users', $path_segments)) {
            $id_index = array_search('users', $path_segments) + 1;
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
                if (isset($path_segments[2]) && $path_segments[2] === 'login') {
                    $this->login();
                } else {
                    $this->create();
                }
                break;
            case 'PUT':
                if ($id) {
                    $this->update($id);
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Bad Request: User ID required for update."));
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->delete($id);
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Bad Request: User ID required for delete."));
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
            !empty($data->member_id) &&
            !empty($data->full_name) &&
            !empty($data->email) &&
            !empty($data->password)
        ) {
            $this->user->member_id = $data->member_id;
            $this->user->full_name = $data->full_name;
            $this->user->email = $data->email;
            $this->user->phone = $data->phone ?? null;
            $this->user->password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            $this->user->role = $data->role ?? 'patron'; // Default role
            $this->user->account_status = $data->account_status ?? 'active'; // Default status

            if ($this->user->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "User was created."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create user."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to create user. Data is incomplete."));
        }
    }

    private function read() {
        $stmt = $this->user->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $users_arr = array();
            $users_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $user_item = array(
                    "id" => $id,
                    "member_id" => $member_id,
                    "full_name" => $full_name,
                    "email" => $email,
                    "phone" => $phone,
                    "role" => $role,
                    "account_status" => $account_status
                );

                array_push($users_arr["records"], $user_item);
            }

            http_response_code(200);
            echo json_encode($users_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No users found."));
        }
    }

    private function readOne($id) {
        $this->user->id = $id;
        if ($this->user->readOne()) {
            $user_arr = array(
                "id" => $this->user->id,
                "member_id" => $this->user->member_id,
                "full_name" => $this->user->full_name,
                "email" => $this->user->email,
                "phone" => $this->user->phone,
                "role" => $this->user->role,
                "account_status" => $this->user->account_status
            );

            http_response_code(200);
            echo json_encode($user_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "User not found."));
        }
    }

    private function update($id) {
        $data = json_decode(file_get_contents("php://input"));

        $this->user->id = $id;

        // Attempt to read user to get existing password hash
        if (!$this->user->readOne()) {
            http_response_code(404);
            echo json_encode(array("message" => "User not found."));
            return;
        }

        // Only update password if provided
        $password_hash = $this->user->password_hash; // Keep existing hash by default
        if (!empty($data->password)) {
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
        }

        if (
            !empty($data->member_id) &&
            !empty($data->full_name) &&
            !empty($data->email)
        ) {
            $this->user->member_id = $data->member_id;
            $this->user->full_name = $data->full_name;
            $this->user->email = $data->email;
            $this->user->phone = $data->phone ?? null;
            $this->user->password_hash = $password_hash; // Use updated or existing hash
            $this->user->role = $data->role ?? $this->user->role; // Keep existing role if not provided
            $this->user->account_status = $data->account_status ?? $this->user->account_status; // Keep existing status if not provided

            if ($this->user->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "User was updated."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to update user."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to update user. Data is incomplete."));
        }
    }

    private function delete($id) {
        $this->user->id = $id;

        if ($this->user->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "User was deleted."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete user."));
        }
    }

    private function login() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->email) && !empty($data->password)) {
            if ($this->user->findByEmail($data->email)) {
                if (password_verify($data->password, $this->user->password_hash)) {
                    http_response_code(200);
                    echo json_encode(array(
                        "message" => "Successful login.",
                        "user" => array(
                            "id" => $this->user->id,
                            "member_id" => $this->user->member_id,
                            "full_name" => $this->user->full_name,
                            "email" => $this->user->email,
                            "role" => $this->user->role,
                            "account_status" => $this->user->account_status
                        )
                    ));
                } else {
                    http_response_code(401);
                    echo json_encode(array("message" => "Login failed. Incorrect password."));
                }
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Login failed. User not found."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Login failed. Data is incomplete."));
        }
    }
}
