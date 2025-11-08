<?php
// Debug API routing
echo "<h2>API Debug</h2>";

echo "<h3>Server Variables:</h3>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "<br>";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "<br>";

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Parsed path: " . $path . "<br>";

$path = str_replace('/Terratrade/api', '', $path);
echo "After removing /Terratrade/api: " . $path . "<br>";

$pathParts = array_filter(explode('/', $path));
echo "Path parts: " . print_r($pathParts, true) . "<br>";

$endpoint = $pathParts[0] ?? '';
$action = $pathParts[1] ?? '';
$id = $pathParts[2] ?? null;

echo "Endpoint: '$endpoint'<br>";
echo "Action: '$action'<br>";
echo "ID: '$id'<br>";

// Test direct access
echo "<h3>Test Direct API Access:</h3>";
echo "<a href='/Terratrade/api/properties/list'>Test /api/properties/list</a><br>";
echo "<a href='/Terratrade/api/'>Test /api/ (root)</a><br>";
?>
