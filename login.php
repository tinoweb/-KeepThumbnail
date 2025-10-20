<?php
require_once 'includes/security.php';
require_once 'classes/AuthManager.php';

$auth = AuthManager::getInstance();
$message = '';
$messageType = '';
$showRateLimit = false;
$remainingTime = 0;

// Se já está autenticado, redirecionar
if ($auth->isAuthenticated()) {
    $redirect = $_GET['redirect'] ?? 'index.php';
    header('Location: ' . $redirect);
    exit;
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $result = $auth->authenticate($_POST['password']);
    
    if ($result['success']) {
        $redirect = $_GET['redirect'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $message = $result['message'];
        $messageType = 'error';
        $showRateLimit = $result['rate_limited'] ?? false;
        
        if ($showRateLimit) {
            $remainingTime = $auth->getRemainingLockoutTime();
        }
    }
}

// Verificar se há tentativas de login
$attempts = $auth->getLoginAttempts();
$maxAttempts = 5;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KeepThumbnail</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .blur-background {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .pulse-red {
            animation: pulse-red 1s infinite;
        }
        
        @keyframes pulse-red {
            0%, 100% { border-color: #ef4444; }
            50% { border-color: #dc2626; }
        }
        
        .security-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            backdrop-filter: blur(10px);
        }
        
        .attempts-indicator {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <!-- Indicador de Segurança -->
    <div class="security-indicator">
        <i class="fas fa-shield-alt mr-1"></i>
        Sistema Protegido
    </div>
    
    <!-- Background com efeito blur -->
    <div class="fixed inset-0 z-0">
        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
        <div class="absolute inset-0 blur-background"></div>
    </div>
    
    <!-- Container de Login -->
    <div class="relative z-10 w-full max-w-md">
        <div class="login-container rounded-2xl shadow-2xl p-8">
            <!-- Logo/Título -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                    <i class="fas fa-images text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">KeepThumbnail</h1>
                <p class="text-gray-600 text-sm">Acesso Restrito ao Sistema</p>
            </div>
            
            <!-- Indicador de Tentativas -->
            <?php if ($attempts > 0): ?>
            <div class="mb-4 text-center">
                <span class="attempts-indicator">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <?php echo $attempts; ?>/<?php echo $maxAttempts; ?> tentativas
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Mensagem de Erro/Sucesso -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'error' ? 'bg-red-100 border border-red-300 text-red-700' : 'bg-green-100 border border-green-300 text-green-700'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                
                <?php if ($showRateLimit && $remainingTime > 0): ?>
                <div class="mt-2 text-sm">
                    <i class="fas fa-clock mr-1"></i>
                    Tente novamente em <span id="countdown"><?php echo ceil($remainingTime / 60); ?></span> minutos
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Formulário de Login -->
            <form method="POST" class="space-y-6" id="loginForm">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key mr-1"></i>
                        Código de Acesso
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            <?php echo $showRateLimit ? 'disabled' : ''; ?>
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 <?php echo $messageType === 'error' ? 'pulse-red' : ''; ?>"
                            placeholder="Digite o código de acesso"
                            autocomplete="off"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    <?php echo $showRateLimit ? 'disabled' : ''; ?>
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <?php if ($showRateLimit): ?>
                        <i class="fas fa-lock mr-2"></i>
                        Bloqueado Temporariamente
                    <?php else: ?>
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Acessar Sistema
                    <?php endif; ?>
                </button>
            </form>
            
            <!-- Informações de Segurança -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-center text-xs text-gray-500 space-y-2">
                    <div>
                        <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                        Conexão segura e criptografada
                    </div>
                    <div>
                        <i class="fas fa-clock text-blue-500 mr-1"></i>
                        Sessão expira em 4 horas
                    </div>
                    <div>
                        <i class="fas fa-user-shield text-purple-500 mr-1"></i>
                        Máximo 5 tentativas por IP
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Rodapé -->
        <div class="text-center mt-6 text-white text-sm opacity-75">
            <i class="fas fa-copyright mr-1"></i>
            <?php echo date('Y'); ?> KeepThumbnail - Sistema de Gerenciamento de Thumbnails
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Adicionar efeito de shake no erro
        <?php if ($messageType === 'error'): ?>
        document.getElementById('loginForm').classList.add('shake');
        setTimeout(() => {
            document.getElementById('loginForm').classList.remove('shake');
        }, 500);
        <?php endif; ?>
        
        // Countdown para rate limit
        <?php if ($showRateLimit && $remainingTime > 0): ?>
        let remainingSeconds = <?php echo $remainingTime; ?>;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            remainingSeconds--;
            const minutes = Math.ceil(remainingSeconds / 60);
            countdownElement.textContent = minutes;
            
            if (remainingSeconds <= 0) {
                clearInterval(countdownInterval);
                location.reload();
            }
        }, 1000);
        <?php endif; ?>
        
        // Auto-focus no campo de senha
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            if (!passwordField.disabled) {
                passwordField.focus();
            }
        });
        
        // Prevenir múltiplos submits
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verificando...';
        });
    </script>
</body>
</html>
