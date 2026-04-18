<?php
require_once __DIR__ . '/api/auth_helpers.php';
require_page_auth();
$loggedInUserName = get_logged_in_user_name();
$loggedInUserId = get_logged_in_user_id();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IoT Manager</title>
    <style>
        :root {
            --primary: #00e676;
            --bg: #0a0a0a;
            --card: #1a1a1a;
            --accent: #0d9488;
            --danger: #ff5252;
            --text-muted: #a0a0a0;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0f0f0f 100%);
            color: #fff;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at 20% 50%, rgba(13, 148, 136, 0.05) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 80%, rgba(0, 230, 118, 0.04) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: #cfd8dc;
        }

        .logout-link {
            color: #ffab91;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background 0.2s ease;
        }

        .logout-link:hover {
            background: rgba(255, 82, 82, 0.1);
            color: #ff8a65;
        }

        .header, .panel {
            background: rgba(22, 22, 22, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(12px);
        }

        .header-note {
            margin: 8px 0 14px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        select, input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 6px;
            padding: 12px 14px;
            margin-top: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
            appearance: none;
            -webkit-appearance: none;
        }

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23a0a0a0' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
            cursor: pointer;
        }

        option {
            background: #1a1a1a;
            color: #fff;
            padding: 10px;
        }

        select:hover, input:hover {
            border-color: rgba(13, 148, 136, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        select:focus, input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }

        .conn-info {
            display: none;
            background: rgba(13, 148, 136, 0.05);
            border: 1px solid rgba(13, 148, 136, 0.2);
            border-left: 4px solid var(--accent);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            font-family: 'Courier New', monospace;
            box-shadow: 0 2px 6px rgba(13, 148, 136, 0.05);
        }

        .url-display {
            display: block;
            margin-top: 8px;
            padding: 10px 12px;
            background: rgba(0, 0, 0, 0.3);
            color: var(--primary);
            border-radius: 8px;
            word-break: break-all;
            border: 1px solid rgba(0, 230, 118, 0.2);
            font-size: 12px;
            line-height: 1.5;
        }

        .copy-hint {
            display: block;
            margin-top: 6px;
            color: #b9c1ff;
            font-size: 12px;
        }

        .url-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .copy-url-btn {
            flex: 0 0 auto;
            width: 32px;
            height: 32px;
            border: 1px solid rgba(41, 121, 255, 0.4);
            border-radius: 8px;
            background: rgba(41, 121, 255, 0.08);
            color: var(--accent);
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .copy-url-btn:hover {
            background: rgba(41, 121, 255, 0.15);
            color: #b9d3ff;
            border-color: rgba(41, 121, 255, 0.6);
            transform: translateY(-2px);
        }

        .copy-url-btn:active {
            transform: translateY(0);
        }

        .copy-icon {
            position: relative;
            width: 12px;
            height: 12px;
            display: inline-block;
        }

        .copy-icon::before,
        .copy-icon::after {
            content: '';
            position: absolute;
            border: 1.5px solid currentColor;
            border-radius: 2px;
            width: 8px;
            height: 8px;
        }

        .copy-icon::before {
            top: 0;
            left: 0;
            opacity: 0.85;
        }

        .copy-icon::after {
            top: 3px;
            left: 3px;
            background: #0a0e27;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .device-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .device-card {
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(28, 28, 28, 0.5);
            color: #fff;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            text-align: left;
            transition: border-color 0.2s ease, background 0.2s ease;
            min-height: 90px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .device-card:hover {
            border-color: rgba(13, 148, 136, 0.4);
            background: linear-gradient(135deg, #2d2d2d, #222222);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(13, 148, 136, 0.08);
        }

        .device-card.active {
            border-color: var(--accent);
            background: rgba(13, 148, 136, 0.1);
            box-shadow: 0 0 0 1px var(--accent) inset;
        }

        .device-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .device-card-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            background: rgba(13, 148, 136, 0.12);
            border: 1px solid rgba(13, 148, 136, 0.3);
            color: #5eead4;
            transition: all 0.3s ease;
        }

        .device-card.active .device-card-icon {
            background: rgba(0, 230, 118, 0.15);
            border-color: rgba(0, 230, 118, 0.4);
            color: #c7ffd8;
        }

        .device-card-name {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            overflow-wrap: anywhere;
        }

        .device-card-sub {
            display: block;
            margin-top: 10px;
            font-size: 0.75rem;
            color: #aeb5c0;
            letter-spacing: 0.35px;
            text-transform: uppercase;
        }

        .device-empty {
            border: 1px dashed rgba(13, 148, 136, 0.25);
            border-radius: 10px;
            padding: 14px;
            color: var(--text-muted);
            font-size: 0.9rem;
            background: rgba(13, 148, 136, 0.04);
        }

        .card {
            background: rgba(28, 28, 28, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: border-color 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
        }

        .card:hover { border-color: rgba(255, 255, 255, 0.12); }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            text-transform: none;
            letter-spacing: 0.1px;
            margin: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .card-value {
            display: none;
        }

        .btn {
            width: 100%;
            margin-top: 10px;
            padding: 12px 16px;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            text-transform: uppercase;
            position: relative;
            vertical-align: middle;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }

        /* Shimmer effect removed to eliminate glow */

        .toggle-btn {
            width: 52px;
            padding: 0;
            border: none;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: background 0.2s ease;
            position: relative;
            height: 26px;
            display: block;
            margin: 8px 0 8px auto;
            overflow: hidden;
        }

        .toggle-btn::before {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #777;
            left: 2px;
            top: 2px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .toggle-btn.active {
            background: var(--primary);
        }

        .toggle-btn.active::before {
            left: calc(100% - 24px);
            background: #fff;
            box-shadow: none;
        }

        .toggle-btn:not(.active):hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .toggle-btn.active:hover {
            background: #00f881;
        }

        .number-input {
            width: 100%;
            height: 38px;
            padding: 0 12px;
            font-size: 0.9rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 4px;
            text-align: left;
            font-family: inherit;
            transition: all 0.2s ease;
            vertical-align: middle;
        }

        .number-input:hover {
            border-color: rgba(13, 148, 136, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .number-input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(13, 148, 136, 0.08);
            box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.1);
        }

        .card-actions {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            width: 100%;
            align-items: center;
        }

        .card-actions .btn {
            flex: none;
            margin: 0;
            height: 38px;
            padding: 0 16px;
            font-size: 12px;
            min-width: 80px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-on {
            background: var(--primary);
            color: #000;
        }

        .btn-on:hover {
            background: #00f881;
        }

        .btn-off {
            background: #444;
            color: #fff;
        }

        .btn-off:hover {
            background: #555;
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-danger:hover {
            background: #ff7676;
        }

        .btn-accent {
            background: var(--accent);
            color: #fff;
        }

        .btn-accent:hover {
            background: #14b8a6;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .text-action {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            border: 1px solid transparent;
            background: transparent;
            color: #cfd8dc;
            font-size: 0.75rem;
            text-decoration: none;
            cursor: pointer;
            line-height: 1.2;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-transform: uppercase;
        }

        .text-action:hover {
            color: #ffffff;
            background: rgba(207, 216, 220, 0.1);
        }

        .text-danger {
            color: #ff8a80;
        }

        .text-danger:hover {
            background: rgba(255, 82, 82, 0.1);
            color: #ff5252;
        }

        .btn-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .management {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.5), rgba(15, 15, 15, 0.3));
            border: 1px dashed rgba(13, 148, 136, 0.25);
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .device-controls {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 8px;
            margin-top: 14px;
        }

        .mini-input {
            margin-top: 10px;
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            background: linear-gradient(135deg, rgba(20, 20, 30, 0.95), rgba(10, 14, 39, 0.95));
            color: #fff;
            border-left: 4px solid var(--primary);
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            border: 1px solid rgba(13, 148, 136, 0.15);
            font-weight: 500;
            font-size: 14px;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .toast.error {
            border-left-color: var(--danger);
            border-color: rgba(255, 82, 82, 0.3);
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .header, .panel { padding: 16px; margin-bottom: 12px; }
            .grid { 
                grid-template-columns: 1fr; 
                gap: 12px;
            }
            .device-list {
                grid-template-columns: 1fr;
            }
            .topbar {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                font-size: 0.85rem;
            }
            .logout-link {
                padding: 6px 0;
            }
            .header h1 { font-size: 1.5rem; margin: 0; }
            .device-controls {
                grid-template-columns: 1fr;
            }
            .card-actions {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            .card-actions .btn {
                width: 100%;
            }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <span>Signed in as <?php echo htmlspecialchars($loggedInUserName, ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="logout-link" href="logout.php">Log out</a>
    </div>

    <div class="header">
        <h1>IOT Dashboard</h1>
        <p class="header-note">Select a device card to view and control its states.</p>
        <div id="device-list" class="device-list"></div>

        <div class="device-controls">
            <input type="text" id="new-device-id" placeholder="New device ID, for example esp32-kitchen">
            <button id="add-device-btn" class="btn btn-accent" type="button">ADD DEVICE</button>
        </div>

        <a id="delete-device-btn" class="text-action text-danger" href="#">Delete selected device</a>
    </div>

    <div id="conn-details" class="conn-info">
        <strong>ESP32 CONNECTION URL:</strong>
        <span class="copy-hint">Copy this into your serverUrl variable in Arduino IDE:</span>
        <div class="url-row">
            <code id="esp-url" class="url-display" style="margin-top:0; flex:1 1 auto;"></code>
            <button id="copy-url-btn" class="copy-url-btn" type="button" aria-label="Copy device URL" title="Copy device URL">
                <span class="copy-icon" aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <div id="features-container" class="grid"></div>

    <div id="add-panel" class="management" style="display:none;">
        <h4>Add / Update State</h4>
        <input type="text" id="new-key" placeholder="Key name, for example led_4">
        <input type="text" id="new-val" placeholder="Value, for example 1">
        <select id="new-type">
            <option value="auto">Auto (number or text)</option>
            <option value="binary">Binary (toggle)</option>
            <option value="number">Number</option>
            <option value="text">Text</option>
        </select>
        <button id="save-feature-btn" class="btn btn-on" type="button">SAVE FEATURE</button>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    const state = {
        currentDevice: '',
        toastTimer: null
    };
    const currentUserId = <?php echo (int)$loggedInUserId; ?>;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function inferTypeFromValue(value) {
        if (value === true || value === false) return 'binary';
        if (typeof value === 'number') return 'number';
        return 'text';
    }

    function isBinaryValue(value) {
        return value === 1 || value === 0 || value === true || value === false || value === '1' || value === '0';
    }

    function isEditingValue() {
        const active = document.activeElement;
        return active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT');
    }

    function showToast(message, kind) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast show' + (kind === 'error' ? ' error' : '');
        clearTimeout(state.toastTimer);
        state.toastTimer = setTimeout(function () {
            toast.className = 'toast';
        }, 1800);
    }

    async function callApi(endpoint, params) {
        const response = await fetch(endpoint + '?' + params.toString(), { cache: 'no-store' });
        const raw = await response.text();
        let data = {};

        if (raw) {
            try {
                data = JSON.parse(raw);
            } catch (error) {
                throw new Error('Non-JSON response from ' + endpoint + ': ' + raw.slice(0, 120));
            }
        }

        if (!response.ok) {
            throw new Error(data.error || ('HTTP ' + response.status + ' from ' + endpoint));
        }

        if (data.error) {
            throw new Error(data.error);
        }

        return data;
    }

    async function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const tempInput = document.createElement('textarea');
        tempInput.value = text;
        tempInput.setAttribute('readonly', 'readonly');
        tempInput.style.position = 'fixed';
        tempInput.style.left = '-9999px';
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
    }

    async function copyDeviceUrl() {
        const urlText = document.getElementById('esp-url').textContent.trim();

        if (!urlText) {
            showToast('Select a device first', 'error');
            return;
        }

        try {
            await copyTextToClipboard(urlText);
            showToast('URL copied', 'success');
        } catch (error) {
            showToast('Could not copy URL', 'error');
        }
    }

    function setPanelVisibility(visible) {
        document.getElementById('conn-details').style.display = visible ? 'block' : 'none';
        document.getElementById('add-panel').style.display = visible ? 'block' : 'none';
    }

    function renderEmpty(message) {
        const container = document.getElementById('features-container');
        container.innerHTML = '';
        const card = document.createElement('div');
        card.className = 'card';
        card.style.gridColumn = '1 / -1';
        card.style.borderLeftColor = '#777';
        card.textContent = message;
        container.appendChild(card);
    }

    function renderFeatures(payload) {
        const container = document.getElementById('features-container');
        container.innerHTML = '';

        if (payload && payload.error) {
            renderEmpty(payload.error);
            return;
        }

        const flatState = payload && payload.state && typeof payload.state === 'object' ? payload.state : {};
        const typedState = payload && payload.typed_state && typeof payload.typed_state === 'object' ? payload.typed_state : {};

        const keys = Object.keys(flatState);
        if (!keys.length) {
            renderEmpty('No current state keys for this device yet.');
            return;
        }

        keys.forEach(function (key) {
            const typedEntry = typedState[key];
            const value = typedEntry && Object.prototype.hasOwnProperty.call(typedEntry, 'value') ? typedEntry.value : flatState[key];
            const fieldType = typedEntry && typedEntry.type ? typedEntry.type : inferTypeFromValue(value);

            const card = document.createElement('div');
            card.className = 'card';

            // Top Row: Key and Remove button
            const head = document.createElement('div');
            head.className = 'card-title';
            
            const keyLabel = document.createElement('span');
            keyLabel.textContent = key;
            head.appendChild(keyLabel);

            const removeBtn = document.createElement('a');
            removeBtn.className = 'text-action text-danger';
            removeBtn.style.marginTop = '0';
            removeBtn.style.padding = '0';
            removeBtn.href = '#';
            removeBtn.innerHTML = '&times;';
            removeBtn.title = 'Remove ' + key;
            removeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                removeFeature(key);
            });
            head.appendChild(removeBtn);
            card.appendChild(head);

            if (fieldType === 'binary' && isBinaryValue(value)) {
                const numericValue = String(value) === '1' || value === true ? 1 : 0;
                const isActive = numericValue === 1;

                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'toggle-btn' + (isActive ? ' active' : '');
                toggleBtn.addEventListener('click', function () {
                    const nextValue = isActive ? 0 : 1;
                    setFeatureValue(key, nextValue, 'binary');
                });

                card.appendChild(toggleBtn);
            } else {
                const actions = document.createElement('div');
                actions.className = 'card-actions';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'number-input';
                input.value = typeof value === 'object' ? JSON.stringify(value) : String(value);
                input.placeholder = 'Value';
                actions.appendChild(input);

                const updateBtn = document.createElement('button');
                updateBtn.type = 'button';
                updateBtn.className = 'btn btn-accent';
                updateBtn.style.marginTop = '0';
                updateBtn.style.width = 'auto';
                updateBtn.textContent = 'SET';
                updateBtn.addEventListener('click', function () {
                    setFeatureValue(key, input.value, fieldType);
                });

                actions.appendChild(updateBtn);
                card.appendChild(actions);
            }

            container.appendChild(card);
        });
    }

    function renderDeviceCards(devices) {
        const list = document.getElementById('device-list');
        list.innerHTML = '';

        if (!Array.isArray(devices) || devices.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'device-empty';
            empty.textContent = 'No devices found. Add your first device below.';
            list.appendChild(empty);
            return;
        }

        devices.forEach(function (deviceId) {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'device-card' + (state.currentDevice === deviceId ? ' active' : '');
            card.setAttribute('data-device-id', deviceId);

            const head = document.createElement('div');
            head.className = 'device-card-head';

            const title = document.createElement('p');
            title.className = 'device-card-name';
            title.textContent = deviceId;

            const icon = document.createElement('span');
            icon.className = 'device-card-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M7 6V4"></path><path d="M12 6V3"></path><path d="M17 6V4"></path><path d="M7 18v2"></path><path d="M12 18v3"></path><path d="M17 18v2"></path></svg>';

            head.appendChild(title);
            head.appendChild(icon);
            card.appendChild(head);

            const sub = document.createElement('span');
            sub.className = 'device-card-sub';
            sub.textContent = state.currentDevice === deviceId ? 'Selected device' : 'Tap to open controls';
            card.appendChild(sub);

            card.addEventListener('click', function () {
                handleDeviceChange(deviceId);
            });
            list.appendChild(card);
        });
    }

    async function loadDevices() {
        try {
            const response = await fetch('api/get_devices.php', { cache: 'no-store' });
            const devices = await response.json();
            const previousDevice = state.currentDevice;

            if (previousDevice && devices.includes(previousDevice)) {
                state.currentDevice = previousDevice;
            } else {
                state.currentDevice = '';
            }

            renderDeviceCards(devices);
        } catch (error) {
            console.error('Could not load devices', error);
            showToast('Could not load devices', 'error');
        }
    }

    async function addDevice() {
        const newDeviceId = document.getElementById('new-device-id').value.trim();

        if (!newDeviceId) {
            showToast('Device ID required', 'error');
            return;
        }

        const params = new URLSearchParams({
            device_id: newDeviceId
        });

        try {
            await callApi('api/add_device.php', params);
            state.currentDevice = newDeviceId;
            await loadDevices();
            document.getElementById('new-device-id').value = '';
            handleDeviceChange(newDeviceId);
            showToast('Added device ' + newDeviceId, 'success');
        } catch (error) {
            showToast(error.message || 'Could not add device', 'error');
        }
    }

    async function deleteSelectedDevice(event) {
        if (event) {
            event.preventDefault();
        }

        if (!state.currentDevice) {
            showToast('Select a device first', 'error');
            return;
        }

        if (!confirm('Delete device ' + state.currentDevice + '? This removes all its keys.')) {
            return;
        }

        const deletingDevice = state.currentDevice;
        const params = new URLSearchParams({
            device_id: deletingDevice
        });

        try {
            await callApi('api/delete_device.php', params);
            await loadDevices();
            handleDeviceChange('');
            showToast('Deleted device ' + deletingDevice, 'success');
        } catch (error) {
            showToast(error.message || 'Could not delete device', 'error');
        }
    }

    function handleDeviceChange(deviceId) {
        if (typeof deviceId === 'string') {
            state.currentDevice = deviceId;
        }

        const cards = document.querySelectorAll('.device-card');
        cards.forEach(function (card) {
            const isActive = card.getAttribute('data-device-id') === state.currentDevice;
            card.classList.toggle('active', isActive);
        });

        if (!state.currentDevice) {
            setPanelVisibility(false);
            renderEmpty('Select a device card to view state.');
            return;
        }

        const baseUrl = window.location.origin + window.location.pathname.replace(/dashboard\.php$/, '');
        const fullUrl = baseUrl + 'api/read.php?device_id=' + encodeURIComponent(state.currentDevice) + '&user_id=' + encodeURIComponent(String(currentUserId));

        document.getElementById('esp-url').textContent = fullUrl;
        setPanelVisibility(true);
        loadFeatures();
    }

    async function loadFeatures() {
        if (!state.currentDevice) return;

        try {
            const response = await fetch('api/read.php?device_id=' + encodeURIComponent(state.currentDevice) + '&format=full', { cache: 'no-store' });
            const payload = await response.json();

            renderFeatures(payload);
        } catch (error) {
            renderEmpty('Could not load device state.');
            showToast(error.message || 'Load failed', 'error');
        }
    }

    async function setFeatureValue(key, value, fieldType) {
        if (!state.currentDevice) return;

        const params = new URLSearchParams({
            device_id: state.currentDevice,
            key: key,
            value: String(value)
        });
        if (fieldType) {
            params.set('type', fieldType);
        }

        try {
            await callApi('api/write.php', params);
            showToast('Updated ' + key + ' to ' + value, 'success');
            loadFeatures();
        } catch (error) {
            showToast(error.message || 'Update failed', 'error');
        }
    }

    async function removeFeature(key) {
        if (!confirm('Delete ' + key + '?')) return;

        const params = new URLSearchParams({
            device_id: state.currentDevice,
            key: key
        });

        try {
            await callApi('api/delete.php', params);
            showToast('Removed ' + key, 'success');
            loadFeatures();
        } catch (error) {
            showToast(error.message || 'Delete failed', 'error');
        }
    }

    async function saveFeature() {
        const key = document.getElementById('new-key').value.trim();
        const value = document.getElementById('new-val').value;
        const typeChoice = document.getElementById('new-type').value;

        if (!key) {
            showToast('Key required', 'error');
            return;
        }

        const params = new URLSearchParams({
            device_id: state.currentDevice,
            key: key,
            value: value
        });
        if (typeChoice !== 'auto') {
            params.set('type', typeChoice);
        }

        try {
            await callApi('api/write.php', params);
            document.getElementById('new-key').value = '';
            document.getElementById('new-val').value = '';
            document.getElementById('new-type').value = 'auto';
            showToast('Saved ' + key, 'success');
            loadFeatures();
        } catch (error) {
            showToast(error.message || 'Save failed', 'error');
        }
    }

    document.getElementById('save-feature-btn').addEventListener('click', saveFeature);
    document.getElementById('add-device-btn').addEventListener('click', addDevice);
    document.getElementById('delete-device-btn').addEventListener('click', deleteSelectedDevice);
    document.getElementById('copy-url-btn').addEventListener('click', copyDeviceUrl);

    loadDevices();
    handleDeviceChange('');
    setInterval(function () {
        if (state.currentDevice && !isEditingValue()) {
            loadFeatures();
        }
    }, 3000);
</script>
</body>
</html>