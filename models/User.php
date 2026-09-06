<?php

class User
{
    private $db;
    public $user_id;
    public $email;
    public $password_hash;
    public $role;
    public $mobile_number;
    public $api_token_hash;
    public $token_expiry;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Create a new user
    public function createUser()
    {
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
    public function emailExists()
    {
        $query = "SELECT 1 FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC); // Return true if email exists, false otherwise
    }

    // Get user by mobile number exists
    public function mobileExists()
    {
        $query = "SELECT 1 FROM users WHERE mobile_number = :mobile_number LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':mobile_number', $this->mobile_number);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC); // true if mobile exists, false otherwise
    }

    // Update null role after completed details registration
    public function updateRole()
    {
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

    // Get user by email
    public function getUserByEmail()
    {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get user update token
    public function UpdateToken()
    {
        $query = "UPDATE users SET api_token_hash=:api_token_hash, token_expiry=:token_expiry WHERE user_id=:user_id";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':api_token_hash', $this->api_token_hash);
        $stmt->bindParam(':token_expiry', $this->token_expiry);
        $stmt->bindParam(':user_id', $this->user_id);
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Fileter Uers profiles 
    public function getUserProfiles($page = 1, $perPage = 9, $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        // Gender
        if (!empty($filters['gender'])) {
            $conditions[] = "p.gender = :gender";
            $params[':gender'] = $filters['gender'];
        }

        // Minimum age
        if ($filters['minAge'] !== null && $filters['minAge'] !== '') {
            $conditions[] = "TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) >= :minAge";
            $params[':minAge'] = (int)$filters['minAge'];
        }

        // Maximum age
        if ($filters['maxAge'] !== null && $filters['maxAge'] !== '') {
            $conditions[] = "TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) <= :maxAge";
            $params[':maxAge'] = (int)$filters['maxAge'];
        }


        // Minimum income
        if ($filters['minIncome'] !== null && $filters['minIncome'] !== '') {
            $conditions[] = "CAST(TRIM( REPLACE(LOWER(p.annual_income), 'lakh', '')) AS DECIMAL(10,2)) >= :minIncome";
            $params[':minIncome'] = (float)$filters['minIncome'];
        }

        // Maximum income
        if ($filters['maxIncome'] !== null && $filters['maxIncome'] !== '') {
            $conditions[] = "CAST(TRIM(REPLACE(LOWER(p.annual_income), 'lakh', '')) AS DECIMAL(10,2)) <= :maxIncome";
            $params[':maxIncome'] = (float)$filters['maxIncome'];
        }

        $whereSql = $conditions
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        // Get profiles
        $query = "
        SELECT 
            ph.photo_url AS photo_url,
            u.user_id,
            p.full_name,
            p.education,
            p.job AS profession,
            p.current_address AS location,
            p.annual_income AS income,
            p.gender,
            TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age
        FROM users u
        JOIN profiles p ON u.user_id = p.user_id
        JOIN photos ph ON u.user_id = ph.user_id
        $whereSql
        LIMIT :perPage OFFSET :offset
    ";

        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $value) {
            if (in_array($key, [':minAge', ':maxAge'], true)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->bindValue(':perPage', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Count total matching profiles
        $countQuery = "
        SELECT COUNT(*)
        FROM users u
        JOIN profiles p ON u.user_id = p.user_id
        $whereSql
    ";

        $countStmt = $this->db->prepare($countQuery);

        foreach ($params as $key => $value) {
            if (in_array($key, [':minAge', ':maxAge'], true)) {
                $countStmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $countStmt->bindValue($key, $value);
            }
        }

        $countStmt->execute();

        $totalRecords = (int)$countStmt->fetchColumn();

        return [
            'status'       => 'SUCCESS',
            'data'         => $data,
            'totalRecords' => $totalRecords
        ];
    }
}
