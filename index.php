<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcritor &amp; Resumidor de Audio IA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="py-10 px-4 md:px-8">
    <div class="max-w-5xl mx-auto space-y-8">
        <!-- Header -->
        <header class="text-center space-y-3">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-card text-xs font-medium text-indigo-300 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                IA Gratuita e de Alta Precisao (Google Gemini)
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-indigo-200 to-indigo-400">
                Transcritor &amp; Resumo Inteligente
            </h1>
            <p class="text-slate-400 max-w-2xl mx-auto text-sm md:text-base">
                Envie audios MP3, WAV, M4A ou videos MP4. Nossa IA transcreve palavra por palavra com extrema fidelidade e gera um resumo executivo com topicos e conclusoes.
            </p>
        </header>

        <!-- API Key & Configuracao -->
        <div class="glass-card p-5 rounded-2xl border border-slate-700/60">
            <div class="flex flex-col md:flex-row items-center gap-4">
                <div class="flex-1 w-full">
                    <label for="api_key" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Chave da API Gemini (Google AI Studio - Gratuito)
                    </label>
                    <div class="relative">
                        <input type="password" id="api_key" placeholder="Cole sua chave AIzaSy... aqui" 
                               class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all font-mono">
                    </div>
                </div>
                <div class="flex items-end gap-2 w-full md:w-auto pt-2 md:pt-5">
                    <button type="button" id="save_key_btn" 
                            class="w-full md:w-auto bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 text-xs font-medium px-4 py-3 rounded-xl transition-all">
                        Salvar Chave
                    </button>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" 
                       class="text-xs text-indigo-400 hover:text-indigo-300 underline whitespace-nowrap p-3">
                        Obter chave gratis &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Area de Upload -->
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-slate-700/60 space-y-6">
            <div id="dropzone" class="dropzone rounded-2xl p-8 md:p-12 text-center cursor-pointer flex flex-col items-center justify-center gap-4">
                <input type="file" id="audio_file" accept=".mp3,.wav,.m4a,.ogg,.aac,.flac,.mp4,.webm" class="hidden">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-base font-semibold text-slate-200">
                        Arraste e solte seu arquivo de audio aqui ou <span class="text-indigo-400 underline">clique para selecionar</span>
                    </p>
                    <p class="text-xs text-slate-400">
                        Formatos suportados: MP3, WAV, M4A, OGG, AAC, MP4, WEBM (Ate 50MB)
                    </p>
                </div>
            </div>

            <!-- Arquivo Selecionado e Player -->
            <div id="selected_file_card" class="hidden bg-slate-900/60 border border-slate-700/60 p-4 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                    </div>
                    <div class="truncate">
                        <p id="file_name" class="text-sm font-medium text-slate-200 truncate"></p>
                        <p id="file_size" class="text-xs text-slate-400"></p>
                    </div>
                </div>

                <div class="w-full md:w-auto flex-1 max-w-md">
                    <audio id="audio_player" controls class="w-full h-10 hidden"></audio>
                </div>
            </div>

            <!-- Progresso do Upload -->
            <div id="upload_progress_card" class="hidden space-y-2">
                <div class="flex justify-between text-xs text-slate-400 font-medium">
                    <span id="status_text">Enviando audio...</span>
                    <span id="upload_progress_text">0%</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div id="upload_progress_bar" class="bg-indigo-500 h-full rounded-full transition-all duration-200" style="width: 0%"></div>
                </div>
            </div>

            <!-- Botao Principal de Acao -->
            <button type="button" id="process_btn" disabled
                    class="w-full py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed font-semibold text-white shadow-xl shadow-indigo-600/30 flex items-center justify-center transition-all text-base">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Transcrever e Gerar Resumo com IA
            </button>
        </div>

        <!-- Secao de Resultados -->
        <div id="results_section" class="hidden glass-card p-6 md:p-8 rounded-3xl border border-slate-700/60 space-y-6">
            <!-- Barra de Ferramentas e Abas -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 border-b border-slate-700/60 pb-5">
                <div class="inline-flex p-1 bg-slate-900/80 rounded-xl border border-slate-700/50">
                    <button id="tab_both_btn" class="tab-btn active px-4 py-2 text-xs md:text-sm font-medium rounded-lg transition-all">
                        Ambos Lado a Lado
                    </button>
                    <button id="tab_summary_btn" class="tab-btn px-4 py-2 text-xs md:text-sm font-medium rounded-lg text-slate-400 transition-all">
                        Somente Resumo
                    </button>
                    <button id="tab_transcription_btn" class="tab-btn px-4 py-2 text-xs md:text-sm font-medium rounded-lg text-slate-400 transition-all">
                        Somente Transcricao
                    </button>
                </div>

                <!-- Botoes de Exportacao -->
                <div class="flex items-center gap-2">
                    <button id="download_txt_btn" title="Baixar como TXT"
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 flex items-center gap-1.5 text-xs font-medium transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>TXT</span>
                    </button>
                    <button id="download_md_btn" title="Baixar como Markdown"
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 flex items-center gap-1.5 text-xs font-medium transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span>Markdown</span>
                    </button>
                </div>
            </div>

            <!-- Container dos Dados -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Coluna Resumo -->
                <div class="flex flex-col space-y-3">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            Resumo Estruturado
                        </h3>
                        <button id="copy_summary_btn" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copiar Resumo
                        </button>
                    </div>
                    <div id="summary_content" class="bg-slate-900/50 rounded-2xl p-5 border border-slate-800/80 custom-scrollbar max-h-[500px] overflow-y-auto space-y-4">
                    </div>
                </div>

                <!-- Coluna Transcricao Completa -->
                <div class="flex flex-col space-y-3">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            Transcricao Completa
                        </h3>
                        <button id="copy_transcription_btn" class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copiar Transcricao
                        </button>
                    </div>
                    <div class="bg-slate-900/50 rounded-2xl p-5 border border-slate-800/80 max-h-[500px] overflow-y-auto custom-scrollbar">
                        <div id="transcription_content" class="text-slate-200 text-sm leading-relaxed whitespace-pre-wrap select-text font-normal">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>