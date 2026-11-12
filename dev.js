/**
 * CDSMS Local Development & Cloud Deployment Launcher
 * Powered by Node.js
 */
const http = require('http');
const fs = require('fs');
const path = require('path');
const { exec, spawn } = require('child_process');

const PORT = process.env.PORT || 8000;

// Search for local PHP executable
function findPhpPath() {
    const candidates = [
        'php',
        'C:\\xampp\\php\\php.exe',
        'D:\\xampp\\php\\php.exe',
        'C:\\tools\\php\\php.exe',
        'C:\\php\\php.exe'
    ];

    for (const cand of candidates) {
        try {
            const { execSync } = require('child_process');
            execSync(`"${cand}" -v`, { stdio: 'ignore' });
            return cand;
        } catch (e) {
            // continue checking
        }
    }
    return null;
}

const phpPath = findPhpPath();

function openBrowser(url) {
    const startCmd = process.platform === 'win32' ? 'start' : (process.platform === 'darwin' ? 'open' : 'xdg-open');
    exec(`${startCmd} ${url}`, () => {});
}

if (phpPath) {
    console.log('\n======================================================');
    console.log('🚀 Found PHP runtime at:', phpPath);
    console.log(`📡 Starting local PHP server on http://localhost:${PORT}`);
    console.log('======================================================\n');

    const phpServer = spawn(phpPath, ['-S', `localhost:${PORT}`], { stdio: 'inherit' });
    openBrowser(`http://localhost:${PORT}/db_setup.php`);

    phpServer.on('close', (code) => {
        console.log(`PHP server exited with code ${code}`);
    });
} else {
    console.log('\n======================================================');
    console.log('🚗 Online Car Driving School Management System (CDSMS)');
    console.log('📦 Production Environment: Render Cloud (Docker) + Aiven MySQL');
    console.log(`🌐 Local Control Dashboard: http://localhost:${PORT}`);
    console.log('======================================================\n');
    console.log('ℹ️  Notice: Native PHP is not currently installed in Windows PATH.');
    console.log('👉 Serving the CDSMS Cloud Deployment Dashboard on http://localhost:' + PORT);

    const htmlContent = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDSMS - Development &amp; Cloud Deployment Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px 15px;
        }
        .main-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            max-width: 900px;
            margin: auto;
        }
        .badge-live {
            background-color: #10b981;
            font-size: 0.9rem;
            padding: 0.45em 0.8em;
            border-radius: 6px;
        }
        .code-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px 16px;
            color: #38bdf8;
            font-family: monospace;
            font-size: 0.95rem;
        }
        .card-inner {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="main-card p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap pb-3 border-bottom border-secondary mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-car text-primary me-2"></i> CDSMS Development &amp; Cloud Hub</h3>
                <p class="text-muted mb-0 small">Online Car Driving School Management System &bull; Render + Aiven Cloud</p>
            </div>
            <div class="mt-2 mt-md-0">
                <span class="badge badge-live"><i class="fas fa-check-circle me-1"></i> Deploy Ready</span>
            </div>
        </div>

        <div class="alert alert-info border-0 shadow-sm mb-4">
            <i class="fas fa-info-circle me-2"></i> <strong>Note:</strong> CDSMS is a complete <strong>PHP 8.2 + Apache</strong> application containerized with Docker for <strong>Render Cloud</strong> and <strong>Aiven MySQL</strong>.
        </div>

        <!-- Credentials Card -->
        <div class="card-inner p-3 mb-4">
            <h6 class="text-warning fw-bold mb-2"><i class="fas fa-key me-2"></i> Default Administrative Credentials</h6>
            <div class="row g-2 align-items-center">
                <div class="col-sm-6">
                    <strong>Username:</strong> <code class="text-info fs-6">admin</code>
                </div>
                <div class="col-sm-6">
                    <strong>Password:</strong> <code class="text-info fs-6">admin123</code>
                </div>
            </div>
        </div>

        <!-- Render Deployment Steps -->
        <h5 class="fw-bold text-white mb-3"><i class="fas fa-cloud-upload-alt text-primary me-2"></i> Deploy to Render in 2 Steps:</h5>
        
        <div class="mb-4">
            <label class="form-label text-muted small fw-bold">STEP 1: Push Code to your GitHub Repository</label>
            <div class="code-box mb-2">
                git remote add origin https://github.com/abadhbiharisd-98/&lt;YOUR-REPO-NAME&gt;.git<br>
                git push -u origin main
            </div>
            <small class="text-muted">Code is already committed on branch <code>main</code> and locked.</small>
        </div>

        <div class="mb-4">
            <label class="form-label text-muted small fw-bold">STEP 2: Connect to Render Web Service</label>
            <div class="card-inner p-3">
                <p class="mb-2">Click below to open Render Dashboard, create a <strong>Web Service</strong> with <strong>Docker</strong> runtime, and add your Aiven Cloud MySQL environment variables:</p>
                <ul class="small text-muted mb-3">
                    <li><code>PORT</code>: <code>10000</code></li>
                    <li><code>DB_SERVER</code>: <i>(Aiven MySQL Hostname)</i></li>
                    <li><code>DB_PORT</code>: <i>(Aiven 5-digit port e.g. 26284)</i></li>
                    <li><code>DB_USERNAME</code>: <code>avnadmin</code></li>
                    <li><code>DB_PASSWORD</code>: <i>(Aiven Password)</i></li>
                    <li><code>DB_NAME</code>: <code>defaultdb</code></li>
                    <li><code>DB_SSL</code>: <code>true</code></li>
                </ul>
                <a href="https://dashboard.render.com" target="_blank" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-external-link-alt me-1"></i> Open Render Dashboard
                </a>
            </div>
        </div>

        <!-- 1-Click Database Installer Notice -->
        <div class="card-inner p-3 mb-4">
            <h6 class="text-success fw-bold mb-2"><i class="fas fa-database me-2"></i> Automated 1-Click Database Setup</h6>
            <p class="small text-muted mb-0">
                Once deployed on Render, visit <code>https://&lt;your-app&gt;.onrender.com/db_setup.php</code>. It will automatically test the SSL connection to Aiven MySQL and import all 5 CDSMS tables and admin account with a single click.
            </p>
        </div>

        <div class="text-center pt-3 border-top border-secondary text-muted small">
            CDSMS v1.0 &bull; Configured for Render Cloud &bull; abadhbiharisd-98
        </div>
    </div>
</body>
</html>`;

    const server = http.createServer((req, res) => {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(htmlContent);
    });

    server.listen(PORT, () => {
        console.log(`Server running at http://localhost:${PORT}/`);
        openBrowser(`http://localhost:${PORT}`);
    });
}
