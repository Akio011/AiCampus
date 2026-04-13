<?php
$current  = basename($_SERVER['PHP_SELF']);
$navUser  = $_SESSION['user'] ?? ['name'=>'Guest','email'=>'','avatar'=>null,'role'=>'guest'];
$navAvatar = $navUser['avatar']
    ? htmlspecialchars($navUser['avatar'])
    : 'https://ui-avatars.com/api/?name='.urlencode($navUser['name']).'&background=8b1a2e&color=fff&bold=true';

// Badge count for Lost & Found new active items
$lfBadge = 0;
try {
    global $pdo;
    if (isset($pdo)) {
        $lfBadge = (int)$pdo->query("SELECT COUNT(*) FROM lost_found WHERE status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    }
} catch(Exception $e) {}

$navItems = [
    ['file'=>'index.php',      'icon'=>'fa-th-large',       'label'=>'Dashboard',         'badge'=>0],
    ['file'=>'devices.php',    'icon'=>'fa-laptop',         'label'=>'Device Borrowing',  'badge'=>0],
    ['file'=>'lost-found.php', 'icon'=>'fa-search-location','label'=>'Lost & Found',      'badge'=>$lfBadge],
    ['file'=>'capstone.php',   'icon'=>'fa-layer-group',    'label'=>'Capstone Catalog',  'badge'=>0],
    ['file'=>'community.php',  'icon'=>'fa-hands-helping',  'label'=>'Community Service', 'badge'=>0],
];
?>
<style>
@keyframes navSlideIn {
    from { opacity: 0; transform: translateX(-22px) scale(.97); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes navPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(139,26,46,.4); }
    60%       { box-shadow: 0 0 0 7px rgba(139,26,46,0); }
}
@keyframes iconBounce {
    0%,100% { transform: scale(1) rotate(0deg); }
    30%     { transform: scale(1.22) rotate(-8deg); }
    60%     { transform: scale(1.12) rotate(5deg); }
}
@keyframes ripple {
    from { transform: scale(0); opacity: .35; }
    to   { transform: scale(2.8); opacity: 0; }
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    font-size: 13.5px;
    text-decoration: none;
    margin-bottom: 4px;
    opacity: 0;
    animation: navSlideIn .4s cubic-bezier(.22,.68,0,1.2) forwards;
    /* smooth multi-property transition */
    transition:
        background .25s cubic-bezier(.4,0,.2,1),
        color .2s ease,
        transform .25s cubic-bezier(.34,1.56,.64,1),
        box-shadow .25s ease;
    color: #6b7280;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

/* Ripple element injected by JS */
.nav-link .ripple-el {
    position: absolute;
    border-radius: 50%;
    background: rgba(139,26,46,.15);
    width: 60px; height: 60px;
    margin-top: -30px; margin-left: -30px;
    pointer-events: none;
    animation: ripple .55s ease-out forwards;
}

.nav-link:hover {
    background: linear-gradient(135deg, #fdf2f4 0%, #fce8ec 100%);
    color: #8b1a2e;
    transform: translateX(5px) scale(1.01);
    box-shadow: 0 2px 12px rgba(139,26,46,.1);
}
.nav-link.active {
    background: linear-gradient(135deg, #fdf2f4 0%, #fce8ec 100%);
    color: #8b1a2e;
    font-weight: 700;
    box-shadow: 0 2px 12px rgba(139,26,46,.12);
}

/* Left accent bar on active */
.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0; top: 20%; bottom: 20%;
    width: 3px;
    border-radius: 99px;
    background: #8b1a2e;
    animation: navSlideIn .3s ease forwards;
}

.nav-icon {
    width: 32px; height: 32px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
    position: relative;
    background: #f3f4f6;
    color: #9ca3af;
    transition:
        background .25s ease,
        color .2s ease,
        transform .3s cubic-bezier(.34,1.56,.64,1);
}
.nav-link:hover .nav-icon {
    background: #f5c6ce;
    color: #8b1a2e;
    transform: scale(1.15) rotate(-6deg);
}
.nav-link.active .nav-icon {
    background: #f5c6ce;
    color: #8b1a2e;
    animation: navPulse 2.4s 1s infinite;
}

.nav-dot {
    margin-left: auto;
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #8b1a2e;
    flex-shrink: 0;
    animation: navPulse 2.4s infinite;
}

/* Label slides slightly on hover */
.nav-link span.nav-label {
    transition: letter-spacing .2s ease;
}
.nav-link:hover span.nav-label {
    letter-spacing: .01em;
}
</style>

<aside style="width:256px;min-height:100vh;background:#fff;position:fixed;left:0;top:0;z-index:100;display:flex;flex-direction:column;border-right:1px solid #e5e7eb" id="sidebar" class="sidebar">

    <!-- Logo -->
    <div style="padding:24px 20px 20px;border-bottom:1px solid #f3f4f6">
        <div style="display:flex;align-items:center;gap:12px">
            <img src="/assets/ccse-seal.jpg" alt="CCSE"
                 style="width:40px;height:40px;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid #e5e7eb">
            <div>
                <div style="color:#111827;font-weight:700;font-size:14px;line-height:1.2">AI Campus</div>
                <div style="color:#9ca3af;font-size:11.5px;margin-top:1px">Management System</div>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <nav style="flex:1;padding:16px 12px">
        <div style="font-size:10px;font-weight:700;color:#9ca3af;letter-spacing:.08em;text-transform:uppercase;padding:0 8px;margin-bottom:8px">Menu</div>
        <?php foreach($navItems as $i => $item):
            $isActive = $current === $item['file'];
        ?>
        <a href="/<?= $item['file'] ?>" class="nav-link <?= $isActive ? 'active' : '' ?>" style="animation-delay:<?= $i * 60 ?>ms">
            <span class="nav-icon">
                <i class="fas <?= $item['icon'] ?>"></i>
                <?php if($item['badge'] > 0): ?>
                <span class="nav-badge"><?= $item['badge'] > 9 ? '9+' : $item['badge'] ?></span>
                <?php endif; ?>
            </span>
            <span class="nav-label"><?= $item['label'] ?></span>
            <?php if($isActive): ?>
            <span class="nav-dot"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- User -->
    <div style="padding:16px;border-top:1px solid #f3f4f6">
        <div style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:#f9fafb;margin-bottom:8px">
            <img src="<?= $navAvatar ?>" referrerpolicy="no-referrer" alt="avatar"
                 style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #f5c6ce">
            <div style="flex:1;min-width:0">
                <div style="color:#111827;font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= htmlspecialchars($navUser['name']) ?>
                </div>
                <div style="color:#9ca3af;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px">
                    <?= htmlspecialchars($navUser['email']) ?>
                </div>
            </div>
            <?php
            $roleStyle = match($navUser['role']) {
                'admin'   => 'background:#fdf2f4;color:#8b1a2e',
                'faculty' => 'background:#fdf2f4;color:#8b1a2e',
                default   => 'background:#f3f4f6;color:#6b7280',
            };
            ?>
            <span style="<?= $roleStyle ?>;font-size:10px;font-weight:700;padding:3px 8px;border-radius:6px;flex-shrink:0;text-transform:capitalize">
                <?= ucfirst($navUser['role']) ?>
            </span>
        </div>
        <a href="/auth/logout.php" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;font-size:12.5px;font-weight:500;color:#ef4444;text-decoration:none;transition:background .15s" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-sign-out-alt" style="font-size:12px"></i> Sign Out
        </a>
    </div>
</aside>

<!-- Bottom nav (mobile only) -->
<nav class="bottom-nav">
    <?php foreach($navItems as $item):
        $isActive = $current === $item['file'];
    ?>
    <a href="/<?= $item['file'] ?>" class="bottom-nav-item <?= $isActive ? 'active' : '' ?>">
        <i class="fas <?= $item['icon'] ?>"></i>
        <span><?= explode(' ', $item['label'])[0] ?></span>
    </a>
    <?php endforeach; ?>
    <a href="/auth/logout.php" class="bottom-nav-item">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</nav>

<!-- Mobile top bar -->
<div class="mob-bar">
    <img src="/assets/ccse-seal.jpg" alt="CCSE" style="width:30px;height:30px;border-radius:8px;object-fit:cover">
    <span class="mob-bar-title">AI Campus</span>
    <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
        <img src="<?= $navAvatar ?>" referrerpolicy="no-referrer" alt="avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:2px solid #f5c6ce">
    </div>
</div>

<script>
document.querySelectorAll('.nav-link').forEach(function(link) {
    link.addEventListener('mouseenter', function(e) {
        var r = document.createElement('span');
        r.className = 'ripple-el';
        var rect = link.getBoundingClientRect();
        r.style.top  = (e.clientY - rect.top)  + 'px';
        r.style.left = (e.clientX - rect.left) + 'px';
        link.appendChild(r);
        setTimeout(function(){ r.remove(); }, 560);
    });
});
</script>
