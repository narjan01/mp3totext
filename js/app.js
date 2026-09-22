document.addEventListener('DOMContentLoaded', function() {
    var dropzone = document.getElementById('dropzone');
    var audioFileInput = document.getElementById('audio_file');
    var apiKeyInput = document.getElementById('api_key');
    var saveKeyBtn = document.getElementById('save_key_btn');
    var processBtn = document.getElementById('process_btn');
    var selectedFileCard = document.getElementById('selected_file_card');
    var fileNameSpan = document.getElementById('file_name');
    var fileSizeSpan = document.getElementById('file_size');
    var audioPlayer = document.getElementById('audio_player');
    var uploadProgressCard = document.getElementById('upload_progress_card');
    var uploadProgressBar = document.getElementById('upload_progress_bar');
    var uploadProgressText = document.getElementById('upload_progress_text');
    var statusText = document.getElementById('status_text');
    var resultsSection = document.getElementById('results_section');
    
    var tabTranscriptionBtn = document.getElementById('tab_transcription_btn');
    var tabSummaryBtn = document.getElementById('tab_summary_btn');
    var tabBothBtn = document.getElementById('tab_both_btn');
    var transcriptionContent = document.getElementById('transcription_content');
    var summaryContent = document.getElementById('summary_content');
    var copyTranscriptionBtn = document.getElementById('copy_transcription_btn');
    var copySummaryBtn = document.getElementById('copy_summary_btn');
    var downloadTxtBtn = document.getElementById('download_txt_btn');
    var downloadMdBtn = document.getElementById('download_md_btn');

    var uploadedFileId = null;
    var currentTranscription = '';
    var currentSummaryObj = null;

    var savedKey = localStorage.getItem('gemini_api_key') || '';
    if (savedKey) {
        apiKeyInput.value = savedKey;
    }

    if (saveKeyBtn) {
        saveKeyBtn.addEventListener('click', function() {
            var key = apiKeyInput.value.trim();
            if (key) {
                localStorage.setItem('gemini_api_key', key);
                showToast('Chave de API salva com sucesso!');
            } else {
                localStorage.removeItem('gemini_api_key');
                showToast('Chave removida.', 'info');
            }
        });
    }

    ['dragenter', 'dragover'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelection(files[0]);
        }
    });

    dropzone.addEventListener('click', function() {
        audioFileInput.click();
    });

    audioFileInput.addEventListener('change', function() {
        if (audioFileInput.files.length > 0) {
            handleFileSelection(audioFileInput.files[0]);
        }
    });

    function handleFileSelection(file) {
        fileNameSpan.textContent = file.name;
        fileSizeSpan.textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        selectedFileCard.classList.remove('hidden');
        resultsSection.classList.add('hidden');
        processBtn.disabled = false;

        var objectUrl = URL.createObjectURL(file);
        audioPlayer.src = objectUrl;
        audioPlayer.classList.remove('hidden');

        uploadFile(file);
    }

    function uploadFile(file) {
        uploadProgressCard.classList.remove('hidden');
        uploadProgressBar.style.width = '0%';
        uploadProgressText.textContent = '0%';
        statusText.textContent = 'Enviando arquivo de audio...';
        processBtn.disabled = true;

        var formData = new FormData();
        formData.append('audio_file', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload.php', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percent = Math.round((e.loaded / e.total) * 100);
                uploadProgressBar.style.width = percent + '%';
                uploadProgressText.textContent = percent + '%';
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        uploadedFileId = res.file_id;
                        statusText.textContent = 'Upload concluido! Pronto para processar com IA.';
                        processBtn.disabled = false;
                        showToast('Arquivo enviado com sucesso!');
                    } else {
                        statusText.textContent = 'Erro: ' + res.error;
                        showToast(res.error, 'error');
                    }
                } catch (err) {
                    statusText.textContent = 'Erro ao ler resposta do servidor.';
                }
            } else {
                statusText.textContent = 'Falha no upload (' + xhr.status + ').';
                showToast('Falha no upload do audio.', 'error');
            }
        };

        xhr.onerror = function() {
            statusText.textContent = 'Falha de conexao com o servidor.';
            showToast('Erro de rede ao enviar audio.', 'error');
        };

        xhr.send(formData);
    }

    processBtn.addEventListener('click', async function() {
        var apiKey = apiKeyInput.value.trim();
        if (!apiKey) {
            showToast('Por favor, informe sua Gemini API Key antes de continuar.', 'warning');
            apiKeyInput.focus();
            return;
        }

        if (!uploadedFileId) {
            showToast('Por favor, selecione e envie um arquivo de audio primeiro.', 'warning');
            return;
        }

        localStorage.setItem('gemini_api_key', apiKey);

        processBtn.disabled = true;
        processBtn.innerHTML = 'Processando com IA...';
        statusText.textContent = 'Processando audio com a IA Gemini (transcrevendo e resumindo)... aguarde.';

        try {
            var resp = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    file_id: uploadedFileId,
                    api_key: apiKey
                })
            });

            var data = await resp.json();

            if (!resp.ok || !data.success) {
                throw new Error(data.error || 'Erro ao processar audio.');
            }

            renderResults(data.data);
            showToast('Transcricao e resumo gerados com sucesso!');
            statusText.textContent = 'Concluido com sucesso!';
        } catch (err) {
            showToast(err.message, 'error');
            statusText.textContent = 'Erro: ' + err.message;
        } finally {
            processBtn.disabled = false;
            processBtn.innerHTML = 'Transcrever e Gerar Resumo com IA';
        }
    });

    function renderResults(result) {
        currentTranscription = result.transcription || '';
        currentSummaryObj = result.summary || {};

        transcriptionContent.innerText = currentTranscription;

        var summaryHtml = '';
        if (currentSummaryObj.executive) {
            summaryHtml += '<div class="mb-6"><h4 class="text-indigo-400 font-semibold mb-2">Resumo Executivo</h4><p class="text-slate-200 leading-relaxed bg-slate-900/60 p-4 rounded-xl border border-slate-700/50">' + escapeHtml(currentSummaryObj.executive) + '</p></div>';
        }

        if (currentSummaryObj.key_points && currentSummaryObj.key_points.length > 0) {
            summaryHtml += '<div class="mb-6"><h4 class="text-indigo-400 font-semibold mb-2">Principais Topicos</h4><ul class="list-disc list-inside space-y-2 bg-slate-900/60 p-4 rounded-xl border border-slate-700/50 text-slate-200">';
            currentSummaryObj.key_points.forEach(function(pt) {
                summaryHtml += '<li>' + escapeHtml(pt) + '</li>';
            });
            summaryHtml += '</ul></div>';
        }

        if (currentSummaryObj.conclusions) {
            summaryHtml += '<div><h4 class="text-indigo-400 font-semibold mb-2">Conclusoes e Proximos Passos</h4><p class="text-slate-200 leading-relaxed bg-slate-900/60 p-4 rounded-xl border border-slate-700/50">' + escapeHtml(currentSummaryObj.conclusions) + '</p></div>';
        }

        summaryContent.innerHTML = summaryHtml || '<p class="text-slate-400">Nenhum resumo disponivel.</p>';

        resultsSection.classList.remove('hidden');
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    }

    tabTranscriptionBtn.addEventListener('click', function() {
        setActiveTab(tabTranscriptionBtn);
        transcriptionContent.parentElement.classList.remove('hidden');
        summaryContent.parentElement.classList.add('hidden');
    });

    tabSummaryBtn.addEventListener('click', function() {
        setActiveTab(tabSummaryBtn);
        transcriptionContent.parentElement.classList.add('hidden');
        summaryContent.parentElement.classList.remove('hidden');
    });

    tabBothBtn.addEventListener('click', function() {
        setActiveTab(tabBothBtn);
        transcriptionContent.parentElement.classList.remove('hidden');
        summaryContent.parentElement.classList.remove('hidden');
    });

    function setActiveTab(activeBtn) {
        [tabTranscriptionBtn, tabSummaryBtn, tabBothBtn].forEach(function(btn) {
            btn.classList.remove('active');
        });
        activeBtn.classList.add('active');
    }

    copyTranscriptionBtn.addEventListener('click', function() {
        navigator.clipboard.writeText(currentTranscription);
        showToast('Transcricao copiada!');
    });

    copySummaryBtn.addEventListener('click', function() {
        var text = '';
        if (currentSummaryObj) {
            if (currentSummaryObj.executive) text += 'RESUMO EXECUTIVO:
' + currentSummaryObj.executive + '

';
            if (currentSummaryObj.key_points) {
                text += 'PONTOS PRINCIPAIS:
';
                currentSummaryObj.key_points.forEach(function(p) { text += '- ' + p + '
'; });
                text += '
';
            }
            if (currentSummaryObj.conclusions) text += 'CONCLUSOES:
' + currentSummaryObj.conclusions + '
';
        }
        navigator.clipboard.writeText(text);
        showToast('Resumo copiado!');
    });

    downloadTxtBtn.addEventListener('click', function() {
        var fullText = '=== RESUMO INTELIGENTE ===

';
        if (currentSummaryObj) {
            if (currentSummaryObj.executive) fullText += 'RESUMO EXECUTIVO:
' + currentSummaryObj.executive + '

';
            if (currentSummaryObj.key_points) {
                fullText += 'PONTOS PRINCIPAIS:
';
                currentSummaryObj.key_points.forEach(function(p) { fullText += '- ' + p + '
'; });
                fullText += '
';
            }
            if (currentSummaryObj.conclusions) fullText += 'CONCLUSOES:
' + currentSummaryObj.conclusions + '

';
        }
        fullText += '=== TRANSCRICAO COMPLETA ===

' + currentTranscription;

        downloadFile(fullText, 'transcricao_e_resumo.txt', 'text/plain');
    });

    downloadMdBtn.addEventListener('click', function() {
        var md = '# Resumo e Transcricao de Audio

';
        if (currentSummaryObj) {
            md += '## Resumo Executivo

' + (currentSummaryObj.executive || '') + '

';
            if (currentSummaryObj.key_points && currentSummaryObj.key_points.length > 0) {
                md += '## Principais Topicos

';
                currentSummaryObj.key_points.forEach(function(p) { md += '* ' + p + '
'; });
                md += '
';
            }
            if (currentSummaryObj.conclusions) {
                md += '## Conclusoes e Proximos Passos

' + currentSummaryObj.conclusions + '

';
            }
        }
        md += '## Transcricao Completa

' + currentTranscription;

        downloadFile(md, 'transcricao_e_resumo.md', 'text/markdown');
    });

    function downloadFile(content, filename, type) {
        var blob = new Blob([content], { type: type + ';charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showToast(message, type) {
        type = type || 'success';
        var toast = document.createElement('div');
        var bgColors = {
            success: 'bg-emerald-600',
            error: 'bg-rose-600',
            warning: 'bg-amber-600',
            info: 'bg-blue-600'
        };
        var bg = bgColors[type] || 'bg-slate-800';
        toast.className = 'fixed bottom-6 right-6 ' + bg + ' text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 z-50 text-sm font-medium transition-all';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(function() {
            toast.remove();
        }, 3500);
    }
});