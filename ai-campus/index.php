<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
$pageTitle = 'Dashboard — AI Campus Management';

$activeBorrowings = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status='active'")->fetchColumn();
$lostItems        = $pdo->query("SELECT COUNT(*) FROM lost_found WHERE status='active'")->fetchColumn();
$capstoneProjects = $pdo->query("SELECT COUNT(*) FROM capstone_projects")->fetchColumn();
$pendingHours     = $pdo->query("SELECT COALESCE(SUM(required_hours-completed_hours),0) FROM community_service WHERE status!='completed'")->fetchColumn();
$activities       = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 6")->fetchAll();
$overdueCount     = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status='overdue'")->fetchColumn();

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<div class="main-content">
    <!-- Top bar -->
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Dashboard Overview</h1>
            <p class="text-slate-400 text-sm mt-0.5">Monitor and manage all department operations in one place</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-teal-50 border border-teal-100 rounded-xl px-4 py-2">
                <span class="w-2 h-2 rounded-full bg-teal-500 pulse-dot"></span>
                <span class="text-teal-700 text-xs font-semibold">AI Active</span>
            </div>
            <div class="text-slate-400 text-sm"><?= date('l, F j, Y') ?></div>
        </div>
    </div>

    <div class="p-8">
        <!-- Stat cards -->
        <div class="grid grid-cols-4 gap-5 mb-8">
            <?php
            $cards = [
                ['icon'=>'fa-laptop','color'=>'teal','count'=>$activeBorrowings,'label'=>'Active Borrowings','change'=>'+3 today','changeColor'=>'text-teal-500','changeIcon'=>'fa-arrow-up','href'=>'devices.php'],
                ['icon'=>'fa-search-location','color'=>'orange','count'=>$lostItems,'label'=>'Unclaimed Items','change'=>'2 AI-matched','changeColor'=>'text-orange-500','changeIcon'=>'fa-robot','href'=>'lost-found.php'],
                ['icon'=>'fa-layer-group','color'=>'purple','count'=>$capstoneProjects,'label'=>'Capstone Projects','change'=>'+4 this sem','changeColor'=>'text-purple-500','changeIcon'=>'fa-arrow-up','href'=>'capstone.php'],
                ['icon'=>'fa-clock','color'=>'green','count'=>$pendingHours,'label'=>'Pending Hours','change'=>'5 at risk','changeColor'=>'text-green-500','changeIcon'=>'fa-exclamation-circle','href'=>'community.php'],
            ];
            $colorMap = [
                'teal'   => ['bg'=>'bg-teal-50','icon'=>'bg-teal-100','text'=>'text-teal-600','border'=>'border-teal-200'],
                'orange' => ['bg'=>'bg-orange-50','icon'=>'bg-orange-100','text'=>'text-orange-600','border'=>'border-orange-200'],
                'purple' => ['bg'=>'bg-purple-50','icon'=>'bg-purple-100','text'=>'text-purple-600','border'=>'border-purple-200'],
                'green'  => ['bg'=>'bg-emerald-50','icon'=>'bg-emerald-100','text'=>'text-emerald-600','border'=>'border-emerald-200'],
            ];
            foreach($cards as $i => $c):
                $colors = $colorMap[$c['color']];
            ?>
            <a href="<?= $c['href'] ?>" class="stat-card-modern anim-card group relative overflow-hidden block no-underline" style="border-left:4px solid; border-left-color:<?= $c['color']=='teal'?'#14b8a6':($c['color']=='orange'?'#f97316':($c['color']=='purple'?'#a855f7':'#10b981')) ?>;animation-delay:<?= $i * 80 ?>ms;cursor:pointer;text-decoration:none">
                <div class="relative">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl <?= $colors['icon'] ?> flex items-center justify-center">
                            <i class="fas <?= $c['icon'] ?> <?= $colors['text'] ?> text-lg"></i>
                        </div>
                        <i class="fas fa-arrow-up-right-from-square text-slate-300 text-xs mt-1 group-hover:text-slate-500 transition-colors"></i>
                    </div>
                    <div class="text-4xl font-bold text-slate-800 mb-2"><?= number_format($c['count']) ?></div>
                    <div class="text-sm font-semibold text-slate-600 mb-3"><?= $c['label'] ?></div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold <?= $c['changeColor'] ?> <?= $c['color']=='orange'?'bg-orange-50':'bg-slate-50' ?> rounded-full px-2.5 py-1 w-fit">
                        <i class="fas <?= $c['changeIcon'] ?>"></i>
                        <span><?= $c['change'] ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-3 gap-6 mb-6">
            <!-- Recent Activity -->
            <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
                            <i class="fas fa-bolt text-slate-500 text-xs"></i>
                        </div>
                        <span class="font-semibold text-slate-800 text-sm">Recent Activity</span>
                    </div>
                    <a href="devices.php" class="text-rose-500 text-xs font-semibold hover:text-rose-700 transition-colors">View all →</a>
                </div>
                <div class="divide-y divide-slate-50">
                    <?php
                    $iconMap  = ['device'=>'fa-laptop','search'=>'fa-search','book'=>'fa-layer-group','check'=>'fa-check-circle','teal'=>'fa-undo'];
                    $gradMap  = ['blue'=>'from-blue-400 to-blue-500','orange'=>'from-orange-400 to-orange-500','purple'=>'from-violet-400 to-purple-500','green'=>'from-emerald-400 to-teal-500','teal'=>'from-teal-400 to-cyan-500'];
                    $pageMap  = ['device'=>'devices.php','search'=>'lost-found.php','book'=>'capstone.php','check'=>'community.php','teal'=>'devices.php'];
                    foreach($activities as $a):
                        $ic   = $iconMap[$a['icon']] ?? 'fa-circle';
                        $gr   = $gradMap[$a['color']] ?? 'from-slate-400 to-slate-500';
                        $href = $pageMap[$a['icon']] ?? '#';
                        $ago  = human_time_diff(strtotime($a['created_at']));
                    ?>
                    <a href="<?= $href ?>" class="flex items-center gap-4 px-6 py-3.5 hover:bg-rose-50/60 transition-all group cursor-pointer no-underline" style="text-decoration:none">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br <?= $gr ?> flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <i class="fas <?= $ic ?> text-white text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-700 truncate group-hover:text-slate-900 transition-colors"><?= htmlspecialchars($a['description']) ?></p>
                        </div>
                        <span class="text-xs text-slate-400 flex-shrink-0 group-hover:text-slate-600 transition-colors"><?= $ago ?></span>
                        <i class="fas fa-chevron-right text-slate-200 text-xs flex-shrink-0 group-hover:text-rose-400 group-hover:translate-x-1 transition-all"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-50">
                    <span class="font-bold text-slate-800 text-base">Quick Actions</span>
                    <p class="text-slate-400 text-xs mt-0.5">Jump to any module instantly</p>
                </div>
                <div class="p-4 grid grid-cols-2 gap-3">
                    <?php $actions = [
                        ['href'=>'devices.php?action=log',    'icon'=>'fa-plus-circle',   'label'=>'Log Borrowing',      'bg'=>'bg-teal-50',   'iconBg'=>'bg-teal-100',   'iconColor'=>'text-teal-600'],
                        ['href'=>'lost-found.php?action=post','icon'=>'fa-file-arrow-up', 'label'=>'Post Item',          'bg'=>'bg-amber-50',  'iconBg'=>'bg-amber-100',  'iconColor'=>'text-amber-500'],
                        ['href'=>'capstone.php',              'icon'=>'fa-folder-open',   'label'=>'Browse Projects',    'bg'=>'bg-purple-50', 'iconBg'=>'bg-purple-100', 'iconColor'=>'text-purple-500'],
                        ['href'=>'community.php?action=assign','icon'=>'fa-clock',        'label'=>'Manage Hours',       'bg'=>'bg-emerald-50','iconBg'=>'bg-emerald-100','iconColor'=>'text-emerald-500'],
                    ]; foreach($actions as $act): ?>
                    <a href="<?= $act['href'] ?>" class="quick-action-card <?= $act['bg'] ?> rounded-2xl p-4 flex flex-col items-center justify-center gap-3 hover:scale-105 transition-transform">
                        <div class="w-14 h-14 <?= $act['iconBg'] ?> rounded-2xl flex items-center justify-center">
                            <i class="fas <?= $act['icon'] ?> <?= $act['iconColor'] ?> text-xl"></i>
                        </div>
                        <span class="text-sm font-bold text-slate-700"><?= $act['label'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- AI Banner -->
        <div class="rounded-2xl overflow-hidden relative" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border:1px solid rgba(255,255,255,.06)">
            <div class="absolute inset-0 opacity-30" style="background:radial-gradient(ellipse at 20% 50%,rgba(20,184,166,.3) 0%,transparent 60%),radial-gradient(ellipse at 80% 50%,rgba(14,165,233,.2) 0%,transparent 60%)"></div>
            <div class="relative px-8 py-6">
                <div class="flex items-center gap-4 mb-5">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:linear-gradient(135deg,#14b8a6,#0ea5e9);box-shadow:0 0 24px rgba(20,184,166,.4)">
                        <i class="fas fa-robot text-white text-lg"></i>
                    </div>
                    <div>
                        <div class="text-white font-bold text-lg">AI Assistant Ready</div>
                        <div class="text-slate-400 text-sm">Smart recommendations and automated insights</div>
                    </div>
                    <div class="ml-auto flex items-center gap-2 bg-teal-500/10 border border-teal-500/20 rounded-xl px-4 py-2">
                        <span class="w-2 h-2 rounded-full bg-teal-400 pulse-dot"></span>
                        <span class="text-teal-300 text-xs font-semibold">Online</span>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-3">
                    <?php $aiFeatures = [
                        ['fa-lightbulb','Smart device availability suggestions','text-yellow-400','devices','Which devices are available now?'],
                        ['fa-bell','Automatic overdue item detection','text-red-400','devices','Show me overdue items'],
                        ['fa-project-diagram','Related capstone recommendations','text-violet-400','capstone','Show me the latest projects'],
                        ['fa-calendar-check','Service hour completion predictions','text-teal-400','community','Give me a summary of community service hours'],
                    ]; foreach($aiFeatures as $f): ?>
                    <button onclick="askAIInsight('<?= $f[3] ?>','<?= addslashes($f[4]) ?>')"
                        class="rounded-xl px-4 py-3 flex items-center gap-3 text-left hover:bg-white/10 transition-colors cursor-pointer"
                        style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)">
                        <i class="fas <?= $f[0] ?> <?= $f[2] ?> text-sm flex-shrink-0"></i>
                        <span class="text-slate-300 text-xs leading-snug"><?= $f[1] ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <!-- AI insight result -->
                <div id="ai-insight-box" style="display:none" class="mt-4 rounded-xl p-4" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-robot text-teal-400 text-xs"></i>
                        <span class="text-teal-300 text-xs font-semibold">AI Insight</span>
                        <span id="ai-insight-spinner" class="ml-1"><i class="fas fa-spinner fa-spin text-slate-400 text-xs"></i></span>
                    </div>
                    <p id="ai-insight-text" class="text-slate-300 text-xs leading-relaxed whitespace-pre-wrap"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function human_time_diff(int $ts): string {
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60).'m ago';
    if ($diff < 86400) return floor($diff/3600).'h ago';
    return floor($diff/86400).'d ago';
}
?>
<script>
function askAIInsight(page, message) {
    var box     = document.getElementById('ai-insight-box');
    var text    = document.getElementById('ai-insight-text');
    var spinner = document.getElementById('ai-insight-spinner');
    box.style.display = 'block';
    text.textContent  = '';
    spinner.style.display = 'inline';
    fetch('/api/ai_chat.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: message, page: page})
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        spinner.style.display = 'none';
        text.textContent = d.reply || 'No response.';
    })
    .catch(function(e){
        spinner.style.display = 'none';
        text.textContent = 'Error: ' + e.message;
    });
}
</script>
</body></html>
