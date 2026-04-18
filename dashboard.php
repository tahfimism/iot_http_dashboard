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
    <title>IoT Manager</title>
    <style>
        :root {
            --primary: #00e676;
            --bg: #0a0e27;
            --card: #1a1f3a;
            --accent: #2979ff;
            --danger: #ff5252;
            --text-muted: #a0a0a0;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, #1a2847 50%, #0f1d3a 100%);
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
            background: radial-gradient(ellipse at 20% 50%, rgba(41, 121, 255, 0.08) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 80%, rgba(0, 230, 118, 0.05) 0%, transparent 50%);
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
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-link:hover {
            background: rgba(255, 82, 82, 0.1);
            color: #ff8a65;
        }

        .header, .panel {
            background: linear-gradient(135deg, #1e2749, #16213e);
            border: 1px solid rgba(41, 121, 255, 0.15);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
        }

        .header-note {
            margin: 8px 0 14px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        select, input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(41, 121, 255, 0.2);
            color: #fff;
            border-radius: 10px;
            padding: 11px 14px;
            margin-top: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        select:hover, input:hover {
            border-color: rgba(41, 121, 255, 0.4);
            background: rgba(255, 255, 255, 0.08);
        }

        select:focus, input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(41, 121, 255, 0.1);
        }

        .conn-info {
            display: none;
            background: linear-gradient(135deg, rgba(26, 35, 126, 0.3), rgba(41, 121, 255, 0.1));
            border: 1px solid rgba(41, 121, 255, 0.25);
            border-left: 5px solid var(--accent);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            font-family: 'Courier New', monospace;
            box-shadow: 0 4px 12px rgba(41, 121, 255, 0.1);
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
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .device-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .device-card {
            border: 1.5px solid rgba(41, 121, 255, 0.2);
            background: linear-gradient(135deg, rgba(30, 39, 73, 0.6), rgba(26, 31, 58, 0.4));
            color: #fff;
            border-radius: 14px;
            padding: 14px;
            cursor: pointer;
            text-align: left;
            transition: all 0.3s ease;
            min-height: 96px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .device-card:hover {
            border-color: rgba(41, 121, 255, 0.5);
            background: linear-gradient(135deg, rgba(30, 39, 73, 0.8), rgba(26, 31, 58, 0.6));
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(41, 121, 255, 0.15);
        }

        .device-card.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(0, 230, 118, 0.2), rgba(26, 63, 47, 0.5));
            box-shadow: 0 0 0 1.5px rgba(0, 230, 118, 0.3) inset, 0 12px 28px rgba(0, 230, 118, 0.15);
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
            background: rgba(41, 121, 255, 0.15);
            border: 1px solid rgba(41, 121, 255, 0.4);
            color: #b9d3ff;
            transition: all 0.3s ease;
        }

        .device-card.active .device-card-icon {
            background: rgba(0, 230, 118, 0.2);
            border-color: rgba(0, 230, 118, 0.5);
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
            border: 1px dashed rgba(41, 121, 255, 0.3);
            border-radius: 10px;
            padding: 14px;
            color: var(--text-muted);
            font-size: 0.9rem;
            background: rgba(41, 121, 255, 0.05);
        }

        .card {
            background: linear-gradient(135deg, rgba(30, 39, 73, 0.5), rgba(26, 31, 58, 0.3));
            border: 1px solid rgba(41, 121, 255, 0.15);
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            border-left: 4px solid rgba(41, 121, 255, 0.4);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .card:hover {
            border-left-color: var(--accent);
            box-shadow: 0 8px 20px rgba(41, 121, 255, 0.1);
        }

        .btn {
            width: 100%;
            margin-top: 10px;
            padding: 11px 14px;
            border: 0;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-on {
            background: linear-gradient(135deg, var(--primary), #00c853);
            color: #000;
            box-shadow: 0 8px 20px rgba(0, 230, 118, 0.25);
        }

        .btn-on:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0, 230, 118, 0.35);
        }

        .btn-off {
            background: linear-gradient(135deg, #555, #444);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-off:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #666, #555);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #ff2a54);
            color: #fff;
            box-shadow: 0 8px 20px rgba(255, 82, 82, 0.25);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(255, 82, 82, 0.35);
        }

        .btn-accent {
            background: linear-gradient(135deg, var(--accent), #1e53e5);
            color: #fff;
            box-shadow: 0 8px 20px rgba(41, 121, 255, 0.25);
        }

        .btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(41, 121, 255, 0.35);
        }

        .btn:active {
            transform: translateY(0);
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
            background: linear-gradient(135deg, rgba(20, 24, 40, 0.5), rgba(15, 29, 58, 0.3));
            border: 1px dashed rgba(41, 121, 255, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
            z-index: 9999;
            border: 1px solid rgba(41, 121, 255, 0.2);
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

        @media (max-width: 700px) {
            .grid { grid-template-columns: 1fr; }
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

            const title = document.createElement('small');
            title.style.color = '#888';
            title.textContent = key;

            const valueLabel = document.createElement('h2');
            valueLabel.style.margin = '10px 0';
            valueLabel.textContent = typeof value === 'object' ? JSON.stringify(value) : String(value);

            card.appendChild(title);
            card.appendChild(valueLabel);

            if (fieldType === 'binary' && isBinaryValue(value)) {
                const numericValue = String(value) === '1' || value === true ? 1 : 0;
                const nextValue = numericValue === 1 ? 0 : 1;

                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'btn ' + (nextValue === 1 ? 'btn-on' : 'btn-off');
                toggleBtn.textContent = nextValue === 1 ? 'ON' : 'OFF';
                toggleBtn.addEventListener('click', function () {
                    setFeatureValue(key, nextValue, 'binary');
                });

                card.appendChild(toggleBtn);
            } else {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'mini-input';
                input.value = typeof value === 'object' ? JSON.stringify(value) : String(value);

                const updateBtn = document.createElement('button');
                updateBtn.type = 'button';
                updateBtn.className = 'btn btn-on';
                updateBtn.textContent = 'UPDATE';
                updateBtn.addEventListener('click', function () {
                    setFeatureValue(key, input.value, fieldType);
                });

                card.appendChild(input);
                card.appendChild(updateBtn);
            }

            const removeBtn = document.createElement('a');
            removeBtn.className = 'text-action text-danger';
            removeBtn.href = '#';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                removeFeature(key);
            });

            card.appendChild(removeBtn);
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