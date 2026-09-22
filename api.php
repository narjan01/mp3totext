<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/GeminiService.php';

header('Content-Type: application/json; charset=utf-8');

// Configura status da chave
if (isset($_GET['check_config'])) {
    echo json_encode([
        'has_backend_key' => !empty(DEFAULT_GEMINI_API_KEY)
    ]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metodo de requisicao invalido.");
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        $data = $_POST;
    }

    $fileId = $data['file_id'] ?? '';
    $apiKey = trim($data['api_key'] ?? '') ?: DEFAULT_GEMINI_API_KEY;

    if (empty($apiKey)) {
        throw new Exception("Chave da API Gemini ausente. Configure GEMINI_API_KEY nas variaveis de ambiente do Render ou insira no campo da tela.");
    }

    if (empty($fileId)) {
        throw new Exception("Identificador do arquivo de audio ausente.");
    }

    // Sanitiza nome do arquivo
    $fileId = basename($fileId);
    $filePath = UPLOAD_DIR . $fileId;

    if (!file_exists($filePath)) {
        throw new Exception("Arquivo de audio nao encontrado ou expirado no servidor.");
    }

    $gemini = new GeminiService($apiKey);
    $result = $gemini->transcribeAndSummarize($filePath);

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}