<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bridge · Pro</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* ====== THEME VARIABLES ====== */
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --border-strong: #cbd5e1;
            --bubble-ai-bg: #ffffff;
            --bubble-ai-border: #e2e8f0;
            --bubble-user-bg: #4f46e5;
            --bubble-user-text: #ffffff;
            --accent: #4f46e5;
            --accent-hover: #4338ca;
            --accent-light: #eef2ff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --header-bg: rgba(255, 255, 255, 0.85);
            --footer-bg: rgba(255, 255, 255, 0.9);
            --glass-blur: blur(16px);
            --table-header-bg: #f8fafc;
            --table-header-text: #334155;
            --table-row-hover: #f1f5f9;
            --table-border: #e2e8f0;
            --table-nested-bg: #f8fafc;
            --modal-overlay: rgba(15, 23, 42, 0.4);
            --action-hover-bg: #f1f5f9;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --input-focus-ring: rgba(79, 70, 229, 0.15);
            --toggle-bg: #e2e8f0;
            --toggle-active: #4f46e5;
            --badge-bg: #eef2ff;
            --badge-text: #4338ca;
            --badge-border: #c7d2fe;
            --file-bg: #f8fafc;
            --file-border: #e2e8f0;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --success-text: #065f46;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
            --error-text: #991b1b;
            --pagination-bg: #f8fafc;
            --pagination-border: #e2e8f0;
            --pagination-text: #475569;
            --pagination-active-bg: #4f46e5;
            --pagination-active-text: #ffffff;
        }

        html.dark {
            --bg-primary: #0b1120;
            --bg-secondary: #0f172a;
            --bg-tertiary: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border-color: #1e293b;
            --border-strong: #334155;
            --bubble-ai-bg: #1e293b;
            --bubble-ai-border: #334155;
            --bubble-user-bg: #4f46e5;
            --bubble-user-text: #ffffff;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --accent-light: #1e1b4b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.4), 0 2px 4px -2px rgb(0 0 0 / 0.4);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.5), 0 4px 6px -4px rgb(0 0 0 / 0.5);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.6), 0 8px 10px -6px rgb(0 0 0 / 0.6);
            --header-bg: rgba(15, 23, 42, 0.85);
            --footer-bg: rgba(15, 23, 42, 0.9);
            --table-header-bg: #0f172a;
            --table-header-text: #cbd5e1;
            --table-row-hover: #1e293b;
            --table-border: #1e293b;
            --table-nested-bg: #0b1120;
            --modal-overlay: rgba(0, 0, 0, 0.7);
            --action-hover-bg: #1e293b;
            --input-bg: #0f172a;
            --input-border: #334155;
            --input-focus-ring: rgba(99, 102, 241, 0.2);
            --toggle-bg: #334155;
            --toggle-active: #6366f1;
            --badge-bg: #1e1b4b;
            --badge-text: #a5b4fc;
            --badge-border: #312e81;
            --file-bg: #0b1120;
            --file-border: #1e293b;
            --success-bg: #022c22;
            --success-border: #065f46;
            --success-text: #6ee7b7;
            --error-bg: #450a0a;
            --error-border: #7f1d1d;
            --error-text: #fca5a5;
            --pagination-bg: #0f172a;
            --pagination-border: #1e293b;
            --pagination-text: #94a3b8;
            --pagination-active-bg: #6366f1;
            --pagination-active-text: #ffffff;
        }

        * { transition: background-color 0.2s ease, border-color 0.2s ease, color 0.15s ease; }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.4); border-radius: 8px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.7); }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .page { display: none; min-height: 100vh; flex-direction: column; }
        .page.active { display: flex; }

        .glass-panel { background: var(--header-bg); backdrop-filter: var(--glass-blur); -webkit-backdrop-filter: var(--glass-blur); }
        .glass-footer { background: var(--footer-bg); backdrop-filter: var(--glass-blur); -webkit-backdrop-filter: var(--glass-blur); }

        .bubble-ai { background: var(--bubble-ai-bg); border: 1px solid var(--bubble-ai-border); color: var(--text-primary); }
        .bubble-user { background: var(--bubble-user-bg); color: var(--bubble-user-text); }
        .bubble-error { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error-text); }

        /* ====== TABLE STYLES ====== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--table-border);
            box-shadow: var(--shadow-sm);
            margin: 12px 0;
            background: var(--bg-secondary);
        }
        .table-responsive table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .table-responsive thead th {
            background: var(--table-header-bg);
            color: var(--table-header-text);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.68rem;
            padding: 11px 14px;
            white-space: nowrap;
            border-bottom: 1px solid var(--table-border);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .table-responsive tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--table-border);
            color: var(--text-primary);
            vertical-align: top;
            font-size: 0.82rem;
            line-height: 1.5;
        }
        .table-responsive tbody tr:last-child td { border-bottom: none; }
        .table-responsive tbody tr:hover td { background: var(--table-row-hover); }
        .table-responsive tbody tr:nth-child(even) { background: var(--table-nested-bg); }

        .table-responsive .table-responsive {
            margin: 6px 0;
            border-radius: 10px;
            box-shadow: none;
            border: 1px solid var(--table-border);
            background: var(--bg-tertiary);
        }
        .table-responsive .table-responsive thead th { background: var(--bg-tertiary); font-size: 0.62rem; padding: 7px 10px; }
        .table-responsive .table-responsive tbody td { padding: 7px 10px; font-size: 0.78rem; }

        .nested-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.6rem;
            background: var(--badge-bg);
            color: var(--badge-text);
            border: 1px solid var(--badge-border);
            padding: 1px 8px;
            border-radius: 99px;
            font-weight: 600;
            letter-spacing: 0.03em;
            margin-bottom: 4px;
        }

        /* ====== PAGINATION ====== */
        .pagination-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            padding: 12px 16px;
            background: var(--pagination-bg);
            border: 1px solid var(--pagination-border);
            border-radius: 12px;
            font-size: 0.78rem;
            color: var(--pagination-text);
        }
        .pagination-info { font-weight: 500; }
        .pagination-controls { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        .pagination-btn {
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 8px;
            border: 1px solid var(--pagination-border);
            background: var(--bg-secondary);
            color: var(--pagination-text);
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }
        .pagination-btn:hover:not(:disabled) { background: var(--action-hover-bg); border-color: var(--accent); color: var(--accent); }
        .pagination-btn.active { background: var(--pagination-active-bg); color: var(--pagination-active-text); border-color: var(--pagination-active-bg); }
        .pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .pagination-ellipsis { padding: 0 4px; color: var(--text-muted); }

        /* ====== MESSAGE ACTIONS ====== */
        .message-actions { opacity: 0; transition: opacity 0.15s ease; }
        .message-wrapper:hover .message-actions { opacity: 1; }

        .copy-feedback { background: var(--success-bg) !important; border-color: var(--success-border) !important; color: var(--success-text) !important; }

        /* ====== TOGGLE SWITCH ====== */
        .theme-toggle {
            position: relative;
            width: 52px;
            height: 28px;
            background: var(--toggle-bg);
            border-radius: 99px;
            cursor: pointer;
            border: none;
            padding: 0;
            transition: background 0.3s ease;
            flex-shrink: 0;
        }
        .theme-toggle .toggle-knob {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        html.dark .theme-toggle { background: var(--toggle-active); }
        html.dark .theme-toggle .toggle-knob { transform: translateX(24px); background: #0f172a; }

        /* ====== INPUT ====== */
        .chat-input { background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary); transition: border-color 0.2s, box-shadow 0.2s; }
        .chat-input:focus-within { border-color: var(--accent); box-shadow: 0 0 0 3px var(--input-focus-ring); }
        .chat-input textarea { background: transparent; color: var(--text-primary); }
        .chat-input textarea::placeholder { color: var(--text-muted); }

        /* ====== MODAL ====== */
        .modal-overlay { background: var(--modal-overlay); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
        .modal-panel { background: var(--bg-secondary); border: 1px solid var(--border-color); box-shadow: var(--shadow-xl); }

        .file-card { background: var(--file-bg); border: 1px solid var(--file-border); }

        .typing-dot { width: 7px; height: 7px; background: var(--accent); border-radius: 50%; display: inline-block; animation: typingBounce 1.2s infinite ease-in-out; }
        .typing-dot:nth-child(2) { animation-delay: 0.15s; }
        .typing-dot:nth-child(3) { animation-delay: 0.3s; }
        @keyframes typingBounce { 0%, 60%, 100% { transform: translateY(0); opacity: 0.4; } 30% { transform: translateY(-6px); opacity: 1; } }

        .fade-in { animation: fadeIn 0.35s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        .hero-gradient { background: linear-gradient(135deg, var(--accent-light) 0%, var(--bg-primary) 50%, var(--bg-tertiary) 100%); }
        html.dark .hero-gradient { background: linear-gradient(135deg, #1e1b4b 0%, #0b1120 50%, #0f172a 100%); }

        @media (max-width: 640px) {
            .table-responsive table { font-size: 0.72rem; }
            .table-responsive thead th { padding: 8px 10px; font-size: 0.6rem; }
            .table-responsive tbody td { padding: 8px 10px; font-size: 0.72rem; }
            .table-responsive .table-responsive table { font-size: 0.65rem; }
            .pagination-container { flex-direction: column; align-items: flex-start; }
        }

        .btn-primary { background: var(--accent); color: white; transition: background 0.2s, transform 0.1s, box-shadow 0.2s; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3); }
        .btn-primary:hover { background: var(--accent-hover); box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .btn-ghost { color: var(--text-secondary); transition: background 0.15s, color 0.15s; }
        .btn-ghost:hover { background: var(--action-hover-bg); color: var(--text-primary); }

        .stat-card { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 14px; box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

        .json-pre {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.72rem;
            line-height: 1.6;
            overflow-x: auto;
            color: var(--text-primary);
            white-space: pre-wrap;
            word-break: break-word;
        }
        .json-pre .json-key { color: #7c3aed; }
        html.dark .json-pre .json-key { color: #a78bfa; }
        .json-pre .json-string { color: #059669; }
        html.dark .json-pre .json-string { color: #6ee7b7; }
        .json-pre .json-number { color: #d97706; }
        html.dark .json-pre .json-number { color: #fbbf24; }
        .json-pre .json-boolean { color: #dc2626; }
        html.dark .json-pre .json-boolean { color: #f87171; }
        .json-pre .json-null { color: var(--text-muted); }

        /* ====== BINARY FILE PREVIEW CARD ====== */
        .binary-file-card {
            background: var(--file-bg);
            border: 1px solid var(--file-border);
            border-radius: 14px;
            padding: 16px;
            margin: 10px 0;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .binary-file-card:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }
        .binary-file-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0; box-shadow: var(--shadow-sm);
        }
        .binary-file-icon.pdf { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; }
        .binary-file-icon.excel { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #166534; }
        .binary-file-icon.word { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; }
        .binary-file-icon.zip { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
        .binary-file-icon.generic { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #3730a3; }
        html.dark .binary-file-icon.pdf { background: linear-gradient(135deg, #450a0a, #7f1d1d); color: #fca5a5; }
        html.dark .binary-file-icon.excel { background: linear-gradient(135deg, #052e16, #065f46); color: #6ee7b7; }
        html.dark .binary-file-icon.word { background: linear-gradient(135deg, #172554, #1e3a8a); color: #93c5fd; }
        html.dark .binary-file-icon.zip { background: linear-gradient(135deg, #451a03, #78350f); color: #fcd34d; }
        html.dark .binary-file-icon.generic { background: linear-gradient(135deg, #1e1b4b, #312e81); color: #a5b4fc; }
        .binary-file-info { flex: 1; min-width: 0; }
        .binary-file-name { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .binary-file-meta { font-size: 0.7rem; color: var(--text-muted); margin-top: 2px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .binary-file-meta .size-badge {
            background: var(--badge-bg); color: var(--badge-text);
            border: 1px solid var(--badge-border);
            padding: 1px 8px; border-radius: 99px;
            font-size: 0.6rem; font-weight: 600;
        }
        .binary-file-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        /* ============================================================
           CLEAN SCROLLBAR — chat container only
           ============================================================ */
        #chat-container::-webkit-scrollbar { width: 6px; height: 6px; }
        #chat-container::-webkit-scrollbar-track { background: transparent; border: none; }
        #chat-container::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.35);
            border-radius: 99px; border: none;
        }
        #chat-container::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.65); }
        #chat-container::-webkit-scrollbar-corner { background: transparent; }
        #chat-container { scrollbar-width: thin; scrollbar-color: rgba(100, 116, 139, 0.35) transparent; }
        html.dark #chat-container::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.4); }
        html.dark #chat-container::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.7); }
        html.dark #chat-container { scrollbar-color: rgba(148, 163, 184, 0.4) transparent; }
    </style>
</head>
<body>

<!-- ============================================================ -->
<!-- PAGE 1: HOME / LANDING                                        -->
<!-- ============================================================ -->
<div id="page-home" class="page active">
    <header class="glass-panel border-b sticky top-0 z-20" style="border-color: var(--border-color);">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-2.5 h-2.5 rounded-full shadow-[0_0_8px_#34d399] bg-emerald-400"></div>
                <h1 class="text-base md:text-lg font-semibold tracking-tight" style="color: var(--text-primary);">
                    AI Bridge <span style="color: var(--accent);" class="font-light">Assistant</span>
                </h1>
                <span class="hidden sm:inline text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider font-medium"
                      style="background: var(--badge-bg); color: var(--badge-text); border: 1px solid var(--badge-border);">Pro</span>
            </div>
            <div class="flex items-center gap-3">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
                    <span class="toggle-knob" id="theme-knob">☀️</span>
                </button>
                <button onclick="showPage('chat')" class="btn-primary text-xs font-semibold px-4 py-2 rounded-xl hidden sm:inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Open Chat
                </button>
            </div>
        </div>
    </header>

    <main class="flex-1 hero-gradient flex items-center justify-center p-6">
        <div class="max-w-3xl w-full text-center space-y-8 fade-in">
            <div class="flex justify-center">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center shadow-2xl"
                     style="background: linear-gradient(135deg, var(--accent), #7c3aed); box-shadow: 0 20px 40px -10px rgba(79,70,229,0.4);">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-4xl md:text-5xl font-bold tracking-tight" style="color: var(--text-primary);">
                    AI Bridge <span style="color: var(--accent);">Assistant</span>
                </h2>
                <p class="text-lg md:text-xl max-w-xl mx-auto leading-relaxed" style="color: var(--text-secondary);">
                    Intelligent JSON mapping with N-th level recursive table rendering, pagination awareness, and full export capabilities.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mx-auto pt-4">
                <div class="stat-card p-5 text-left">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3" style="background: var(--accent-light); color: var(--accent);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    </div>
                    <h3 class="font-semibold text-sm mb-1" style="color: var(--text-primary);">Recursive Tables</h3>
                    <p class="text-xs leading-relaxed" style="color: var(--text-muted);">N-th level JSON rendering with nested tables and inline pagination.</p>
                </div>
                <div class="stat-card p-5 text-left">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3" style="background: var(--accent-light); color: var(--accent);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-semibold text-sm mb-1" style="color: var(--text-primary);">Binary Files</h3>
                    <p class="text-xs leading-relaxed" style="color: var(--text-muted);">PDF, Excel, Word, ZIP — preview and download directly.</p>
                </div>
                <div class="stat-card p-5 text-left">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3" style="background: var(--accent-light); color: var(--accent);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    </div>
                    <h3 class="font-semibold text-sm mb-1" style="color: var(--text-primary);">Smart Pagination</h3>
                    <p class="text-xs leading-relaxed" style="color: var(--text-muted);">Detects meta pagination and renders full navigation controls.</p>
                </div>
            </div>

            <div class="pt-2">
                <button onclick="showPage('chat')" class="btn-primary text-sm font-semibold px-8 py-3.5 rounded-2xl inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Start Conversation
                </button>
            </div>
        </div>
    </main>

    <footer class="py-4 text-center text-[11px] tracking-wider" style="color: var(--text-muted);">
        AI BRIDGE · POWERED BY LARAVEL & GEMINI
    </footer>
</div>

<!-- ============================================================ -->
<!-- PAGE 2: CHAT                                                  -->
<!-- ============================================================ -->
<div id="page-chat" class="page" style="height: 100vh; overflow: hidden;">
    <header class="glass-panel border-b sticky top-0 z-20 flex-shrink-0" style="border-color: var(--border-color);">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="showPage('home')" class="btn-ghost p-2 rounded-xl" title="Back to home">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </button>
                <div class="flex items-center space-x-2.5">
                    <div class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_6px_#34d399]"></div>
                    <h1 class="text-sm md:text-base font-semibold tracking-tight" style="color: var(--text-primary);">
                        AI Bridge Assistant
                    </h1>
                </div>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme" style="width:44px;height:24px;">
                    <span class="toggle-knob" id="theme-knob-chat" style="width:18px;height:18px;font-size:9px;">☀️</span>
                </button>
                <button id="login-btn" onclick="openLoginModal()" class="btn-primary text-xs font-semibold px-3.5 py-1.5 rounded-xl">
                    Login
                </button>
                <div id="user-info" class="hidden items-center gap-2">
                    <div class="text-right leading-tight hidden sm:block">
                        <div id="user-name" class="text-xs font-semibold" style="color: var(--text-primary);"></div>
                        <div id="user-role" class="text-[10px] uppercase tracking-wider" style="color: var(--text-muted);"></div>
                    </div>
                    <button onclick="logout()" title="Logout" class="btn-ghost p-2 rounded-xl">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main id="chat-container" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-5 max-w-5xl w-full mx-auto" style="scroll-behavior: smooth;">
        <div class="flex items-start gap-3 md:gap-4 message-wrapper fade-in">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 text-white"
                 style="background: linear-gradient(135deg, var(--accent), #7c3aed); box-shadow: 0 2px 16px -4px rgba(79,70,229,0.4);">AI</div>
            <div class="bubble-ai rounded-2xl p-5 max-w-3xl text-sm leading-relaxed">
                <p class="font-semibold mb-1" style="color: var(--text-primary);">Hello, I'm your AI Bridge assistant.</p>
                <p style="color: var(--text-secondary);">
                  Ask me to execute tasks, manage records, or generate reports. I handle tables, files, images, and more with full recursive data support.</p>
            </div>
        </div>
    </main>

    <footer class="glass-footer border-t p-3 md:p-4 flex-shrink-0" style="border-color: var(--border-color);">
        <div class="max-w-5xl mx-auto">
            <div class="chat-input rounded-2xl p-1.5 flex items-end gap-2">
                <textarea id="prompt-input" rows="1" placeholder="Type a message or command…"
                    class="flex-1 bg-transparent px-3.5 py-3 resize-none text-sm focus:outline-none min-h-[50px] max-h-40"></textarea>
                <button id="send-btn" type="button"
                    class="btn-primary p-3 rounded-xl flex items-center justify-center flex-shrink-0 mb-0.5 mr-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </div>
            <p class="text-center text-[10px] tracking-wider mt-2" style="color: var(--text-muted);">AI BRIDGE · POWERED BY LARAVEL & GEMINI</p>
        </div>
    </footer>
</div>

<!-- ============================================================ -->
<!-- LOGIN MODAL                                                    -->
<!-- ============================================================ -->
<div id="login-modal" class="hidden fixed inset-0 modal-overlay items-center justify-center z-50 p-4">
    <div class="modal-panel rounded-2xl w-full max-w-sm p-6 fade-in">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold" style="color: var(--text-primary);">Login</h2>
            <button onclick="closeLoginModal()" class="btn-ghost p-1.5 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="login-error" class="hidden mb-3 text-xs rounded-xl px-3 py-2.5"
             style="background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error-text);"></div>
        <form id="login-form" onsubmit="return submitLogin(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Email</label>
                <input id="login-email" type="email" required autocomplete="username"
                    class="chat-input w-full rounded-xl px-3.5 py-2.5 text-sm focus:outline-none" />
            </div>
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Password</label>
                <input id="login-password" type="password" required autocomplete="current-password"
                    class="chat-input w-full rounded-xl px-3.5 py-2.5 text-sm focus:outline-none" />
            </div>
            <button id="login-submit-btn" type="submit" class="btn-primary w-full text-sm font-semibold py-2.5 rounded-xl flex items-center justify-center">
                Login
            </button>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- PREVIEW MODAL                                                  -->
<!-- ============================================================ -->
<div id="preview-modal" class="hidden fixed inset-0 modal-overlay items-center justify-center z-[60] p-4">
    <div class="modal-panel rounded-2xl w-full max-w-3xl max-h-[90vh] flex flex-col fade-in">
        <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--border-color);">
            <h3 id="preview-title" class="text-sm font-semibold truncate" style="color: var(--text-primary);">Preview</h3>
            <button onclick="closePreviewModal()" class="btn-ghost p-1.5 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="preview-content" class="p-4 overflow-auto flex-1"></div>
        <div class="p-4 border-t flex justify-end gap-3" style="border-color: var(--border-color);">
            <button onclick="closePreviewModal()" class="btn-ghost text-xs font-medium px-4 py-2 rounded-lg" style="border: 1px solid var(--border-strong);">Close</button>
            <button id="preview-download-btn" onclick="downloadPreviewFile()" class="btn-primary text-xs font-semibold px-4 py-2 rounded-lg">Download</button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SCRIPTS                                                        -->
<!-- ============================================================ -->
<script>
/* ============================================================
   THEME
   ============================================================ */
function getStoredTheme() { return localStorage.getItem('ai_bridge_theme') || 'light'; }
function applyTheme(theme) {
    if (theme === 'dark') document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
    const knob1 = document.getElementById('theme-knob');
    const knob2 = document.getElementById('theme-knob-chat');
    if (knob1) knob1.textContent = theme === 'dark' ? '🌙' : '☀️';
    if (knob2) knob2.textContent = theme === 'dark' ? '🌙' : '☀️';
    localStorage.setItem('ai_bridge_theme', theme);
}
function toggleTheme() {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
}
applyTheme(getStoredTheme());

/* ============================================================
   PAGE NAV
   ============================================================ */
function showPage(name) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById('page-' + name).classList.add('active');
    if (name === 'chat') setTimeout(() => document.getElementById('prompt-input')?.focus(), 100);
}

/* ============================================================
   DOM
   ============================================================ */
const chatContainer = document.getElementById('chat-container');
const promptInput = document.getElementById('prompt-input');
const sendBtn = document.getElementById('send-btn');

/* ============================================================
   AUTH
   ============================================================ */
const AUTH_TOKEN_KEY = 'ai_bridge_auth_token';
const AUTH_USER_KEY = 'ai_bridge_auth_user';
function getAuthToken() { return localStorage.getItem(AUTH_TOKEN_KEY); }
function getAuthUser() { try { return JSON.parse(localStorage.getItem(AUTH_USER_KEY) || 'null'); } catch (e) { return null; } }
function setAuthSession(token, user) {
    localStorage.setItem(AUTH_TOKEN_KEY, token);
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user || {}));
    updateAuthUI();
}
function clearAuthSession() {
    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(AUTH_USER_KEY);
    updateAuthUI();
}
function logout() { clearAuthSession(); }

function extractAuthPayload(json) {
    const root = json?.data ?? json ?? {};
    const token = root.token || root.access_token || json?.token || json?.access_token || null;
    const user = root.user || json?.user || null;
    let role = null;
    if (user) {
        if (typeof user.role === 'string') role = user.role;
        else if (Array.isArray(user.roles) && user.roles.length > 0)
            role = typeof user.roles[0] === 'string' ? user.roles[0] : (user.roles[0]?.name ?? null);
    }
    return { token, user: user ? { ...user, role } : null };
}

function updateAuthUI() {
    const token = getAuthToken();
    const user = getAuthUser();
    const loginBtn = document.getElementById('login-btn');
    const userInfo = document.getElementById('user-info');
    if (token && user) {
        loginBtn.classList.add('hidden');
        userInfo.classList.remove('hidden'); userInfo.classList.add('flex');
        document.getElementById('user-name').textContent = user.name || user.email || 'Logged in';
        const roleEl = document.getElementById('user-role');
        if (user.role) { roleEl.textContent = user.role; roleEl.classList.remove('hidden'); }
        else { roleEl.textContent = ''; roleEl.classList.add('hidden'); }
    } else {
        loginBtn.classList.remove('hidden');
        userInfo.classList.add('hidden'); userInfo.classList.remove('flex');
    }
}

function openLoginModal() {
    document.getElementById('login-error').classList.add('hidden');
    const m = document.getElementById('login-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
    setTimeout(() => document.getElementById('login-email').focus(), 100);
}
function closeLoginModal() {
    const m = document.getElementById('login-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.getElementById('login-form').reset();
}
async function submitLogin(event) {
    event.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const errorBox = document.getElementById('login-error');
    const submitBtn = document.getElementById('login-submit-btn');
    errorBox.classList.add('hidden');
    submitBtn.disabled = true; submitBtn.textContent = 'Logging in...';
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/api/v1/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ email, password })
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(json.message || 'Invalid email or password.');
        const { token, user } = extractAuthPayload(json);
        if (!token) throw new Error('Login succeeded but no token returned.');
        setAuthSession(token, user);
        closeLoginModal();
    } catch (err) {
        errorBox.textContent = err.message || 'Login failed.';
        errorBox.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false; submitBtn.textContent = 'Login';
    }
    return false;
}

/* ============================================================
   APPEND MESSAGE
   ============================================================ */
function appendMessage(content, sender, isError = false, isHtml = false, rawUserText = '') {
    const isUser = sender === 'user';
    const messageDiv = document.createElement('div');
    messageDiv.className = `flex items-start gap-3 md:gap-4 ${isUser ? 'flex-row-reverse' : ''} message-wrapper fade-in`;

    let bubbleClass = isUser ? 'bubble-user' : 'bubble-ai';
    if (isError) bubbleClass = 'bubble-error';

    const avatar = isUser
        ? `<div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 shadow-md text-white" style="background: linear-gradient(135deg, #475569, #334155);">U</div>`
        : `<div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 shadow-lg text-white" style="background: linear-gradient(135deg, var(--accent), #7c3aed);">AI</div>`;

    let actionsHtml = '';
    if (!isUser) {
        actionsHtml = `
            <div class="message-actions flex items-center gap-1 mt-2">
                <button onclick="copyMessageContent(this)" class="btn-ghost p-1.5 rounded-lg" title="Copy response">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </button>
                <button onclick="exportMessageJson(this)" class="btn-ghost p-1.5 rounded-lg" title="Export JSON">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </button>
            </div>
        `;
    }

    let userActions = '';
    if (isUser && rawUserText) {
        userActions = `
            <div class="message-actions flex items-center gap-1 mt-1 justify-end">
                <button onclick="resubmitPrompt('${escapeForAttribute(rawUserText)}')" class="btn-ghost p-1.5 rounded-lg" title="Re-submit">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
            </div>
        `;
    }

    const bubbleContent = isHtml ? content : `<p>${escapeHtml(content)}</p>`;

    messageDiv.innerHTML = `
        ${avatar}
        <div class="flex flex-col max-w-3xl w-full ${isUser ? 'items-end' : 'items-start'}">
            <div class="${bubbleClass} rounded-2xl p-4 md:p-5 w-full text-sm leading-relaxed overflow-hidden">
                ${bubbleContent}
            </div>
            ${actionsHtml}
            ${userActions}
        </div>
    `;

    const bubbleEl = messageDiv.querySelector('.rounded-2xl');
    if (bubbleEl) {
        bubbleEl.dataset.rawContent = isHtml ? content : escapeHtml(content);
        bubbleEl.dataset.isHtml = isHtml ? 'true' : 'false';
    }
    chatContainer.appendChild(messageDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function escapeForAttribute(str) {
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, '\\n');
}

/* ============================================================
   COPY / EXPORT
   ============================================================ */
function copyMessageContent(btn) {
    const wrapper = btn.closest('.message-wrapper');
    const bubble = wrapper.querySelector('.rounded-2xl');
    const raw = bubble.dataset.rawContent || bubble.innerText;
    const isHtml = bubble.dataset.isHtml === 'true';
    let textToCopy = raw;
    if (isHtml) {
        const temp = document.createElement('div');
        temp.innerHTML = raw;
        textToCopy = temp.innerText || temp.textContent || '';
    }
    navigator.clipboard.writeText(textToCopy).then(() => {
        btn.classList.add('copy-feedback');
        const orig = btn.innerHTML;
        btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
        setTimeout(() => { btn.classList.remove('copy-feedback'); btn.innerHTML = orig; }, 1500);
    }).catch(() => alert('Copy failed'));
}

function exportMessageJson(btn) {
    const wrapper = btn.closest('.message-wrapper');
    const bubble = wrapper.querySelector('.rounded-2xl');
    const raw = bubble.dataset.rawContent || bubble.innerText;
    const isHtml = bubble.dataset.isHtml === 'true';
    let textContent = raw;
    if (isHtml) {
        const temp = document.createElement('div');
        temp.innerHTML = raw;
        textContent = temp.innerText || temp.textContent || '';
    }
    downloadJson({ exportedAt: new Date().toISOString(), content: textContent, isHtml, rawHtml: isHtml ? raw : null }, `ai-response-${Date.now()}.json`);
}

function downloadJson(data, filename) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
}

function resubmitPrompt(text) {
    promptInput.value = text;
    promptInput.style.height = 'auto';
    promptInput.style.height = Math.min(promptInput.scrollHeight, 160) + 'px';
    promptInput.focus();
}

/* ============================================================
   LOADING
   ============================================================ */
function showLoadingIndicator() {
    const id = 'loading-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'flex items-start gap-3 md:gap-4 message-wrapper';
    div.innerHTML = `
        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 shadow-lg text-white" style="background: linear-gradient(135deg, var(--accent), #7c3aed);">AI</div>
        <div class="bubble-ai rounded-2xl p-4 flex items-center gap-2">
            <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
        </div>
    `;
    chatContainer.appendChild(div);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    return id;
}
function removeLoadingIndicator(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

/* ============================================================
   JSON HELPERS
   ============================================================ */
function tryParseJson(str) {
    try {
        const trimmed = str.trim();
        if ((trimmed.startsWith('{') && trimmed.endsWith('}')) || (trimmed.startsWith('[') && trimmed.endsWith(']'))) {
            return JSON.parse(trimmed);
        }
    } catch (e) {}
    return null;
}

/* ============================================================
   UNIVERSAL PAYLOAD & META EXTRACTOR
   ------------------------------------------------------------
   Auto-detects and handles all known response formats:

   1) Wrapped standard:   { status, message, data: [...] }
   2) Wrapped + paginator: { status, message, data: { data: [...], links, meta } }
   3) Action envelope:    { status, action, result: { success, data: [...] } }
   4) Action + paginator: { status, action, result: { success, data: { data: [...], links, meta } } }
   5) Nested array:       { status, action, result: { data: [ [...records...], {...links...}, {...meta...} ] } }
   6) Plain array:        [ {...}, {...} ]
   7) Single record:      { id, name, ... }

   Returns: { content, meta, links }
   ============================================================ */
function extractPayloadAndMeta(response) {
    if (!response || typeof response !== 'object') {
        return { content: response, meta: null, links: null };
    }

    let node = response;
    let meta = null;
    let links = null;

    // Descend through common envelope keys (result, data, payload)
    // We do this iteratively until we find an array or a leaf object.
    let guard = 0;
    while (guard++ < 6) {
        if (!node || typeof node !== 'object' || Array.isArray(node)) break;

        // If current node has explicit meta/links, capture them (highest priority)
        if (node.meta && typeof node.meta === 'object' && meta === null) meta = node.meta;
        if (node.links && typeof node.links === 'object' && links === null) links = node.links;

        // Descend priority: result → data → payload → content → items → records
        if (node.result !== undefined) { node = node.result; continue; }
        if (node.payload !== undefined) { node = node.payload; continue; }
        if (node.data !== undefined) { node = node.data; continue; }
        if (node.content !== undefined && node.content !== null) { node = node.content; continue; }
        break;
    }

    // ============================================================
    // FORMAT 5: Nested array  [ [records...], {links}, {meta} ]
    // Detected when node is an array whose first element is an array
    // of objects and the trailing elements are objects.
    // ============================================================
    if (
        Array.isArray(node) &&
        node.length >= 1 &&
        Array.isArray(node[0]) &&
        node[0].length >= 0 &&
        (node[0].length === 0 || typeof node[0][0] === 'object') &&
        node.slice(1).every(x => x === null || typeof x === 'object')
    ) {
        const records = node[0];
        const trailing = node.slice(1);
        // Convention: [records, links, meta] — but be tolerant of order
        for (const t of trailing) {
            if (!t || typeof t !== 'object' || Array.isArray(t)) continue;
            // Heuristic: an object with 'current_page' or 'total' or 'last_page' is pagination meta
            if ('current_page' in t || 'last_page' in t || 'per_page' in t || 'total' in t) {
                if (meta === null) meta = t;
            }
            // An object with 'first'/'last'/'prev'/'next' is links
            else if ('first' in t || 'last' in t || 'prev' in t || 'next' in t) {
                if (links === null) links = t;
            }
            // Fallback: fill remaining slot
            else if (meta === null) meta = t;
            else if (links === null) links = t;
        }
        return { content: records, meta, links };
    }

    // ============================================================
    // FORMAT 1,2,3,4 (post-descent): node is now the actual payload
    // It may still be wrapped in { data: [...], links, meta }
    // ============================================================
    if (node && typeof node === 'object' && !Array.isArray(node)) {
        if (Array.isArray(node.data)) {
            if (node.meta && meta === null) meta = node.meta;
            if (node.links && links === null) links = node.links;
            node = node.data;
        } else if (Array.isArray(node.items)) {
            if (node.meta && meta === null) meta = node.meta;
            if (node.links && links === null) links = node.links;
            node = node.items;
        }
    }

    return { content: node, meta, links };
}

/* ============================================================
   BINARY FILE DETECTION
   ============================================================ */
const BINARY_EXTENSIONS = {
    pdf: { type: 'pdf', icon: '📄', label: 'PDF Document', mime: 'application/pdf' },
    doc: { type: 'word', icon: '📝', label: 'Word Document', mime: 'application/msword' },
    docx: { type: 'word', icon: '📝', label: 'Word Document', mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
    xls: { type: 'excel', icon: '📊', label: 'Excel Spreadsheet', mime: 'application/vnd.ms-excel' },
    xlsx: { type: 'excel', icon: '📊', label: 'Excel Spreadsheet', mime: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
    csv: { type: 'excel', icon: '📊', label: 'CSV File', mime: 'text/csv' },
    zip: { type: 'zip', icon: '📦', label: 'ZIP Archive', mime: 'application/zip' },
    rar: { type: 'zip', icon: '📦', label: 'RAR Archive', mime: 'application/x-rar-compressed' },
    '7z': { type: 'zip', icon: '📦', label: '7-Zip Archive', mime: 'application/x-7z-compressed' },
    tar: { type: 'zip', icon: '📦', label: 'TAR Archive', mime: 'application/x-tar' },
    gz: { type: 'zip', icon: '📦', label: 'GZIP Archive', mime: 'application/gzip' },
    ppt: { type: 'generic', icon: '📽️', label: 'PowerPoint', mime: 'application/vnd.ms-powerpoint' },
    pptx: { type: 'generic', icon: '📽️', label: 'PowerPoint', mime: 'application/vnd.openxmlformats-officedocument.presentationml.presentation' },
    txt: { type: 'generic', icon: '📃', label: 'Text File', mime: 'text/plain' },
    bin: { type: 'generic', icon: '💾', label: 'Binary File', mime: 'application/octet-stream' },
    exe: { type: 'generic', icon: '⚙️', label: 'Executable', mime: 'application/x-msdownload' },
    dmg: { type: 'generic', icon: '💿', label: 'Disk Image', mime: 'application/x-apple-diskimage' },
    iso: { type: 'generic', icon: '💿', label: 'ISO Image', mime: 'application/x-iso9660-image' }
};

function getFileExtension(str) {
    if (!str || typeof str !== 'string') return '';
    const clean = str.split('?')[0].split('#')[0];
    const lastDot = clean.lastIndexOf('.');
    if (lastDot === -1) return '';
    return clean.substring(lastDot + 1).toLowerCase();
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return null;
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    let size = bytes;
    while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
    return `${size.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
}

function detectFileResource(value) {
    if (!value) return null;

    if (typeof value === 'string') {
        if (!(value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/') || value.startsWith('data:'))) return null;
        const ext = getFileExtension(value);
        if (BINARY_EXTENSIONS[ext]) {
            const info = BINARY_EXTENSIONS[ext];
            return {
                url: value, type: info.type, icon: info.icon, label: info.label, mime: info.mime,
                name: value.split('/').pop().split('?')[0] || `file.${ext}`, size: null
            };
        }
        if (/\.(jpg|jpeg|png|gif|webp|svg|bmp|ico)$/i.test(value)) {
            return {
                url: value, type: 'image', icon: '🖼️', label: 'Image', mime: 'image/*',
                name: value.split('/').pop().split('?')[0] || 'image', size: null
            };
        }
        return null;
    }

    if (typeof value === 'object' && value !== null) {
        const fileFields = [
            'file', 'file_url', 'url', 'download_url', 'download',
            'image', 'image_url', 'thumbnail', 'attachment', 'attachment_url',
            'document', 'document_url', 'archive', 'archive_url',
            'zip', 'zip_url', 'pdf', 'pdf_url', 'excel', 'excel_url'
        ];

        for (const field of fileFields) {
            if (value[field] && typeof value[field] === 'string' &&
                (value[field].startsWith('http') || value[field].startsWith('/') || value[field].startsWith('data:'))) {
                const url = value[field];
                const ext = getFileExtension(url);
                const name = value.name || value.filename || value.file_name ||
                             value.original_name || url.split('/').pop().split('?')[0] || 'file';
                const size = value.size || value.file_size || value.filesize || null;

                if (/\.(jpg|jpeg|png|gif|webp|svg|bmp|ico)$/i.test(url)) {
                    return { url, type: 'image', icon: '🖼️', label: 'Image', mime: 'image/*', name, size };
                }
                if (BINARY_EXTENSIONS[ext]) {
                    const info = BINARY_EXTENSIONS[ext];
                    return {
                        url, type: info.type, icon: info.icon, label: info.label,
                        mime: value.mime_type || value.mime || info.mime, name, size
                    };
                }
                return {
                    url, type: 'binary', icon: '📁', label: 'File',
                    mime: value.mime_type || value.mime || 'application/octet-stream', name, size
                };
            }
        }

        if (value.data && typeof value.data === 'string' && value.data.startsWith('data:')) {
            const match = value.data.match(/^data:([^;]+);/);
            const mime = match ? match[1] : 'application/octet-stream';
            return {
                url: value.data, type: 'binary', icon: '💾', label: 'Binary Data',
                mime, name: value.name || value.filename || 'file', size: value.size || null, isBase64: true
            };
        }
    }

    return null;
}

function renderFileCard(file) {
    const sizeText = file.size ? formatFileSize(file.size) : null;
    const ext = getFileExtension(file.name || file.url) || 'bin';

    let iconClass = 'generic';
    if (file.type === 'pdf') iconClass = 'pdf';
    else if (file.type === 'excel') iconClass = 'excel';
    else if (file.type === 'word') iconClass = 'word';
    else if (file.type === 'zip') iconClass = 'zip';

    const safeUrl = escapeHtml(file.url);
    const safeName = escapeHtml(file.name || 'file');
    const safeType = escapeHtml(file.type || 'binary');
    const safeLabel = escapeHtml(file.label || 'File');
    const safeMime = escapeHtml(file.mime || 'application/octet-stream');

    const canPreview = ['image', 'pdf', 'excel', 'word', 'zip'].includes(file.type);

    return `
        <div class="binary-file-card">
            <div class="binary-file-icon ${iconClass}">${file.icon || '📁'}</div>
            <div class="binary-file-info">
                <div class="binary-file-name" title="${safeName}">${safeName}</div>
                <div class="binary-file-meta">
                    <span class="size-badge">${ext.toUpperCase()}</span>
                    <span>${safeLabel}</span>
                    ${sizeText ? `<span>·</span><span>${sizeText}</span>` : ''}
                </div>
            </div>
            <div class="binary-file-actions">
                ${canPreview ? `
                    <button onclick="previewFileResource('${safeUrl}', '${safeName}', '${safeType}', '${safeMime}')"
                        class="text-xs px-3 py-2 rounded-lg border transition-colors font-medium"
                        style="background: var(--bg-tertiary); color: var(--text-primary); border-color: var(--border-color);">
                        👁 Preview
                    </button>
                ` : ''}
                <a href="${safeUrl}" download="${safeName}"
                    class="btn-primary text-xs px-3 py-2 rounded-lg font-medium inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download
                </a>
            </div>
        </div>
    `;
}

function renderInlineFileCard(file) {
    const safeUrl = escapeHtml(file.url);
    const safeName = escapeHtml(file.name || 'file');
    const safeType = escapeHtml(file.type || 'binary');
    const safeMime = escapeHtml(file.mime || 'application/octet-stream');
    const ext = getFileExtension(file.name || file.url) || 'bin';

    let iconClass = 'generic';
    if (file.type === 'pdf') iconClass = 'pdf';
    else if (file.type === 'excel') iconClass = 'excel';
    else if (file.type === 'word') iconClass = 'word';
    else if (file.type === 'zip') iconClass = 'zip';

    const canPreview = ['image', 'pdf', 'excel', 'word', 'zip'].includes(file.type);

    return `
        <div class="flex items-center gap-2 p-1.5 rounded-lg"
             style="background: var(--bg-tertiary); border: 1px solid var(--border-color); max-width: 240px;">
            <div class="binary-file-icon ${iconClass}" style="width: 28px; height: 28px; font-size: 14px; border-radius: 8px; flex-shrink: 0;">
                ${file.icon || '📁'}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[11px] font-medium truncate" style="color: var(--text-primary);" title="${safeName}">${safeName}</div>
                <div class="text-[9px]" style="color: var(--text-muted);">${ext.toUpperCase()}</div>
            </div>
            <div class="flex gap-1 flex-shrink-0">
                ${canPreview ? `
                    <button onclick="previewFileResource('${safeUrl}', '${safeName}', '${safeType}', '${safeMime}')"
                        class="p-1 rounded-md" style="color: var(--accent);" title="Preview">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>
                ` : ''}
                <a href="${safeUrl}" download="${safeName}" class="p-1 rounded-md" style="color: var(--accent);" title="Download">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </a>
            </div>
        </div>
    `;
}

/* ============================================================
   RENDER RESPONSE
   ============================================================ */
function renderResponse(data, action) {
    let htmlContent = '';

    if (action) {
        htmlContent += `<div class="mb-3 text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg"
            style="background: var(--badge-bg); color: var(--badge-text); border: 1px solid var(--badge-border);">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            Action: <code class="font-mono">${escapeHtml(action)}</code></div>`;
    }

    // Prefer message from nested result if present
    const msg = data.message || (data.result && data.result.message);
    if (msg) {
        htmlContent += `<div class="mb-3 text-xs font-medium px-3 py-2 rounded-lg flex items-center gap-2"
            style="background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border);">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            ${escapeHtml(msg)}
        </div>`;
    }

    const { content, meta } = extractPayloadAndMeta(data);

    const topLevelFile = detectFileResource(content);

    if (topLevelFile) {
        htmlContent += renderFileCard(topLevelFile);
    } else if (Array.isArray(content)) {
        const allFiles = content.length > 0 && content.every(item => detectFileResource(item) !== null);
        if (allFiles) {
            htmlContent += `<div class="space-y-2 my-2">`;
            content.forEach(item => {
                const f = detectFileResource(item);
                if (f) htmlContent += renderFileCard(f);
            });
            htmlContent += `</div>`;
        } else {
            htmlContent += parseRecursive(content);
        }
    } else if (content === undefined || content === null || (typeof content === 'object' && Object.keys(content).length === 0 && !Array.isArray(content))) {
        htmlContent += `<div class="text-sm italic" style="color: var(--text-muted);">No data returned.</div>`;
    } else {
        htmlContent += parseRecursive(content);
    }

    if (meta) {
        htmlContent += renderPagination(meta);
    }

    htmlContent += `
        <div class="mt-4 pt-3 border-t flex flex-wrap items-center gap-3" style="border-color: var(--border-color);">
            <button onclick="toggleRawJson(this)" class="text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-colors"
                style="background: var(--bg-tertiary); color: var(--text-secondary); border: 1px solid var(--border-color);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                <span class="raw-json-label">Show raw JSON</span>
            </button>
            <button onclick="exportFullResponse(this)" class="text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-colors"
                style="background: var(--bg-tertiary); color: var(--accent); border: 1px solid var(--border-color);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export JSON
            </button>
        </div>
        <div class="raw-json-container hidden mt-3">
            <pre class="json-pre">${syntaxHighlightJson(data)}</pre>
        </div>
    `;

    appendMessage(htmlContent, 'ai', false, true);

    const bubbles = chatContainer.querySelectorAll('.message-wrapper');
    const lastBubble = bubbles[bubbles.length - 1]?.querySelector('.rounded-2xl');
    if (lastBubble) {
        lastBubble.dataset.rawJson = JSON.stringify(data);
    }
}

function toggleRawJson(btn) {
    const wrapper = btn.closest('.message-wrapper');
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
}

function exportFullResponse(btn) {
    const wrapper = btn.closest('.message-wrapper');
    const bubble = wrapper.querySelector('.rounded-2xl');
    const raw = bubble.dataset.rawJson;
    if (!raw) { alert('No raw JSON available'); return; }
    try {
        const data = JSON.parse(raw);
        downloadJson(data, `ai-response-${Date.now()}.json`);
    } catch (e) {
        alert('Failed to parse JSON');
    }
}

/* ============================================================
   PAGINATION
   ============================================================ */
function renderPagination(meta) {
    if (!meta) return '';
    const current = meta.current_page || 1;
    const last = meta.last_page || 1;
    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;
    const perPage = meta.per_page || 10;

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
        if (p === '...') {
            buttonsHtml += `<span class="pagination-ellipsis">…</span>`;
        } else {
            const active = p === current ? 'active' : '';
            buttonsHtml += `<button class="pagination-btn ${active}" onclick="goToPage(${p})">${p}</button>`;
        }
    });

    return `
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <strong>${from}</strong>–<strong>${to}</strong> of <strong>${total}</strong> entries
                · Page <strong>${current}</strong> of <strong>${last}</strong>
                · <span style="color: var(--text-muted);">${perPage} per page</span>
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" ${current <= 1 ? 'disabled' : ''} onclick="goToPage(${current - 1})" title="Previous">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                ${buttonsHtml}
                <button class="pagination-btn" ${current >= last ? 'disabled' : ''} onclick="goToPage(${current + 1})" title="Next">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>
    `;
}

function goToPage(page) {
    const prompt = `Show page ${page} of the previous data`;
    promptInput.value = prompt;
    window.sendPrompt();
}

/* ============================================================
   PREVIEW MODAL
   ============================================================ */
let currentPreviewUrl = '', currentPreviewName = '', currentPreviewType = '', currentPreviewMime = '';

function previewFileResource(url, name, type, mime) {
    currentPreviewUrl = url;
    currentPreviewName = name;
    currentPreviewType = type;
    currentPreviewMime = mime || '';

    const modal = document.getElementById('preview-modal');
    const content = document.getElementById('preview-content');
    const title = document.getElementById('preview-title');
    const downloadBtn = document.getElementById('preview-download-btn');

    title.textContent = name;
    downloadBtn.style.display = 'inline-flex';

    let fullUrl = url;
    if (url.startsWith('/')) fullUrl = window.location.origin + url;

    if (type === 'image') {
        content.innerHTML = `
            <div class="flex items-center justify-center" style="min-height: 300px;">
                <img src="${escapeHtml(url)}" alt="${escapeHtml(name)}"
                    class="max-h-[70vh] w-full object-contain rounded-lg border"
                    style="border-color: var(--border-color);"
                    onerror="this.outerHTML='<div class=&quot;p-12 text-center&quot; style=&quot;color: var(--text-muted);&quot;><div class=&quot;text-6xl mb-4&quot;>🖼️</div><p class=&quot;text-sm&quot;>Image could not be loaded.</p></div>'" />
            </div>
        `;
    } else if (type === 'pdf') {
        content.innerHTML = `
            <div style="height: 70vh; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
                <iframe src="${escapeHtml(fullUrl)}" style="width: 100%; height: 100%; border: none;" title="${escapeHtml(name)}"></iframe>
            </div>
            <p class="text-xs mt-2 text-center" style="color: var(--text-muted);">
                If the PDF doesn't display, your browser may not support embedded previews. Use the download button.
            </p>
        `;
    } else if (type === 'excel' || type === 'word') {
        const icon = type === 'excel' ? '📊' : '📝';
        const label = type === 'excel' ? 'Excel Spreadsheet' : 'Word Document';
        content.innerHTML = `
            <div class="p-10 text-center" style="color: var(--text-muted);">
                <div class="text-6xl mb-4">${icon}</div>
                <p class="text-sm font-semibold mb-1" style="color: var(--text-primary);">${label} Preview</p>
                <p class="text-xs mb-4">This file type cannot be previewed directly in the browser.</p>
                <p class="text-xs">Use the <strong>Download</strong> button below to open it in your local application.</p>
            </div>
        `;
    } else if (type === 'zip') {
        content.innerHTML = `
            <div class="p-10 text-center" style="color: var(--text-muted);">
                <div class="text-6xl mb-4">📦</div>
                <p class="text-sm font-semibold mb-1" style="color: var(--text-primary);">ZIP Archive</p>
                <p class="text-xs mb-4">Archive contents cannot be previewed directly.</p>
                <p class="text-xs">Download the file to extract and view its contents.</p>
            </div>
        `;
    } else if (type === 'binary' || type === 'generic') {
        if (currentPreviewMime && (currentPreviewMime.startsWith('image/') || currentPreviewMime === 'application/pdf')) {
            content.innerHTML = `
                <div style="height: 70vh; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
                    <iframe src="${escapeHtml(fullUrl)}" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="p-10 text-center" style="color: var(--text-muted);">
                    <div class="text-6xl mb-4">📁</div>
                    <p class="text-sm font-semibold mb-1" style="color: var(--text-primary);">Binary File</p>
                    <p class="text-xs mb-4">Preview is not available for this file type.</p>
                    <p class="text-xs">Download to open with a compatible application.</p>
                </div>
            `;
        }
    } else {
        content.innerHTML = `
            <div class="p-10 text-center" style="color: var(--text-muted);">
                <div class="text-6xl mb-4">📄</div>
                <p class="text-sm">Preview not available. Please download.</p>
            </div>
        `;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function previewAttachment(url, name, type) {
    let mappedType = type;
    let mime = '';
    if (type === 'image') mime = 'image/*';
    else if (type === 'zip') mime = 'application/zip';
    else if (type === 'doc') mappedType = 'pdf';
    previewFileResource(url, name, mappedType, mime);
}

function closePreviewModal() {
    const m = document.getElementById('preview-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.getElementById('preview-content').innerHTML = '';
    currentPreviewUrl = '';
    currentPreviewName = '';
    currentPreviewType = '';
    currentPreviewMime = '';
}

function downloadPreviewFile() {
    if (!currentPreviewUrl) return;
    const a = document.createElement('a');
    a.href = currentPreviewUrl;
    a.download = currentPreviewName || 'download';
    a.click();
}

/* ============================================================
   RECURSIVE PARSER
   ============================================================ */
function parseRecursive(item, depth = 0) {
    if (item === null || item === undefined) return '<span class="italic" style="color: var(--text-muted);">N/A</span>';
    if (typeof item !== 'object') {
        if (typeof item === 'boolean') {
            return `<span style="color: ${item ? '#059669' : '#dc2626'}; font-weight: 500;">${item ? '✓ true' : '✗ false'}</span>`;
        }
        if (typeof item === 'number') {
            return `<span style="color: #d97706; font-weight: 500;">${item.toLocaleString()}</span>`;
        }
        return `<span>${escapeHtml(String(item))}</span>`;
    }

    if (Array.isArray(item)) {
        if (item.length === 0) return '<span class="italic" style="color: var(--text-muted);">Empty list</span>';
        if (typeof item[0] === 'object' && item[0] !== null && !Array.isArray(item[0])) {
            return buildTableFromArray(item, depth);
        }
        let html = '<ul class="list-disc pl-5 space-y-1 my-2">';
        item.forEach(subItem => { html += `<li class="text-sm">${parseRecursive(subItem, depth + 1)}</li>`; });
        html += '</ul>';
        return html;
    }

    let html = '<div class="space-y-2 my-2 text-sm">';
    for (let key in item) {
        if (!Object.prototype.hasOwnProperty.call(item, key)) continue;
        let val = item[key];
        let formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        // Skip noisy wrapper keys already handled
        if (['status', 'success', 'exception', 'links', 'meta', 'current_page', 'last_page', 'per_page', 'from', 'to', 'path', 'first', 'prev', 'next'].includes(key)) continue;

        if (Array.isArray(val) && val.length > 0 && typeof val[0] === 'object' && val[0] !== null) {
            html += `<div class="border-l-2 pl-3 py-1" style="border-color: var(--accent);">
                <div class="nested-badge">📊 ${escapeHtml(formattedKey)} (${val.length} items)</div>
                <div>${buildTableFromArray(val, depth + 1)}</div>
            </div>`;
        } else {
            html += `<div class="border-l-2 pl-3 py-0.5" style="border-color: var(--accent);">
                <strong style="color: var(--text-secondary);">${escapeHtml(formattedKey)}:</strong>
                <div class="mt-1">${parseRecursive(val, depth + 1)}</div>
            </div>`;
        }
    }
    html += '</div>';
    return html;
}

/* ============================================================
   BUILD TABLE (N-TH LEVEL)
   ============================================================ */
function buildTableFromArray(arr, depth = 0) {
    if (!arr || arr.length === 0) return '<span class="italic" style="color: var(--text-muted);">Empty list</span>';

    if (Array.isArray(arr[0])) {
        return arr.map(sub => parseRecursive(sub, depth + 1)).join('');
    }

    const keys = [...new Set(arr.flatMap(obj => Object.keys(obj)))];

    let html = `<div class="table-responsive" style="margin-left: ${depth > 0 ? '4px' : '0'};">
        <table><thead><tr>`;
    keys.forEach(key => {
        let title = key.replace(/_/g, ' ');
        html += `<th>${escapeHtml(title)}</th>`;
    });
    html += `</tr></thead><tbody>`;

    arr.forEach((row) => {
        html += `<tr>`;
        keys.forEach(key => {
            let val = row[key];
            if (val === undefined || val === null) {
                html += `<td><span class="italic" style="color: var(--text-muted);">—</span></td>`;
            } else if (typeof val === 'object' && !Array.isArray(val)) {
                const fileRes = detectFileResource(val);
                if (fileRes) {
                    html += `<td>${renderInlineFileCard(fileRes)}</td>`;
                } else {
                    html += `<td>${parseRecursive(val, depth + 1)}</td>`;
                }
            } else if (Array.isArray(val) && val.length > 0 && typeof val[0] === 'object' && val[0] !== null) {
                html += `<td>
                    <div class="nested-badge">📊 ${val.length} rows</div>
                    ${buildTableFromArray(val, depth + 1)}
                </td>`;
            } else if (typeof val === 'boolean') {
                html += `<td><span style="color: ${val ? '#059669' : '#dc2626'}; font-weight: 500;">${val ? '✓ true' : '✗ false'}</span></td>`;
            } else if (typeof val === 'number') {
                html += `<td><span style="color: #d97706; font-weight: 500;">${val.toLocaleString()}</span></td>`;
            } else if (typeof val === 'string') {
                const fileRes = detectFileResource(val);
                if (fileRes) {
                    html += `<td>${renderInlineFileCard(fileRes)}</td>`;
                } else if (val.startsWith('http://') || val.startsWith('https://')) {
                    html += `<td><a href="${escapeHtml(val)}" target="_blank" class="hover:underline truncate block max-w-[200px]" style="color: var(--accent);" title="${escapeHtml(val)}">${escapeHtml(val.length > 40 ? val.substring(0, 40) + '…' : val)}</a></td>`;
                } else {
                    html += `<td>${escapeHtml(String(val))}</td>`;
                }
            } else {
                html += `<td>${escapeHtml(String(val))}</td>`;
            }
        });
        html += `</tr>`;
    });

    html += `</tbody></table></div>`;
    return html;
}

/* ============================================================
   JSON SYNTAX HIGHLIGHT
   ============================================================ */
function syntaxHighlightJson(obj) {
    let json = JSON.stringify(obj, null, 2);
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        let cls = 'json-number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) cls = 'json-key';
            else cls = 'json-string';
        } else if (/true|false/.test(match)) cls = 'json-boolean';
        else if (/null/.test(match)) cls = 'json-null';
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

/* ============================================================
   ESCAPE
   ============================================================ */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

/* ============================================================
   SEND PROMPT
   ============================================================ */
async function sendPrompt() {
    const prompt = promptInput.value.trim();
    if (!prompt) return;

    sendBtn.disabled = true;
    appendMessage(escapeHtml(prompt), 'user', false, true, prompt);
    promptInput.value = '';
    promptInput.style.height = 'auto';

    const parsedJson = tryParseJson(prompt);
    if (parsedJson) {
        const loadingId = showLoadingIndicator();
        setTimeout(() => {
            removeLoadingIndicator(loadingId);
            renderResponse(parsedJson, null);
            sendBtn.disabled = false;
            promptInput.focus();
        }, 300);
        return;
    }

    const loadingId = showLoadingIndicator();
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const headers = { 'Accept': 'application/json, application/pdf, application/octet-stream, */*' };
        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
        const authToken = getAuthToken();
        if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

        const response = await fetch('/api/ai/prompt', {
            method: 'POST',
            headers: { ...headers, 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt })
        });

        const contentType = (response.headers.get('Content-Type') || '').toLowerCase();

        // CASE 1: Binary file response
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
            removeLoadingIndicator(loadingId);

            let filename = extractFilenameFromDisposition(response.headers.get('Content-Disposition')) || 'download';
            if (!filename || filename === 'download') filename = filenameWithExtensionFromMime(contentType, filename);

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);

            const ext = getFileExtension(filename) || mimeToExtension(contentType);
            const binaryInfo = BINARY_EXTENSIONS[ext] || { type: 'binary', icon: '📁', label: 'File', mime: contentType };

            const fileRes = {
                url: objectUrl, type: binaryInfo.type, icon: binaryInfo.icon,
                label: binaryInfo.label, mime: contentType, name: filename, size: blob.size
            };

            let htmlContent = '';
            if (response.status >= 400) {
                htmlContent += `<div class="mb-3 text-xs font-medium px-3 py-2 rounded-lg flex items-center gap-2"
                    style="background: var(--error-bg); color: var(--error-text); border: 1px solid var(--error-border);">
                    Failed to generate file (HTTP ${response.status})
                </div>`;
            } else {
                htmlContent += `<div class="mb-3 text-xs font-medium px-3 py-2 rounded-lg flex items-center gap-2"
                    style="background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    File generated successfully
                </div>`;
                htmlContent += renderFileCard(fileRes);
            }

            appendMessage(htmlContent, 'ai', false, true);

            const bubbles = chatContainer.querySelectorAll('.message-wrapper');
            const lastBubble = bubbles[bubbles.length - 1]?.querySelector('.rounded-2xl');
            if (lastBubble) {
                lastBubble.dataset.objectUrl = objectUrl;
                lastBubble.dataset.rawJson = JSON.stringify({
                    file: filename, mime: contentType, size: blob.size, generatedAt: new Date().toISOString()
                });
            }

            sendBtn.disabled = false;
            promptInput.focus();
            return;
        }

        // CASE 2: JSON response
        const data = await response.json();
        removeLoadingIndicator(loadingId);

        if (response.status === 401) {
            appendMessage('Your session has expired. Please login again.', 'ai', true, false);
            clearAuthSession();
            openLoginModal();
            return;
        }
        if (response.status === 403 || data.status === 'error') {
            appendMessage(data.message || 'Unauthorized action.', 'ai', true, false);
            return;
        }

        // Accept any of these success markers
        const isSuccess = data.status === 'success' ||
                          data.success === true ||
                          (data.result && data.result.success === true);

        if (isSuccess) {
            renderResponse(data, data.action);
        } else {
            appendMessage(data.message || 'No response generated.', 'ai', true, false);
        }
    } catch (error) {
        removeLoadingIndicator(loadingId);
        appendMessage('Network or server error occurred.', 'ai', true, false);
        console.error('Error sending prompt:', error);
    } finally {
        sendBtn.disabled = false;
        promptInput.focus();
    }
}

/* ============================================================
   HELPER: Extract filename from Content-Disposition
   ============================================================ */
function extractFilenameFromDisposition(disposition) {
    if (!disposition) return null;
    const utf8Match = disposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
    if (utf8Match) {
        try { return decodeURIComponent(utf8Match[1].trim().replace(/^["']|["']$/g, '')); } catch (e) {}
    }
    const stdMatch = disposition.match(/filename\s*=\s*"?([^";]+)"?/i);
    if (stdMatch) return stdMatch[1].trim();
    return null;
}

function filenameWithExtensionFromMime(mime, currentName) {
    const base = currentName && currentName !== 'download' ? currentName.replace(/\.[^.]+$/, '') : 'download';
    const ext = mimeToExtension(mime);
    return `${base}.${ext}`;
}

function mimeToExtension(mime) {
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
    if (mime.includes('octet-stream')) return 'bin';
    return 'bin';
}

/* ============================================================
   BIND TEXTAREA & SEND BUTTON
   ============================================================ */
promptInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 160) + 'px';
});

promptInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (typeof window.sendPrompt === 'function') window.sendPrompt();
    }
});

sendBtn.addEventListener('click', function () {
    if (typeof window.sendPrompt === 'function') window.sendPrompt();
});

/* ============================================================
   INIT & GLOBAL EXPORTS
   ============================================================ */
updateAuthUI();
promptInput.style.height = 'auto';
promptInput.style.height = Math.min(promptInput.scrollHeight, 160) + 'px';

window.toggleTheme = toggleTheme;
window.showPage = showPage;
window.openLoginModal = openLoginModal;
window.closeLoginModal = closeLoginModal;
window.submitLogin = submitLogin;
window.logout = logout;
window.sendPrompt = sendPrompt;
window.copyMessageContent = copyMessageContent;
window.exportMessageJson = exportMessageJson;
window.resubmitPrompt = resubmitPrompt;
window.previewAttachment = previewAttachment;
window.previewFileResource = previewFileResource;
window.closePreviewModal = closePreviewModal;
window.downloadPreviewFile = downloadPreviewFile;
window.toggleRawJson = toggleRawJson;
window.exportFullResponse = exportFullResponse;
window.goToPage = goToPage;

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (!localStorage.getItem('ai_bridge_theme')) applyTheme(e.matches ? 'dark' : 'light');
});
</script>
</body>
</html>