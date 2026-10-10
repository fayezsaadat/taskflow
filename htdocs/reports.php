<?php
/** TaskFlow — Reports & CSV export */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$pdo = db();
$activeNav = 'reports';
$pageTitle = APP_NAME . ' — Reports';

$type = clean($_GET['type'] ?? 'overview');
$export = $_GET['export'] ?? '';

/* ---------- CSV export ---------- */
if ($export === 'csv') {
  $filename = 'taskflow_' . $type . '_' . date('Y-m-d') . '.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  $out = fopen('php://output','w');
  fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
  $csv = function($row) use ($out) { fputcsv($out, $row, ",", "\"", ""); };
  if ($type==='projects') {
    $csv(['Project','Status','Priority','Start','Deadline','Budget','Spent','Progress','Owner']);
    foreach ($pdo->query("SELECT p.*,u.name owner FROM projects p LEFT JOIN users u ON u.id=p.created_by ORDER BY p.name") as $r)
      $csv([$r['name'],$r['status'],$r['priority'],$r['start_date'],$r['deadline'],$r['budget'],$r['spent'],$r['progress'].'%',$r['owner']]);
  } elseif ($type==='tasks') {
    $csv(['Task','Project','Status','Priority','Assignee','Deadline','Progress']);
    $stmt=$pdo->query("SELECT t.title,p.name project,t.status,t.priority,u.name assignee,t.deadline,t.progress FROM tasks t JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.assigned_to ORDER BY p.name,t.title");
    foreach ($stmt as $r) $csv([$r['title'],$r['project'],$r['status'],$r['priority'],$r['assignee']?:'Unassigned',$r['deadline'],$r['progress'].'%']);
  } elseif ($type==='team') {
    $csv(['Name','Email','Role','Department','Job Title','Total Tasks','Open Tasks','Completed']);
    foreach ($pdo->query("SELECT * FROM users ORDER BY role='admin' DESC,name") as $u) {
      $s=$pdo->prepare("SELECT COUNT(*) total, SUM(status<>'completed') opn, SUM(status='completed') dn FROM tasks WHERE assigned_to=?");
      $s->execute([$u['id']]); $c=$s->fetch();
      $csv([$u['name'],$u['email'],$u['role'],$u['department'],$u['job_title'],$c['total'],$c['opn']?:0,$c['dn']?:0]);
    }
  } elseif ($type==='logs') {
    $csv(['Time','User','Action','Entity','Description','IP']);
    foreach ($pdo->query("SELECT l.*,u.name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC") as $l)
      $csv([$l['created_at'],$l['name']?:'System',$l['action'],$l['entity_type'],$l['description'],$l['ip_address']]);
  }
  fclose($out); exit;
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h1>Reports</h1><p>Insights, summaries &amp; exports</p></div>
  <div class="page-head__actions no-print">
    <button class="btn btn--ghost btn--sm" onclick="window.print()"><svg viewBox="0 0 24 24" class="ic"><path d="M19 8h-1V3H6v5H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zM8 5h8v3H8V5zm8 14H8v-4h8v4zm2-6.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg> Print</button>
  </div>
</div>

<div class="toolbar no-print">
  <a class="btn btn--sm <?= $type==='overview'?'btn--soft':'btn--ghost' ?>" href="reports.php?type=overview">Overview</a>
  <a class="btn btn--sm <?= $type==='projects'?'btn--soft':'btn--ghost' ?>" href="reports.php?type=projects">Projects</a>
  <a class="btn btn--sm <?= $type==='tasks'?'btn--soft':'btn--ghost' ?>" href="reports.php?type=tasks">Tasks</a>
  <a class="btn btn--sm <?= $type==='team'?'btn--soft':'btn--ghost' ?>" href="reports.php?type=team">Team</a>
  <div class="spacer"></div>
  <?php if ($type!=='overview'): ?>
  <a class="btn btn--sm" href="reports.php?type=<?= $type ?>&export=csv"><svg viewBox="0 0 24 24" class="ic"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Export CSV</a>
  <?php endif; ?>
</div>

<?php
/* ---------------- OVERVIEW ---------------- */
if ($type==='overview'):
  // KPIs
  $projTotal=(int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
  $projActive=(int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='active'")->fetchColumn();
  $taskTotal=(int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
  $taskDone=(int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status='completed'")->fetchColumn();
  $completionRate = $taskTotal?round($taskDone/$taskTotal*100):0;
  $budgetTotal=(float)$pdo->query("SELECT COALESCE(SUM(budget),0) FROM projects")->fetchColumn();
  $budgetSpent=(float)$pdo->query("SELECT COALESCE(SUM(spent),0) FROM projects")->fetchColumn();
  $overdue=(int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status<>'completed' AND deadline<CURDATE()")->fetchColumn();

  $statusDist=$pdo->query("SELECT status,COUNT(*) c FROM projects GROUP BY status")->fetchAll();
  $prioDist=$pdo->query("SELECT priority,COUNT(*) c FROM tasks GROUP BY priority")->fetchAll();
  $byDept=$pdo->query("SELECT u.department, COUNT(t.id) c FROM tasks t JOIN users u ON u.id=t.assigned_to GROUP BY u.department ORDER BY c DESC LIMIT 6")->fetchAll();
?>
<div class="stat-grid">
  <div class="stat"><div class="stat__icon stat__icon--indigo"><svg viewBox="0 0 24 24"><path d="M20 6h-4V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4z"/></svg></div><div class="stat__label">Projects</div><div class="stat__value"><?= $projTotal ?></div><div class="stat__trend"><strong><?= $projActive ?></strong> active</div></div>
  <div class="stat"><div class="stat__icon stat__icon--green"><svg viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></div><div class="stat__label">Completion Rate</div><div class="stat__value"><?= $completionRate ?>%</div><div class="stat__trend"><?= $taskDone ?>/<?= $taskTotal ?> tasks done</div></div>
  <div class="stat"><div class="stat__icon stat__icon--blue"><svg viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h9zm-9-2h10V8H12v8zm4-2.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg></div><div class="stat__label">Budget</div><div class="stat__value" style="font-size:22px"><?= money($budgetTotal) ?></div><div class="stat__trend"><?= money($budgetSpent) ?> spent</div></div>
  <div class="stat"><div class="stat__icon stat__icon--red"><svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg></div><div class="stat__label">Overdue Tasks</div><div class="stat__value"><?= $overdue ?></div><div class="stat__trend"><strong class="is-down">needs attention</strong></div></div>
</div>
<div class="grid grid-2">
  <div class="card"><div class="card__header"><h3>Projects by Status</h3></div><div class="card__body"><canvas id="rChart1" height="180"></canvas></div></div>
  <div class="card"><div class="card__header"><h3>Tasks by Priority</h3></div><div class="card__body"><canvas id="rChart2" height="180"></canvas></div></div>
</div>
<div class="card mt-3"><div class="card__header"><h3>Tasks per Department</h3></div><div class="card__body"><canvas id="rChart3" height="120"></canvas></div></div>

<?php
/* ---------------- PROJECTS ---------------- */
elseif ($type==='projects'):
  $rows=$pdo->query("SELECT p.*,u.name owner FROM projects p LEFT JOIN users u ON u.id=p.created_by ORDER BY p.name")->fetchAll();
?>
<div class="card card--padless">
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Project</th><th>Status</th><th>Priority</th><th>Deadline</th><th>Budget</th><th>Spent</th><th>Progress</th><th>Owner</th></tr></thead>
    <tbody>
    <?php foreach($rows as $p): [$sl,$sb]=project_status_label($p['status']); [$pl,$pb]=priority_label($p['priority']); ?>
    <tr><td><a class="title-link" href="project_view.php?id=<?= $p['id'] ?>"><?= e($p['name']) ?></a></td><td><span class="badge <?= $sb ?>"><?= $sl ?></span></td><td><span class="badge <?= $pb ?>"><?= $pl ?></span></td><td class="text-mute"><?= fmt_date($p['deadline']) ?></td><td><?= money($p['budget']) ?></td><td><?= money($p['spent']) ?></td><td style="min-width:110px"><div class="flex items-center gap-1"><div class="progress" style="flex:1"><div class="progress__bar" style="width:<?= (int)$p['progress'] ?>%"></div></div><span class="text-mute" style="font-size:11px"><?= (int)$p['progress'] ?>%</span></div></td><td class="text-mute"><?= e($p['owner']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php elseif ($type==='tasks'):
  $rows=$pdo->query("SELECT t.title,p.name project,t.status,t.priority,u.name assignee,t.deadline,t.progress FROM tasks t JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.assigned_to ORDER BY p.name,t.title")->fetchAll();
?>
<div class="card card--padless"><div class="table-wrap"><table class="data">
  <thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Deadline</th><th>Progress</th></tr></thead>
  <tbody>
  <?php foreach($rows as $t): [$sl,$sb]=status_label($t['status']); [$pl,$pb]=priority_label($t['priority']); ?>
  <tr><td><a class="title-link" href="tasks.php"><?= e($t['title']) ?></a></td><td class="text-mute"><?= e($t['project']) ?></td><td><span class="badge <?= $sb ?>"><?= $sl ?></span></td><td><span class="badge <?= $pb ?>"><?= $pl ?></span></td><td><?= e($t['assignee'] ?: '—') ?></td><td class="text-mute"><?= fmt_date($t['deadline']) ?></td><td style="min-width:110px"><div class="flex items-center gap-1"><div class="progress" style="flex:1"><div class="progress__bar" style="width:<?= (int)$t['progress'] ?>%"></div></div><span class="text-mute" style="font-size:11px"><?= (int)$t['progress'] ?>%</span></div></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>

<?php elseif ($type==='team'):
  $rows=$pdo->query("SELECT * FROM users ORDER BY role='admin' DESC,name")->fetchAll();
?>
<div class="card card--padless"><div class="table-wrap"><table class="data">
  <thead><tr><th>Member</th><th>Role</th><th>Department</th><th>Total</th><th>Open</th><th>Completed</th><th>Completion</th></tr></thead>
  <tbody>
  <?php foreach($rows as $u):
    $s=$pdo->prepare("SELECT COUNT(*) total,SUM(status<>'completed') opn,SUM(status='completed') dn FROM tasks WHERE assigned_to=?");
    $s->execute([$u['id']]); $c=$s->fetch();
    $rate=(int)$c['total']?round(((int)$c['dn']?:0)/$c['total']*100):0;
  ?>
  <tr><td><div class="flex items-center gap-1"><?= avatar_html($u['name'],$u['avatar'],'32') ?><div><div style="font-weight:600"><?= e($u['name']) ?></div><div class="text-mute" style="font-size:12px"><?= e($u['email']) ?></div></div></div></td>
  <td><span class="badge <?= role_badge($u['role']) ?>"><?= role_label($u['role']) ?></span></td>
  <td class="text-mute"><?= e($u['department'] ?: '—') ?></td>
  <td><?= $c['total']?:0 ?></td><td><?= $c['opn']?:0 ?></td><td><?= $c['dn']?:0 ?></td>
  <td style="min-width:110px"><div class="flex items-center gap-1"><div class="progress" style="flex:1"><div class="progress__bar <?= $rate>=100?'progress__bar--green':'' ?>" style="width:<?= $rate ?>%"></div></div><span class="text-mute" style="font-size:11px"><?= $rate ?>%</span></div></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script>
const fontColor=getComputedStyle(document.documentElement).getPropertyValue('--text-mute').trim();
const gridColor=getComputedStyle(document.documentElement).getPropertyValue('--border').trim();
Chart.defaults.color=fontColor;
<?php if ($type==='overview'): ?>
new Chart(document.getElementById('rChart1'),{type:'doughnut',data:{labels:<?= json_encode(array_map(fn($s)=>$s['status'],$statusDist)) ?>,datasets:[{data:<?= json_encode(array_map(fn($s)=>(int)$s['c'],$statusDist)) ?>,backgroundColor:['#94a3b8','#10b981','#f59e0b','#3b82f6','#ef4444'],borderWidth:0}]},options:{maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'right',labels:{boxWidth:10,padding:10}}}}});
new Chart(document.getElementById('rChart2'),{type:'bar',data:{labels:<?= json_encode(array_map(fn($s)=>$s['priority'],$prioDist)) ?>,datasets:[{data:<?= json_encode(array_map(fn($s)=>(int)$s['c'],$prioDist)) ?>,backgroundColor:['#94a3b8','#3b82f6','#f59e0b','#ef4444'],borderRadius:6}]},options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0},grid:{color:gridColor}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('rChart3'),{type:'bar',data:{labels:<?= json_encode(array_map(fn($d)=>$d['department']?:'Unassigned',$byDept)) ?>,datasets:[{data:<?= json_encode(array_map(fn($d)=>(int)$d['c'],$byDept)) ?>,backgroundColor:'#6366f1',borderRadius:6,barThickness:30}]},options:{indexAxis:'y',maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{precision:0},grid:{color:gridColor}},y:{grid:{display:false}}}}});
<?php endif; ?>
</script>
