<?php
session_start();
if (!empty($_SESSION['user'])) { header('Location: ../index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — AI Campus Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
        body { margin: 0; height: 100vh; display: flex; overflow: hidden; background: #0d1117; }

        /* ── LEFT PANEL ── */
        .left-panel {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        .left-bg {
            position: absolute; inset: 0;
            background: url('../assets/lorma-logo.png') center center / cover no-repeat;
            transform: scale(1.08);
            animation: bgZoom 18s ease-in-out infinite alternate;
        }
        @keyframes bgZoom {
            from { transform: scale(1.08); }
            to   { transform: scale(1.18); }
        }
        .left-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(10,10,20,.72) 0%, rgba(0,0,0,.3) 60%, rgba(139,26,46,.18) 100%);
        }
        /* Animated gradient shimmer */
        .left-shimmer {
            position: absolute; inset: 0;
            background: linear-gradient(120deg, transparent 30%, rgba(139,26,46,.08) 50%, transparent 70%);
            background-size: 200% 200%;
            animation: shimmer 6s ease-in-out infinite;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Floating particles */
        .particles { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            animation: floatUp linear infinite;
        }
        @keyframes floatUp {
            0%   { transform: translateY(100vh) scale(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: .4; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        .left-content {
            position: relative; z-index: 3;
            height: 100%;
            display: flex; flex-direction: column; justify-content: flex-end;
            padding: 52px;
        }

        /* Seal */
        .seal-wrap {
            opacity: 0;
            animation: fadeUp .7s .2s cubic-bezier(.22,.68,0,1.2) forwards;
        }
        .seal-img {
            width: 76px; height: 76px; border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(255,255,255,.3);
            box-shadow: 0 0 0 0 rgba(255,255,255,.3);
            animation: sealPulse 3s 1.5s ease-in-out infinite;
        }
        @keyframes sealPulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,.25); }
            50%      { box-shadow: 0 0 0 10px rgba(255,255,255,0); }
        }

        .left-label {
            color: rgba(255,255,255,.6);
            font-size: 11px; font-weight: 600; letter-spacing: .22em; text-transform: uppercase;
            margin: 20px 0 12px;
            opacity: 0;
            animation: fadeUp .8s .4s cubic-bezier(.22,.68,0,1.2) forwards;
            text-shadow: 0 0 18px rgba(255,180,190,.25);
        }

        /* Word-by-word title reveal */
        .left-title {
            font-size: 38px; font-weight: 900; line-height: 1.18;
            margin-bottom: 16px;
            overflow: visible;
        }
        .word {
            display: inline-block;
            color: #fff;
            opacity: 0;
            transform: translateY(36px);
            will-change: transform, opacity;
            animation: wordReveal .8s cubic-bezier(.22,.68,0,1.2) forwards;
        }
        @keyframes wordReveal {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Glowing floating gradient words — GPU only */
        .word-grad {
            display: inline-block;
            position: relative;
            opacity: 0;
            transform: translateY(36px);
            background: linear-gradient(90deg, #ffe4ea 0%, #ff6b8a 30%, #fff 55%, #ff8fa3 80%, #ffe4ea 100%);
            background-size: 250% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            will-change: transform, opacity;
            animation:
                wordReveal .8s cubic-bezier(.22,.68,0,1.2) forwards,
                gradShift 7s 1.5s ease-in-out infinite,
                floatY 6s 1.5s ease-in-out infinite;
        }
        /* Glow via opacity-only pseudo — no repaint */
        .word-grad::after {
            content: attr(data-text);
            position: absolute;
            inset: 0;
            background: inherit;
            background-size: inherit;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: blur(10px);
            opacity: 0;
            will-change: opacity;
            animation: glowPulse 6s 1.5s ease-in-out infinite;
            pointer-events: none;
            white-space: nowrap;
        }
        @keyframes gradShift {
            0%,100% { background-position: 0% 0; }
            50%      { background-position: 100% 0; }
        }
        @keyframes floatY {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }
        @keyframes glowPulse {
            0%,100% { opacity: 0; }
            50%      { opacity: .65; }
        }

        /* Typewriter subtitle */
        .left-sub {
            color: rgba(255,255,255,.7);
            font-size: 14px; line-height: 1.85; max-width: 380px;
            opacity: 0;
            will-change: transform, opacity;
            animation: fadeUp .8s 1.1s cubic-bezier(.22,.68,0,1.2) forwards,
                       floatY 7s 2s ease-in-out infinite;
            min-height: 3.6em;
        }
        @keyframes subtleFloat {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-4px); }
        }
        .tw-cursor {
            display: inline-block;
            width: 2px; height: .9em;
            background: rgba(255,180,190,.9);
            margin-left: 1px;
            vertical-align: middle;
            animation: blink .9s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

        .tags-wrap {
            display: flex; flex-wrap: wrap; gap: 8px; margin-top: 28px;
            opacity: 0;
            animation: fadeUp .7s 1.3s cubic-bezier(.22,.68,0,1.2) forwards;
        }
        .tag {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.7);
            font-size: 12px; padding: 6px 16px; border-radius: 99px;
            backdrop-filter: blur(8px);
            transition: all .35s cubic-bezier(.34,1.56,.64,1);
            cursor: default;
            opacity: 0;
            animation: tagPop .6s cubic-bezier(.34,1.56,.64,1) forwards;
            text-shadow: 0 0 10px rgba(255,200,210,.15);
        }
        .tag:hover {
            background: rgba(139,26,46,.35);
            border-color: rgba(220,80,100,.45);
            transform: translateY(-4px) scale(1.06);
            color: #fff;
            text-shadow: 0 0 14px rgba(255,160,175,.6);
            box-shadow: 0 6px 20px rgba(139,26,46,.25);
        }
        @keyframes tagPop {
            from { opacity: 0; transform: scale(.75) translateY(12px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── RIGHT PANEL ── */
        .right-panel {
            width: 460px; flex-shrink: 0;
            background: #fff;
            display: flex; flex-direction: column; justify-content: center;
            padding: 56px 48px;
            overflow-y: auto;
            position: relative;
            animation: slideInRight .65s .1s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        /* Maroon accent line at top */
        .right-panel::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8b1a2e, #c0392b, #8b1a2e);
            background-size: 200% 100%;
            animation: accentSlide 3s linear infinite;
        }
        @keyframes accentSlide {
            0%   { background-position: 0% 0; }
            100% { background-position: 200% 0; }
        }

        /* Form elements stagger */
        .form-el {
            opacity: 0;
            animation: fadeUp .5s cubic-bezier(.22,.68,0,1.2) forwards;
        }

        .logo-wrap   { animation-delay: .3s; }
        .heading-wrap{ animation-delay: .45s; }
        .error-wrap  { animation-delay: .5s; }
        .btn-wrap    { animation-delay: .6s; }
        .divider-wrap{ animation-delay: .72s; }
        .features-wrap{ animation-delay: .82s; }
        .footer-wrap { animation-delay: .95s; }

        /* Welcome heading glow float */
        .welcome-title {
            font-size: 28px; font-weight: 900; color: #0f172a;
            margin: 0 0 8px;
            position: relative;
            display: inline-block;
            will-change: transform;
            animation: rFloatY 6s 1.2s ease-in-out infinite;
        }
        .welcome-title .glow-word {
            position: relative;
            display: inline-block;
            background: linear-gradient(90deg, #0f172a 0%, #8b1a2e 40%, #0f172a 70%, #8b1a2e 100%);
            background-size: 250% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            will-change: opacity;
            animation: rGradShift 6s 1.2s ease-in-out infinite;
        }
        .welcome-title .glow-word::after {
            content: attr(data-text);
            position: absolute;
            inset: 0;
            background: inherit;
            background-size: inherit;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: blur(8px);
            opacity: 0;
            will-change: opacity;
            animation: rGlowPulse 6s 1.2s ease-in-out infinite;
            pointer-events: none;
            white-space: nowrap;
        }
        @keyframes rFloatY {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-5px); }
        }
        @keyframes rGradShift {
            0%,100% { background-position: 0% 0; }
            50%      { background-position: 100% 0; }
        }
        @keyframes rGlowPulse {
            0%,100% { opacity: 0; }
            50%      { opacity: .5; }
        }

        /* Subtitle soft float */
        .welcome-sub {
            font-size: 14px; color: #64748b; margin: 0;
            will-change: transform;
            animation: rFloatY 7s 1.6s ease-in-out infinite;
        }

        /* Google button */
        .google-btn {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            width: 100%; padding: 15px 20px;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            background: #fff; color: #1e293b;
            font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            position: relative; overflow: hidden;
            transition: border-color .2s, box-shadow .2s, transform .2s;
        }
        .google-btn::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(120deg, transparent 0%, rgba(139,26,46,.06) 50%, transparent 100%);
            transform: translateX(-100%);
            transition: transform .45s ease;
        }
        .google-btn:hover::before { transform: translateX(100%); }
        .google-btn:hover {
            border-color: #8b1a2e;
            box-shadow: 0 4px 20px rgba(139,26,46,.18);
            transform: translateY(-2px);
        }
        .google-btn:active { transform: translateY(0); }

        .divider { display: flex; align-items: center; gap: 12px; margin: 24px 0; }
        .divider span { font-size: 12px; color: #94a3b8; white-space: nowrap; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

        .feature-item {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px; border-radius: 10px;
            background: #f8fafc; border: 1px solid #f1f5f9;
            font-size: 13px; color: #475569;
            transition: background .2s, border-color .2s, transform .2s;
        }
        .feature-item:hover {
            background: #fdf2f4;
            border-color: #f5c6ce;
            transform: translateX(4px);
        }

        /* Typewriter cursor */
        .cursor {
            display: inline-block;
            width: 2px; height: 1em;
            background: #8b1a2e;
            margin-left: 2px;
            vertical-align: middle;
            animation: blink .75s step-end infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
    </style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
    <div class="left-bg"></div>
    <div class="left-overlay"></div>
    <div class="left-shimmer"></div>

    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <div class="left-content">
        <div class="seal-wrap">
            <img src="../assets/ccse-seal.jpg" alt="CCSE" class="seal-img">
        </div>
        <div class="left-label">Lorma Colleges — CCSE</div>
        <div class="left-title">
            <span class="word" style="animation-delay:.55s">AI&nbsp;</span><span class="word-grad word" data-text="Campus" style="animation-delay:.7s">Campus</span><br>
            <span class="word" style="animation-delay:.85s;color:#fff">Management&nbsp;</span><span class="word-grad word" data-text="System" style="animation-delay:1s">System</span>
        </div>
        <div class="left-sub" id="heroSub"><span class="tw-cursor"></span></div>
        <div class="tags-wrap">
            <?php
            $tags = ['Device Borrowing','Lost & Found','Capstone Catalog','Community Service','AI Insights'];
            foreach($tags as $i => $t): ?>
            <span class="tag" style="animation-delay:<?= 1.4 + $i * 0.1 ?>s"><?= $t ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">

    <!-- Logo -->
    <div class="form-el logo-wrap" style="display:flex;align-items:center;gap:12px;margin-bottom:40px">
        <img src="../assets/ccse-seal.jpg" alt="CCSE"
             style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #f5c6ce">
        <div>
            <div style="font-size:15px;font-weight:700;color:#0f172a">AI Campus</div>
            <div style="font-size:12px;color:#94a3b8">Management System</div>
        </div>
    </div>

    <!-- Heading -->
    <div class="form-el heading-wrap" style="margin-bottom:32px">
        <h1 class="welcome-title">
            <span class="glow-word" data-text="Welcome back">Welcome back</span><span class="cursor"></span>
        </h1>
        <p class="welcome-sub">Sign in with your Lorma Google account to continue.</p>
    </div>

    <!-- Error -->
    <?php if (!empty($_GET['error'])): ?>
    <div class="form-el error-wrap" style="display:flex;align-items:center;gap:10px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;font-size:13px;border-radius:10px;padding:12px 16px;margin-bottom:20px">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <!-- Google Button -->
    <div class="form-el btn-wrap">
        <a href="google.php" class="google-btn">
            <svg width="20" height="20" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                <path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
                <path d="M3.964 10.707A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.707V4.961H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.039l3.007-2.332z" fill="#FBBC05"/>
                <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.961L3.964 7.293C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
            </svg>
            Continue with Google
        </a>
    </div>

    <div class="form-el divider-wrap">
        <div class="divider"><span>Lorma College accounts only</span></div>
    </div>

    <!-- Features -->
    <div class="form-el features-wrap" style="display:flex;flex-direction:column;gap:8px;margin-bottom:32px">
        <?php $features = [
            ['fa-shield-alt','#22c55e','Secure OAuth 2.0 authentication'],
            ['fa-robot','#3b82f6','AI-powered campus insights'],
            ['fa-lock','#8b1a2e','Role-based access control'],
        ]; foreach($features as $f): ?>
        <div class="feature-item">
            <i class="fas <?= $f[0] ?>" style="color:<?= $f[1] ?>;width:16px;text-align:center"></i>
            <?= $f[2] ?>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="form-el footer-wrap" style="font-size:12px;color:#cbd5e1;text-align:center;margin:0">
        © <?= date('Y') ?> Lorma Colleges — CCSE &nbsp;·&nbsp; For authorized personnel only
    </p>
</div>

<script>
// Generate floating particles
(function() {
    var container = document.getElementById('particles');
    for (var i = 0; i < 22; i++) {
        var p = document.createElement('div');
        p.className = 'particle';
        var size = Math.random() * 5 + 2;
        p.style.cssText = [
            'width:' + size + 'px',
            'height:' + size + 'px',
            'left:' + (Math.random() * 100) + '%',
            'animation-duration:' + (Math.random() * 12 + 8) + 's',
            'animation-delay:' + (Math.random() * 10) + 's',
            'opacity:' + (Math.random() * 0.4 + 0.1),
        ].join(';');
        container.appendChild(p);
    }
})();

// Typewriter effect for subtitle
(function() {
    var text = 'A smart platform for managing devices, lost & found, capstone projects, and community service — all in one place.';
    var el = document.getElementById('heroSub');
    var i = 0;
    var cursor = '<span class="tw-cursor"></span>';
    function type() {
        if (i <= text.length) {
            el.innerHTML = text.slice(0, i) + cursor;
            i++;
            setTimeout(type, i === 1 ? 1100 : 28 + Math.random() * 18);
        }
    }
    setTimeout(type, 1100);
})();
</script>
</body>
</html>
