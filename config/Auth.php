<?php

class Auth
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function authenticate()
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            exit("Token required");
        }

        $token = $headers['Authorization'];

        // Hash received token
        $hash = hash("sha256", $token);

        $stmt = $this->db->prepare(
            "SELECT user_id, email 
             FROM users
             WHERE api_token_hash = ?
             AND token_expiry > NOW()" 
        );

        $stmt->execute([$hash]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            exit("Invalid or expired token");
        }

        return $user;
    }
}