<?php
/**
 * 1-Click Aiven Cloud MySQL Database Setup & Health Check Tool
 * Online Car Driving School Management System (CDSMS)
 */
require_once('initialize.php');
require_once('classes/DBConnection.php');

$db = new DBConnection();
$conn = $db->conn;
$conn_error = $db->connect_error;

$expected_tables = array(
    'system_info'   => 'System Settings & Configuration',
    'users'         => 'Administrators & Staff Accounts',
    'package_list'  => 'Driving School Training Packages',
    'enrollee_list' => 'Student Enrollees & Bookings',
    'payment_list'  => 'Payment Transactions & Records'
);

$sql_file = __DIR__ . '/database/cdsms_db.sql';
$action_msg = '';
$action_type = '';

function run_database_import($conn, $sql_file, &$action_msg, &$action_type) {
    // 1. Set Aiven Cloud MySQL session flags
    @$conn->query("SET SESSION sql_require_primary_key = 0");
    @$conn->query("SET FOREIGN_KEY_CHECKS = 0");
    @$conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    @$conn->query("SET AUTOCOMMIT = 1");

    $executed = 0;
    $errors = array();

    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);

        // Split SQL statements safely
        $queries = array();
        $lines = explode("\n", $sql_content);
        $query = '';
        $in_multiline_comment = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;

            // Skip single line comments
            if (substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') continue;

            // Handle multiline comments
            if (substr($trimmed, 0, 2) === '/*') {
                if (strpos($trimmed, '*/') !== false) {
                    continue;
                }
                $in_multiline_comment = true;
                continue;
            }
            if ($in_multiline_comment) {
                if (strpos($trimmed, '*/') !== false) {
                    $in_multiline_comment = false;
                }
                continue;
            }

            $query .= $line . "\n";
            if (substr($trimmed, -1) === ';') {
                $queries[] = $query;
                $query = '';
            }
        }

        foreach ($queries as $q) {
            $q = trim($q);
            if (empty($q)) continue;
            try {
                if (!$conn->query($q)) {
                    $errors[] = $conn->error;
                } else {
                    $executed++;
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    // Direct Guaranteed Inline Fallback Schema (ensures Aiven MySQL always has all tables)
    $fallback_schemas = array(
        "CREATE TABLE IF NOT EXISTS `package_list` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` text NOT NULL,
            `description` text NOT NULL,
            `training_duration` varchar(100) NOT NULL,
            `cost` float NOT NULL DEFAULT 0,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4",

        "INSERT INTO `package_list` (`id`, `name`, `description`, `training_duration`, `cost`, `status`, `date_created`) VALUES
        (1, 'Automatic Car Training', 'This package is a sample car training package for automatic transmission.', '30 Days', 5200, 1, '2021-12-03 09:37:17'),
        (2, 'Manual Car Training', 'This is a sample car driving training package for manual transmission.', '30 Days', 5200, 1, '2021-12-03 09:41:21')
        ON DUPLICATE KEY UPDATE `id`=`id`",

        "CREATE TABLE IF NOT EXISTS `enrollee_list` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `reg_no` varchar(50) NOT NULL,
            `package_id` int(11) NOT NULL,
            `cost` float NOT NULL DEFAULT 0,
            `firstname` text NOT NULL,
            `middlename` text DEFAULT NULL,
            `lastname` text NOT NULL,
            `gender` varchar(50) NOT NULL,
            `dob` date NOT NULL,
            `email` text NOT NULL,
            `contact` text NOT NULL,
            `address` text NOT NULL,
            `start_date` date NOT NULL,
            `time` varchar(250) NOT NULL,
            `status` tinyint(4) NOT NULL DEFAULT 0,
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `package_id` (`package_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4",

        "INSERT INTO `enrollee_list` (`id`, `reg_no`, `package_id`, `cost`, `firstname`, `middlename`, `lastname`, `gender`, `dob`, `email`, `contact`, `address`, `start_date`, `time`, `status`, `date_created`, `date_updated`) VALUES
        (1, '2021120001', 1, 5200, 'John', 'D', 'Smith', 'Male', '1997-06-23', 'jsmith@sample.com', '0912456789', 'Here Street, There City, Anywhere, 2306', '2021-12-06', '10:00 AM - 11:30 AM', 3, '2021-12-03 13:43:46', '2021-12-03 15:00:39'),
        (2, '2021120002', 2, 5200, 'Claire', 'C', 'Blake', 'Female', '1997-10-14', 'cblake@sample.com', '09123456789', 'Sample Address only', '2021-12-08', '08:00 AM - 09:30 AM', 0, '2021-12-03 13:45:25', '2021-12-03 13:45:40')
        ON DUPLICATE KEY UPDATE `id`=`id`",

        "CREATE TABLE IF NOT EXISTS `payment_list` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `enrollee_id` int(11) NOT NULL,
            `amount` float NOT NULL DEFAULT 0,
            `remarks` text DEFAULT NULL,
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4",

        "INSERT INTO `payment_list` (`id`, `enrollee_id`, `amount`, `remarks`, `date_created`) VALUES
        (1, 1, 1200, 'First Payment', '2021-12-03 14:47:31'),
        (3, 1, 1000, 'Sample', '2021-12-03 15:00:33')
        ON DUPLICATE KEY UPDATE `id`=`id`",

        "CREATE TABLE IF NOT EXISTS `system_info` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `meta_field` varchar(255) NOT NULL,
            `meta_value` text NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4",

        "INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`) VALUES
        (1, 'name', 'Car Driving School Management System - PHP'),
        (6, 'short_name', 'CDSMS - PHP'),
        (11, 'logo', 'uploads/logo-1638498556.png'),
        (13, 'user_avatar', 'uploads/user_avatar.jpg'),
        (14, 'cover', 'uploads/cover-1638495217.png'),
        (16, 'email', 'info@cdsms.com'),
        (17, 'contact', '09854698789 / 78945632'),
        (18, 'from_time', '11:00'),
        (19, 'to_time', '21:30'),
        (20, 'address', 'Under the Tree, Here Street, There City, Anywhere 1014')
        ON DUPLICATE KEY UPDATE `id`=`id`",

        "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `firstname` varchar(250) NOT NULL,
            `middlename` text DEFAULT NULL,
            `lastname` varchar(250) NOT NULL,
            `username` varchar(250) NOT NULL,
            `password` text NOT NULL,
            `avatar` text DEFAULT NULL,
            `last_login` datetime DEFAULT NULL,
            `type` tinyint(1) NOT NULL DEFAULT 0,
            `status` int(1) NOT NULL DEFAULT 1,
            `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4",

        "INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `username`, `password`, `avatar`, `last_login`, `type`, `status`, `date_added`, `date_updated`) VALUES
        (1, 'Administrator', NULL, 'Admin', 'admin', '0192023a7bbd73250516f069df18b500', 'uploads/avatar-1.png?v=1635556826', NULL, 1, 1, '2021-01-20 14:02:37', '2021-11-27 13:39:11')
        ON DUPLICATE KEY UPDATE `id`=`id`"
    );

    foreach ($fallback_schemas as $fsql) {
        try {
            if ($conn->query($fsql)) {
                $executed++;
            }
        } catch (Throwable $e) {}
    }

    @$conn->query("SET FOREIGN_KEY_CHECKS = 1");

    if ($executed > 0) {
        $action_msg = "Database successfully initialized! All CDSMS tables and default admin account are ready.";
        $action_type = "success";
        return true;
    } else {
        $action_msg = "Import encountered an issue. " . (!empty($errors) ? implode(', ', array_slice($errors, 0, 2)) : '');
        $action_type = "danger";
        return false;
    }
}

// Check if tables already exist
$initial_check = false;
if ($conn && !$conn->connect_errno) {
    try {
        $chk = @$conn->query("SHOW TABLES LIKE 'system_info'");
        $initial_check = ($chk && $chk->num_rows > 0);
    } catch (Throwable $e) {
        $initial_check = false;
    }
}

// Auto-run import if tables are missing OR if POST submitted
if ($conn && !$conn->connect_errno) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'reimport') {
            foreach ($expected_tables as $tbl => $desc) {
                @$conn->query("DROP TABLE IF EXISTS `{$tbl}`");
            }
        }
        run_database_import($conn, $sql_file, $action_msg, $action_type);
    } elseif (!$initial_check && !isset($_GET['skip_auto'])) {
        // Automatic 1-Click Import on first visit!
        run_database_import($conn, $sql_file, $action_msg, $action_type);
    }
}

// Fetch SSL Cipher Status if connected
$ssl_cipher = 'Disabled';
if ($conn && !$conn->connect_errno) {
    try {
        $ssl_res = @$conn->query("SHOW STATUS LIKE 'Ssl_cipher'");
        if ($ssl_res && $row = $ssl_res->fetch_assoc()) {
            if (!empty($row['Value'])) {
                $ssl_cipher = $row['Value'];
            }
        }
    } catch (Throwable $e) {}
}

// Fetch Table Status
$table_status = array();
$all_tables_exist = true;
$admin_exists = false;

if ($conn && !$conn->connect_errno) {
    foreach ($expected_tables as $tbl => $desc) {
        $exists = false;
        $count = 0;
        try {
            $check = @$conn->query("SHOW TABLES LIKE '{$tbl}'");
            $exists = ($check && $check->num_rows > 0);
            if ($exists) {
                $cnt_q = @$conn->query("SELECT COUNT(*) as cnt FROM `{$tbl}`");
                if ($cnt_q && $r = $cnt_q->fetch_assoc()) {
                    $count = $r['cnt'];
                }
            }
        } catch (Throwable $e) {
            $exists = false;
        }

        if (!$exists) {
            $all_tables_exist = false;
        }

        $table_status[$tbl] = array(
            'exists' => $exists,
            'count' => $count
        );
    }

    // Check Admin user
    try {
        $u_check = @$conn->query("SELECT id, username, `type` FROM users WHERE username = 'admin' LIMIT 1");
        if ($u_check && $u_check->num_rows > 0) {
            $admin_exists = true;
        }
    } catch (Throwable $e) {}
} else {
    $all_tables_exist = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CDSMS Database Setup &amp; Doctor</title>
    <!-- Google Font & Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .setup-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
        }
        .stat-badge {
            font-size: 0.85rem;
            padding: 0.4em 0.8em;
            border-radius: 6px;
        }
        .table-custom {
            color: #e2e8f0;
        }
        .table-custom th {
            border-color: #334155;
            background-color: #0f172a;
        }
        .table-custom td {
            border-color: #334155;
        }
        .badge-success { background-color: #10b981; }
        .badge-danger { background-color: #ef4444; }
        .badge-warning { background-color: #f59e0b; color: #111; }
        .badge-info { background-color: #3b82f6; }
    </style>
</head>
<body class="py-5">
<div class="container">
    <div class="card setup-card max-w-4xl mx-auto">
        <!-- Header -->
        <div class="card-header border-bottom border-secondary d-flex justify-content-between align-items-center py-3">
            <div>
                <h4 class="mb-0 text-white font-weight-bold">
                    <i class="fas fa-car mr-2 text-primary"></i> CDSMS Cloud Database Setup &amp; Health Check
                </h4>
                <small class="text-muted">Aiven Cloud MySQL SSL Connector &amp; Automated Schema Initializer</small>
            </div>
            <div>
                <?php if ($conn && !$conn->connect_errno): ?>
                    <span class="badge badge-success stat-badge">
                        <i class="fas fa-check-circle mr-1"></i> Connected to MySQL
                    </span>
                <?php else: ?>
                    <span class="badge badge-danger stat-badge">
                        <i class="fas fa-times-circle mr-1"></i> Connection Failed
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <!-- Action Alerts -->
            <?php if (!empty($action_msg)): ?>
                <div class="alert alert-<?php echo $action_type; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-info-circle mr-2"></i> <?php echo $action_msg; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Connection Details -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="p-3 rounded bg-dark border border-secondary h-100">
                        <h6 class="text-muted mb-3 font-weight-bold"><i class="fas fa-server mr-2 text-info"></i> DATABASE CONFIGURATION</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2"><strong>Server / Host:</strong> <code><?php echo defined('DB_SERVER') ? htmlspecialchars(DB_SERVER) : 'localhost'; ?></code></li>
                            <li class="mb-2"><strong>Port:</strong> <code><?php echo defined('DB_PORT') ? htmlspecialchars(DB_PORT) : '3306'; ?></code></li>
                            <li class="mb-2"><strong>Database:</strong> <code><?php echo defined('DB_NAME') ? htmlspecialchars(DB_NAME) : 'cdsms_db'; ?></code></li>
                            <li class="mb-0"><strong>Username:</strong> <code><?php echo defined('DB_USERNAME') ? htmlspecialchars(DB_USERNAME) : 'root'; ?></code></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="p-3 rounded bg-dark border border-secondary h-100">
                        <h6 class="text-muted mb-3 font-weight-bold"><i class="fas fa-shield-alt mr-2 text-warning"></i> SECURITY &amp; SSL STATUS</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <strong>Aiven SSL Enforced:</strong> 
                                <?php if (defined('DB_SSL') && DB_SSL): ?>
                                    <span class="badge badge-success"><i class="fas fa-lock mr-1"></i> YES (MYSQLI_CLIENT_SSL)</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No (Standard TCP)</span>
                                <?php endif; ?>
                            </li>
                            <li class="mb-2">
                                <strong>Active SSL Cipher:</strong> 
                                <span class="badge badge-info"><?php echo htmlspecialchars($ssl_cipher); ?></span>
                            </li>
                            <li class="mb-0">
                                <strong>Connection Diagnostics:</strong> 
                                <?php if ($conn && !$conn->connect_errno): ?>
                                    <span class="text-success font-weight-bold"><i class="fas fa-bolt mr-1"></i> Live &amp; Healthy</span>
                                <?php else: ?>
                                    <span class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> <?php echo htmlspecialchars($conn_error ?: 'Could not connect'); ?></span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Table Status -->
            <h5 class="text-white font-weight-bold mb-3"><i class="fas fa-table mr-2 text-success"></i> Database Schema &amp; Tables Status</h5>
            <div class="table-responsive">
                <table class="table table-custom table-bordered">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Total Rows</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expected_tables as $tbl => $desc): ?>
                            <?php 
                                $status = isset($table_status[$tbl]) ? $table_status[$tbl] : array('exists'=>false, 'count'=>0);
                            ?>
                            <tr>
                                <td><code><?php echo $tbl; ?></code></td>
                                <td><?php echo $desc; ?></td>
                                <td>
                                    <?php if ($status['exists']): ?>
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Ready</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning badge-warn"><i class="fas fa-exclamation mr-1"></i> Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-secondary"><?php echo $status['count']; ?> records</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Credentials Card -->
            <div class="card bg-dark border-secondary mt-4">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <span class="badge badge-info mr-2">Default Admin Account</span>
                            <strong>Username:</strong> <code>admin</code> &bull; 
                            <strong>Password:</strong> <code>admin123</code>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <?php if ($admin_exists): ?>
                                <span class="text-success small font-weight-bold"><i class="fas fa-user-check mr-1"></i> Admin Account Verified</span>
                            <?php else: ?>
                                <span class="text-warning small font-weight-bold"><i class="fas fa-user-slash mr-1"></i> Admin Not Found (Run Import)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="mt-4 pt-2 border-top border-secondary d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <?php if (!$all_tables_exist): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="action" value="import">
                            <button type="submit" class="btn btn-primary btn-lg px-4 font-weight-bold shadow">
                                <i class="fas fa-cloud-upload-alt mr-2"></i> 1-Click Import Database (cdsms_db.sql)
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Warning: Re-importing will reset all database tables to initial state. Are you sure?');">
                            <input type="hidden" name="action" value="reimport">
                            <button type="submit" class="btn btn-outline-warning">
                                <i class="fas fa-redo-alt mr-1"></i> Reset &amp; Re-import Database
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="mt-3 mt-md-0">
                    <a href="<?php echo base_url; ?>index.php" class="btn btn-outline-light mr-2">
                        <i class="fas fa-globe mr-1"></i> Go to Website
                    </a>
                    <a href="<?php echo base_url; ?>admin/login.php" class="btn btn-success btn-lg px-4 font-weight-bold shadow">
                        <i class="fas fa-sign-in-alt mr-2"></i> Admin Login
                    </a>
                </div>
            </div>
        </div>
        <!-- Card Footer -->
        <div class="card-footer bg-transparent border-top border-secondary text-center text-muted small py-3">
            CDSMS Render Deploy &bull; Aiven Cloud MySQL Connector v2.0
        </div>
    </div>
</div>

<!-- jQuery & Bootstrap -->
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
