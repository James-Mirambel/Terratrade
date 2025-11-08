<?php
/**
 * API Router
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../config/config.php';

// Set JSON content type
header('Content-Type: application/json');

// Enable CORS for development (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/Terratrade/api', '', $path);
$pathParts = array_filter(explode('/', $path));

// Get request body for POST/PUT requests
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Merge with $_POST for form data
$data = array_merge($_POST, $input);

try {
    // Route the request
    if (empty($pathParts)) {
        jsonResponse(['message' => 'TerraTrade API v1.0', 'status' => 'active']);
    }
    
    $endpoint = $pathParts[0] ?? '';
    $action = $pathParts[1] ?? '';
    $id = $pathParts[2] ?? null;
    
    switch ($endpoint) {
        case 'auth':
            require_once __DIR__ . '/../controllers/AuthController.php';
            $controller = new AuthController();
            handleAuthRoutes($controller, $action, $method, $data);
            break;
            
        case 'properties':
            // Handle specific property endpoints
            if ($action === 'my-listings') {
                require_once __DIR__ . '/properties/my-listings.php';
                return;
            }
            if ($action === 'create') {
                require_once __DIR__ . '/properties/create.php';
                return;
            }
            if ($action === 'details') {
                require_once __DIR__ . '/properties/details.php';
                return;
            }
            if (is_numeric($action)) {
                // Handle /properties/{id} routes
                if ($method === 'PUT') {
                    require_once __DIR__ . '/properties/update.php';
                    return;
                } elseif ($method === 'DELETE') {
                    require_once __DIR__ . '/properties/delete.php';
                    return;
                } elseif ($method === 'GET') {
                    // Redirect to details endpoint
                    $_GET['id'] = $action;
                    require_once __DIR__ . '/properties/details.php';
                    return;
                }
            }
            require_once __DIR__ . '/../controllers/PropertyController.php';
            $controller = new PropertyController();
            handlePropertyRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'offers':
            // Handle specific offer endpoints
            if ($action === 'my-offers') {
                require_once __DIR__ . '/offers/my-offers.php';
                return;
            }
            require_once __DIR__ . '/../controllers/OfferController.php';
            $controller = new OfferController();
            handleOfferRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'users':
            require_once __DIR__ . '/../controllers/UserController.php';
            $controller = new UserController();
            handleUserRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'messages':
            // Handle specific message endpoints
            if ($action === 'unread-count') {
                require_once __DIR__ . '/messages/unread-count.php';
                return;
            }
            require_once __DIR__ . '/../controllers/MessageController.php';
            $controller = new MessageController();
            handleMessageRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'admin':
            require_once __DIR__ . '/../controllers/AdminController.php';
            $controller = new AdminController();
            handleAdminRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'kyc':
            require_once __DIR__ . '/../controllers/KYCController.php';
            $controller = new KYCController();
            handleKYCRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'auctions':
            require_once __DIR__ . '/../controllers/AuctionController.php';
            $controller = new AuctionController();
            handleAuctionRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'escrow':
            require_once __DIR__ . '/../controllers/EscrowController.php';
            $controller = new EscrowController();
            handleEscrowRoutes($controller, $action, $id, $method, $data);
            break;
            
        case 'notifications':
            require_once __DIR__ . '/notifications/index.php';
            return;
            
        default:
            jsonResponse(['error' => 'Endpoint not found'], 404);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}


// Auth routes handler
function handleAuthRoutes($controller, $action, $method, $data) {
    switch ($action) {
        case 'login':
            if ($method === 'POST') {
                $controller->login($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'register':
            if ($method === 'POST') {
                $controller->register($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'logout':
            if ($method === 'POST') {
                $controller->logout();
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'me':
            if ($method === 'GET') {
                $controller->getCurrentUser();
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'change-password':
            if ($method === 'POST') {
                $controller->changePassword($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}

// Property routes handler
function handlePropertyRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'list':
        case '':
            if ($method === 'GET') {
                $controller->getProperties($_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'create':
            if ($method === 'POST') {
                $controller->createProperty($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'update':
            if ($method === 'PUT' && $id) {
                $controller->updateProperty($id, $data);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'delete':
            if ($method === 'DELETE' && $id) {
                $controller->deleteProperty($id);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'details':
            if ($method === 'GET' && $id) {
                $controller->getPropertyDetails($id);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'favorite':
            if ($method === 'POST' && $id) {
                $controller->toggleFavorite($id);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'upload-image':
            if ($method === 'POST' && $id) {
                $controller->uploadImage($id, $_FILES);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        default:
            if (is_numeric($action)) {
                // Handle /properties/{id} format
                if ($method === 'GET') {
                    $controller->getPropertyDetails($action);
                } elseif ($method === 'PUT') {
                    $controller->updateProperty($action, $data);
                } elseif ($method === 'DELETE') {
                    $controller->deleteProperty($action);
                } else {
                    jsonResponse(['error' => 'Method not allowed'], 405);
                }
            } else {
                jsonResponse(['error' => 'Action not found'], 404);
            }
    }
}

// Offer routes handler
function handleOfferRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'create':
            if ($method === 'POST') {
                $controller->createOffer($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'list':
            if ($method === 'GET') {
                $controller->getOffers($_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'respond':
            if ($method === 'POST' && $id) {
                $controller->respondToOffer($id, $data);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'counter':
            if ($method === 'POST' && $id) {
                $controller->createCounterOffer($id, $data);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}

// User routes handler  
function handleUserRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'profile':
            if ($method === 'GET') {
                $controller->getProfile();
            } elseif ($method === 'PUT') {
                $controller->updateProfile($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'listings':
            if ($method === 'GET') {
                $controller->getUserListings($_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'offers':
            if ($method === 'GET') {
                $controller->getUserOffers($_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'favorites':
            if ($method === 'GET') {
                $controller->getUserFavorites($_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}

// Message routes handler
function handleMessageRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'conversations':
            if ($method === 'GET') {
                $controller->getConversations($_GET);
            } elseif ($method === 'POST') {
                $controller->createConversation($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'send':
            if ($method === 'POST') {
                $controller->sendMessage($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'history':
            if ($method === 'GET' && $id) {
                $controller->getMessageHistory($id, $_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}

// Admin routes handler
function handleAdminRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'dashboard':
            if ($method === 'GET') {
                $controller->getDashboard();
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'users':
            if ($method === 'GET') {
                $controller->getUsers($_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'properties':
            if ($method === 'GET') {
                $controller->getPropertiesForReview($_GET);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'approve-property':
            if ($method === 'POST' && $id) {
                $controller->approveProperty($id);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'reject-property':
            if ($method === 'POST' && $id) {
                $controller->rejectProperty($id, $data);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}

// KYC routes handler
function handleKYCRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'submit':
            if ($method === 'POST') {
                $controller->submitKYC($data, $_FILES);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'status':
            if ($method === 'GET') {
                $controller->getKYCStatus();
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}

// Auction routes handler
function handleAuctionRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'bid':
            if ($method === 'POST' && $id) {
                $controller->placeBid($id, $data);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'history':
            if ($method === 'GET' && $id) {
                $controller->getBidHistory($id);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}

// Escrow routes handler
function handleEscrowRoutes($controller, $action, $id, $method, $data) {
    switch ($action) {
        case 'create':
            if ($method === 'POST') {
                $controller->createEscrowAccount($data);
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case 'deposit':
            if ($method === 'POST' && $id) {
                $controller->depositFunds($id, $data);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        case 'release':
            if ($method === 'POST' && $id) {
                $controller->releaseFunds($id, $data);
            } else {
                jsonResponse(['error' => 'Method not allowed or ID missing'], 405);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
}
?>
