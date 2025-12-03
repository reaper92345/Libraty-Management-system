<?php

class Reservation {
    private $conn;
    private $table_name = "reservations";

    public $id;
    public $book_id;
    public $user_id;
    public $reservation_date;
    public $status; // 'pending', 'fulfilled', 'cancelled'

    public function __construct($db){
        $this->conn = $db;
    }

    // Create Reservation
    public function create(){
        $query = "INSERT INTO " . $this->table_name . " SET book_id=:book_id, user_id=:user_id, reservation_date=:reservation_date, status=:status";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->book_id=htmlspecialchars(strip_tags($this->book_id));
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $this->reservation_date=htmlspecialchars(strip_tags($this->reservation_date));
        $this->status=htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":reservation_date", $this->reservation_date);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Read Reservations
    public function read(){
        $query = "SELECT r.id, r.reservation_date, r.status, b.title, u.full_name " .
                 "FROM " . $this->table_name . " r " .
                 "LEFT JOIN books b ON r.book_id = b.id " .
                 "LEFT JOIN users u ON r.user_id = u.id " .
                 "ORDER BY r.reservation_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read Single Reservation
    public function readOne(){
        $query = "SELECT r.id, r.book_id, r.user_id, r.reservation_date, r.status, b.title, u.full_name " .
                 "FROM " . $this->table_name . " r " .
                 "LEFT JOIN books b ON r.book_id = b.id " .
                 "LEFT JOIN users u ON r.user_id = u.id " .
                 "WHERE r.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->book_id = $row['book_id'];
            $this->user_id = $row['user_id'];
            $this->reservation_date = $row['reservation_date'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // Update Reservation
    public function update(){
        $query = "UPDATE " . $this->table_name . " SET book_id=:book_id, user_id=:user_id, reservation_date=:reservation_date, status=:status WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->book_id=htmlspecialchars(strip_tags($this->book_id));
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $this->reservation_date=htmlspecialchars(strip_tags($this->reservation_date));
        $this->status=htmlspecialchars(strip_tags($this->status));
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":reservation_date", $this->reservation_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Delete Reservation
    public function delete(){
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Get active reservations for a book
    public function getActiveReservationsForBook($book_id) {
        $query = "SELECT COUNT(*) as total_reservations FROM " . $this->table_name . " WHERE book_id = ? AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $book_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_reservations'];
    }

    // Get user's pending reservations
    public function getPendingReservationsByUserId($user_id) {
        $query = "SELECT r.id, r.reservation_date, r.status, b.title, b.author " .
                 "FROM " . $this->table_name . " r " .
                 "LEFT JOIN books b ON r.book_id = b.id " .
                 "WHERE r.user_id = ? AND r.status = 'pending' ORDER BY r.reservation_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }
}
