<?php
$aiTitle = $aiContext['title']       ?? 'AI Assistant';
$aiIntro = $aiContext['intro']       ?? 'Hi! I am your AI Assistant. How can I help you?';
$aiSuggs = $aiContext['suggestions'] ?? [];
$aiPage  = $aiContext['page']        ?? 'general';

$pageColors = [
    'devices'   => ['grad'=>'#3b82f6,#2563eb', 'shadow'=>'rgba(59,130,246,.5)',  'border'=>'rgba(59,130,246,.2)',  'sbBg'=>'#eff6ff', 'sbBorder'=>'#3b82f6', 'sbColor'=>'#1d4ed8', 'dot'=>'#93c5fd', 'focus'=>'#3b82f6'],
    'lostfound' => ['grad'=>'#f97316,#ea580c', 'shadow'=>'rgba(249,115,22,.5)',  'border'=>'rgba(249,115,22,.2)',  'sbBg'=>'#fff7ed', 'sbBorder'=>'#f97316', 'sbColor'=>'#c2410c', 'dot'=>'#fdba74', 'focus'=>'#f97316'],
    'capstone'  => ['grad'=>'#9333ea,#7c3aed', 'shadow'=>'rgba(147,51,234,.5)',  'border'=>'rgba(147,51,234,.2)',  'sbBg'=>'#faf5ff', 'sbBorder'=>'#9333ea', 'sbColor'=>'#6b21a8', 'dot'=>'#d8b4fe', 'focus'=>'#9333ea'],
    'community' => ['grad'=>'#10b981,#059669', 'shadow'=>'rgba(16,185,129,.5)',  'border'=>'rgba(16,185,129,.2)',  'sbBg'=>'#f0fdf4', 'sbBorder'=>'#10b981', 'sbColor'=>'#065f46', 'dot'=>'#6ee7b7', 'focus'=>'#10b981'],
    'general'   => ['grad'=>'#22c55e,#16a34a', 'shadow'=>'rgba(34,197,94,.5)',   'border'=>'rgba(34,197,94,.2)',   'sbBg'=>'#f0fdf4', 'sbBorder'=>'#22c55e', 'sbColor'=>'#16a34a', 'dot'=>'#a7f3d0', 'focus'=>'#22c55e'],
];
$c = $pageColors[$aiPage] ?? $pageColors['general'];
?>
<style>
#ai-fab{position:fixed;bottom:28px;right:28px;z-index:9999;width:56px;height:56px;border-radius:16px;border:none;cursor:pointer;background:linear-gradient(135deg,<?= $c['grad'] ?>);box-shadow:0 4px 20px <?= $c['shadow'] ?>;display:flex;align-items:center;justify-content:center;transition:transform .2s}
#ai-fab:hover{transform:scale(1.08)}
#ai-panel{position:fixed;bottom:96px;right:28px;z-index:9999;width:340px;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.18);border:1px solid <?= $c['border'] ?>;background:#fff;display:none;flex-direction:column}
@media(max-width:768px){
    #ai-fab{bottom:72px;right:16px;width:48px;height:48px;}
    #ai-panel{bottom:128px;right:8px;width:calc(100vw - 16px);max-width:340px;}
}
#ai-head{background:linear-gradient(135deg,<?= $c['grad'] ?>);padding:12px 16px;display:flex;align-items:center;justify-content:space-between}
#ai-msgs{height:300px;overflow-y:auto;padding:16px;background:#f8fafc;display:flex;flex-direction:column;gap:12px}
#ai-foot{padding:10px 12px;background:#fff;border-top:1px solid #f1f5f9;display:flex;gap:8px;align-items:center}
#ai-inp{flex:1;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;font-family:inherit}
#ai-inp:focus{border-color:<?= $c['focus'] ?>}
#ai-go{width:40px;height:40px;border-radius:12px;border:none;cursor:pointer;background:linear-gradient(135deg,<?= $c['grad'] ?>);flex-shrink:0;display:flex;align-items:center;justify-content:center}
.ai-row{display:flex;gap:8px;align-items:flex-start}
.ai-row.u{justify-content:flex-end}
.ai-av{width:28px;height:28px;border-radius:10px;flex-shrink:0;background:linear-gradient(135deg,<?= $c['grad'] ?>);display:flex;align-items:center;justify-content:center}
.ai-bub{background:#fff;border-radius:16px;border-top-left-radius:4px;padding:10px 14px;font-size:13px;color:#374151;line-height:1.6;max-width:82%;box-shadow:0 1px 4px rgba(0,0,0,.07);white-space:pre-wrap;word-break:break-word}
.u-bub{background:linear-gradient(135deg,<?= $c['grad'] ?>);border-radius:16px;border-top-right-radius:4px;padding:10px 14px;font-size:13px;color:#fff;line-height:1.6;max-width:82%;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.ai-sb{background:<?= $c['sbBg'] ?>;border:1.5px solid <?= $c['sbBorder'] ?>;color:<?= $c['sbColor'] ?>;border-radius:99px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;text-align:left;font-family:inherit;transition:all .15s;width:100%}
.ai-sb:hover{background:linear-gradient(135deg,<?= $c['grad'] ?>);color:#fff;border-color:transparent}
.ai-dot{width:8px;height:8px;border-radius:50%;background:<?= $c['sbBorder'] ?>;animation:ai-b 1s infinite}
.ai-dot:nth-child(2){animation-delay:.15s}
.ai-dot:nth-child(3){animation-delay:.3s}
@keyframes ai-b{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
</style>

<button id="ai-fab" title="AI Assistant">
    <i class="fas fa-robot" style="color:#fff;font-size:20px"></i>
</button>

<div id="ai-panel">
    <div id="ai-head">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center">
                <i class="fas fa-robot" style="color:#fff;font-size:13px"></i>
            </div>
            <div>
                <div style="color:#fff;font-weight:700;font-size:14px"><?= htmlspecialchars($aiTitle) ?></div>
                <div style="color:rgba(255,255,255,.8);font-size:11px;display:flex;align-items:center;gap:4px">
                    <span style="width:6px;height:6px;border-radius:50%;background:<?= $c['dot'] ?>;display:inline-block"></span>
                    AI Online
                </div>
            </div>
        </div>
        <button id="ai-close" style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.2);border:none;cursor:pointer;color:#fff;font-size:12px">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div id="ai-msgs">
        <div class="ai-row">
            <div class="ai-av"><i class="fas fa-robot" style="color:#fff;font-size:11px"></i></div>
            <div class="ai-bub"><?= nl2br(htmlspecialchars($aiIntro)) ?></div>
        </div>
        <div id="ai-suggs" style="display:flex;flex-direction:column;gap:6px;padding-left:36px">
            <?php foreach($aiSuggs as $s): ?>
            <button class="ai-sb" data-text="<?= htmlspecialchars($s, ENT_QUOTES) ?>"><?= htmlspecialchars($s) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="ai-foot">
        <input type="text" id="ai-inp" placeholder="Ask the AI assistant...">
        <button id="ai-go">
            <i class="fas fa-paper-plane" style="color:#fff;font-size:12px"></i>
        </button>
    </div>
</div>

<script>
(function(){
    var PAGE = <?= json_encode($aiPage) ?>;
    var URL  = '/api/ai_chat.php';
    var open = false;

    var fab   = document.getElementById('ai-fab');
    var panel = document.getElementById('ai-panel');
    var close = document.getElementById('ai-close');
    var msgs  = document.getElementById('ai-msgs');
    var inp   = document.getElementById('ai-inp');
    var go    = document.getElementById('ai-go');
    var suggs = document.getElementById('ai-suggs');

    function toggle(){
        open = !open;
        panel.style.display = open ? 'flex' : 'none';
        fab.querySelector('i').className = open ? 'fas fa-times' : 'fas fa-robot';
        fab.querySelector('i').style.cssText = 'color:#fff;font-size:20px';
    }

    fab.addEventListener('click', toggle);
    close.addEventListener('click', toggle);

    // Suggestion buttons
    suggs.querySelectorAll('.ai-sb').forEach(function(btn){
        btn.addEventListener('click', function(){
            var text = this.getAttribute('data-text');
            suggs.style.display = 'none';
            send(text);
        });
    });

    // Send on Enter
    inp.addEventListener('keydown', function(e){
        if(e.key === 'Enter') send();
    });
    go.addEventListener('click', function(){ send(); });

    function append(text, isUser){
        var row = document.createElement('div');
        row.className = 'ai-row' + (isUser ? ' u' : '');
        if(isUser){
            row.innerHTML = '<div class="u-bub">' + esc(text) + '</div>';
        } else {
            row.innerHTML = '<div class="ai-av"><i class="fas fa-robot" style="color:#fff;font-size:11px"></i></div>'
                          + '<div class="ai-bub">' + esc(text).replace(/\n/g,'<br>') + '</div>';
        }
        msgs.appendChild(row);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function esc(s){
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function showTyping(){
        var row = document.createElement('div');
        row.id = 'ai-typing'; row.className = 'ai-row';
        row.innerHTML = '<div class="ai-av"><i class="fas fa-robot" style="color:#fff;font-size:11px"></i></div>'
                      + '<div class="ai-bub" style="display:flex;gap:4px;padding:12px 14px">'
                      + '<div class="ai-dot"></div><div class="ai-dot"></div><div class="ai-dot"></div></div>';
        msgs.appendChild(row);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function send(preset){
        var msg = preset !== undefined ? preset : inp.value.trim();
        if(!msg) return;
        inp.value = '';
        append(msg, true);
        showTyping();
        fetch(URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({message: msg, page: PAGE})
        })
        .then(function(r){ return r.text(); })
        .then(function(t){
            var el = document.getElementById('ai-typing');
            if(el) el.remove();
            try {
                var d = JSON.parse(t);
                append(d.reply || 'No reply.');
            } catch(e) {
                append('Error: ' + t.substring(0,200));
            }
        })
        .catch(function(e){
            var el = document.getElementById('ai-typing');
            if(el) el.remove();
            append('Network error: ' + e.message);
        });
    }
})();
</script>
