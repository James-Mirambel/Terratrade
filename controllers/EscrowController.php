<?php
/**
 * Escrow Controller
 * TerraTrade Land Trading System
 */

class EscrowController {
    
    /**
     * Create escrow account
     */
    public function createEscrowAccount($data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        if (empty($data['contract_id']) || empty($data['total_amount'])) {
            jsonResponse(['success' => false, 'error' => 'Contract ID and total amount are required'], 400);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Verify contract exists and user is involved
            $contract = fetchOne("
                SELECT * FROM contracts 
                WHERE id = ? AND (buyer_id = ? OR seller_id = ?)
            ", [$data['contract_id'], $user['id'], $user['id']]);
            
            if (!$contract) {
                jsonResponse(['success' => false, 'error' => 'Contract not found or unauthorized'], 404);
            }
            
            // Check if escrow account already exists
            $existingEscrow = fetchOne("SELECT id FROM escrow_accounts WHERE contract_id = ?", [$data['contract_id']]);
            if ($existingEscrow) {
                jsonResponse(['success' => false, 'error' => 'Escrow account already exists for this contract'], 400);
            }
            
            // Create escrow account
            $sql = "INSERT INTO escrow_accounts (contract_id, total_amount, status) VALUES (?, ?, 'pending')";
            $params = [$data['contract_id'], (float)$data['total_amount']];
            
            executeQuery($sql, $params);
            $escrowId = lastInsertId();
            
            // Log audit
            logAudit($user['id'], 'escrow_create', 'escrow_accounts', $escrowId, null, $data);
            
            // Send notifications to both parties
            $buyerId = $contract['buyer_id'];
            $sellerId = $contract['seller_id'];
            
            if ($user['id'] != $buyerId) {
                sendNotification(
                    $buyerId,
                    'system',
                    'Escrow Account Created',
                    'An escrow account has been created for your property purchase.',
                    ['escrow_id' => $escrowId, 'contract_id' => $data['contract_id']]
                );
            }
            
            if ($user['id'] != $sellerId) {
                sendNotification(
                    $sellerId,
                    'system',
                    'Escrow Account Created',
                    'An escrow account has been created for your property sale.',
                    ['escrow_id' => $escrowId, 'contract_id' => $data['contract_id']]
                );
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'Escrow account created successfully',
                'escrow_id' => $escrowId
            ], 201);
            
        } catch (Exception $e) {
            error_log("Create escrow account error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to create escrow account'], 500);
        }
    }
    
    /**
     * Deposit funds to escrow
     */
    public function depositFunds($escrowId, $data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        if (empty($data['amount']) || empty($data['description'])) {
            jsonResponse(['success' => false, 'error' => 'Amount and description are required'], 400);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Get escrow account with contract details
            $escrow = fetchOne("
                SELECT ea.*, c.buyer_id, c.seller_id, c.contract_amount
                FROM escrow_accounts ea
                JOIN contracts c ON ea.contract_id = c.id
                WHERE ea.id = ?
            ", [$escrowId]);
            
            if (!$escrow) {
                jsonResponse(['success' => false, 'error' => 'Escrow account not found'], 404);
            }
            
            // Verify user is authorized (buyer, seller, or admin)
            if (!in_array($user['id'], [$escrow['buyer_id'], $escrow['seller_id']]) && $user['role'] !== 'admin') {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
            }
            
            $amount = (float)$data['amount'];
            
            // Validate deposit amount
            if ($amount <= 0) {
                jsonResponse(['success' => false, 'error' => 'Deposit amount must be positive'], 400);
            }
            
            $newDepositedAmount = $escrow['deposited_amount'] + $amount;
            if ($newDepositedAmount > $escrow['total_amount']) {
                jsonResponse(['success' => false, 'error' => 'Deposit exceeds total escrow amount'], 400);
            }
            
            // Create deposit transaction
            $sql = "
                INSERT INTO escrow_transactions (
                    escrow_account_id, transaction_type, amount, description, 
                    milestone, authorized_by, status, transaction_reference
                ) VALUES (?, 'deposit', ?, ?, ?, ?, 'completed', ?)
            ";
            
            $params = [
                $escrowId,
                $amount,
                sanitize($data['description']),
                sanitize($data['milestone'] ?? ''),
                $user['id'],
                $data['transaction_reference'] ?? uniqid('DEP_')
            ];
            
            executeQuery($sql, $params);
            $transactionId = lastInsertId();
            
            // Update escrow account
            executeQuery("
                UPDATE escrow_accounts 
                SET deposited_amount = deposited_amount + ?, 
                    status = CASE WHEN deposited_amount + ? >= total_amount THEN 'funded' ELSE 'pending' END,
                    updated_at = NOW()
                WHERE id = ?
            ", [$amount, $amount, $escrowId]);
            
            // Log audit
            logAudit($user['id'], 'escrow_deposit', 'escrow_transactions', $transactionId, null, $data);
            
            // Send notifications
            $otherPartyId = $user['id'] == $escrow['buyer_id'] ? $escrow['seller_id'] : $escrow['buyer_id'];
            sendNotification(
                $otherPartyId,
                'system',
                'Escrow Deposit Made',
                "A deposit of " . formatCurrency($amount) . " has been made to the escrow account.",
                ['escrow_id' => $escrowId, 'transaction_id' => $transactionId]
            );
            
            jsonResponse([
                'success' => true,
                'message' => 'Funds deposited successfully',
                'transaction_id' => $transactionId,
                'new_balance' => $newDepositedAmount
            ]);
            
        } catch (Exception $e) {
            error_log("Deposit funds error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to deposit funds'], 500);
        }
    }
    
    /**
     * Release funds from escrow
     */
    public function releaseFunds($escrowId, $data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        if (empty($data['amount']) || empty($data['description'])) {
            jsonResponse(['success' => false, 'error' => 'Amount and description are required'], 400);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Get escrow account with contract details
            $escrow = fetchOne("
                SELECT ea.*, c.buyer_id, c.seller_id, c.contract_amount
                FROM escrow_accounts ea
                JOIN contracts c ON ea.contract_id = c.id
                WHERE ea.id = ?
            ", [$escrowId]);
            
            if (!$escrow) {
                jsonResponse(['success' => false, 'error' => 'Escrow account not found'], 404);
            }
            
            // Verify user is authorized (buyer, seller, or admin)
            if (!in_array($user['id'], [$escrow['buyer_id'], $escrow['seller_id']]) && $user['role'] !== 'admin') {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
            }
            
            $amount = (float)$data['amount'];
            $availableAmount = $escrow['deposited_amount'] - $escrow['released_amount'];
            
            // Validate release amount
            if ($amount <= 0) {
                jsonResponse(['success' => false, 'error' => 'Release amount must be positive'], 400);
            }
            
            if ($amount > $availableAmount) {
                jsonResponse(['success' => false, 'error' => 'Insufficient funds in escrow'], 400);
            }
            
            // Create release transaction
            $sql = "
                INSERT INTO escrow_transactions (
                    escrow_account_id, transaction_type, amount, description, 
                    milestone, authorized_by, status, transaction_reference
                ) VALUES (?, 'release', ?, ?, ?, ?, 'completed', ?)
            ";
            
            $params = [
                $escrowId,
                $amount,
                sanitize($data['description']),
                sanitize($data['milestone'] ?? ''),
                $user['id'],
                $data['transaction_reference'] ?? uniqid('REL_')
            ];
            
            executeQuery($sql, $params);
            $transactionId = lastInsertId();
            
            // Update escrow account
            $newReleasedAmount = $escrow['released_amount'] + $amount;
            $newStatus = $newReleasedAmount >= $escrow['deposited_amount'] ? 'completed' : 'partial_release';
            
            executeQuery("
                UPDATE escrow_accounts 
                SET released_amount = released_amount + ?, 
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [$amount, $newStatus, $escrowId]);
            
            // Calculate and create escrow fee transaction
            $feePercentage = getSetting('escrow_fee_percentage', 2.5);
            $feeAmount = ($amount * $feePercentage) / 100;
            
            if ($feeAmount > 0) {
                $feeSql = "
                    INSERT INTO escrow_transactions (
                        escrow_account_id, transaction_type, amount, description, 
                        authorized_by, status, transaction_reference
                    ) VALUES (?, 'fee', ?, 'Escrow service fee', ?, 'completed', ?)
                ";
                
                executeQuery($feeSql, [
                    $escrowId,
                    $feeAmount,
                    $user['id'],
                    uniqid('FEE_')
                ]);
            }
            
            // Log audit
            logAudit($user['id'], 'escrow_release', 'escrow_transactions', $transactionId, null, $data);
            
            // Send notifications
            $otherPartyId = $user['id'] == $escrow['buyer_id'] ? $escrow['seller_id'] : $escrow['buyer_id'];
            sendNotification(
                $otherPartyId,
                'system',
                'Escrow Funds Released',
                "Funds of " . formatCurrency($amount) . " have been released from the escrow account.",
                ['escrow_id' => $escrowId, 'transaction_id' => $transactionId]
            );
            
            jsonResponse([
                'success' => true,
                'message' => 'Funds released successfully',
                'transaction_id' => $transactionId,
                'remaining_balance' => $availableAmount - $amount,
                'escrow_status' => $newStatus
            ]);
            
        } catch (Exception $e) {
            error_log("Release funds error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to release funds'], 500);
        }
    }
    
    /**
     * Get escrow account details
     */
    public function getEscrowDetails($escrowId) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            // Get escrow account with contract and user details
            $escrow = fetchOne("
                SELECT ea.*, 
                       c.contract_amount, c.closing_date,
                       p.title as property_title, p.location as property_location,
                       buyer.full_name as buyer_name, buyer.email as buyer_email,
                       seller.full_name as seller_name, seller.email as seller_email
                FROM escrow_accounts ea
                JOIN contracts c ON ea.contract_id = c.id
                JOIN properties p ON c.property_id = p.id
                JOIN users buyer ON c.buyer_id = buyer.id
                JOIN users seller ON c.seller_id = seller.id
                WHERE ea.id = ?
            ", [$escrowId]);
            
            if (!$escrow) {
                jsonResponse(['success' => false, 'error' => 'Escrow account not found'], 404);
            }
            
            // Verify user is authorized
            if (!in_array($user['id'], [$escrow['buyer_id'], $escrow['seller_id']]) && $user['role'] !== 'admin') {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
            }
            
            // Get transaction history
            $transactions = fetchAll("
                SELECT et.*, u.full_name as authorized_by_name
                FROM escrow_transactions et
                JOIN users u ON et.authorized_by = u.id
                WHERE et.escrow_account_id = ?
                ORDER BY et.created_at DESC
            ", [$escrowId]);
            
            // Format data
            $escrow['total_amount_formatted'] = formatCurrency($escrow['total_amount']);
            $escrow['deposited_amount_formatted'] = formatCurrency($escrow['deposited_amount']);
            $escrow['released_amount_formatted'] = formatCurrency($escrow['released_amount']);
            $escrow['available_amount'] = $escrow['deposited_amount'] - $escrow['released_amount'];
            $escrow['available_amount_formatted'] = formatCurrency($escrow['available_amount']);
            $escrow['created_ago'] = timeAgo($escrow['created_at']);
            
            foreach ($transactions as &$transaction) {
                $transaction['amount_formatted'] = formatCurrency($transaction['amount']);
                $transaction['created_ago'] = timeAgo($transaction['created_at']);
                $transaction['completed_ago'] = $transaction['completed_at'] ? timeAgo($transaction['completed_at']) : null;
            }
            
            jsonResponse([
                'success' => true,
                'escrow' => $escrow,
                'transactions' => $transactions
            ]);
            
        } catch (Exception $e) {
            error_log("Get escrow details error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch escrow details'], 500);
        }
    }
    
    /**
     * Get user's escrow accounts
     */
    public function getUserEscrowAccounts($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause
            $whereConditions = ["(c.buyer_id = ? OR c.seller_id = ?)"];
            $queryParams = [$user['id'], $user['id']];
            
            // Status filter
            if (!empty($params['status'])) {
                $whereConditions[] = "ea.status = ?";
                $queryParams[] = $params['status'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) as total 
                FROM escrow_accounts ea
                JOIN contracts c ON ea.contract_id = c.id
                WHERE {$whereClause}
            ";
            $totalResult = fetchOne($countSql, $queryParams);
            $totalRecords = $totalResult['total'];
            
            // Get escrow accounts
            $sql = "
                SELECT ea.*, 
                       c.contract_amount, c.closing_date,
                       p.title as property_title, p.location as property_location,
                       buyer.full_name as buyer_name,
                       seller.full_name as seller_name,
                       CASE WHEN c.buyer_id = ? THEN 'buyer' ELSE 'seller' END as user_role
                FROM escrow_accounts ea
                JOIN contracts c ON ea.contract_id = c.id
                JOIN properties p ON c.property_id = p.id
                JOIN users buyer ON c.buyer_id = buyer.id
                JOIN users seller ON c.seller_id = seller.id
                WHERE {$whereClause}
                ORDER BY ea.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $queryParams[] = $user['id'];
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;
            
            $escrowAccounts = fetchAll($sql, $queryParams);
            
            // Format data
            foreach ($escrowAccounts as &$escrow) {
                $escrow['total_amount_formatted'] = formatCurrency($escrow['total_amount']);
                $escrow['deposited_amount_formatted'] = formatCurrency($escrow['deposited_amount']);
                $escrow['released_amount_formatted'] = formatCurrency($escrow['released_amount']);
                $escrow['available_amount'] = $escrow['deposited_amount'] - $escrow['released_amount'];
                $escrow['available_amount_formatted'] = formatCurrency($escrow['available_amount']);
                $escrow['created_ago'] = timeAgo($escrow['created_at']);
                $escrow['progress_percentage'] = $escrow['total_amount'] > 0 
                    ? round(($escrow['deposited_amount'] / $escrow['total_amount']) * 100, 1) 
                    : 0;
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'escrow_accounts' => $escrowAccounts,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get user escrow accounts error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch escrow accounts'], 500);
        }
    }
    
    /**
     * Dispute escrow transaction
     */
    public function disputeEscrow($escrowId, $data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        if (empty($data['dispute_reason'])) {
            jsonResponse(['success' => false, 'error' => 'Dispute reason is required'], 400);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Get escrow account with contract details
            $escrow = fetchOne("
                SELECT ea.*, c.buyer_id, c.seller_id, c.property_id
                FROM escrow_accounts ea
                JOIN contracts c ON ea.contract_id = c.id
                WHERE ea.id = ?
            ", [$escrowId]);
            
            if (!$escrow) {
                jsonResponse(['success' => false, 'error' => 'Escrow account not found'], 404);
            }
            
            // Verify user is involved in the contract
            if (!in_array($user['id'], [$escrow['buyer_id'], $escrow['seller_id']])) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
            }
            
            // Update escrow status to disputed
            executeQuery("UPDATE escrow_accounts SET status = 'disputed', updated_at = NOW() WHERE id = ?", [$escrowId]);
            
            // Create dispute record
            $respondentId = $user['id'] == $escrow['buyer_id'] ? $escrow['seller_id'] : $escrow['buyer_id'];
            
            $disputeSql = "
                INSERT INTO disputes (
                    contract_id, property_id, complainant_id, respondent_id, 
                    dispute_type, subject, description, status, priority
                ) VALUES (?, ?, ?, ?, 'payment_issue', 'Escrow Dispute', ?, 'open', 'high')
            ";
            
            executeQuery($disputeSql, [
                $escrow['contract_id'],
                $escrow['property_id'],
                $user['id'],
                $respondentId,
                sanitize($data['dispute_reason'])
            ]);
            
            $disputeId = lastInsertId();
            
            // Log audit
            logAudit($user['id'], 'escrow_dispute', 'escrow_accounts', $escrowId, null, $data);
            
            // Send notifications
            sendNotification(
                $respondentId,
                'system',
                'Escrow Dispute Filed',
                'A dispute has been filed regarding the escrow account. An admin will review the case.',
                ['escrow_id' => $escrowId, 'dispute_id' => $disputeId]
            );
            
            // Notify admins
            $admins = fetchAll("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            foreach ($admins as $admin) {
                sendNotification(
                    $admin['id'],
                    'system',
                    'New Escrow Dispute',
                    'A new escrow dispute requires admin attention.',
                    ['escrow_id' => $escrowId, 'dispute_id' => $disputeId]
                );
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'Dispute filed successfully. An admin will review your case.',
                'dispute_id' => $disputeId
            ]);
            
        } catch (Exception $e) {
            error_log("Dispute escrow error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to file dispute'], 500);
        }
    }
}
?>
