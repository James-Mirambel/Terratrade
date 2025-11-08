<?php
/**
 * Notification Class - TerraTrade Full-Stack Implementation
 * Handles all notification operations
 */

class Notification {
    private $conn;
    private $table = 'notifications';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create notification
    public function create($userId, $title, $message, $type = 'system', $relatedId = null, $relatedType = null) {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, message, type, related_id, related_type, created_at) 
                  VALUES (:user_id, :title, :message, :type, :related_id, :related_type, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':related_id', $relatedId);
        $stmt->bindParam(':related_type', $relatedType);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // Get notification by ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            return $this->processNotificationForFrontend($stmt->fetch());
        }
        
        return false;
    }
    
    // Get notifications by user
    public function getByUser($userId, $limit = 50, $unreadOnly = false) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        
        if ($unreadOnly) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll();
        
        // Process for frontend compatibility
        foreach ($notifications as &$notification) {
            $notification = $this->processNotificationForFrontend($notification);
        }
        
        return $notifications;
    }
    
    // Mark notification as read
    public function markAsRead($notificationId, $userId) {
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1, read_at = NOW() 
                  WHERE id = :notification_id AND user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':notification_id', $notificationId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
    
    // Mark all notifications as read for a user
    public function markAllAsRead($userId) {
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1, read_at = NOW() 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        
        return false;
    }
    
    // Get unread notification count
    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['count'] : 0;
    }
    
    // Delete notification
    public function delete($notificationId, $userId) {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id = :notification_id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':notification_id', $notificationId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
    
    // Delete all notifications for a user
    public function deleteAll($userId) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        
        return false;
    }
    
    // Get notifications by type
    public function getByType($userId, $type, $limit = 20) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id AND type = :type 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll();
        
        foreach ($notifications as &$notification) {
            $notification = $this->processNotificationForFrontend($notification);
        }
        
        return $notifications;
    }
    
    // Get notifications related to specific entity
    public function getByRelated($userId, $relatedType, $relatedId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id AND related_type = :related_type AND related_id = :related_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':related_type', $relatedType);
        $stmt->bindParam(':related_id', $relatedId);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll();
        
        foreach ($notifications as &$notification) {
            $notification = $this->processNotificationForFrontend($notification);
        }
        
        return $notifications;
    }
    
    // Bulk create notifications (for system-wide announcements)
    public function createBulk($userIds, $title, $message, $type = 'system') {
        if (empty($userIds)) return false;
        
        $placeholders = str_repeat('(?, ?, ?, ?, NOW()),', count($userIds));
        $placeholders = rtrim($placeholders, ',');
        
        $query = "INSERT INTO " . $this->table . " (user_id, title, message, type, created_at) VALUES $placeholders";
        $stmt = $this->conn->prepare($query);
        
        $params = [];
        foreach ($userIds as $userId) {
            $params[] = $userId;
            $params[] = $title;
            $params[] = $message;
            $params[] = $type;
        }
        
        return $stmt->execute($params);
    }
    
    // Get notification statistics
    public function getStats($userId) {
        $stats = [];
        
        // Total notifications
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['total'] = $stmt->fetch()['count'];
        
        // Unread notifications
        $stats['unread'] = $this->getUnreadCount($userId);
        
        // By type
        $typeQuery = "SELECT type, COUNT(*) as count FROM " . $this->table . " 
                      WHERE user_id = :user_id GROUP BY type";
        $typeStmt = $this->conn->prepare($typeQuery);
        $typeStmt->bindParam(':user_id', $userId);
        $typeStmt->execute();
        
        $stats['by_type'] = [];
        while ($row = $typeStmt->fetch()) {
            $stats['by_type'][$row['type']] = $row['count'];
        }
        
        return $stats;
    }
    
    // Clean old notifications (for maintenance)
    public function cleanOld($daysOld = 30) {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY) AND is_read = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $daysOld, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        
        return false;
    }
    
    // Process notification for frontend compatibility
    private function processNotificationForFrontend($notification) {
        // Frontend compatibility fields
        $notification['text'] = $notification['message'];
        $notification['time'] = date('M j, Y g:i A', strtotime($notification['created_at']));
        
        return $notification;
    }
    
    // Send system notification to all users
    public function sendSystemNotification($title, $message) {
        // Get all active user IDs
        $userQuery = "SELECT id FROM users WHERE is_active = 1";
        $userStmt = $this->conn->prepare($userQuery);
        $userStmt->execute();
        $users = $userStmt->fetchAll();
        
        $userIds = array_column($users, 'id');
        
        return $this->createBulk($userIds, $title, $message, 'system');
    }
    
    // Send notification to specific users
    public function sendToUsers($userIds, $title, $message, $type = 'system') {
        return $this->createBulk($userIds, $title, $message, $type);
    }
}
?>
