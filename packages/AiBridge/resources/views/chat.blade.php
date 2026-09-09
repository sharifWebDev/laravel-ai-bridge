<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bridge Assistant - Copilot Style</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body class="bg-slate-900 text-slate-100 h-screen flex flex-col font-sans">

    <!-- Header -->
    <header class="bg-slate-800 border-b border-slate-700 px-6 py-4 flex items-center justify-between shadow-md">
        <div class="flex items-center space-x-3">
            <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></div>
            <h1 class="text-lg font-semibold tracking-wide">AI Bridge Assistant</h1>
        </div>
        <span class="text-xs bg-slate-700 text-slate-300 px-2.5 py-1 rounded-full border border-slate-600">Copilot UI</span>
    </header>

    <!-- Chat Container -->
    <main id="chat-container" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 max-w-4xl w-full mx-auto">
        <!-- Welcome Message -->
        <div class="flex items-start space-x-4">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-sm shrink-0 shadow">AI</div>
            <div class="bg-slate-800 border border-slate-700/60 rounded-2xl p-4 max-w-2xl text-sm leading-relaxed shadow-sm">
                <p>Hello! I am your AI controller bridge assistant. How can I help you execute tasks or manage records today?</p>
            </div>
        </div>
    </main>

    <!-- Input Footer -->
    <footer class="bg-slate-800 border-t border-slate-700 p-4 shadow-lg">
        <div class="max-w-4xl mx-auto relative flex items-center">
            <textarea id="prompt-input" rows="1" placeholder="Type a message or command (e.g., Export users to CSV)..." class="w-full bg-slate-900 text-slate-100 placeholder-slate-400 border border-slate-700 rounded-xl pl-4 pr-14 py-3.5 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none text-sm shadow-inner"></textarea>
            <button id="send-btn" onclick="sendPrompt()" class="absolute right-3 bg-indigo-600 hover:bg-indigo-500 text-white p-2 rounded-lg transition-colors flex items-center justify-center shadow">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </div>
        <p class="text-center text-xs text-slate-500 mt-2">AI Bridge powered by Laravel & Gemini</p>
    </footer>

    <!-- Scripting -->
    <script>
        const chatContainer = document.getElementById('chat-container');
        const promptInput = document.getElementById('prompt-input');

        // Auto-resize textarea
        promptInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Enter key to submit (Shift+Enter for newline)
        promptInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendPrompt();
            }
        });

        async function sendPrompt() {
            const prompt = promptInput.value.trim();
            if (!prompt) return;

            // Append User Message
            appendMessage(prompt, 'user');
            promptInput.value = '';
            promptInput.style.height = 'auto';

            // Show Typing Indicator
            const loadingId = showLoadingIndicator();

            try {
                const response = await fetch('/api/ai/prompt', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ prompt })
                });

                const data = await response.json();
                removeLoadingIndicator(loadingId);

                if (response.status === 403 || data.status === 'error') {
                    appendMessage(data.message || 'Unauthorized action.', 'ai', true);
                    return;
                }

                if (data.status === 'success' && data.action) {
                    let htmlContent = `<strong>Executed Action:</strong> <code class="bg-slate-900 px-1.5 py-0.5 rounded text-indigo-400">${data.action}</code><br>`;
                    if (Object.keys(data.arguments).length > 0) {
                        htmlContent += `<pre class="mt-2 bg-slate-900 p-2 rounded text-xs text-slate-300 overflow-x-auto">${JSON.stringify(data.arguments, null, 2)}</pre>`;
                    }
                    appendMessage(htmlContent, 'ai', false, true);
                } else {
                    appendMessage(data.message || 'No response generated.', 'ai');
                }

            } catch (error) {
                removeLoadingIndicator(loadingId);
                appendMessage('Network or server error occurred.', 'ai', true);
            }
        }

        function appendMessage(text, sender, isError = = false, isHtml = false) {
            const isUser = sender === 'user';
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex items-start space-x-4 ${isUser ? 'flex-row-reverse space-x-reverse' : ''}`;
            
            let bubbleBg = isUser ? 'bg-indigo-600 text-white' : 'bg-slate-800 border border-slate-700/60 text-slate-100';
            if (isError) bubbleBg = 'bg-rose-900/50 border border-rose-700 text-rose-200';

            messageDiv.innerHTML = `
                <div class="w-8 h-8 rounded-lg ${isUser ? 'bg-slate-700' : 'bg-indigo-600'} flex items-center justify-center font-bold text-sm shrink-0 shadow">
                    ${isUser ? 'U' : 'AI'}
                </div>
                <div class="${bubbleBg} rounded-2xl p-4 max-w-2xl text-sm leading-relaxed shadow-sm">
                    ${isHtml ? text : `<p>${escapeHtml(text)}</p>`}
                </div>
            `;

            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showLoadingIndicator() {
            const id = 'loading-' + Date.now();
            const loadingDiv = document.createElement('div');
            loadingDiv.id = id;
            loadingDiv.className = 'flex items-start space-x-4';
            loadingDiv.innerHTML = `
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-sm shrink-0 shadow">AI</div>
                <div class="bg-slate-800 border border-slate-700/60 rounded-2xl p-4 text-sm text-slate-400 flex items-center space-x-2">
                    <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></span>
                    <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                </div>
            `;
            chatContainer.appendChild(loadingDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
            return id;
        }

        function removeLoadingIndicator(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    </script>
</body>
</html>
