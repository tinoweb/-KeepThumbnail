<?php
// Configurações da API Gemini

// Chave da API do Google Gemini
define('GEMINI_API_KEY', 'AIzaSyDORTlD4BaConWE0spjrZ29PZz_oEebZgk');

// URL base da API
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

// Configurações do modelo
define('GEMINI_MODEL', 'gemini-2.5-flash');
define('GEMINI_API_VERSION', 'v1beta');

// Configurações de timeout
define('GEMINI_TIMEOUT', 60);
define('GEMINI_CONNECT_TIMEOUT', 30);

// Configurações de geração
define('GEMINI_TEMPERATURE', 0.4);
define('GEMINI_MAX_TOKENS', 512);
define('GEMINI_TOP_K', 32);
define('GEMINI_TOP_P', 1);

// Idiomas suportados
define('GEMINI_SUPPORTED_LANGUAGES', [
    'pt-BR' => 'Português (Brasil)',
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'it' => 'Italiano',
    'ja' => '日本語',
    'ko' => '한국어',
    'zh' => '中文'
]);

// Tipos de arquivo suportados para análise
define('GEMINI_SUPPORTED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/bmp',
    'image/tiff'
]);

// Tamanho máximo de arquivo para análise (em bytes)
define('GEMINI_MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB

// Configurações de rate limiting
define('GEMINI_REQUESTS_PER_MINUTE', 60);
define('GEMINI_REQUESTS_PER_DAY', 1500);

// Configurações de cache
define('GEMINI_CACHE_ENABLED', true);
define('GEMINI_CACHE_TTL', 3600); // 1 hora

// Configurações de log
define('GEMINI_LOG_ENABLED', true);
define('GEMINI_LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('GEMINI_LOG_FILE', DATA_DIR . 'gemini_api.log');

/**
 * Verifica se a API key está configurada
 */
function isGeminiConfigured() {
    return !empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'your-api-key-here';
}

/**
 * Obtém a configuração completa do Gemini
 */
function getGeminiConfig() {
    return [
        'api_key' => GEMINI_API_KEY,
        'api_url' => GEMINI_API_URL,
        'model' => GEMINI_MODEL,
        'version' => GEMINI_API_VERSION,
        'timeout' => GEMINI_TIMEOUT,
        'connect_timeout' => GEMINI_CONNECT_TIMEOUT,
        'temperature' => GEMINI_TEMPERATURE,
        'max_tokens' => GEMINI_MAX_TOKENS,
        'top_k' => GEMINI_TOP_K,
        'top_p' => GEMINI_TOP_P,
        'supported_languages' => GEMINI_SUPPORTED_LANGUAGES,
        'supported_mime_types' => GEMINI_SUPPORTED_MIME_TYPES,
        'max_file_size' => GEMINI_MAX_FILE_SIZE,
        'requests_per_minute' => GEMINI_REQUESTS_PER_MINUTE,
        'requests_per_day' => GEMINI_REQUESTS_PER_DAY,
        'cache_enabled' => GEMINI_CACHE_ENABLED,
        'cache_ttl' => GEMINI_CACHE_TTL,
        'log_enabled' => GEMINI_LOG_ENABLED,
        'log_level' => GEMINI_LOG_LEVEL,
        'configured' => isGeminiConfigured()
    ];
}

/**
 * Valida se um tipo MIME é suportado
 */
function isGeminiMimeTypeSupported($mimeType) {
    return in_array($mimeType, GEMINI_SUPPORTED_MIME_TYPES);
}

/**
 * Valida se um arquivo é adequado para análise
 */
function validateGeminiFile($filePath) {
    if (!file_exists($filePath)) {
        return ['valid' => false, 'error' => 'Arquivo não encontrado'];
    }
    
    $fileSize = filesize($filePath);
    if ($fileSize > GEMINI_MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'Arquivo muito grande (máx: ' . formatBytes(GEMINI_MAX_FILE_SIZE) . ')'];
    }
    
    $mimeType = mime_content_type($filePath);
    if (!isGeminiMimeTypeSupported($mimeType)) {
        return ['valid' => false, 'error' => 'Tipo de arquivo não suportado: ' . $mimeType];
    }
    
    return ['valid' => true];
}

function geminiWriteLog($entry) {
    if (!GEMINI_LOG_ENABLED) {
        return;
    }
    $dir = dirname(GEMINI_LOG_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $entry['timestamp'] = date('Y-m-d H:i:s');
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents(GEMINI_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
?>
