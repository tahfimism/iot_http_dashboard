<?php
define('SKIP_API_HEADERS', true);
require_once __DIR__ . '/api/auth_helpers.php';
require_page_auth();
$loggedInUserName = get_logged_in_user_name();
$loggedInUserId = get_logged_in_user_id();

$device_id = $_GET['device_id'] ?? '';
if ($device_id === '') {
    header('Location: dashboard.php');
    exit;
}

// Fetch user type for limits display
require_once __DIR__ . '/api/db_config.php';
$uStmt = $conn->prepare("SELECT user_type FROM iot_users WHERE id = ?");
$uStmt->bind_param("i", $loggedInUserId);
$uStmt->execute();
$uStmt->bind_result($userType);
$uStmt->fetch();
$uStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Telemetry - <?php echo htmlspecialchars($device_id); ?></title>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        h1 { margin: 0; font-size: 1.8rem; }
        .subtitle { color: var(--text-muted); margin-top: 8px; }

        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
            margin-top: 20px;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        th {
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .payload-badge {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(13, 148, 136, 0.2);
            border: 1px solid rgba(13, 148, 136, 0.3);
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.8rem;
            color: #5eead4;
        }

        .btn-danger {
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-danger:hover {
            background: var(--danger);
            color: #fff;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            opacity: 0;
            padding: 12px 20px;
            background: var(--card);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            transition: all 0.3s;
            z-index: 1000;
        }
        .toast.show { opacity: 1; transform: translateY(-10px); }

        @media (max-width: 600px) {
            h1 { font-size: 1.4rem; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <a href="dashboard.php" class="back-link">← BACK TO DASHBOARD</a>
        <div style="font-size: 0.85rem;">
            User: <strong><?php echo htmlspecialchars($loggedInUserName); ?></strong>
            <span style="color: var(--text-muted); margin: 0 8px;">|</span>
            Plan: <span style="color: #64ffda;"><?php echo strtoupper($userType); ?></span>
        </div>
    </div>

    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1><?php echo htmlspecialchars($device_id); ?></h1>
                <p class="subtitle">Historical telemetry logs and sensor visualization</p>
            </div>
            <button onclick="clearHistory()" class="btn-danger">CLEAR HISTORY</button>
        </div>

        <div id="chart-panel" class="chart-container" style="display: none;">
            <canvas id="telemetryChart"></canvas>
        </div>
        <div id="no-data" class="empty-state">No telemetry data found for this device yet.</div>
    </div>

    <div class="panel">
        <h2 style="font-size: 1.1rem; margin-top: 0;">Raw Logs</h2>
        <div id="logs-container" class="table-container">
            <table>
                <thead>
                    <tr id="table-head">
                        <th>Timestamp</th>
                        <th>Payload</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <tr><td colspan="2" style="text-align:center;">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    const deviceId = "<?php echo $device_id; ?>";
    let myChart = null;

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast show';
        setTimeout(() => t.className = 'toast', 2000);
    }

    async function clearHistory() {
        if (!confirm('Are you sure you want to permanently delete all history for this device?')) return;
        
        try {
            const res = await fetch(`api/telemetry_delete.php?device_id=${encodeURIComponent(deviceId)}`);
            const data = await res.json();
            if (data.status === 'success') {
                showToast('History cleared');
                location.reload();
            } else {
                alert(data.error || 'Failed to clear');
            }
        } catch (e) {
            alert('Error connecting to API');
        }
    }

    async function loadTelemetry() {
        try {
            const res = await fetch(`api/telemetry_read.php?device_id=${encodeURIComponent(deviceId)}&limit=100`);
            const data = await res.json();
            
            if (!data.records || data.records.length === 0) {
                document.getElementById('table-body').innerHTML = '<tr><td colspan="2" class="empty-state">No records found</td></tr>';
                return;
            }

            document.getElementById('no-data').style.display = 'none';
            document.getElementById('chart-panel').style.display = 'block';

            renderChart(data.records);
            renderTable(data.records);
        } catch (e) {
            console.error(e);
            document.getElementById('table-body').innerHTML = '<tr><td colspan="2" class="empty-state">Error loading telemetry</td></tr>';
        }
    }

    function renderChart(records) {
        // Prepare data for Chart.js
        // We want data in chronological order for the chart
        const sorted = [...records].reverse();
        
        const labels = sorted.map(r => new Date(r.timestamp).toLocaleTimeString());
        
        // Find all unique numeric keys in payloads
        const numericKeys = new Set();
        sorted.forEach(r => {
            Object.keys(r.data).forEach(k => {
                if (typeof r.data[k] === 'number') numericKeys.add(k);
            });
        });

        const datasets = Array.from(numericKeys).map((key, i) => {
            const colors = ['#00e676', '#0d9488', '#2979ff', '#ff5252', '#ffab40', '#e040fb'];
            return {
                label: key,
                data: sorted.map(r => r.data[key]),
                borderColor: colors[i % colors.length],
                backgroundColor: colors[i % colors.length] + '22',
                tension: 0.3,
                fill: false
            };
        });

        const ctx = document.getElementById('telemetryChart').getContext('2d');
        if (myChart) myChart.destroy();
        
        myChart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0a0' } },
                    x: { grid: { display: false }, ticks: { color: '#a0a0a0' } }
                },
                plugins: {
                    legend: { labels: { color: '#e0e0e0', font: { size: 12, weight: '600' } } }
                }
            }
        });
    }

    function renderTable(records) {
        const body = document.getElementById('table-body');
        body.innerHTML = '';
        
        records.forEach(r => {
            const tr = document.createElement('tr');
            
            const tdTime = document.createElement('td');
            tdTime.textContent = r.timestamp;
            tr.appendChild(tdTime);
            
            const tdData = document.createElement('td');
            Object.keys(r.data).forEach(k => {
                const badge = document.createElement('div');
                badge.className = 'payload-badge';
                badge.style.margin = '2px';
                badge.textContent = `${k}: ${r.data[k]}`;
                tdData.appendChild(badge);
            });
            tr.appendChild(tdData);
            
            body.appendChild(tr);
        });
    }

    loadTelemetry();
</script>
</body>
</html>
