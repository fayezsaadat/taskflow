<?php
/** TaskFlow — Team management */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$activeNav = 'team';
$pageTitle = APP_NAME . ' — Team';
$pdo = db();

$search = clean($_GET['q'] ?? '');
$roleF  = clean($_GET['role'] ?? '');
$deptF  = clean($_GET['dept'] ?? '');

$where=[]; $params=[];
if ($search) { $where[]="(name LIKE ? OR email LIKE ? OR job_title LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
if ($roleF) { $where[]="role=?"; $params[]=$roleF; }
if ($deptF) { $where[]="department=?"; $params[]=$deptF; }
$whereSql = $where ? implode(' AND ',$where) : '1=1';

$result = paginate_sql("SELECT * FROM users WHERE $whereSql ORDER BY role='admin' DESC, role='manager' DESC, name ASC", $params, max(1,(int)($_GET['page']??1)), 12);
$users = $result['rows'];

// departments for filter
$depts = $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department<>'' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// counts for each user
$counts = [];
if ($users) {
    $ids = array_column($users,'id');
    $in = implode(',', array_fill(0,count($ids),'?'));
    $stmt=$pdo->prepare("SELECT assigned_to uid, COUNT(*) total, SUM(status<>'completed') open FROM tasks WHERE assigned_to IN ($in) GROUP BY assigned_to");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $r) $counts[$r['uid']] = ['total'=>(int)$r['total'],'open'=>(int)$r['open']];
}

$qs=http_build_query(array_filter(['q'=>$search,'role'=>$roleF,'department'=>$deptF]));
$base='team.php?'.($qs?$qs.'&':'');

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>Team Members</h1><p><?= $result['total'] ?> member<?= $result['total']===1?'':'s' ?></p></div>
  <div class="page-head__actions">
    <?php if (is_admin()): ?>
    <button class="btn" onclick="openModal('userModal')"><svg viewBox="0 0 24 24" class="ic"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/></svg> Add Member</button>
    <?php endif; ?>
  </div>
</div>

<div class="toolbar">
  <input type="text" id="teamSearch" class="input" placeholder="Search members…" value="<?= e($search) ?>" style="min-width:220px">
  <label>Role<select id="fRole" class="select"><option value="">All</option>
    <?php foreach(['admin'=>'Admin','manager'=>'Manager','employee'=>'Employee'] as $k=>$v): ?><option value="<?= $k ?>" <?= $roleF===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
  </select></label>
  <label>Department<select id="fDept" class="select"><option value="">All</option>
    <?php foreach($depts as $d): ?><option value="<?= e($d) ?>" <?= $deptF===$d?'selected':'' ?>><?= e($d) ?></option><?php endforeach; ?>
  </select></label>
  <div class="spacer"></div>
  <a href="reports.php?type=team" class="btn btn--ghost btn--sm"><svg viewBox="0 0 24 24" class="ic"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Export CSV</a>
</div>

<div class="grid grid-3">
<?php foreach ($users as $u): $cnt=$counts[$u['id']] ?? ['total'=>0,'open'=>0]; ?>
  <div class="card" data-row style="text-align:center">
    <div class="card__body">
      <div style="display:flex;justify-content:center;margin-bottom:10px"><?= avatar_html($u['name'],$u['avatar'],'64') ?></div>
      <div style="font-weight:700;font-size:15px"><?= e($u['name']) ?></div>
      <div class="text-mute" style="font-size:12.5px;margin-bottom:8px"><?= e($u['job_title'] ?: role_label($u['role'])) ?></div>
      <span class="badge <?= role_badge($u['role']) ?>"><?= role_label($u['role']) ?></span>
      <div class="divider"></div>
      <div class="flex justify-between text-center" style="margin-bottom:10px">
        <div><div style="font-weight:700;font-size:16px"><?= $cnt['total'] ?></div><div class="text-mute" style="font-size:11px">Tasks</div></div>
        <div><div style="font-weight:700;font-size:16px"><?= $cnt['open'] ?></div><div class="text-mute" style="font-size:11px">Open</div></div>
        <div><div style="font-weight:700;font-size:16px"><?= $cnt['total']-$cnt['open'] ?></div><div class="text-mute" style="font-size:11px">Done</div></div>
      </div>
      <div class="text-mute" style="font-size:12px"><?= e($u['department'] ?: '—') ?> · <?= e($u['email']) ?></div>
    </div>
    <div class="card__header" style="border-top:1px solid var(--border);border-bottom:0;padding:8px;justify-content:center">
      <a href="profile.php?id=<?= (int)$u['id'] ?>" class="btn btn--ghost btn--sm">View profile</a>
      <?php if (is_admin() && (int)$u['id']!==$cu['id']): ?>
      <button class="btn btn--ghost btn--icon btn--sm" title="Remove" data-delete-url="api/user_delete.php" data-confirm-title="Remove member" data-confirm-msg="Remove <?= e($u['name']) ?> from the team? Their tasks will be unassigned."><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?= pagination_links($result['page'],$result['pages'],$base) ?>

<!-- Add member modal -->
<div class="modal-overlay" id="userModal">
  <div class="modal">
    <div class="modal__header"><h3>Add Team Member</h3><button class="modal__close" data-close-modal>&times;</button></div>
    <div class="modal__body">
      <form id="userForm" data-ajax action="api/user_save.php" data-reset-on-success="1">
        <input type="hidden" name="action" value="create">
        <div class="field"><label>Full name *</label><input type="text" name="name" class="input" required></div>
        <div class="field"><label>Email *</label><input type="email" name="email" class="input" required></div>
        <div class="form-grid">
          <div class="field"><label>Role</label><select name="new_role" class="select"><option value="employee">Employee</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
          <div class="field"><label>Phone</label><input type="text" name="phone" class="input"></div>
          <div class="field"><label>Department</label><input type="text" name="department" class="input"></div>
          <div class="field"><label>Job title</label><input type="text" name="job_title" class="input"></div>
        </div>
        <div class="field"><label>Temporary password *</label><input type="text" name="password" class="input" required value="changeme123"><div class="hint">User can change this later in settings.</div></div>
        <div class="form-actions"><button type="button" class="btn btn--ghost" data-close-modal>Cancel</button><button type="submit" class="btn">Add Member</button></div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script>
function goFilter(){const p=new URLSearchParams();const q=document.getElementById('teamSearch').value.trim();
  if(q)p.set('q',q);const r=document.getElementById('fRole');const d=document.getElementById('fDept');
  if(r.value)p.set('role',r.value);if(d.value)p.set('department',d.value);
  location.href='team.php?'+p.toString();}
document.getElementById('teamSearch').addEventListener('keydown',e=>{if(e.key==='Enter')goFilter();});
['fRole','fDept'].forEach(id=>document.getElementById(id).addEventListener('change',goFilter));
</script>
