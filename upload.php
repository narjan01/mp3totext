<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/AudioHelper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metodo de requisicao invalido.");
    }

    if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        throw new Exception("O arquivo enviado excedeu o limite do servidor (post_max_size / upload_max_filesize).");
    }

    if (!isset($_FILES['audio_file'])) {
        throw new Exception("Nenhum arquivo de audio recebido pelo servidor.");
    }

    $file = $_FILES['audio_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "O arquivo excede o limite upload_max_filesize do PHP.",
            UPLOAD_ERR_FORM_SIZE  => "O arquivo excede o limite do formulario HTML.",
            UPLOAD_ERR_PARTIAL    => "O arquivo foi apenas parcialmente carregado.",
            UPLOAD_ERR_NO_FILE    => "Nenhum arquivo foi enviado.",
            UPLOAD_ERR_NO_TMP_DIR => "Pasta temporaria ausente no servidor.",
            UPLOAD_ERR_CANT_WRITE => "Falha ao gravar arquivo em disco.",
            UPLOAD_ERR_EXTENSION  => "Uma extensao do PHP interrompeu o upload."
        ];
        $errMsg = $uploadErrors[$file['error']] ?? ("Erro no upload (Codigo: " . $file['error'] . ")");
        throw new Exception($errMsg);
    }

    $fileSize = $file['size'];
    $fileName = basename($file['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        throw new Exception("Formato de arquivo nao suportado (.$ext). Envie MP3, WAV, M4A, OGG, AAC, MP4 ou WEBM.");
    }

    if ($fileSize > MAX_FILE_SIZE) {
        throw new Exception("Tamanho do arquivo (" . round($fileSize / (1024*1024), 1) . "MB) excede o limite permitido de " . (MAX_FILE_SIZE / (1024 * 1024)) . "MB.");
    }

    // Limpa uploads antigos antes
    AudioHelper::cleanOldUploads(UPLOAD_DIR);

    // Salva arquivo com nome unico e seguro
    $uniqueName = uniqid('audio_', true) . '.' . $ext;
    $targetPath = UPLOAD_DIR . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Nao foi possivel salvar o arquivo de audio no servidor. Verifique permissoes da pasta.");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Upload realizado com sucesso!',
        'file_id' => $uniqueName,
        'original_name' => $fileName,
        'size_formatted' => round($fileSize / (1024 * 1024), 2) . ' MB',
        'file_url' => 'uploads/' . $uniqueName
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}