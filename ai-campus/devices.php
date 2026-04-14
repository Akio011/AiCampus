<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
$pageTitle = 'Device Borrowing — AI Campus Management';

$canBorrow    = isStaff();
$canAddDevice = isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireStaff();
    if (isset($_POST['add_device'])) {
        $pdo->prepare("INSERT INTO devices (name, category, total_units) VALUES (?,?,?)")
            ->execute([trim($_POST['device_name']), trim($_POST['device_category']), (int)$_POST['total_units']]);
        $pdo->prepare("INSERT INTO activity_log(description,icon,color) VALUES(?,?,?)")
            ->execute(['Added new device: '.trim($_POST['device_name']),'box','teal']);
        header('Location: devices.php'); exit;
    }
    if (isset($_POST['log_borrowing'])) {        $pdo->prepare("INSERT INTO borrowings (borrower_name,borrower_email,device_id,device_label,borrow_date,status) VALUES(?,?,?,?,NOW(),'active')")
            ->execute([$_POST['borrower_name'],$authUser['email'],$_POST['device_id'],$_POST['device_label']]);
        $pdo->prepare("INSERT INTO activity_log(description,icon,color) VALUES(?,?,?)")
            ->execute([$_POST['borrower_name'].' borrowed '.$_POST['device_label'],'device','blue']);
        header('Location: devices.php'); exit;
    }
    if (isset($_POST['mark_returned'])) {
        $pdo->prepare("UPDATE borrowings SET return_date=NOW(),status='returned' WHERE id=?")->execute([$_POST['id']]);
        header('Location: devices.php'); exit;
    }
}

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$showForm = isset($_GET['action']) && $_GET['action']==='log';

$sql = "SELECT * FROM borrowings WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (borrower_name LIKE ? OR device_label LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($statusFilter) { $sql .= " AND status=?"; $params[] = $statusFilter; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$borrowings = $stmt->fetchAll();

$devices = $pdo->query("SELECT d.*,
    (SELECT COUNT(*) FROM borrowings b WHERE b.device_id=d.id AND b.status='active') as in_use,
    (SELECT COUNT(*) FROM borrowings b WHERE b.device_id=d.id AND b.status='overdue') as overdue
    FROM devices d")->fetchAll();

$totalAvailable = (int)$pdo->query("SELECT SUM(d.total_units-(SELECT COUNT(*) FROM borrowings b WHERE b.device_id=d.id AND b.status IN('active','overdue'))) FROM devices d")->fetchColumn();
$totalInUse  = (int)$pdo->query("SELECT COUNT(*) FROM borrowings WHERE status='active'")->fetchColumn();
$totalOverdue= (int)$pdo->query("SELECT COUNT(*) FROM borrowings WHERE status='overdue'")->fetchColumn();

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Device Borrowing Logbook</h1>
            <p class="text-slate-400 text-sm mt-0.5">Digital tracking system for department equipment</p>
        </div>
        <?php if($canBorrow): ?>
        <div class="flex gap-2">
        <?php if($canAddDevice): ?>
        <button onclick="document.getElementById('addDeviceModal').classList.remove('hidden')" class="btn-primary">
            <i class="fas fa-box text-xs"></i> Add Device
        </button>
        <?php endif; ?>
        <button onclick="document.getElementById('borrowModal').classList.remove('hidden')" class="btn-primary">
            <i class="fas fa-plus text-xs"></i> Log Borrowing
        </button>
        </div>
        <?php else: ?>
        <div class="flex items-center gap-2 bg-red-50 border border-red-100 text-red-500 text-xs font-medium px-4 py-2 rounded-xl">
            <i class="fas fa-lock text-xs"></i> Staff only — borrowing restricted
        </div>
        <?php endif; ?>
    </div>

    <div class="p-8">
        <!-- Inventory Section -->
        <div class="mb-8">
            <!-- Section header -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <div style="display:flex;align-items:center;gap:14px">
                    <div style="width:46px;height:46px;border-radius:14px;overflow:hidden;box-shadow:0 6px 20px rgba(139,26,46,.3);flex-shrink:0;border:2px solid rgba(139,26,46,.12)">
                        <img src="assets/ccse-seal.jpg" alt="CCSE" style="width:100%;height:100%;object-fit:cover">
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:16px;color:#0f172a;letter-spacing:-.01em">Device Inventory</div>
                        <div style="font-size:12px;color:#94a3b8;margin-top:2px;font-weight:500">Real-time availability across all equipment</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="stat-pill stat-pill-green">
                        <span class="stat-dot" style="background:#22c55e;animation:dotPulse 2s ease-in-out infinite"></span>
                        <span><?= $totalAvailable ?> Available</span>
                    </div>
                    <div class="stat-pill stat-pill-blue">
                        <span class="stat-dot" style="background:#3b82f6"></span>
                        <span><?= $totalInUse ?> In Use</span>
                    </div>
                    <div class="stat-pill stat-pill-red">
                        <span class="stat-dot" style="background:#ef4444;animation:dotPulse 1.5s ease-in-out infinite"></span>
                        <span><?= $totalOverdue ?> Overdue</span>
                    </div>
                </div>
            </div>

            <!-- Device cards grid -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px" class="inv-grid">
                <?php
                // Category color palettes
                $catPalettes = [
                    'computing'    => ['#1e40af','#3b82f6','#dbeafe','#eff6ff','linear-gradient(135deg,#1e40af,#3b82f6)'],
                    'av equipment' => ['#7c3aed','#a78bfa','#ede9fe','#f5f3ff','linear-gradient(135deg,#7c3aed,#a78bfa)'],
                    'cables'       => ['#0f766e','#2dd4bf','#ccfbf1','#f0fdfa','linear-gradient(135deg,#0f766e,#2dd4bf)'],
                    'accessories'  => ['#b45309','#f59e0b','#fef3c7','#fffbeb','linear-gradient(135deg,#b45309,#f59e0b)'],
                    'supplies'     => ['#be185d','#f472b6','#fce7f3','#fdf2f8','linear-gradient(135deg,#be185d,#f472b6)'],
                    'default'      => ['#374151','#6b7280','#f3f4f6','#f9fafb','linear-gradient(135deg,#374151,#6b7280)'],
                ];
                foreach($devices as $idx => $d):
                    $avail = $d['total_units'] - $d['in_use'] - $d['overdue'];
                    $pct   = $d['total_units'] > 0 ? round(($avail / $d['total_units']) * 100) : 0;
                    $catKey = strtolower($d['category'] ?? '');
                    $pal = $catPalettes[$catKey] ?? $catPalettes['default'];
                    [$darkColor,$lightColor,$bgLight,$bgLighter,$gradient] = $pal;

                    if ($pct > 60) {
                        $statusLabel = 'Available'; $statusStyle = 'color:#15803d;background:#dcfce7;border:1px solid #86efac';
                        $barGrad = 'linear-gradient(90deg,#4ade80,#16a34a)';
                    } elseif ($pct > 30) {
                        $statusLabel = 'Limited'; $statusStyle = 'color:#92400e;background:#fef3c7;border:1px solid #fcd34d';
                        $barGrad = 'linear-gradient(90deg,#fbbf24,#d97706)';
                    } else {
                        $statusLabel = 'Low Stock'; $statusStyle = 'color:#991b1b;background:#fee2e2;border:1px solid #fca5a5';
                        $barGrad = 'linear-gradient(90deg,#f87171,#dc2626)';
                    }
                    $catIcon = match($catKey) {
                        'computing'    => 'fa-laptop',
                        'av equipment' => 'fa-tv',
                        'cables'       => 'fa-plug',
                        'accessories'  => 'fa-keyboard',
                        'supplies'     => 'fa-box',
                        default        => 'fa-cube',
                    };
                ?>
                <div class="inv-card reveal" style="transition-delay:<?= ($idx % 4) * 60 ?>ms;overflow:hidden">
                    <!-- Colored header band -->
                    <div style="background:<?= $gradient ?>;margin:-18px -18px 16px;padding:16px 18px 14px;position:relative">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;backdrop-filter:blur(4px)">
                                    <i class="fas <?= $catIcon ?>" style="color:#fff;font-size:15px"></i>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:13px;color:#fff;line-height:1.2"><?= htmlspecialchars($d['name']) ?></div>
                                    <div style="font-size:10px;color:rgba(255,255,255,.7);margin-top:1px;text-transform:uppercase;letter-spacing:.06em;font-weight:600"><?= htmlspecialchars($d['category']) ?></div>
                                </div>
                            </div>
                            <span style="font-size:9.5px;font-weight:800;padding:4px 10px;border-radius:99px;white-space:nowrap;<?= $statusStyle ?>"><?= $statusLabel ?></span>
                        </div>
                    </div>

                    <!-- Big number + pct -->
                    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:10px">
                        <div>
                            <div style="font-size:40px;font-weight:900;line-height:1;color:<?= $darkColor ?>;letter-spacing:-.03em"><?= $avail ?></div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:3px;font-weight:500">of <?= $d['total_units'] ?> units free</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:20px;font-weight:900;color:<?= $darkColor ?>;line-height:1"><?= $pct ?>%</div>
                            <div style="font-size:10px;color:#cbd5e1;margin-top:2px;font-weight:500">utilization</div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div style="height:5px;background:#f1f5f9;border-radius:99px;overflow:hidden;margin-bottom:14px">
                        <div class="device-bar-fill" data-width="<?= $pct ?>" style="height:100%;border-radius:99px;background:<?= $barGrad ?>;width:0%"></div>
                    </div>

                    <!-- Stats row -->
                    <div style="display:flex;align-items:center;gap:14px;padding-top:10px;border-top:1px solid #f8fafc">
                        <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#64748b;font-weight:600">
                            <span style="width:6px;height:6px;border-radius:50%;background:<?= $lightColor ?>;display:inline-block"></span>
                            <?= $d['in_use'] ?> in use
                        </span>
                        <?php if($d['overdue'] > 0): ?>
                        <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#ef4444;font-weight:700">
                            <i class="fas fa-exclamation-circle" style="font-size:10px"></i>
                            <?= $d['overdue'] ?> overdue
                        </span>
                        <?php else: ?>
                        <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#22c55e;font-weight:600">
                            <i class="fas fa-check-circle" style="font-size:11px"></i>
                            No overdue
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filters & Table Header -->
        <div style="background:#fff;border-radius:20px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);overflow:hidden">
            <!-- Table toolbar -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f8fafc">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#8b1a2e,#c0392b);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(139,26,46,.25)">
                        <i class="fas fa-list" style="color:#fff;font-size:13px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#0f172a">Borrowing Records</div>
                        <div style="font-size:11.5px;color:#94a3b8;margin-top:1px"><?= count($borrowings) ?> total entries</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="position:relative">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px"></i>
                        <input type="text" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Search borrower or device..." style="padding:9px 14px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;color:#1e293b;width:240px;transition:border .2s,box-shadow .2s" onfocus="this.style.borderColor='#8b1a2e';this.style.boxShadow='0 0 0 3px rgba(139,26,46,.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                    </div>
                    <select id="statusFilter" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;color:#374151;cursor:pointer;transition:border .2s">
                        <option value="">All Status</option>
                        <option value="active"   <?= $statusFilter=='active'?'selected':'' ?>>Active</option>
                        <option value="returned" <?= $statusFilter=='returned'?'selected':'' ?>>Returned</option>
                        <option value="overdue"  <?= $statusFilter=='overdue'?'selected':'' ?>>Overdue</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="borrow-table-wrap">
            <table style="width:100%;border-collapse:collapse;min-width:600px">
                <thead>
                    <tr style="background:#f8fafc">
                        <?php foreach([
                            ['Borrower','fas fa-user'],
                            ['Device','fas fa-laptop'],
                            ['Borrow Date','fas fa-calendar'],
                            ['Return Date','fas fa-calendar-check'],
                            ['Status','fas fa-circle'],
                            ['Actions','fas fa-bolt'],
                        ] as [$h,$icon]): ?>
                        <th style="padding:12px 20px;text-align:left;font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;white-space:nowrap;border-bottom:1px solid #f1f5f9">
                            <span style="display:inline-flex;align-items:center;gap:6px">
                                <i class="<?= $icon ?>" style="font-size:9px;opacity:.7"></i><?= $h ?>
                            </span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $avatarColors = ['#8b1a2e','#7c3aed','#0369a1','#065f46','#92400e','#1e40af','#be185d','#0f766e'];
                    foreach($borrowings as $idx => $b):
                        $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $b['borrower_name']), 0, 2))));
                        $avatarBg = $avatarColors[$idx % count($avatarColors)];
                        [$badgeStyle,$dotColor,$dotPulse] = match($b['status']) {
                            'active'   => ['background:linear-gradient(135deg,#eff6ff,#dbeafe);color:#1d4ed8;border:1px solid #bfdbfe','#3b82f6',true],
                            'returned' => ['background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#15803d;border:1px solid #bbf7d0','#22c55e',false],
                            'overdue'  => ['background:linear-gradient(135deg,#fef2f2,#fee2e2);color:#dc2626;border:1px solid #fecaca','#ef4444',true],
                            default    => ['background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb','#9ca3af',false],
                        };
                    ?>
                    <tr class="borrow-row reveal" style="transition-delay:<?= ($idx % 10) * 35 ?>ms">
                        <!-- Borrower -->
                        <td style="padding:16px 20px;border-bottom:1px solid #f8fafc;vertical-align:middle">
                            <div style="display:flex;align-items:center;gap:12px">
                                <div style="width:38px;height:38px;border-radius:12px;background:<?= $avatarBg ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 3px 8px rgba(0,0,0,.15)">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:13.5px;color:#0f172a"><?= htmlspecialchars($b['borrower_name']) ?></div>
                                    <?php if(!empty($b['borrower_email'])): ?>
                                    <div style="font-size:11.5px;color:#94a3b8;margin-top:2px"><?= htmlspecialchars($b['borrower_email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <!-- Device -->
                        <td style="padding:16px 20px;border-bottom:1px solid #f8fafc;vertical-align:middle">
                            <div style="display:inline-flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:6px 12px">
                                <i class="fas fa-laptop" style="color:#94a3b8;font-size:11px"></i>
                                <span style="font-size:13px;font-weight:600;color:#334155"><?= htmlspecialchars($b['device_label']) ?></span>
                            </div>
                        </td>
                        <!-- Borrow Date -->
                        <td style="padding:16px 20px;border-bottom:1px solid #f8fafc;vertical-align:middle">
                            <div style="font-size:13px;font-weight:600;color:#334155"><?= date('M j, Y', strtotime($b['borrow_date'])) ?></div>
                            <div style="font-size:11.5px;color:#94a3b8;margin-top:2px"><i class="fas fa-clock" style="font-size:10px;margin-right:3px"></i><?= date('H:i', strtotime($b['borrow_date'])) ?></div>
                        </td>
                        <!-- Return Date -->
                        <td style="padding:16px 20px;border-bottom:1px solid #f8fafc;vertical-align:middle">
                            <?php if($b['return_date']): ?>
                            <div style="font-size:13px;font-weight:600;color:#334155"><?= date('M j, Y', strtotime($b['return_date'])) ?></div>
                            <div style="font-size:11.5px;color:#94a3b8;margin-top:2px"><i class="fas fa-clock" style="font-size:10px;margin-right:3px"></i><?= date('H:i', strtotime($b['return_date'])) ?></div>
                            <?php else: ?>
                            <span style="font-size:13px;color:#cbd5e1;font-weight:500">—</span>
                            <?php endif; ?>
                        </td>
                        <!-- Status -->
                        <td style="padding:16px 20px;border-bottom:1px solid #f8fafc;vertical-align:middle">
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;padding:5px 12px;border-radius:99px;<?= $badgeStyle ?>">
                                <span style="width:6px;height:6px;border-radius:50%;background:<?= $dotColor ?>;display:inline-block<?= $dotPulse ? ';animation:pulse-dot 2s infinite' : '' ?>"></span>
                                <?= ucfirst($b['status']) ?>
                            </span>
                        </td>
                        <!-- Actions -->
                        <td style="padding:16px 20px;border-bottom:1px solid #f8fafc;vertical-align:middle">
                            <?php if($b['status'] !== 'returned'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button name="mark_returned" class="ret-btn">
                                    <i class="fas fa-check" style="font-size:11px"></i> Mark Returned
                                </button>
                            </form>
                            <?php else: ?>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#86efac;font-weight:600">
                                <i class="fas fa-check-circle" style="color:#22c55e;font-size:13px"></i> Completed
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($borrowings)): ?>
                    <tr>
                        <td colspan="6" style="padding:60px 20px;text-align:center">
                            <div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                                <i class="fas fa-inbox" style="font-size:22px;color:#cbd5e1"></i>
                            </div>
                            <div style="font-size:14px;font-weight:600;color:#94a3b8">No records found</div>
                            <div style="font-size:12px;color:#cbd5e1;margin-top:4px">Try adjusting your search or filters</div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Device Modal -->
<div id="addDeviceModal" class="hidden modal-overlay">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Add Device to Inventory</h3>
                <p class="text-slate-400 text-xs mt-0.5">Register new equipment in the system</p>
            </div>
            <button onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors"><i class="fas fa-times text-xs"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <div><label class="form-label">Device Name</label>
                <input type="text" name="device_name" required class="form-input" placeholder="e.g. Projector"></div>
            <div><label class="form-label">Category</label>
                <select name="device_category" required class="form-input">
                    <option value="">Select category</option>
                    <option value="AV Equipment">AV Equipment</option>
                    <option value="Computing">Computing</option>
                    <option value="Cables">Cables</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Supplies">Supplies</option>
                    <option value="Other">Other</option>
                </select></div>
            <div><label class="form-label">Total Units</label>
                <input type="number" name="total_units" required min="1" class="form-input" placeholder="e.g. 5"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="flex-1 border border-slate-200 text-slate-600 rounded-xl py-2.5 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" name="add_device" class="flex-1 btn-primary justify-center">Add Device</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div id="borrowModal" class="<?= $showForm?'':'hidden' ?> modal-overlay">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Log Device Borrowing</h3>
                <p class="text-slate-400 text-xs mt-0.5">Record a new equipment borrowing</p>
            </div>
            <button onclick="document.getElementById('borrowModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors"><i class="fas fa-times text-xs"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <div><label class="form-label">Borrower Name</label>
                <input type="text" name="borrower_name" required class="form-input" value="<?= htmlspecialchars($authUser['name']) ?>" placeholder="Enter full name"></div>
            <div><label class="form-label">Email</label>
                <input type="text" class="form-input bg-slate-50 text-slate-400 cursor-not-allowed" value="<?= htmlspecialchars($authUser['email']) ?>" readonly></div>
            <div><label class="form-label">Device</label>
                <select name="device_id" id="deviceSelect" required class="form-input" onchange="updateDeviceLabel(this)">
                    <option value="">Select device</option>
                    <?php foreach($devices as $d):
                        $avail = $d['total_units'] - $d['in_use'] - $d['overdue'];
                        if ($avail <= 0) continue;
                    ?><option value="<?= $d['id'] ?>" data-name="<?= htmlspecialchars($d['name']) ?>" data-avail="<?= $avail ?>">
                        <?= htmlspecialchars($d['name']) ?> (<?= $avail ?> available)
                    </option><?php endforeach; ?>
                </select></div>
            <div><label class="form-label">Device Label</label>
                <input type="text" name="device_label" id="deviceLabel" required class="form-input" placeholder="e.g. Projector #3"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('borrowModal').classList.add('hidden')" class="flex-1 border border-slate-200 text-slate-600 rounded-xl py-2.5 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" name="log_borrowing" class="flex-1 btn-primary justify-center">Log Borrowing</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('searchInput').addEventListener('keyup',function(){const u=new URL(window.location);u.searchParams.set('search',this.value);window.location=u;});
document.getElementById('statusFilter').addEventListener('change',function(){const u=new URL(window.location);u.searchParams.set('status',this.value);window.location=u;});
function updateDeviceLabel(sel) {
    const opt = sel.options[sel.selectedIndex];
    const label = document.getElementById('deviceLabel');
    if (opt && opt.dataset.name) {
        const avail = parseInt(opt.dataset.avail) || 1;
        label.value = opt.dataset.name + ' #' + avail;
    } else {
        label.value = '';
        label.placeholder = 'e.g. Projector #3';
    }
}

// Animate progress bars on load
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        document.querySelectorAll('.device-bar-fill').forEach(function (bar) {
            bar.style.transition = 'width 1s cubic-bezier(.4,0,.2,1)';
            bar.style.width = bar.dataset.width + '%';
        });
    }, 200);
});
</script>

<style>
.inv-card {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 18px;
    padding: 18px 18px 16px;
    position: relative;
    overflow: hidden;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s ease, border-color .2s;
}
.inv-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 14px 32px rgba(0,0,0,.09);
    border-color: #e2e8f0;
}
.inv-card:hover .inv-icon {
    transform: scale(1.12) rotate(-5deg);
}
.inv-icon {
    transition: transform .35s cubic-bezier(.34,1.56,.64,1);
}
@keyframes borderGlow { 0%,100%{opacity:0} 50%{opacity:1} }
@keyframes cardFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-7px)} }

/* Stat pills */
.stat-pill {
    display: flex; align-items: center; gap: 7px;
    border-radius: 99px; padding: 7px 14px;
    font-size: 12px; font-weight: 700;
}
.stat-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.stat-pill-green { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border: 1px solid #86efac; color: #15803d; }
.stat-pill-blue  { background: linear-gradient(135deg,#eff6ff,#dbeafe); border: 1px solid #93c5fd; color: #1d4ed8; }
.stat-pill-red   { background: linear-gradient(135deg,#fef2f2,#fee2e2); border: 1px solid #fca5a5; color: #dc2626; }
@keyframes dotPulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50%      { opacity: .5; transform: scale(1.4); }
}

/* ── Pro Table ── */
.pro-table { width: 100%; border-collapse: collapse; }
.pro-table thead tr {
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.pro-table th {
    padding: 13px 20px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .08em;
    white-space: nowrap;
}
.pro-row td {
    padding: 15px 20px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}
.pro-row:last-child td { border-bottom: none; }
.pro-row {
    transition: background .18s ease, transform .18s ease;
}
.pro-row:hover {
    background: #fdf8f9;
}
.pro-row:hover td:first-child { border-left: 3px solid #8b1a2e; }
.pro-row td:first-child { border-left: 3px solid transparent; transition: border-color .18s; }

.borrower-avatar {
    width: 34px; height: 34px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    color: #fff;
    flex-shrink: 0;
    letter-spacing: .02em;
}

.pro-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 99px;
    white-space: nowrap;
}
.pro-badge-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* New pro row hover */
.borrow-row { transition: background .18s ease; }
.borrow-row:hover { background: #fdf8f9; }
.borrow-row:last-child td { border-bottom: none !important; }

/* Return button */
.ret-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #8b1a2e;
    background: linear-gradient(135deg,#fff5f6,#fdf2f4);
    border: 1.5px solid #f5c6ce;
    border-radius: 9px;
    padding: 6px 14px;
    cursor: pointer;
    transition: all .2s cubic-bezier(.34,1.56,.64,1);
    box-shadow: 0 1px 3px rgba(139,26,46,.08);
}
.ret-btn:hover {
    background: linear-gradient(135deg,#8b1a2e,#c0392b);
    color: #fff;
    border-color: #8b1a2e;
    box-shadow: 0 6px 16px rgba(139,26,46,.3);
    transform: translateY(-2px) scale(1.03);
}
@media (max-width: 768px) {
    .inv-grid { grid-template-columns: repeat(2,1fr) !important; gap: 12px !important; }
    .inv-card { padding: 14px 14px 12px; }
    .borrow-row td { padding: 12px 10px; font-size: 12px; }
    .borrow-row td:nth-child(3), .borrow-row td:nth-child(4) { display: none; }
    /* Make table horizontally scrollable */
    .borrow-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
}
</style>
<?php
$aiContext = [
    'title'       => 'Device Assistant',
    'page'        => 'devices',
    'intro'       => "Hi! I am your Device Borrowing Assistant.\nI can help you find available devices, detect overdue items, and suggest the best equipment for your needs.",
    'suggestions' => ['Which devices are available now?','Show me overdue items','What projectors are free?','Suggest a device for a presentation'],
];
require_once 'includes/ai_widget.php';
?>
</body></html>
