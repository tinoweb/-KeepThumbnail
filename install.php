<?php
// Instalador automático do KeepThumbnail

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

function checkRequirements() {
    global $errors, $success;
    
    // Verificar versão do PHP
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $errors[] = "PHP 7.4 ou superior é necessário. Versão atual: " . PHP_VERSION;
    } else {
        $success[] = "Versão do PHP: " . PHP_VERSION . " ✓";
    }
    
    // Verificar extensões
    $requiredExtensions = ['sqlite3', 'gd', 'fileinfo'];
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extensão PHP '{$ext}' não está carregada";
        } else {
            $success[] = "Extensão '{$ext}' carregada ✓";
        }
    }
    
    return empty($errors);
}

function createDirectories() {
    global $errors, $success;
    
    $directories = [
        __DIR__ . '/uploads/',
        __DIR__ . '/data/',
        __DIR__ . '/assets/',
        __DIR__ . '/assets/css/',
        __DIR__ . '/assets/js/',
        __DIR__ . '/config/',
        __DIR__ . '/classes/'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                $success[] = "Diretório criado: " . basename($dir) . " ✓";
            } else {
                $errors[] = "Não foi possível criar o diretório: " . basename($dir);
            }
        } else {
            $success[] = "Diretório já existe: " . basename($dir) . " ✓";
        }
        
        // Verificar permissões de escrita
        if (!is_writable($dir)) {
            $errors[] = "Diretório não é gravável: " . basename($dir);
        }
    }
    
    return empty($errors);
}

function setupDatabase() {
    global $errors, $success;
    
    try {
        require_once 'config/config.php';
        require_once 'config/database.php';
        
        $db = Database::getInstance();
        $success[] = "Banco de dados SQLite configurado ✓";
        
        // Verificar se as tabelas foram criadas
        $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
        if (count($tables) > 0) {
            $success[] = "Tabelas do banco criadas ✓";
        } else {
            $errors[] = "Nenhuma tabela encontrada no banco";
        }
        
        return true;
    } catch (Exception $e) {
        $errors[] = "Erro ao configurar banco: " . $e->getMessage();
        return false;
    }
}

function createHtaccess() {
    global $errors, $success;
    
    $htaccessContent = '# KeepThumbnail Security
<Files "*.db">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

<Directory "uploads">
    <Files "*.php">
        Order allow,deny
        Deny from all
    </Files>
</Directory>';
    
    if (file_put_contents(__DIR__ . '/.htaccess', $htaccessContent)) {
        $success[] = "Arquivo .htaccess criado ✓";
        return true;
    } else {
        $errors[] = "Não foi possível criar .htaccess";
        return false;
    }
}

// Processar etapas
switch ($step) {
    case 2:
        checkRequirements();
        createDirectories();
        break;
    case 3:
        setupDatabase();
        createHtaccess();
        break;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - KeepThumbnail</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-images text-blue-600"></i> KeepThumbnail
                </h1>
                <p class="text-gray-600">Instalação do Sistema</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Progresso da Instalação</span>
                    <span class="text-sm text-gray-600"><?php echo min($step, 3); ?>/3</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo (min($step, 3) / 3) * 100; ?>%"></div>
                </div>
            </div>

            <?php if ($step == 1): ?>
            <!-- Etapa 1: Boas-vindas -->
            <div class="text-center">
                <h2 class="text-2xl font-semibold mb-4">Bem-vindo ao KeepThumbnail!</h2>
                <p class="text-gray-600 mb-6">
                    Este assistente irá configurar seu sistema de gerenciamento de thumbnails.
                    O processo é rápido e automático.
                </p>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-blue-800 mb-2">O que será instalado:</h3>
                    <ul class="text-left text-blue-700 space-y-1">
                        <li><i class="fas fa-check text-green-600 mr-2"></i> Banco de dados SQLite</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i> Estrutura de diretórios</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i> Configurações de segurança</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i> Interface web completa</li>
                    </ul>
                </div>
                
                <a href="?step=2" class="bg-blue-600 text-white px-8 py-3 rounded-md hover:bg-blue-700 transition duration-200 inline-block">
                    <i class="fas fa-play mr-2"></i> Iniciar Instalação
                </a>
            </div>

            <?php elseif ($step == 2): ?>
            <!-- Etapa 2: Verificação de requisitos -->
            <h2 class="text-2xl font-semibold mb-6">Verificação de Requisitos</h2>
            
            <?php if (!empty($success)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <h3 class="font-semibold text-green-800 mb-2">Requisitos Atendidos:</h3>
                <ul class="text-green-700 space-y-1">
                    <?php foreach ($success as $msg): ?>
                    <li><?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <h3 class="font-semibold text-red-800 mb-2">Problemas Encontrados:</h3>
                <ul class="text-red-700 space-y-1">
                    <?php foreach ($errors as $error): ?>
                    <li><i class="fas fa-times mr-2"></i><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="text-center">
                <p class="text-gray-600 mb-4">Corrija os problemas acima antes de continuar.</p>
                <a href="?step=2" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-redo mr-2"></i> Verificar Novamente
                </a>
            </div>
            <?php else: ?>
            <div class="text-center">
                <p class="text-green-600 mb-4 font-semibold">Todos os requisitos foram atendidos!</p>
                <a href="?step=3" class="bg-blue-600 text-white px-8 py-3 rounded-md hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-arrow-right mr-2"></i> Continuar Instalação
                </a>
            </div>
            <?php endif; ?>

            <?php elseif ($step == 3): ?>
            <!-- Etapa 3: Configuração final -->
            <h2 class="text-2xl font-semibold mb-6">Configuração Final</h2>
            
            <?php if (!empty($success)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <h3 class="font-semibold text-green-800 mb-2">Configurações Aplicadas:</h3>
                <ul class="text-green-700 space-y-1">
                    <?php foreach ($success as $msg): ?>
                    <li><?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <h3 class="font-semibold text-red-800 mb-2">Problemas na Configuração:</h3>
                <ul class="text-red-700 space-y-1">
                    <?php foreach ($errors as $error): ?>
                    <li><i class="fas fa-times mr-2"></i><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (empty($errors)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <div class="text-center">
                    <i class="fas fa-check-circle text-green-600 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-green-800 mb-2">Instalação Concluída!</h3>
                    <p class="text-green-700 mb-4">
                        O KeepThumbnail foi instalado com sucesso e está pronto para uso.
                    </p>
                </div>
            </div>
            
            <div class="text-center space-y-4">
                <a href="index.php" class="bg-blue-600 text-white px-8 py-3 rounded-md hover:bg-blue-700 transition duration-200 inline-block">
                    <i class="fas fa-home mr-2"></i> Acessar Sistema
                </a>
                
                <div>
                    <a href="test.php" class="text-blue-600 hover:text-blue-800 underline">
                        <i class="fas fa-vial mr-1"></i> Executar Testes
                    </a>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
                    <p class="text-yellow-800 text-sm">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Importante:</strong> Por segurança, remova ou renomeie este arquivo (install.php) após a instalação.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center">
                <a href="?step=3" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-redo mr-2"></i> Tentar Novamente
                </a>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
        
        <div class="text-center mt-6 text-gray-500 text-sm">
            KeepThumbnail v1.0.0 - Sistema de Gerenciamento de Thumbnails
        </div>
    </div>
</body>
</html>
