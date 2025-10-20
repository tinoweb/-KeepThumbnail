<?php
require_once 'includes/security.php';
require_once 'classes/AuthManager.php';

$auth = AuthManager::getInstance();
$auth->requireAuth();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Sistema de Autenticação</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-shield-alt text-green-600"></i> Sistema Protegido
                    </h1>
                    <p class="text-gray-600">Você está autenticado com sucesso!</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="bg-blue-600 text-white font-bold px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-home mr-2"></i> Início
                    </a>
                    
                    <a href="logout.php" class="bg-red-600 text-white font-bold px-4 py-2 rounded-md hover:bg-red-700 transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>

        <!-- Informações da Sessão -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-info-circle text-blue-600"></i> Informações da Sessão
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-green-600 mr-3"></i>
                        <div>
                            <strong>Login realizado em:</strong><br>
                            <span class="text-gray-600">
                                <?php echo date('d/m/Y H:i:s', $_SESSION['login_time'] ?? time()); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-network-wired text-blue-600 mr-3"></i>
                        <div>
                            <strong>IP de origem:</strong><br>
                            <span class="text-gray-600 font-mono">
                                <?php echo $_SESSION['user_ip'] ?? 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center">
                        <i class="fas fa-key text-purple-600 mr-3"></i>
                        <div>
                            <strong>ID da Sessão:</strong><br>
                            <span class="text-gray-600 font-mono text-sm">
                                <?php echo substr(session_id(), 0, 16) . '...'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-hourglass-half text-orange-600 mr-3"></i>
                        <div>
                            <strong>Sessão expira em:</strong><br>
                            <span class="text-gray-600">
                                <?php 
                                $loginTime = $_SESSION['login_time'] ?? time();
                                $expirationTime = $loginTime + 14400; // 4 horas
                                $remainingTime = $expirationTime - time();
                                $hours = floor($remainingTime / 3600);
                                $minutes = floor(($remainingTime % 3600) / 60);
                                echo "{$hours}h {$minutes}min";
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recursos de Segurança -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-shield-alt text-green-600"></i> Recursos de Segurança Implementados
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-lock text-green-600 mr-2"></i>
                        <h3 class="font-semibold text-green-800">Autenticação Segura</h3>
                    </div>
                    <p class="text-green-700 text-sm">
                        Sistema de login com senha protegida e verificação de sessão.
                    </p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-ban text-blue-600 mr-2"></i>
                        <h3 class="font-semibold text-blue-800">Rate Limiting</h3>
                    </div>
                    <p class="text-blue-700 text-sm">
                        Máximo 5 tentativas de login por IP em 15 minutos.
                    </p>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-sync-alt text-purple-600 mr-2"></i>
                        <h3 class="font-semibold text-purple-800">Regeneração de Sessão</h3>
                    </div>
                    <p class="text-purple-700 text-sm">
                        ID da sessão regenerado periodicamente para segurança.
                    </p>
                </div>
                
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-clock text-orange-600 mr-2"></i>
                        <h3 class="font-semibold text-orange-800">Expiração Automática</h3>
                    </div>
                    <p class="text-orange-700 text-sm">
                        Sessão expira automaticamente após 4 horas de inatividade.
                    </p>
                </div>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-network-wired text-red-600 mr-2"></i>
                        <h3 class="font-semibold text-red-800">Verificação de IP</h3>
                    </div>
                    <p class="text-red-700 text-sm">
                        Logout automático se o IP do usuário mudar.
                    </p>
                </div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-shield text-gray-600 mr-2"></i>
                        <h3 class="font-semibold text-gray-800">Headers de Segurança</h3>
                    </div>
                    <p class="text-gray-700 text-sm">
                        Headers HTTP de segurança para prevenir ataques XSS e CSRF.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
