<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
$pageTitle = 'Community Service — AI Campus Management';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    requireStaff();
    if (isset($_POST['assign_service'])) {
        $pdo->prepare("INSERT INTO community_service(student_name,student_id,violation,required_hours,supervisor,status) VALUES(?,?,?,?,?,'pending')")
            ->execute([$_POST['student_name'],$_POST['student_id'],$_POST['violation'],$_POST['required_hours'],$_POST['supervisor']]);
        header('Location: community.php'); exit;
    }
    if (isset($_POST['log_hours'])) {
        $cs = $pdo->prepare("SELECT * FROM community_service WHERE id=?"); $cs->execute([$_POST['id']]); $cs=$cs->fetch();
        $newHours = min($cs['completed_hours']+(int)$_POST['hours'], $cs['required_hours']);
        $status   = $newHours>=$cs['required_hours'] ? 'completed' : 'in_progress';
        $pdo->prepare("UPDATE community_service SET completed_hours=?,status=? WHERE id=?")->execute([$newHours,$status,$_POST['id']]);
        $pdo->prepare("INSERT INTO activity_log(description,icon,color) VALUES(?,?,?)")
            ->execute([$cs['student_name'].' completed '.$_POST['hours'].' community service hours','check','green']);
        header('Location: community.php'); exit;
    }
}

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$showForm = isset($_GET['action']) && $_GET['action']==='assign';

$sql = "SELECT * FROM community_service WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (student_name LIKE ? OR student_id LIKE ? OR violation LIKE ?)"; $params=["%$search%","%$search%","%$search%"]; }
if ($statusFilter) { $sql .= " AND status=?"; $params[]=$statusFilter; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$records = $stmt->fetchAll();

$pending    = (int)$pdo->query("SELECT COUNT(*) FROM community_service WHERE status='pending'")->fetchColumn();
$inProgress = (int)$pdo->query("SELECT COUNT(*) FROM community_service WHERE status='in_progress'")->fetchColumn();
$completed  = (int)$pdo->query("SELECT COUNT(*) FROM community_service WHERE status='completed'")->fetchColumn();
$totalPendingHours = (int)$pdo->query("SELECT COALESCE(SUM(required_hours-completed_hours),0) FROM community_service WHERE status!='completed'")->fetchColumn();
$totalAll = $pending + $inProgress + $completed;
$completionRate = $totalAll > 0 ? round(($completed / $totalAll) * 100) : 0;

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>

<style>
/* ── Workflow pipeline ── */
.pipeline {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    margin-bottom: 28px;
}
.pipeline-stage {
    padding: 24px 28px;
    position: relative;
    transition: background .2s;
}
.pipeline-stage:not(:last-child) {
    border-right: 1px solid #f1f5f9;
}
.pipeline-stage:not(:last-child)::after {
    content: '';
    position: absolute;
    right: -10px; top: 50%;
    transform: translateY(-50%);
    width: 0; height: 0;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
    border-left: 10px solid #f1f5f9;
    z-index: 2;
}
.pipeline-stage:not(:last-child)::before {
    content: '';
    position: absolute;
    right: -8px; top: 50%;
    transform: translateY(-50%);
    width: 0; height: 0;
    border-top: 7px solid transparent;
    border-bottom: 7px solid transparent;
    border-left: 9px solid #fff;
    z-index: 3;
}
.pipeline-stage:hover { background: #fafafa; }

/* Progress ring */
.ring-wrap { position: relative; width: 56px; height: 56px; flex-shrink: 0; }
.ring-wrap svg { transform: rotate(-90deg); }
.ring-bg  { fill: none; stroke: #f1f5f9; stroke-width: 4; }
.ring-fill { fill: none; stroke-width: 4; stroke-linecap: round; transition: stroke-dashoffset 1.2s cubic-bezier(.4,0,.2,1); }
.ring-label {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800; color: #1e293b;
}

/* Workflow cards */
.wf-card {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    padding: 20px;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s ease;
    position: relative;
    overflow: hidden;
}
.wf-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0,0,0,.09);
}
.wf-card-accent {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 16px 16px 0 0;
}

/* Status pill */
.status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700;
    padding: 3px 10px; border-radius: 99px;
}
.status-pill-dot { width: 6px; height: 6px; border-radius: 50%; }

/* Inline progress bar */
.wf-bar-track { height: 6px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.wf-bar-fill  { height: 100%; border-radius: 99px; transition: width 1s cubic-bezier(.4,0,.2,1); }

/* Pro table */
.pro-table { width: 100%; border-collapse: collapse; }
.pro-table thead tr { background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
.pro-table th { padding: 13px 20px; text-align: left; font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; }
.pro-row td { padding: 15px 20px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.pro-row:last-child td { border-bottom: none; }
.pro-row { transition: background .15s; }
.pro-row:hover { background: #fdf8f9; }
.pro-row:hover td:first-child { border-left: 3px solid #8b1a2e; }
.pro-row td:first-child { border-left: 3px solid transparent; transition: border-color .15s; }

.student-avatar {
    width: 34px; height: 34px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800; color: #fff; flex-shrink: 0;
}
.pro-action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 600;
    color: #8b1a2e; background: #fdf2f4;
    border: 1px solid #f5c6ce; border-radius: 8px;
    padding: 5px 12px; cursor: pointer;
    transition: background .18s, box-shadow .18s, transform .18s;
}
.pro-action-btn:hover {
    background: #8b1a2e; color: #fff;
    border-color: #8b1a2e;
    box-shadow: 0 4px 12px rgba(139,26,46,.25);
    transform: translateY(-1px);
}

@keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
.anim-in { opacity:0; animation: fadeUp .45s cubic-bezier(.22,.68,0,1.2) forwards; }
@media(max-width:768px){
    .pro-table th:nth-child(3),.pro-table td:nth-child(3){ display:none; }
    .pro-row td { padding:12px 12px; font-size:12px; }
    .pipeline { flex-direction:column !important; gap:12px !important; }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Community Service Workflow</h1>
            <p class="text-slate-400 text-sm mt-0.5">Track student service assignments through every stage</p>
        </div>
        <?php if(isStaff()): ?>
        <button onclick="document.getElementById('assignModal').classList.remove('hidden')" class="btn-primary">
            <i class="fas fa-plus text-xs"></i> Assign Service
        </button>
        <?php endif; ?>
    </div>

    <div class="p-8">

        <!-- ── Workflow Pipeline ── -->
        <div class="pipeline anim-in" style="animation-delay:.05s">
            <?php
            $stages = [
                ['label'=>'Pending',     'sub'=>'Awaiting start',      'count'=>$pending,    'color'=>'#f59e0b','bg'=>'#fffbeb','icon'=>'fa-clock'],
                ['label'=>'In Progress', 'sub'=>'Currently serving',   'count'=>$inProgress, 'color'=>'#3b82f6','bg'=>'#eff6ff','icon'=>'fa-spinner'],
                ['label'=>'Completed',   'sub'=>'Fully served',        'count'=>$completed,  'color'=>'#22c55e','bg'=>'#f0fdf4','icon'=>'fa-check-circle'],
            ];
            foreach($stages as $s):
                $r = 24; $circ = round(2 * M_PI * $r, 2);
                $stagePct = $totalAll > 0 ? round(($s['count']/$totalAll)*100) : 0;
                $offset = round($circ - ($stagePct/100)*$circ, 2);
            ?>
            <div class="pipeline-stage">
                <div style="display:flex;align-items:center;gap:16px">
                    <div class="ring-wrap">
                        <svg width="56" height="56" viewBox="0 0 56 56">
                            <circle class="ring-bg" cx="28" cy="28" r="<?= $r ?>"/>
                            <circle class="ring-fill" cx="28" cy="28" r="<?= $r ?>"
                                stroke="<?= $s['color'] ?>"
                                stroke-dasharray="<?= $circ ?>"
                                stroke-dashoffset="<?= $circ ?>"
                                data-offset="<?= $offset ?>"/>
                        </svg>
                        <div class="ring-label"><?= $s['count'] ?></div>
                    </div>
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
                            <div style="width:28px;height:28px;border-radius:8px;background:<?= $s['bg'] ?>;display:flex;align-items:center;justify-content:center">
                                <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;font-size:12px"></i>
                            </div>
                            <span style="font-weight:700;font-size:14px;color:#1e293b"><?= $s['label'] ?></span>
                        </div>
                        <div style="font-size:12px;color:#94a3b8"><?= $s['sub'] ?></div>
                        <div style="font-size:11px;font-weight:600;color:<?= $s['color'] ?>;margin-top:4px"><?= $stagePct ?>% of total</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Summary bar ── -->
        <div class="anim-in" style="animation-delay:.12s;background:#fff;border:1px solid #f1f5f9;border-radius:16px;padding:18px 24px;margin-bottom:28px;display:flex;align-items:center;gap:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="flex:1">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span style="font-size:13px;font-weight:600;color:#1e293b">Overall Completion Rate</span>
                    <span style="font-size:13px;font-weight:800;color:#8b1a2e"><?= $completionRate ?>%</span>
                </div>
                <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden">
                    <div id="overallBar" style="height:100%;border-radius:99px;background:linear-gradient(90deg,#8b1a2e,#c0392b);width:0%;transition:width 1.2s cubic-bezier(.4,0,.2,1)" data-width="<?= $completionRate ?>"></div>
                </div>
            </div>
            <div style="display:flex;gap:20px;flex-shrink:0">
                <div style="text-align:center">
                    <div style="font-size:20px;font-weight:900;color:#f59e0b"><?= $pending ?></div>
                    <div style="font-size:11px;color:#94a3b8">Pending</div>
                </div>
                <div style="width:1px;background:#f1f5f9"></div>
                <div style="text-align:center">
                    <div style="font-size:20px;font-weight:900;color:#3b82f6"><?= $inProgress ?></div>
                    <div style="font-size:11px;color:#94a3b8">In Progress</div>
                </div>
                <div style="width:1px;background:#f1f5f9"></div>
                <div style="text-align:center">
                    <div style="font-size:20px;font-weight:900;color:#22c55e"><?= $completed ?></div>
                    <div style="font-size:11px;color:#94a3b8">Completed</div>
                </div>
                <div style="width:1px;background:#f1f5f9"></div>
                <div style="text-align:center">
                    <div style="font-size:20px;font-weight:900;color:#8b1a2e"><?= $totalPendingHours ?>h</div>
                    <div style="font-size:11px;color:#94a3b8">Remaining</div>
                </div>
            </div>
        </div>

        <!-- ── Filters ── -->
        <div class="flex items-center gap-3 mb-5 anim-in" style="animation-delay:.18s">
            <div class="search-bar flex-1"><i class="fas fa-search"></i>
                <input type="text" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Search by student name, ID, or violation...">
            </div>
            <select id="statusFilter" class="filter-select">
                <option value="">All Stages</option>
                <option value="pending"     <?= $statusFilter=='pending'?'selected':'' ?>>Pending</option>
                <option value="in_progress" <?= $statusFilter=='in_progress'?'selected':'' ?>>In Progress</option>
                <option value="completed"   <?= $statusFilter=='completed'?'selected':'' ?>>Completed</option>
            </select>
        </div>

        <!-- ── Pro Table ── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden anim-in" style="animation-delay:.22s">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f8fafc">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:32px;height:32px;border-radius:9px;background:#fdf2f4;display:flex;align-items:center;justify-content:center">
                        <i class="fas fa-list-check" style="color:#8b1a2e;font-size:13px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#1e293b">Service Records</div>
                        <div style="font-size:12px;color:#94a3b8"><?= count($records) ?> assignments</div>
                    </div>
                </div>
            </div>
            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
            <table class="pro-table" style="min-width:700px">
                <thead><tr>
                    <?php foreach(['Student','Violation','Supervisor','Hours Progress','Status','Actions'] as $h): ?>
                    <th><?= $h ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php
                $avatarColors = ['#8b1a2e','#7c3aed','#0369a1','#065f46','#92400e','#1e40af','#be185d','#0f766e'];
                foreach($records as $idx => $r):
                    $pct = $r['required_hours']>0 ? min(round(($r['completed_hours']/$r['required_hours'])*100),100) : 0;
                    $initials = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ',$r['student_name']),0,2))));
                    $avatarBg = $avatarColors[$idx % count($avatarColors)];
                    [$barColor,$pillStyle,$pillDot] = match($r['status']) {
                        'completed'   => ['#22c55e','background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0','#22c55e'],
                        'in_progress' => ['#3b82f6','background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe','#3b82f6'],
                        default       => ['#f59e0b','background:#fffbeb;color:#b45309;border:1px solid #fde68a','#f59e0b'],
                    };
                ?>
                <tr class="pro-row reveal" style="transition-delay:<?= ($idx%8)*40 ?>ms">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="student-avatar" style="background:<?= $avatarBg ?>"><?= $initials ?></div>
                            <div>
                                <div style="font-weight:700;font-size:13px;color:#1e293b"><?= htmlspecialchars($r['student_name']) ?></div>
                                <div style="font-size:11px;color:#94a3b8;margin-top:1px"><?= htmlspecialchars($r['student_id']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:13px;color:#475569;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['violation']) ?></div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:#475569">
                            <i class="fas fa-user-tie" style="color:#94a3b8;font-size:11px"></i>
                            <?= htmlspecialchars($r['supervisor']) ?>
                        </div>
                    </td>
                    <td style="min-width:160px">
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                            <span style="font-size:12px;color:#64748b;font-weight:600"><?= $r['completed_hours'] ?>/<?= $r['required_hours'] ?>h</span>
                            <span style="font-size:12px;font-weight:800;color:<?= $barColor ?>"><?= $pct ?>%</span>
                        </div>
                        <div class="wf-bar-track">
                            <div class="wf-bar-fill" data-width="<?= $pct ?>" style="background:<?= $barColor ?>;width:0%"></div>
                        </div>
                    </td>
                    <td>
                        <span class="status-pill" style="<?= $pillStyle ?>">
                            <span class="status-pill-dot" style="background:<?= $pillDot ?>"></span>
                            <?= ucfirst(str_replace('_',' ',$r['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($r['status']!=='completed' && isStaff()): ?>
                        <button onclick="openLogHours(<?= $r['id'] ?>,'<?= htmlspecialchars($r['student_name'],ENT_QUOTES) ?>')" class="pro-action-btn">
                            <i class="fas fa-plus text-xs"></i> Log Hours
                        </button>
                        <?php else: ?>
                        <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#94a3b8">
                            <i class="fas fa-check-circle" style="color:#22c55e"></i> Done
                        </span>
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

<!-- Assign Modal -->
<div id="assignModal" class="<?= $showForm?'':'hidden' ?> modal-overlay">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Assign Community Service</h3>
                <p class="text-slate-400 text-xs mt-0.5">Create a new service assignment</p>
            </div>
            <button onclick="document.getElementById('assignModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors"><i class="fas fa-times text-xs"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div><label class="form-label">Student Name</label>
                    <input type="text" name="student_name" required class="form-input"></div>
                <div><label class="form-label">Student ID</label>
                    <input type="text" name="student_id" required class="form-input" placeholder="2024-00001"></div>
            </div>
            <div><label class="form-label">Violation</label>
                <input type="text" name="violation" required class="form-input"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="form-label">Required Hours</label>
                    <input type="number" name="required_hours" min="1" required class="form-input"></div>
                <div><label class="form-label">Supervisor</label>
                    <input type="text" name="supervisor" required class="form-input"></div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" class="flex-1 border border-slate-200 text-slate-600 rounded-xl py-2.5 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" name="assign_service" class="flex-1 btn-primary justify-center">Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Log Hours Modal -->
<div id="logHoursModal" class="hidden modal-overlay">
    <div class="modal-box" style="max-width:380px">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Log Service Hours</h3>
                <p class="text-slate-400 text-xs mt-0.5">For: <span id="logHoursName" class="font-semibold text-slate-700"></span></p>
            </div>
            <button onclick="document.getElementById('logHoursModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors"><i class="fas fa-times text-xs"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" id="logHoursId">
            <div><label class="form-label">Hours Completed</label>
                <input type="number" name="hours" min="1" required class="form-input" placeholder="e.g. 5"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('logHoursModal').classList.add('hidden')" class="flex-1 border border-slate-200 text-slate-600 rounded-xl py-2.5 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" name="log_hours" class="flex-1 btn-primary justify-center">Log Hours</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLogHours(id,name){
    document.getElementById('logHoursId').value=id;
    document.getElementById('logHoursName').textContent=name;
    document.getElementById('logHoursModal').classList.remove('hidden');
}
document.getElementById('searchInput').addEventListener('keyup',function(){const u=new URL(window.location);u.searchParams.set('search',this.value);window.location=u;});
document.getElementById('statusFilter').addEventListener('change',function(){const u=new URL(window.location);u.searchParams.set('status',this.value);window.location=u;});

document.addEventListener('DOMContentLoaded', function () {
    // Animate progress bars
    setTimeout(function () {
        document.querySelectorAll('.wf-bar-fill').forEach(function(b){
            b.style.width = b.dataset.width + '%';
        });
        document.getElementById('overallBar').style.width =
            document.getElementById('overallBar').dataset.width + '%';
    }, 150);

    // Animate SVG rings
    document.querySelectorAll('.ring-fill').forEach(function(circle){
        setTimeout(function(){
            circle.style.transition = 'stroke-dashoffset 1.2s cubic-bezier(.4,0,.2,1)';
            circle.style.strokeDashoffset = circle.dataset.offset;
        }, 200);
    });
});
</script>

<?php
$aiContext = [
    'title'       => 'Service Assistant',
    'page'        => 'community',
    'intro'       => "Hi! I am your Community Service Assistant.\nI can help you track pending assignments, monitor progress, and view completed service hours.",
    'suggestions' => ['Show pending assignments','Who is in progress?','Show completed assignments','Give me a summary'],
];
require_once 'includes/ai_widget.php';
?>
</body></html>
