<?php

class User {
    private $db;
    public $user_id;
    public $email;
    public $password_hash;
    public $role;
    public $mobile_number;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create a new user
    public function createUser() {
        $query = "INSERT INTO users (email, password_hash, role, mobile_number) VALUES (:email, :password_hash, :role, :mobile_number)";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':mobile_number', $this->mobile_number);

        if ($stmt->execute()) {
            // Return the last inserted user_id (auto-increment value)
           return $this->db->lastInsertId();
        }

        return false;
    }
   
    // Get user by email exists
    public function emailExists() {
        $query = "SELECT 1 FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
    
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC); // Return true if email exists, false otherwise
    }


    // Update null role after completed details registration
    public function updateRole() {
        $query = "UPDATE users SET role = :role WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
    
        // Bind the role and user_id parameters
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':user_id', $this->user_id);
    
        if ($stmt->execute()) {
            return true; // Role updated successfully
        }
    
        return false; // Failed to update role
    }



    // #######################################   End #######################################################


















    // Get user by email
    public function getUserByEmail() {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    

    
    public function getUserProfiles($page = 1, $perPage = 10) {
        // Calculate the OFFSET based on the page number
        $offset = ($page - 1) * $perPage;
    
        // SQL query to fetch user profiles with pagination
        $query = "
            SELECT 
                u.user_id,
                uph.photo_url,
                CONCAT(up.first_name, ' ', up.last_name) AS full_name,
                TIMESTAMPDIFF(YEAR, up.date_of_birth, CURDATE()) AS age,
                up.religion,
                up.caste,
                up.occupation,
                up.country,
                up.state
            FROM users u
            JOIN user_photos uph ON u.user_id = uph.user_id
            JOIN user_profiles up ON u.user_id = up.user_id
            WHERE uph.is_primary = 1
            LIMIT :perPage OFFSET :offset
        ";
    
        // Prepare the query
        $stmt = $this->db->prepare($query);
    
        // Bind the parameters
        $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
        // Execute the query
        $stmt->execute();
    
        // Fetch the paginated records
        $userProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Query to count the total number of records
        $countQuery = "
            SELECT COUNT(*) AS total_records
            FROM users u
            JOIN user_photos uph ON u.user_id = uph.user_id
            JOIN user_profiles up ON u.user_id = up.user_id
            WHERE uph.is_primary = 1
        ";
    
        // Prepare and execute the count query
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute();
    
        // Fetch the total count of records
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total_records'];
    
        // Return the paginated results and the total count
        return [
            'status' => 'SUCCESS',
            'data' => $userProfiles,
            'totalRecords' => $totalCount
        ];
    }
    
    
}
