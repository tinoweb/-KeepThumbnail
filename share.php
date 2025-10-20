<?php
require_once 'config/database.php';
require_once 'classes/ThumbnailManager.php';

$thumbnailManager = new ThumbnailManager();

$token = $_GET['token'] ?? null;

if (!$token) {
    http_response_code(400);
    die('Token de compartilhamento é obrigatório');
}

$thumbnail = $thumbnailManager->getThumbnailByToken($token);

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

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
           '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thumbnail['title']); ?> - KeepThumbnail</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Meta tags para compartilhamento -->
    <meta property="og:title" content="<?php echo htmlspecialchars($thumbnail['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($thumbnail['description'] ?: 'Thumbnail compartilhado via KeepThumbnail'); ?>">
    <meta property="og:image" content="<?php echo $baseUrl . '/' . $thumbnail['file_path']; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $baseUrl . '/share.php?token=' . $token; ?>">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($thumbnail['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($thumbnail['description'] ?: 'Thumbnail compartilhado via KeepThumbnail'); ?>">
    <meta name="twitter:image" content="<?php echo $baseUrl . '/' . $thumbnail['file_path']; ?>">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-share text-blue-600"></i> Thumbnail Compartilhado
                    </h1>
                    <p class="text-gray-600">Via KeepThumbnail</p>
                </div>
                <a href="index.php" class="bg-blue-600 text-white font-bold px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-home mr-2"></i> Voltar ao Início
                </a>
            </div>
        </div>

        <!-- Thumbnail -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4"><?php echo htmlspecialchars($thumbnail['title']); ?></h2>
                
                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Imagem -->
                    <div class="lg:w-2/3">
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars($thumbnail['file_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($thumbnail['title']); ?>"
                                 class="w-full rounded-lg shadow-md">
                            
                            <div class="absolute bottom-4 right-4">
                                <span class="bg-black bg-opacity-75 text-white px-3 py-1 rounded-full text-sm">
                                    <?php echo $thumbnail['width']; ?>x<?php echo $thumbnail['height']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações -->
                    <div class="lg:w-1/3">
                        <?php if ($thumbnail['description']): ?>
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-800 mb-2">Descrição</h3>
                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($thumbnail['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($thumbnail['tags']): ?>
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-800 mb-2">Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (explode(',', $thumbnail['tags']) as $tag): ?>
                                <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm">
                                    <?php echo htmlspecialchars(trim($tag)); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-800 mb-2">Informações</h3>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <span>Dimensões:</span>
                                    <span><?php echo $thumbnail['width']; ?>x<?php echo $thumbnail['height']; ?>px</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tamanho:</span>
                                    <span><?php echo number_format($thumbnail['file_size'] / 1024, 1); ?> KB</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tipo:</span>
                                    <span><?php echo strtoupper(pathinfo($thumbnail['filename'], PATHINFO_EXTENSION)); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Downloads:</span>
                                    <span><?php echo $thumbnail['download_count']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Criado em:</span>
                                    <span><?php echo date('d/m/Y', strtotime($thumbnail['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ações -->
                        <div class="space-y-3">
                            <a href="download.php?token=<?php echo $token; ?>" 
                               class="w-full bg-green-600 text-white font-bold px-4 py-3 rounded-md hover:bg-green-700 transition duration-200 flex items-center justify-center">
                                <i class="fas fa-download mr-2"></i> Baixar Thumbnail
                            </a>
                            
                            <button onclick="copyShareLink()" 
                                    class="w-full bg-blue-600 text-white font-bold px-4 py-3 rounded-md hover:bg-blue-700 transition duration-200 flex items-center justify-center">
                                <i class="fas fa-link mr-2"></i> Copiar Link
                            </button>
                            
                            <button onclick="shareOnSocial()" 
                                    class="w-full bg-purple-600 text-white font-bold px-4 py-3 rounded-md hover:bg-purple-700 transition duration-200 flex items-center justify-center">
                                <i class="fas fa-share-alt mr-2"></i> Compartilhar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Links de compartilhamento social -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Compartilhar em Redes Sociais</h3>
            <div class="flex flex-wrap gap-3">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($baseUrl . '/share.php?token=' . $token); ?>" 
                   target="_blank" 
                   class="bg-blue-600 text-white font-bold px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                    <i class="fab fa-facebook-f mr-2"></i> Facebook
                </a>
                
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($baseUrl . '/share.php?token=' . $token); ?>&text=<?php echo urlencode($thumbnail['title']); ?>" 
                   target="_blank" 
                   class="bg-sky-500 text-white font-bold px-4 py-2 rounded-md hover:bg-sky-600 transition duration-200">
                    <i class="fab fa-twitter mr-2"></i> Twitter
                </a>
                
                <a href="https://wa.me/?text=<?php echo urlencode($thumbnail['title'] . ' - ' . $baseUrl . '/share.php?token=' . $token); ?>" 
                   target="_blank" 
                   class="bg-green-500 text-white font-bold px-4 py-2 rounded-md hover:bg-green-600 transition duration-200">
                    <i class="fab fa-whatsapp mr-2"></i> WhatsApp
                </a>
                
                <a href="https://t.me/share/url?url=<?php echo urlencode($baseUrl . '/share.php?token=' . $token); ?>&text=<?php echo urlencode($thumbnail['title']); ?>" 
                   target="_blank" 
                   class="bg-blue-500 text-white font-bold px-4 py-2 rounded-md hover:bg-blue-600 transition duration-200">
                    <i class="fab fa-telegram-plane mr-2"></i> Telegram
                </a>
            </div>
        </div>
    </div>

    <script>
        function copyShareLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                alert('Link copiado para a área de transferência!');
            }).catch(function(err) {
                console.error('Erro ao copiar link: ', err);
                // Fallback para navegadores mais antigos
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Link copiado para a área de transferência!');
            });
        }

        function shareOnSocial() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($thumbnail['title']); ?>',
                    text: '<?php echo addslashes($thumbnail['description'] ?: 'Thumbnail compartilhado via KeepThumbnail'); ?>',
                    url: window.location.href
                }).catch(console.error);
            } else {
                copyShareLink();
            }
        }
    </script>
</body>
</html>
