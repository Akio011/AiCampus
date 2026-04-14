<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'AI Campus Management' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }

        /* Sidebar */
        .sidebar { width: 256px; min-height: 100vh; background: #fff; position: fixed; left: 0; top: 0; z-index: 100; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; transition: transform .3s cubic-bezier(.4,0,.2,1); }
        .main-content { margin-left: 256px; min-height: 100vh; background: #f9fafb; }

        /* Mobile hamburger */
        .mob-bar { display: none; position: fixed; top: 0; left: 0; right: 0; height: 56px; background: #fff; border-bottom: 1px solid #f1f5f9; z-index: 99; align-items: center; padding: 0 16px; gap: 12px; }
        .mob-bar-title { font-weight: 700; font-size: 15px; color: #0f172a; }
        .mob-toggle { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.4); z-index: 99; backdrop-filter: blur(2px); }

        /* Bottom nav bar (mobile) */
        .bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; height: 60px; background: #fff; border-top: 1px solid #f1f5f9; z-index: 100; align-items: center; justify-content: space-around; padding: 0 4px; box-shadow: 0 -4px 20px rgba(0,0,0,.06); }
        .bottom-nav-item { display: flex; flex-direction: column; align-items: center; gap: 3px; padding: 6px 12px; border-radius: 12px; text-decoration: none; color: #94a3b8; font-size: 9.5px; font-weight: 600; transition: all .2s; flex: 1; }
        .bottom-nav-item i { font-size: 18px; }
        .bottom-nav-item.active { color: #8b1a2e; }
        .bottom-nav-item.active i { transform: scale(1.15); }

        @media (max-width: 768px) {
            .sidebar { display: none !important; }
            .main-content { margin-left: 0; padding-top: 56px; padding-bottom: 68px; }
            .mob-bar { display: flex; }
            .bottom-nav { display: flex; }
            .page-header { padding: 14px 16px; flex-wrap: wrap; gap: 10px; }
            .modal-box { margin: 16px; max-width: calc(100vw - 32px) !important; }
            /* Force single column grids on mobile */
            [style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(2,1fr) !important; }
            [style*="grid-template-columns:repeat(3"] { grid-template-columns: repeat(1,1fr) !important; }
            [style*="grid-cols-4"] { grid-template-columns: repeat(2,1fr) !important; }
            .grid-cols-4 { grid-template-columns: repeat(2,1fr) !important; }
            .grid-cols-3 { grid-template-columns: repeat(1,1fr) !important; }
            .grid-cols-2 { grid-template-columns: repeat(1,1fr) !important; }
            /* Stack page header buttons */
            .page-header > div:last-child { width: 100%; }
            .page-header .flex { flex-wrap: wrap; }
            /* Smaller padding on mobile */
            .p-8 { padding: 16px !important; }
            .p-6 { padding: 12px !important; }
        }

        /* Nav items */
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 10px; font-size: 13.5px; font-weight: 500; color: #6b7280; transition: all .15s; margin: 1px 0; cursor: pointer; text-decoration: none; }
        .nav-item:hover { background: #f3f4f6; color: #111827; }
        .nav-item.active { background: #f0fdf4; color: #15803d; font-weight: 600; }
        .nav-item .nav-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; background: #f3f4f6; color: #9ca3af; }
        .nav-item.active .nav-icon { background: #dcfce7; color: #16a34a; }

        /* Cards */
        .stat-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04); border: 1px solid #f1f5f9; transition: all .2s; }
        .stat-card:hover { box-shadow: 0 4px 24px rgba(0,0,0,.1); transform: translateY(-2px); }

        /* Page header */
        .page-header { background: #fff; border-bottom: 1px solid #f1f5f9; padding: 20px 32px; display: flex; align-items: center; justify-content: space-between; }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead tr { background: #f8fafc; }
        .data-table th { padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid #f1f5f9; }
        .data-table td { padding: 14px 20px; font-size: 13.5px; color: #374151; border-bottom: 1px solid #f8fafc; }
        .data-table tbody tr:hover { background: #fafbfc; }
        .data-table tbody tr:last-child td { border-bottom: none; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; }
        .badge-active    { background: #dbeafe; color: #1d4ed8; }
        .badge-returned  { background: #dcfce7; color: #15803d; }
        .badge-overdue   { background: #fee2e2; color: #dc2626; }
        .badge-pending   { background: #fef9c3; color: #a16207; }
        .badge-in_progress { background: #e0f2fe; color: #0369a1; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-claimed   { background: #f3f4f6; color: #6b7280; }
        .badge-found     { background: #dcfce7; color: #15803d; }
        .badge-lost      { background: #fee2e2; color: #dc2626; }

        /* Search bar */
        .search-bar { position: relative; }
        .search-bar i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
        .search-bar input { padding: 10px 14px 10px 40px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px; width: 100%; outline: none; background: #fff; color: #1e293b; transition: border .2s; }
        .search-bar input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }

        /* Select */
        .filter-select { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px; outline: none; background: #fff; color: #374151; cursor: pointer; transition: border .2s; }
        .filter-select:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }

        /* Primary button */
        .btn-primary { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #8b1a2e; color: #fff; border-radius: 10px; font-size: 13.5px; font-weight: 600; border: none; cursor: pointer; transition: all .2s; box-shadow: 0 1px 3px rgba(0,0,0,.1); position: relative; overflow: hidden; }
        .btn-primary::after { content: ''; position: absolute; inset: 0; background: linear-gradient(120deg, transparent 0%, rgba(255,255,255,.12) 50%, transparent 100%); transform: translateX(-100%); transition: transform .4s ease; }
        .btn-primary:hover::after { transform: translateX(100%); }
        .btn-primary:hover { background: #6e1424; box-shadow: 0 4px 14px rgba(139,26,46,.35); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); display: flex; align-items: center; justify-content: center; z-index: 200; }
        .modal-box { background: #fff; border-radius: 20px; padding: 28px; width: 100%; max-width: 460px; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        .modal-box.lg { max-width: 560px; max-height: 90vh; overflow-y: auto; }

        /* Form inputs */
        .form-input { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px; outline: none; color: #1e293b; transition: border .2s; }
        .form-input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }
        .form-label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }

        /* Gradient text */
        .gradient-text { background: linear-gradient(135deg, #22c55e, #16a34a); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /* AI glow */
        .ai-glow { box-shadow: 0 0 30px rgba(34,197,94,.2), 0 0 60px rgba(22,163,74,.1); }

        /* Progress bar */
        .progress-track { background: #f1f5f9; border-radius: 99px; height: 6px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #22c55e, #16a34a); transition: width .4s ease; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        /* Pulse dot */
        @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }
        .pulse-dot { animation: pulse-dot 2s infinite; }

        /* Modern stat cards */
        .stat-card-modern { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04); border: 1px solid #f1f5f9; transition: all .2s; }
        .stat-card-modern:hover { box-shadow: 0 4px 24px rgba(0,0,0,.1); transform: translateY(-2px); }

        /* Quick action cards */
        .quick-action-card { text-decoration: none; cursor: pointer; }
        .quick-action-card:hover { text-decoration: none; }

        /* ── Entrance animations ── */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .anim-card {
            opacity: 0;
            animation: fadeSlideUp .45s cubic-bezier(.22,.68,0,1.2) forwards;
        }
        /* Scroll-reveal: hidden until JS adds .revealed */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .45s ease, transform .45s cubic-bezier(.22,.68,0,1.2);
        }
        .reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }
        /* Hover lift for interactive cards */
        .card-lift {
            transition: box-shadow .2s, transform .2s;
        }
        .card-lift:hover {
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
            transform: translateY(-4px);
        }
        /* Notification badge */
        .nav-badge {
            position: absolute;
            top: -3px; right: -3px;
            min-width: 16px; height: 16px;
            background: #ef4444;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            border-radius: 99px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 4px;
            border: 2px solid #fff;
            line-height: 1;
        }
        /* Full-screen detail modal */
        .detail-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(15,23,42,.6);
            backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center;
            z-index: 300;
            animation: fadeIn .2s ease;
        }
        .detail-modal-box {
            background: #fff;
            border-radius: 24px;
            width: 100%; max-width: 680px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 80px rgba(0,0,0,.25);
            animation: fadeSlideUp .3s cubic-bezier(.22,.68,0,1.2);
        }
    </style>
<script>
// Scroll-reveal observer — used on all module pages
document.addEventListener('DOMContentLoaded', function () {
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('revealed'); io.unobserve(e.target); }
        });
    }, { threshold: 0.08 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
});
</script>
</head>
<body class="min-h-screen">
