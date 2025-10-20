<?php
// API para obter dados de um thumbnail específico

header('Content-Type: application/json');

require_once 'includes/security.php';
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/ThumbnailManager.php';
require_once 'classes/AuthManager.php';

// Verificar autenticação
$auth = AuthManager::getInstance();
$auth->requireAuth();

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID do thumbnail é obrigatório');
    }
    
    $thumbnailManager = new ThumbnailManager();
    $thumbnail = $thumbnailManager->getThumbnailById($id);
    
    if (!$thumbnail) {
        throw new Exception('Thumbnail não encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'thumbnail' => $thumbnail
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
