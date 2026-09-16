<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bridge · Pro</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(100,116,139,0.35); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(100,116,139,0.6); }
        .dark ::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); }
        .dark ::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.6); }

        .fade-in { animation: fadeIn 0.3s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        .typing-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; animation: typingBounce 1.2s infinite ease-in-out; }
        .typing-dot:nth-child(2) { animation-delay: 0.15s; }
        .typing-dot:nth-child(3) { animation-delay: 0.3s; }
        @keyframes typingBounce { 0%,60%,100% { transform: translateY(0); opacity: 0.4; } 30% { transform: translateY(-5px); opacity: 1; } }

        .msg-actions { opacity: 0; transition: opacity 0.15s; }
        .msg-wrapper:hover .msg-actions { opacity: 1; }

        .pin-actions { opacity: 0; transition: opacity 0.15s; }
        .pin-item:hover .pin-actions { opacity: 1; }

        .json-key { color: #7c3aed; }
        .dark .json-key { color: #a78bfa; }
        .json-string { color: #059669; }
        .dark .json-string { color: #6ee7b7; }
        .json-number { color: #d97706; }
        .dark .json-number { color: #fbbf24; }
        .json-boolean { color: #dc2626; }
        .dark .json-boolean { color: #f87171; }
        .json-null { color: #94a3b8; }

        #pin-sidebar { transition: transform 0.3s ease, opacity 0.3s ease; }
        @media (max-width: 767px) {
            #pin-sidebar {
                position: fixed; top: 0; left: 0; bottom: 0;
                z-index: 40; width: 280px;
                transform: translateX(-100%);
            }
            #pin-sidebar.open { transform: translateX(0); }
        }
        @media (min-width: 768px) {
            #pin-sidebar { position: static; transform: none !important; }
            #pin-overlay { display: none !important; }
            #mobile-pin-toggle { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans antialiased min-h-screen flex flex-col transition-colors duration-200">

<!-- ============================================================ -->
<!-- PAGE: HOME                                                    -->
<!-- ============================================================ -->
<div id="page-home" class="page flex flex-col min-h-screen">
    <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg border-b border-slate-200 dark:border-slate-800 sticky top-0 z-20 transition-colors">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]"></div>
                <h1 class="text-base md:text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                    AI Bridge <span class="text-indigo-600 dark:text-indigo-400 font-light">Assistant</span>
                </h1>
                <span class="hidden sm:inline text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider font-medium bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50">Pro</span>
            </div>
            <div class="flex items-center gap-3">
                <button data-action="toggle-theme" class="relative w-[52px] h-7 rounded-full bg-slate-200 dark:bg-indigo-600 transition-colors duration-300 focus:outline-none" title="Toggle theme">
                    <span id="theme-knob" class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white dark:bg-slate-900 shadow flex items-center justify-center text-[10px] transition-transform duration-300 dark:translate-x-[24px]">☀️</span>
                </button>
                <button data-action="show-page" data-page="chat" class="hidden sm:inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-sm transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Open Chat
                </button>
            </div>
        </div>
    </header>

    <main class="flex-1 bg-gradient-to-br from-indigo-50 via-white to-slate-100 dark:from-indigo-950 dark:via-slate-950 dark:to-slate-900 flex items-center justify-center p-6 transition-colors">
        <div class="max-w-3xl w-full text-center space-y-8 fade-in">
            <div class="flex justify-center">
                <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-2xl shadow-indigo-500/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
            <div class="space-y-4">
                <h2 class="text-4xl md:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">AI Bridge <span class="text-indigo-600 dark:text-indigo-400">Assistant</span></h2>
                <p class="text-lg md:text-xl max-w-xl mx-auto leading-relaxed text-slate-500 dark:text-slate-400">Intelligent JSON mapping with N-th level recursive table rendering, pagination awareness, and full export capabilities.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mx-auto pt-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 text-left shadow-sm hover:shadow-md transition-all">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center mb-3 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    </div>
                    <h3 class="font-semibold text-sm text-slate-800 dark:text-slate-200 mb-1">Recursive Tables</h3>
                    <p class="text-xs leading-relaxed text-slate-400 dark:text-slate-500">N-th level JSON rendering with nested tables and inline pagination.</p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 text-left shadow-sm hover:shadow-md transition-all">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center mb-3 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-semibold text-sm text-slate-800 dark:text-slate-200 mb-1">Binary Files</h3>
                    <p class="text-xs leading-relaxed text-slate-400 dark:text-slate-500">PDF, Excel, Word, ZIP — preview and download directly.</p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 text-left shadow-sm hover:shadow-md transition-all">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center mb-3 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    </div>
                    <h3 class="font-semibold text-sm text-slate-800 dark:text-slate-200 mb-1">Smart Pagination</h3>
                    <p class="text-xs leading-relaxed text-slate-400 dark:text-slate-500">Detects meta pagination and renders full navigation controls.</p>
                </div>
            </div>
            <div class="pt-2">
                <button data-action="show-page" data-page="chat" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-8 py-3.5 rounded-2xl shadow-lg shadow-indigo-500/30 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Start Conversation
                </button>
            </div>
        </div>
    </main>
    <footer class="py-4 text-center text-[11px] tracking-wider text-slate-400 dark:text-slate-600">AI BRIDGE · POWERED BY LARAVEL & GEMINI</footer>
</div>

<!-- ============================================================ -->
<!-- PAGE: CHAT                                                    -->
<!-- ============================================================ -->
<div id="page-chat" class="page hidden h-screen overflow-hidden flex-col">
    <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 flex-shrink-0 transition-colors">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button data-action="show-page" data-page="home" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition-colors" title="Back to home">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </button>
                <div class="flex items-center space-x-2.5">
                    <div class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_6px_#34d399]"></div>
                    <h1 class="text-sm md:text-base font-semibold tracking-tight text-slate-900 dark:text-white">AI Bridge Assistant</h1>
                </div>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <button id="mobile-pin-toggle" data-action="toggle-pin-sidebar" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition-colors md:hidden" title="Toggle pins">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                </button>
                <button data-action="toggle-theme" class="relative w-11 h-6 rounded-full bg-slate-200 dark:bg-indigo-600 transition-colors duration-300">
                    <span id="theme-knob-chat" class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white dark:bg-slate-900 shadow flex items-center justify-center text-[9px] transition-transform duration-300 dark:translate-x-[20px]">☀️</span>
                </button>
                <button id="login-btn" data-action="open-login" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-xl shadow-sm transition-all">Login</button>
                <div id="user-info" class="hidden items-center gap-2">
                    <div class="text-right leading-tight hidden sm:block">
                        <div id="user-name" class="text-xs font-semibold text-slate-800 dark:text-slate-200"></div>
                        <div id="user-role" class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500"></div>
                    </div>
                    <button data-action="logout" title="Logout" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden max-w-6xl w-full mx-auto relative">
        <div id="pin-overlay" data-action="close-pin-sidebar" class="hidden fixed inset-0 bg-black/40 z-30 md:hidden"></div>

        <aside id="pin-sidebar" class="w-64 border-r border-slate-200 dark:border-slate-800 flex-shrink-0 flex flex-col bg-white dark:bg-slate-900 transition-colors shadow-xl md:shadow-none">
            <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                    Pinned
                </h2>
                <div class="flex items-center gap-1">
                    <span id="pin-count" class="text-[10px] bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full font-medium">0</span>
                    <button data-action="close-pin-sidebar" class="md:hidden p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <div id="pin-list" class="flex-1 overflow-y-auto p-2 space-y-1 text-sm"></div>
            <div class="p-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                <button data-action="restore-default-pins" class="text-[11px] text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300 py-1.5 transition-colors font-medium">Restore defaults</button>
                <button data-action="clear-all-pins" class="text-[11px] text-slate-400 hover:text-red-500 dark:hover:text-red-400 py-1.5 transition-colors">Clear all</button>
            </div>
        </aside>

        <main id="chat-container" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-5 scroll-smooth">
            <div class="flex items-start gap-3 md:gap-4 fade-in">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-lg shadow-indigo-500/20">AI</div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 max-w-3xl text-sm leading-relaxed shadow-sm transition-colors">
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mb-1">Hello, I'm your AI Bridge assistant.</p>
                    <p class="text-slate-500 dark:text-slate-400">Ask me to execute tasks, manage records, or generate reports. I handle tables, files, images, and more with full recursive data support.</p>
                </div>
            </div>
        </main>
    </div>

    <footer class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg border-t border-slate-200 dark:border-slate-800 p-3 md:p-4 flex-shrink-0 transition-colors">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-1.5 flex items-end gap-2 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all">
                <textarea id="prompt-input" rows="1" placeholder="Type a message or command…" class="flex-1 bg-transparent px-3.5 py-3 resize-none text-sm focus:outline-none min-h-[50px] max-h-40 text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500"></textarea>
                <button id="send-btn" data-action="send-prompt" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl flex items-center justify-center flex-shrink-0 mb-0.5 mr-0.5 shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </div>
            <p class="text-center text-[10px] tracking-wider mt-2 text-slate-400 dark:text-slate-600">AI BRIDGE · POWERED BY LARAVEL & GEMINI</p>
        </div>
    </footer>
</div>

<!-- ============================================================ -->
<!-- MODAL: LOGIN                                                  -->
<!-- ============================================================ -->
<div id="login-modal" class="hidden fixed inset-0 bg-slate-900/40 dark:bg-black/70 backdrop-blur-sm items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-sm p-6 shadow-2xl fade-in transition-colors">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Login</h2>
            <button data-action="close-login" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <div id="login-error" class="hidden mb-3 text-xs rounded-xl px-3 py-2.5 bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300"></div>
        <form id="login-form" class="space-y-4">
            <div>
                <label class="block text-xs font-medium mb-1.5 text-slate-600 dark:text-slate-400">Email</label>
                <input id="login-email" type="email" required autocomplete="username" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 dark:text-slate-100 transition-colors" />
            </div>
            <div>
                <label class="block text-xs font-medium mb-1.5 text-slate-600 dark:text-slate-400">Password</label>
                <input id="login-password" type="password" required autocomplete="current-password" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 dark:text-slate-100 transition-colors" />
            </div>
            <button id="login-submit-btn" type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-xl shadow-sm transition-all">Login</button>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: PREVIEW                                                -->
<!-- ============================================================ -->
<div id="preview-modal" class="hidden fixed inset-0 bg-slate-900/40 dark:bg-black/70 backdrop-blur-sm items-center justify-center z-[60] p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-3xl max-h-[90vh] flex flex-col shadow-2xl fade-in transition-colors">
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800">
            <h3 id="preview-title" class="text-sm font-semibold truncate text-slate-800 dark:text-slate-200">Preview</h3>
            <button data-action="close-preview" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <div id="preview-content" class="p-4 overflow-auto flex-1"></div>
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
            <button data-action="close-preview" class="text-xs font-medium px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Close</button>
            <button data-action="download-preview" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg shadow-sm transition-all">Download</button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- APPLICATION SCRIPT (Vue-like structure)                       -->
<!-- ============================================================ -->
<script>
/* ============================================================
 * AI BRIDGE · Pro
 * Vue-like vanilla architecture with fixed auth flow
 * ============================================================ */
(() => {
    'use strict';

    /* ==========================================================
     * CONSTANTS
     * ========================================================== */
    const STORAGE_KEYS = Object.freeze({
        THEME: 'ai_bridge_theme',
        PINNED: 'ai_bridge_pinned_prompts',
        PIN_INIT: 'ai_bridge_pins_initialized',
        AUTH_TOKEN: 'ai_bridge_auth_token',
        AUTH_USER: 'ai_bridge_auth_user',
    });

    const API = Object.freeze({
        LOGIN: '/api/v1/login',
        PROMPT: '/api/ai/prompt',
    });

    const MAX_AUTO_PINS = 5;
    const TYPING_MAX_HEIGHT = 160;

    const DEFAULT_PINS = Object.freeze([
        { id: 'default-1', text: 'Show me the latest 10 records', isDefault: true },
        { id: 'default-2', text: 'List all users with their roles', isDefault: true },
        { id: 'default-3', text: 'Generate a summary report', isDefault: true },
        { id: 'default-4', text: 'Export all data as JSON', isDefault: true },
    ]);

    const BINARY_EXTENSIONS = Object.freeze({
        pdf:  { type: 'pdf',     icon: '📄', label: 'PDF Document',      mime: 'application/pdf' },
        doc:  { type: 'word',    icon: '📝', label: 'Word Document',     mime: 'application/msword' },
        docx: { type: 'word',    icon: '📝', label: 'Word Document',     mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
        xls:  { type: 'excel',   icon: '📊', label: 'Excel Spreadsheet', mime: 'application/vnd.ms-excel' },
        xlsx: { type: 'excel',   icon: '📊', label: 'Excel Spreadsheet', mime: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
        csv:  { type: 'excel',   icon: '📊', label: 'CSV File',          mime: 'text/csv' },
        zip:  { type: 'zip',     icon: '📦', label: 'ZIP Archive',       mime: 'application/zip' },
        rar:  { type: 'zip',     icon: '📦', label: 'RAR Archive',       mime: 'application/x-rar-compressed' },
        '7z': { type: 'zip',     icon: '📦', label: '7-Zip Archive',     mime: 'application/x-7z-compressed' },
        tar:  { type: 'zip',     icon: '📦', label: 'TAR Archive',       mime: 'application/x-tar' },
        gz:   { type: 'zip',     icon: '📦', label: 'GZIP Archive',      mime: 'application/gzip' },
        ppt:  { type: 'generic', icon: '📽️', label: 'PowerPoint',        mime: 'application/vnd.ms-powerpoint' },
        pptx: { type: 'generic', icon: '📽️', label: 'PowerPoint',        mime: 'application/vnd.openxmlformats-officedocument.presentationml.presentation' },
        txt:  { type: 'generic', icon: '📃', label: 'Text File',         mime: 'text/plain' },
        bin:  { type: 'generic', icon: '💾', label: 'Binary File',       mime: 'application/octet-stream' },
        exe:  { type: 'generic', icon: '⚙️', label: 'Executable',        mime: 'application/x-msdownload' },
        dmg:  { type: 'generic', icon: '💿', label: 'Disk Image',        mime: 'application/x-apple-diskimage' },
        iso:  { type: 'generic', icon: '💿', label: 'ISO Image',         mime: 'application/x-iso9660-image' },
    });

    const IMAGE_EXT_REGEX = /\.(jpg|jpeg|png|gif|webp|svg|bmp|ico)$/i;

    /* ==========================================================
     * UTILITIES
     * ========================================================== */
    const Utils = {
        escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        escapeForAttribute(str) {
            return String(str)
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '&quot;')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '');
        },

        tryParseJson(str) {
            try {
                const trimmed = str.trim();
                if ((trimmed.startsWith('{') && trimmed.endsWith('}')) ||
                    (trimmed.startsWith('[') && trimmed.endsWith(']'))) {
                    return JSON.parse(trimmed);
                }
            } catch (e) { /* ignore */ }
            return null;
        },

        getFileExtension(str) {
            if (!str || typeof str !== 'string') return '';
            const clean = str.split('?')[0].split('#')[0];
            const dot = clean.lastIndexOf('.');
            return dot === -1 ? '' : clean.substring(dot + 1).toLowerCase();
        },

        formatFileSize(bytes) {
            if (!bytes || bytes === 0) return null;
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let i = 0, size = bytes;
            while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
            return `${size.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
        },

        mimeToExtension(mime) {
            if (!mime) return 'bin';
            if (mime.includes('pdf')) return 'pdf';
            if (mime.includes('spreadsheetml') || mime.includes('ms-excel')) return 'xlsx';
            if (mime.includes('wordprocessingml') || mime.includes('msword')) return 'docx';
            if (mime.includes('presentationml') || mime.includes('ms-powerpoint')) return 'pptx';
            if (mime.includes('zip')) return 'zip';
            if (mime.includes('rar')) return 'rar';
            if (mime.includes('7z')) return '7z';
            if (mime.includes('gzip')) return 'gz';
            if (mime.includes('tar')) return 'tar';
            if (mime.includes('csv')) return 'csv';
            if (mime.includes('png')) return 'png';
            if (mime.includes('jpeg') || mime.includes('jpg')) return 'jpg';
            if (mime.includes('gif')) return 'gif';
            if (mime.includes('webp')) return 'webp';
            if (mime.includes('svg')) return 'svg';
            return 'bin';
        },

        extractFilenameFromDisposition(disposition) {
            if (!disposition) return null;
            const utf8 = disposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
            if (utf8) {
                try { return decodeURIComponent(utf8[1].trim().replace(/^["']|["']$/g, '')); }
                catch (e) { /* ignore */ }
            }
            const std = disposition.match(/filename\s*=\s*"?([^";]+)"?/i);
            return std ? std[1].trim() : null;
        },

        downloadJson(data, filename) {
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        },
    };

    /* ==========================================================
     * STORAGE LAYER
     * ========================================================== */
    const Storage = {
        get(key, fallback = null) {
            try {
                const raw = localStorage.getItem(key);
                return raw === null ? fallback : raw;
            } catch (e) { return fallback; }
        },
        set(key, value) {
            try { localStorage.setItem(key, value); }
            catch (e) { console.warn('[Storage] set failed', key, e); }
        },
        remove(key) {
            try { localStorage.removeItem(key); }
            catch (e) { /* ignore */ }
        },
        getJSON(key, fallback = null) {
            try {
                const raw = localStorage.getItem(key);
                return raw === null ? fallback : JSON.parse(raw);
            } catch (e) { return fallback; }
        },
        setJSON(key, value) {
            try { localStorage.setItem(key, JSON.stringify(value)); }
            catch (e) { console.warn('[Storage] setJSON failed', key, e); }
        },
    };

    /* ==========================================================
     * REACTIVE STATE (Vue-like proxy)
     * ========================================================== */
    const createReactive = (initial = {}) => {
        const watchers = [];
        return new Proxy(initial, {
            set(target, key, value) {
                const old = target[key];
                target[key] = value;
                if (old !== value) {
                    watchers.forEach(fn => {
                        try { fn(key, value, old); }
                        catch (e) { console.warn('[Reactive] watcher error', e); }
                    });
                }
                return true;
            },
            get(target, key) { return target[key]; },
        });
    };

    /* ==========================================================
     * HTTP CLIENT (centralized request wrapper)
     * ========================================================== */
    const Http = {
        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        getAuthToken() {
            // Prefer reactive state, fall back to localStorage
            return (typeof AuthStore !== 'undefined' && AuthStore.state.token) ||
                   Storage.get(STORAGE_KEYS.AUTH_TOKEN);
        },

        buildHeaders(extra = {}, includeAuth = true) {
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...extra,
            };

            const csrf = this.getCsrfToken();
            if (csrf) headers['X-CSRF-TOKEN'] = csrf;

            if (includeAuth) {
                const token = this.getAuthToken();
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`;
                } else {
                    console.warn('[Http] No auth token available for this request');
                }
            }

            return headers;
        },

        async request(url, options = {}) {
            const {
                method = 'GET',
                body = null,
                headers = {},
                includeAuth = true,
                accept = 'application/json',
            } = options;

            const finalHeaders = this.buildHeaders({ ...headers, 'Accept': accept }, includeAuth);

            const init = {
                method,
                headers: finalHeaders,
                credentials: 'include',
            };

            if (body !== null) {
                if (body instanceof FormData) {
                    init.body = body;
                    delete finalHeaders['Content-Type'];
                } else {
                    finalHeaders['Content-Type'] = 'application/json';
                    init.body = JSON.stringify(body);
                }
            }

            console.groupCollapsed(`[Http] → ${method} ${url}`);
            console.log('Headers:', finalHeaders);
            if (body && !(body instanceof FormData)) console.log('Body:', body);
            console.groupEnd();

            const response = await fetch(url, init);
            console.log(`[Http] ← ${response.status} ${method} ${url}`);

            return response;
        },
    };

    /* ==========================================================
     * STORES
     * ========================================================== */

    /* ---- PinStore ---- */
    const PinStore = {
        state: createReactive({ items: [], sidebarOpen: false }),

        get count() { return this.state.items.length; },
        get autoPins() { return this.state.items.filter(p => p.isAuto); },
        get manualPins() { return this.state.items.filter(p => !p.isAuto); },

        load() {
            this.state.items = Storage.getJSON(STORAGE_KEYS.PINNED, []);
        },

        persist() {
            Storage.setJSON(STORAGE_KEYS.PINNED, this.state.items);
        },

        initDefaults() {
            if (Storage.get(STORAGE_KEYS.PIN_INIT)) return;
            const existing = Storage.getJSON(STORAGE_KEYS.PINNED, []);
            const merged = [...DEFAULT_PINS.map(p => ({ ...p, createdAt: new Date().toISOString() }))];
            existing.forEach(p => {
                if (!merged.some(m => m.text === p.text)) merged.push(p);
            });
            this.state.items = merged;
            this.persist();
            Storage.set(STORAGE_KEYS.PIN_INIT, '1');
        },

        addManual(text) {
            if (!text || !text.trim()) return;
            const trimmed = text.trim();
            if (this.state.items.some(p => p.text === trimmed)) return;
            this.state.items.unshift({
                id: 'manual-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
                text: trimmed,
                createdAt: new Date().toISOString(),
                isManual: true,
            });
            this.persist();
        },

        addAuto(text) {
            if (!text || !text.trim()) return;
            const trimmed = text.trim();
            const filtered = this.state.items.filter(p => p.text !== trimmed);
            filtered.unshift({
                id: 'auto-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
                text: trimmed,
                createdAt: new Date().toISOString(),
                isAuto: true,
            });
            const manual = filtered.filter(p => !p.isAuto);
            const autos = filtered.filter(p => p.isAuto).slice(0, MAX_AUTO_PINS);
            this.state.items = [...autos, ...manual];
            this.persist();
        },

        remove(id) {
            this.state.items = this.state.items.filter(p => p.id !== id);
            this.persist();
        },

        clearAll() {
            if (!confirm('Remove all pinned prompts? (You can restore defaults later)')) return;
            this.state.items = [];
            this.persist();
        },

        restoreDefaults() {
            if (!confirm('Restore default pinned prompts?')) return;
            const merged = [...DEFAULT_PINS.map(p => ({ ...p, createdAt: new Date().toISOString() }))];
            this.state.items.forEach(p => {
                if (!merged.some(m => m.text === p.text)) merged.push(p);
            });
            this.state.items = merged;
            this.persist();
        },

        toggleSidebar() { this.state.sidebarOpen = !this.state.sidebarOpen; },
        closeSidebar() { this.state.sidebarOpen = false; },
    };

    /* ---- ThemeStore ---- */
    const ThemeStore = {
        state: createReactive({ current: 'light' }),

        load() {
            this.state.current = Storage.get(STORAGE_KEYS.THEME) || 'light';
            this.apply();
        },

        apply() {
            const root = document.documentElement;
            if (this.state.current === 'dark') {
                root.classList.add('dark');
                root.classList.remove('light');
            } else {
                root.classList.remove('dark');
                root.classList.add('light');
            }
            const icon = this.state.current === 'dark' ? '🌙' : '☀️';
            const knob1 = document.getElementById('theme-knob');
            const knob2 = document.getElementById('theme-knob-chat');
            if (knob1) knob1.textContent = icon;
            if (knob2) knob2.textContent = icon;
        },

        toggle() {
            this.state.current = this.state.current === 'dark' ? 'light' : 'dark';
            Storage.set(STORAGE_KEYS.THEME, this.state.current);
            this.apply();
        },
    };

    /* ---- AuthStore ---- */
    const AuthStore = {
        state: createReactive({
            token: null,
            user: null,
            loading: false,
        }),

        get isAuthenticated() {
            return Boolean(this.state.token && this.state.user);
        },

        get displayName() {
            if (!this.state.user) return '';
            return this.state.user.name || this.state.user.username || this.state.user.email || 'Logged in';
        },

        get displayRole() {
            return this.state.user?.role || '';
        },

        load() {
            this.state.token = Storage.get(STORAGE_KEYS.AUTH_TOKEN);
            this.state.user = Storage.getJSON(STORAGE_KEYS.AUTH_USER);
            if (this.state.token) {
                console.log('[Auth] Restored session for', this.displayName);
            }
        },

        setSession(token, user) {
            if (!token) {
                console.warn('[Auth] setSession called without token');
                return;
            }
            // Persist FIRST so any concurrent request picks it up
            Storage.set(STORAGE_KEYS.AUTH_TOKEN, token);
            Storage.setJSON(STORAGE_KEYS.AUTH_USER, user || {});

            // Then update reactive state
            this.state.token = token;
            this.state.user = user || {};

            console.log('[Auth] Session saved:', {
                token: String(token).substring(0, 20) + '…',
                user: this.state.user,
                isAuthenticated: this.isAuthenticated,
            });
        },

        clear() {
            this.state.token = null;
            this.state.user = null;
            Storage.remove(STORAGE_KEYS.AUTH_TOKEN);
            Storage.remove(STORAGE_KEYS.AUTH_USER);
            console.log('[Auth] Session cleared');
        },

        extractPayload(json) {
            let root = json;
            if (root && typeof root === 'object' && root.data && typeof root.data === 'object' && !Array.isArray(root.data)) {
                root = root.data;
            }

            const token =
                root?.token || root?.access_token || root?.api_token || root?.bearer_token ||
                json?.token || json?.access_token || null;

            let user =
                root?.user || root?.data?.user ||
                json?.user || json?.data?.user || null;

            if (user && typeof user === 'object' && user.data && typeof user.data === 'object' && !Array.isArray(user.data)) {
                const hasUserFields = user.id || user.email || user.name || user.username || user.role || user.roles;
                if (!hasUserFields) user = user.data;
            }

            let role = null;
            if (user) {
                if (typeof user.role === 'string') role = user.role;
                else if (user.role && typeof user.role === 'object' && typeof user.role.name === 'string') role = user.role.name;
                else if (Array.isArray(user.roles) && user.roles.length > 0)
                    role = typeof user.roles[0] === 'string' ? user.roles[0] : (user.roles[0]?.name ?? null);
            }

            return { token, user: user ? { ...user, role } : null };
        },

        async login(email, password) {
            this.state.loading = true;
            try {
                const response = await Http.request(API.LOGIN, {
                    method: 'POST',
                    body: { email, password },
                    includeAuth: false,
                });

                const rawText = await response.text();
                let json = {};
                try { json = rawText ? JSON.parse(rawText) : {}; }
                catch (e) { json = {}; }

                console.log('[Login] HTTP', response.status, 'Response:', json);

                if (!response.ok) {
                    throw new Error(json.message || json.error || `Login failed (HTTP ${response.status})`);
                }

                const { token, user } = this.extractPayload(json);
                if (!token) {
                    console.error('[Login] No token found. Full payload:', json);
                    throw new Error('Login succeeded but no token was returned by the server.');
                }

                // Normalize: strip any "Bearer " prefix the server might have included
                const cleanToken = String(token).replace(/^Bearer\s+/i, '').trim();

                this.setSession(cleanToken, user);
                return { ok: true };
            } catch (err) {
                console.error('[Login] Error:', err);
                return { ok: false, error: err.message || 'Login failed.' };
            } finally {
                this.state.loading = false;
            }
        },

        logout() {
            this.clear();
        },
    };

    /* ---- ChatStore ---- */
    const ChatStore = {
        state: createReactive({ messages: [], sending: false }),

        get lastMessage() {
            return this.state.messages[this.state.messages.length - 1];
        },

        async sendPrompt(prompt) {
            const token = Http.getAuthToken();
            if (!token) {
                console.warn('[Chat] Sending prompt WITHOUT auth token — expect 401');
            } else {
                console.log('[Chat] Sending with token:', token.substring(0, 20) + '…');
            }

            const response = await Http.request(API.PROMPT, {
                method: 'POST',
                body: { prompt },
                accept: 'application/json, application/pdf, application/octet-stream, */*',
                includeAuth: true,
            });

            const contentType = (response.headers.get('Content-Type') || '').toLowerCase();

            /* Binary response */
            if (
                contentType.includes('application/pdf') ||
                contentType.includes('application/vnd.ms-excel') ||
                contentType.includes('application/vnd.openxmlformats-officedocument') ||
                contentType.includes('application/msword') ||
                contentType.includes('application/zip') ||
                contentType.includes('application/x-zip') ||
                contentType.includes('application/octet-stream') ||
                contentType.includes('application/x-rar') ||
                contentType.includes('application/x-7z') ||
                contentType.includes('application/x-tar') ||
                contentType.includes('application/gzip') ||
                contentType.startsWith('image/')
            ) {
                let filename = Utils.extractFilenameFromDisposition(response.headers.get('Content-Disposition')) || 'download';
                if (filename === 'download') {
                    filename = `download.${Utils.mimeToExtension(contentType)}`;
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                const ext = Utils.getFileExtension(filename) || Utils.mimeToExtension(contentType);
                const info = BINARY_EXTENSIONS[ext] || { type: 'binary', icon: '📁', label: 'File', mime: contentType };
                return {
                    kind: 'binary',
                    status: response.status,
                    file: {
                        url: objectUrl, type: info.type, icon: info.icon,
                        label: info.label, mime: contentType,
                        name: filename, size: blob.size,
                    },
                };
            }

            /* JSON response */
            const data = await response.json();
            return { kind: 'json', status: response.status, data };
        },
    };

    /* ==========================================================
     * RESPONSE PARSER
     * ========================================================== */
    class ResponseParser {
    /**
     * Recursively scan an object/array for pagination meta and links.
     * Returns { meta, links } found at any depth.
     */
    static findPaginationData(node, depth = 0) {
        if (!node || typeof node !== 'object' || depth > 10) {
            return { meta: null, links: null };
        }

        let meta = null;
        let links = null;

        // Check if this node itself is a meta object
        if (!Array.isArray(node)) {
            const metaKeys = [
                'current_page', 'currentPage', 'page', 'page_number', 'pageNumber',
                'last_page', 'lastPage', 'total_pages', 'totalPages',
                'per_page', 'perPage', 'page_size', 'pageSize',
                'total', 'total_count', 'totalCount', 'count',
                'from', 'to', 'start', 'end',
                'has_more_pages', 'hasMorePages', 'has_more', 'hasMore',
                'has_next', 'hasNext', 'has_previous', 'hasPrevious',
                'next_page', 'nextPage', 'previous_page', 'previousPage',
                'path', 'first_page', 'firstPage', 'last_page_url'
            ];
            const linkKeys = ['first', 'last', 'prev', 'next'];

            const nodeKeys = Object.keys(node);
            const hasMetaKeys = nodeKeys.some(k => metaKeys.includes(k));
            const hasLinkKeys = nodeKeys.some(k => linkKeys.includes(k));

            if (hasMetaKeys) {
                meta = node;
            } else if (hasLinkKeys) {
                links = node;
            }
        }

        // If node has explicit `meta` or `links` properties, use those
        if (node.meta && typeof node.meta === 'object' && !meta) {
            meta = node.meta;
        }
        if (node.links && typeof node.links === 'object' && !links) {
            links = node.links;
        }

        // Recursively scan all properties
        const children = Array.isArray(node) ? node : Object.values(node);
        for (const child of children) {
            if (child && typeof child === 'object') {
                const found = this.findPaginationData(child, depth + 1);
                if (!meta && found.meta) meta = found.meta;
                if (!links && found.links) links = found.links;
                if (meta && links) break;
            }
        }

        return { meta, links };
    }

    /**
     * Extract the main content payload, plus any pagination meta/links
     * found anywhere in the response tree.
     */
    static extractPayloadAndMeta(response) {
        if (!response || typeof response !== 'object') {
            return { content: response, meta: null, links: null };
        }

        // First, find pagination data anywhere in the tree
        const { meta, links } = this.findPaginationData(response);

        // Now unwrap the content payload
        let node = response;
        let guard = 0;

        while (guard++ < 10) {
            if (!node || typeof node !== 'object' || Array.isArray(node)) break;

            // Handle Laravel-style { data: [...] }
            if (node.result !== undefined) { node = node.result; continue; }
            if (node.payload !== undefined) { node = node.payload; continue; }
            if (node.data !== undefined) { node = node.data; continue; }
            if (node.content !== undefined && node.content !== null) { node = node.content; continue; }
            if (node.items !== undefined) { node = node.items; continue; }

            break;
        }

        // Handle the [records, links, meta] tuple pattern
        if (Array.isArray(node) && node.length >= 1 && Array.isArray(node[0]) &&
            (node[0].length === 0 || typeof node[0][0] === 'object') &&
            node.slice(1).every(x => x === null || typeof x === 'object')) {
            const records = node[0];
            return { content: records, meta, links };
        }

        // If node is an object with data/items array
        if (node && typeof node === 'object' && !Array.isArray(node)) {
            if (Array.isArray(node.data)) {
                node = node.data;
            } else if (Array.isArray(node.items)) {
                node = node.items;
            }
        }

        return { content: node, meta, links };
    }
}

    /* ==========================================================
     * FILE DETECTOR
     * ========================================================== */
    class FileDetector {
        static detect(value) {
            if (!value) return null;

            if (typeof value === 'string') {
                if (!(value.startsWith('http://') || value.startsWith('https://') ||
                      value.startsWith('/') || value.startsWith('data:'))) return null;

                const ext = Utils.getFileExtension(value);
                if (BINARY_EXTENSIONS[ext]) {
                    const info = BINARY_EXTENSIONS[ext];
                    return {
                        url: value, type: info.type, icon: info.icon, label: info.label, mime: info.mime,
                        name: value.split('/').pop().split('?')[0] || `file.${ext}`, size: null,
                    };
                }
                if (IMAGE_EXT_REGEX.test(value)) {
                    return {
                        url: value, type: 'image', icon: '🖼️', label: 'Image', mime: 'image/*',
                        name: value.split('/').pop().split('?')[0] || 'image', size: null,
                    };
                }
                return null;
            }

            if (typeof value === 'object' && value !== null) {
                const fileFields = [
                    'file', 'file_url', 'url', 'download_url', 'download',
                    'image', 'image_url', 'thumbnail', 'attachment', 'attachment_url',
                    'document', 'document_url', 'archive', 'archive_url',
                    'zip', 'zip_url', 'pdf', 'pdf_url', 'excel', 'excel_url',
                ];

                for (const field of fileFields) {
                    const url = value[field];
                    if (url && typeof url === 'string' &&
                        (url.startsWith('http') || url.startsWith('/') || url.startsWith('data:'))) {
                        const ext = Utils.getFileExtension(url);
                        const name = value.name || value.filename || value.file_name ||
                                     value.original_name || url.split('/').pop().split('?')[0] || 'file';
                        const size = value.size || value.file_size || value.filesize || null;

                        if (IMAGE_EXT_REGEX.test(url)) {
                            return { url, type: 'image', icon: '🖼️', label: 'Image', mime: 'image/*', name, size };
                        }
                        if (BINARY_EXTENSIONS[ext]) {
                            const info = BINARY_EXTENSIONS[ext];
                            return {
                                url, type: info.type, icon: info.icon, label: info.label,
                                mime: value.mime_type || value.mime || info.mime, name, size,
                            };
                        }
                        return {
                            url, type: 'binary', icon: '📁', label: 'File',
                            mime: value.mime_type || value.mime || 'application/octet-stream', name, size,
                        };
                    }
                }

                if (value.data && typeof value.data === 'string' && value.data.startsWith('data:')) {
                    const match = value.data.match(/^data:([^;]+);/);
                    const mime = match ? match[1] : 'application/octet-stream';
                    return {
                        url: value.data, type: 'binary', icon: '💾', label: 'Binary Data',
                        mime, name: value.name || value.filename || 'file',
                        size: value.size || null, isBase64: true,
                    };
                }
            }
            return null;
        }
    }

    /* ==========================================================
     * VIEW: CHAT
     * ========================================================== */
    const ChatView = {
        els: {},

        mount() {
            this.els.container = document.getElementById('chat-container');
            this.els.input = document.getElementById('prompt-input');
            this.els.sendBtn = document.getElementById('send-btn');

            this.els.input.addEventListener('input', () => this.autoResize());
            this.els.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    Actions.sendPrompt();
                }
            });
            this.els.sendBtn.addEventListener('click', () => Actions.sendPrompt());
        },

        autoResize() {
            const el = this.els.input;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, TYPING_MAX_HEIGHT) + 'px';
        },

        scrollToBottom() {
            this.els.container.scrollTop = this.els.container.scrollHeight;
        },

        appendMessage({ content, sender, isError = false, isHtml = false, rawText = '' }) {
            const isUser = sender === 'user';
            const msg = document.createElement('div');
            msg.className = `flex items-start gap-3 md:gap-4 msg-wrapper fade-in ${isUser ? 'flex-row-reverse' : ''}`;

            let bubbleClass = isUser
                ? 'bg-indigo-600 text-white'
                : (isError
                    ? 'bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'
                    : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200');

            const avatar = isUser
                ? `<div class="w-9 h-9 rounded-xl bg-gradient-to-br from-slate-500 to-slate-700 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-md">U</div>`
                : `<div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-lg shadow-indigo-500/20">AI</div>`;

            const aiActions = !isUser ? this.buildAiActions() : '';
            const userActions = (isUser && rawText) ? this.buildUserActions(rawText) : '';

            const bubbleContent = isHtml ? content : `<p>${Utils.escapeHtml(content)}</p>`;

            msg.innerHTML = `
                ${avatar}
                <div class="flex flex-col max-w-3xl w-full ${isUser ? 'items-end' : 'items-start'}">
                    <div class="${bubbleClass} rounded-2xl p-4 md:p-5 w-full text-sm leading-relaxed overflow-hidden shadow-sm transition-colors">
                        ${bubbleContent}
                    </div>
                    ${aiActions}
                    ${userActions}
                </div>
            `;

            const bubble = msg.querySelector('.rounded-2xl');
            if (bubble) {
                bubble.dataset.rawContent = isHtml ? content : Utils.escapeHtml(content);
                bubble.dataset.isHtml = isHtml ? 'true' : 'false';
            }

            this.els.container.appendChild(msg);
            this.scrollToBottom();
        },

        buildAiActions() {
            return `
                <div class="msg-actions flex items-center gap-1 mt-2">
                    <button data-action="copy-response" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" title="Copy response">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                    <button data-action="export-response" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" title="Export JSON">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </button>
                </div>
            `;
        },

        buildUserActions(text) {
            const t = Utils.escapeForAttribute(text);
            return `
                <div class="msg-actions flex items-center gap-1 mt-1 justify-end">
                    <button data-action="copy-prompt" data-text="${t}" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" title="Copy prompt">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                    <button data-action="edit-prompt" data-text="${t}" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" title="Edit prompt">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    <button data-action="pin-prompt" data-text="${t}" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" title="Pin prompt">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                    </button>
                    <button data-action="resubmit-prompt" data-text="${t}" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" title="Re-submit">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
            `;
        },

        showLoading() {
            const id = 'loading-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            div.className = 'flex items-start gap-3 md:gap-4 fade-in';
            div.innerHTML = `
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-lg shadow-indigo-500/20">AI</div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-2 shadow-sm transition-colors">
                    <span class="typing-dot bg-indigo-500"></span><span class="typing-dot bg-indigo-500"></span><span class="typing-dot bg-indigo-500"></span>
                </div>
            `;
            this.els.container.appendChild(div);
            this.scrollToBottom();
            return id;
        },

        removeLoading(id) {
            document.getElementById(id)?.remove();
        },

        setSending(state) {
            this.els.sendBtn.disabled = state;
            ChatStore.state.sending = state;
        },
    };

    /* ==========================================================
     * RENDERERS
     * ========================================================== */
    const Renderers = {
        fileCard(file) {
            const sizeText = file.size ? Utils.formatFileSize(file.size) : null;
            const ext = Utils.getFileExtension(file.name || file.url) || 'bin';

            let iconClass = 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400';
            if (file.type === 'pdf') iconClass = 'bg-red-100 dark:bg-red-950/50 text-red-700 dark:text-red-300';
            else if (file.type === 'excel') iconClass = 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300';
            else if (file.type === 'word') iconClass = 'bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300';
            else if (file.type === 'zip') iconClass = 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300';

            const safeUrl = Utils.escapeHtml(file.url);
            const safeName = Utils.escapeHtml(file.name || 'file');
            const safeType = Utils.escapeHtml(file.type || 'binary');
            const safeLabel = Utils.escapeHtml(file.label || 'File');
            const safeMime = Utils.escapeHtml(file.mime || 'application/octet-stream');
            const canPreview = ['image', 'pdf', 'excel', 'word', 'zip'].includes(file.type);

            return `
                <div class="flex flex-wrap items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all my-2">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0 ${iconClass}">${file.icon || '📁'}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate" title="${safeName}">${safeName}</div>
                        <div class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-2 flex-wrap mt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50 text-[10px] font-semibold">${ext.toUpperCase()}</span>
                            <span>${safeLabel}</span>
                            ${sizeText ? `<span>·</span><span>${sizeText}</span>` : ''}
                        </div>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        ${canPreview ? `<button data-action="preview-file" data-url="${safeUrl}" data-name="${safeName}" data-type="${safeType}" data-mime="${safeMime}" class="text-xs px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors font-medium">👁 Preview</button>` : ''}
                        <a href="${safeUrl}" download="${safeName}" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-2 rounded-lg font-medium shadow-sm transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download
                        </a>
                    </div>
                </div>
            `;
        },

        inlineFileCard(file) {
            const safeUrl = Utils.escapeHtml(file.url);
            const safeName = Utils.escapeHtml(file.name || 'file');
            const safeType = Utils.escapeHtml(file.type || 'binary');
            const safeMime = Utils.escapeHtml(file.mime || 'application/octet-stream');
            const ext = Utils.getFileExtension(file.name || file.url) || 'bin';

            let iconClass = 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400';
            if (file.type === 'pdf') iconClass = 'bg-red-100 dark:bg-red-950/50 text-red-700 dark:text-red-300';
            else if (file.type === 'excel') iconClass = 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300';
            else if (file.type === 'word') iconClass = 'bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300';
            else if (file.type === 'zip') iconClass = 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300';

            const canPreview = ['image', 'pdf', 'excel', 'word', 'zip'].includes(file.type);

            return `
                <div class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 max-w-[240px]">
                    <div class="w-7 h-7 rounded-md flex items-center justify-center text-sm shrink-0 ${iconClass}">${file.icon || '📁'}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-medium text-slate-700 dark:text-slate-300 truncate" title="${safeName}">${safeName}</div>
                        <div class="text-[9px] text-slate-400 dark:text-slate-500">${ext.toUpperCase()}</div>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        ${canPreview ? `<button data-action="preview-file" data-url="${safeUrl}" data-name="${safeName}" data-type="${safeType}" data-mime="${safeMime}" class="p-1 rounded-md text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30" title="Preview"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>` : ''}
                        <a href="${safeUrl}" download="${safeName}" class="p-1 rounded-md text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30" title="Download"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                    </div>
                </div>
            `;
        },

        parseRecursive(item, depth = 0) {
            if (item === null || item === undefined) return '<span class="italic text-slate-400 dark:text-slate-500">N/A</span>';

            if (typeof item !== 'object') {
                if (typeof item === 'boolean') return `<span class="${item ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'} font-medium">${item ? '✓ true' : '✗ false'}</span>`;
                if (typeof item === 'number') return `<span class="text-amber-600 dark:text-amber-400 font-medium">${item.toLocaleString()}</span>`;
                return `<span>${Utils.escapeHtml(String(item))}</span>`;
            }

            if (Array.isArray(item)) {
                if (item.length === 0) return '<span class="italic text-slate-400 dark:text-slate-500">Empty list</span>';
                if (typeof item[0] === 'object' && item[0] !== null && !Array.isArray(item[0])) {
                    return this.table(item, depth);
                }
                let html = '<ul class="list-disc pl-5 space-y-1 my-2">';
                item.forEach(sub => { html += `<li class="text-sm">${this.parseRecursive(sub, depth + 1)}</li>`; });
                return html + '</ul>';
            }

            let html = '<div class="space-y-2 my-2 text-sm">';
            for (const key in item) {
                if (!Object.prototype.hasOwnProperty.call(item, key)) continue;
                const val = item[key];
                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                if (['status','success','exception','links','meta','current_page','last_page','per_page','from','to','path','first','prev','next'].includes(key)) continue;

                if (Array.isArray(val) && val.length > 0 && typeof val[0] === 'object' && val[0] !== null) {
                    html += `<div class="border-l-2 border-indigo-400 dark:border-indigo-600 pl-3 py-1">
                        <div class="inline-flex items-center gap-1 text-[10px] bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50 px-2 py-0.5 rounded-full font-semibold tracking-wide mb-1">📊 ${Utils.escapeHtml(formattedKey)} (${val.length} items)</div>
                        <div>${this.table(val, depth + 1)}</div>
                    </div>`;
                } else {
                    html += `<div class="border-l-2 border-indigo-400 dark:border-indigo-600 pl-3 py-0.5">
                        <strong class="text-slate-500 dark:text-slate-400">${Utils.escapeHtml(formattedKey)}:</strong>
                        <div class="mt-1">${this.parseRecursive(val, depth + 1)}</div>
                    </div>`;
                }
            }
            return html + '</div>';
        },

        table(arr, depth = 0) {
            if (!arr || arr.length === 0) return '<span class="italic text-slate-400 dark:text-slate-500">Empty list</span>';
            if (Array.isArray(arr[0])) return arr.map(sub => this.parseRecursive(sub, depth + 1)).join('');

            const keys = [...new Set(arr.flatMap(obj => Object.keys(obj)))];

            let html = `<div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm my-2" style="margin-left: ${depth > 0 ? '4px' : '0'};">
                <table class="w-full border-collapse text-xs"><thead><tr class="bg-slate-50 dark:bg-slate-800/50">`;

            keys.forEach(key => {
                const title = key.replace(/_/g, ' ');
                html += `<th class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 whitespace-nowrap border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/50">${Utils.escapeHtml(title)}</th>`;
            });
            html += '</tr></thead><tbody>';

            arr.forEach((row, idx) => {
                const bg = idx % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/50 dark:bg-slate-800/20';
                html += `<tr class="${bg} hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors">`;
                keys.forEach(key => {
                    const val = row[key];
                    if (val === undefined || val === null) {
                        html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top"><span class="italic text-slate-400 dark:text-slate-500">—</span></td>`;
                    } else if (typeof val === 'object' && !Array.isArray(val)) {
                        const file = FileDetector.detect(val);
                        if (file) html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top">${this.inlineFileCard(file)}</td>`;
                        else html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top">${this.parseRecursive(val, depth + 1)}</td>`;
                    } else if (Array.isArray(val) && val.length > 0 && typeof val[0] === 'object' && val[0] !== null) {
                        html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top"><div class="inline-flex items-center gap-1 text-[10px] bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50 px-2 py-0.5 rounded-full font-semibold tracking-wide mb-1">📊 ${val.length} rows</div>${this.table(val, depth + 1)}</td>`;
                    } else if (typeof val === 'boolean') {
                        html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top"><span class="${val ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'} font-medium">${val ? '✓ true' : '✗ false'}</span></td>`;
                    } else if (typeof val === 'number') {
                        html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top"><span class="text-amber-600 dark:text-amber-400 font-medium">${val.toLocaleString()}</span></td>`;
                    } else if (typeof val === 'string') {
                        const file = FileDetector.detect(val);
                        if (file) html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top">${this.inlineFileCard(file)}</td>`;
                        else if (val.startsWith('http://') || val.startsWith('https://'))
                            html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top"><a href="${Utils.escapeHtml(val)}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline truncate block max-w-[200px]" title="${Utils.escapeHtml(val)}">${Utils.escapeHtml(val.length > 40 ? val.substring(0, 40) + '…' : val)}</a></td>`;
                        else html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top">${Utils.escapeHtml(String(val))}</td>`;
                    } else {
                        html += `<td class="px-3 py-2 border-b border-slate-200 dark:border-slate-800 align-top">${Utils.escapeHtml(String(val))}</td>`;
                    }
                });
                html += '</tr>';
            });

            return html + '</tbody></table></div>';
        },

        pagination(meta) {
            if (!meta) return '';
          const current =
                meta.current_page ??
                meta.currentPage ??
                meta.page ??
                meta.page_number ??
                meta.pageNumber ??
                meta.pageno ??
                meta.pageNo ??
                meta.current ??
                1;

            const last =
                meta.last_page ??
                meta.lastPage ??
                meta.total_pages ??
                meta.totalPages ??
                meta.pages ??
                1;

            const from =
                meta.from ??
                meta.start ??
                meta.start_index ??
                meta.startIndex ??
                meta.offset ??
                0;

            const to =
                meta.to ??
                meta.end ??
                meta.end_index ??
                meta.endIndex ??
                0;

            const total =
                meta.total ??
                meta.total_count ??
                meta.totalCount ??
                meta.count ??
                meta.records_total ??
                meta.recordsTotal ??
                0;

            const perPage =
                meta.per_page ??
                meta.perPage ??
                meta.page_size ??
                meta.pageSize ??
                meta.limit ??
                meta.items_per_page ??
                meta.itemsPerPage ??
                meta.size ??
                10;

            const hasMore =
                meta.has_more_pages ??
                meta.hasMorePages ??
                meta.has_more ??
                meta.hasMore ??
                meta.has_next ??
                meta.hasNext ??
                meta.next_page !== null ??
                meta.nextPage !== null ??
                false;

            const hasPrevious =
                meta.has_previous_pages ??
                meta.hasPreviousPages ??
                meta.has_previous ??
                meta.hasPrevious ??
                meta.has_prev ??
                meta.hasPrev ??
                meta.prev_page !== null ??
                meta.previousPage !== null ??
                current > 1;

            const nextPage =
                meta.next_page ??
                meta.nextPage ??
                meta.next ??
                null;

            const previousPage =
                meta.previous_page ??
                meta.previousPage ??
                meta.prev_page ??
                meta.prevPage ??
                meta.previous ??
                null;

            const firstPage =
                meta.first_page ??
                meta.firstPage ??
                1;

            const lastPage =
                meta.last_page ??
                meta.lastPage ??
                meta.total_pages ??
                meta.totalPages ??
                1;

            let pages = [];
            if (last <= 7) {
                for (let i = 1; i <= last; i++) pages.push(i);
            } else {
                pages.push(1);
                if (current > 3) pages.push('...');
                for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) pages.push(i);
                if (current < last - 2) pages.push('...');
                pages.push(last);
            }

            let buttonsHtml = '';
            pages.forEach(p => {
                if (p === '...') buttonsHtml += `<span class="px-1 text-slate-400 dark:text-slate-600">…</span>`;
                else {
                    const active = p === current
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-700 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400';
                    buttonsHtml += `<button data-action="goto-page" data-page="${p}" class="min-w-[32px] h-8 px-2 rounded-lg border text-xs font-medium transition-colors ${active}">${p}</button>`;
                }
            });

            return `
                <div class="flex flex-wrap items-center justify-between gap-3 mt-3 p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-500 dark:text-slate-400 transition-colors">
                    <div>Showing <strong>${from}</strong>–<strong>${to}</strong> of <strong>${total}</strong> entries · Page <strong>${current}</strong> of <strong>${last}</strong> · <span class="text-slate-400 dark:text-slate-600">${perPage} per page</span></div>
                    <div class="flex items-center gap-1 flex-wrap">
                        <button data-action="goto-page" data-page="${current - 1}" class="min-w-[32px] h-8 px-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 disabled:opacity-40 disabled:cursor-not-allowed hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" ${current <= 1 ? 'disabled' : ''}>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        ${buttonsHtml}
                        <button data-action="goto-page" data-page="${current + 1}" class="min-w-[32px] h-8 px-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 disabled:opacity-40 disabled:cursor-not-allowed hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" ${current >= last ? 'disabled' : ''}>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
            `;
        },

        response(data, action) {
            let html = '';

            if (action) {
                html += `<div class="mb-3 text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Action: <code class="font-mono">${Utils.escapeHtml(action)}</code></div>`;
            }

            const msg = data.message || (data.result && data.result.message);
            if (msg) {
                html += `<div class="mb-3 text-xs font-medium px-3 py-2 rounded-lg flex items-center gap-2 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    ${Utils.escapeHtml(msg)}
                </div>`;
            }

            const { content, meta } = ResponseParser.extractPayloadAndMeta(data);
            const topFile = FileDetector.detect(content);

            if (topFile) {
                html += this.fileCard(topFile);
            } else if (Array.isArray(content)) {
                const allFiles = content.length > 0 && content.every(item => FileDetector.detect(item) !== null);
                if (allFiles) {
                    html += `<div class="space-y-2 my-2">`;
                    content.forEach(item => {
                        const f = FileDetector.detect(item);
                        if (f) html += this.fileCard(f);
                    });
                    html += `</div>`;
                } else {
                    html += this.parseRecursive(content);
                }
            } else if (content === undefined || content === null ||
                (typeof content === 'object' && Object.keys(content).length === 0 && !Array.isArray(content))) {
                html += `<div class="text-sm italic text-slate-400 dark:text-slate-500">No data returned.</div>`;
            } else {
                html += this.parseRecursive(content);
            }

            if (meta) html += this.pagination(meta);

            html += `
                <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-3">
                    <button data-action="toggle-raw" class="text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                        <span class="raw-json-label">Show raw JSON</span>
                    </button>
                    <button data-action="export-full" class="text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Export JSON
                    </button>
                </div>
                <div class="raw-json-container hidden mt-3">
                    <pre class="bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-[11px] leading-relaxed overflow-x-auto font-mono text-slate-700 dark:text-slate-300 whitespace-pre-wrap break-words">${this.syntaxHighlight(data)}</pre>
                </div>
            `;

            return html;
        },

        syntaxHighlight(obj) {
            let json = JSON.stringify(obj, null, 2);
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, (match) => {
                let cls = 'json-number';
                if (/^"/.test(match)) cls = /:$/.test(match) ? 'json-key' : 'json-string';
                else if (/true|false/.test(match)) cls = 'json-boolean';
                else if (/null/.test(match)) cls = 'json-null';
                return `<span class="${cls}">${match}</span>`;
            });
        },
    };

    /* ==========================================================
     * VIEW: PIN SIDEBAR
     * ========================================================== */
    const PinSidebar = {
        els: {},
        mount() {
            this.els.sidebar = document.getElementById('pin-sidebar');
            this.els.overlay = document.getElementById('pin-overlay');
            this.els.list = document.getElementById('pin-list');
            this.els.count = document.getElementById('pin-count');
            this.render();
        },

        render() {
            const pins = PinStore.state.items;
            if (this.els.count) this.els.count.textContent = pins.length;

            if (pins.length === 0) {
                this.els.list.innerHTML = `<div class="text-xs text-slate-400 dark:text-slate-600 text-center py-6 italic">No pinned prompts yet.<br>Pin a user message to see it here.</div>`;
                return;
            }

            this.els.list.innerHTML = pins.map(pin => {
                const autoBadge = pin.isAuto ? '<span class="text-[8px] px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 font-semibold uppercase tracking-wide shrink-0">auto</span>' : '';
                const defBadge = pin.isDefault ? '<span class="text-[8px] px-1.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 font-semibold uppercase tracking-wide shrink-0">default</span>' : '';
                const t = Utils.escapeForAttribute(pin.text);
                return `
                    <div class="pin-item group relative flex items-center gap-1.5 p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition-colors" data-action="send-pinned" data-text="${t}">
                        <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                        <span class="flex-1 text-xs text-slate-700 dark:text-slate-300 truncate" title="${Utils.escapeHtml(pin.text)}">${Utils.escapeHtml(pin.text)}</span>
                        ${autoBadge}${defBadge}
                        <button data-action="remove-pin" data-id="${pin.id}" data-stop="1" class="pin-actions p-0.5 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-slate-400 hover:text-red-500 dark:hover:text-red-400 transition-all" title="Remove pin">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                `;
            }).join('');
        },

        syncOpenState() {
            if (PinStore.state.sidebarOpen) {
                this.els.sidebar.classList.add('open');
                this.els.overlay.classList.remove('hidden');
            } else {
                this.els.sidebar.classList.remove('open');
                this.els.overlay.classList.add('hidden');
            }
        },
    };

    /* ==========================================================
     * VIEW: HEADER
     * ========================================================== */
    const HeaderView = {
        els: {},
        mount() {
            this.els.loginBtn = document.getElementById('login-btn');
            this.els.userInfo = document.getElementById('user-info');
            this.els.userName = document.getElementById('user-name');
            this.els.userRole = document.getElementById('user-role');
            this.render();
        },
        render() {
            if (!this.els.loginBtn || !this.els.userInfo) {
                console.warn('[Header] Missing elements');
                return;
            }
            if (AuthStore.isAuthenticated) {
                this.els.loginBtn.classList.add('hidden');
                this.els.userInfo.classList.remove('hidden');
                this.els.userInfo.classList.add('flex');
                this.els.userName.textContent = AuthStore.displayName;
                if (AuthStore.displayRole) {
                    this.els.userRole.textContent = AuthStore.displayRole;
                    this.els.userRole.classList.remove('hidden');
                } else {
                    this.els.userRole.textContent = '';
                    this.els.userRole.classList.add('hidden');
                }
                console.log('[Header] Logged in as', AuthStore.displayName);
            } else {
                this.els.loginBtn.classList.remove('hidden');
                this.els.userInfo.classList.add('hidden');
                this.els.userInfo.classList.remove('flex');
                console.log('[Header] Logged out');
            }
        },
    };

    /* ==========================================================
     * VIEW: LOGIN MODAL
     * ========================================================== */
    const LoginModal = {
        els: {},
        mount() {
            this.els.modal = document.getElementById('login-modal');
            this.els.form = document.getElementById('login-form');
            this.els.email = document.getElementById('login-email');
            this.els.password = document.getElementById('login-password');
            this.els.error = document.getElementById('login-error');
            this.els.submit = document.getElementById('login-submit-btn');

            this.els.form.addEventListener('submit', (e) => {
                e.preventDefault();
                Actions.submitLogin();
            });
        },
        open() {
            this.els.error.classList.add('hidden');
            this.els.modal.classList.remove('hidden');
            this.els.modal.classList.add('flex');
            setTimeout(() => this.els.email.focus(), 100);
        },
        close() {
            this.els.modal.classList.add('hidden');
            this.els.modal.classList.remove('flex');
            this.els.form.reset();
        },
        setError(msg) {
            this.els.error.textContent = msg;
            this.els.error.classList.remove('hidden');
        },
        setLoading(state) {
            this.els.submit.disabled = state;
            this.els.submit.textContent = state ? 'Logging in...' : 'Login';
        },
    };

    /* ==========================================================
     * VIEW: PREVIEW MODAL
     * ========================================================== */
    const PreviewModal = {
        state: { url: '', name: '', type: '', mime: '' },
        els: {},
        mount() {
            this.els.modal = document.getElementById('preview-modal');
            this.els.content = document.getElementById('preview-content');
            this.els.title = document.getElementById('preview-title');
        },
        open({ url, name, type, mime }) {
            this.state = { url, name, type, mime };
            this.els.title.textContent = name;

            let fullUrl = url;
            if (url.startsWith('/')) fullUrl = window.location.origin + url;

            this.els.content.innerHTML = this.buildContent(fullUrl, name, type, mime);
            this.els.modal.classList.remove('hidden');
            this.els.modal.classList.add('flex');
        },
        buildContent(fullUrl, name, type, mime) {
            if (type === 'image') {
                return `<div class="flex items-center justify-center min-h-[300px]"><img src="${Utils.escapeHtml(fullUrl)}" alt="${Utils.escapeHtml(name)}" class="max-h-[70vh] w-full object-contain rounded-lg border border-slate-200 dark:border-slate-700" onerror="this.outerHTML='<div class=&quot;p-12 text-center text-slate-400 dark:text-slate-500&quot;><div class=&quot;text-6xl mb-4&quot;>🖼️</div><p class=&quot;text-sm&quot;>Image could not be loaded.</p></div>'" /></div>`;
            }
            if (type === 'pdf') {
                return `<div class="h-[70vh] rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700"><iframe src="${Utils.escapeHtml(fullUrl)}" class="w-full h-full border-none" title="${Utils.escapeHtml(name)}"></iframe></div><p class="text-xs mt-2 text-center text-slate-400 dark:text-slate-500">If the PDF doesn't display, your browser may not support embedded previews. Use the download button.</p>`;
            }
            if (type === 'excel' || type === 'word') {
                const icon = type === 'excel' ? '📊' : '📝';
                const label = type === 'excel' ? 'Excel Spreadsheet' : 'Word Document';
                return `<div class="p-10 text-center text-slate-400 dark:text-slate-500"><div class="text-6xl mb-4">${icon}</div><p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1">${label} Preview</p><p class="text-xs mb-4">This file type cannot be previewed directly in the browser.</p><p class="text-xs">Use the <strong>Download</strong> button below to open it in your local application.</p></div>`;
            }
            if (type === 'zip') {
                return `<div class="p-10 text-center text-slate-400 dark:text-slate-500"><div class="text-6xl mb-4">📦</div><p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1">ZIP Archive</p><p class="text-xs mb-4">Archive contents cannot be previewed directly.</p><p class="text-xs">Download the file to extract and view its contents.</p></div>`;
            }
            if ((type === 'binary' || type === 'generic') && mime && (mime.startsWith('image/') || mime === 'application/pdf')) {
                return `<div class="h-[70vh] rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700"><iframe src="${Utils.escapeHtml(fullUrl)}" class="w-full h-full border-none"></iframe></div>`;
            }
            return `<div class="p-10 text-center text-slate-400 dark:text-slate-500"><div class="text-6xl mb-4">📄</div><p class="text-sm">Preview not available. Please download.</p></div>`;
        },
        close() {
            this.els.modal.classList.add('hidden');
            this.els.modal.classList.remove('flex');
            this.els.content.innerHTML = '';
            this.state = { url: '', name: '', type: '', mime: '' };
        },
        download() {
            if (!this.state.url) return;
            const a = document.createElement('a');
            a.href = this.state.url;
            a.download = this.state.name || 'download';
            a.click();
        },
    };

    /* ==========================================================
     * ACTIONS
     * ========================================================== */
    const Actions = {
        showPage(name) {
            document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
            const target = document.getElementById('page-' + name);
            if (target) {
                target.classList.remove('hidden');
                if (name === 'chat') target.classList.add('flex');
            }
            if (name === 'chat') setTimeout(() => document.getElementById('prompt-input')?.focus(), 100);
            PinStore.closeSidebar();
            PinSidebar.syncOpenState();
        },

        toggleTheme() { ThemeStore.toggle(); },

        togglePinSidebar() {
            PinStore.toggleSidebar();
            PinSidebar.syncOpenState();
        },

        closePinSidebar() {
            PinStore.closeSidebar();
            PinSidebar.syncOpenState();
        },

        sendPinnedPrompt(text) {
            const input = document.getElementById('prompt-input');
            input.value = text;
            ChatView.autoResize();
            input.focus();
            Actions.closePinSidebar();
        },

        removePin(id) {
            PinStore.remove(id);
            PinSidebar.render();
        },

        clearAllPins() {
            PinStore.clearAll();
            PinSidebar.render();
        },

        restoreDefaultPins() {
            PinStore.restoreDefaults();
            PinSidebar.render();
        },

        openLoginModal() { LoginModal.open(); },
        closeLoginModal() { LoginModal.close(); },

        async submitLogin() {
            const email = LoginModal.els.email.value.trim();
            const password = LoginModal.els.password.value;

            LoginModal.setLoading(true);
            const result = await AuthStore.login(email, password);
            LoginModal.setLoading(false);

            if (result.ok) {
                LoginModal.close();
                HeaderView.render();
            } else {
                LoginModal.setError(result.error);
            }
        },

        logout() {
            AuthStore.logout();
            HeaderView.render();
        },

        async sendPrompt() {
            const input = document.getElementById('prompt-input');
            const prompt = input.value.trim();
            if (!prompt) return;

            /* Warn user if unauthenticated — the API will 401 anyway */
            if (!AuthStore.isAuthenticated) {
                console.warn('[Chat] Attempting to send without authentication. Backend will return 401.');
            }

            ChatView.setSending(true);

            ChatView.appendMessage({
                content: prompt,
                sender: 'user',
                isHtml: true,
                rawText: prompt,
            });

            PinStore.addAuto(prompt);
            PinSidebar.render();

            input.value = '';
            ChatView.autoResize();

            /* Local JSON shortcut */
            const localJson = Utils.tryParseJson(prompt);
            if (localJson) {
                const loadingId = ChatView.showLoading();
                setTimeout(() => {
                    ChatView.removeLoading(loadingId);
                    ChatView.appendMessage({
                        content: Renderers.response(localJson, null),
                        sender: 'ai',
                        isHtml: true,
                    });
                    ChatView.setSending(false);
                    input.focus();
                }, 300);
                return;
            }

            const loadingId = ChatView.showLoading();

            try {
                const result = await ChatStore.sendPrompt(prompt);
                ChatView.removeLoading(loadingId);

                if (result.kind === 'binary') {
                    const { status, file } = result;
                    let html = '';
                    if (status >= 400) {
                        html += `<div class="mb-3 text-xs font-medium px-3 py-2 rounded-lg flex items-center gap-2 bg-red-50 dark:bg-red-950/50 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/50">Failed to generate file (HTTP ${status})</div>`;
                    } else {
                        html += `<div class="mb-3 text-xs font-medium px-3 py-2 rounded-lg flex items-center gap-2 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>File generated successfully</div>`;
                        html += Renderers.fileCard(file);
                    }
                    ChatView.appendMessage({ content: html, sender: 'ai', isHtml: true });
                    return;
                }

                /* JSON response */
                const { status, data } = result;

                if (status === 401) {
                    ChatView.appendMessage({
                        content: 'Your session has expired or you are not logged in. Please login again.',
                        sender: 'ai',
                        isError: true,
                    });
                    AuthStore.logout();
                    HeaderView.render();
                    Actions.openLoginModal();
                    return;
                }

                if (status === 403 || data.status === 'error') {
                    ChatView.appendMessage({
                        content: data.message || 'Unauthorized action.',
                        sender: 'ai',
                        isError: true,
                    });
                    return;
                }

                const isSuccess = data.status === 'success' ||
                                  data.success === true ||
                                  (data.result && data.result.success === true);

                if (isSuccess) {
                    ChatView.appendMessage({
                        content: Renderers.response(data, data.action),
                        sender: 'ai',
                        isHtml: true,
                    });
                } else {
                    ChatView.appendMessage({
                        content: data.message || 'No response generated.',
                        sender: 'ai',
                        isError: true,
                    });
                }
            } catch (error) {
                ChatView.removeLoading(loadingId);
                ChatView.appendMessage({
                    content: 'Network or server error occurred.',
                    sender: 'ai',
                    isError: true,
                });
                console.error('[Chat] Error:', error);
            } finally {
                ChatView.setSending(false);
                input.focus();
            }
        },

        resubmitPrompt(text) {
            const input = document.getElementById('prompt-input');
            input.value = text;
            ChatView.autoResize();
            input.focus();
        },

        pinPrompt(text) {
            PinStore.addManual(text);
            PinSidebar.render();
        },

        copyUserPrompt(text) {
            navigator.clipboard.writeText(text).catch(() => alert('Copy failed'));
        },

        copyResponse(btn) {
            const wrapper = btn.closest('.flex-col');
            const bubble = wrapper.querySelector('.rounded-2xl');
            const raw = bubble.dataset.rawContent || bubble.innerText;
            const isHtml = bubble.dataset.isHtml === 'true';
            let text = raw;
            if (isHtml) {
                const tmp = document.createElement('div');
                tmp.innerHTML = raw;
                text = tmp.innerText || tmp.textContent || '';
            }
            navigator.clipboard.writeText(text).then(() => {
                const orig = btn.innerHTML;
                btn.innerHTML = `<svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
                setTimeout(() => { btn.innerHTML = orig; }, 1500);
            }).catch(() => alert('Copy failed'));
        },

        exportResponse(btn) {
            const wrapper = btn.closest('.flex-col');
            const bubble = wrapper.querySelector('.rounded-2xl');
            const raw = bubble.dataset.rawContent || bubble.innerText;
            const isHtml = bubble.dataset.isHtml === 'true';
            let text = raw;
            if (isHtml) {
                const tmp = document.createElement('div');
                tmp.innerHTML = raw;
                text = tmp.innerText || tmp.textContent || '';
            }
            Utils.downloadJson(
                { exportedAt: new Date().toISOString(), content: text, isHtml, rawHtml: isHtml ? raw : null },
                `ai-response-${Date.now()}.json`
            );
        },

        toggleRawJson(btn) {
            const wrapper = btn.closest('.flex-col');
            const bubble = wrapper.querySelector('.rounded-2xl');
            const container = bubble.querySelector('.raw-json-container');
            const label = btn.querySelector('.raw-json-label');
            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                if (label) label.textContent = 'Hide raw JSON';
            } else {
                container.classList.add('hidden');
                if (label) label.textContent = 'Show raw JSON';
            }
        },

        exportFullResponse(btn) {
            const wrapper = btn.closest('.flex-col');
            const bubble = wrapper.querySelector('.rounded-2xl');
            const raw = bubble.dataset.rawJson;
            if (!raw) { alert('No raw JSON available'); return; }
            try { Utils.downloadJson(JSON.parse(raw), `ai-response-${Date.now()}.json`); }
            catch (e) { alert('Failed to parse JSON'); }
        },

        gotoPage(page) {
            const input = document.getElementById('prompt-input');
            input.value = `Show page ${page} of the previous data`;
            Actions.sendPrompt();
        },

        editPrompt(btn, originalText) {
            const wrapper = btn.closest('.flex-col');
            const bubble = wrapper.querySelector('.rounded-2xl');
            if (!bubble || bubble.dataset.editing === 'true') return;

            bubble.dataset.editing = 'true';
            const originalHtml = bubble.innerHTML;

            bubble.innerHTML = `
                <div class="flex flex-col gap-2">
                    <textarea class="w-full bg-white/20 dark:bg-slate-800/50 border border-white/30 dark:border-slate-600 rounded-lg px-3 py-2 text-sm text-white dark:text-slate-100 resize-none focus:outline-none focus:ring-2 focus:ring-white/50 dark:focus:ring-indigo-500/50" rows="3">${Utils.escapeHtml(originalText)}</textarea>
                    <div class="flex gap-2 justify-end">
                        <button class="save-edit text-xs px-3 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">Save</button>
                        <button class="cancel-edit text-xs px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white/80 transition-colors">Cancel</button>
                    </div>
                </div>
            `;

            const textarea = bubble.querySelector('textarea');
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);

            bubble.querySelector('.save-edit').addEventListener('click', () => {
                const newText = textarea.value.trim();
                if (newText && newText !== originalText) {
                    bubble.dataset.editing = 'false';
                    bubble.innerHTML = `<p>${Utils.escapeHtml(newText)}</p>`;
                    bubble.dataset.rawContent = Utils.escapeHtml(newText);

                    const t = Utils.escapeForAttribute(newText);
                    const editBtn = wrapper.querySelector('[data-action="edit-prompt"]');
                    if (editBtn) editBtn.dataset.text = t;
                    const pinBtn = wrapper.querySelector('[data-action="pin-prompt"]');
                    if (pinBtn) pinBtn.dataset.text = t;
                    const resubmitBtn = wrapper.querySelector('[data-action="resubmit-prompt"]');
                    if (resubmitBtn) resubmitBtn.dataset.text = t;
                    const copyBtn = wrapper.querySelector('[data-action="copy-prompt"]');
                    if (copyBtn) copyBtn.dataset.text = t;
                } else {
                    bubble.dataset.editing = 'false';
                    bubble.innerHTML = originalHtml;
                }
            });

            bubble.querySelector('.cancel-edit').addEventListener('click', () => {
                bubble.dataset.editing = 'false';
                bubble.innerHTML = originalHtml;
            });
        },

        previewFile(btn) {
            const { url, name, type, mime } = btn.dataset;
            PreviewModal.open({ url, name, type, mime });
        },

        closePreview() { PreviewModal.close(); },
        downloadPreview() { PreviewModal.download(); },
    };

    /* ==========================================================
     * GLOBAL EVENT ROUTER (delegation)
     * ========================================================== */
    const EventRouter = {
        mount() {
            document.body.addEventListener('click', (e) => {
                const target = e.target.closest('[data-action]');
                if (!target) return;
                const action = target.dataset.action;

                if (target.dataset.stop === '1') e.stopPropagation();

                switch (action) {
                    case 'show-page': Actions.showPage(target.dataset.page); break;
                    case 'toggle-theme': Actions.toggleTheme(); break;
                    case 'toggle-pin-sidebar': Actions.togglePinSidebar(); break;
                    case 'close-pin-sidebar': Actions.closePinSidebar(); break;
                    case 'open-login': Actions.openLoginModal(); break;
                    case 'close-login': Actions.closeLoginModal(); break;
                    case 'logout': Actions.logout(); break;
                    case 'send-prompt': Actions.sendPrompt(); break;
                    case 'send-pinned': Actions.sendPinnedPrompt(target.dataset.text); break;
                    case 'remove-pin': Actions.removePin(target.dataset.id); break;
                    case 'clear-all-pins': Actions.clearAllPins(); break;
                    case 'restore-default-pins': Actions.restoreDefaultPins(); break;
                    case 'copy-response': Actions.copyResponse(target); break;
                    case 'export-response': Actions.exportResponse(target); break;
                    case 'toggle-raw': Actions.toggleRawJson(target); break;
                    case 'export-full': Actions.exportFullResponse(target); break;
                    case 'goto-page': Actions.gotoPage(Number(target.dataset.page)); break;
                    case 'copy-prompt': Actions.copyUserPrompt(target.dataset.text); break;
                    case 'pin-prompt': Actions.pinPrompt(target.dataset.text); break;
                    case 'resubmit-prompt': Actions.resubmitPrompt(target.dataset.text); break;
                    case 'edit-prompt': Actions.editPrompt(target, target.dataset.text); break;
                    case 'preview-file': Actions.previewFile(target); break;
                    case 'close-preview': Actions.closePreview(); break;
                    case 'download-preview': Actions.downloadPreview(); break;
                    default: console.warn('[EventRouter] Unknown action:', action);
                }
            });
        },
    };

    /* ==========================================================
     * GLOBAL 401 GUARD
     * Wraps window.fetch so any 401 clears the session and
     * re-renders the header.
     * ========================================================== */
    const installAuthGuard = () => {
        const originalFetch = window.fetch;
        window.fetch = async function (...args) {
            const res = await originalFetch.apply(this, args);
            if (res.status === 401) {
                const url = typeof args[0] === 'string' ? args[0] : args[0]?.url;
                const isLoginRequest = url && url.includes('/login');
                if (!isLoginRequest && AuthStore.isAuthenticated) {
                    console.warn('[AuthGuard] 401 received from', url, '— clearing session');
                    AuthStore.clear();
                    HeaderView.render();
                }
            }
            return res;
        };
    };

    /* ==========================================================
     * APP LIFECYCLE
     * ========================================================== */
    const App = {
        mounted: false,

        init() {
            if (this.mounted) return;

            /* Load persisted state */
            ThemeStore.load();
            AuthStore.load();
            PinStore.load();
            PinStore.initDefaults();

            /* Mount views */
            ChatView.mount();
            PinSidebar.mount();
            HeaderView.mount();
            LoginModal.mount();
            PreviewModal.mount();
            EventRouter.mount();
            installAuthGuard();

            /* Re-sync header on focus / bfcache restore */
            window.addEventListener('pageshow', () => HeaderView.render());
            window.addEventListener('focus', () => HeaderView.render());

            /* OS theme listener (only if user hasn't chosen) */
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!Storage.get(STORAGE_KEYS.THEME)) {
                    ThemeStore.state.current = e.matches ? 'dark' : 'light';
                    ThemeStore.apply();
                }
            });

            ChatView.autoResize();

            /* Debug helper */
            window.__AIBridgeDebug = () => {
                console.table({
                    'Auth token (first 20)': (AuthStore.state.token || '').substring(0, 20),
                    'Token in localStorage': (Storage.get(STORAGE_KEYS.AUTH_TOKEN) || '').substring(0, 20),
                    'Is authenticated': AuthStore.isAuthenticated,
                    'User name': AuthStore.displayName,
                    'User role': AuthStore.displayRole,
                    'Pin count': PinStore.count,
                });
                console.log('Full user object:', AuthStore.state.user);
                return 'Debug info printed above.';
            };

            this.mounted = true;
            console.log('[App] Mounted. Auth:', AuthStore.isAuthenticated, '| Pins:', PinStore.count);
        },
    };

    /* ==========================================================
     * BOOTSTRAP
     * ========================================================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => App.init());
    } else {
        App.init();
    }
})();
</script>
</body>
</html>