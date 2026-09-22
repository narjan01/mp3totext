<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/GeminiService.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metodo de requisicao invalido.");
    }

     = file_get_contents('php://input');
     = json_decode(, true);

    if (!) {
         = ;
    }

     = ['file_id'] ?? '';
     = trim(['api_key'] ?? '') ?: DEFAULT_GEMINI_API_KEY;

    if (empty()) {
        throw new Exception("Por favor, forneca sua Gemini API Key para continuar.");
    }

    if (empty()) {
        throw new Exception("Identificador do arquivo de audio ausente.");
    }

    // Sanitiza nome do arquivo
     = basename();
     = UPLOAD_DIR . ;

    if (!file_exists()) {
        throw new Exception("Arquivo de audio nao encontrado ou expirado.");
    }

     = new GeminiService();
     = ->transcribeAndSummarize();

    echo json_encode([
        'success' => true,
        'data' => 
    ]);

} catch (Exception ) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => ->getMessage()
    ]);
}
