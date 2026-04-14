<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
requireSuperAdmin();
$pageTitle = 'User Management — AI Campus Management';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $uid  = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    $allowed = ['super_admin','admin','faculty','staff','student'];
    if (in_array($newRole, $allowed) && $uid !== (int)$authUser['id']) {
        $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, $uid]);
    }
    header('Location: users.php'); exit;
}

// Handle delete
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

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">User Management</h1>
            <p class="text-slate-400 text-sm mt-0.5">Manage all system users and their roles</p>
        </div>
    </div>

    <div class="p-8">
        <!-- Filters -->
        <div style="background:#fff;border-radius:20px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f8fafc">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#8b1a2e,#c0392b);display:flex;align-items:center;justify-content:center">
                        <i class="fas fa-users" style="color:#fff;font-size:13px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#0f172a">All Users</div>
                        <div style="font-size:11.5px;color:#94a3b8"><?= count($users) ?> total</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="position:relative">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px"></i>
                        <input type="text" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email..." style="padding:9px 14px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;width:220px">
                    </div>
                    <select id="roleFilter" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none">
                        <option value="">All Roles</option>
                        <?php foreach(['admin','faculty','staff','student'] as $r): ?>
                        <option value="<?= $r ?>" <?= $roleFilter===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
            <table style="width:100%;border-collapse:collapse;min-width:700px">
                <thead>
                    <tr style="background:#f8fafc">
                        <?php foreach(['User','Email','Role','Joined','Actions'] as $h): ?>
                        <th style="padding:12px 20px;text-align:left;font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid #f1f5f9"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $avatarColors = ['#8b1a2e','#7c3aed','#0369a1','#065f46','#92400e','#1e40af'];
                foreach($users as $idx => $u):
                    $initials = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ',$u['name']),0,2))));
                    $isSelf = $u['id'] == $authUser['id'];
                    $roleColors = [
                        'super_admin' => 'background:#fdf2f4;color:#8b1a2e;border:1px solid #f5c6ce',
                        'admin'       => 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d',
                        'faculty'     => 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe',
                        'staff'       => 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0',
                        'student'     => 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb',
                    ];
                ?>
                <tr style="border-bottom:1px solid #f8fafc">
                    <td style="padding:16px 20px;vertical-align:middle">
                        <div style="display:flex;align-items:center;gap:10px">
                            <?php if($u['avatar']): ?>
                            <img src="<?= htmlspecialchars($u['avatar']) ?>" referrerpolicy="no-referrer" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0">
                            <?php else: ?>
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $avatarColors[$idx%count($avatarColors)] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0"><?= $initials ?></div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:700;font-size:13.5px;color:#0f172a"><?= htmlspecialchars($u['name']) ?></div>
                                <?php if($isSelf): ?><div style="font-size:10px;color:#94a3b8">(You)</div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="padding:16px 20px;font-size:13px;color:#64748b;vertical-align:middle"><?= htmlspecialchars($u['email']) ?></td>
                    <td style="padding:16px 20px;vertical-align:middle">
                        <span style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:99px;<?= $roleColors[$u['role']] ?? $roleColors['student'] ?>"><?= ucfirst(str_replace('_',' ',$u['role'])) ?></span>
                    </td>
                    <td style="padding:16px 20px;font-size:12px;color:#94a3b8;vertical-align:middle"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td style="padding:16px 20px;vertical-align:middle">
                        <?php if(!$isSelf): ?>
                        <div style="display:flex;align-items:center;gap:8px">
                            <form method="POST" style="display:inline-flex;align-items:center;gap:6px">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;outline:none">
                                    <?php foreach(['admin','faculty','staff','student'] as $r): ?>
                                    <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button name="update_role" style="padding:6px 12px;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer">Save</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this user?')">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button name="delete_user" style="padding:6px 12px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer">
                                    <i class="fas fa-trash" style="font-size:11px"></i>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span style="font-size:12px;color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
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
