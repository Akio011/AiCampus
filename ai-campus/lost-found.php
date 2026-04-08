<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
$pageTitle = 'Lost & Found — AI Campus Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireStaff();
    if (isset($_POST['post_item'])) {
        $imagePath = null;
        if (!empty($_FILES['item_image']['name'])) {
            $uploadDir = __DIR__ . '/assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','gif','webp']) && $_FILES['item_image']['size'] < 5*1024*1024) {
                $filename = uniqid('item_') . '.' . $ext;
                move_uploaded_file($_FILES['item_image']['tmp_name'], $uploadDir . $filename);
                $imagePath = 'assets/uploads/' . $filename;
            }
        }
        $pdo->prepare("INSERT INTO lost_found(title,description,type,location,posted_by,posted_date,status,image) VALUES(?,?,?,?,?,CURDATE(),'active',?)")
            ->execute([$_POST['title'],$_POST['description'],$_POST['type'],$_POST['location'],$_POST['posted_by'],$imagePath]);
        $pdo->prepare("INSERT INTO activity_log(description,icon,color) VALUES(?,?,?)")
            ->execute([$_POST['posted_by'].' posted '.$_POST['type'].' item: '.$_POST['title'],'search','orange']);
        header('Location: lost-found.php'); exit;
    }
    if (isset($_POST['mark_claimed'])) {
        $pdo->prepare("UPDATE lost_found SET status='claimed' WHERE id=?")->execute([$_POST['id']]);
        header('Location: lost-found.php'); exit;
    }
}

$search     = $_GET['search']   ?? '';
$typeFilter = $_GET['type']     ?? '';
$viewMode   = $_GET['view']     ?? 'board'; // board | list
$showForm   = isset($_GET['action']) && $_GET['action']==='post';

$sql = "SELECT * FROM lost_found WHERE 1=1";
$params = [];
if ($search)     { $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)"; $params=["%$search%","%$search%","%$search%"]; }
if ($typeFilter) { $sql .= " AND type=?"; $params[]=$typeFilter; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$items = $stmt->fetchAll();

$totalFound   = (int)$pdo->query("SELECT COUNT(*) FROM lost_found WHERE type='found' AND status='active'")->fetchColumn();
$totalLost    = (int)$pdo->query("SELECT COUNT(*) FROM lost_found WHERE type='lost'  AND status='active'")->fetchColumn();
$totalClaimed = (int)$pdo->query("SELECT COUNT(*) FROM lost_found WHERE status='claimed'")->fetchColumn();
$totalActive  = $totalFound + $totalLost;
$todayNew     = (int)$pdo->query("SELECT COUNT(*) FROM lost_found WHERE DATE(created_at)=CURDATE()")->fetchColumn();

// Top locations
$topLocations = $pdo->query("SELECT location, COUNT(*) as cnt FROM lost_found GROUP BY location ORDER BY cnt DESC LIMIT 6")->fetchAll();

// Separate by status for kanban
$activeItems   = array_filter($items, fn($i) => $i['status']==='active');
$claimedItems  = array_filter($items, fn($i) => $i['status']==='claimed');
$lostItems     = array_filter($items, fn($i) => $i['type']==='lost'  && $i['status']==='active');
$foundItems    = array_filter($items, fn($i) => $i['type']==='found' && $i['status']==='active');

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>

<style>
/* Pipeline */
.lf-pipeline{display:flex;align-items:stretch;background:#fff;border:1px solid #f1f5f9;border-radius:20px;overflow:hidden;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.lf-stage{flex:1;padding:20px 24px;position:relative;transition:background .2s;cursor:default}
.lf-stage:not(:last-child){border-right:1px solid #f1f5f9}
.lf-stage:not(:last-child)::after{content:'';position:absolute;right:-10px;top:50%;transform:translateY(-50%);width:0;height:0;border-top:8px solid transparent;border-bottom:8px solid transparent;border-left:10px solid #f1f5f9;z-index:2}
.lf-stage:not(:last-child)::before{content:'';position:absolute;right:-8px;top:50%;transform:translateY(-50%);width:0;height:0;border-top:7px solid transparent;border-bottom:7px solid transparent;border-left:9px solid #fff;z-index:3}
.lf-stage:hover{background:#fafafa}
.lf-stage-num{font-size:28px;font-weight:900;color:#1e293b;line-height:1}
.lf-stage-label{font-size:12px;font-weight:700;color:#475569;margin-top:2px}
.lf-stage-sub{font-size:11px;color:#94a3b8;margin-top:1px}
.lf-stage-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:10px}

/* Item card */
.lf-card{background:#fff;border:1px solid #f1f5f9;border-radius:18px;overflow:hidden;cursor:pointer;transition:transform .25s cubic-bezier(.34,1.56,.64,1),box-shadow .25s ease,border-color .2s;position:relative}
.lf-card:hover{transform:translateY(-5px) scale(1.01);box-shadow:0 12px 36px rgba(0,0,0,.1);border-color:#e2e8f0}
.lf-card-img{width:100%;height:160px;object-fit:cover;display:block}
.lf-card-img-placeholder{width:100%;height:160px;display:flex;align-items:center;justify-content:center;font-size:32px}

/* Type badge */
.type-badge{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:99px;letter-spacing:.02em}
.type-lost{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.type-found{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.type-claimed{background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb}

/* Kanban columns */
.kanban-col{background:#f8fafc;border:1px solid #f1f5f9;border-radius:16px;padding:14px}
.kanban-col-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding-bottom:10px;border-bottom:2px solid}

/* List table */
.lf-table{width:100%;border-collapse:collapse}
.lf-table thead tr{background:#f8fafc}
.lf-table th{padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid #f1f5f9}
.lf-table td{padding:13px 16px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.lf-table tbody tr{transition:background .15s;cursor:pointer}
.lf-table tbody tr:hover{background:#fdf8f9}
.lf-table tbody tr:last-child td{border-bottom:none}

/* View toggle */
.view-btn{padding:7px 14px;border-radius:9px;font-size:12px;font-weight:600;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .2s}
.view-btn.active{background:#8b1a2e;color:#fff;border-color:#8b1a2e}

/* Detail modal */
.lf-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:300;animation:fadeIn .2s ease}
.lf-modal-box{background:#fff;border-radius:24px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.25);animation:modalPop .35s cubic-bezier(.34,1.56,.64,1)}
@keyframes modalPop{from{opacity:0;transform:scale(.92) translateY(20px)}to{opacity:1;transform:none}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.anim-in{opacity:0;animation:fadeUp .45s cubic-bezier(.22,.68,0,1.2) forwards}
</style>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Lost &amp; Found Board</h1>
      <p class="text-slate-400 text-sm mt-0.5">AI-assisted item tracking and recovery workflow</p>
    </div>
    <?php if(isStaff()): ?>
    <button onclick="document.getElementById('postModal').classList.remove('hidden')" class="btn-primary">
      <i class="fas fa-plus text-xs"></i> Post Item
    </button>
    <?php endif; ?>
  </div>

  <div class="p-8">

    <!-- ── Workflow Pipeline ── -->
    <div class="lf-pipeline anim-in" style="animation-delay:.05s">
      <?php $stages=[
        ['fa-exclamation-circle','#fef2f2','#dc2626',$totalLost,   'Lost Items',    'Actively searching'],
        ['fa-search',            '#fff7ed','#f97316',$totalActive,  'Under Review',  'AI matching active'],
        ['fa-check-circle',      '#f0fdf4','#16a34a',$totalFound,   'Found Items',   'Awaiting claim'],
        ['fa-hand-holding-heart','#f5f3ff','#7c3aed',$totalClaimed, 'Claimed',       'Successfully returned'],
      ];
      foreach($stages as $s): ?>
      <div class="lf-stage">
        <div class="lf-stage-icon" style="background:<?=$s[1]?>">
          <i class="fas <?=$s[0]?>" style="color:<?=$s[2]?>;font-size:15px"></i>
        </div>
        <div class="lf-stage-num"><?=$s[3]?></div>
        <div class="lf-stage-label"><?=$s[4]?></div>
        <div class="lf-stage-sub"><?=$s[5]?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Stats + Locations ── -->
    <div class="grid grid-cols-4 gap-4 mb-6 anim-in" style="animation-delay:.1s">
      <?php $stats=[
        ['fa-exclamation-circle','#dc2626','#fef2f2',$totalLost,  'Lost Items',   'Active searches'],
        ['fa-check-circle',      '#16a34a','#f0fdf4',$totalFound, 'Found Items',  'Awaiting owners'],
        ['fa-hand-holding',      '#7c3aed','#f5f3ff',$totalClaimed,'Claimed',     'Returned to owner'],
        ['fa-calendar-day',      '#f97316','#fff7ed',$todayNew,   "Today's Posts",'New this day'],
      ]; foreach($stats as $s): ?>
      <div style="background:#fff;border:1px solid #f1f5f9;border-radius:16px;padding:18px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="width:42px;height:42px;border-radius:12px;background:<?=$s[2]?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fas <?=$s[0]?>" style="color:<?=$s[1]?>;font-size:15px"></i>
        </div>
        <div>
          <div style="font-size:22px;font-weight:900;color:#1e293b"><?=$s[3]?></div>
          <div style="font-size:12px;font-weight:600;color:#475569"><?=$s[4]?></div>
          <div style="font-size:11px;color:#94a3b8"><?=$s[5]?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Hot Locations ── -->
    <?php if($topLocations): ?>
    <div class="anim-in" style="animation-delay:.13s;background:#fff;border:1px solid #f1f5f9;border-radius:16px;padding:16px 20px;margin-bottom:22px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
      <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px"><i class="fas fa-map-marker-alt" style="color:#f97316;margin-right:5px"></i>Hot Spots</div>
      <div style="display:flex;flex-wrap:wrap;gap:7px">
        <?php $maxL=$topLocations[0]['cnt']; foreach($topLocations as $loc): ?>
        <div onclick="filterLocation('<?=htmlspecialchars($loc['location'],ENT_QUOTES)?>')"
             style="display:flex;align-items:center;gap:7px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:6px 12px;cursor:pointer;transition:all .2s"
             onmouseover="this.style.borderColor='#f97316';this.style.background='#fff7ed'" onmouseout="this.style.borderColor='#f1f5f9';this.style.background='#f8fafc'">
          <i class="fas fa-map-marker-alt" style="color:#f97316;font-size:11px"></i>
          <span style="font-size:12px;font-weight:600;color:#1e293b"><?=htmlspecialchars($loc['location'])?></span>
          <span style="font-size:11px;font-weight:700;background:#fff7ed;color:#f97316;border-radius:99px;padding:1px 7px"><?=$loc['cnt']?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Filters + View Toggle ── -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px" class="anim-in" style="animation-delay:.16s">
      <div class="search-bar" style="flex:1"><i class="fas fa-search"></i>
        <input type="text" id="searchInput" value="<?=htmlspecialchars($search)?>" placeholder="Search by title, description, or location...">
      </div>
      <select id="typeFilter" class="filter-select">
        <option value="">All Items</option>
        <option value="lost"  <?=$typeFilter=='lost' ?'selected':''?>>Lost</option>
        <option value="found" <?=$typeFilter=='found'?'selected':''?>>Found</option>
      </select>
      <div style="display:flex;gap:4px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:3px">
        <button class="view-btn <?=$viewMode==='board'?'active':''?>" onclick="setView('board')" title="Board"><i class="fas fa-th-large"></i></button>
        <button class="view-btn <?=$viewMode==='kanban'?'active':''?>" onclick="setView('kanban')" title="Kanban"><i class="fas fa-columns"></i></button>
        <button class="view-btn <?=$viewMode==='list'?'active':''?>" onclick="setView('list')" title="List"><i class="fas fa-list"></i></button>
      </div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <p style="font-size:13px;color:#64748b">Showing <span style="font-weight:700;color:#1e293b"><?=count($items)?></span> items</p>
      <span id="locFilterBadge" style="display:none;font-size:12px;font-weight:600;background:#fff7ed;color:#f97316;border:1px solid #fed7aa;border-radius:99px;padding:3px 12px;cursor:pointer" onclick="clearLocFilter()">
        <i class="fas fa-times" style="margin-right:4px"></i><span id="locFilterLabel"></span>
      </span>
    </div>

    <?php
    // Helper to render a single item card
    function renderCard($item, $idx=0) {
        $placeholder = "https://placehold.co/400x200/f1f5f9/94a3b8?text=".urlencode($item['title']);
        $imgSrc = $item['image'] ? htmlspecialchars($item['image']) : $placeholder;
        $hasImg = !empty($item['image']);
        $emojis = ['📱'=>['phone','mobile'],'💻'=>['laptop','computer'],'📚'=>['book','notebook'],'🎒'=>['bag','backpack'],'🔑'=>['key','keys'],'👓'=>['glasses','eyeglasses'],'⌚'=>['watch'],'🖊'=>['pen','pencil']];
        $emoji = '📦';
        foreach($emojis as $e=>$kw) foreach($kw as $k) if(stripos($item['title'],$k)!==false){$emoji=$e;break 2;}
        $iData = json_encode(['id'=>$item['id'],'title'=>$item['title'],'description'=>$item['description'],'type'=>$item['type'],'location'=>$item['location'],'posted_by'=>$item['posted_by'],'posted_date'=>date('F j, Y',strtotime($item['posted_date'])),'status'=>$item['status'],'image'=>$imgSrc],JSON_HEX_QUOT|JSON_HEX_APOS);
        $typeClass = $item['type']==='lost' ? 'type-lost' : 'type-found';
        $accentColor = $item['type']==='lost' ? '#dc2626' : '#16a34a';
        if($item['status']==='claimed') { $typeClass='type-claimed'; $accentColor='#6b7280'; }
        echo '<div class="lf-card reveal" style="transition-delay:'.($idx%6*60).'ms" onclick=\'openLFDetail('.$iData.')\'>';
        echo '<div style="height:3px;background:'.$accentColor.'"></div>';
        if($hasImg) {
            echo '<div style="position:relative"><img src="'.$imgSrc.'" class="lf-card-img" alt="'.htmlspecialchars($item['title']).'"><div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.3),transparent)"></div></div>';
        } else {
            echo '<div class="lf-card-img-placeholder" style="background:'.($item['type']==='lost'?'#fef2f2':'#f0fdf4').'">'.$emoji.'</div>';
        }
        echo '<div style="padding:16px">';
        echo '<div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:8px">';
        echo '<h3 style="font-weight:800;font-size:13px;color:#1e293b;line-height:1.3;flex:1;padding-right:8px">'.htmlspecialchars($item['title']).'</h3>';
        echo '<span class="type-badge '.$typeClass.'">'.ucfirst($item['status']==='claimed'?'claimed':$item['type']).'</span>';
        echo '</div>';
        echo '<p style="font-size:11.5px;color:#64748b;line-height:1.6;margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">'.htmlspecialchars($item['description']).'</p>';
        echo '<div style="display:flex;flex-direction:column;gap:5px;margin-bottom:12px">';
        echo '<div style="display:flex;align-items:center;gap:6px;font-size:11px;color:#64748b"><i class="fas fa-map-marker-alt" style="color:#f97316;width:12px"></i>'.htmlspecialchars($item['location']).'</div>';
        echo '<div style="display:flex;align-items:center;gap:6px;font-size:11px;color:#64748b"><i class="fas fa-calendar" style="color:#3b82f6;width:12px"></i>'.date('M j, Y',strtotime($item['posted_date'])).'</div>';
        echo '<div style="display:flex;align-items:center;gap:6px;font-size:11px;color:#64748b"><i class="fas fa-user" style="color:#7c3aed;width:12px"></i>'.htmlspecialchars($item['posted_by']).'</div>';
        echo '</div>';
        if($item['status']!=='claimed' && isStaff()) {
            echo '<form method="POST" onclick="event.stopPropagation()"><input type="hidden" name="id" value="'.$item['id'].'"><button name="mark_claimed" class="btn-primary w-full justify-center" style="font-size:11.5px;padding:8px">Mark as Claimed</button></form>';
        } elseif($item['status']==='claimed') {
            echo '<div style="text-align:center;font-size:11.5px;font-weight:600;color:#94a3b8;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:8px"><i class="fas fa-check-circle" style="color:#22c55e;margin-right:4px"></i>Claimed</div>';
        } else {
            echo '<div style="text-align:center;font-size:11.5px;font-weight:600;color:#94a3b8;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:8px">View Only</div>';
        }
        echo '</div></div>';
    }
    ?>

    <!-- ── BOARD VIEW ── -->
    <div id="boardView" style="<?=$viewMode!=='board'?'display:none':''?>">
      <div class="grid grid-cols-3 gap-5" id="itemGrid">
        <?php foreach($items as $idx=>$item) renderCard($item,$idx); ?>
      </div>
    </div>

    <!-- ── KANBAN VIEW ── -->
    <div id="kanbanView" style="<?=$viewMode!=='kanban'?'display:none':''?>">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
        <!-- Lost -->
        <div class="kanban-col">
          <div class="kanban-col-header" style="border-color:#fecaca">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:28px;height:28px;border-radius:8px;background:#fef2f2;display:flex;align-items:center;justify-content:center"><i class="fas fa-exclamation-circle" style="color:#dc2626;font-size:12px"></i></div>
              <span style="font-weight:700;font-size:13px;color:#1e293b">Lost</span>
            </div>
            <span style="font-size:12px;font-weight:700;background:#fef2f2;color:#dc2626;border-radius:99px;padding:2px 10px"><?=count($lostItems)?></span>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php foreach($lostItems as $idx=>$item) renderCard($item,$idx); ?>
            <?php if(!$lostItems): ?><div style="text-align:center;padding:24px;color:#94a3b8;font-size:12px"><i class="fas fa-check-circle" style="color:#22c55e;display:block;font-size:20px;margin-bottom:6px"></i>No lost items</div><?php endif; ?>
          </div>
        </div>
        <!-- Found -->
        <div class="kanban-col">
          <div class="kanban-col-header" style="border-color:#bbf7d0">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:28px;height:28px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center"><i class="fas fa-check-circle" style="color:#16a34a;font-size:12px"></i></div>
              <span style="font-weight:700;font-size:13px;color:#1e293b">Found</span>
            </div>
            <span style="font-size:12px;font-weight:700;background:#f0fdf4;color:#16a34a;border-radius:99px;padding:2px 10px"><?=count($foundItems)?></span>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php foreach($foundItems as $idx=>$item) renderCard($item,$idx); ?>
            <?php if(!$foundItems): ?><div style="text-align:center;padding:24px;color:#94a3b8;font-size:12px"><i class="fas fa-search" style="display:block;font-size:20px;margin-bottom:6px;opacity:.3"></i>No found items</div><?php endif; ?>
          </div>
        </div>
        <!-- Claimed -->
        <div class="kanban-col">
          <div class="kanban-col-header" style="border-color:#ddd6fe">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:28px;height:28px;border-radius:8px;background:#f5f3ff;display:flex;align-items:center;justify-content:center"><i class="fas fa-hand-holding-heart" style="color:#7c3aed;font-size:12px"></i></div>
              <span style="font-weight:700;font-size:13px;color:#1e293b">Claimed</span>
            </div>
            <span style="font-size:12px;font-weight:700;background:#f5f3ff;color:#7c3aed;border-radius:99px;padding:2px 10px"><?=count($claimedItems)?></span>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php foreach($claimedItems as $idx=>$item) renderCard($item,$idx); ?>
            <?php if(!$claimedItems): ?><div style="text-align:center;padding:24px;color:#94a3b8;font-size:12px"><i class="fas fa-box-open" style="display:block;font-size:20px;margin-bottom:6px;opacity:.3"></i>None claimed yet</div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ── LIST VIEW ── -->
    <div id="listView" style="<?=$viewMode!=='list'?'display:none':''?>">
      <div style="background:#fff;border:1px solid #f1f5f9;border-radius:20px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <table class="lf-table">
          <thead><tr>
            <th>Item</th><th>Type</th><th>Location</th><th>Posted By</th><th>Date</th><th>Status</th><?php if(isStaff()): ?><th>Action</th><?php endif; ?>
          </tr></thead>
          <tbody>
          <?php foreach($items as $item):
            $placeholder="https://placehold.co/60x60/f1f5f9/94a3b8?text=".urlencode($item['title'][0]);
            $imgSrc=$item['image']?htmlspecialchars($item['image']):$placeholder;
            $iData=json_encode(['id'=>$item['id'],'title'=>$item['title'],'description'=>$item['description'],'type'=>$item['type'],'location'=>$item['location'],'posted_by'=>$item['posted_by'],'posted_date'=>date('F j, Y',strtotime($item['posted_date'])),'status'=>$item['status'],'image'=>$imgSrc],JSON_HEX_QUOT|JSON_HEX_APOS);
          ?>
          <tr onclick='openLFDetail(<?=$iData?>)'>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <img src="<?=$imgSrc?>" style="width:40px;height:40px;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid #f1f5f9">
                <div>
                  <div style="font-weight:700;font-size:13px;color:#1e293b"><?=htmlspecialchars($item['title'])?></div>
                  <div style="font-size:11px;color:#94a3b8;margin-top:1px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($item['description'])?></div>
                </div>
              </div>
            </td>
            <td><span class="type-badge <?=$item['type']==='lost'?'type-lost':'type-found'?>"><?=ucfirst($item['type'])?></span></td>
            <td style="font-size:12px;color:#475569"><i class="fas fa-map-marker-alt" style="color:#f97316;margin-right:4px"></i><?=htmlspecialchars($item['location'])?></td>
            <td style="font-size:12px;color:#475569"><?=htmlspecialchars($item['posted_by'])?></td>
            <td style="font-size:12px;color:#64748b"><?=date('M j, Y',strtotime($item['posted_date']))?></td>
            <td>
              <?php $sc=$item['status']==='claimed'; ?>
              <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;<?=$sc?'background:#f3f4f6;color:#6b7280':'background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0'?>">
                <?=$sc?'Claimed':'Active'?>
              </span>
            </td>
            <?php if(isStaff()): ?>
            <td>
              <?php if($item['status']!=='claimed'): ?>
              <form method="POST" onclick="event.stopPropagation()">
                <input type="hidden" name="id" value="<?=$item['id']?>">
                <button name="mark_claimed" class="pro-action-btn" style="font-size:11px;padding:4px 10px"><i class="fas fa-check" style="font-size:10px"></i> Claim</button>
              </form>
              <?php else: ?>
              <span style="font-size:11px;color:#94a3b8"><i class="fas fa-check-circle" style="color:#22c55e"></i> Done</span>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ── Detail Modal ── -->
<div id="lfDetailModal" class="lf-modal-overlay" style="display:none">
  <div class="lf-modal-box">
    <div id="lfd-accent" style="height:4px;border-radius:24px 24px 0 0"></div>
    <div style="position:relative">
      <img id="lfd-img" src="" alt="" style="width:100%;height:260px;object-fit:cover;display:block">
      <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.55),transparent)"></div>
      <button onclick="closeLFDetail()" style="position:absolute;top:14px;right:14px;width:36px;height:36px;background:rgba(255,255,255,.2);backdrop-filter:blur(6px);border:none;border-radius:10px;cursor:pointer;color:#fff;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .2s" onmouseover="this.style.background='rgba(255,255,255,.35)'" onmouseout="this.style.background='rgba(255,255,255,.2)'">
        <i class="fas fa-times"></i>
      </button>
      <div style="position:absolute;bottom:14px;left:16px;display:flex;align-items:center;gap:8px">
        <span id="lfd-type-badge" class="type-badge"></span>
        <span id="lfd-status-badge" style="font-size:10.5px;font-weight:700;padding:4px 10px;border-radius:99px"></span>
      </div>
    </div>
    <div style="padding:24px 28px">
      <h2 id="lfd-title" style="font-weight:900;font-size:20px;color:#0f172a;margin-bottom:8px;line-height:1.3"></h2>
      <p id="lfd-desc" style="font-size:13px;color:#64748b;line-height:1.75;margin-bottom:20px"></p>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        <div style="background:#f8fafc;border-radius:14px;padding:14px">
          <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Location</div>
          <div id="lfd-location" style="font-size:13px;font-weight:600;color:#1e293b;display:flex;align-items:center;gap:6px"><i class="fas fa-map-marker-alt" style="color:#f97316"></i></div>
        </div>
        <div style="background:#f8fafc;border-radius:14px;padding:14px">
          <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Date Posted</div>
          <div id="lfd-date" style="font-size:13px;font-weight:600;color:#1e293b;display:flex;align-items:center;gap:6px"><i class="fas fa-calendar" style="color:#3b82f6"></i></div>
        </div>
      </div>

      <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:14px;padding:14px;margin-bottom:20px">
        <div style="font-size:10px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Contact / Posted By</div>
        <div id="lfd-contact" style="font-size:14px;font-weight:700;color:#4c1d95;display:flex;align-items:center;gap:8px"><i class="fas fa-user" style="color:#7c3aed"></i></div>
      </div>

      <div id="lfd-claim-form"></div>

      <button onclick="closeLFDetail()" style="width:100%;border:1.5px solid #e2e8f0;background:#fff;color:#475569;border-radius:12px;padding:11px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">Close</button>
    </div>
  </div>
</div>

<!-- ── Post Modal ── -->
<div id="postModal" class="<?=$showForm?'':'hidden'?> modal-overlay">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-6">
      <div><h3 class="font-bold text-slate-800 text-base">Post Item</h3>
        <p class="text-slate-400 text-xs mt-0.5">Add a lost or found item to the board</p></div>
      <button onclick="document.getElementById('postModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors"><i class="fas fa-times text-xs"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label class="form-label">Type</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <label style="display:flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .2s" id="lostLabel">
            <input type="radio" name="type" value="lost" onchange="highlightType(this)" style="accent-color:#dc2626">
            <span style="font-size:13px;font-weight:600;color:#dc2626"><i class="fas fa-exclamation-circle" style="margin-right:4px"></i>Lost</span>
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .2s" id="foundLabel">
            <input type="radio" name="type" value="found" checked onchange="highlightType(this)" style="accent-color:#16a34a">
            <span style="font-size:13px;font-weight:600;color:#16a34a"><i class="fas fa-check-circle" style="margin-right:4px"></i>Found</span>
          </label>
        </div>
      </div>
      <div><label class="form-label">Item Title</label>
        <input type="text" name="title" required class="form-input" placeholder="e.g. Blue Notebook"></div>
      <div><label class="form-label">Description</label>
        <textarea name="description" rows="2" class="form-input" placeholder="Describe the item in detail..."></textarea></div>
      <div><label class="form-label">Location</label>
        <input type="text" name="location" required class="form-input" placeholder="e.g. Room 301, Library 2F"></div>
      <div><label class="form-label">Posted By</label>
        <input type="text" name="posted_by" required class="form-input" placeholder="Your name or contact"></div>
      <div>
        <label class="form-label">Photo <span class="text-slate-400 font-normal">(optional)</span></label>
        <div id="img-drop" onclick="document.getElementById('item_image').click()"
             style="border:2px dashed #e2e8f0;border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:border .2s;background:#fafafa"
             onmouseover="this.style.borderColor='#8b1a2e'" onmouseout="this.style.borderColor='#e2e8f0'">
          <i class="fas fa-image" style="color:#cbd5e1;font-size:24px;display:block;margin-bottom:6px"></i>
          <div style="font-size:12px;color:#94a3b8">Click to upload or drag & drop</div>
          <div style="font-size:11px;color:#cbd5e1;margin-top:2px">JPG, PNG, GIF up to 5MB</div>
          <img id="img-preview" src="" alt="" style="display:none;max-height:120px;margin:10px auto 0;border-radius:8px;object-fit:cover">
        </div>
        <input type="file" id="item_image" name="item_image" accept="image/*" style="display:none" onchange="previewImg(this)">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('postModal').classList.add('hidden')" class="flex-1 border border-slate-200 text-slate-600 rounded-xl py-2.5 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
        <button type="submit" name="post_item" class="flex-1 btn-primary justify-center">Post Item</button>
      </div>
    </form>
  </div>
</div>

<script>
// Filters
document.getElementById('searchInput').addEventListener('keyup',function(){var u=new URL(window.location);u.searchParams.set('search',this.value);window.location=u;});
document.getElementById('typeFilter').addEventListener('change',function(){var u=new URL(window.location);u.searchParams.set('type',this.value);window.location=u;});
function setView(v){var u=new URL(window.location);u.searchParams.set('view',v);window.location=u;}

// Location filter
function filterLocation(loc){
    document.getElementById('locFilterBadge').style.display='inline-flex';
    document.getElementById('locFilterLabel').textContent=loc;
    document.querySelectorAll('.lf-card').forEach(function(c){
        c.style.display=c.innerHTML.toLowerCase().includes(loc.toLowerCase())?'':'none';
    });
}
function clearLocFilter(){
    document.getElementById('locFilterBadge').style.display='none';
    document.querySelectorAll('.lf-card').forEach(function(c){c.style.display='';});
}

// Image preview
function previewImg(input){
    if(!input.files||!input.files[0]) return;
    var r=new FileReader();
    r.onload=function(e){var p=document.getElementById('img-preview');p.src=e.target.result;p.style.display='block';};
    r.readAsDataURL(input.files[0]);
}

// Type radio highlight
function highlightType(radio){
    document.getElementById('lostLabel').style.borderColor=radio.value==='lost'?'#dc2626':'#e2e8f0';
    document.getElementById('foundLabel').style.borderColor=radio.value==='found'?'#16a34a':'#e2e8f0';
}
// Init
document.querySelector('input[name="type"][value="found"]') && highlightType({value:'found'});

// Detail modal
function openLFDetail(item){
    var isLost=item.type==='lost', isClaimed=item.status==='claimed';
    var accent=isClaimed?'#6b7280':(isLost?'#dc2626':'#16a34a');
    document.getElementById('lfd-accent').style.background=accent;
    document.getElementById('lfd-img').src=item.image;
    document.getElementById('lfd-title').textContent=item.title;
    document.getElementById('lfd-desc').textContent=item.description||'No description provided.';
    document.getElementById('lfd-location').innerHTML='<i class="fas fa-map-marker-alt" style="color:#f97316"></i>'+esc(item.location);
    document.getElementById('lfd-date').innerHTML='<i class="fas fa-calendar" style="color:#3b82f6"></i>'+esc(item.posted_date);
    document.getElementById('lfd-contact').innerHTML='<i class="fas fa-user" style="color:#7c3aed"></i>'+esc(item.posted_by);

    var tb=document.getElementById('lfd-type-badge');
    tb.textContent=item.type.charAt(0).toUpperCase()+item.type.slice(1);
    tb.className='type-badge '+(isLost?'type-lost':'type-found');

    var sb=document.getElementById('lfd-status-badge');
    sb.textContent=isClaimed?'Claimed':'Active';
    sb.style.cssText='font-size:10.5px;font-weight:700;padding:4px 10px;border-radius:99px;'+(isClaimed?'background:rgba(0,0,0,.5);color:#fff':'background:rgba(16,185,129,.2);color:#fff;border:1px solid rgba(255,255,255,.3)');

    document.getElementById('lfDetailModal').style.display='flex';
    document.body.style.overflow='hidden';
}
function closeLFDetail(){
    document.getElementById('lfDetailModal').style.display='none';
    document.body.style.overflow='';
}
document.getElementById('lfDetailModal').addEventListener('click',function(e){if(e.target===this)closeLFDetail();});
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
</script>

<?php
$aiContext=['title'=>'Lost & Found Assistant','page'=>'lostfound','intro'=>"Hi! I am your Lost & Found Assistant.\nI can help you find lost items, check unclaimed found items, and give tips on posting.",'suggestions'=>['Show me lost items','Show me found items','How many items are unclaimed?','Tips for posting an item']];
require_once 'includes/ai_widget.php';
?>
</body></html>
