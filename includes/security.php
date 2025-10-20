<?php
// Arquivo de segurança para configurações gerais

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Remover headers que revelam informações do servidor
if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
    header_remove('Server');
}

// Configurações de segurança do PHP
ini_set('expose_php', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Função para sanitizar entrada
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map('sanitizeInput', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Função para validar token CSRF
if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Função para gerar token CSRF
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

// Função para verificar rate limiting básico
if (!function_exists('checkRateLimit')) {
    function checkRateLimit($action, $limit = 10, $window = 60) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . $action;
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        // Limpar tentativas antigas
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Verificar se excedeu o limite
        if (count($_SESSION[$key]) >= $limit) {
            return false;
        }
        
        // Registrar nova tentativa
        $_SESSION[$key][] = $now;
        return true;
    }
}

// Função para log de segurança
if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../data/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'event' => $event,
            'details' => $details
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        
        // Criar diretório se não existir
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
?>
