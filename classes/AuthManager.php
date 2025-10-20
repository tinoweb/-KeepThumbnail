<?php

class AuthManager {
    private static $instance = null;
    private $sessionKey = 'keepthumbnail_authenticated';
    private $passwordHash;
    
    private function __construct() {
        // Hash seguro da senha "tinoweb100"
        $this->passwordHash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // tinoweb100
        $this->startSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurações de segurança da sessão
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Mude para 1 se usar HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerar ID da sessão periodicamente para segurança
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    public function authenticate($password) {
        // Limpar tentativas antigas (mais de 15 minutos)
        $this->cleanOldAttempts();
        
        // Verificar rate limiting
        if ($this->isRateLimited()) {
            return [
                'success' => false,
                'message' => 'Muitas tentativas de login. Tente novamente em alguns minutos.',
                'rate_limited' => true
            ];
        }
        
        // Registrar tentativa
        $this->recordAttempt();
        
        // Verificar senha
        if ($password === 'tinoweb100') {
            $_SESSION[$this->sessionKey] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['user_ip'] = $this->getUserIP();
            
            // Limpar tentativas após login bem-sucedido
            $this->clearAttempts();
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Senha incorreta. Tente novamente.',
            'rate_limited' => false
        ];
    }
    
    public function isAuthenticated() {
        if (!isset($_SESSION[$this->sessionKey]) || $_SESSION[$this->sessionKey] !== true) {
            return false;
        }
        
        // Verificar se a sessão não expirou (4 horas)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 14400) {
            $this->logout();
            return false;
        }
        
        // Verificar se o IP mudou (segurança adicional)
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $this->getUserIP()) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function logout() {
        unset($_SESSION[$this->sessionKey]);
        unset($_SESSION['login_time']);
        unset($_SESSION['user_ip']);
        session_regenerate_id(true);
    }
    
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            // Se for uma requisição AJAX, retornar JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Não autenticado']);
                exit;
            }
            
            // Redirecionar para página de login
            $currentPage = $_SERVER['REQUEST_URI'];
            header('Location: login.php?redirect=' . urlencode($currentPage));
            exit;
        }
    }
    
    private function getUserIP() {
        // Obter IP real do usuário (considerando proxies)
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    private function recordAttempt() {
        $ip = $this->getUserIP();
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        if (!isset($_SESSION['login_attempts'][$ip])) {
            $_SESSION['login_attempts'][$ip] = [];
        }
        $_SESSION['login_attempts'][$ip][] = time();
    }
    
    private function isRateLimited() {
        $ip = $this->getUserIP();
        if (!isset($_SESSION['login_attempts'][$ip])) {
            return false;
        }
        
        $attempts = $_SESSION['login_attempts'][$ip];
        $recentAttempts = array_filter($attempts, function($time) {
            return (time() - $time) < 900; // 15 minutos
        });
        
        return count($recentAttempts) >= 5; // Máximo 5 tentativas em 15 minutos
    }
    
    private function clearAttempts() {
        $ip = $this->getUserIP();
        if (isset($_SESSION['login_attempts'][$ip])) {
            unset($_SESSION['login_attempts'][$ip]);
        }
    }
    
    private function cleanOldAttempts() {
        if (!isset($_SESSION['login_attempts'])) {
            return;
        }
        
        foreach ($_SESSION['login_attempts'] as $ip => $attempts) {
            $_SESSION['login_attempts'][$ip] = array_filter($attempts, function($time) {
                return (time() - $time) < 900; // Manter apenas tentativas dos últimos 15 minutos
            });
            
            if (empty($_SESSION['login_attempts'][$ip])) {
                unset($_SESSION['login_attempts'][$ip]);
            }
        }
    }
    
    public function getLoginAttempts() {
        $ip = $this->getUserIP();
        if (!isset($_SESSION['login_attempts'][$ip])) {
            return 0;
        }
        
        $attempts = $_SESSION['login_attempts'][$ip];
        $recentAttempts = array_filter($attempts, function($time) {
            return (time() - $time) < 900; // 15 minutos
        });
        
        return count($recentAttempts);
    }
    
    public function getRemainingLockoutTime() {
        $ip = $this->getUserIP();
        if (!isset($_SESSION['login_attempts'][$ip])) {
            return 0;
        }
        
        $attempts = $_SESSION['login_attempts'][$ip];
        if (count($attempts) < 5) {
            return 0;
        }
        
        $lastAttempt = max($attempts);
        $lockoutEnd = $lastAttempt + 900; // 15 minutos
        $remaining = $lockoutEnd - time();
        
        return max(0, $remaining);
    }
}
