<?php
require_once 'includes/auth_guard.php';
require_once 'config/db.php';
requireStaff();
$pageTitle = 'Post Item — AI Campus Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_item'])) {
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

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Post Item</h1>
            <p class="text-slate-400 text-sm mt-0.5">Add a lost or found item to the board</p>
        </div>
        <a href="lost-found.php" class="btn-primary" style="background:#64748b">
            <i class="fas fa-arrow-left text-xs"></i> Back
        </a>
    </div>
    <div class="p-8">
        <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:20px;padding:28px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.06)">
            <form method="POST" enctype="multipart/form-data" class="space-y-4" id="postForm">
                <input type="hidden" name="post_item" value="1">
                <div>
                    <label class="form-label">Type</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <label style="display:flex;align-items:center;gap:8px;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer">
                            <input type="radio" name="type" value="lost" style="accent-color:#dc2626">
                            <span style="font-size:13px;font-weight:600;color:#dc2626"><i class="fas fa-exclamation-circle" style="margin-right:4px"></i>Lost</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer">
                            <input type="radio" name="type" value="found" checked style="accent-color:#16a34a">
                            <span style="font-size:13px;font-weight:600;color:#16a34a"><i class="fas fa-check-circle" style="margin-right:4px"></i>Found</span>
                        </label>
                    </div>
                </div>
                <div><label class="form-label">Item Title</label>
                    <input type="text" name="title" required class="form-input" placeholder="e.g. Blue Notebook"></div>
                <div><label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input" placeholder="Describe the item..."></textarea></div>
                <div><label class="form-label">Location</label>
                    <input type="text" name="location" required class="form-input" placeholder="e.g. Room 301"></div>
                <div><label class="form-label">Posted By</label>
                    <input type="text" name="posted_by" required class="form-input" placeholder="Your name"></div>
                <div>
                    <label class="form-label">Photo <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
                    <input type="file" name="item_image" accept="image/*" class="form-input" style="padding:8px">
                </div>
                <button type="button" onclick="this.closest('form').submit()" class="btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:15px">
                    <i class="fas fa-paper-plane"></i> Post Item
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once 'includes/ai_widget.php'; ?>
</body></html>
