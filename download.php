<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/ThumbnailManager.php';
require_once 'classes/AuthManager.php';

// Verificar autenticação
$auth = AuthManager::getInstance();
$auth->requireAuth();

$thumbnailManager = new ThumbnailManager();

// Verificar se foi fornecido ID ou token
$id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$id && !$token) {
    http_response_code(400);
    die('ID ou token do thumbnail é obrigatório');
}

// Buscar thumbnail
if ($token) {
    $thumbnail = $thumbnailManager->getThumbnailByToken($token);
} else {
    $thumbnail = $thumbnailManager->getThumbnailById($id);
}

if (!$thumbnail) {
    http_response_code(404);
    die('Thumbnail não encontrado');
}

// Verificar se o arquivo existe
$filePath = __DIR__ . '/' . $thumbnail['file_path'];
if (!file_exists($filePath)) {
    http_response_code(404);
    die('Arquivo não encontrado no servidor');
}

// Incrementar contador de downloads
$thumbnailManager->incrementDownloadCount($thumbnail['id']);

// Preparar nome do arquivo para download
$downloadName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $thumbnail['title']);
$downloadName = $downloadName . '.' . pathinfo($thumbnail['filename'], PATHINFO_EXTENSION);

// Headers para download
header('Content-Type: ' . $thumbnail['mime_type']);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . $thumbnail['file_size']);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Enviar arquivo
readfile($filePath);
exit;
