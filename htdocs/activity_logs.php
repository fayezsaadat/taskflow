<?php
/** TaskFlow — Activity logs (admin) */
require_once __DIR__ . '/includes/auth.php';
require_role('admin','manager');
$activeNav = 'logs';
$pageTitle = APP_NAME . ' — Activity Logs';
$pdo = db();

$search = clean($_GET['q'] ?? '');
$where=[]; $params=[];
if ($search) { $where[]="(l.description LIKE ? OR u.name LIKE ? OR l.action LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
$whereSql = $where ? implode(' AND ',$where) : '1=1';
$result = paginate_sql("SELECT l.*, u.name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id WHERE $whereSql ORDER BY l.created_at DESC", $params, max(1,(int)($_GET['page']??1)), 30);
$logs = $result['rows'];
$base='activity_logs.php?'.($search?'q='.urlencode($search).'&':'');

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Activity Logs</h1><p>Audit trail of all actions across TaskFlow</p></div>
  <div class="page-head__actions">
    <a href="reports.php?type=logs" class="btn btn--ghost btn--sm"><svg viewBox="0 0 24 24" class="ic"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Export CSV</a>
  </div>
</div>

<div class="toolbar">
  <input type="text" id="logSearch" class="input" placeholder="Search logs…" value="<?= e($search) ?>" style="min-width:260px">
</div>

<div class="card card--padless">
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Description</th><th>IP</th></tr></thead>
    <tbody>
    <?php if(!$logs): ?><tr><td colspan="6"><div class="empty">No activity yet.</div></td></tr><?php else: foreach($logs as $l): ?>
      <tr>
        <td class="text-mute" style="white-space:nowrap"><?= fmt_datetime($l['created_at'],'M j, g:ia') ?></td>
        <td><?= $l['name']?'<div class="flex items-center gap-1">'.avatar_html($l['name'],null,'24').'<span>'.e($l['name']).'</span></div>':'<span class="text-mute">System</span>' ?></td>
        <td><span class="badge badge--gray"><?= e($l['action']) ?></span></td>
        <td class="text-mute"><?= e($l['entity_type'] ?: '—') ?> <?= $l['entity_id']?'#'.(int)$l['entity_id']:'' ?></td>
        <td style="max-width:400px"><?= e($l['description'] ?: '—') ?></td>
        <td class="text-mute"><?= e($l['ip_address'] ?: '—') ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
<?= pagination_links($result['page'],$result['pages'],$base) ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script>
document.getElementById('logSearch').addEventListener('keydown',e=>{if(e.key==='Enter'){const q=e.target.value.trim();location.href='activity_logs.php'+(q?'?q='+encodeURIComponent(q):'');}});
</script>
