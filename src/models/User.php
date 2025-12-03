<?php

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $member_id;
    public $full_name;
    public $email;
    public $phone;
    public $password_hash;
    public $role;
    public $account_status;

    public function __construct($db){
        $this->conn = $db;
    }

    // Create User
    public function create(){
        $query = "INSERT INTO " . $this->table_name . " SET member_id=:member_id, full_name=:full_name, email=:email, phone=:phone, password_hash=:password_hash, role=:role, account_status=:account_status";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->member_id=htmlspecialchars(strip_tags($this->member_id));
        $this->full_name=htmlspecialchars(strip_tags($this->full_name));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->phone=htmlspecialchars(strip_tags($this->phone));
        $this->password_hash=htmlspecialchars(strip_tags($this->password_hash));
        $this->role=htmlspecialchars(strip_tags($this->role));
        $this->account_status=htmlspecialchars(strip_tags($this->account_status));

        // bind values
        $stmt->bindParam(":member_id", $this->member_id);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":account_status", $this->account_status);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Read Users
    public function read(){
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read Single User
    public function readOne(){
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->member_id = $row['member_id'];
            $this->full_name = $row['full_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->password_hash = $row['password_hash'];
            $this->role = $row['role'];
            $this->account_status = $row['account_status'];
            return true;
        }
        return false;
    }

    // Update User
    public function update(){
        $query = "UPDATE " . $this->table_name . " SET member_id=:member_id, full_name=:full_name, email=:email, phone=:phone, password_hash=:password_hash, role=:role, account_status=:account_status WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->member_id=htmlspecialchars(strip_tags($this->member_id));
        $this->full_name=htmlspecialchars(strip_tags($this->full_name));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->phone=htmlspecialchars(strip_tags($this->phone));
        $this->password_hash=htmlspecialchars(strip_tags($this->password_hash));
        $this->role=htmlspecialchars(strip_tags($this->role));
        $this->account_status=htmlspecialchars(strip_tags($this->account_status));
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":member_id", $this->member_id);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":account_status", $this->account_status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        }

        return false;
    }

    // Delete User
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

    // Find user by email
    public function findByEmail($email){
        $query = "SELECT id, member_id, full_name, email, password_hash, role, account_status FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->member_id = $row['member_id'];
            $this->full_name = $row['full_name'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->role = $row['role'];
            $this->account_status = $row['account_status'];
            return true;
        }
        return false;
    }

    // Get total number of users
    public function getTotalUsers(){
        $query = "SELECT COUNT(*) as total_users FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_users'];
    }
}
