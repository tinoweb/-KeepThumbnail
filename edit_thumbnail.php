<?php
// Editar dados de um thumbnail

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/ThumbnailManager.php';
require_once 'classes/AuthManager.php';

// Verificar autenticação
$auth = AuthManager::getInstance();
$auth->requireAuth();

$thumbnailManager = new ThumbnailManager();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        $message = 'ID do thumbnail é obrigatório';
    } else {
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'tags' => $_POST['tags'] ?? ''
        ];
        
        $result = $thumbnailManager->updateThumbnail($id, $data);
        $message = $result['success'] ? 'Thumbnail atualizado com sucesso!' : 'Erro: ' . $result['message'];
    }
}

// Redirecionar de volta para a galeria
header('Location: gallery.php' . ($message ? '?message=' . urlencode($message) : ''));
exit;
