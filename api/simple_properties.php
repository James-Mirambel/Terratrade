<?php
/**
 * Simple Properties API Endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Database connection
    $dsn = "mysql:host=localhost;dbname=terratrade_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get properties
    $page = $_GET['page'] ?? 1;
    $pageSize = $_GET['page_size'] ?? 20;
    $offset = ($page - 1) * $pageSize;
    
    // Build WHERE clause for filters
    $where = "WHERE p.status = 'active'";
    $params = [];
    
    if (!empty($_GET['search'])) {
        $where .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ?)";
        $searchTerm = '%' . $_GET['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($_GET['zoning'])) {
        $where .= " AND p.zoning = ?";
        $params[] = $_GET['zoning'];
    }
    
    if (!empty($_GET['min_price'])) {
        $where .= " AND p.price >= ?";
        $params[] = $_GET['min_price'];
    }
    
    if (!empty($_GET['max_price'])) {
        $where .= " AND p.price <= ?";
        $params[] = $_GET['max_price'];
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM properties p $where";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    
    // Get properties
    $sql = "
        SELECT p.*, u.full_name as owner_name 
        FROM properties p 
        LEFT JOIN users u ON p.user_id = u.id 
        $where 
        ORDER BY p.featured DESC, p.created_at DESC 
        LIMIT $pageSize OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();
    
    // Format properties
    $formattedProperties = [];
    foreach ($properties as $property) {
        $formattedProperties[] = [
            'id' => $property['id'],
            'title' => $property['title'],
            'description' => $property['description'],
            'location' => $property['location'],
            'region' => $property['region'],
            'city' => $property['city'],
            'province' => $property['province'],
            'zoning' => $property['zoning'],
            'area_sqm' => $property['area_sqm'],
            'price' => $property['price'],
            'price_per_sqm' => $property['price_per_sqm'],
            'listing_type' => $property['listing_type'],
            'status' => $property['status'],
            'featured' => (bool)$property['featured'],
            'owner_name' => $property['owner_name'],
            'created_at' => $property['created_at'],
            'formatted_price' => '₱' . number_format($property['price']),
            'formatted_area' => number_format($property['area_sqm']) . ' sqm'
        ];
    }
    
    // Pagination info
    $totalPages = ceil($totalRecords / $pageSize);
    
    $response = [
        'success' => true,
        'properties' => $formattedProperties,
        'pagination' => [
            'current_page' => (int)$page,
            'page_size' => (int)$pageSize,
            'total_records' => (int)$totalRecords,
            'total_pages' => (int)$totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load properties: ' . $e->getMessage()
    ]);
}
?>
