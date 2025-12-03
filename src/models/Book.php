<?php

class Book {
    private $conn;
    private $table_name = "books";

    public $id;
    public $title;
    public $author;
    public $isbn;
    public $publication_year;
    public $category;
    public $total_copies;
    public $available_copies;

    public function __construct($db){
        $this->conn = $db;
    }

    // Create Book
    public function create(){
        $query = "INSERT INTO " . $this->table_name . " SET title=:title, author=:author, isbn=:isbn, publication_year=:publication_year, category=:category, total_copies=:total_copies, available_copies=:available_copies";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->title=htmlspecialchars(strip_tags($this->title));
        $this->author=htmlspecialchars(strip_tags($this->author));
        $this->isbn=htmlspecialchars(strip_tags($this->isbn));
        $this->publication_year=htmlspecialchars(strip_tags($this->publication_year));
        $this->category=htmlspecialchars(strip_tags($this->category));
        $this->total_copies=htmlspecialchars(strip_tags($this->total_copies));
        $this->available_copies=htmlspecialchars(strip_tags($this->available_copies));

        // bind values
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":author", $this->author);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":publication_year", $this->publication_year);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":total_copies", $this->total_copies);
        $stmt->bindParam(":available_copies", $this->available_copies);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Read Books
    public function read(){
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read Single Book
    public function readOne(){
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->title = $row['title'];
            $this->author = $row['author'];
            $this->isbn = $row['isbn'];
            $this->publication_year = $row['publication_year'];
            $this->category = $row['category'];
            $this->total_copies = $row['total_copies'];
            $this->available_copies = $row['available_copies'];
            return true;
        }
        return false;
    }

    // Update Book
    public function update(){
        $query = "UPDATE " . $this->table_name . " SET title=:title, author=:author, isbn=:isbn, publication_year=:publication_year, category=:category, total_copies=:total_copies, available_copies=:available_copies WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->title=htmlspecialchars(strip_tags($this->title));
        $this->author=htmlspecialchars(strip_tags($this->author));
        $this->isbn=htmlspecialchars(strip_tags($this->isbn));
        $this->publication_year=htmlspecialchars(strip_tags($this->publication_year));
        $this->category=htmlspecialchars(strip_tags($this->category));
        $this->total_copies=htmlspecialchars(strip_tags($this->total_copies));
        $this->available_copies=htmlspecialchars(strip_tags($this->available_copies));
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":author", $this->author);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":publication_year", $this->publication_year);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":total_copies", $this->total_copies);
        $stmt->bindParam(":available_copies", $this->available_copies);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Delete Book
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

    // Search Books
    public function search($keywords){
        $query = "SELECT * FROM " . $this->table_name . " WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $keywords=htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        // bind
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);

        $stmt->execute();

        return $stmt;
    }

    // Update available copies for a book
    public function updateAvailableCopies($book_id, $change){
        $query = "UPDATE " . $this->table_name . " SET available_copies = available_copies + (:change) WHERE id = :book_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":change", $change);
        $stmt->bindParam(":book_id", $book_id);

        if($stmt->execute()){
            return true;
        }
        return false;
    }

    // Read Popular Books
    public function readPopularBooks(){
        $query = "SELECT b.id, b.title, b.author, b.isbn, COUNT(t.id) as borrow_count " .
                 "FROM " . $this->table_name . " b " .
                 "LEFT JOIN book_copies bc ON b.id = bc.book_id " .
                 "LEFT JOIN transactions t ON bc.id = t.book_copy_id " .
                 "GROUP BY b.id " .
                 "ORDER BY borrow_count DESC, b.title ASC " .
                 "LIMIT 10"; // Top 10 popular books

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
