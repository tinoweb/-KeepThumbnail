<?php
require_once 'includes/security.php';
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/ThumbnailManager.php';
require_once 'classes/AuthManager.php';

// Verificar autenticação
$auth = AuthManager::getInstance();
$auth->requireAuth();

$thumbnailManager = new ThumbnailManager();

// Processar ações
$action = $_GET['action'] ?? '';
$message = '';

switch ($action) {
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['thumbnail'])) {
            // Verificar se é upload múltiplo
            if (is_array($_FILES['thumbnail']['name'])) {
                $useGemini = isset($_POST['use_gemini']) && $_POST['use_gemini'] === '1';
                $result = $thumbnailManager->uploadMultipleThumbnails($_FILES['thumbnail'], $useGemini);
                
                if ($result['success']) {
                    $successful = $result['successful_uploads'];
                    $failed = $result['failed_uploads'];
                    $total = $result['total_files'];
                    
                    if ($failed > 0) {
                        $message = "Upload concluído: {$successful} de {$total} arquivos enviados com sucesso. {$failed} falharam.";
                    } else {
                        $message = "Todos os {$successful} thumbnails foram enviados com sucesso!";
                        // Redirecionar para galeria após upload múltiplo bem-sucedido
                        header('Location: gallery.php?message=' . urlencode($message));
                        exit;
                    }
                } else {
                    $message = 'Erro no upload múltiplo: ' . $result['message'];
                }
            } else {
                // Upload único
                $result = $thumbnailManager->uploadThumbnail($_FILES['thumbnail'], $_POST);
                if ($result['success']) {
                    $message = 'Thumbnail enviado com sucesso!';
                    // Redirecionar para galeria após upload único bem-sucedido
                    header('Location: gallery.php?message=' . urlencode($message));
                    exit;
                } else {
                    $message = 'Erro: ' . $result['message'];
                }
            }
        }
        break;
    
    case 'delete':
        if (isset($_GET['id'])) {
            $result = $thumbnailManager->deleteThumbnail($_GET['id']);
            $message = $result['success'] ? 'Thumbnail excluído com sucesso!' : 'Erro: ' . $result['message'];
        }
        break;
}

// Obter estatísticas para exibir no header
$stats = $thumbnailManager->getStats();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KeepThumbnail - Gerenciador de Thumbnails</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen" style="background: var(--gradient-primary) !important;">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-images text-blue-600"></i> KeepThumbnail
                    </h1>
                    <p class="text-gray-600">Envie seus thumbnails de forma simples e eficiente</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="gallery.php" class="bg-blue-600 text-white font-bold px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-th-large mr-2"></i> Ver Galeria
                    </a>
                    
                    <a href="logout.php" class="bg-red-600 text-white font-bold px-4 py-2 rounded-md hover:bg-red-700 transition duration-200" title="Sair do Sistema">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sair
                    </a>
                    
                    <div class="text-right text-sm text-gray-600">
                        <div><strong><?php echo $stats['total_thumbnails']; ?></strong> thumbnails</div>
                        <div><strong><?php echo $stats['total_downloads']; ?></strong> downloads</div>
                        <div><strong><?php echo $stats['total_size_formatted']; ?></strong> usado</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagem de feedback -->
        <?php if ($message): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Formulário de Upload -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-upload text-green-600"></i> Enviar Thumbnails
                </h2>
                
                <!-- Toggle para modo de upload -->
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="upload_mode" value="single" checked onchange="toggleUploadMode()" class="mr-2">
                        <span class="text-sm">Upload Único</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="upload_mode" value="multiple" onchange="toggleUploadMode()" class="mr-2">
                        <span class="text-sm">Upload Múltiplo</span>
                    </label>
                </div>
            </div>
            
            <!-- Upload Único -->
            <form id="single-upload-form" action="?action=upload" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo da Imagem</label>
                        <input type="file" name="thumbnail" accept="image/*" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                        <input type="text" name="title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Digite o título do thumbnail">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição (opcional)</label>
                    <textarea name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Descrição do thumbnail"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags (separadas por vírgula)</label>
                    <input type="text" name="tags" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="youtube, gaming, tutorial">
                </div>
                
                <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-upload mr-2"></i> Enviar Thumbnail
                </button>
            </form>
            
            <!-- Upload Múltiplo -->
            <form id="multiple-upload-form" action="?action=upload" method="POST" enctype="multipart/form-data" class="space-y-4" style="display: none;">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        <h3 class="font-semibold text-blue-800">Upload Múltiplo com IA</h3>
                    </div>
                    <p class="text-blue-700 text-sm">
                        Selecione múltiplas imagens e a IA do Gemini irá gerar nomes sugestivos automaticamente baseados no conteúdo de cada imagem.
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selecionar Múltiplas Imagens</label>
                    <div class="file-upload-area border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors">
                        <input type="file" name="thumbnail[]" accept="image/*" multiple required 
                               class="hidden" id="multiple-file-input" onchange="handleMultipleFiles(this)">
                        <label for="multiple-file-input" class="cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4 block"></i>
                            <p class="text-gray-600 mb-2">Clique aqui ou arraste múltiplas imagens</p>
                            <p class="text-sm text-gray-500">Suporta: JPG, PNG, GIF, WebP (máx. 10MB cada)</p>
                        </label>
                    </div>
                    
                    <!-- Preview das imagens selecionadas -->
                    <div id="multiple-preview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4" style="display: none;"></div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="use_gemini" value="1" checked class="mr-2">
                        <span class="text-sm font-medium text-gray-700">
                            <i class="fas fa-robot text-purple-600 mr-1"></i>
                            Usar IA Gemini para gerar nomes sugestivos
                        </span>
                    </label>
                    
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-magic mr-1"></i>
                        A IA analisará cada imagem e sugerirá nomes descritivos
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button type="submit" class="bg-green-600 text-white font-bold px-8 py-3 rounded-md hover:bg-green-700 transition duration-200 flex items-center">
                        <i class="fas fa-upload mr-2"></i>
                        <span id="upload-btn-text">Enviar Thumbnails</span>
                        <div id="upload-spinner" class="spinner ml-2" style="display: none;"></div>
                    </button>
                    
                    <div id="upload-progress" class="flex-1" style="display: none;">
                        <div class="bg-gray-200 rounded-full h-2">
                            <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p id="progress-text" class="text-sm text-gray-600 mt-1">Preparando upload...</p>
                    </div>
                </div>
            </form>
        </div>

        <!-- Seção de Ações Rápidas -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-bolt text-yellow-600"></i> Ações Rápidas
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="gallery.php" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition duration-200">
                    <div class="flex items-center">
                        <i class="fas fa-th-large text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-blue-800">Ver Galeria</h3>
                            <p class="text-blue-600 text-sm">Visualizar todos os thumbnails</p>
                        </div>
                    </div>
                </a>
                
                <a href="test_gemini_complete.php" class="bg-purple-50 border border-purple-200 rounded-lg p-4 hover:bg-purple-100 transition duration-200 text-left block">
                    <div class="flex items-center">
                        <i class="fas fa-robot text-purple-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-purple-800">Testar IA Gemini</h3>
                            <p class="text-purple-600 text-sm">Teste completo da API Gemini</p>
                        </div>
                    </div>
                </a>
                
                <a href="test.php" class="bg-green-50 border border-green-200 rounded-lg p-4 hover:bg-green-100 transition duration-200">
                    <div class="flex items-center">
                        <i class="fas fa-vial text-green-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-green-800">Testar Sistema</h3>
                            <p class="text-green-600 text-sm">Verificar funcionamento</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
