<?php
require_once __DIR__ . '/AudioHelper.php';

class GeminiService
{
    private string ;
    private string ;

    public function __construct(string , string  = 'gemini-2.5-flash')
    {
        ->apiKey = trim();
        ->model = ;
    }

    /**
     * Realiza upload de arquivo de audio usando a Files API do Gemini
     */
    private function uploadFile(string , string ): array
    {
         = filesize();
         = basename();

        // Etapa 1: Iniciar upload resumivel
         = "https://generativelanguage.googleapis.com/upload/v1beta/files?key=" . urlencode(->apiKey);
        
         = json_encode([
            'file' => [
                'display_name' => 
            ]
        ]);

         = curl_init();
        curl_setopt_array(, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => ,
            CURLOPT_HTTPHEADER => [
                'X-Goog-Upload-Protocol: resumable',
                'X-Goog-Upload-Command: start',
                'X-Goog-Upload-Header-Content-Length: ' . ,
                'X-Goog-Upload-Header-Content-Type: ' . ,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

         = curl_exec();
         = curl_getinfo(, CURLINFO_HTTP_CODE);
         = curl_getinfo(, CURLINFO_HEADER_SIZE);
         = substr(, 0, );
        curl_close();

        if ( !== 200) {
            throw new Exception("Falha ao iniciar upload para Gemini (HTTP ): " . substr(, ));
        }

        // Extrai o upload-url retornado no header
         = null;
        foreach (explode("\r\n", ) as ) {
            if (stripos(, 'x-goog-upload-url:') === 0) {
                 = trim(substr(, strlen('x-goog-upload-url:')));
                break;
            }
        }

        if (!) {
            throw new Exception("Nao foi possivel obter a URL de upload da resposta da Gemini API.");
        }

        // Etapa 2: Enviar os bytes do arquivo
         = fopen(, 'rb');
        if (!) {
            throw new Exception("Nao foi possivel abrir o arquivo local para upload.");
        }

         = curl_init();
        curl_setopt_array(, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_INFILE => ,
            CURLOPT_INFILESIZE => ,
            CURLOPT_UPLOAD => true,
            CURLOPT_HTTPHEADER => [
                'Content-Length: ' . ,
                'X-Goog-Upload-Offset: 0',
                'X-Goog-Upload-Command: upload, finalize'
            ],
            CURLOPT_TIMEOUT => 180,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

         = curl_exec();
         = curl_getinfo(, CURLINFO_HTTP_CODE);
        curl_close();
        fclose();

        if ( !== 200) {
            throw new Exception("Falha ao enviar arquivo para Gemini (HTTP ): " . );
        }

         = json_decode(, true);
        if (!isset(['file']['uri'])) {
            throw new Exception("Resposta inesperada no upload: " . );
        }

        return ['file'];
    }

    /**
     * Processa o audio para transcricao completa e resumo estruturado
     */
    public function transcribeAndSummarize(string ): array
    {
        if (empty(->apiKey)) {
            throw new Exception("Chave da API do Gemini nao configurada. Por favor, forneca sua API Key.");
        }

        if (!file_exists()) {
            throw new Exception("Arquivo de audio nao encontrado no servidor.");
        }

         = AudioHelper::getMimeType();
         = filesize();

         = <<<EOT
Voce e um assistente especialista de altissima precisao em transcricao de audio e sintese textual.
O arquivo fornecido contem uma gravacao em audio ou video.

Sua missao e realizar DUAS tarefas obrigatorias de forma impecavel:

1. TRANSCRICAO COMPLETA:
   - Transcreva CADA palavra dita com a maxima fidelidade e exatidao.
   - Aplique pontuacao correta, letras maiusculas e quebre em paragrafos de leitura confortavel.
   - Nao omita nem invente falas. Se houver partes inaudiveis, sinalize com [inaudivel].
   - Se houver interlocutores distintos perceptiveis, identifique-os como 'Interlocutor 1', 'Interlocutor 2', etc.

2. RESUMO ESTRUTURADO:
   - Resumo Executivo: um panorama geral conciso em 1 a 2 paragrafos.
   - Pontos Principais: lista organizada com marcadores dos principais assuntos e temas discutidos.
   - Conclusoes e Proximos Passos: se houver decisoes, orientacoes ou acoes acordadas, destaque-as de forma clara.

FORMATO OBRIGATORIO DE RETORNO:
Retorne EXCLUSIVAMENTE um objeto JSON valido sem nenhum markdown adicional em volta, no seguinte formato:
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

        // Se o arquivo for menor que 15MB, podemos enviar como inline_data direto (mais rapido)
        // Caso contrario, usa a Files API
        if ( <= 15 * 1024 * 1024) {
             = base64_encode(file_get_contents());
             = [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => ,
                                'data' => 
                            ]
                        ],
                        [
                            'text' => 
                        ]
                    ]
                ]
            ];
        } else {
            // Usa Files API do Gemini
             = ->uploadFile(, );
             = ['uri'];

             = [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'file_data' => [
                                'mime_type' => ,
                                'file_uri' => 
                            ]
                        ],
                        [
                            'text' => 
                        ]
                    ]
                ]
            ];
        }

         = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode(->model) . ":generateContent?key=" . urlencode(->apiKey);

         = [
            'contents' => ,
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json'
            ]
        ];

         = curl_init();
        curl_setopt_array(, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode(),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

         = curl_exec();
         = curl_getinfo(, CURLINFO_HTTP_CODE);
         = curl_error();
        curl_close();

        if () {
            throw new Exception("Erro de conexao cURL: " . );
        }

        if ( !== 200) {
             = json_decode(, true);
             = ['error']['message'] ?? ("HTTP " .  . ": " . );
            throw new Exception("Erro na API Gemini: " . );
        }

         = json_decode(, true);
         = ['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty()) {
            throw new Exception("A IA nao retornou conteudo para este audio.");
        }

        // Tenta decodificar o JSON retornado
         = trim();
        // Remove delimitadores markdown `json caso a IA coloque
        if (str_starts_with(, '`json')) {
             = substr(, 7);
        }
        if (str_ends_with(, '`')) {
             = substr(, 0, -3);
        }
         = trim();

         = json_decode(, true);
        if (! || !isset(['transcription'])) {
            // Fallback: se nao for JSON perfeito, devolve texto cru
            return [
                'transcription' => ,
                'summary' => [
                    'executive' => 'Resumo gerado integrado ao texto.',
                    'key_points' => [],
                    'conclusions' => ''
                ]
            ];
        }

        return ;
    }
}
