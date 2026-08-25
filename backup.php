<?php
require_once __DIR__ . '/../config/app.php';
adminCheck();

// ── Handle PDF Data Report ────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'pdf') {
    global $database;
    $dbname = $database ?? 'electrostore';

    // Fetch real website data
    $products   = $pdo->query("SELECT p.id, p.name, p.selling_price, p.qty, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
    $orders     = $pdo->query("SELECT o.id, o.tracking_no, u.name as customer, o.total_amount, o.payment_mode, o.status FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC")->fetchAll();
    $users      = $pdo->query("SELECT id, name, email, phone, created_at FROM users WHERE role = 'user' ORDER BY id DESC")->fetchAll();
    $categories = $pdo->query("SELECT c.id, c.name, c.status, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.id")->fetchAll();

    $total_revenue = array_sum(array_column($orders, 'total_amount'));
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>ElectroStore – Data Report <?= date('Y-m-d') ?></title>
        <style>
            @page {
                size: A4;
                margin: 15mm 12mm;
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                background: #fff;
                color: #1e293b;
                font-size: 12px;
            }

            .header {
                background: linear-gradient(135deg, #0657f9, #6366f1);
                color: #fff;
                padding: 22px 28px;
                border-radius: 10px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .header h1 {
                font-size: 20px;
                font-weight: 800;
            }

            .header p {
                font-size: 10px;
                opacity: 0.8;
                margin-top: 3px;
            }

            .header .meta {
                text-align: right;
                font-size: 10px;
                opacity: 0.8;
            }

            .stats {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
            }

            .stat-box {
                flex: 1;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 12px;
                text-align: center;
            }

            .stat-box .val {
                font-size: 20px;
                font-weight: 800;
                color: #0657f9;
            }

            .stat-box .lbl {
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #94a3b8;
                margin-top: 2px;
            }

            .section-title {
                font-size: 13px;
                font-weight: 800;
                color: #0f172a;
                margin: 18px 0 8px;
                padding-bottom: 6px;
                border-bottom: 2px solid #0657f9;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .section-title span {
                background: #0657f9;
                color: #fff;
                font-size: 9px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 20px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 6px;
                page-break-inside: auto;
            }

            thead tr {
                background: #0f172a;
                color: #94a3b8;
            }

            thead th {
                padding: 8px 10px;
                text-align: left;
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }

            tbody tr:nth-child(even) {
                background: #f8fafc;
            }

            tbody td {
                padding: 7px 10px;
                font-size: 11px;
                color: #1e293b;
                border-bottom: 1px solid #e2e8f0;
            }

            tr {
                page-break-inside: avoid;
            }

            .badge {
                display: inline-block;
                font-size: 9px;
                font-weight: 700;
                padding: 2px 7px;
                border-radius: 20px;
            }

            .badge-blue {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .badge-green {
                background: #dcfce7;
                color: #15803d;
            }

            .badge-yellow {
                background: #fef9c3;
                color: #a16207;
            }

            .badge-red {
                background: #fee2e2;
                color: #b91c1c;
            }

            .footer {
                text-align: center;
                font-size: 9px;
                color: #94a3b8;
                border-top: 1px solid #e2e8f0;
                padding-top: 10px;
                margin-top: 16px;
            }

            @media print {
                .no-print {
                    display: none !important;
                }
            }

            .print-btn {
                position: fixed;
                top: 16px;
                right: 16px;
                background: #0657f9;
                color: #fff;
                border: none;
                padding: 10px 18px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 4px 16px rgba(6, 87, 249, 0.4);
                z-index: 99;
            }
            .back-btn {
                position: fixed;
                top: 16px;
                left: 16px;
                background: #1e293b;
                color: #fff;
                border: none;
                padding: 10px 18px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 4px 16px rgba(0,0,0,0.3);
                z-index: 99;
                text-decoration: none;
            }
        </style>
    </head>

    <body>
        <button class="print-btn no-print" onclick="window.print()">⬇ Save as PDF</button>
        <button class="back-btn no-print" onclick="closePdfTab()">← Back</button>
        <script>
            function closePdfTab() {
                if (window.opener || window.history.length <= 1) {
                    window.close();
                } else {
                    window.history.length > 1 ? window.history.back() : window.location.href = 'backup.php';
                }
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closePdfTab();
            });
        </script>

        <div class="header">
            <div>
                <h1>⚡ ElectroStore</h1>
                <p>Full Website Data Report</p>
            </div>
            <div class="meta">
                <div style="font-size:12px; font-weight:700;"><?= date('d M Y') ?></div>
                <div><?= date('h:i A') ?></div>
                <div>DB: <?= htmlspecialchars($dbname) ?></div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats">
            <div class="stat-box">
                <div class="val"><?= count($products) ?></div>
                <div class="lbl">Products</div>
            </div>
            <div class="stat-box">
                <div class="val"><?= count($orders) ?></div>
                <div class="lbl">Orders</div>
            </div>
            <div class="stat-box">
                <div class="val"><?= count($users) ?></div>
                <div class="lbl">Customers</div>
            </div>
            <div class="stat-box">
                <div class="val">₹<?= number_format($total_revenue) ?></div>
                <div class="lbl">Total Revenue</div>
            </div>
            <div class="stat-box">
                <div class="val"><?= count($categories) ?></div>
                <div class="lbl">Categories</div>
            </div>
        </div>

        <!-- PRODUCTS -->
        <div class="section-title">📦 Products <span><?= count($products) ?></span></div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price (₹)</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                        <td style="font-weight:700; color:#0657f9;">₹<?= number_format($p['selling_price']) ?></td>
                        <td>
                            <span class="badge <?= $p['qty'] > 0 ? 'badge-green' : 'badge-red' ?>">
                                <?= $p['qty'] > 0 ? $p['qty'] . ' in stock' : 'Out of stock' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$products): ?><tr>
                        <td colspan="5" style="text-align:center; color:#94a3b8;">No products found</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>

        <!-- ORDERS -->
        <div class="section-title">🛒 Orders <span><?= count($orders) ?></span></div>
        <table>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $i => $o):
                        $statusClass = match ($o['status']) {
                            'delivered' => 'badge-green',
                            'pending'   => 'badge-yellow',
                            'cancelled' => 'badge-red',
                            default     => 'badge-blue',
                        };
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td style="font-family:monospace; font-size:10px;"><?= htmlspecialchars($o['tracking_no']) ?></td>
                            <td><?= htmlspecialchars($o['customer'] ?? '—') ?></td>
                            <td style="font-weight:700; color:#0657f9;">₹<?= number_format($o['total_amount']) ?></td>
                            <td><?= htmlspecialchars($o['payment_mode']) ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= ucfirst($o['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$orders): ?><tr>
                            <td colspan="6" style="text-align:center; color:#94a3b8;">No orders found</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>

            <!-- CUSTOMERS -->
            <div class="section-title">👤 Customers <span><?= count($users) ?></span></div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                            <td style="font-size:10px; color:#64748b;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$users): ?><tr>
                            <td colspan="5" style="text-align:center; color:#94a3b8;">No customers found</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>

            <!-- CATEGORIES -->
            <div class="section-title">🗂 Categories <span><?= count($categories) ?></span></div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category Name</th>
                        <th>Products</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $i => $cat): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($cat['name']) ?></td>
                            <td><span class="badge badge-blue"><?= $cat['product_count'] ?></span></td>
                            <td><span class="badge <?= $cat['status'] == '1' ? 'badge-green' : 'badge-red' ?>"><?= $cat['status'] == '1' ? 'Active' : 'Inactive' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="footer">
                ElectroStore Admin Report &middot; Generated: <?= date('d M Y, h:i A') ?> &middot; Confidential
            </div>
    </body>

    </html>
<?php
    exit;
}

// ── Handle Backup Download ─────────────────────────────────────────────────── 
if (isset($_GET['action']) && $_GET['action'] === 'download') {

    // Use the same DB variables defined in config/db.php (already loaded via app.php)
    global $host, $username, $password, $database;
    $dbname = $database;

    if (!$dbname) {
        $_SESSION['message']      = 'Could not determine database name from config.';
        $_SESSION['message_type'] = 'danger';
        header('Location: backup.php');
        exit;
    }

    // Build SQL dump manually via PDO (works even without mysqldump binary)
    $filename = 'electrostore_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $tables   = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    ob_start();
    echo "-- =====================================================\n";
    echo "-- ElectroStore Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Database : $dbname\n";
    echo "-- =====================================================\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Table structure
        $createRes = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        echo "-- ---------------------------------------------------\n";
        echo "-- Table: `$table`\n";
        echo "-- ---------------------------------------------------\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo $createRes[1] . ";\n\n";

        // Table data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
            foreach ($rows as $row) {
                $vals = array_map(function ($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote($v);
                }, array_values($row));
                echo "INSERT INTO `$table` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
            }
            echo "\n";
        }
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    $sql = ob_get_clean();

    // Force-download the SQL file
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Content-Length: ' . strlen($sql));
    header('Cache-Control: no-cache, must-revalidate');
    echo $sql;
    exit;
}

// ── Gather DB info for display ───────────────────────────────────────────────
global $database;
$dbname = $database ?? 'Unknown';

// Get all table names + row counts
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$table_info = [];
foreach ($tables as $t) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $size_row = $pdo->query("SHOW TABLE STATUS LIKE '$t'")->fetch(PDO::FETCH_ASSOC);
    $size_kb  = round(($size_row['Data_length'] + $size_row['Index_length']) / 1024, 1);
    $table_info[] = ['name' => $t, 'rows' => $cnt, 'size_kb' => $size_kb];
}
$total_rows = array_sum(array_column($table_info, 'rows'));
$total_kb   = array_sum(array_column($table_info, 'size_kb'));

include __DIR__ . '/includes/header.php';
?>

<style>
    .backup-hero {
        background: linear-gradient(135deg, rgba(6, 87, 249, 0.15), rgba(139, 92, 246, 0.1));
        border: 1px solid rgba(6, 87, 249, 0.2);
        border-radius: 1.25rem;
        padding: 2rem 2.5rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .backup-hero-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #0657f9, #8b5cf6);
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        box-shadow: 0 0 24px rgba(6, 87, 249, 0.4);
        flex-shrink: 0;
    }

    .stat-pill {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        padding: 0.75rem 1.25rem;
        text-align: center;
        min-width: 120px;
    }

    .stat-pill .val {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
    }

    .stat-pill .lbl {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #64748b;
        margin-top: 2px;
    }

    .download-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.875rem 2rem;
        background: linear-gradient(135deg, #0657f9, #6366f1);
        border: none;
        border-radius: 0.75rem;
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 4px 20px rgba(6, 87, 249, 0.4);
        transition: all 0.2s;
        cursor: pointer;
    }

    .download-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 28px rgba(6, 87, 249, 0.5);
        color: #fff;
    }

    .download-btn:active {
        transform: translateY(0);
    }

    .table-card {
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 1rem;
        overflow: hidden;
    }

    .table-card table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-card th {
        padding: 0.875rem 1.25rem;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #475569;
        background: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .table-card td {
        padding: 0.875rem 1.25rem;
        font-size: 0.875rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        color: #cbd5e1;
    }

    .table-card tr:last-child td {
        border-bottom: none;
    }

    .table-card tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }

    .badge-rows {
        display: inline-block;
        background: rgba(6, 87, 249, 0.15);
        border: 1px solid rgba(6, 87, 249, 0.25);
        color: #60a5fa;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 20px;
    }

    .info-box {
        background: rgba(251, 191, 36, 0.07);
        border: 1px solid rgba(251, 191, 36, 0.2);
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        font-size: 0.8rem;
        color: #fbbf24;
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        margin-bottom: 1.5rem;
    }
</style>

<!-- Page Header -->
<div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
    <span class="material-symbols-outlined" style="font-size:2rem; color:var(--primary);">backup</span>
    <div>
        <h1 style="font-size:1.5rem; font-weight:700; color:#f1f5f9;">Database Backup</h1>
        <p style="color:#64748b; font-size:0.8rem; margin-top:2px;">Download a full SQL export of your ElectroStore database.</p>
    </div>
</div>

<!-- Hero Card -->
<div class="backup-hero">
    <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
        <div class="backup-hero-icon">
            <span class="material-symbols-outlined" style="color:#fff; font-size:2rem; font-variation-settings:'FILL' 1;">database</span>
        </div>
        <div>
            <div style="font-size:1.3rem; font-weight:700; color:#fff;">Database: <span style="color:#60a5fa;"><?= htmlspecialchars($dbname) ?></span></div>
            <div style="color:#64748b; font-size:0.8rem; margin-top:4px;">Last backup: whenever you click "Download"</div>
        </div>
    </div>
    <div style="display:flex; gap:1rem; flex-wrap:wrap;">
        <div class="stat-pill">
            <div class="val"><?= count($tables) ?></div>
            <div class="lbl">Tables</div>
        </div>
        <div class="stat-pill">
            <div class="val"><?= number_format($total_rows) ?></div>
            <div class="lbl">Total Rows</div>
        </div>
        <div class="stat-pill">
            <div class="val"><?= $total_kb ?> KB</div>
            <div class="lbl">DB Size</div>
        </div>
        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
            <a href="backup.php?action=download" class="download-btn" id="downloadBtn" onclick="startDownload()">
                <span class="material-symbols-outlined" style="font-size:1.25rem; font-variation-settings:'FILL' 1;">download</span>
                Download SQL
            </a>
            <a href="backup.php?action=pdf" target="_blank" class="download-btn" style="background: linear-gradient(135deg, #dc2626, #ef4444); box-shadow: 0 4px 20px rgba(220,38,38,0.4);">
                <span class="material-symbols-outlined" style="font-size:1.25rem; font-variation-settings:'FILL' 1;">picture_as_pdf</span>
                Save as PDF
            </a>
        </div>
    </div>
</div>

<!-- Info -->
<div class="info-box">
    <span class="material-symbols-outlined" style="font-size:1rem; margin-top:1px; flex-shrink:0;">info</span>
    <span>The backup file is a complete <strong>.sql</strong> file containing all your tables and data. You can import it anytime using phpMyAdmin → Import to restore your database.</span>
</div>

<!-- Tables Overview -->
<div style="margin-bottom:1.25rem; display:flex; align-items:center; justify-content:space-between;">
    <h2 style="font-size:1rem; font-weight:700; color:#f1f5f9;">Tables in <span style="color:#60a5fa;"><?= htmlspecialchars($dbname) ?></span></h2>
    <span style="font-size:0.75rem; color:#64748b;"><?= count($tables) ?> tables found</span>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Table Name</th>
                <th>Rows</th>
                <th>Size</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($table_info as $i => $t): ?>
                <tr>
                    <td style="color:#475569;"><?= $i + 1 ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span class="material-symbols-outlined" style="font-size:0.9rem; color:#475569;">table_chart</span>
                            <span style="font-weight:600; color:#e2e8f0; font-family:monospace;"><?= htmlspecialchars($t['name']) ?></span>
                        </div>
                    </td>
                    <td><span class="badge-rows"><?= number_format($t['rows']) ?> rows</span></td>
                    <td style="color:#64748b;"><?= $t['size_kb'] ?> KB</td>
                    <td>
                        <span style="color:#4ade80; font-size:0.75rem; font-weight:600; display:flex; align-items:center; gap:4px;">
                            <span class="material-symbols-outlined" style="font-size:0.9rem; font-variation-settings:'FILL' 1;">check_circle</span>
                            Ready
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function startDownload() {
        const btn = document.getElementById('downloadBtn');
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.25rem; animation:spin 1s linear infinite;">sync</span> Generating...';
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
        setTimeout(() => {
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.25rem; font-variation-settings:\'FILL\' 1;">download</span> Download Backup';
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }, 3000);
    }
</script>
<style>
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>