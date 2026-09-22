<?php

class AudioHelper
{
    /**
     * Identifica o MIME type correto para o arquivo de audio/video
     */
    public static function getMimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $map = [
            'mp3'  => 'audio/mp3',
            'wav'  => 'audio/wav',
            'm4a'  => 'audio/m4a',
            'ogg'  => 'audio/ogg',
            'aac'  => 'audio/aac',
            'flac' => 'audio/flac',
            'mp4'  => 'video/mp4',
            'webm' => 'audio/webm',
        ];

        if (isset($map[$ext])) {
            return $map[$ext];
        }

        if (function_exists('mime_content_type')) {
            $detected = mime_content_type($filePath);
            if ($detected) {
                return $detected;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Fragmenta um arquivo de audio em partes menores de duracao caso necessario usando ffmpeg
     */
    public static function splitAudioIfLarge(string $filePath, int $maxSizeBytes = 80000000): array
    {
        if (filesize($filePath) <= $maxSizeBytes) {
            return [$filePath];
        }

        // Verifica se ffmpeg esta disponivel no sistema
        exec('ffmpeg -version 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            // Se nao tiver ffmpeg, retorna o proprio arquivo original
            return [$filePath];
        }

        $dir = dirname($filePath);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $base = pathinfo($filePath, PATHINFO_FILENAME);
        $part1 = $dir . DIRECTORY_SEPARATOR . $base . '_part1.' . $ext;
        $part2 = $dir . DIRECTORY_SEPARATOR . $base . '_part2.' . $ext;

        // Obtem duracao total
        $durationCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath);
        $duration = (float)trim(shell_exec($durationCmd) ?: '0');

        if ($duration <= 0) {
            return [$filePath];
        }

        $half = $duration / 2;

        // Parte 1 (0 ate metade)
        $cmd1 = "ffmpeg -y -i " . escapeshellarg($filePath) . " -t " . round($half) . " -c copy " . escapeshellarg($part1) . " 2>&1";
        exec($cmd1, $o1, $r1);

        // Parte 2 (metade ate o fim)
        $cmd2 = "ffmpeg -y -ss " . round($half) . " -i " . escapeshellarg($filePath) . " -c copy " . escapeshellarg($part2) . " 2>&1";
        exec($cmd2, $o2, $r2);

        if ($r1 === 0 && $r2 === 0 && file_exists($part1) && file_exists($part2)) {
            return [$part1, $part2];
        }

        return [$filePath];
    }

    /**
     * Limpa arquivos com mais de 2 horas na pasta uploads
     */
    public static function cleanOldUploads(string $dir, int $maxAgeSeconds = 7200): void
    {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        $now = time();
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path) && ($now - filemtime($path)) > $maxAgeSeconds) {
                @unlink($path);
            }
        }
    }
}