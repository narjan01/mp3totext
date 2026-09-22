<?php
require_once __DIR__ . '/AudioHelper.php';

class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash')
    {
        $this->apiKey = trim($apiKey);
        $this->model = $model;
    }

    /**
     * Realiza upload de arquivo de audio usando a Files API do Gemini
     */
    private function uploadFile(string $filePath, string $mimeType): array
    {
        $fileSize = filesize($filePath);
        $displayName = basename($filePath);

        $initUrl = "https://generativelanguage.googleapis.com/upload/v1beta/files?key=" . urlencode($this->apiKey);
        
        $metadata = json_encode([
            'file' => [
                'display_name' => $displayName
            ]
        ]);

        $ch = curl_init($initUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => $metadata,
            CURLOPT_HTTPHEADER => [
                'X-Goog-Upload-Protocol: resumable',
                'X-Goog-Upload-Command: start',
                'X-Goog-Upload-Header-Content-Length: ' . $fileSize,
                'X-Goog-Upload-Header-Content-Type: ' . $mimeType,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersText = substr($response, 0, $headerSize);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Falha ao iniciar upload para Gemini (HTTP $httpCode): " . substr($response, $headerSize));
        }

        $uploadUrl = null;
        foreach (explode("\r\n", $headersText) as $line) {
            if (stripos($line, 'x-goog-upload-url:') === 0) {
                $uploadUrl = trim(substr($line, strlen('x-goog-upload-url:')));
                break;
            }
        }

        if (!$uploadUrl) {
            throw new Exception("Nao foi possivel obter a URL de upload da resposta da Gemini API.");
        }

        $fileHandle = fopen($filePath, 'rb');
        if (!$fileHandle) {
            throw new Exception("Nao foi possivel abrir o arquivo local para upload.");
        }

        $ch = curl_init($uploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_INFILE => $fileHandle,
            CURLOPT_INFILESIZE => $fileSize,
            CURLOPT_UPLOAD => true,
            CURLOPT_HTTPHEADER => [
                'Content-Length: ' . $fileSize,
                'X-Goog-Upload-Offset: 0',
                'X-Goog-Upload-Command: upload, finalize'
            ],
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $uploadResponse = curl_exec($ch);
        $uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fileHandle);

        if ($uploadHttpCode !== 200) {
            throw new Exception("Falha ao enviar arquivo para Gemini (HTTP $uploadHttpCode): " . $uploadResponse);
        }

        $data = json_decode($uploadResponse, true);
        if (!isset($data['file']['uri'])) {
            throw new Exception("Resposta inesperada no upload: " . $uploadResponse);
        }

        return $data['file'];
    }

    /**
     * Processa um arquivo unico ou partes fragmentadas
     */
    public function transcribeAndSummarize(string $filePath): array
    {
        if (empty($this->apiKey)) {
            throw new Exception("Chave da API do Gemini nao configurada. Por favor, forneca sua API Key.");
        }

        if (!file_exists($filePath)) {
            throw new Exception("Arquivo de audio nao encontrado no servidor.");
        }

        // Se o arquivo for muito grande (ex: > 80MB), tenta fragmentar em 2 partes
        $parts = AudioHelper::splitAudioIfLarge($filePath, 80 * 1024 * 1024);

        if (count($parts) > 1) {
            $transcriptions = [];
            foreach ($parts as $idx => $partFile) {
                $subResult = $this->processSingleAudio($partFile, "Parte " . ($idx + 1) . " de " . count($parts));
                $transcriptions[] = $subResult['transcription'];
                // Remove arquivo temporario de fragmento
                if ($partFile !== $filePath) {
                    @unlink($partFile);
                }
            }
            $fullTranscription = implode("\n\n--- [CONTINUACAO DO AUDIO] ---\n\n", $transcriptions);

            // Gera resumo global consolidado a partir do texto completo
            $summary = $this->generateSummaryFromText($fullTranscription);

            return [
                'transcription' => $fullTranscription,
                'summary' => $summary
            ];
        }

        return $this->processSingleAudio($filePath);
    }

    /**
     * Processa uma parte individual do audio
     */
    private function processSingleAudio(string $filePath, string $contextHint = ''): array
    {
        $mimeType = AudioHelper::getMimeType($filePath);
        $fileSize = filesize($filePath);

        $prompt = <<<EOT
Voce e um assistente especialista de altissima precisao em transcricao de audio e sintese textual.
$contextHint

Sua missao e realizar DUAS tarefas de forma impecavel:

1. TRANSCRICAO COMPLETA:
   - Transcreva CADA palavra dita com a maxima fidelidade e exatidao.
   - Aplique pontuacao correta, letras maiusculas e quebre em paragrafos de leitura confortavel.
   - Nao omita nem invente falas. Se houver partes inaudiveis, sinalize com [inaudivel].
   - Se houver interlocutores distintos perceptiveis, identifique-os como 'Interlocutor 1', 'Interlocutor 2', etc.

2. RESUMO ESTRUTURADO:
   - Resumo Executivo: um panorama geral conciso em 1 a 2 paragrafos.
   - Pontos Principais: lista organizada com marcadores dos principais assuntos discutidos.
   - Conclusoes e Proximos Passos: se houver decisoes ou orientacoes, destaque-as.

FORMATO OBRIGATORIO:
Retorne EXCLUSIVAMENTE um objeto JSON valido no seguinte formato:
{
  "summary": {
    "executive": "Texto do resumo executivo...",
    "key_points": [
      "Ponto 1...",
      "Ponto 2..."
    ],
    "conclusions": "Conclusoes ou acoes..."
  },
  "transcription": "Texto integral da transcricao..."
}
EOT;

        if ($fileSize <= 15 * 1024 * 1024) {
            $base64Data = base64_encode(file_get_contents($filePath));
            $contents = [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Data
                            ]
                        ],
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ];
        } else {
            $uploaded = $this->uploadFile($filePath, $mimeType);
            $contents = [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'file_data' => [
                                'mime_type' => $mimeType,
                                'file_uri' => $uploaded['uri']
                            ]
                        ],
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($this->model) . ":generateContent?key=" . urlencode($this->apiKey);

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Erro de conexao cURL: " . $curlError);
        }

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = $errData['error']['message'] ?? ("HTTP " . $httpCode . ": " . $response);
            throw new Exception("Erro na API Gemini: " . $errMsg);
        }

        $result = json_decode($response, true);
        $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $cleanJson = trim($rawText);
        if (str_starts_with($cleanJson, '```json')) {
            $cleanJson = substr($cleanJson, 7);
        }
        if (str_ends_with($cleanJson, '```')) {
            $cleanJson = substr($cleanJson, 0, -3);
        }
        $cleanJson = trim($cleanJson);

        $parsed = json_decode($cleanJson, true);
        if (!$parsed || !isset($parsed['transcription'])) {
            return [
                'transcription' => $rawText,
                'summary' => [
                    'executive' => 'Resumo integrado.',
                    'key_points' => [],
                    'conclusions' => ''
                ]
            ];
        }

        return $parsed;
    }

    /**
     * Gera resumo a partir de texto consolidado
     */
    private function generateSummaryFromText(string $text): array
    {
        $prompt = <<<EOT
Com base na seguinte transcricao integral consolidada de audio, elabore um resumo estruturado de alta qualidade:

TEXTO:
$text

Retorne EXCLUSIVAMENTE um objeto JSON valido no seguinte formato:
{
  "executive": "Resumo executivo completo...",
  "key_points": [
    "Ponto 1...",
    "Ponto 2..."
  ],
  "conclusions": "Conclusoes e acoes..."
}
EOT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($this->model) . ":generateContent?key=" . urlencode($this->apiKey);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $raw = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $clean = trim($raw);
        if (str_starts_with($clean, '```json')) $clean = substr($clean, 7);
        if (str_ends_with($clean, '```')) $clean = substr($clean, 0, -3);
        $parsed = json_decode(trim($clean), true);

        return $parsed ?: [
            'executive' => 'Resumo consolidado.',
            'key_points' => [],
            'conclusions' => ''
        ];
    }
}