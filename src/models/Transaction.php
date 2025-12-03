<?php

class Transaction {
    private $conn;
    private $table_name = "transactions";

    public $id;
    public $book_copy_id;
    public $user_id;
    public $borrow_date;
    public $due_date;
    public $return_date;
    public $fine_amount;
    public $status; // 'borrowed', 'returned', 'overdue'

    public function __construct($db){
        $this->conn = $db;
    }

    // Borrow Book
    public function borrow(){
        $query = "INSERT INTO " . $this->table_name . " SET book_copy_id=:book_copy_id, user_id=:user_id, borrow_date=:borrow_date, due_date=:due_date, status=:status";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->book_copy_id=htmlspecialchars(strip_tags($this->book_copy_id));
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $this->borrow_date=htmlspecialchars(strip_tags($this->borrow_date));
        $this->due_date=htmlspecialchars(strip_tags($this->due_date));
        $this->status=htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":book_copy_id", $this->book_copy_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":borrow_date", $this->borrow_date);
        $stmt->bindParam(":due_date", $this->due_date);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Return Book
    public function returnBook(){
        $query = "UPDATE " . $this->table_name . " SET return_date=:return_date, fine_amount=:fine_amount, status=:status WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->return_date=htmlspecialchars(strip_tags($this->return_date));
        $this->fine_amount=htmlspecialchars(strip_tags($this->fine_amount));
        $this->status=htmlspecialchars(strip_tags($this->status));
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":return_date", $this->return_date);
        $stmt->bindParam(":fine_amount", $this->fine_amount);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Read Transactions
    public function read(){
        $query = "SELECT t.id, t.borrow_date, t.due_date, t.return_date, t.fine_amount, t.status, " .
                 "bc.barcode, b.title, u.full_name " .
                 "FROM " . $this->table_name . " t " .
                 "LEFT JOIN book_copies bc ON t.book_copy_id = bc.id " .
                 "LEFT JOIN books b ON bc.book_id = b.id " .
                 "LEFT JOIN users u ON t.user_id = u.id " .
                 "ORDER BY t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read Single Transaction
    public function readOne(){
        $query = "SELECT t.id, t.borrow_date, t.due_date, t.return_date, t.fine_amount, t.status, " .
                 "bc.barcode, b.title, u.full_name, t.book_copy_id, t.user_id " .
                 "FROM " . $this->table_name . " t " .
                 "LEFT JOIN book_copies bc ON t.book_copy_id = bc.id " .
                 "LEFT JOIN books b ON bc.book_id = b.id " .
                 "LEFT JOIN users u ON t.user_id = u.id " .
                 "WHERE t.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->book_copy_id = $row['book_copy_id'];
            $this->user_id = $row['user_id'];
            $this->borrow_date = $row['borrow_date'];
            $this->due_date = $row['due_date'];
            $this->return_date = $row['return_date'];
            $this->fine_amount = $row['fine_amount'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // Get transactions by user ID
    public function readByUserId($user_id){
        $query = "SELECT t.id, t.borrow_date, t.due_date, t.return_date, t.fine_amount, t.status, " .
                 "bc.barcode, b.title, b.author " .
                 "FROM " . $this->table_name . " t " .
                 "LEFT JOIN book_copies bc ON t.book_copy_id = bc.id " .
                 "LEFT JOIN books b ON bc.book_id = b.id " .
                 "WHERE t.user_id = ? ORDER BY t.borrow_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Get overdue books
    public function readOverdue(){
        $query = "SELECT t.id, t.borrow_date, t.due_date, t.fine_amount, bc.barcode, b.title, u.full_name, " .
                 "DATEDIFF(CURRENT_DATE(), t.due_date) as overdue_days " .
                 "FROM " . $this->table_name . " t " .
                 "LEFT JOIN book_copies bc ON t.book_copy_id = bc.id " .
                 "LEFT JOIN books b ON bc.book_id = b.id " .
                 "LEFT JOIN users u ON t.user_id = u.id " .
                 "WHERE t.status = 'borrowed' AND t.due_date < CURRENT_DATE()";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
