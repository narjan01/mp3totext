<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/AudioHelper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metodo de requisicao invalido.");
    }

    if (!isset(['audio_file']) || ['audio_file']['error'] !== UPLOAD_ERR_OK) {
         = ['audio_file']['error'] ?? 'desconhecido';
        throw new Exception("Erro no upload do arquivo (Codigo: ).");
    }

     = ['audio_file'];
     = ['size'];
     = basename(['name']);
     = strtolower(pathinfo(, PATHINFO_EXTENSION));

    if (!in_array(, ALLOWED_EXTENSIONS)) {
        throw new Exception("Formato de arquivo nao suportado (.). Envie MP3, WAV, M4A, OGG, AAC, MP4 ou WEBM.");
    }

    if ( > MAX_FILE_SIZE) {
        throw new Exception("Tamanho do arquivo excede o limite permitido de " . (MAX_FILE_SIZE / (1024 * 1024)) . "MB.");
    }

    // Limpa uploads antigos antes
    AudioHelper::cleanOldUploads(UPLOAD_DIR);

    // Salva arquivo com nome unico e seguro
     = uniqid('audio_', true) . '.' . ;
     = UPLOAD_DIR . ;

    if (!move_uploaded_file(['tmp_name'], )) {
        throw new Exception("Nao foi possivel salvar o arquivo de audio no servidor.");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Upload realizado com sucesso!',
        'file_id' => ,
        'original_name' => ,
        'size_formatted' => round( / (1024 * 1024), 2) . ' MB',
        'file_url' => 'uploads/' . 
    ]);
} catch (Exception ) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => ->getMessage()
    ]);
}
