<?php
/** TaskFlow — Projects list */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$activeNav = 'projects';
$pageTitle = APP_NAME . ' — Projects';
$pdo = db();

// Filters
$search   = clean($_GET['q'] ?? '');
$status   = clean($_GET['status'] ?? '');
$priority = clean($_GET['priority'] ?? '');
$sort     = clean($_GET['sort'] ?? 'updated');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;

$where = []; $params = [];
if ($search)   { $where[] = "(p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status)   { $where[] = "p.status = ?"; $params[] = $status; }
if ($priority) { $where[] = "p.priority = ?"; $params[] = $priority; }

$whereSql = $where ? implode(' AND ', $where) : '1=1';

$orderMap = [
    'updated' => 'p.updated_at DESC',
    'name'    => 'p.name ASC',
    'deadline'=> 'p.deadline IS NULL, p.deadline ASC',
    'budget'  => 'p.budget DESC',
    'progress'=> 'p.progress DESC',
];
$orderBy = $orderMap[$sort] ?? $orderMap['updated'];

$baseSql = "FROM projects p WHERE $whereSql";
$result = paginate_sql("SELECT p.*, u.name owner_name FROM projects p LEFT JOIN users u ON u.id=p.created_by WHERE $whereSql ORDER BY $orderBy", $params, $page, $perPage);
$projects = $result['rows'];

// member counts per project
$memberCounts = [];
if ($projects) {
    $ids = array_column($projects, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT project_id, COUNT(*) c FROM project_members WHERE project_id IN ($in) GROUP BY project_id");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $r) $memberCounts[$r['project_id']] = (int)$r['c'];
}

// members for modal
$teams = $pdo->query("SELECT id, name FROM users WHERE status='active' ORDER BY name")->fetchAll();

$qs = http_build_query(array_filter(['q'=>$search,'status'=>$status,'priority'=>$priority,'sort'=>$sort]));
$base = 'projects.php?' . ($qs ? $qs . '&' : '');

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Projects</h1>
    <p><?= $result['total'] ?> project<?= $result['total']===1?'':'s' ?> total</p>
  </div>
  <div class="page-head__actions">
    <?php if (is_manager()): ?>
    <button class="btn" data-open-modal="projectModal" onclick="fillProjectModal(null)"><svg viewBox="0 0 24 24" class="ic"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg> New Project</button>
    <?php endif; ?>
  </div>
</div>

<div class="toolbar">
  <input type="text" id="projSearch" class="input" placeholder="Search projects…" value="<?= e($search) ?>" style="min-width:220px">
  <label>Status
  <select id="fStatus" class="select">
    <option value="">All</option>
    <?php foreach (['planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select></label>
  <label>Priority
  <select id="fPriority" class="select">
    <option value="">All</option>
    <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $priority===$k?'selected':'' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select></label>
  <label>Sort
  <select id="fSort" class="select">
    <option value="updated" <?= $sort==='updated'?'selected':'' ?>>Last updated</option>
    <option value="name" <?= $sort==='name'?'selected':'' ?>>Name</option>
    <option value="deadline" <?= $sort==='deadline'?'selected':'' ?>>Deadline</option>
    <option value="budget" <?= $sort==='budget'?'selected':'' ?>>Budget</option>
    <option value="progress" <?= $sort==='progress'?'selected':'' ?>>Progress</option>
  </select></label>
  <div class="spacer"></div>
  <a href="reports.php?type=projects" class="btn btn--ghost btn--sm"><svg viewBox="0 0 24 24" class="ic"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Export</a>
</div>

<?php if (!$projects): ?>
  <div class="card"><div class="empty">
    <svg viewBox="0 0 24 24" class="ic"><path d="M20 6h-4V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4z"/></svg>
    <h3>No projects found</h3>
    <p>Try adjusting filters or create a new project.</p>
  </div></div>
<?php else: ?>
<div class="grid grid-3">
  <?php foreach ($projects as $p):
    [$sl,$sb] = project_status_label($p['status']);
    [$pl,$pb] = priority_label($p['priority']);
    $budgetPct = $p['budget'] > 0 ? min(100, round($p['spent']/$p['budget']*100)) : 0;
    $overBudget = $p['budget'] > 0 && $p['spent'] > $p['budget'];
  ?>
  <div class="card" data-row style="display:flex;flex-direction:column">
    <div class="card__body" style="flex:1">
      <div class="flex items-center justify-between mb-2">
        <span class="badge <?= $sb ?> badge--dot"><?= $sl ?></span>
        <span class="badge <?= $pb ?>"><?= $pl ?></span>
      </div>
      <a href="project_view.php?id=<?= $p['id'] ?>" class="title-link" style="font-size:16px;font-weight:700"><?= e($p['name']) ?></a>
      <p class="text-mute" style="font-size:13px;margin-top:6px;min-height:38px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= e($p['description'] ?: 'No description') ?></p>

      <div class="flex justify-between items-center mb-1" style="margin-top:14px;font-size:12px">
        <span class="text-mute">Progress</span>
        <strong><?= (int)$p['progress'] ?>%</strong>
      </div>
      <div class="progress"><div class="progress__bar <?= $p['progress']>=100?'progress__bar--green':'' ?>" style="width:<?= (int)$p['progress'] ?>%"></div></div>

      <div class="flex justify-between items-center" style="margin-top:14px">
        <div class="avatar-group">
          <?php
          $ms = $pdo->prepare("SELECT u.name FROM project_members pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=? LIMIT 4");
          $ms->execute([$p['id']]);
          foreach ($ms->fetchAll() as $m) echo avatar_html($m['name'], null, '28');
          if (($memberCounts[$p['id']] ?? 0) > 4) echo '<span class="avatar avatar--text" style="width:28px;height:28px;font-size:11px">+' . ((int)$memberCounts[$p['id']]-4) . '</span>';
          ?>
        </div>
        <span class="text-mute" style="font-size:12px">
          <svg viewBox="0 0 24 24" class="ic ic-sm" style="vertical-align:-3px"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14v10z"/></svg>
          <?= fmt_date($p['deadline'],'M j') ?>
        </span>
      </div>

      <div class="divider" style="margin:14px 0 10px"></div>
      <div class="flex justify-between items-center">
        <span class="chip"><?= money($p['budget']) ?> <span class="text-mute">· <?= $budgetPct ?>% used</span></span>
        <?php if ($overBudget): ?><span class="badge badge--red">Over budget</span><?php endif; ?>
      </div>
    </div>
    <div class="card__header" style="border-top:1px solid var(--border);border-bottom:0;padding:10px 16px">
      <span class="text-mute" style="font-size:12px">Owner: <?= e($p['owner_name']) ?></span>
      <div class="flex gap-1">
        <?php if (is_manager()): ?>
        <button class="btn btn--ghost btn--sm btn--icon" title="Edit" onclick="editProject(<?= (int)$p['id'] ?>)"><svg viewBox="0 0 24 24" class="ic"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
        <button class="btn btn--ghost btn--sm btn--icon" title="Delete"
          data-delete-url="api/project_delete.php"
          data-confirm-title="Delete project"
          data-confirm-msg="Delete \"<?= e($p['name']) ?>\" and all its tasks? This cannot be undone.">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <svg viewBox="0 0 24 24" class="ic"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?= pagination_links($result['page'], $result['pages'], $base) ?>
<?php endif; ?>

<?php
include __DIR__ . '/includes/_project_modal.php';
require __DIR__ . '/includes/footer.php';
?>
<script>
// filter wiring
function goFilter(){
  const p = new URLSearchParams();
  const q=document.getElementById('projSearch').value.trim();
  const s=document.getElementById('fStatus').value;
  const pr=document.getElementById('fPriority').value;
  const so=document.getElementById('fSort').value;
  if(q)p.set('q',q); if(s)p.set('status',s); if(pr)p.set('priority',pr); if(so)p.set('sort',so);
  location.href='projects.php?'+p.toString();
}
document.getElementById('projSearch').addEventListener('keydown',e=>{if(e.key==='Enter')goFilter();});
['fStatus','fPriority','fSort'].forEach(id=>document.getElementById(id).addEventListener('change',goFilter));

// edit project: fetch + fill
function editProject(id){
  fetch('api/project_get.php?id='+id,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}})
    .then(r=>r.json()).then(d=>{
      if(d.success){ fillProjectModal(d.project); openModal('projectModal'); }
      else toast(d.message||'Could not load','error');
    });
}
</script>
