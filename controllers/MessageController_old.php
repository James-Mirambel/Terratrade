<?php
/**
 * Message Controller
 * TerraTrade Land Trading System
 */

class MessageController {
    
    /**
     * Get user conversations
     */
    public function getConversations($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(50, max(1, (int)($params['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;
            
            // Get conversations where user is a participant
            $sql = "
                SELECT DISTINCT c.*,
                       CASE 
                           WHEN c.user1_id = ? THEN u2.full_name 
                           ELSE u1.full_name 
                       END as other_user_name,
                       CASE 
                           WHEN c.user1_id = ? THEN u2.profile_image 
                           ELSE u1.profile_image 
                       END as other_user_image,
                       CASE 
                           WHEN c.user1_id = ? THEN c.user2_id 
                           ELSE c.user1_id 
                       END as other_user_id,
                       p.title as property_title,
                       p.location as property_location,
                       lm.message as last_message,
                       lm.created_at as last_message_at,
                       (SELECT COUNT(*) FROM messages m 
                        WHERE (m.sender_id = c.user1_id AND m.recipient_id = c.user2_id) 
                           OR (m.sender_id = c.user2_id AND m.recipient_id = c.user1_id)
                        AND (c.listing_id IS NULL OR m.listing_id = c.listing_id)) as message_count,
                       (SELECT COUNT(*) FROM messages m 
                        WHERE m.recipient_id = ? AND m.is_read = FALSE
                        AND ((m.sender_id = c.user1_id AND m.recipient_id = c.user2_id) 
                             OR (m.sender_id = c.user2_id AND m.recipient_id = c.user1_id))
                        AND (c.listing_id IS NULL OR m.listing_id = c.listing_id)) as unread_count
                FROM conversations c
                JOIN users u1 ON c.user1_id = u1.id
                JOIN users u2 ON c.user2_id = u2.id
                LEFT JOIN properties p ON c.listing_id = p.id
                LEFT JOIN messages lm ON c.last_message_id = lm.id
                WHERE (c.user1_id = ? OR c.user2_id = ?)
                ORDER BY c.last_activity DESC
                LIMIT ? OFFSET ?
            ";
            
            $conversations = fetchAll($sql, [
                $user['id'], $user['id'], $user['id'], $user['id'], 
                $user['id'], $user['id'], $pageSize, $offset
            ]);
            
            // Format conversation data
            foreach ($conversations as &$conversation) {
                $conversation['last_message_ago'] = $conversation['last_message_at'] ? timeAgo($conversation['last_message_at']) : null;
                $conversation['unread_count'] = (int)$conversation['unread_count'];
                $conversation['message_count'] = (int)$conversation['message_count'];
            }
            
            // Count total conversations
            $countSql = "
                SELECT COUNT(DISTINCT c.id) as total
                FROM conversations c
                WHERE (c.user1_id = ? OR c.user2_id = ?)
            ";
            
            $totalResult = fetchOne($countSql, [$user['id'], $user['id']]);
            $total = (int)$totalResult['total'];
            
            $pagination = paginate($total, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'conversations' => $conversations,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get conversations error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to load conversations'], 500);
        }
    }
    
    /**
     * Get messages in a conversation
     */
    public function getMessages($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        if (empty($params['other_user_id'])) {
            jsonResponse(['success' => false, 'error' => 'Other user ID is required'], 400);
        }
        
        try {
            $otherUserId = (int)$params['other_user_id'];
            $listingId = !empty($params['listing_id']) ? (int)$params['listing_id'] : null;
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(100, max(1, (int)($params['page_size'] ?? 50)));
            $offset = ($page - 1) * $pageSize;
            
            // Get messages between users
            $sql = "
                SELECT m.*, 
                       s.full_name as sender_name,
                       s.profile_image as sender_image,
                       r.full_name as recipient_name,
                       p.title as listing_title
                FROM messages m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.recipient_id = r.id
                LEFT JOIN properties p ON m.listing_id = p.id
                WHERE ((m.sender_id = ? AND m.recipient_id = ?) 
                       OR (m.sender_id = ? AND m.recipient_id = ?))
                AND (? IS NULL OR m.listing_id = ?)
                AND m.is_deleted_by_sender = FALSE 
                AND m.is_deleted_by_recipient = FALSE
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $messages = fetchAll($sql, [
                $user['id'], $otherUserId, $otherUserId, $user['id'],
                $listingId, $listingId, $pageSize, $offset
            ]);
            
            // Mark messages as read
            $updateSql = "
                UPDATE messages 
                SET is_read = TRUE, read_at = NOW() 
                WHERE recipient_id = ? AND sender_id = ? AND is_read = FALSE
                AND (? IS NULL OR listing_id = ?)
            ";
            executeQuery($updateSql, [$user['id'], $otherUserId, $listingId, $listingId]);
            
            // Format message data
            foreach ($messages as &$message) {
                $message['created_ago'] = timeAgo($message['created_at']);
                $message['is_own'] = $message['sender_id'] == $user['id'];
            }
            
            // Reverse to show oldest first
            $messages = array_reverse($messages);
            
            jsonResponse([
                'success' => true,
                'messages' => $messages
            ]);
            
        } catch (Exception $e) {
            error_log("Get messages error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to load messages'], 500);
        }
    }
    
    /**
     * Send a new message
     */
    public function sendMessage($data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate input
        if (empty($data['recipient_id']) || empty($data['message'])) {
            jsonResponse(['success' => false, 'error' => 'Recipient and message are required'], 400);
        }
        
        try {
            $recipientId = (int)$data['recipient_id'];
            $message = trim($data['message']);
            $subject = !empty($data['subject']) ? trim($data['subject']) : null;
            $listingId = !empty($data['listing_id']) ? (int)$data['listing_id'] : null;
            
            // Verify recipient exists
            $recipient = fetchOne("SELECT id, full_name FROM users WHERE id = ? AND status = 'active'", [$recipientId]);
            if (!$recipient) {
                jsonResponse(['success' => false, 'error' => 'Recipient not found'], 404);
            }
            
            // Insert message
            $sql = "
                INSERT INTO messages (sender_id, recipient_id, subject, message, listing_id, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ";
            executeQuery($sql, [$user['id'], $recipientId, $subject, $message, $listingId]);
            $messageId = lastInsertId();
            
            // Create or update conversation
            $this->createOrUpdateConversation($user['id'], $recipientId, $listingId, $messageId);
            
            // Send notification
            $notificationTitle = $subject ?: 'New Message';
            $notificationMessage = "You have a new message from {$user['full_name']}";
            sendNotification($recipientId, 'message', $notificationTitle, $notificationMessage, [
                'sender_id' => $user['id'],
                'message_id' => $messageId,
                'listing_id' => $listingId
            ]);
            
            jsonResponse([
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message sent successfully'
            ], 201);
            
        } catch (Exception $e) {
            error_log("Send message error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to send message'], 500);
        }
    }
    
    /**
     * Create or update conversation
     */
    private function createOrUpdateConversation($user1Id, $user2Id, $listingId, $messageId) {
        // Ensure consistent ordering for conversation lookup
        $minUserId = min($user1Id, $user2Id);
        $maxUserId = max($user1Id, $user2Id);
        
        // Check if conversation exists
        $sql = "
            SELECT id FROM conversations 
            WHERE user1_id = ? AND user2_id = ? 
            AND (listing_id = ? OR (listing_id IS NULL AND ? IS NULL))
        ";
        $conversation = fetchOne($sql, [$minUserId, $maxUserId, $listingId, $listingId]);
        
        if ($conversation) {
            // Update existing conversation
            executeQuery("
                UPDATE conversations 
                SET last_message_id = ?, last_activity = NOW() 
                WHERE id = ?
            ", [$messageId, $conversation['id']]);
        } else {
            // Create new conversation
            executeQuery("
                INSERT INTO conversations (user1_id, user2_id, listing_id, last_message_id, last_activity, created_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ", [$minUserId, $maxUserId, $listingId, $messageId]);
        }
    }
    
    /**
     * Delete a message
     */
    public function deleteMessage($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        if (empty($params['message_id'])) {
            jsonResponse(['success' => false, 'error' => 'Message ID is required'], 400);
        }
        
        try {
            $messageId = (int)$params['message_id'];
            
            // Get message to verify ownership
            $message = fetchOne("
                SELECT * FROM messages 
                WHERE id = ? AND (sender_id = ? OR recipient_id = ?)
            ", [$messageId, $user['id'], $user['id']]);
            
            if (!$message) {
                jsonResponse(['success' => false, 'error' => 'Message not found'], 404);
            }
            
            // Soft delete based on user role
            if ($message['sender_id'] == $user['id']) {
                executeQuery("UPDATE messages SET is_deleted_by_sender = TRUE WHERE id = ?", [$messageId]);
            } else {
                executeQuery("UPDATE messages SET is_deleted_by_recipient = TRUE WHERE id = ?", [$messageId]);
            }
            
            jsonResponse(['success' => true, 'message' => 'Message deleted']);
            
        } catch (Exception $e) {
            error_log("Delete message error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to delete message'], 500);
        }
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount() {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $result = fetchOne("
                SELECT COUNT(*) as count 
                FROM messages 
                WHERE recipient_id = ? AND is_read = FALSE 
                AND is_deleted_by_recipient = FALSE
            ", [$user['id']]);
            
            jsonResponse([
                'success' => true,
                'unread_count' => (int)$result['count']
            ]);
            
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to get unread count'], 500);
        }
    }
}
?>
        
        try {
            // Verify user is participant in conversation
            $participant = fetchOne("
                SELECT cp.id 
                FROM conversation_participants cp
                JOIN conversations c ON cp.conversation_id = c.id
                WHERE cp.conversation_id = ? AND cp.user_id = ? AND c.status = 'active'
            ", [$data['conversation_id'], $user['id']]);
            
            if (!$participant) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized or conversation not found'], 403);
            }
            
            $messageId = $this->sendMessageToConversation($data['conversation_id'], $user['id'], $data['content'], $data['message_type'] ?? 'text');
            
            // Update conversation timestamp
            executeQuery("UPDATE conversations SET updated_at = NOW() WHERE id = ?", [$data['conversation_id']]);
            
            // Get other participants for notifications
            $otherParticipants = fetchAll("
                SELECT cp.user_id, u.full_name
                FROM conversation_participants cp
                JOIN users u ON cp.user_id = u.id
                WHERE cp.conversation_id = ? AND cp.user_id != ?
            ", [$data['conversation_id'], $user['id']]);
            
            // Send notifications to other participants
            foreach ($otherParticipants as $participant) {
                sendNotification(
                    $participant['user_id'],
                    'message',
                    'New Message',
                    "You have a new message from {$user['full_name']}",
                    ['conversation_id' => $data['conversation_id'], 'message_id' => $messageId]
                );
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'Message sent successfully',
                'message_id' => $messageId
            ], 201);
            
        } catch (Exception $e) {
            error_log("Send message error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to send message'], 500);
        }
    }
    
    /**
     * Get message history for a conversation
     */
    public function getMessageHistory($conversationId, $params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            // Verify user is participant in conversation
            $participant = fetchOne("
                SELECT cp.id 
                FROM conversation_participants cp
                JOIN conversations c ON cp.conversation_id = c.id
                WHERE cp.conversation_id = ? AND cp.user_id = ? AND c.status = 'active'
            ", [$conversationId, $user['id']]);
            
            if (!$participant) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized or conversation not found'], 403);
            }
            
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? 50)));
            $offset = ($page - 1) * $pageSize;
            
            // Get messages
            $sql = "
                SELECT m.*, u.full_name as sender_name, u.profile_image as sender_avatar
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $messages = fetchAll($sql, [$conversationId, $pageSize, $offset]);
            
            // Reverse order to show oldest first
            $messages = array_reverse($messages);
            
            // Format messages
            foreach ($messages as &$message) {
                $message['created_ago'] = timeAgo($message['created_at']);
                $message['is_own_message'] = $message['sender_id'] == $user['id'];
                $message['read_by'] = $message['read_by'] ? json_decode($message['read_by'], true) : [];
                $message['is_read'] = isset($message['read_by'][$user['id']]);
            }
            
            // Mark messages as read
            $this->markMessagesAsRead($conversationId, $user['id']);
            
            // Count total messages
            $countSql = "SELECT COUNT(*) as total FROM messages WHERE conversation_id = ?";
            $totalResult = fetchOne($countSql, [$conversationId]);
            $totalRecords = $totalResult['total'];
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'messages' => $messages,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get message history error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch message history'], 500);
        }
    }
    
    /**
     * Upload file for message
     */
    public function uploadMessageFile($conversationId, $files) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Verify user is participant in conversation
        $participant = fetchOne("
            SELECT cp.id 
            FROM conversation_participants cp
            JOIN conversations c ON cp.conversation_id = c.id
            WHERE cp.conversation_id = ? AND cp.user_id = ? AND c.status = 'active'
        ", [$conversationId, $user['id']]);
        
        if (!$participant) {
            jsonResponse(['success' => false, 'error' => 'Unauthorized or conversation not found'], 403);
        }
        
        if (empty($files['file'])) {
            jsonResponse(['success' => false, 'error' => 'No file provided'], 400);
        }
        
        try {
            $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOCUMENT_TYPES);
            $uploadResult = uploadFile(
                $files['file'],
                UPLOAD_PATH . 'messages/',
                $allowedTypes
            );
            
            if (!$uploadResult['success']) {
                jsonResponse($uploadResult, 400);
            }
            
            // Send file message
            $content = "File: " . $files['file']['name'];
            $messageId = $this->sendMessageToConversation($conversationId, $user['id'], $content, 'file', [
                'file_path' => 'messages/' . $uploadResult['filename'],
                'file_name' => $files['file']['name'],
                'file_size' => $files['file']['size']
            ]);
            
            jsonResponse([
                'success' => true,
                'message' => 'File uploaded and sent successfully',
                'message_id' => $messageId,
                'file_path' => $uploadResult['filename']
            ]);
            
        } catch (Exception $e) {
            error_log("Upload message file error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to upload file'], 500);
        }
    }
    
    /**
     * Archive conversation
     */
    public function archiveConversation($conversationId) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            // Verify user is participant in conversation
            $participant = fetchOne("
                SELECT cp.id 
                FROM conversation_participants cp
                WHERE cp.conversation_id = ? AND cp.user_id = ?
            ", [$conversationId, $user['id']]);
            
            if (!$participant) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized or conversation not found'], 403);
            }
            
            // Update conversation status
            executeQuery("UPDATE conversations SET status = 'archived', updated_at = NOW() WHERE id = ?", [$conversationId]);
            
            // Log audit
            logAudit($user['id'], 'conversation_archive', 'conversations', $conversationId, null, null);
            
            jsonResponse(['success' => true, 'message' => 'Conversation archived successfully']);
            
        } catch (Exception $e) {
            error_log("Archive conversation error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to archive conversation'], 500);
        }
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount() {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $result = fetchOne("
                SELECT COUNT(*) as unread_count
                FROM messages m
                JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
                JOIN conversations c ON m.conversation_id = c.id
                WHERE cp.user_id = ? 
                AND m.sender_id != ? 
                AND c.status = 'active'
                AND JSON_EXTRACT(m.read_by, CONCAT('$.\"', ?, '\"')) IS NULL
            ", [$user['id'], $user['id'], $user['id']]);
            
            jsonResponse([
                'success' => true,
                'unread_count' => (int)$result['unread_count']
            ]);
            
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to get unread count'], 500);
        }
    }
    
    /**
     * Send message to conversation (internal helper)
     */
    private function sendMessageToConversation($conversationId, $senderId, $content, $messageType = 'text', $fileData = null) {
        $sql = "INSERT INTO messages (conversation_id, sender_id, message_type, content, file_path, file_name, file_size, read_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $readBy = json_encode([$senderId => date('Y-m-d H:i:s')]); // Mark as read by sender
        
        $params = [
            $conversationId,
            $senderId,
            $messageType,
            sanitize($content),
            $fileData['file_path'] ?? null,
            $fileData['file_name'] ?? null,
            $fileData['file_size'] ?? null,
            $readBy
        ];
        
        executeQuery($sql, $params);
        return lastInsertId();
    }
    
    /**
     * Mark messages as read (internal helper)
     */
    private function markMessagesAsRead($conversationId, $userId) {
        try {
            // Get unread messages
            $unreadMessages = fetchAll("
                SELECT id, read_by
                FROM messages 
                WHERE conversation_id = ? 
                AND sender_id != ?
                AND JSON_EXTRACT(read_by, CONCAT('$.\"', ?, '\"')) IS NULL
            ", [$conversationId, $userId, $userId]);
            
            foreach ($unreadMessages as $message) {
                $readBy = $message['read_by'] ? json_decode($message['read_by'], true) : [];
                $readBy[$userId] = date('Y-m-d H:i:s');
                
                executeQuery("UPDATE messages SET read_by = ? WHERE id = ?", [
                    json_encode($readBy),
                    $message['id']
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Mark messages as read error: " . $e->getMessage());
        }
    }
}
?>
