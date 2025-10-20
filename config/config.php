<?php

// Configurações do KeepThumbnail

// Configurações gerais
define('APP_NAME', 'KeepThumbnail');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistema de Gerenciamento de Thumbnails');

// Configurações de upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'image/webp'
]);

define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Diretórios
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('DATA_DIR', __DIR__ . '/../data/');

// Configurações do banco de dados
define('DB_PATH', DATA_DIR . 'thumbnails.db');

// Configurações de paginação
define('THUMBNAILS_PER_PAGE', 20);
define('SEARCH_RESULTS_LIMIT', 50);

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hora

// Configurações de segurança
define('SHARE_TOKEN_LENGTH', 32);
define('MAX_TITLE_LENGTH', 255);
define('MAX_DESCRIPTION_LENGTH', 1000);
define('MAX_TAGS_LENGTH', 500);

// Configurações de interface
define('DEFAULT_THEME', 'light');
define('ITEMS_PER_ROW_DESKTOP', 4);
define('ITEMS_PER_ROW_TABLET', 3);
define('ITEMS_PER_ROW_MOBILE', 1);

// URLs base (configurar conforme seu ambiente)
define('BASE_URL', '');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// Configurações de log
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', DATA_DIR . 'app.log');

// Configurações de email (para futuras funcionalidades)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', '');
define('SMTP_FROM_NAME', APP_NAME);

// Configurações de backup
define('BACKUP_ENABLED', false);
define('BACKUP_INTERVAL', 86400); // 24 horas
define('BACKUP_RETENTION', 7); // 7 dias

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de desenvolvimento
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Função para obter configuração
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Função para verificar se está em modo de desenvolvimento
function isDevelopment() {
    return getConfig('DEBUG_MODE', false);
}

// Função para obter URL base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['REQUEST_URI'] ?? '');
    
    return $protocol . '://' . $host . $path;
}

// Função para obter URL de assets
function getAssetsUrl() {
    return getBaseUrl() . '/assets/';
}

// Função para obter URL de uploads
function getUploadsUrl() {
    return getBaseUrl() . '/uploads/';
}

// Função para log
function writeLog($message, $level = 'INFO') {
    if (!getConfig('LOG_ENABLED', false)) {
        return;
    }
    
    $logFile = getConfig('LOG_FILE', DATA_DIR . 'app.log');
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Função sanitizeInput() movida para includes/security.php para evitar conflitos

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para gerar token seguro
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Função para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Função para verificar se é imagem válida
function isValidImage($filePath) {
    $imageInfo = @getimagesize($filePath);
    return $imageInfo !== false;
}

// Função para redimensionar imagem (para futuras funcionalidades)
function resizeImage($sourcePath, $destPath, $maxWidth, $maxHeight, $quality = 85) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Calcular novas dimensões
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = intval($width * $ratio);
    $newHeight = intval($height * $ratio);
    
    // Criar imagem de origem
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Criar imagem de destino
    $dest = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preservar transparência para PNG e GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Redimensionar
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Salvar
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($dest, $destPath, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($dest, $destPath);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($dest, $destPath);
            break;
    }
    
    // Limpar memória
    imagedestroy($source);
    imagedestroy($dest);
    
    return $result;
}
