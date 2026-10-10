<?php
/** TaskFlow — My Tasks / All Tasks */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$activeNav = 'tasks';
$pageTitle = APP_NAME . ' — Tasks';
$pdo = db();

$search   = clean($_GET['q'] ?? '');
$status   = clean($_GET['status'] ?? '');
$priority = clean($_GET['priority'] ?? '');
$projectF = (int)($_GET['project'] ?? 0);
$view     = clean($_GET['view'] ?? 'list'); // list | board
$scope    = clean($_GET['scope'] ?? ($cu['role']==='employee'?'mine':'all'));

$where = []; $params = [];
if ($scope === 'mine' || $cu['role']==='employee') {
    $where[] = "t.assigned_to = ?"; $params[] = $cu['id'];
}
if ($search)   { $where[] = "(t.title LIKE ? OR t.description LIKE ?)"; $params[]=$search; $params[]=$search; }
if ($status)   { $where[] = "t.status = ?"; $params[]=$status; }
if ($priority) { $where[] = "t.priority = ?"; $params[]=$priority; }
if ($projectF) { $where[] = "t.project_id = ?"; $params[]=$projectF; }

$whereSql = $where ? implode(' AND ', $where) : '1=1';

// board view needs all matching
if ($view === 'board') {
    $stmt = $pdo->prepare("SELECT t.*, p.name project_name, u.name assignee_name FROM tasks t JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.assigned_to WHERE $whereSql ORDER BY t.priority='urgent' DESC, t.deadline IS NULL, t.deadline ASC");
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    $result = ['rows'=>$tasks,'total'=>count($tasks),'page'=>1,'pages'=>1];
} else {
    $result = paginate_sql(
        "SELECT t.*, p.name project_name, u.name assignee_name FROM tasks t JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.assigned_to WHERE $whereSql ORDER BY t.status<>'completed' DESC, t.deadline IS NULL, t.deadline ASC",
        $params, max(1,(int)($_GET['page']??1)), 12);
    $tasks = $result['rows'];
}

$projects = $pdo->query("SELECT id,name FROM projects ORDER BY name")->fetchAll();
$members  = $pdo->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetchAll();
$qs = http_build_query(array_filter(['q'=>$search,'status'=>$status,'priority'=>$priority,'project'=>$projectF,'scope'=>$scope,'view'=>$view]));
$base = 'tasks.php?' . ($qs?$qs.'&':'');

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1><?= ($scope==='mine'||$cu['role']==='employee')?'My Tasks':'All Tasks' ?></h1>
    <p><?= $result['total'] ?> task<?= $result['total']===1?'':'s' ?></p>
  </div>
  <div class="page-head__actions">
    <?php if (is_manager()): ?>
    <button class="btn btn--sm" onclick="openTaskModal(0, <?= $projects? (int)$projects[0]['id']:0 ?>)"><svg viewBox="0 0 24 24" class="ic"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg> New Task</button>
    <?php endif; ?>
  </div>
</div>

<div class="toolbar">
  <input type="text" id="taskSearch" class="input" placeholder="Search tasks…" value="<?= e($search) ?>" style="min-width:200px">
  <label>Status
  <select id="fStatus" class="select">
    <option value="">All</option>
    <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','completed'=>'Completed'] as $k=>$v): ?><option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
  </select></label>
  <label>Priority
  <select id="fPriority" class="select">
    <option value="">All</option>
    <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $k=>$v): ?><option value="<?= $k ?>" <?= $priority===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
  </select></label>
  <label>Project
  <select id="fProject" class="select">
    <option value="">All</option>
    <?php foreach ($projects as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $projectF===(int)$p['id']?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
  </select></label>
  <?php if ($cu['role']!=='employee'): ?>
  <label>Scope
  <select id="fScope" class="select">
    <option value="all" <?= $scope==='all'?'selected':'' ?>>All tasks</option>
    <option value="mine" <?= $scope==='mine'?'selected':'' ?>>Assigned to me</option>
  </select></label>
  <?php endif; ?>
  <div class="spacer"></div>
  <div class="flex gap-1">
    <a class="btn btn--sm <?= $view!=='board'?'btn--soft':'btn--ghost' ?>" href="<?= strtok($_SERVER['REQUEST_URI'],'?') ?>?<?= http_build_query(array_filter(['q'=>$search,'status'=>$status,'priority'=>$priority,'project'=>$projectF,'scope'=>$scope,'view'=>'list'])) ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg> List</a>
    <a class="btn btn--sm <?= $view==='board'?'btn--soft':'btn--ghost' ?>" href="<?= strtok($_SERVER['REQUEST_URI'],'?') ?>?<?= http_build_query(array_filter(['q'=>$search,'status'=>$status,'priority'=>$priority,'project'=>$projectF,'scope'=>$scope,'view'=>'board'])) ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M3 5h8v6H3V5zm10 0h8v6h-8V5zM3 13h8v6H3v-6zm10 0h8v6h-8v-6z"/></svg> Board</a>
  </div>
</div>

<?php if ($view === 'board' && $tasks): ?>
<div class="grid grid-kanban">
  <?php
  $cols = ['todo'=>['To Do','#94a3b8'],'in_progress'=>['In Progress','#3b82f6'],'review'=>['Review','#f59e0b'],'completed'=>['Completed','#10b981']];
  foreach ($cols as $key=>[$label,$color]):
    $colTasks = array_filter($tasks, fn($t)=>$t['status']===$key);
  ?>
  <div class="kanban-col">
    <div class="kanban-col__head"><h4><span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;display:inline-block"></span> <?= $label ?></h4><span class="kanban-col__count"><?= count($colTasks) ?></span></div>
    <?php foreach ($colTasks as $t): [$pl,$pb]=priority_label($t['priority']); $ov=is_overdue($t['deadline']) && $key!=='completed'; ?>
    <div class="kanban-card" onclick="location.href='task_view.php?id=<?= $t['id'] ?>'">
      <div class="kanban-card__tags"><span class="badge <?= $pb ?>"><?= $pl ?></span><?php if($ov):?><span class="badge badge--red">Overdue</span><?php endif;?></div>
      <h5><?= e($t['title']) ?></h5>
      <div class="text-mute" style="font-size:11.5px"><?= e($t['project_name']) ?></div>
      <div class="kanban-card__meta"><span><?= fmt_date($t['deadline'],'M j') ?></span><?= avatar_html($t['assignee_name']??'Unassigned',null,'22') ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php elseif ($tasks): ?>
<div class="card card--padless">
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Due</th><th>Progress</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tasks as $t):
      [$sl,$sb]=status_label($t['status']); [$pl,$pb]=priority_label($t['priority']); $ov=is_overdue($t['deadline'])&&$t['status']!=='completed';
    ?>
      <tr data-row>
        <td><a class="title-link" href="task_view.php?id=<?= $t['id'] ?>"><?= e($t['title']) ?></a></td>
        <td class="text-mute"><?= e($t['project_name']) ?></td>
        <td>
          <select class="select" data-task-status="<?= (int)$t['id'] ?>" style="width:auto;padding:4px 26px 4px 10px;font-size:12px" <?= is_employee()&&(int)$t['assigned_to']!==$cu['id']?'disabled':'' ?>>
            <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','completed'=>'Completed'] as $k=>$v): ?><option value="<?= $k ?>" <?= $t['status']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
          </select>
        </td>
        <td><span class="badge <?= $pb ?>"><?= $pl ?></span></td>
        <td><?= $t['assignee_name']?avatar_html($t['assignee_name'],null,'24').'<span style="margin-left:8px">'.e(explode(' ',$t['assignee_name'])[0]).'</span>':'<span class="text-mute">—</span>' ?></td>
        <td class="<?= $ov?'text-danger':'text-mute' ?>" style="white-space:nowrap"><?= fmt_date($t['deadline'],'M j') ?><?= $ov?' ⚠':'' ?></td>
        <td style="min-width:90px"><div class="flex items-center gap-1"><div class="progress" style="flex:1"><div class="progress__bar <?= $t['progress']>=100?'progress__bar--green':'' ?>" style="width:<?= (int)$t['progress'] ?>%"></div></div><span class="text-mute" style="font-size:11px;width:30px"><?= (int)$t['progress'] ?>%</span></div></td>
        <td style="white-space:nowrap">
          <button class="btn btn--ghost btn--icon btn--sm" title="Edit" onclick="editTask(<?= (int)$t['id'] ?>)"><svg viewBox="0 0 24 24" class="ic"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
          <button class="btn btn--ghost btn--icon btn--sm" title="Delete" data-delete-url="api/task_delete.php" data-confirm-title="Delete task" data-confirm-msg="Delete this task?"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?= pagination_links($result['page'], $result['pages'], $base) ?>

<?php else: ?>
<div class="card"><div class="empty">
  <svg viewBox="0 0 24 24" class="ic"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-9 14l-4-4 1.4-1.4L10 14.2l6.6-6.6L18 9l-8 8z"/></svg>
  <h3>No tasks found</h3><p>Adjust your filters or create a new task.</p>
</div></div>
<?php endif; ?>

<?php
include __DIR__ . '/includes/_task_modal.php';
require __DIR__ . '/includes/footer.php';
?>
<script>
function goFilter(){const p=new URLSearchParams();const q=taskSearch.value.trim();
  ['status','priority','project','scope'].forEach(f=>{const el=document.getElementById('f'+f.charAt(0).toUpperCase()+f.slice(1));if(el&&el.value)p.set(f,el.value);});
  if(q)p.set('q',q);p.set('view','<?= $view ?>');location.href='tasks.php?'+p.toString();}
document.getElementById('taskSearch').addEventListener('keydown',e=>{if(e.key==='Enter')goFilter();});
['fStatus','fPriority','fProject','fScope'].forEach(id=>{const el=document.getElementById(id);if(el)el.addEventListener('change',goFilter);});

function editTask(id){
  fetch('api/task_get.php?id='+id,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}})
    .then(r=>r.json()).then(d=>{ if(d.success){ openTaskModal(id, d.task.project_id, d.task);} else toast(d.message,'error'); });
}
</script>
