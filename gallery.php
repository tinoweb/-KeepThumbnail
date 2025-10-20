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
$message = $_GET['message'] ?? '';

switch ($action) {
    case 'delete':
        if (isset($_GET['id'])) {
            $result = $thumbnailManager->deleteThumbnail($_GET['id']);
            $message = $result['success'] ? 'Thumbnail excluído com sucesso!' : 'Erro: ' . $result['message'];
        }
        break;
}

// Buscar thumbnails
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$thumbnails = $thumbnailManager->getThumbnails($search, $perPage, $offset);
$stats = $thumbnailManager->getStats();

// Calcular paginação
$totalThumbnails = $thumbnailManager->getTotalCount($search);
$totalPages = ceil($totalThumbnails / $perPage);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria - KeepThumbnail</title>
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
                        <i class="fas fa-images text-blue-600"></i> Galeria de Thumbnails
                    </h1>
                    <p class="text-gray-600">Visualize e gerencie seus thumbnails</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="bg-green-600 text-white font-bold px-4 py-2 rounded-md hover:bg-green-700 transition duration-200">
                        <i class="fas fa-plus mr-2"></i> Novo Upload
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

        <!-- Busca e Filtros -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <form method="GET" class="flex-1 flex gap-4">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Buscar por título, descrição ou tags..."
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                    <?php if ($search): ?>
                    <a href="gallery.php" class="bg-red-600 text-white font-bold px-6 py-2 rounded-md hover:bg-red-700 transition duration-200">
                        <i class="fas fa-times mr-2"></i> Limpar
                    </a>
                    <?php endif; ?>
                </form>
                
                <div class="flex items-center space-x-4">
                    <!-- Filtros adicionais -->
                    <select onchange="filterByDate(this.value)" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos os períodos</option>
                        <option value="today">Hoje</option>
                        <option value="week">Esta semana</option>
                        <option value="month">Este mês</option>
                    </select>
                    
                    <select onchange="changeViewMode(this.value)" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="grid">Grade</option>
                        <option value="list">Lista</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Paginação Superior -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Mostrando <?php echo min($offset + 1, $totalThumbnails); ?> - <?php echo min($offset + $perPage, $totalThumbnails); ?> de <?php echo $totalThumbnails; ?> thumbnails
                </div>
                
                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-1 <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Grid de Thumbnails -->
        <div id="thumbnails-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($thumbnails as $thumbnail): ?>
            <div class="thumbnail-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-200">
                <div class="relative">
                    <img src="<?php echo htmlspecialchars($thumbnail['file_path']); ?>" 
                         alt="<?php echo htmlspecialchars($thumbnail['title']); ?>"
                         class="thumbnail-image cursor-pointer"
                         onclick="viewFullscreen('<?php echo htmlspecialchars($thumbnail['file_path']); ?>', '<?php echo htmlspecialchars($thumbnail['title']); ?>')">
                    
                    <div class="absolute top-2 right-2 flex gap-2">
                        <a href="download.php?id=<?php echo $thumbnail['id']; ?>" 
                           class="action-btn bg-green-600 hover:bg-green-700"
                           title="Download">
                            <i class="fas fa-download text-sm"></i>
                        </a>
                        
                        <button onclick="copyShareLink(<?php echo $thumbnail['id']; ?>)" 
                                class="action-btn bg-blue-600 hover:bg-blue-700"
                                title="Copiar link">
                            <i class="fas fa-share text-sm"></i>
                        </button>
                        
                        <button onclick="editThumbnail(<?php echo $thumbnail['id']; ?>)" 
                                class="action-btn bg-yellow-600 hover:bg-yellow-700"
                                title="Editar">
                            <i class="fas fa-edit text-sm"></i>
                        </button>
                        
                        <button onclick="confirmDelete(<?php echo $thumbnail['id']; ?>, '<?php echo htmlspecialchars($thumbnail['title']); ?>')" 
                                class="action-btn bg-red-600 hover:bg-red-700"
                                title="Excluir">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                    
                    <div class="absolute bottom-2 left-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs">
                        <?php echo $thumbnail['width']; ?>x<?php echo $thumbnail['height']; ?>
                    </div>
                </div>
                
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 mb-2 truncate" title="<?php echo htmlspecialchars($thumbnail['title']); ?>">
                        <?php echo htmlspecialchars($thumbnail['title']); ?>
                    </h3>
                    
                    <?php if ($thumbnail['description']): ?>
                    <p class="text-gray-600 text-sm mb-2 line-clamp-2" title="<?php echo htmlspecialchars($thumbnail['description']); ?>">
                        <?php echo htmlspecialchars(substr($thumbnail['description'], 0, 100)); ?>
                        <?php if (strlen($thumbnail['description']) > 100): ?>...<?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($thumbnail['tags']): ?>
                    <div class="flex flex-wrap gap-1 mb-2">
                        <?php foreach (array_slice(explode(',', $thumbnail['tags']), 0, 3) as $tag): ?>
                        <span class="tag cursor-pointer" onclick="filterByTag('<?php echo htmlspecialchars(trim($tag)); ?>')">
                            <?php echo htmlspecialchars(trim($tag)); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count(explode(',', $thumbnail['tags'])) > 3): ?>
                        <span class="text-xs text-gray-500">+<?php echo count(explode(',', $thumbnail['tags'])) - 3; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between text-xs text-gray-500 mt-2">
                        <div>
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('d/m/Y', strtotime($thumbnail['created_at'])); ?>
                        </div>
                        <div>
                            <i class="fas fa-download mr-1"></i>
                            <?php echo $thumbnail['download_count']; ?>
                        </div>
                        <div>
                            <?php echo number_format($thumbnail['file_size'] / 1024, 1); ?> KB
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($thumbnails)): ?>
        <div class="empty-state">
            <i class="fas fa-images"></i>
            <h3 class="text-xl text-gray-600 mb-2">Nenhum thumbnail encontrado</h3>
            <p class="text-gray-500 mb-4">
                <?php echo $search ? 'Tente uma busca diferente.' : 'Comece enviando seu primeiro thumbnail!'; ?>
            </p>
            <?php if (!$search): ?>
            <a href="index.php" class="bg-blue-600 text-white font-bold px-6 py-3 rounded-md hover:bg-blue-700 transition duration-200">
                <i class="fas fa-plus mr-2"></i> Enviar Primeiro Thumbnail
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Paginação Inferior -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-white rounded-lg shadow-md p-4 mt-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <span class="px-4 py-2 text-gray-700">
                        Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Edição -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Editar Thumbnail</h3>
                <button onclick="closeModal('edit-modal')" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            
            <form id="edit-form" method="POST" action="edit_thumbnail.php">
                <input type="hidden" id="edit-id" name="id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                    <input type="text" id="edit-title" name="title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea id="edit-description" name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags (separadas por vírgula)</label>
                    <input type="text" id="edit-tags" name="tags" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('edit-modal')" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Funções específicas da galeria
        function filterByTag(tag) {
            const searchInput = document.querySelector('input[name="search"]');
            searchInput.value = tag;
            searchInput.form.submit();
        }
        
        function filterByDate(period) {
            if (period) {
                window.location.href = `gallery.php?period=${period}`;
            }
        }
        
        function changeViewMode(mode) {
            const container = document.getElementById('thumbnails-container');
            if (mode === 'list') {
                container.className = 'space-y-4';
                // Implementar vista de lista se necessário
            } else {
                container.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6';
            }
        }
        
        function editThumbnail(id) {
            // Buscar dados do thumbnail via AJAX
            fetch(`get_thumbnail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit-id').value = data.thumbnail.id;
                        document.getElementById('edit-title').value = data.thumbnail.title;
                        document.getElementById('edit-description').value = data.thumbnail.description || '';
                        document.getElementById('edit-tags').value = data.thumbnail.tags || '';
                        openModal('edit-modal');
                    } else {
                        showNotification('Erro ao carregar dados do thumbnail', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erro na requisição', 'error');
                });
        }
        
        function confirmDelete(id, title) {
            if (confirm(`Tem certeza que deseja excluir o thumbnail "${title}"?`)) {
                window.location.href = `gallery.php?action=delete&id=${id}`;
            }
        }
    </script>
</body>
</html>
