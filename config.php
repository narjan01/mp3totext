<?php
/**
 * Configuracoes da Aplicacao de Transcricao e Resumo com IA
 */

// Diretorio de uploads temporarios
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Tamanho maximo permitido de upload (ex: 50MB)
define('MAX_FILE_SIZE', 50 * 1024 * 1024);

// Extensoes de audio e video permitidas
define('ALLOWED_EXTENSIONS', ['mp3', 'wav', 'm4a', 'ogg', 'aac', 'flac', 'mp4', 'webm']);

// Chave da API do Gemini (pode ser definida aqui ou informada na interface)
define('DEFAULT_GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');

// Cria o diretorio de uploads caso nao exista
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
