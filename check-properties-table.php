<?php
/**
 * Check Properties Table Structure
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Check if properties table exists
    $result = $db->query("SHOW TABLES LIKE 'properties'");
    $tableExists = $result->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'error' => 'Properties table does not exist'
        ]);
        exit;
    }
    
    // Get table structure
    $result = $db->query("DESCRIBE properties");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for required columns
    $requiredColumns = [
        'id', 'user_id', 'title', 'description', 'price', 'area_sqm', 
        'hectares', 'zoning', 'type', 'region', 'province', 'city', 
        'barangay', 'contact_name', 'contact_phone', 'status', 
        'created_at', 'updated_at'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    // Get sample data
    $sampleResult = $db->query("SELECT * FROM properties LIMIT 3");
    $sampleData = $sampleResult->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'table_exists' => $tableExists,
        'columns' => $columns,
        'existing_columns' => $existingColumns,
        'required_columns' => $requiredColumns,
        'missing_columns' => $missingColumns,
        'sample_data' => $sampleData,
        'total_records' => $db->query("SELECT COUNT(*) FROM properties")->fetchColumn()
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
