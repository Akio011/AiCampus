<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
requireSuperAdmin();
$pageTitle = 'User Management — AI Campus Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $uid = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    if (in_array($newRole, ['admin','faculty','staff','student']) && $uid !== (int)$authUser['id']) {
        $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, $uid]);
    }
    header('Location: users.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    if ($uid !== (int)$authUser['id']) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
    }
    header('Location: users.php'); exit;
}

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleFilter) { $sql .= " AND role=?"; $params[] = $roleFilter; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll();

// Stats
$totalUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$totalStaff  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<style>
.user-stat-card {
    background:#fff;border-radius:16px;padding:20px 24px;border:1px solid #f1f5f9;
    box-shadow:0 1px 3px rgba(0,0,0,.05),0 4px 16px rgba(0,0,0,.04);
    display:flex;align-items:center;gap:16px;transition:transform .2s,box-shadow .2s;
}
.user-stat-card:hover { transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.09); }
.user-row { transition:background .15s; }
.user-row:hover { background:#fdf8f9; }
.user-row:last-child td { border-bottom:none !important; }
.role-badge { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:99px;white-space:nowrap; }
.save-btn { padding:6px 14px;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s; }
.save-btn:hover { background:#1e293b;transform:translateY(-1px); }
.del-btn { width:32px;height:32px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s; }
.del-btn:hover { background:#dc2626;color:#fff;border-color:#dc2626; }
@media(max-width:768px){
    .user-stats-grid { grid-template-columns:repeat(2,1fr) !important; }
    .hide-mobile { display:none !important; }
}
</style>
<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">User Management</h1>
            <p class="text-slate-400 text-sm mt-0.5">Manage all system users and their roles</p>
        </div>
    </div>

    <div class="p-8">
        <!-- Stats -->
        <div class="user-stats-grid reveal" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px">
            <?php foreach([
                ['fa-users','#8b1a2e','#fdf2f4',$totalUsers,'Total Users','All accounts'],
                ['fa-user-shield','#d97706','#fffbeb',$totalAdmins,'Admins','Admin accounts'],
                ['fa-user-tie','#0369a1','#eff6ff',$totalStaff,'Staff','Staff accounts'],
                ['fa-user-graduate','#15803d','#f0fdf4',$totalStudents,'Students','Student accounts'],
            ] as [$icon,$color,$bg,$count,$label,$sub]): ?>
            <div class="user-stat-card">
                <div style="width:44px;height:44px;border-radius:13px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="fas <?= $icon ?>" style="color:<?= $color ?>;font-size:17px"></i>
                </div>
                <div>
                    <div style="font-size:26px;font-weight:900;color:#0f172a;line-height:1"><?= $count ?></div>
                    <div style="font-size:13px;font-weight:600;color:#374151;margin-top:2px"><?= $label ?></div>
                    <div style="font-size:11px;color:#94a3b8"><?= $sub ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Table card -->
        <div style="background:#fff;border-radius:20px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);overflow:hidden">
            <!-- Toolbar -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f8fafc;flex-wrap:wrap;gap:12px">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,#8b1a2e,#c0392b);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(139,26,46,.25)">
                        <i class="fas fa-users-cog" style="color:#fff;font-size:14px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#0f172a">All Users</div>
                        <div style="font-size:11.5px;color:#94a3b8"><?= count($users) ?> result<?= count($users)!==1?'s':'' ?></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="position:relative">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px"></i>
                        <input type="text" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email..."
                            style="padding:9px 14px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;width:220px;transition:border .2s"
                            onfocus="this.style.borderColor='#8b1a2e'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <select id="roleFilter" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;cursor:pointer">
                        <option value="">All Roles</option>
                        <?php foreach(['admin','faculty','staff','student'] as $r): ?>
                        <option value="<?= $r ?>" <?= $roleFilter===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
            <table style="width:100%;border-collapse:collapse;min-width:680px">
                <thead>
                    <tr style="background:#f8fafc">
                        <?php foreach([['User','fa-user'],['Email','fa-envelope'],['Role','fa-tag'],['Joined','fa-calendar'],['Actions','fa-bolt']] as [$h,$ic]): ?>
                        <th style="padding:12px 20px;text-align:left;font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid #f1f5f9;white-space:nowrap">
                            <span style="display:inline-flex;align-items:center;gap:6px"><i class="fas <?= $ic ?>" style="font-size:9px;opacity:.6"></i><?= $h ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $avatarColors = ['#8b1a2e','#7c3aed','#0369a1','#065f46','#92400e','#1e40af','#be185d','#0f766e'];
                $roleStyles = [
                    'super_admin' => 'background:linear-gradient(135deg,#fdf2f4,#fce8ec);color:#8b1a2e;border:1px solid #f5c6ce',
                    'admin'       => 'background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;border:1px solid #fcd34d',
                    'faculty'     => 'background:linear-gradient(135deg,#eff6ff,#dbeafe);color:#1d4ed8;border:1px solid #bfdbfe',
                    'staff'       => 'background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#15803d;border:1px solid #bbf7d0',
                    'student'     => 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb',
                ];
                foreach($users as $idx => $u):
                    $initials = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ',$u['name']),0,2))));
                    $isSelf = $u['id'] == $authUser['id'];
                ?>
                <tr class="user-row" style="border-bottom:1px solid #f8fafc">
                    <td style="padding:16px 20px;vertical-align:middle">
                        <div style="display:flex;align-items:center;gap:12px">
                            <?php if($u['avatar']): ?>
                            <img src="<?= htmlspecialchars($u['avatar']) ?>" referrerpolicy="no-referrer"
                                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #f1f5f9">
                            <?php else: ?>
                            <div style="width:38px;height:38px;border-radius:50%;background:<?= $avatarColors[$idx%count($avatarColors)] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0">
                                <?= $initials ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:700;font-size:13.5px;color:#0f172a"><?= htmlspecialchars($u['name']) ?></div>
                                <?php if($isSelf): ?>
                                <div style="font-size:10px;color:#8b1a2e;font-weight:600">● You</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="padding:16px 20px;vertical-align:middle;font-size:13px;color:#64748b" class="hide-mobile">
                        <?= htmlspecialchars($u['email']) ?>
                    </td>
                    <td style="padding:16px 20px;vertical-align:middle">
                        <span class="role-badge" style="<?= $roleStyles[$u['role']] ?? $roleStyles['student'] ?>">
                            <?= ucfirst(str_replace('_',' ',$u['role'])) ?>
                        </span>
                    </td>
                    <td style="padding:16px 20px;vertical-align:middle;font-size:12px;color:#94a3b8;white-space:nowrap" class="hide-mobile">
                        <?= date('M j, Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td style="padding:16px 20px;vertical-align:middle">
                        <?php if(!$isSelf): ?>
                        <div style="display:flex;align-items:center;gap:8px">
                            <form method="POST" style="display:inline-flex;align-items:center;gap:6px">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;outline:none;cursor:pointer">
                                    <?php foreach(['admin','faculty','staff','student'] as $r): ?>
                                    <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button name="update_role" class="save-btn">Save</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this user?')">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button name="delete_user" class="del-btn" title="Delete user">
                                    <i class="fas fa-trash" style="font-size:11px"></i>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span style="font-size:12px;color:#cbd5e1;font-style:italic">Current user</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($users)): ?>
                <tr><td colspan="5" style="padding:60px 20px;text-align:center">
                    <div style="width:52px;height:52px;border-radius:14px;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                        <i class="fas fa-users" style="font-size:20px;color:#cbd5e1"></i>
                    </div>
                    <div style="font-size:14px;font-weight:600;color:#94a3b8">No users found</div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('searchInput').addEventListener('keyup',function(){const u=new URL(window.location);u.searchParams.set('search',this.value);window.location=u;});
document.getElementById('roleFilter').addEventListener('change',function(){const u=new URL(window.location);u.searchParams.set('role',this.value);window.location=u;});
</script>
<?php require_once 'includes/ai_widget.php'; ?>
</body></html>
