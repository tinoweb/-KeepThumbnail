<?php

class ThumbnailManager {
    private $db;
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB

    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../uploads/';
        $this->createUploadDirectory();
    }

    private function createUploadDirectory() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function uploadThumbnail($file, $data) {
        try {
            // Validar arquivo
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Validar dados
            if (empty(trim($data['title']))) {
                return ['success' => false, 'message' => 'Título é obrigatório'];
            }

            // Gerar nome único para o arquivo
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('thumb_') . '.' . $fileExtension;
            $filePath = $this->uploadDir . $filename;

            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return ['success' => false, 'message' => 'Erro ao salvar arquivo'];
            }

            // Obter dimensões da imagem
            $imageInfo = getimagesize($filePath);
            $width = $imageInfo[0] ?? null;
            $height = $imageInfo[1] ?? null;

            // Gerar token de compartilhamento
            $shareToken = $this->generateShareToken();

            // Salvar no banco de dados
            $sql = "INSERT INTO thumbnails (title, description, tags, filename, file_path, file_size, mime_type, width, height, share_token) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $relativePath = 'uploads/' . $filename;
            
            $this->db->execute($sql, [
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['tags'] ?? ''),
                $filename,
                $relativePath,
                $file['size'],
                $file['type'],
                $width,
                $height,
                $shareToken
            ]);

            return [
                'success' => true, 
                'message' => 'Thumbnail enviado com sucesso',
                'id' => $this->db->lastInsertId()
            ];

        } catch (Exception $e) {
            // Remover arquivo se houve erro
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    /**
     * Upload múltiplo de thumbnails com geração automática de nomes
     */
    public function uploadMultipleThumbnails($files, $useGeminiNames = true) {
        require_once __DIR__ . '/GeminiImageAnalyzer.php';
        
        $results = [];
        $geminiAnalyzer = null;
        
        if ($useGeminiNames) {
            $geminiAnalyzer = new GeminiImageAnalyzer();
        }
        
        // Processar cada arquivo
        foreach ($files['tmp_name'] as $index => $tmpName) {
            if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                $results[] = [
                    'success' => false,
                    'filename' => $files['name'][$index],
                    'message' => 'Erro no upload do arquivo'
                ];
                continue;
            }
            
            // Criar array de arquivo individual
            $singleFile = [
                'name' => $files['name'][$index],
                'type' => $files['type'][$index],
                'tmp_name' => $tmpName,
                'error' => $files['error'][$index],
                'size' => $files['size'][$index]
            ];
            
            try {
                // Validar arquivo
                $validation = $this->validateFile($singleFile);
                if (!$validation['success']) {
                    $results[] = [
                        'success' => false,
                        'filename' => $singleFile['name'],
                        'message' => $validation['message']
                    ];
                    continue;
                }
                
                // Gerar nome sugestivo se habilitado
                $suggestedTitle = '';
                if ($useGeminiNames && $geminiAnalyzer) {
                    try {
                        $suggestedTitle = $geminiAnalyzer->generateSuggestiveName($tmpName);
                    } catch (Exception $e) {
                        writeLog("Erro Gemini para {$singleFile['name']}: " . $e->getMessage(), 'WARNING');
                        $suggestedTitle = $this->generateBasicName($singleFile['name']);
                    }
                } else {
                    $suggestedTitle = $this->generateBasicName($singleFile['name']);
                }
                
                // Gerar nome único para o arquivo
                $fileExtension = pathinfo($singleFile['name'], PATHINFO_EXTENSION);
                $filename = uniqid('thumb_') . '.' . $fileExtension;
                $filePath = $this->uploadDir . $filename;
                
                // Mover arquivo
                if (!move_uploaded_file($tmpName, $filePath)) {
                    $results[] = [
                        'success' => false,
                        'filename' => $singleFile['name'],
                        'message' => 'Erro ao salvar arquivo'
                    ];
                    continue;
                }
                
                // Obter dimensões da imagem
                $imageInfo = getimagesize($filePath);
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;
                
                // Gerar token de compartilhamento
                $shareToken = $this->generateShareToken();
                
                // Salvar no banco de dados
                $sql = "INSERT INTO thumbnails (title, description, tags, filename, file_path, file_size, mime_type, width, height, share_token) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $relativePath = 'uploads/' . $filename;
                
                $this->db->execute($sql, [
                    $suggestedTitle,
                    '', // Descrição vazia para upload múltiplo
                    '', // Tags vazias para upload múltiplo
                    $filename,
                    $relativePath,
                    $singleFile['size'],
                    $singleFile['type'],
                    $width,
                    $height,
                    $shareToken
                ]);
                
                $results[] = [
                    'success' => true,
                    'filename' => $singleFile['name'],
                    'suggested_title' => $suggestedTitle,
                    'id' => $this->db->lastInsertId(),
                    'message' => 'Upload realizado com sucesso'
                ];
                
            } catch (Exception $e) {
                // Remover arquivo se houve erro
                if (isset($filePath) && file_exists($filePath)) {
                    unlink($filePath);
                }
                
                $results[] = [
                    'success' => false,
                    'filename' => $singleFile['name'],
                    'message' => 'Erro interno: ' . $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => true,
            'total_files' => count($files['tmp_name']),
            'successful_uploads' => count(array_filter($results, function($r) { return $r['success']; })),
            'failed_uploads' => count(array_filter($results, function($r) { return !$r['success']; })),
            'results' => $results
        ];
    }
    
    /**
     * Gera um nome básico quando Gemini não está disponível
     */
    private function generateBasicName($originalFilename) {
        $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $baseName);
        $baseName = preg_replace('/-+/', '-', $baseName);
        $baseName = trim($baseName, '-');
        
        if (empty($baseName) || strlen($baseName) < 3) {
            $baseName = 'imagem-' . date('Ymd-His') . '-' . uniqid();
        }
        
        return substr($baseName, 0, 50);
    }

    private function validateFile($file) {
        // Verificar se houve erro no upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload do arquivo'];
        }

        // Verificar tamanho
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => 'Arquivo muito grande (máximo 10MB)'];
        }

        // Verificar tipo MIME
        if (!in_array($file['type'], $this->allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
        }

        // Verificar se é realmente uma imagem
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => 'Arquivo não é uma imagem válida'];
        }

        return ['success' => true];
    }

    private function generateShareToken() {
        do {
            $token = bin2hex(random_bytes(16));
            $exists = $this->db->fetchOne("SELECT id FROM thumbnails WHERE share_token = ?", [$token]);
        } while ($exists);
        
        return $token;
    }

    public function getThumbnails($search = '', $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM thumbnails";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE title LIKE ? OR description LIKE ? OR tags LIKE ?";
            $searchTerm = '%' . $search . '%';
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function getTotalCount($search = '') {
        $sql = "SELECT COUNT(*) FROM thumbnails";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE title LIKE ? OR description LIKE ? OR tags LIKE ?";
            $searchTerm = '%' . $search . '%';
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        return $this->db->count($sql, $params);
    }

    public function getThumbnailById($id) {
        return $this->db->fetchOne("SELECT * FROM thumbnails WHERE id = ?", [$id]);
    }

    public function getThumbnailByToken($token) {
        return $this->db->fetchOne("SELECT * FROM thumbnails WHERE share_token = ?", [$token]);
    }

    public function deleteThumbnail($id) {
        try {
            $thumbnail = $this->getThumbnailById($id);
            if (!$thumbnail) {
                return ['success' => false, 'message' => 'Thumbnail não encontrado'];
            }

            // Remover arquivo físico
            $filePath = __DIR__ . '/../' . $thumbnail['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Remover do banco
            $this->db->execute("DELETE FROM thumbnails WHERE id = ?", [$id]);

            return ['success' => true, 'message' => 'Thumbnail excluído com sucesso'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()];
        }
    }

    public function incrementDownloadCount($id) {
        $this->db->execute("UPDATE thumbnails SET download_count = download_count + 1 WHERE id = ?", [$id]);
    }

    public function updateThumbnail($id, $data) {
        try {
            $sql = "UPDATE thumbnails SET title = ?, description = ?, tags = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            $this->db->execute($sql, [
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['tags'] ?? ''),
                $id
            ]);

            return ['success' => true, 'message' => 'Thumbnail atualizado com sucesso'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()];
        }
    }

    public function getStats() {
        $totalThumbnails = $this->db->count("SELECT COUNT(*) FROM thumbnails");
        $totalDownloads = $this->db->count("SELECT SUM(download_count) FROM thumbnails");
        $totalSize = $this->db->count("SELECT SUM(file_size) FROM thumbnails");

        return [
            'total_thumbnails' => $totalThumbnails,
            'total_downloads' => $totalDownloads ?: 0,
            'total_size' => $totalSize ?: 0,
            'total_size_formatted' => $this->formatFileSize($totalSize ?: 0)
        ];
    }

    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function searchByTags($tags) {
        if (empty($tags)) {
            return [];
        }

        $tagArray = array_map('trim', explode(',', $tags));
        $conditions = [];
        $params = [];

        foreach ($tagArray as $tag) {
            if (!empty($tag)) {
                $conditions[] = "tags LIKE ?";
                $params[] = '%' . $tag . '%';
            }
        }

        if (empty($conditions)) {
            return [];
        }

        $sql = "SELECT * FROM thumbnails WHERE " . implode(' OR ', $conditions) . " ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getRecentThumbnails($limit = 10) {
        return $this->db->fetchAll("SELECT * FROM thumbnails ORDER BY created_at DESC LIMIT ?", [$limit]);
    }

    public function getPopularThumbnails($limit = 10) {
        return $this->db->fetchAll("SELECT * FROM thumbnails ORDER BY download_count DESC LIMIT ?", [$limit]);
    }
}
