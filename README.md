# 🎙️ Transcritor & Resumidor Inteligente de Áudio com IA (PHP)

Aplicação web desenvolvida em **PHP** integrada com **IA Gratuita e de Alta Precisão (Google Gemini)** para:
1. **Upload e Extração de Áudio**: Aceita MP3, WAV, M4A, OGG, AAC, MP4 e WEBM.
2. **Transcrição Completa**: Converte a fala com fidelidade absoluta palavra por palavra e pontuação adequada.
3. **Resumo Estruturado**: Gera automaticamente:
   - Resumo Executivo
   - Principais Tópicos Abordados (Bullet points)
   - Conclusões & Ações / Próximos passos
4. **Player de Áudio Integrado**: Ouça o áudio enviado enquanto lê a transcrição.
5. **Exportação Facilitada**:
   - Copiar transcrição ou resumo com 1 clique
   - Download em formato `.txt`
   - Download em formato `.md` (Markdown)

---

## 🚀 Como Executar Localmente

### Opção 1: Com PHP Instalado (Linha de Comando)
Se você possui o PHP instalado:
```bash
php -S localhost:8000
```
Depois, abra seu navegador em: [http://localhost:8000](http://localhost:8000)

### Opção 2: Com XAMPP, Laragon ou WampServer
1. Copie a pasta deste projeto para dentro da pasta `htdocs` (no XAMPP) ou `www` (no Laragon).
2. Inicie o servidor Apache no painel do XAMPP/Laragon.
3. Acesse pelo navegador: `http://localhost/gallant-oppenheimer` (ou o nome da pasta do projeto).

---

## 🔑 Configuração da Chave de IA (Gratuita)

A aplicação utiliza a API do **Google Gemini** (que oferece uso gratuito com cotas generosas através do Google AI Studio):

1. Obtenha sua chave gratuita em: **[https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)**
2. Você pode inserir e salvar a chave **diretamente pela interface do site** (ela fica salva de forma segura no seu navegador para seus próximos acessos).
3. Ou opcionalmente, defina a variável de ambiente `GEMINI_API_KEY` ou edite o arquivo `config.php`.

---

## 📂 Estrutura de Arquivos

- `index.php`: Interface web moderna com TailwindCSS e player de áudio.
- `upload.php`: Endpoint que recebe e valida os arquivos enviados.
- `api.php`: Endpoint que recebe os parâmetros e aciona a IA Gemini.
- `config.php`: Definições globais de limites e extensões.
- `services/GeminiService.php`: Comunicação cURL nativa com a API REST do Gemini.
- `services/AudioHelper.php`: Utilitários de MIME type e limpeza de temporários.
- `css/style.css`: Estilos customizados (cards com efeito glassmorphism e scrollbars).
- `js/app.js`: Lógica client-side com drag & drop, progresso de upload, tabs e exportações.