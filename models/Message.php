<?php

class Message
{
    private $db;

    // Message properties
    public $id;
    public $senderId;
    public $receiverId;
    public $message;
    public $createdAt;
    public $isRead;

    // Interest properties
    public $interestId;
    public $interestStatus;


    public function __construct($db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | MESSAGES
    |--------------------------------------------------------------------------
    */


    /**
     * Send a new message
     */
    public function sendMessage()
    {
        try {

            $query = "
                INSERT INTO messages (
                    sender_id,
                    receiver_id,
                    message
                )
                VALUES (
                    :sender_id,
                    :receiver_id,
                    :message
                )
            ";

            $stmt = $this->db->prepare($query);

            $stmt->bindValue(
                ':sender_id',
                $this->senderId,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':receiver_id',
                $this->receiverId,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':message',
                $this->message,
                PDO::PARAM_STR
            );

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {

            error_log(
                "Message::sendMessage Error: " . $e->getMessage()
            );

            return false;
        }
    }


    /**
     * Get conversation between two users
     */
    public function getConversation($senderUserId, $receiverUserId)
    {
        try {

            $query = "
                SELECT
                    id,
                    sender_id,
                    receiver_id,
                    message,
                    created_at,
                    is_read
                FROM messages
                WHERE
                    (
                        sender_id = :sender_user_id
                        AND receiver_id = :receiver_user_id
                    )
                    OR
                    (
                        sender_id = :receiver_user_id
                        AND receiver_id = :sender_user_id
                    )
                ORDER BY created_at ASC
            ";

            $stmt = $this->db->prepare($query);

            $stmt->bindValue(
                ':sender_user_id',
                $senderUserId,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':receiver_user_id',
                $receiverUserId,
                PDO::PARAM_INT
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            error_log(
                "Message::getConversation Error: " . $e->getMessage()
            );

            return false;
        }
    }


    /**
     * Mark messages as read
     */
    public function markAsRead($senderUserId, $receiverUserId)
    {
        try {

            $query = "
                UPDATE messages
                SET is_read = 1
                WHERE
                    sender_id = :sender_id
                    AND receiver_id = :receiver_id
                    AND is_read = 0
            ";

            $stmt = $this->db->prepare($query);

            $stmt->bindValue(
                ':sender_id',
                $senderUserId,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':receiver_id',
                $receiverUserId,
                PDO::PARAM_INT
            );

            return $stmt->execute();

        } catch (PDOException $e) {

            error_log(
                "Message::markAsRead Error: " . $e->getMessage()
            );

            return false;
        }
    }


    /**
     * Get unread message count
     */
    public function getUnreadCount($userId)
    {
        try {

            $query = "
                SELECT COUNT(*) AS unread_count
                FROM messages
                WHERE receiver_id = :user_id
                AND is_read = 0
            ";

            $stmt = $this->db->prepare($query);

            $stmt->bindValue(
                ':user_id',
                $userId,
                PDO::PARAM_INT
            );

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['unread_count'];

        } catch (PDOException $e) {

            error_log(
                "Message::getUnreadCount Error: " . $e->getMessage()
            );

            return 0;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INTERESTS
    |--------------------------------------------------------------------------
    */


    /**
     * Send interest
     *
     * POST /api/interests
     */
    public function sendInterest()
    {
        try {

            // Check whether interest already exists
            $checkQuery = "
                SELECT id, status
                FROM interests
                WHERE sender_id = :sender_id
                AND receiver_id = :receiver_id
                LIMIT 1
            ";

            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindValue(':sender_id', $this->senderId, PDO::PARAM_INT);
            $checkStmt->bindValue(':receiver_id',$this->receiverId,PDO::PARAM_INT);
            $checkStmt->execute();
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);


            if ($existing) {

                // Don't allow sending duplicate pending interest
                if ($existing['status'] === 'pending') {
                    return [
                        'success' => false,
                        'message' => 'Interest already sent',
                        'id' => $existing['id']
                    ];
                }

                // If previously rejected, allow sending again
                $updateQuery = "
                    UPDATE interests
                    SET
                        status = 'pending',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ";

                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindValue(':id',$existing['id'],PDO::PARAM_INT);

                if ($updateStmt->execute()) {

                    return [
                        'success' => true,
                        'message' => 'Interest sent successfully again previously it was rejected',
                        'id' => $existing['id']
                    ];
                }

                return false;
            }


            // Create new interest
            $query = "
                INSERT INTO interests (
                    sender_id,
                    receiver_id,
                    status
                )
                VALUES (
                    :sender_id,
                    :receiver_id,
                    'pending'
                )
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':sender_id',$this->senderId,PDO::PARAM_INT);
            $stmt->bindValue(':receiver_id',$this->receiverId,PDO::PARAM_INT);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Interest sent successfully',
                    'id' => $this->db->lastInsertId()
                ];
            }

            return false;

        } catch (PDOException $e) {

            error_log(
                "Message::sendInterest Error: " . $e->getMessage()
            );

            return false;
        }
    }


    /**
     * Accept or reject interest
     *
     * PUT /api/interests/:id
     *
     * $status = accepted / rejected
     */
    public function updateInterest($interestId, $status)
    {
        try {

            if (!in_array($status, ['accepted', 'rejected'])) {
                return false;
            }


            $query = "
                UPDATE interests
                SET
                    status = :status
                WHERE id = :id
            ";

            $stmt = $this->db->prepare($query);

            $stmt->bindValue(
                ':status',
                $status,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':id',
                $interestId,
                PDO::PARAM_INT
            );


            if ($stmt->execute()) {

                return $stmt->rowCount() > 0;
            }

            return false;

        } catch (PDOException $e) {

            error_log(
                "Message::updateInterest Error: " . $e->getMessage()
            );

            return false;
        }
    }


    /**
     * Get sent and received interests
     *
     * GET /api/interests
     */
    public function getReceivedInterests($userId)
    {
        try {

            $query = "
               SELECT *
                FROM profiles AS p
                JOIN photos AS ph
                    ON p.user_id = ph.user_id
                LEFT JOIN interests AS i
                    ON i.sender_id = p.user_id  
                   WHERE  i.receiver_id = :user_id and i.status = 'pending'
            ";

            $stmt = $this->db->prepare($query);

            $stmt->bindValue(
                ':user_id',
                $userId,
                PDO::PARAM_INT
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            error_log(
                "Message::getReceivedInterests Error: " . $e->getMessage()
            );

            return false;
        }
    }

     public function getSentInterests($userId)
    {
        try {

            $query = "
               SELECT *
                FROM profiles AS p
                JOIN photos AS ph
                    ON p.user_id = ph.user_id
                LEFT JOIN interests AS i
                    ON i.receiver_id = p.user_id  
                   WHERE  i.sender_id = :user_id  AND i.status = 'pending'
            ";

            $stmt = $this->db->prepare($query);

            $stmt->bindValue(
                ':user_id',
                $userId,
                PDO::PARAM_INT
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            error_log(
                "Message::getSentInterests Error: " . $e->getMessage()
            );

            return false;
        }
    }


}
