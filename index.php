<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LYLY PDF Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans+JP:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f1419;
            --bg-card: #1a1f26;
            --bg-elevated: #242a33;
            --bg-input: #0d1117;
            --accent-primary: #00d4aa;
            --accent-secondary: #0ea5e9;
            --accent-gradient: linear-gradient(135deg, #00d4aa 0%, #0ea5e9 100%);
            --text-primary: #e7e9ea;
            --text-secondary: #8b98a5;
            --text-muted: #536471;
            --border: #2f3842;
            --border-light: #38444d;
            --success: #00ba7c;
            --success-glow: rgba(0, 186, 124, 0.15);
            --error: #f4212e;
            --error-glow: rgba(244, 33, 46, 0.15);
            --grid-color: rgba(47, 56, 66, 0.3);
            --header-bg: rgba(15, 20, 25, 0.9);
        }

        [data-theme="light"] {
            --bg-dark: #f0f2f5;
            --bg-card: #ffffff;
            --bg-elevated: #f8f9fa;
            --bg-input: #ffffff;
            --accent-primary: #059669;
            --accent-secondary: #0284c7;
            --accent-gradient: linear-gradient(135deg, #059669 0%, #0284c7 100%);
            --text-primary: #1a1a2e;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border: #e2e8f0;
            --border-light: #cbd5e1;
            --success: #059669;
            --success-glow: rgba(5, 150, 105, 0.12);
            --error: #dc2626;
            --error-glow: rgba(220, 38, 38, 0.1);
            --grid-color: rgba(0, 0, 0, 0.04);
            --header-bg: rgba(255, 255, 255, 0.9);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'IBM Plex Sans JP', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .app {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            padding: 24px 32px;
            border-bottom: 1px solid var(--border);
            background: var(--header-bg);
            backdrop-filter: blur(12px);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo-mark {
            width: 40px;
            height: 40px;
            background: var(--accent-gradient);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--bg-dark);
        }

        .logo-text {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 1.4rem;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        .logo-text span { color: var(--accent-primary); }

        .tagline {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-left: auto;
            font-family: 'IBM Plex Mono', monospace;
        }

        .theme-toggle {
            width: 40px;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-card);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: all 0.2s ease;
            margin-left: 16px;
        }

        .theme-toggle:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        .main-container {
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
            width: 100%;
            display: grid;
            grid-template-columns: minmax(320px, 420px) 1fr;
            gap: 32px;
            align-items: start;
        }

        .input-column { position: sticky; top: 32px; }

        .output-column {
            display: flex;
            flex-direction: column;
            gap: 24px;
            min-height: 600px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header h2 {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-muted);
        }

        .status-indicator.active {
            background: var(--accent-primary);
            box-shadow: 0 0 12px var(--accent-primary);
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .status-indicator.success {
            background: var(--success);
            box-shadow: 0 0 12px var(--success);
        }

        .status-indicator.error {
            background: var(--error);
            box-shadow: 0 0 12px var(--error);
        }

        @keyframes pulse-glow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .card-body { padding: 20px; }

        .drop-zone {
            border: 2px dashed var(--border);
            border-radius: 6px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--bg-input);
        }

        .drop-zone:hover {
            border-color: var(--accent-primary);
            background: rgba(0, 212, 170, 0.05);
        }

        .drop-zone.dragover {
            border-color: var(--accent-primary);
            background: rgba(0, 212, 170, 0.1);
            transform: scale(1.01);
        }

        .drop-zone.has-file {
            border-color: var(--success);
            border-style: solid;
            background: var(--success-glow);
        }

        .drop-zone-icon { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.8; }

        .drop-zone-text { color: var(--text-secondary); font-size: 0.9rem; line-height: 1.8; }

        .drop-zone-file {
            margin-top: 16px;
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--success);
            display: none;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .drop-zone.has-file .drop-zone-file { display: flex; }

        .drop-zone-file::before {
            content: '✓';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background: var(--success);
            color: var(--bg-dark);
            border-radius: 50%;
            font-size: 0.65rem;
            font-weight: 600;
        }

        #file-input { display: none; }

        .section-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 24px 0 12px;
        }

        .process-type { display: flex; flex-direction: column; gap: 8px; }

        .process-type label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            transition: all 0.15s ease;
            background: var(--bg-input);
        }

        .process-type label:hover {
            border-color: var(--accent-primary);
            background: rgba(0, 212, 170, 0.05);
        }

        .process-type input { display: none; }

        .process-type .radio-custom {
            width: 16px;
            height: 16px;
            border: 2px solid var(--border-light);
            border-radius: 50%;
            position: relative;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .process-type input:checked + .radio-custom {
            border-color: var(--accent-primary);
            background: var(--accent-primary);
        }

        .process-type input:checked + .radio-custom::after {
            content: '';
            position: absolute;
            inset: 3px;
            background: var(--bg-dark);
            border-radius: 50%;
        }

        .process-type .label-content { flex: 1; }

        .process-type .label-text { font-weight: 500; font-size: 0.9rem; color: var(--text-primary); }

        .process-type .type-desc {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .process-type label:has(input:checked) {
            border-color: var(--accent-primary);
            background: rgba(0, 212, 170, 0.08);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: var(--bg-dark);
            width: 100%;
            margin-top: 24px;
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-primary:hover:not(:disabled) {
            filter: brightness(1.1);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(0, 212, 170, 0.3);
        }

        .btn-primary:active:not(:disabled) { transform: translateY(0); }

        .btn-primary:disabled {
            background: var(--border);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .btn-download {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 8px 14px;
            font-size: 0.8rem;
            font-family: 'IBM Plex Mono', monospace;
            text-decoration: none;
        }

        .btn-download:hover { border-color: var(--accent-primary); color: var(--accent-primary); }

        .progress-section { flex: 1; display: flex; flex-direction: column; }

        .progress-section .card-body { flex: 1; display: flex; flex-direction: column; }

        .progress-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            padding: 40px;
            text-align: center;
        }

        .progress-empty-icon { font-size: 3rem; margin-bottom: 16px; opacity: 0.5; }
        .progress-empty-text { font-size: 0.9rem; }

        .progress-content { display: none; flex-direction: column; gap: 16px; }
        .progress-content.active { display: flex; }

        .progress-bar-container {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 4px;
            overflow: hidden;
            height: 32px;
        }

        .progress-bar {
            background: var(--accent-gradient);
            height: 100%;
            width: 0%;
            transition: width 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bg-dark);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .progress-bar.shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.3) 50%,
                transparent 100%
            );
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .progress-status { font-family: 'IBM Plex Mono', monospace; font-size: 0.8rem; color: var(--text-secondary); }

        .progress-log {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 16px;
            max-height: 240px;
            overflow-y: auto;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            line-height: 1.8;
            color: var(--text-secondary);
            flex: 1;
        }

        .progress-log::-webkit-scrollbar { width: 6px; }
        .progress-log::-webkit-scrollbar-track { background: transparent; }
        .progress-log::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        .progress-log .error { color: var(--error); }
        .progress-log .success { color: var(--success); }
        .progress-log .skipped { color: var(--warning); opacity: 0.8; }
        .progress-log .info { color: var(--accent); }

        .result-section .card-body { padding: 0; }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }

        .result-count { font-family: 'IBM Plex Mono', monospace; font-size: 0.85rem; color: var(--text-secondary); }
        .result-count strong { color: var(--accent-primary); font-weight: 600; }

        .file-list { max-height: 300px; overflow-y: auto; }
        .file-list::-webkit-scrollbar { width: 6px; }
        .file-list::-webkit-scrollbar-track { background: transparent; }
        .file-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            transition: background 0.15s ease;
        }

        .file-item:last-child { border-bottom: none; }
        .file-item:hover { background: var(--bg-elevated); }

        .file-name {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            word-break: break-all;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .file-type {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .file-type.temp { background: rgba(14, 165, 233, 0.15); color: var(--accent-secondary); }
        .file-type.draft { background: rgba(0, 212, 170, 0.15); color: var(--accent-primary); }

        .error-box {
            background: var(--error-glow);
            border: 1px solid var(--error);
            border-radius: 6px;
            padding: 16px 20px;
            display: none;
        }

        .error-box.active { display: block; }

        .error-box h4 {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--error);
            margin-bottom: 8px;
        }

        .error-box p { font-size: 0.85rem; color: var(--text-primary); }

        .error-guidance {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(244, 33, 46, 0.3);
        }

        .error-guidance-title {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .error-guidance-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-guidance-list li {
            font-size: 0.8rem;
            color: var(--text-secondary);
            padding: 6px 0;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .error-guidance-list li::before {
            content: '→';
            color: var(--accent-primary);
            flex-shrink: 0;
        }

        .error-retry-btn {
            margin-top: 12px;
            padding: 8px 16px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 0.8rem;
            font-family: 'IBM Plex Mono', monospace;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .error-retry-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        /* 履歴セクションのスタイル */
        .history-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg-elevated);
            transition: all 0.2s ease;
        }

        .history-item:hover {
            border-color: var(--accent-primary);
            background: var(--bg-card);
        }

        .history-info {
            flex: 1;
        }

        .history-datetime {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .history-stats {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .history-actions {
            display: flex;
            gap: 8px;
        }

        /* スケルトンローディング */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-elevated) 25%, var(--bg-card) 50%, var(--bg-elevated) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s ease-in-out infinite;
            border-radius: 4px;
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg-elevated);
            margin-bottom: 12px;
        }

        .skeleton-item:last-child { margin-bottom: 0; }

        .skeleton-info { flex: 1; }
        .skeleton-title { height: 18px; width: 60%; margin-bottom: 8px; }
        .skeleton-stats { height: 14px; width: 40%; }
        .skeleton-actions { display: flex; gap: 8px; }
        .skeleton-btn { height: 32px; width: 60px; }

        /* ステージ進捗表示 */
        .stage-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
        }

        .stage-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 6px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.85rem;
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .stage-item.active {
            border-color: var(--accent-primary);
            background: rgba(0, 212, 170, 0.08);
            color: var(--text-primary);
        }

        .stage-item.completed {
            border-color: var(--success);
            color: var(--success);
        }

        .stage-item.completed .stage-icon::after {
            content: '✓';
            position: absolute;
            font-size: 0.7rem;
        }

        .stage-icon {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            position: relative;
        }

        .stage-item.active .stage-icon {
            animation: pulse-icon 1s ease-in-out infinite;
        }

        @keyframes pulse-icon {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        .stage-label { flex: 1; }

        .stage-count {
            font-size: 0.75rem;
            color: var(--text-muted);
            padding: 2px 8px;
            background: var(--bg-elevated);
            border-radius: 10px;
        }

        /* CSVプレビューモーダル */
        .preview-modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .preview-modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .preview-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .preview-modal.active .preview-content {
            transform: scale(1);
        }

        .preview-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .preview-header h3 {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--bg-elevated);
            border-radius: 6px;
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .preview-close:hover {
            background: var(--error-glow);
            color: var(--error);
        }

        .preview-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .preview-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .preview-stat {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }

        .preview-stat-value {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--accent-primary);
        }

        .preview-stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .preview-stat.warning .preview-stat-value {
            color: #f59e0b;
        }

        .preview-stat.error .preview-stat-value {
            color: var(--error);
        }

        .preview-section {
            margin-bottom: 16px;
        }

        .preview-section-title {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .preview-list {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 6px;
            max-height: 150px;
            overflow-y: auto;
        }

        .preview-list-item {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-list-item:last-child { border-bottom: none; }

        .preview-list-item.warning { color: #f59e0b; }
        .preview-list-item.error { color: var(--error); }
        .preview-list-item.success { color: var(--success); }

        .preview-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .preview-footer .btn {
            min-width: 120px;
        }

        .btn-secondary {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        /* サクセスアニメーション */
        .success-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .success-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .success-content {
            text-align: center;
            transform: scale(0.8);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .success-overlay.active .success-content {
            transform: scale(1);
        }

        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: success-bounce 0.6s ease;
        }

        @keyframes success-bounce {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .success-title {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--success);
            margin-bottom: 8px;
        }

        .success-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @media (max-width: 900px) {
            .main-container { grid-template-columns: 1fr; padding: 24px 16px; }
            .input-column { position: static; }
            header { padding: 16px; }
            .header-content { flex-wrap: wrap; }
            .tagline { width: 100%; margin-left: 0; margin-top: 8px; }
        }
        /* PDFプレビューモーダル */
        .pdf-preview-modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1001;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .pdf-preview-modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .pdf-preview-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 90vw;
            max-width: 1000px;
            height: 90vh;
            max-height: 900px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .pdf-preview-modal.active .pdf-preview-content {
            transform: scale(1);
        }

        .pdf-preview-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }

        .pdf-preview-header h3 {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.9rem;
            font-weight: 600;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pdf-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .pdf-controls button {
            width: 32px;
            height: 32px;
            border: 1px solid var(--border);
            background: var(--bg-elevated);
            border-radius: 6px;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .pdf-controls button:hover:not(:disabled) {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        .pdf-controls button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pdf-page-info, .pdf-zoom-level {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            color: var(--text-secondary);
            min-width: 60px;
            text-align: center;
        }

        .pdf-preview-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--bg-elevated);
            border-radius: 6px;
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .pdf-preview-close:hover {
            background: var(--error-glow);
            color: var(--error);
        }

        .pdf-preview-body {
            flex: 1;
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-input);
            padding: 20px;
        }

        .pdf-preview-body canvas {
            max-width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .pdf-preview-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .pdf-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--text-secondary);
            font-family: 'IBM Plex Mono', monospace;
        }

        .pdf-loading-spinner {
            width: 24px;
            height: 24px;
            border: 2px solid var(--border);
            border-top-color: var(--accent-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-preview {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 4px 10px;
            font-size: 0.75rem;
        }

        .btn-preview:hover {
            border-color: var(--accent-secondary);
            color: var(--accent-secondary);
        }

        /* 履歴詳細アコーディオン */
        .history-detail {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: var(--bg-input);
            border-radius: 0 0 8px 8px;
            margin-top: -8px;
            margin-bottom: 12px;
        }

        .history-detail.expanded {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-top: none;
        }

        .history-detail-content {
            padding: 12px 16px;
        }

        .history-detail-loading {
            text-align: center;
            padding: 16px;
            color: var(--text-muted);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.85rem;
        }

        .history-detail-files {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* タブスタイル */
        .history-detail-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }

        .history-detail-tab {
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: 6px 6px 0 0;
            background: var(--bg-elevated);
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .history-detail-tab:hover {
            background: var(--bg-input);
        }

        .history-detail-tab.active {
            background: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        .history-detail-tab-content {
            display: none;
        }

        .history-detail-tab-content.active {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .history-detail .file-item {
            padding: 8px 12px;
            background: var(--bg-elevated);
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        .history-item.expanded {
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
</head>
<body>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>

    <!-- CSVプレビューモーダル -->
    <div class="preview-modal" id="preview-modal">
        <div class="preview-content">
            <div class="preview-header">
                <h3>📋 CSVプレビュー</h3>
                <button class="preview-close" id="preview-close">×</button>
            </div>
            <div class="preview-body">
                <div class="preview-stats">
                    <div class="preview-stat" id="preview-stat-total">
                        <div class="preview-stat-value">0</div>
                        <div class="preview-stat-label">注文件数</div>
                    </div>
                    <div class="preview-stat" id="preview-stat-valid">
                        <div class="preview-stat-value">0</div>
                        <div class="preview-stat-label">有効</div>
                    </div>
                    <div class="preview-stat" id="preview-stat-warning">
                        <div class="preview-stat-value">0</div>
                        <div class="preview-stat-label">警告</div>
                    </div>
                </div>

                <div class="preview-section" id="preview-orders-section">
                    <div class="preview-section-title">注文一覧（先頭10件）</div>
                    <div class="preview-list" id="preview-orders"></div>
                </div>

                <div class="preview-section" id="preview-warnings-section" style="display: none;">
                    <div class="preview-section-title">⚠️ 警告</div>
                    <div class="preview-list" id="preview-warnings"></div>
                </div>
            </div>
            <div class="preview-footer">
                <button class="btn btn-secondary" id="preview-cancel">キャンセル</button>
                <button class="btn btn-primary" id="preview-proceed" style="margin-top: 0;">生成を開始</button>
            </div>
        </div>
    </div>

    <!-- サクセスオーバーレイ -->
    <div class="success-overlay" id="success-overlay">
        <div class="success-content">
            <div class="success-icon">✅</div>
            <div class="success-title">処理完了</div>
            <div class="success-subtitle" id="success-subtitle">PDFの生成が完了しました</div>
        </div>
    </div>

    <!-- PDFプレビューモーダル -->
    <div class="pdf-preview-modal" id="pdf-preview-modal">
        <div class="pdf-preview-content">
            <div class="pdf-preview-header">
                <h3 id="pdf-preview-title">PDFプレビュー</h3>
                <div class="pdf-controls">
                    <button id="pdf-prev-page" title="前のページ">◀</button>
                    <span class="pdf-page-info" id="pdf-page-info">1 / 1</span>
                    <button id="pdf-next-page" title="次のページ">▶</button>
                    <span style="margin: 0 8px; color: var(--border);">|</span>
                    <button id="pdf-zoom-out" title="縮小">−</button>
                    <span class="pdf-zoom-level" id="pdf-zoom-level">100%</span>
                    <button id="pdf-zoom-in" title="拡大">+</button>
                </div>
                <button class="pdf-preview-close" id="pdf-preview-close" title="閉じる">×</button>
            </div>
            <div class="pdf-preview-body" id="pdf-preview-body">
                <div class="pdf-loading" id="pdf-loading">
                    <div class="pdf-loading-spinner"></div>
                    <span>読み込み中...</span>
                </div>
                <canvas id="pdf-canvas" style="display: none;"></canvas>
            </div>
            <div class="pdf-preview-footer">
                <button class="btn btn-secondary" id="pdf-preview-close-btn">閉じる</button>
                <a class="btn btn-download" id="pdf-download-btn" download>ダウンロード</a>
            </div>
        </div>
    </div>

    <div class="app">
        <header>
            <div class="header-content">
                <div class="logo-mark">L</div>
                <div class="logo-text">LYLY <span>PDF</span></div>
                <div class="tagline">// アクリル製品用PDF生成システム</div>
                <button class="theme-toggle" id="theme-toggle" title="テーマ切替">🌙</button>
            </div>
        </header>

        <main class="main-container">
            <div class="input-column">
                <div class="card">
                    <div class="card-header">
                        <div class="status-indicator" id="input-status"></div>
                        <h2>入力</h2>
                    </div>
                    <div class="card-body">
                        <div class="drop-zone" id="drop-zone">
                            <div class="drop-zone-icon">📄</div>
                            <div class="drop-zone-text">CSVファイルをドラッグ＆ドロップ<br>または クリックして選択</div>
                            <div class="drop-zone-file" id="file-name"></div>
                        </div>
                        <input type="file" id="file-input" accept=".csv">

                        <div class="section-label">処理タイプ</div>
                        <div class="process-type">
                            <label>
                                <input type="radio" name="process" value="all" checked>
                                <span class="radio-custom"></span>
                                <div class="label-content">
                                    <div class="label-text">すべて生成</div>
                                    <div class="type-desc">temp/ + draft/</div>
                                </div>
                            </label>
                            <label>
                                <input type="radio" name="process" value="temp">
                                <span class="radio-custom"></span>
                                <div class="label-content">
                                    <div class="label-text">個別のみ</div>
                                    <div class="type-desc">temp/ に出力</div>
                                </div>
                            </label>
                            <label>
                                <input type="radio" name="process" value="draft">
                                <span class="radio-custom"></span>
                                <div class="label-content">
                                    <div class="label-text">印刷用のみ</div>
                                    <div class="type-desc">draft/ に出力</div>
                                </div>
                            </label>
                        </div>

                        <button class="btn btn-primary" id="generate-btn" disabled>PDF生成を開始</button>
                    </div>
                </div>
            </div>

            <div class="output-column">
                <div class="error-box" id="error-box">
                    <h4>⚠ エラー</h4>
                    <p id="error-message"></p>
                    <div class="error-guidance" id="error-guidance" style="display: none;">
                        <div class="error-guidance-title">解決方法</div>
                        <ul class="error-guidance-list" id="error-guidance-list"></ul>
                        <button class="error-retry-btn" id="error-retry-btn" style="display: none;">🔄 再試行</button>
                    </div>
                </div>

                <div class="card progress-section" id="progress-section">
                    <div class="card-header">
                        <div class="status-indicator" id="progress-status-indicator"></div>
                        <h2>処理状況</h2>
                    </div>
                    <div class="card-body">
                        <div class="progress-empty" id="progress-empty">
                            <div class="progress-empty-icon">⏳</div>
                            <div class="progress-empty-text">CSVをアップロードして処理を開始</div>
                        </div>
                        <div class="progress-content" id="progress-content">
                            <!-- ステージ表示 (Labor Illusion) -->
                            <div class="stage-list" id="stage-list">
                                <div class="stage-item" data-stage="parse">
                                    <span class="stage-icon">📊</span>
                                    <span class="stage-label">CSVデータを解析中...</span>
                                </div>
                                <div class="stage-item" data-stage="download">
                                    <span class="stage-icon">🖼️</span>
                                    <span class="stage-label">画像をダウンロード中...</span>
                                    <span class="stage-count" id="download-count" style="display:none"></span>
                                </div>
                                <div class="stage-item" data-stage="temp">
                                    <span class="stage-icon">📄</span>
                                    <span class="stage-label">個別PDFを生成中...</span>
                                    <span class="stage-count" id="temp-count" style="display:none"></span>
                                </div>
                                <div class="stage-item" data-stage="draft">
                                    <span class="stage-icon">🖨️</span>
                                    <span class="stage-label">印刷用PDFを作成中...</span>
                                </div>
                                <div class="stage-item" data-stage="zip">
                                    <span class="stage-icon">📦</span>
                                    <span class="stage-label">ZIPアーカイブを作成中...</span>
                                </div>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" id="progress-bar">0%</div>
                            </div>
                            <div class="progress-status" id="progress-status">準備中...</div>
                            <div class="progress-log" id="progress-log"></div>
                        </div>
                    </div>
                </div>

                <div class="card result-section" id="result-section" style="display: none;">
                    <div class="card-header">
                        <div class="status-indicator success"></div>
                        <h2>生成結果</h2>
                    </div>
                    <div class="card-body">
                        <div class="result-header">
                            <span class="result-count" id="result-count">完了: <strong>0</strong>件</span>
                            <button class="btn btn-download" id="download-all-btn">📦 ZIP一括DL</button>
                        </div>
                        <div class="file-list" id="file-list"></div>
                    </div>
                </div>

                <!-- 履歴セクション -->
                <div class="card history-section" id="history-section" style="display: block;">
                    <div class="card-header">
                        <div class="status-indicator"></div>
                        <h2>過去の生成履歴</h2>
                    </div>
                    <div class="card-body">
                        <div class="history-list" id="history-list">
                            <!-- スケルトンローディング -->
                            <div class="skeleton-item">
                                <div class="skeleton-info">
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-stats"></div>
                                </div>
                                <div class="skeleton-actions">
                                    <div class="skeleton skeleton-btn"></div>
                                    <div class="skeleton skeleton-btn"></div>
                                </div>
                            </div>
                            <div class="skeleton-item">
                                <div class="skeleton-info">
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-stats"></div>
                                </div>
                                <div class="skeleton-actions">
                                    <div class="skeleton skeleton-btn"></div>
                                    <div class="skeleton skeleton-btn"></div>
                                </div>
                            </div>
                            <div class="skeleton-item">
                                <div class="skeleton-info">
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-stats"></div>
                                </div>
                                <div class="skeleton-actions">
                                    <div class="skeleton skeleton-btn"></div>
                                    <div class="skeleton skeleton-btn"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const fileName = document.getElementById('file-name');
        const generateBtn = document.getElementById('generate-btn');
        const inputStatus = document.getElementById('input-status');
        const errorBox = document.getElementById('error-box');
        const errorMessage = document.getElementById('error-message');
        const progressEmpty = document.getElementById('progress-empty');
        const progressContent = document.getElementById('progress-content');
        const progressBar = document.getElementById('progress-bar');
        const progressStatus = document.getElementById('progress-status');
        const progressStatusIndicator = document.getElementById('progress-status-indicator');
        const progressLog = document.getElementById('progress-log');
        const resultSection = document.getElementById('result-section');
        const resultCount = document.getElementById('result-count');
        const fileList = document.getElementById('file-list');
        const downloadAllBtn = document.getElementById('download-all-btn');

        let selectedFile = null;
        const successOverlay = document.getElementById('success-overlay');
        const successSubtitle = document.getElementById('success-subtitle');

        // CSVプレビュー関連
        const previewModal = document.getElementById('preview-modal');
        const previewClose = document.getElementById('preview-close');
        const previewCancel = document.getElementById('preview-cancel');
        const previewProceed = document.getElementById('preview-proceed');

        // CSVの1行をパースするヘルパー関数（引用符対応）
        function parseCSVLine(line) {
            const values = [];
            let current = '';
            let inQuotes = false;

            for (let j = 0; j < line.length; j++) {
                const char = line[j];
                if (char === '"') {
                    inQuotes = !inQuotes;
                } else if (char === ',' && !inQuotes) {
                    values.push(current.trim().replace(/^"|"$/g, ''));
                    current = '';
                } else {
                    current += char;
                }
            }
            values.push(current.trim().replace(/^"|"$/g, ''));

            return values;
        }

        // CSVパース関数（引用符内の改行に対応）
        function parseCSV(text) {
            // UTF-8 BOM除去
            if (text.charCodeAt(0) === 0xFEFF) {
                text = text.slice(1);
            }

            // 引用符内の改行を保持しながら行を分割
            const csvRows = [];
            let currentRow = '';
            let inQuotes = false;

            for (let i = 0; i < text.length; i++) {
                const char = text[i];

                if (char === '"') {
                    inQuotes = !inQuotes;
                    currentRow += char;
                } else if ((char === '\n' || (char === '\r' && text[i+1] === '\n')) && !inQuotes) {
                    // 引用符外の改行 = 行の終わり
                    if (currentRow.trim()) {
                        csvRows.push(currentRow);
                    }
                    currentRow = '';
                    if (char === '\r') i++; // \r\n の場合は \n もスキップ
                } else if (char === '\r' && !inQuotes) {
                    // 単独の \r も行の終わり
                    if (currentRow.trim()) {
                        csvRows.push(currentRow);
                    }
                    currentRow = '';
                } else {
                    currentRow += char;
                }
            }
            // 最後の行
            if (currentRow.trim()) {
                csvRows.push(currentRow);
            }

            if (csvRows.length < 2) return { headers: [], rows: [] };

            // ヘッダー行も引用符対応でパース
            const headers = parseCSVLine(csvRows[0]);
            const rows = [];

            for (let i = 1; i < csvRows.length; i++) {
                const values = parseCSVLine(csvRows[i]);

                // 全ての値が空かどうかをチェック（バックエンドのarray_filter相当）
                const hasNonEmptyValue = values.some(v => v.trim() !== '');
                if (values.length > 0 && hasNonEmptyValue) {
                    const row = {};
                    headers.forEach((h, idx) => {
                        row[h] = values[idx] || '';
                    });
                    rows.push({ line: i + 1, data: row });
                }
            }

            return { headers, rows };
        }

        // CSVプレビュー表示
        function showCSVPreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const { headers, rows } = parseCSV(text);

                const warnings = [];
                const skipped = [];  // Nameが#で始まらない行（バックエンドでスキップされる）
                let validCount = 0;

                // バリデーション（バックエンドのlyly.phpと同じロジック）
                rows.forEach((row, idx) => {
                    const name = row.data['Name'] || '';
                    const orderId = row.data['Order ID'] || row.data['OrderID'] || '';

                    // Nameが空の行は完全にスキップ（空白行・フォーマット上の余分な行）
                    if (!name.trim()) {
                        return;  // 警告にも含めない
                    }

                    // バックエンドと同じ: Nameが#で始まらない行はスキップ（注文としてカウントしない）
                    if (!name.startsWith('#')) {
                        skipped.push({ line: row.line, message: `Nameが#で始まっていません: "${name}"` });
                        return;  // この行は注文件数に含めない
                    }

                    // Nameが#で始まる行は注文としてカウント
                    if (!orderId || isNaN(orderId)) {
                        warnings.push({ line: row.line, message: `Order IDが不正: "${orderId}"` });
                        // 警告だが、バックエンドは処理するので注文としてはカウント（有効にはカウントしない）
                    } else {
                        validCount++;  // 完全に有効な行のみカウント
                    }
                });

                // 統計を更新
                // 注文件数 = Nameが#で始まる行の数 = 有効 + Order ID警告
                const orderCount = validCount + warnings.length;
                document.querySelector('#preview-stat-total .preview-stat-value').textContent = orderCount;
                document.querySelector('#preview-stat-valid .preview-stat-value').textContent = validCount;

                // 警告 + スキップを合計して表示
                const allWarnings = [...warnings, ...skipped];
                const warningStatEl = document.getElementById('preview-stat-warning');
                warningStatEl.querySelector('.preview-stat-value').textContent = allWarnings.length;
                warningStatEl.className = 'preview-stat' + (allWarnings.length > 0 ? ' warning' : '');

                // 注文一覧を表示（Nameが#で始まる行のみ = バックエンドで処理される注文）
                const ordersEl = document.getElementById('preview-orders');
                ordersEl.innerHTML = '';
                const validOrders = rows.filter(row => (row.data['Name'] || '').startsWith('#'));
                validOrders.slice(0, 10).forEach(row => {
                    const name = row.data['Name'] || '(不明)';
                    const orderId = row.data['Order ID'] || row.data['OrderID'] || '';
                    const hasValidOrderId = orderId && !isNaN(orderId);
                    const item = document.createElement('div');
                    item.className = 'preview-list-item ' + (hasValidOrderId ? 'success' : 'warning');
                    // XSS対策: textContentを使用
                    const iconSpan = document.createElement('span');
                    iconSpan.textContent = hasValidOrderId ? '✓' : '⚠';
                    const nameSpan = document.createElement('span');
                    nameSpan.textContent = name;
                    item.appendChild(iconSpan);
                    item.appendChild(nameSpan);
                    ordersEl.appendChild(item);
                });

                if (validOrders.length > 10) {
                    const more = document.createElement('div');
                    more.className = 'preview-list-item';
                    const dotSpan = document.createElement('span');
                    dotSpan.textContent = '...';
                    const countSpan = document.createElement('span');
                    countSpan.textContent = `他 ${validOrders.length - 10} 件`;
                    more.appendChild(dotSpan);
                    more.appendChild(countSpan);
                    ordersEl.appendChild(more);
                }

                // 警告を表示（スキップ行も含む）
                const warningsSection = document.getElementById('preview-warnings-section');
                const warningsEl = document.getElementById('preview-warnings');
                if (allWarnings.length > 0) {
                    warningsSection.style.display = 'block';
                    warningsEl.innerHTML = '';
                    allWarnings.slice(0, 5).forEach(w => {
                        const item = document.createElement('div');
                        item.className = 'preview-list-item warning';
                        // XSS対策: textContentを使用
                        const lineSpan = document.createElement('span');
                        lineSpan.textContent = `行${w.line}:`;
                        const msgSpan = document.createElement('span');
                        msgSpan.textContent = w.message;
                        item.appendChild(lineSpan);
                        item.appendChild(msgSpan);
                        warningsEl.appendChild(item);
                    });
                    if (allWarnings.length > 5) {
                        const more = document.createElement('div');
                        more.className = 'preview-list-item warning';
                        const dotSpan = document.createElement('span');
                        dotSpan.textContent = '...';
                        const countSpan = document.createElement('span');
                        countSpan.textContent = `他 ${allWarnings.length - 5} 件の警告`;
                        more.appendChild(dotSpan);
                        more.appendChild(countSpan);
                        warningsEl.appendChild(more);
                    }
                } else {
                    warningsSection.style.display = 'none';
                }

                // モーダルを表示
                previewModal.classList.add('active');
            };
            reader.readAsText(file);
        }

        // プレビューモーダルを閉じる
        function closePreview() {
            previewModal.classList.remove('active');
        }

        previewClose.addEventListener('click', closePreview);
        previewCancel.addEventListener('click', () => {
            closePreview();
            // ファイル選択をリセット
            selectedFile = null;
            fileName.textContent = '';
            dropZone.classList.remove('has-file');
            generateBtn.disabled = true;
            inputStatus.classList.remove('success');
        });

        previewProceed.addEventListener('click', () => {
            closePreview();
            // 生成を開始
            generateBtn.click();
        });

        previewModal.addEventListener('click', (e) => {
            if (e.target === previewModal) closePreview();
        });

        // ステージ管理
        function resetStages() {
            document.querySelectorAll('.stage-item').forEach(item => {
                item.classList.remove('active', 'completed');
            });
        }

        function setStage(stageName) {
            const stages = ['parse', 'download', 'temp', 'draft', 'zip'];
            const currentIndex = stages.indexOf(stageName);

            document.querySelectorAll('.stage-item').forEach((item, index) => {
                const stage = item.dataset.stage;
                const stageIndex = stages.indexOf(stage);

                if (stageIndex < currentIndex) {
                    item.classList.remove('active');
                    item.classList.add('completed');
                } else if (stageIndex === currentIndex) {
                    item.classList.add('active');
                    item.classList.remove('completed');
                } else {
                    item.classList.remove('active', 'completed');
                }
            });
        }

        function completeAllStages() {
            document.querySelectorAll('.stage-item').forEach(item => {
                item.classList.remove('active');
                item.classList.add('completed');
            });
        }

        // サクセスオーバーレイ
        function showSuccessOverlay(fileCount) {
            successSubtitle.textContent = `${fileCount}件のPDFを生成しました`;
            successOverlay.classList.add('active');

            // 2秒後に自動で閉じる
            setTimeout(() => {
                successOverlay.classList.remove('active');
            }, 2000);
        }

        successOverlay.addEventListener('click', () => {
            successOverlay.classList.remove('active');
        });

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => { if (e.target.files.length > 0) handleFile(e.target.files[0]); });

        function handleFile(file) {
            if (!file.name.endsWith('.csv')) { showError('CSVファイルを選択してください'); return; }
            selectedFile = file;
            fileName.textContent = file.name;
            dropZone.classList.add('has-file');
            generateBtn.disabled = false;
            inputStatus.classList.add('success');
            hideError();

            // CSVプレビューを表示 (Error Prevention)
            showCSVPreview(file);
        }

        // エラーガイダンス定義
        const errorGuides = {
            'テンプレートなし': {
                tips: [
                    '商品タイトルが正しいか確認してください',
                    'サイズ（S/M）が指定されているか確認してください',
                    'サポートされている商品タイプか確認してください'
                ],
                canRetry: false
            },
            '画像': {
                tips: [
                    '画像URLが有効か確認してください',
                    'URLが https:// で始まっているか確認してください',
                    '画像ファイルがサーバー上で公開されているか確認してください'
                ],
                canRetry: true
            },
            'CSV': {
                tips: [
                    'CSVファイルがUTF-8エンコードか確認してください',
                    'ヘッダー行が正しいか確認してください（Name, Order ID等）',
                    'Excelで保存する場合は「CSV UTF-8」形式を選択してください'
                ],
                canRetry: false
            },
            'メモリ': {
                tips: [
                    '注文件数を減らして再試行してください',
                    '画像サイズが大きすぎる可能性があります',
                    '複数回に分けて処理してください'
                ],
                canRetry: true
            }
        };

        function showError(msg) {
            errorMessage.textContent = msg;
            errorBox.classList.add('active');

            // エラーガイダンスを表示
            const guidanceEl = document.getElementById('error-guidance');
            const guidanceList = document.getElementById('error-guidance-list');
            const retryBtn = document.getElementById('error-retry-btn');

            let matchedGuide = null;
            for (const [key, guide] of Object.entries(errorGuides)) {
                if (msg.includes(key)) {
                    matchedGuide = guide;
                    break;
                }
            }

            if (matchedGuide) {
                guidanceEl.style.display = 'block';
                guidanceList.innerHTML = '';
                matchedGuide.tips.forEach(tip => {
                    const li = document.createElement('li');
                    li.textContent = tip;
                    guidanceList.appendChild(li);
                });
                retryBtn.style.display = matchedGuide.canRetry ? 'inline-block' : 'none';
            } else {
                // デフォルトガイダンス
                guidanceEl.style.display = 'block';
                guidanceList.innerHTML = '<li>CSVファイルの形式を確認してください</li><li>問題が続く場合はログを確認してください</li>';
                retryBtn.style.display = 'inline-block';
            }
        }

        function hideError() {
            errorBox.classList.remove('active');
            document.getElementById('error-guidance').style.display = 'none';
        }

        // 再試行ボタン
        document.getElementById('error-retry-btn').addEventListener('click', () => {
            hideError();
            if (selectedFile) {
                generateBtn.click();
            }
        });
        function addLog(msg, type = '') {
            const line = document.createElement('div');
            line.textContent = `> ${msg}`;
            if (type) line.classList.add(type);
            progressLog.appendChild(line);
            progressLog.scrollTop = progressLog.scrollHeight;
        }

        generateBtn.addEventListener('click', async () => {
            if (!selectedFile) return;
            hideError();
            progressEmpty.style.display = 'none';
            progressContent.classList.add('active');
            progressStatusIndicator.classList.add('active');
            progressBar.classList.add('shimmer');
            resultSection.style.display = 'none';
            progressLog.innerHTML = '';
            generateBtn.disabled = true;

            // ステージをリセット
            resetStages();

            const processType = document.querySelector('input[name="process"]:checked').value;
            addLog(`ファイル: ${selectedFile.name}`);
            addLog(`タイプ: ${processType}`);
            addLog('処理開始...');

            // 最初のステージを開始 (Labor Illusion)
            setStage('parse');
            progressBar.style.width = '10%';
            progressBar.textContent = '10%';

            const formData = new FormData();
            formData.append('csv', selectedFile);
            formData.append('process', processType);

            // ステージ進行のシミュレーション (Labor Illusion)
            // try外に宣言してfinally節でもクリア可能にする
            const stageTimers = [];

            try {

                // 処理中にステージを順次進める
                stageTimers.push(setTimeout(() => {
                    setStage('download');
                    progressBar.style.width = '25%';
                    progressBar.textContent = '25%';
                    progressStatus.textContent = '画像をダウンロード中...';
                }, 500));

                stageTimers.push(setTimeout(() => {
                    setStage('temp');
                    progressBar.style.width = '50%';
                    progressBar.textContent = '50%';
                    progressStatus.textContent = '個別PDFを生成中...';
                }, 1500));

                stageTimers.push(setTimeout(() => {
                    setStage('draft');
                    progressBar.style.width = '75%';
                    progressBar.textContent = '75%';
                    progressStatus.textContent = '印刷用PDFを作成中...';
                }, 3000));

                stageTimers.push(setTimeout(() => {
                    setStage('zip');
                    progressBar.style.width = '90%';
                    progressBar.textContent = '90%';
                    progressStatus.textContent = 'ZIPを作成中...';
                }, 4500));

                const response = await fetch('api.php?action=generate', { method: 'POST', body: formData });
                const result = await response.json();

                if (!result.success) throw new Error(result.error || '不明なエラー');

                // 全ステージ完了
                completeAllStages();
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
                progressBar.classList.remove('shimmer');
                progressStatus.textContent = '完了';
                progressStatusIndicator.classList.remove('active');
                progressStatusIndicator.classList.add('success');

                if (result.logs) result.logs.forEach(log => addLog(log.message, log.type));
                addLog('処理完了', 'success');

                // サクセスオーバーレイを表示
                showSuccessOverlay(result.files ? result.files.length : 0);

                showResults(result.files, result.zipFilePath, result.outputFolder);

                // 履歴を再読み込み
                loadHistory();
            } catch (err) {
                addLog(`エラー: ${err.message}`, 'error');
                showError(err.message);
                progressBar.classList.remove('shimmer');
                progressStatusIndicator.classList.remove('active');
                progressStatusIndicator.classList.add('error');
            } finally {
                // タイマーをクリア（エラー時も確実にクリア）
                stageTimers.forEach(t => clearTimeout(t));
                generateBtn.disabled = false;
            }
        });

        function showResults(files, zipFilePath, outputFolder) {
            resultSection.style.display = 'block';
            const tempFiles = files.filter(f => f.type === 'temp');
            const draftFiles = files.filter(f => f.type === 'draft');

            let countText = `完了: <strong>${files.length}</strong>件 (個別: ${tempFiles.length} / 印刷: ${draftFiles.length})`;
            if (outputFolder) {
                countText += `<br><span style="font-size: 0.75rem; color: var(--text-muted); font-family: 'IBM Plex Mono', monospace;">📁 ${outputFolder}</span>`;
            }
            resultCount.innerHTML = countText;

            // ZIPダウンロードボタンの設定
            if (zipFilePath) {
                downloadAllBtn.style.display = 'inline-block';
                downloadAllBtn.onclick = () => {
                    window.location.href = zipFilePath;
                };
            } else {
                downloadAllBtn.style.display = 'none';
            }

            fileList.innerHTML = '';
            files.forEach(file => {
                const item = document.createElement('div');
                item.className = 'file-item';
                const nameSpan = document.createElement('span');
                nameSpan.className = 'file-name';
                const typeSpan = document.createElement('span');
                typeSpan.className = 'file-type ' + (file.type === 'temp' ? 'temp' : 'draft');
                typeSpan.textContent = file.type;
                nameSpan.appendChild(typeSpan);
                nameSpan.appendChild(document.createTextNode(file.name));
                // プレビューボタン
                const previewBtn = document.createElement('button');
                previewBtn.className = 'btn btn-preview';
                previewBtn.textContent = 'Preview';
                previewBtn.addEventListener('click', () => showPdfPreview(file.path));

                // ダウンロードボタン
                const downloadLink = document.createElement('a');
                downloadLink.href = 'api.php?action=download&file=' + encodeURIComponent(file.path);
                downloadLink.className = 'btn btn-download';
                downloadLink.download = '';
                downloadLink.textContent = 'DL';

                item.appendChild(nameSpan);
                item.appendChild(previewBtn);
                item.appendChild(downloadLink);
                fileList.appendChild(item);
            });
        }

        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        themeToggle.textContent = currentTheme === 'light' ? '🌙' : '☀️';

        themeToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            themeToggle.textContent = next === 'light' ? '🌙' : '☀️';
        });

        // 履歴機能
        async function loadHistory() {
            try {
                const response = await fetch('api.php?action=history');
                const result = await response.json();

                if (!result.success || !result.histories || result.histories.length === 0) {
                    document.getElementById('history-list').innerHTML =
                        '<div style="padding: 20px; text-align: center; color: var(--text-muted);">履歴がありません</div>';
                    return;
                }

                const historyList = document.getElementById('history-list');
                historyList.innerHTML = '';

                result.histories.forEach(history => {
                    const item = document.createElement('div');
                    item.className = 'history-item';
                    item.dataset.timestamp = history.timestamp;

                    // 履歴情報セクション
                    const historyInfo = document.createElement('div');
                    historyInfo.className = 'history-info';

                    const datetime = document.createElement('div');
                    datetime.className = 'history-datetime';
                    datetime.textContent = '📅 ' + history.datetimeFormatted;

                    const stats = document.createElement('div');
                    stats.className = 'history-stats';
                    stats.textContent = `印刷用: ${history.draftCount}件 | 個別: ${history.tempCount}件`;

                    historyInfo.appendChild(datetime);
                    historyInfo.appendChild(stats);

                    // アクションボタンセクション
                    const actions = document.createElement('div');
                    actions.className = 'history-actions';

                    const detailBtn = document.createElement('button');
                    detailBtn.className = 'btn btn-download';
                    detailBtn.textContent = '詳細';
                    detailBtn.addEventListener('click', () => toggleHistoryDetail(history.timestamp, item));
                    actions.appendChild(detailBtn);

                    if (history.zipFile) {
                        const zipLink = document.createElement('a');
                        zipLink.href = history.folderPath + history.zipFile;
                        zipLink.className = 'btn btn-download';
                        zipLink.textContent = 'ZIP DL';
                        zipLink.download = '';
                        actions.appendChild(zipLink);
                    }

                    item.appendChild(historyInfo);
                    item.appendChild(actions);
                    historyList.appendChild(item);

                    // 詳細パネルを追加
                    const detailPanel = document.createElement('div');
                    detailPanel.className = 'history-detail';
                    detailPanel.id = `detail-${history.timestamp}`;
                    detailPanel.innerHTML = `
                        <div class="history-detail-content">
                            <div class="history-detail-loading">読み込み中...</div>
                            <div class="history-detail-files"></div>
                        </div>
                    `;
                    historyList.appendChild(detailPanel);
                });
            } catch (err) {
                console.error('履歴取得エラー:', err);
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'padding: 20px; text-align: center; color: var(--error);';
                errorDiv.textContent = '履歴の読み込みに失敗しました';

                const errorDetail = document.createElement('small');
                errorDetail.style.cssText = 'display: block; margin-top: 8px; color: var(--text-muted); font-size: 0.75rem;';
                errorDetail.textContent = err.message;

                errorDiv.appendChild(errorDetail);
                document.getElementById('history-list').innerHTML = '';
                document.getElementById('history-list').appendChild(errorDiv);
            }
        }

        // アコーディオン形式で履歴詳細を展開/折りたたみ
        async function toggleHistoryDetail(timestamp, historyItem) {
            const detailPanel = document.getElementById(`detail-${timestamp}`);
            const loading = detailPanel.querySelector('.history-detail-loading');
            const filesContainer = detailPanel.querySelector('.history-detail-files');

            // 既に開いている場合は閉じる
            if (detailPanel.classList.contains('expanded')) {
                detailPanel.classList.remove('expanded');
                historyItem.classList.remove('expanded');
                return;
            }

            // 展開
            detailPanel.classList.add('expanded');
            historyItem.classList.add('expanded');

            // 既にロード済みならスキップ
            if (filesContainer.children.length > 0) {
                return;
            }

            // ローディング表示
            loading.style.display = 'block';
            filesContainer.innerHTML = '';

            try {
                const response = await fetch(`api.php?action=history_detail&timestamp=${encodeURIComponent(timestamp)}`);
                const result = await response.json();

                loading.style.display = 'none';

                if (!result.success) {
                    const errorDiv = document.createElement('div');
                    errorDiv.style.color = 'var(--error)';
                    errorDiv.textContent = result.error;
                    filesContainer.appendChild(errorDiv);
                    return;
                }

                // ファイルをtemp/とdraft/に分類
                const tempFiles = result.files.filter(f => f.type === 'temp');
                const draftFiles = result.files.filter(f => f.type === 'draft');

                if (result.files.length === 0) {
                    filesContainer.innerHTML = '<div style="color: var(--text-muted);">ファイルがありません</div>';
                    return;
                }

                // タブを作成
                const tabsContainer = document.createElement('div');
                tabsContainer.className = 'history-detail-tabs';

                const tempTab = document.createElement('button');
                tempTab.className = 'history-detail-tab active';
                tempTab.textContent = `個別 (${tempFiles.length})`;
                tempTab.dataset.tab = 'temp';

                const draftTab = document.createElement('button');
                draftTab.className = 'history-detail-tab';
                draftTab.textContent = `印刷用 (${draftFiles.length})`;
                draftTab.dataset.tab = 'draft';

                tabsContainer.appendChild(tempTab);
                tabsContainer.appendChild(draftTab);
                filesContainer.appendChild(tabsContainer);

                // ファイル一覧コンテナを作成
                const tempContent = document.createElement('div');
                tempContent.className = 'history-detail-tab-content active';
                tempContent.dataset.content = 'temp';

                const draftContent = document.createElement('div');
                draftContent.className = 'history-detail-tab-content';
                draftContent.dataset.content = 'draft';

                // ファイルアイテムを生成する関数
                function createFileItem(file) {
                    const item = document.createElement('div');
                    item.className = 'file-item';

                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'file-name';
                    nameSpan.textContent = file.name;

                    // プレビューボタン
                    const previewBtn = document.createElement('button');
                    previewBtn.className = 'btn btn-preview';
                    previewBtn.textContent = 'Preview';
                    previewBtn.addEventListener('click', () => showPdfPreview(file.path));

                    // ダウンロードボタン
                    const downloadLink = document.createElement('a');
                    downloadLink.href = 'api.php?action=download&file=' + encodeURIComponent(file.path);
                    downloadLink.className = 'btn btn-download';
                    downloadLink.download = '';
                    downloadLink.textContent = 'DL';

                    item.appendChild(nameSpan);
                    item.appendChild(previewBtn);
                    item.appendChild(downloadLink);
                    return item;
                }

                // 個別PDFを追加
                if (tempFiles.length > 0) {
                    tempFiles.forEach(file => tempContent.appendChild(createFileItem(file)));
                } else {
                    tempContent.innerHTML = '<div style="color: var(--text-muted);">個別PDFがありません</div>';
                }

                // 印刷用PDFを追加
                if (draftFiles.length > 0) {
                    draftFiles.forEach(file => draftContent.appendChild(createFileItem(file)));
                } else {
                    draftContent.innerHTML = '<div style="color: var(--text-muted);">印刷用PDFがありません</div>';
                }

                filesContainer.appendChild(tempContent);
                filesContainer.appendChild(draftContent);

                // タブ切り替え処理
                [tempTab, draftTab].forEach(tab => {
                    tab.addEventListener('click', () => {
                        // タブのアクティブ状態を切り替え
                        tempTab.classList.toggle('active', tab === tempTab);
                        draftTab.classList.toggle('active', tab === draftTab);
                        // コンテンツの表示を切り替え
                        tempContent.classList.toggle('active', tab === tempTab);
                        draftContent.classList.toggle('active', tab === draftTab);
                    });
                });
            } catch (err) {
                loading.style.display = 'none';
                const errorDiv = document.createElement('div');
                errorDiv.style.color = 'var(--error)';
                errorDiv.textContent = '読み込みエラー: ' + err.message;
                filesContainer.appendChild(errorDiv);
            }
        }

        // 後方互換性のため残す（直接使用されなくなった）
        async function showHistoryDetail(timestamp) {
            const historyItem = document.querySelector(`[data-timestamp="${timestamp}"]`);
            if (historyItem) {
                toggleHistoryDetail(timestamp, historyItem);
            }
        }

        // ページ読み込み時に履歴を取得
        window.addEventListener('DOMContentLoaded', () => {
            loadHistory();
        });

        // =========================================
        // PDF.js プレビュー機能
        // =========================================
        let pdfDoc = null;
        let currentPage = 1;
        let currentScale = 1.0;
        let currentPdfPath = null;

        const pdfPreviewModal = document.getElementById('pdf-preview-modal');
        const pdfPreviewTitle = document.getElementById('pdf-preview-title');
        const pdfCanvas = document.getElementById('pdf-canvas');
        const pdfLoading = document.getElementById('pdf-loading');
        const pdfPageInfo = document.getElementById('pdf-page-info');
        const pdfZoomLevel = document.getElementById('pdf-zoom-level');
        const pdfPrevPageBtn = document.getElementById('pdf-prev-page');
        const pdfNextPageBtn = document.getElementById('pdf-next-page');
        const pdfZoomInBtn = document.getElementById('pdf-zoom-in');
        const pdfZoomOutBtn = document.getElementById('pdf-zoom-out');
        const pdfDownloadBtn = document.getElementById('pdf-download-btn');

        // PDF.jsのワーカー設定
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        } else {
            console.error('PDF.js library not loaded - preview feature will not work');
        }

        async function showPdfPreview(filePath) {
            currentPdfPath = filePath;
            pdfPreviewModal.classList.add('active');
            pdfCanvas.style.display = 'none';
            pdfLoading.style.display = 'flex';
            pdfPreviewTitle.textContent = filePath.split('/').pop();
            pdfDownloadBtn.href = 'api.php?action=download&file=' + encodeURIComponent(filePath);

            try {
                const url = 'api.php?action=preview&file=' + encodeURIComponent(filePath);
                pdfDoc = await pdfjsLib.getDocument(url).promise;
                currentPage = 1;
                currentScale = 1.0;
                await renderPage(currentPage);
                pdfLoading.style.display = 'none';
                pdfCanvas.style.display = 'block';
                updateControls();
            } catch (err) {
                console.error('PDFプレビューエラー:', err);
                pdfLoading.innerHTML = '<span style="color: var(--error);">PDFの読み込みに失敗しました</span>';
            }
        }

        async function renderPage(num) {
            if (!pdfDoc) return;

            const page = await pdfDoc.getPage(num);
            const ctx = pdfCanvas.getContext('2d');
            const viewport = page.getViewport({ scale: currentScale });

            pdfCanvas.width = viewport.width;
            pdfCanvas.height = viewport.height;

            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;

            updateControls();
        }

        function updateControls() {
            if (!pdfDoc) return;
            pdfPageInfo.textContent = currentPage + ' / ' + pdfDoc.numPages;
            pdfZoomLevel.textContent = Math.round(currentScale * 100) + '%';
            pdfPrevPageBtn.disabled = currentPage <= 1;
            pdfNextPageBtn.disabled = currentPage >= pdfDoc.numPages;
            pdfZoomOutBtn.disabled = currentScale <= 0.25;
            pdfZoomInBtn.disabled = currentScale >= 3.0;
        }

        function closePdfPreview() {
            pdfPreviewModal.classList.remove('active');
            if (pdfDoc) {
                pdfDoc.destroy();  // PDF.jsリソース解放
            }
            pdfDoc = null;
            currentPdfPath = null;
            pdfLoading.innerHTML = '<div class="pdf-loading-spinner"></div><span>読み込み中...</span>';
        }

        // イベントリスナー
        pdfPrevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        });

        pdfNextPageBtn.addEventListener('click', () => {
            if (pdfDoc && currentPage < pdfDoc.numPages) {
                currentPage++;
                renderPage(currentPage);
            }
        });

        pdfZoomInBtn.addEventListener('click', () => {
            if (currentScale < 3.0) {
                currentScale += 0.25;
                renderPage(currentPage);
            }
        });

        pdfZoomOutBtn.addEventListener('click', () => {
            if (currentScale > 0.25) {
                currentScale -= 0.25;
                renderPage(currentPage);
            }
        });

        document.getElementById('pdf-preview-close').addEventListener('click', closePdfPreview);
        document.getElementById('pdf-preview-close-btn').addEventListener('click', closePdfPreview);

        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && pdfPreviewModal.classList.contains('active')) {
                closePdfPreview();
            }
        });

        // モーダル背景クリックで閉じる
        pdfPreviewModal.addEventListener('click', (e) => {
            if (e.target === pdfPreviewModal) {
                closePdfPreview();
            }
        });
    </script>
</body>
</html>
