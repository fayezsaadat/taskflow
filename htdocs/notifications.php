<?php
/** TaskFlow — Notifications */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$activeNav = '';
$pageTitle = APP_NAME . ' — Notifications';
$pdo = db();

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='read_all') {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$cu['id']]);
    set_flash('success','All notifications marked as read');
    redirect('notifications.php');
}

$tab = clean($_GET['tab'] ?? 'all');
$where = "user_id = ?";
$params = [$cu['id']];
if ($tab==='unread') { $where .= " AND is_read=0"; }
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$notifications = $stmt->fetchAll();
$unread = unread_count((int)$cu['id']);

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Notifications</h1><p><?= $unread ?> unread</p></div>
  <div class="page-head__actions">
    <?php if ($unread>0): ?>
    <form method="post" style="display:inline"><input type="hidden" name="action" value="read_all"><button class="btn btn--ghost btn--sm"><svg viewBox="0 0 24 24" class="ic"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5c-1.7-4.4-6-7.5-11-7.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg> Mark all read</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="tabs mb-3">
  <button class="tab <?= $tab!=='unread'?'is-active':'' ?>" onclick="location.href='notifications.php'">All</button>
  <button class="tab <?= $tab==='unread'?'is-active':'' ?>" onclick="location.href='notifications.php?tab=unread'">Unread (<?= $unread ?>)</button>
</div>

<div class="card card--padless">
  <?php if(!$notifications): ?>
    <div class="empty"><svg viewBox="0 0 24 24" class="ic"><path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg><h3>You're all caught up</h3><p>No notifications here.</p></div>
  <?php else: foreach ($notifications as $n):
    $iconMap=['task'=>'📋','project'=>'📦','comment'=>'💬','warning'=>'⚠️','info'=>'ℹ️'];
    $icon=$iconMap[$n['type']] ?? '🔔';
  ?>
    <a href="<?= $n['link'] ?: '#' ?>" class="flex items-start gap-2" style="padding:14px 18px;border-bottom:1px solid var(--border);<?= $n['is_read']?'':'background:var(--primary-50)' ?>" onclick="markRead(<?= (int)$n['id'] ?>)">
      <div style="width:38px;height:38px;border-radius:50%;background:var(--bg-soft);display:grid;place-items:center;font-size:18px;flex-shrink:0"><?= $icon ?></div>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13.5px;color:var(--text)"><?= e($n['title']) ?> <?= !$n['is_read']?'<span style="width:8px;height:8px;border-radius:50%;background:var(--primary);display:inline-block"></span>':'' ?></div>
        <div class="text-soft" style="font-size:13px;margin-top:2px"><?= e($n['message']) ?></div>
        <div class="text-mute" style="font-size:11.5px;margin-top:3px"><?= time_ago($n['created_at']) ?></div>
      </div>
    </a>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script>
function markRead(id){
  fetch('api/notification_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':window.CSRF_TOKEN},body:'id='+id}).catch(()=>{});
}
</script>
