// JavaScript para KeepThumbnail

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    setupFileUpload();
    setupDragAndDrop();
    setupImagePreview();
    setupSearch();
    setupModals();
    setupNotifications();
    setupMultipleUpload();
}

// Configurar upload de arquivo
function setupFileUpload() {
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
    }
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        validateFile(file);
        previewImage(file);
    }
}

function validateFile(file) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 10 * 1024 * 1024; // 10MB

    if (!allowedTypes.includes(file.type)) {
        showNotification('Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.', 'error');
        return false;
    }

    if (file.size > maxSize) {
        showNotification('Arquivo muito grande. Máximo 10MB.', 'error');
        return false;
    }

    return true;
}

// Configurar drag and drop
function setupDragAndDrop() {
    const uploadArea = document.querySelector('.file-upload-area');
    if (!uploadArea) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    uploadArea.addEventListener('drop', handleDrop, false);
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight(e) {
    e.currentTarget.classList.add('drag-over');
}

function unhighlight(e) {
    e.currentTarget.classList.remove('drag-over');
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        const fileInput = document.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.files = files;
            handleFileSelect({ target: { files: files } });
        }
    }
}

// Preview de imagem
function setupImagePreview() {
    // Implementado no handleFileSelect
}

function previewImage(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        let preview = document.getElementById('image-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'image-preview';
            preview.className = 'mt-4';
            
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput && fileInput.parentNode) {
                fileInput.parentNode.appendChild(preview);
            }
        }
        
        preview.innerHTML = `
            <div class="relative inline-block">
                <img src="${e.target.result}" alt="Preview" class="image-preview">
                <button type="button" onclick="removePreview()" 
                        class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-700">
                    ×
                </button>
            </div>
        `;
    };
    reader.readAsDataURL(file);
}

function removePreview() {
    const preview = document.getElementById('image-preview');
    if (preview) {
        preview.remove();
    }
    
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.value = '';
    }
}

// Configurar busca
function setupSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }
}

function handleSearch(event) {
    const query = event.target.value.trim();
    if (query.length >= 2) {
        // Implementar sugestões de busca se necessário
        showSearchSuggestions(query);
    } else {
        hideSearchSuggestions();
    }
}

function showSearchSuggestions(query) {
    // Implementar se necessário
}

function hideSearchSuggestions() {
    const suggestions = document.querySelector('.search-suggestions');
    if (suggestions) {
        suggestions.style.display = 'none';
    }
}

// Configurar modais
function setupModals() {
    // Fechar modal ao clicar fora
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Sistema de notificações
function setupNotifications() {
    // Remover notificações automaticamente após 5 segundos
    setTimeout(() => {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            notification.remove();
        });
    }, 5000);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Copiar link de compartilhamento
function copyShareLink(thumbnailId) {
    const baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
    
    // Buscar o token do thumbnail (seria necessário uma requisição AJAX)
    // Por simplicidade, vamos usar o ID por enquanto
    const shareUrl = `${baseUrl}share.php?id=${thumbnailId}`;
    
    navigator.clipboard.writeText(shareUrl).then(function() {
        showNotification('Link de compartilhamento copiado!', 'success');
    }).catch(function(err) {
        console.error('Erro ao copiar link: ', err);
        // Fallback para navegadores mais antigos
        const textArea = document.createElement('textarea');
        textArea.value = shareUrl;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Link de compartilhamento copiado!', 'success');
    });
}

// Função para visualizar thumbnail em tela cheia
function viewFullscreen(imageSrc, title) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content max-w-4xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">${title}</h3>
                <button onclick="this.closest('.modal').remove(); document.body.style.overflow = 'auto';" 
                        class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <img src="${imageSrc}" alt="${title}" class="w-full h-auto max-h-96 object-contain rounded-lg">
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

// Função para confirmar exclusão
function confirmDelete(thumbnailId, title) {
    if (confirm(`Tem certeza que deseja excluir o thumbnail "${title}"?`)) {
        window.location.href = `?action=delete&id=${thumbnailId}`;
    }
}

// Função para filtrar por tag
function filterByTag(tag) {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.value = tag;
        searchInput.form.submit();
    }
}

// Função debounce para otimizar busca
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Função para formatar tamanho de arquivo
function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

// Função para lazy loading de imagens
function setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Função para upload com progress
function uploadWithProgress(file, progressCallback) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('thumbnail', file);
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressCallback(percentComplete);
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                resolve(xhr.responseText);
            } else {
                reject(new Error('Upload failed'));
            }
        });
        
        xhr.addEventListener('error', () => {
            reject(new Error('Upload error'));
        });
        
        xhr.open('POST', '?action=upload');
        xhr.send(formData);
    });
}

// Inicializar tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.classList.add('tooltip');
    });
}

// Função para exportar dados
function exportThumbnails() {
    // Implementar exportação se necessário
    showNotification('Funcionalidade de exportação em desenvolvimento', 'info');
}

// Função para importar dados
function importThumbnails() {
    // Implementar importação se necessário
    showNotification('Funcionalidade de importação em desenvolvimento', 'info');
}

// ========== FUNCIONALIDADES DE UPLOAD MÚLTIPLO ==========

// Configurar upload múltiplo
function setupMultipleUpload() {
    const multipleInput = document.getElementById('multiple-file-input');
    const uploadArea = document.querySelector('.file-upload-area');
    
    if (multipleInput && uploadArea) {
        // Drag and drop para upload múltiplo
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.add('drag-over'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('drag-over'), false);
        });

        uploadArea.addEventListener('drop', handleMultipleDrop, false);
        
        // Interceptar submit do formulário múltiplo
        const multipleForm = document.getElementById('multiple-upload-form');
        if (multipleForm) {
            multipleForm.addEventListener('submit', handleMultipleUploadSubmit);
        }
    }
}

// Alternar entre modo único e múltiplo
function toggleUploadMode() {
    const singleForm = document.getElementById('single-upload-form');
    const multipleForm = document.getElementById('multiple-upload-form');
    const mode = document.querySelector('input[name="upload_mode"]:checked').value;
    
    if (mode === 'single') {
        singleForm.style.display = 'block';
        multipleForm.style.display = 'none';
    } else {
        singleForm.style.display = 'none';
        multipleForm.style.display = 'block';
    }
}

// Manipular arquivos múltiplos selecionados
function handleMultipleFiles(input) {
    const files = Array.from(input.files);
    if (files.length === 0) return;
    
    // Validar arquivos
    const validFiles = files.filter(file => {
        if (!file.type.startsWith('image/')) {
            showNotification(`${file.name} não é uma imagem válida`, 'error');
            return false;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB
            showNotification(`${file.name} é muito grande (máx. 10MB)`, 'error');
            return false;
        }
        
        return true;
    });
    
    if (validFiles.length !== files.length) {
        // Atualizar input com apenas arquivos válidos
        const dt = new DataTransfer();
        validFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;
    }
    
    // Mostrar preview
    showMultiplePreview(validFiles);
    
    // Atualizar texto do botão
    updateUploadButtonText(validFiles.length);
}

// Mostrar preview de múltiplas imagens
function showMultiplePreview(files) {
    const previewContainer = document.getElementById('multiple-preview');
    previewContainer.innerHTML = '';
    previewContainer.style.display = files.length > 0 ? 'grid' : 'none';
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'relative bg-gray-100 rounded-lg overflow-hidden';
            previewItem.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}" class="w-full h-24 object-cover">
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button type="button" onclick="removeFileFromPreview(${index})" 
                            class="bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-700">
                        ×
                    </button>
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 truncate">
                    ${file.name}
                </div>
            `;
            previewContainer.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    });
}

// Remover arquivo do preview
function removeFileFromPreview(index) {
    const input = document.getElementById('multiple-file-input');
    const files = Array.from(input.files);
    
    files.splice(index, 1);
    
    const dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    showMultiplePreview(files);
    updateUploadButtonText(files.length);
}

// Atualizar texto do botão de upload
function updateUploadButtonText(fileCount) {
    const btnText = document.getElementById('upload-btn-text');
    if (fileCount === 0) {
        btnText.textContent = 'Enviar Thumbnails';
    } else if (fileCount === 1) {
        btnText.textContent = 'Enviar 1 Thumbnail';
    } else {
        btnText.textContent = `Enviar ${fileCount} Thumbnails`;
    }
}

// Manipular drop de múltiplos arquivos
function handleMultipleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    const input = document.getElementById('multiple-file-input');
    input.files = files;
    
    handleMultipleFiles(input);
}

// Manipular submit do upload múltiplo
function handleMultipleUploadSubmit(e) {
    const files = document.getElementById('multiple-file-input').files;
    
    if (files.length === 0) {
        e.preventDefault();
        showNotification('Selecione pelo menos uma imagem', 'error');
        return;
    }
    
    // Mostrar indicadores de progresso
    showUploadProgress();
    
    // Simular progresso (o progresso real seria via AJAX)
    simulateUploadProgress(files.length);
}

// Mostrar indicadores de progresso
function showUploadProgress() {
    const btn = document.querySelector('#multiple-upload-form button[type="submit"]');
    const spinner = document.getElementById('upload-spinner');
    const progress = document.getElementById('upload-progress');
    const btnText = document.getElementById('upload-btn-text');
    
    btn.disabled = true;
    spinner.style.display = 'block';
    progress.style.display = 'block';
    btnText.textContent = 'Processando...';
}

// Simular progresso de upload
function simulateUploadProgress(fileCount) {
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    
    let progress = 0;
    const increment = 100 / (fileCount * 2); // Simular análise + upload
    
    const interval = setInterval(() => {
        progress += increment;
        
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
            progressText.textContent = 'Upload concluído! Redirecionando...';
        } else if (progress < 50) {
            progressText.textContent = `Analisando imagens com IA... ${Math.round(progress)}%`;
        } else {
            progressText.textContent = `Enviando arquivos... ${Math.round(progress)}%`;
        }
        
        progressBar.style.width = progress + '%';
    }, 200);
}

// Função para testar conexão com Gemini
async function testGeminiConnection() {
    try {
        const response = await fetch('test_gemini.php');
        const result = await response.json();
        
        if (result.success) {
            showNotification('Conexão com IA Gemini: OK', 'success');
        } else {
            showNotification('Erro na conexão com IA: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Erro ao testar conexão com IA', 'error');
    }
}

// Função para preview em tempo real durante seleção
function setupLivePreview() {
    const inputs = document.querySelectorAll('input[type="file"]');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.multiple) {
                handleMultipleFiles(this);
            } else {
                handleFileSelect({ target: this });
            }
        });
    });
}
