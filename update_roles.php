<?php
// Update database to support unified user role
try {
    $dsn = "mysql:host=localhost;dbname=terratrade_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Updating User Roles</h2>";
    
    // Modify the users table to allow 'user' role
    $pdo->exec("
        ALTER TABLE users 
        MODIFY COLUMN role ENUM('buyer', 'seller', 'admin', 'broker', 'user') DEFAULT 'user'
    ");
    
    echo "✅ Updated users table to support 'user' role<br>";
    
    // Update existing users (except admin) to have 'user' role
    $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE role != 'admin'");
    $stmt->execute();
    
    echo "✅ Updated existing users to 'user' role<br>";
    
    // Show current users
    $stmt = $pdo->query("SELECT id, email, full_name, role FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h3>Current Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p>✅ Role update completed successfully!</p>";
    echo "<p><a href='index.php'>Go to TerraTrade</a></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
