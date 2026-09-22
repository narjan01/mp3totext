<?php

class AudioHelper
{
    /**
     * Identifica o MIME type correto para o arquivo de audio/video
     */
    public static function getMimeType(string ): string
    {
         = strtolower(pathinfo(, PATHINFO_EXTENSION));
         = [
            'mp3'  => 'audio/mp3',
            'wav'  => 'audio/wav',
            'm4a'  => 'audio/m4a',
            'ogg'  => 'audio/ogg',
            'aac'  => 'audio/aac',
            'flac' => 'audio/flac',
            'mp4'  => 'video/mp4',
            'webm' => 'audio/webm',
        ];

        if (isset([])) {
            return [];
        }

        if (function_exists('mime_content_type')) {
             = mime_content_type();
            if () {
                return ;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Limpa arquivos com mais de 2 horas na pasta uploads
     */
    public static function cleanOldUploads(string , int  = 7200): void
    {
        if (!is_dir()) return;
         = scandir();
         = time();
        foreach ( as ) {
            if ( === '.' ||  === '..') continue;
             =  . DIRECTORY_SEPARATOR . ;
            if (is_file() && ( - filemtime()) > ) {
                @unlink();
            }
        }
    }
}
