<?php

class BookCopy {
    private $conn;
    private $table_name = "book_copies";

    public $id;
    public $book_id;
    public $barcode;
    public $status; // 'available', 'on_loan', 'reserved', 'lost', 'damaged'

    public function __construct($db){
        $this->conn = $db;
    }

    // Create Book Copy
    public function create(){
        $query = "INSERT INTO " . $this->table_name . " SET book_id=:book_id, barcode=:barcode, status=:status";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->book_id=htmlspecialchars(strip_tags($this->book_id));
        $this->barcode=htmlspecialchars(strip_tags($this->barcode));
        $this->status=htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":barcode", $this->barcode);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Read Book Copies
    public function read(){
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read Single Book Copy
    public function readOne(){
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->book_id = $row['book_id'];
            $this->barcode = $row['barcode'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // Read Book Copy by Barcode
    public function readByBarcode(){
        $query = "SELECT * FROM " . $this->table_name . " WHERE barcode = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->barcode);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->book_id = $row['book_id'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // Update Book Copy
    public function update(){
        $query = "UPDATE " . $this->table_name . " SET book_id=:book_id, barcode=:barcode, status=:status WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->book_id=htmlspecialchars(strip_tags($this->book_id));
        $this->barcode=htmlspecialchars(strip_tags($this->barcode));
        $this->status=htmlspecialchars(strip_tags($this->status));
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":barcode", $this->barcode);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Delete Book Copy
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
}
