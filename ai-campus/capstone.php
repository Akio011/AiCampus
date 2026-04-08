<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
$pageTitle = 'Capstone Catalog — AI Campus Management';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_project'])) {
    requireStaff();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO capstone_projects(title,description,year,github_url,advisor) VALUES(?,?,?,?,?)");
    $stmt->execute([$_POST['title'],$_POST['description'],$_POST['year'],$_POST['github_url'],trim($_POST['advisor']??'')]);
    $pid = $pdo->lastInsertId();
    foreach(array_filter(explode(',',$_POST['authors'])) as $a)
        $pdo->prepare("INSERT INTO capstone_authors(project_id,author_name) VALUES(?,?)")->execute([$pid,trim($a)]);
    foreach(array_filter(explode(',',$_POST['technologies'])) as $t)
        $pdo->prepare("INSERT INTO capstone_technologies(project_id,technology) VALUES(?,?)")->execute([$pid,trim($t)]);
    $pdo->prepare("INSERT INTO activity_log(description,icon,color) VALUES(?,?,?)")
        ->execute(['Admin added capstone project: '.$_POST['title'],'book','purple']);
    $pdo->commit();
    header('Location: capstone.php'); exit;
}

$search     = $_GET['search'] ?? '';
$yearFilter = $_GET['year']   ?? '';
$viewMode   = $_GET['view']   ?? 'grid'; // grid | timeline

$sql = "SELECT p.*, GROUP_CONCAT(DISTINCT a.author_name ORDER BY a.id SEPARATOR '|') as authors,
        GROUP_CONCAT(DISTINCT t.technology ORDER BY t.id SEPARATOR '|') as technologies
        FROM capstone_projects p
        LEFT JOIN capstone_authors a ON a.project_id=p.id
        LEFT JOIN capstone_technologies t ON t.project_id=p.id WHERE 1=1";
$params = [];
if ($search)     { $sql .= " AND (p.title LIKE ? OR p.description LIKE ? OR t.technology LIKE ?)"; $params=["%$search%","%$search%","%$search%"]; }
if ($yearFilter) { $sql .= " AND p.year=?"; $params[]=$yearFilter; }
$sql .= " GROUP BY p.id ORDER BY p.year DESC,p.id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$projects = $stmt->fetchAll();

$years      = $pdo->query("SELECT DISTINCT year FROM capstone_projects ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
$totalProj  = $pdo->query("SELECT COUNT(*) FROM capstone_projects")->fetchColumn();
$totalAuth  = $pdo->query("SELECT COUNT(*) FROM capstone_authors")->fetchColumn();
$totalTech  = $pdo->query("SELECT COUNT(DISTINCT technology) FROM capstone_technologies")->fetchColumn();
$latestYear = $years[0] ?? date('Y');

// Group by year for timeline
$byYear = [];
foreach ($projects as $p) { $byYear[$p['year']][] = $p; }

// Top technologies
$topTechs = $pdo->query("SELECT technology, COUNT(*) as cnt FROM capstone_technologies GROUP BY technology ORDER BY cnt DESC LIMIT 8")->fetchAll();

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>

<style>
/* ── Workflow pipeline ── */
.cap-pipeline{display:flex;align-items:center;background:#fff;border:1px solid #f1f5f9;border-radius:20px;padding:20px 28px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow-x:auto;gap:0}
.cap-stage{flex:1;min-width:100px;display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;cursor:pointer;transition:transform .2s}
.cap-stage:hover{transform:translateY(-2px)}
.cap-stage:not(:last-child)::after{content:'';position:absolute;top:18px;left:calc(50% + 24px);right:calc(-50% + 24px);height:2px;background:linear-gradient(90deg,#8b1a2e22,#8b1a2e44);z-index:0}
.cap-stage-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;position:relative;z-index:1;transition:all .3s cubic-bezier(.34,1.56,.64,1)}
.cap-stage-label{font-size:9.5px;font-weight:700;color:#94a3b8;text-align:center;line-height:1.3}
.cap-stage-count{font-size:16px;font-weight:900;color:#1e293b}
.cap-stage.active .cap-stage-icon{transform:scale(1.15);box-shadow:0 0 0 4px rgba(139,26,46,.15)}
.cap-stage.active .cap-stage-label{color:#8b1a2e}

/* ── Tech bar ── */
.tech-bar{height:6px;border-radius:99px;background:#f1f5f9;overflow:hidden;margin-top:4px}
.tech-bar-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#8b1a2e,#c0392b);transition:width 1s cubic-bezier(.4,0,.2,1)}

/* ── Project card ── */
.proj-card{background:#fff;border:1px solid #f1f5f9;border-radius:18px;padding:22px;position:relative;overflow:hidden;cursor:pointer;transition:transform .25s cubic-bezier(.34,1.56,.64,1),box-shadow .25s ease,border-color .2s}
.proj-card:hover{transform:translateY(-5px) scale(1.01);box-shadow:0 12px 36px rgba(0,0,0,.1);border-color:#e2e8f0}
.proj-card-accent{position:absolute;top:0;left:0;right:0;height:3px;border-radius:18px 18px 0 0}
.proj-year-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:3px 10px;border-radius:99px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe}
.proj-tech-tag{font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:99px}
.proj-author-chip{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:4px 10px;border-radius:99px;background:#f8fafc;color:#475569;border:1px solid #f1f5f9}

/* ── Timeline ── */
.timeline-year{position:relative;padding-left:32px;margin-bottom:32px}
.timeline-year::before{content:'';position:absolute;left:10px;top:28px;bottom:0;width:2px;background:linear-gradient(180deg,#8b1a2e,#f1f5f9)}
.timeline-dot{position:absolute;left:0;top:4px;width:22px;height:22px;border-radius:50%;background:#8b1a2e;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 4px rgba(139,26,46,.15)}
.timeline-year-label{font-size:18px;font-weight:900;color:#1e293b;margin-bottom:14px;padding-left:8px}

/* ── Detail modal ── */
.cap-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:300;animation:fadeIn .2s ease}
.cap-modal-box{background:#fff;border-radius:24px;width:100%;max-width:720px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.25);animation:modalPop .35s cubic-bezier(.34,1.56,.64,1)}
@keyframes modalPop{from{opacity:0;transform:scale(.92) translateY(20px)}to{opacity:1;transform:none}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}

/* ── View toggle ── */
.view-btn{padding:7px 14px;border-radius:9px;font-size:12px;font-weight:600;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .2s}
.view-btn.active{background:#8b1a2e;color:#fff;border-color:#8b1a2e}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.anim-in{opacity:0;animation:fadeUp .45s cubic-bezier(.22,.68,0,1.2) forwards}
</style>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Capstone Catalog</h1>
      <p class="text-slate-400 text-sm mt-0.5">Browse, explore and track 4th year capstone projects</p>
    </div>
    <?php if(isStaff()): ?>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="btn-primary">
      <i class="fas fa-plus text-xs"></i> Add Project
    </button>
    <?php endif; ?>
  </div>

  <div class="p-8">

    <!-- ── Stats + Top Techs ── -->
    <div class="grid grid-cols-4 gap-4 mb-6 anim-in" style="animation-delay:.1s">
      <?php $stats=[
        ['fa-layer-group','#8b1a2e','#fdf2f4',$totalProj,'Total Projects','In catalog'],
        ['fa-users','#3b82f6','#eff6ff',$totalAuth,'Student Authors','Across all projects'],
        ['fa-code','#8b5cf6','#f5f3ff',$totalTech,'Technologies','Unique tech used'],
        ['fa-calendar','#22c55e','#f0fdf4',$latestYear,'Latest Year','Most recent batch'],
      ]; foreach($stats as $s): ?>
      <div style="background:#fff;border:1px solid #f1f5f9;border-radius:16px;padding:18px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="width:42px;height:42px;border-radius:12px;background:<?=$s[2]?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fas <?=$s[0]?>" style="color:<?=$s[1]?>;font-size:15px"></i>
        </div>
        <div>
          <div style="font-size:20px;font-weight:900;color:#1e293b"><?=$s[3]?></div>
          <div style="font-size:12px;font-weight:600;color:#475569"><?=$s[4]?></div>
          <div style="font-size:11px;color:#94a3b8"><?=$s[5]?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Top Technologies ── -->
    <?php if($topTechs): ?>
    <div class="anim-in" style="animation-delay:.14s;background:#fff;border:1px solid #f1f5f9;border-radius:16px;padding:18px 22px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
      <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px">Top Technologies</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <?php
        $maxCnt = $topTechs[0]['cnt'];
        $tColors = ['#8b1a2e','#7c3aed','#0369a1','#065f46','#92400e','#1e40af','#be185d','#0f766e'];
        foreach($topTechs as $ti=>$t): ?>
        <div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:6px 12px;cursor:pointer;transition:all .2s"
             onclick="filterTech('<?=htmlspecialchars($t['technology'],ENT_QUOTES)?>')"
             onmouseover="this.style.borderColor='<?=$tColors[$ti%8]?>'" onmouseout="this.style.borderColor='#f1f5f9'">
          <span style="width:8px;height:8px;border-radius:50%;background:<?=$tColors[$ti%8]?>;flex-shrink:0"></span>
          <span style="font-size:12px;font-weight:600;color:#1e293b"><?=htmlspecialchars($t['technology'])?></span>
          <span style="font-size:11px;font-weight:700;color:<?=$tColors[$ti%8]?>"><?=$t['cnt']?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Filters + View Toggle ── -->
    <div class="flex items-center gap-3 mb-5 anim-in" style="animation-delay:.17s">
      <div class="search-bar flex-1"><i class="fas fa-search"></i>
        <input type="text" id="searchInput" value="<?=htmlspecialchars($search)?>" placeholder="Search title, keyword, technology, author...">
      </div>
      <select id="yearFilter" class="filter-select">
        <option value="">All Years</option>
        <?php foreach($years as $y): ?>
        <option value="<?=$y?>" <?=$yearFilter==$y?'selected':''?>><?=$y?></option>
        <?php endforeach; ?>
      </select>
      <div style="display:flex;gap:4px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:3px">
        <button class="view-btn <?=$viewMode==='grid'?'active':''?>" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        <button class="view-btn <?=$viewMode==='timeline'?'active':''?>" onclick="setView('timeline')"><i class="fas fa-stream"></i></button>
      </div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px" class="anim-in" style="animation-delay:.19s">
      <p style="font-size:13px;color:#64748b">Showing <span style="font-weight:700;color:#1e293b"><?=count($projects)?></span> projects</p>
      <span id="techFilterBadge" style="display:none;font-size:12px;font-weight:600;background:#fdf2f4;color:#8b1a2e;border:1px solid #f5c6ce;border-radius:99px;padding:3px 12px;cursor:pointer" onclick="clearTechFilter()">
        <i class="fas fa-times mr-1"></i> <span id="techFilterLabel"></span>
      </span>
    </div>

    <!-- ── GRID VIEW ── -->
    <div id="gridView" style="<?=$viewMode==='timeline'?'display:none':''?>">
      <div class="grid grid-cols-2 gap-5" id="projectGrid">
        <?php
        $techPalette=[['#f5f3ff','#7c3aed'],['#f0fdf4','#059669'],['#eff6ff','#1d4ed8'],['#fef3c7','#b45309'],['#fdf2f4','#8b1a2e'],['#f0f9ff','#0369a1']];
        $accentColors=['#8b1a2e','#7c3aed','#0369a1','#059669','#d97706','#be185d'];
        foreach($projects as $idx=>$p):
          $authors = $p['authors'] ? explode('|',$p['authors']) : [];
          $techs   = $p['technologies'] ? explode('|',$p['technologies']) : [];
          $accent  = $accentColors[$idx % count($accentColors)];
          $pData   = json_encode(['title'=>$p['title'],'description'=>$p['description'],'year'=>$p['year'],'github_url'=>$p['github_url'],'authors'=>$authors,'techs'=>$techs,'advisor'=>$p['advisor']??null],JSON_HEX_QUOT|JSON_HEX_APOS);
        ?>
        <div class="proj-card reveal" style="transition-delay:<?=($idx%4)*70?>ms" onclick='openDetails(<?=$pData?>)' data-techs="<?=htmlspecialchars(implode(',',$techs))?>">
          <div class="proj-card-accent" style="background:<?=$accent?>"></div>
          <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:12px">
            <div style="flex:1;padding-right:12px">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span class="proj-year-badge"><?=$p['year']?></span>
                <span style="font-size:11px;color:#94a3b8"><i class="fas fa-users" style="margin-right:3px"></i><?=count($authors)?> authors</span>
              </div>
              <h3 style="font-weight:800;font-size:14px;color:#1e293b;line-height:1.35"><?=htmlspecialchars($p['title'])?></h3>
            </div>
            <?php if($p['github_url']): ?>
            <a href="<?=htmlspecialchars($p['github_url'])?>" target="_blank" onclick="event.stopPropagation()"
               style="width:34px;height:34px;background:#1e293b;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s"
               onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#1e293b'">
              <i class="fab fa-github" style="color:#fff;font-size:14px"></i>
            </a>
            <?php endif; ?>
          </div>

          <p style="font-size:12px;color:#64748b;line-height:1.65;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?=htmlspecialchars($p['description'])?></p>

          <?php if($authors): ?>
          <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px">
            <?php foreach(array_slice($authors,0,3) as $a): ?>
            <span class="proj-author-chip"><i class="fas fa-user" style="font-size:9px;color:#94a3b8"></i><?=htmlspecialchars(trim($a))?></span>
            <?php endforeach; ?>
            <?php if(count($authors)>3): ?><span class="proj-author-chip">+<?=count($authors)-3?> more</span><?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if($techs): ?>
          <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:14px">
            <?php foreach(array_slice($techs,0,4) as $ti=>$t):
              $tc=$techPalette[$ti%count($techPalette)]; ?>
            <span class="proj-tech-tag" style="background:<?=$tc[0]?>;color:<?=$tc[1]?>"><?=htmlspecialchars(trim($t))?></span>
            <?php endforeach; ?>
            <?php if(count($techs)>4): ?><span class="proj-tech-tag" style="background:#f8fafc;color:#94a3b8">+<?=count($techs)-4?></span><?php endif; ?>
          </div>
          <?php endif; ?>

          <div style="display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f8fafc">
            <span style="font-size:11px;color:#94a3b8"><i class="fas fa-calendar" style="margin-right:4px"></i>Batch <?=$p['year']?></span>
            <span style="font-size:11px;font-weight:700;color:<?=$accent?>">View Details <i class="fas fa-arrow-right" style="font-size:9px"></i></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── TIMELINE VIEW ── -->
    <div id="timelineView" style="<?=$viewMode==='grid'?'display:none':''?>">
      <?php foreach($byYear as $yr=>$yProjects): ?>
      <div class="timeline-year reveal">
        <div class="timeline-dot"><i class="fas fa-layer-group" style="color:#fff;font-size:9px"></i></div>
        <div class="timeline-year-label"><?=$yr?> <span style="font-size:13px;font-weight:500;color:#94a3b8">(<?=count($yProjects)?> projects)</span></div>
        <div class="grid grid-cols-2 gap-4">
          <?php foreach($yProjects as $idx=>$p):
            $authors=explode('|',$p['authors']??''); $techs=explode('|',$p['technologies']??'');
            $authors=array_filter($authors); $techs=array_filter($techs);
            $accent=$accentColors[$idx%count($accentColors)];
            $pData=json_encode(['title'=>$p['title'],'description'=>$p['description'],'year'=>$p['year'],'github_url'=>$p['github_url'],'authors'=>array_values($authors),'techs'=>array_values($techs),'advisor'=>$p['advisor']??null],JSON_HEX_QUOT|JSON_HEX_APOS);
          ?>
          <div class="proj-card" onclick='openDetails(<?=$pData?>)' style="padding:16px">
            <div class="proj-card-accent" style="background:<?=$accent?>"></div>
            <h4 style="font-weight:700;font-size:13px;color:#1e293b;margin-bottom:6px;line-height:1.3"><?=htmlspecialchars($p['title'])?></h4>
            <p style="font-size:11px;color:#64748b;line-height:1.6;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?=htmlspecialchars($p['description'])?></p>
            <div style="display:flex;flex-wrap:wrap;gap:4px">
              <?php foreach(array_slice($techs,0,3) as $ti=>$t):
                $tc=$techPalette[$ti%count($techPalette)]; ?>
              <span class="proj-tech-tag" style="background:<?=$tc[0]?>;color:<?=$tc[1]?>;font-size:10px"><?=htmlspecialchars(trim($t))?></span>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<!-- ── Detail Modal ── -->
<div id="detailsModal" class="cap-modal-overlay" style="display:none">
  <div class="cap-modal-box">
    <div id="det-accent-bar" style="height:4px;border-radius:24px 24px 0 0"></div>
    <div style="padding:28px 32px">
      <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:20px">
        <div style="flex:1;padding-right:16px">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
            <span id="det-year" class="proj-year-badge"></span>
            <span id="det-author-count" style="font-size:12px;color:#94a3b8"></span>
          </div>
          <h2 id="det-title" style="font-weight:900;font-size:20px;color:#0f172a;line-height:1.3"></h2>
        </div>
        <button onclick="closeCapstoneDetail()" style="width:36px;height:36px;border-radius:10px;background:#f1f5f9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;transition:background .2s" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
          <i class="fas fa-times" style="font-size:13px"></i>
        </button>
      </div>

      <!-- Abstract -->
      <div style="background:#f8fafc;border-radius:16px;padding:18px;margin-bottom:18px">
        <div style="font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Abstract / Description</div>
        <p id="det-desc" style="font-size:13px;color:#475569;line-height:1.75"></p>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px">
        <!-- Team -->
        <div style="background:#f8fafc;border-radius:16px;padding:16px">
          <div style="font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Team Members</div>
          <div id="det-authors" style="display:flex;flex-wrap:wrap;gap:6px"></div>
        </div>
        <!-- Advisor -->
        <div style="background:#f8fafc;border-radius:16px;padding:16px">
          <div style="font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Advisor</div>
          <div id="det-advisor" style="font-size:13px;color:#475569"></div>
          <div style="margin-top:12px">
            <div style="font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Year</div>
            <span id="det-year2" class="proj-year-badge"></span>
          </div>
        </div>
      </div>

      <!-- Technologies -->
      <div style="margin-bottom:20px">
        <div style="font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Technologies Used</div>
        <div id="det-techs" style="display:flex;flex-wrap:wrap;gap:7px"></div>
      </div>

      <!-- Actions -->
      <div style="display:flex;gap:10px">
        <button onclick="closeCapstoneDetail()" style="flex:1;border:1.5px solid #e2e8f0;background:#fff;color:#475569;border-radius:12px;padding:11px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">Close</button>
        <a id="det-github" href="#" target="_blank" class="btn-primary justify-center" style="flex:1;display:none;text-decoration:none">
          <i class="fab fa-github" style="margin-right:6px"></i> View on GitHub
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ── Add Project Modal ── -->
<div id="addModal" class="hidden modal-overlay">
  <div class="modal-box lg">
    <div class="flex items-center justify-between mb-6">
      <div><h3 class="font-bold text-slate-800 text-base">Add Capstone Project</h3>
        <p class="text-slate-400 text-xs mt-0.5">Add a new project to the catalog</p></div>
      <button onclick="document.getElementById('addModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors"><i class="fas fa-times text-xs"></i></button>
    </div>
    <form method="POST" class="space-y-4">
      <div><label class="form-label">Project Title</label>
        <input type="text" name="title" required class="form-input"></div>
      <div><label class="form-label">Description / Abstract</label>
        <textarea name="description" rows="3" class="form-input" placeholder="Describe the project, its goals and outcomes..."></textarea></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="form-label">Year</label>
          <input type="number" name="year" value="<?=date('Y')?>" required class="form-input"></div>
        <div><label class="form-label">GitHub URL</label>
          <input type="url" name="github_url" class="form-input" placeholder="https://github.com/..."></div>
      </div>
      <div><label class="form-label">Authors <span class="text-slate-400 font-normal">(comma-separated)</span></label>
        <input type="text" name="authors" class="form-input" placeholder="John Doe, Jane Smith"></div>
      <div><label class="form-label">Technologies <span class="text-slate-400 font-normal">(comma-separated)</span></label>
        <input type="text" name="technologies" class="form-input" placeholder="React, Node.js, MySQL"></div>
      <div><label class="form-label">Advisor</label>
        <input type="text" name="advisor" class="form-input" placeholder="e.g. Dr. Maria Santos"></div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 border border-slate-200 text-slate-600 rounded-xl py-2.5 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
        <button type="submit" name="add_project" class="flex-1 btn-primary justify-center">Add Project</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Filters ──────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('keyup',function(){
    var u=new URL(window.location); u.searchParams.set('search',this.value); window.location=u;
});
document.getElementById('yearFilter').addEventListener('change',function(){
    var u=new URL(window.location); u.searchParams.set('year',this.value); window.location=u;
});

// ── View toggle ──────────────────────────────────────────
function setView(v){
    var u=new URL(window.location); u.searchParams.set('view',v); window.location=u;
}

// ── Tech filter ──────────────────────────────────────────
var activeTech='';
function filterTech(tech){
    activeTech=tech;
    document.getElementById('techFilterBadge').style.display='inline-flex';
    document.getElementById('techFilterLabel').textContent=tech;
    document.querySelectorAll('.proj-card').forEach(function(card){
        var techs=(card.dataset.techs||'').toLowerCase();
        card.closest('.proj-card') && (card.style.display=techs.includes(tech.toLowerCase())?'':'none');
    });
}
function clearTechFilter(){
    activeTech='';
    document.getElementById('techFilterBadge').style.display='none';
    document.querySelectorAll('.proj-card').forEach(function(c){ c.style.display=''; });
}
function filterStage(i){ /* future: filter by stage */ }

// ── Detail modal ─────────────────────────────────────────
var accentColors=['#8b1a2e','#7c3aed','#0369a1','#059669','#d97706','#be185d'];
var techPalette=[['#f5f3ff','#7c3aed'],['#f0fdf4','#059669'],['#eff6ff','#1d4ed8'],['#fef3c7','#b45309'],['#fdf2f4','#8b1a2e'],['#f0f9ff','#0369a1']];
var currentIdx=0;

function openDetails(p,idx){
    idx=idx||0;
    var accent=accentColors[idx%accentColors.length];
    document.getElementById('det-accent-bar').style.background=accent;
    document.getElementById('det-title').textContent=p.title;
    document.getElementById('det-year').textContent=p.year;
    document.getElementById('det-year2').textContent=p.year;
    document.getElementById('det-desc').textContent=p.description||'No description provided.';
    document.getElementById('det-author-count').textContent=p.authors.length+' team members';

    var authEl=document.getElementById('det-authors');
    authEl.innerHTML=p.authors.length
        ? p.authors.map(function(a){ return '<span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;padding:5px 11px;border-radius:99px;background:#fff;border:1px solid #e2e8f0;color:#475569"><i class="fas fa-user" style="font-size:9px;color:#94a3b8"></i>'+esc(a.trim())+'</span>'; }).join('')
        : '<span style="font-size:12px;color:#94a3b8">No authors listed</span>';

    var advEl=document.getElementById('det-advisor');
    advEl.innerHTML=p.advisor
        ? '<div style="display:flex;align-items:center;gap:8px"><div style="width:28px;height:28px;border-radius:8px;background:#fdf2f4;display:flex;align-items:center;justify-content:center"><i class="fas fa-chalkboard-teacher" style="color:#8b1a2e;font-size:11px"></i></div><span style="font-size:13px;font-weight:600;color:#1e293b">'+esc(p.advisor)+'</span></div>'
        : '<span style="font-size:12px;color:#94a3b8;font-style:italic">Not specified</span>';

    var techEl=document.getElementById('det-techs');
    techEl.innerHTML=p.techs.length
        ? p.techs.map(function(t,i){ var c=techPalette[i%techPalette.length]; return '<span style="font-size:12px;font-weight:600;padding:5px 12px;border-radius:99px;background:'+c[0]+';color:'+c[1]+'">'+esc(t.trim())+'</span>'; }).join('')
        : '<span style="font-size:12px;color:#94a3b8">No technologies listed</span>';

    var gh=document.getElementById('det-github');
    if(p.github_url){ gh.href=p.github_url; gh.style.display='flex'; }
    else { gh.style.display='none'; }

    document.getElementById('detailsModal').style.display='flex';
    document.body.style.overflow='hidden';
}

function closeCapstoneDetail(){
    document.getElementById('detailsModal').style.display='none';
    document.body.style.overflow='';
}
document.getElementById('detailsModal').addEventListener('click',function(e){ if(e.target===this) closeCapstoneDetail(); });
function esc(s){ var d=document.createElement('div');d.textContent=s;return d.innerHTML; }

// Pass index to openDetails from cards
document.querySelectorAll('.proj-card').forEach(function(card,i){
    card.addEventListener('click',function(e){
        if(e.target.closest('a')) return;
        var fn=card.getAttribute('onclick');
        if(fn) card.setAttribute('onclick',fn.replace('openDetails(','openDetails(').replace(/\)$/,','+i+')'));
    });
});
</script>

<?php
$aiContext=['title'=>'Capstone Assistant','page'=>'capstone','intro'=>"Hi! I am your Capstone Catalog Assistant.\nI can help you explore projects, find technologies used, and discover the latest research.",'suggestions'=>['How many projects are there?','Show me the latest projects','Most used technologies','How many student authors?']];
require_once 'includes/ai_widget.php';
?>
</body></html>
