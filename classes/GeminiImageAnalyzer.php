<?php

require_once __DIR__ . '/../config/gemini.php';

class GeminiImageAnalyzer {
    private $apiKey;
    private $apiUrl;
    private $config;
    
    public function __construct($apiKey = null) {
        $this->config = getGeminiConfig();
        $this->apiKey = $apiKey ?: $this->config['api_key'];
        // Construir o endpoint com base no modelo/versão atuais (v1)
        $this->apiUrl = $this->buildApiUrlForModel($this->config['model']);
        
        if (!$this->config['configured']) {
            throw new Exception('Gemini API não está configurada. Verifique a chave da API.');
        }
    }

    /**
     * Monta a URL do endpoint generateContent para um determinado modelo
     */
    private function buildApiUrlForModel($modelName) {
        $version = $this->config['version']; // ex: v1
        return "https://generativelanguage.googleapis.com/{$version}/models/{$modelName}:generateContent";
    }
    
    /**
     * Analisa uma imagem e gera um nome sugestivo
     */
    public function generateSuggestiveName($imagePath, $language = 'pt-BR') {
        try {
            // Verificar se o arquivo existe
            if (!file_exists($imagePath)) {
                throw new Exception('Arquivo de imagem não encontrado');
            }
            
            // Converter imagem para base64
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);
            
            // Preparar prompt para análise
            $prompt = $this->createAnalysisPrompt($language);
            
            // Preparar dados para a API
            $requestData = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt
                            ],
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $this->config['temperature'],
                    'topK' => $this->config['top_k'],
                    'topP' => $this->config['top_p'],
                    'maxOutputTokens' => $this->config['max_tokens']
                ]
            ];
            
            // Fazer requisição com fallback de modelos
            $result = $this->attemptWithFallback($requestData);
            $response = $result['response'];
            
            if ($response && isset($response['candidates'][0])) {
                $candidate = $response['candidates'][0];
                
                // Log da resposta completa para debug
                geminiWriteLog([
                    'event' => 'generateSuggestiveName.response_received',
                    'model_used' => $result['model'],
                    'finish_reason' => $candidate['finishReason'] ?? 'unknown',
                    'has_content' => isset($candidate['content']),
                    'has_parts' => isset($candidate['content']['parts']),
                    'has_text_in_parts' => isset($candidate['content']['parts'][0]['text']),
                    'has_direct_text' => isset($candidate['content']['text'])
                ]);
                
                // Tentar extrair texto de diferentes estruturas
                $text = null;
                if (isset($candidate['content']['parts'][0]['text'])) {
                    $text = $candidate['content']['parts'][0]['text'];
                } elseif (isset($candidate['content']['text'])) {
                    $text = $candidate['content']['text'];
                }
                
                if ($text) {
                    $suggestedName = trim($text);
                    $finalName = $this->sanitizeName($suggestedName);
                    geminiWriteLog([
                        'event' => 'generateSuggestiveName.success',
                        'model_used' => $result['model'],
                        'raw_response' => $suggestedName,
                        'output' => $finalName
                    ]);
                    return $finalName;
                } else {
                    geminiWriteLog([
                        'event' => 'generateSuggestiveName.no_text_found',
                        'model_used' => $result['model'],
                        'candidate' => $candidate
                    ]);
                }
            } else {
                geminiWriteLog([
                    'event' => 'generateSuggestiveName.no_candidates',
                    'response' => $response
                ]);
            }
            
            // Fallback se a API falhar
            geminiWriteLog([
                'event' => 'generateSuggestiveName.using_fallback',
                'reason' => 'API did not return usable text'
            ]);
            return $this->generateFallbackName($imagePath);
            
        } catch (Exception $e) {
            writeLog("Erro na análise Gemini: " . $e->getMessage(), 'ERROR');
            geminiWriteLog([
                'event' => 'generateSuggestiveName.error',
                'error' => $e->getMessage()
            ]);
            return $this->generateFallbackName($imagePath);
        }
    }
    
    /**
     * Cria o prompt para análise da imagem
     */
    private function createAnalysisPrompt($language = 'pt-BR') {
        if ($language === 'pt-BR') {
            return "Analise esta imagem e sugira um nome descritivo e conciso para ela. 
                   O nome deve ser:
                   - Máximo 50 caracteres
                   - Descritivo do conteúdo principal
                   - Sem caracteres especiais (apenas letras, números e hífens)
                   - Em português brasileiro
                   - Adequado para ser usado como nome de arquivo
                   
                   Responda apenas com o nome sugerido, sem explicações adicionais.
                   
                   Exemplos de bons nomes:
                   - gato-dormindo-sofa
                   - paisagem-montanha-por-do-sol
                   - pessoa-trabalhando-computador
                   - comida-pizza-margherita
                   - carro-esportivo-vermelho";
        } else {
            return "Analyze this image and suggest a descriptive and concise name for it.
                   The name should be:
                   - Maximum 50 characters
                   - Descriptive of the main content
                   - No special characters (only letters, numbers and hyphens)
                   - In English
                   - Suitable for use as a filename
                   
                   Respond only with the suggested name, no additional explanations.";
        }
    }
    
    /**
     * Faz a requisição para a API do Gemini
     */
    private function makeApiRequest($data) {
        // Chama com o modelo atual (sem fallback)
        return $this->makeApiRequestToModel($data, $this->config['model']);
    }

    private function sanitizePayloadForLog($data) {
        $copy = $data;
        if (isset($copy['contents']) && is_array($copy['contents'])) {
            foreach ($copy['contents'] as $ci => $content) {
                if (isset($content['parts']) && is_array($content['parts'])) {
                    foreach ($content['parts'] as $pi => $part) {
                        if (isset($part['inlineData']['data'])) {
                            $len = strlen($part['inlineData']['data']);
                            $mime = $part['inlineData']['mimeType'] ?? 'unknown';
                            $copy['contents'][$ci]['parts'][$pi]['inlineData'] = [
                                'mimeType' => $mime,
                                'data' => '[omitted base64]',
                                'bytes' => $len
                            ];
                        }
                    }
                }
            }
        }
        return $copy;
    }

    private function makeApiRequestToModel($data, $modelName) {
        $url = $this->buildApiUrlForModel($modelName) . '?key=' . $this->apiKey;

        $payloadForLog = $this->sanitizePayloadForLog($data);
        geminiWriteLog([
            'event' => 'request',
            'model' => $modelName,
            'url' => $url,
            'payload' => $payloadForLog
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: KeepThumbnail/1.0'
            ],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            writeLog("Erro cURL na API Gemini: " . $error, 'ERROR');
            geminiWriteLog([
                'event' => 'response',
                'model' => $modelName,
                'http_code' => null,
                'error' => $error
            ]);
            throw new Exception("Erro de conexão: " . $error);
        }

        $responseData = json_decode($response, true);

        geminiWriteLog([
            'event' => 'response',
            'model' => $modelName,
            'http_code' => $httpCode,
            'raw' => $httpCode === 200 ? '[omitted]' : $response,
            'has_candidates' => isset($responseData['candidates'])
        ]);

        if ($httpCode !== 200) {
            $errorMessage = "Erro HTTP: " . $httpCode;
            if ($responseData && isset($responseData['error'])) {
                $errorMessage .= " - " . ($responseData['error']['message'] ?? $responseData['error']);
            } else {
                $errorMessage .= " - " . $response;
            }
            writeLog("Erro API Gemini: " . $errorMessage, 'ERROR');
            throw new Exception($errorMessage);
        }

        if (!$responseData) {
            throw new Exception("Resposta inválida da API");
        }

        return $responseData;
    }

    private function attemptWithFallback($data) {
        $candidates = array_values(array_unique([
            $this->config['model'],
            'gemini-2.5-flash-lite',
            'gemini-2.5-pro'
        ]));

        $lastError = null;
        foreach ($candidates as $model) {
            try {
                $resp = $this->makeApiRequestToModel($data, $model);
                return ['response' => $resp, 'model' => $model];
            } catch (Exception $e) {
                $lastError = $e;
                $msg = $e->getMessage();
                geminiWriteLog([
                    'event' => 'fallback.try_failed',
                    'model' => $model,
                    'error' => $msg
                ]);
                // Se erro for 404/not found/not supported, tenta próximo modelo
                if (strpos($msg, '404') !== false || stripos($msg, 'not found') !== false || stripos($msg, 'not supported') !== false) {
                    continue;
                }
                // Outros erros: propaga
                throw $e;
            }
        }
        if ($lastError) {
            throw $lastError;
        }
        throw new Exception('Nenhum modelo disponível para fallback.');
    }

    /**
     * Requisição GET simples (utilitário)
     */
    private function makeGetRequest($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: KeepThumbnail/1.0'
            ],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Erro GET: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('Erro HTTP GET: ' . $httpCode . ' - ' . $response);
        }

        return json_decode($response, true);
    }

    /**
     * Lista modelos disponíveis na API (diagnóstico)
     */
    public function listModels() {
        $version = $this->config['version'];
        $url = "https://generativelanguage.googleapis.com/{$version}/models?key=" . $this->apiKey;
        return $this->makeGetRequest($url);
    }
    
    /**
     * Sanitiza o nome sugerido
     */
    private function sanitizeName($name) {
        // Remover quebras de linha e espaços extras
        $name = trim(preg_replace('/\s+/', ' ', $name));
        
        // Converter para minúsculas
        $name = strtolower($name);
        
        // Remover acentos
        $name = $this->removeAccents($name);
        
        // Substituir espaços por hífens
        $name = str_replace(' ', '-', $name);
        
        // Remover caracteres especiais, manter apenas letras, números e hífens
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);
        
        // Remover hífens múltiplos
        $name = preg_replace('/-+/', '-', $name);
        
        // Remover hífens do início e fim
        $name = trim($name, '-');
        
        // Limitar tamanho
        $name = substr($name, 0, 50);
        
        // Se ficou vazio, usar fallback
        if (empty($name)) {
            $name = 'imagem-' . uniqid();
        }
        
        return $name;
    }
    
    /**
     * Remove acentos de uma string
     */
    private function removeAccents($string) {
        $accents = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N'
        ];
        
        return strtr($string, $accents);
    }
    
    /**
     * Gera um nome fallback quando a API falha
     */
    private function generateFallbackName($imagePath) {
        // Tentar extrair informações básicas da imagem
        $imageInfo = getimagesize($imagePath);
        $width = $imageInfo[0] ?? 0;
        $height = $imageInfo[1] ?? 0;
        
        // Determinar orientação
        $orientation = '';
        if ($width > $height) {
            $orientation = 'landscape';
        } elseif ($height > $width) {
            $orientation = 'portrait';
        } else {
            $orientation = 'square';
        }
        
        // Determinar tamanho aproximado
        $size = '';
        $pixels = $width * $height;
        if ($pixels > 8000000) { // > 8MP
            $size = 'hd';
        } elseif ($pixels > 2000000) { // > 2MP
            $size = 'medium';
        } else {
            $size = 'small';
        }
        
        // Gerar nome baseado nas características
        $timestamp = date('Ymd-His');
        return "imagem-{$orientation}-{$size}-{$timestamp}";
    }
    
    /**
     * Analisa múltiplas imagens em lote
     */
    public function analyzeMultipleImages($imagePaths, $language = 'pt-BR') {
        $results = [];
        
        foreach ($imagePaths as $index => $imagePath) {
            try {
                $suggestedName = $this->generateSuggestiveName($imagePath, $language);
                
                // Garantir que o nome seja único
                $uniqueName = $this->ensureUniqueName($suggestedName, $results);
                
                $results[$index] = [
                    'original_path' => $imagePath,
                    'suggested_name' => $uniqueName,
                    'success' => true
                ];
                
                // Pequena pausa para não sobrecarregar a API
                usleep(500000); // 0.5 segundos
                
            } catch (Exception $e) {
                $results[$index] = [
                    'original_path' => $imagePath,
                    'suggested_name' => $this->generateFallbackName($imagePath),
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Garante que o nome seja único
     */
    private function ensureUniqueName($baseName, $existingResults) {
        $existingNames = array_column($existingResults, 'suggested_name');
        
        if (!in_array($baseName, $existingNames)) {
            return $baseName;
        }
        
        $counter = 1;
        do {
            $newName = $baseName . '-' . $counter;
            $counter++;
        } while (in_array($newName, $existingNames));
        
        return $newName;
    }
    
    /**
     * Testa a conexão com a API
     */
    public function testConnection() {
        try {
            // Fazer um teste simples com texto apenas
            $testData = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => 'Olá! Este é um teste de conexão. Responda apenas com "OK".'
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 50
                ]
            ];
            
            $result = $this->attemptWithFallback($testData);
            $response = $result['response'];
            
            if ($response && isset($response['candidates'][0])) {
                $candidate = $response['candidates'][0];
                
                // Tentar extrair texto de diferentes estruturas
                $text = null;
                if (isset($candidate['content']['parts'][0]['text'])) {
                    $text = $candidate['content']['parts'][0]['text'];
                } elseif (isset($candidate['content']['text'])) {
                    $text = $candidate['content']['text'];
                }
                
                if ($text) {
                    return [
                        'success' => true,
                        'message' => 'Conexão com Gemini API estabelecida com sucesso!',
                        'api_response' => trim($text),
                        'model' => $result['model'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                }
            }
            
            // Se chegou aqui, não conseguiu extrair texto
            geminiWriteLog([
                'event' => 'testConnection.no_text_extracted',
                'response' => $response,
                'has_candidates' => isset($response['candidates']),
                'candidates_count' => isset($response['candidates']) ? count($response['candidates']) : 0
            ]);

            return [
                'success' => false,
                'message' => 'Resposta inesperada da API (verifique os logs)',
                'response' => $response
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na conexão com Gemini API: ' . $e->getMessage(),
                'error_type' => get_class($e),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Verifica se a API key é válida
     */
    public function validateApiKey() {
        try {
            $testResult = $this->testConnection();
            return $testResult['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtém informações sobre o modelo
     */
    public function getModelInfo() {
        return [
            'model_name' => $this->config['model'],
            'api_version' => $this->config['version'],
            'endpoint' => $this->apiUrl,
            'supports_images' => true,
            'max_tokens' => $this->config['max_tokens'],
            'languages' => $this->config['supported_languages'],
            'supported_mime_types' => $this->config['supported_mime_types'],
            'max_file_size' => $this->config['max_file_size'],
            'max_file_size_formatted' => (function_exists('formatBytes') ? formatBytes($this->config['max_file_size']) : (number_format($this->config['max_file_size'] / (1024*1024), 2) . ' MB')),
            'rate_limits' => [
                'requests_per_minute' => $this->config['requests_per_minute'],
                'requests_per_day' => $this->config['requests_per_day']
            ],
            'configuration' => [
                'temperature' => $this->config['temperature'],
                'top_k' => $this->config['top_k'],
                'top_p' => $this->config['top_p'],
                'timeout' => $this->config['timeout'],
                'connect_timeout' => $this->config['connect_timeout']
            ]
        ];
    }
}
