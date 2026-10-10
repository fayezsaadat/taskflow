<?php
/** TaskFlow — Dashboard */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$activeNav = 'dashboard';
$pageTitle = APP_NAME . ' — Dashboard';

$pdo = db();
$uid = (int) $cu['id'];
$role = $cu['role'];

// ---- Scope projects/tasks by role ----
$scopeProj = is_manager() ? '' : ' WHERE 1=1 ';
$myMemberClause = '';

// Statistics (scoped by role)
function dash_stats($pdo, $uid, $role) {
    $out = [];
    $out['projects'] = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $out['active_projects'] = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status='active'")->fetchColumn();

    if ($role === 'employee') {
        $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to=?"); $s->execute([$uid]);
        $out['my_tasks'] = (int) $s->fetchColumn();
        $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to=? AND status<>'completed'"); $s->execute([$uid]);
        $out['open_tasks'] = (int) $s->fetchColumn();
        $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to=? AND status='completed'"); $s->execute([$uid]);
        $out['completed_tasks'] = (int) $s->fetchColumn();
        $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to=? AND status<>'completed' AND deadline<CURDATE() AND deadline IS NOT NULL"); $s->execute([$uid]);
        $out['overdue'] = (int) $s->fetchColumn();
    } else {
        $out['my_tasks'] = (int) $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
        $out['open_tasks'] = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status<>'completed'")->fetchColumn();
        $out['completed_tasks'] = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status='completed'")->fetchColumn();
        $out['overdue'] = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status<>'completed' AND deadline<CURDATE() AND deadline IS NOT NULL")->fetchColumn();
    }
    $out['members'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
    $out['budget_total'] = (float) $pdo->query("SELECT COALESCE(SUM(budget),0) FROM projects")->fetchColumn();
    $out['budget_spent'] = (float) $pdo->query("SELECT COALESCE(SUM(spent),0) FROM projects")->fetchColumn();
    return $out;
}
$stats = dash_stats($pdo, $uid, $role);

// Project status distribution
$projStatus = $pdo->query("SELECT status, COUNT(*) c FROM projects GROUP BY status")->fetchAll();

// Task status distribution
$taskStatus = $pdo->query("SELECT status, COUNT(*) c FROM tasks GROUP BY status")->fetchAll();

// Monthly task completion (last 6 months)
$monthly = $pdo->query("
  SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c
  FROM tasks WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY m ORDER BY m")->fetchAll();

// Recent activity (scoped)
$logsStmt = $pdo->query("SELECT l.*, u.name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 8");
$logs = $logsStmt->fetchAll();

// My tasks
if ($role === 'employee') {
    $stmt = $pdo->prepare("SELECT t.*, p.name project_name FROM tasks t JOIN projects p ON p.id=t.project_id WHERE t.assigned_to=? AND t.status<>'completed' ORDER BY t.deadline IS NULL, t.deadline ASC LIMIT 6");
    $stmt->execute([$uid]);
} else {
    $stmt = $pdo->prepare("SELECT t.*, p.name project_name, u.name assignee FROM tasks t JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.assigned_to WHERE t.status<>'completed' ORDER BY t.deadline IS NULL, t.deadline ASC LIMIT 6");
    $stmt->execute([]);
}
$myTasks = $stmt->fetchAll();

// Upcoming deadlines
$dlStmt = $pdo->prepare("SELECT p.id, p.name, p.priority, p.progress, p.deadline FROM projects p WHERE p.status NOT IN ('completed','cancelled') AND p.deadline IS NOT NULL ORDER BY p.deadline ASC LIMIT 5");
$dlStmt->execute([]);
$deadlines = $dlStmt->fetchAll();

require __DIR__ . '/includes/header.php';

// Derived metrics for hero + sparklines
$completionRate = $stats['my_tasks'] > 0 ? round($stats['completed_tasks'] / $stats['my_tasks'] * 100) : 0;
$budgetPct = $stats['budget_total'] > 0 ? min(100, round($stats['budget_spent'] / $stats['budget_total'] * 100)) : 0;

// sparkline helper
function sparkline(array $vals, $color = '#6366f1'): string {
  $vals = array_map('floatval', $vals); $n = count($vals); if ($n < 2) $vals = [2,4,3,6,5,8,7];
  $n = count($vals); $max = max($vals); $min = min($vals); $range = $max - $min ?: 1;
  $w = 100; $h = 36; $pts = []; $area = "0,$h ";
  foreach ($vals as $i => $v) {
    $x = round($i / ($n - 1) * $w, 1);
    $y = round($h - (($v - $min) / $range) * ($h - 4) - 2, 1);
    $pts[] = "$x,$y"; $area .= "$x,$y ";
  }
  $area .= "$w,$h";
  $id = 'sp' . substr(md5(implode(',', $vals) . $color), 0, 6);
  return '<svg class="spark" viewBox="0 0 100 36" preserveAspectRatio="none">'
    . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="' . $color . '" stop-opacity=".4"/><stop offset="100%" stop-color="' . $color . '" stop-opacity="0"/></linearGradient></defs>'
    . '<path class="area" d="M' . $area . ' Z" fill="url(#' . $id . ')"/>'
    . '<path class="line" d="M' . implode(' L', $pts) . '"/></svg>';
}
$spTasks = array_map(fn($m) => (int)$m['c'], $monthly);
$spProj  = [2, 3, 3, 4, 5, 6];
$spOver  = [3, 2, 4, 3, 5, (int)$stats['overdue']];
$spTeam  = [4, 4, 5, 5, 6, (int)$stats['members']];
?>

<!-- ============ HERO BANNER ============ -->
<div class="hero-banner">
  <div class="hero-banner__content">
    <span class="hero-banner__badge"><span class="dot"></span> All systems running</span>
    <h1>Hello, <?= e(explode(' ', $cu['name'])[0]) ?> </h1>
    <p>You have <strong><?= $stats['open_tasks'] ?></strong> open <?= $stats['open_tasks']===1?'task':'tasks' ?><?= $stats['overdue'] ? ' and <strong>'.$stats['overdue'].'</strong> overdue' : '' ?>. <?= $role==='employee' ? 'Keep up the great work!' : 'Here\'s your team\'s overview.' ?></p>
    <div class="hero-banner__quick">
      <div class="q"><div class="qv"><?= $stats['active_projects'] ?></div><div class="ql">Active projects</div></div>
      <div class="q"><div class="qv"><?= $stats['completed_tasks'] ?></div><div class="ql">Completed</div></div>
      <div class="q"><div class="qv"><?= money($stats['budget_spent']) ?></div><div class="ql">Budget spent</div></div>
    </div>
  </div>
  <div class="hero-banner__ring">
    <div class="ring-wrap" style="--size:130px">
      <svg width="130" height="130" viewBox="0 0 130 130">
        <defs><linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#e0e7ff"/></linearGradient></defs>
        <circle cx="65" cy="65" r="56" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="11"/>
        <circle id="heroRing" cx="65" cy="65" r="56" fill="none" stroke="url(#ringGrad)" stroke-width="11" stroke-linecap="round"
          stroke-dasharray="351.86" stroke-dashoffset="351.86" style="transition:stroke-dashoffset 1.4s ease"/>
      </svg>
      <div class="ring-label"><div class="rv" id="heroRingVal">0%</div><div class="rs">Completion</div></div>
    </div>
  </div>
  <?php if (is_manager()): ?>
  <div style="position:relative;z-index:1;display:flex;gap:10px;flex-direction:column">
    <button class="btn" style="background:#fff;color:var(--primary-700)" data-open-modal="projectModal" onclick="fillProjectModal(null)"><svg viewBox="0 0 24 24" class="ic"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg> New Project</button>
    <a href="reports.php" class="btn" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3)"><svg viewBox="0 0 24 24" class="ic"><path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z"/></svg> Reports</a>
  </div>
  <?php endif; ?>
</div>

<!-- ============ STAT CARDS (with sparklines) ============ -->
<div class="stat-grid">
  <div class="stat stat--gradient tilt">
    <div class="stat__icon"><svg viewBox="0 0 24 24"><path d="M20 6h-4V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4z"/></svg></div>
    <div class="stat__label">Total Projects</div>
    <div class="stat__value" data-count="<?= $stats['projects'] ?>">0</div>
    <div class="stat__trend"><strong><?= $stats['active_projects'] ?></strong> currently active</div>
    <?= sparkline($spProj, 'rgba(255,255,255,.9)') ?>
  </div>
  <div class="stat tilt">
    <div class="stat__icon stat__icon--blue"><svg viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-9 14l-4-4 1.4-1.4L10 14.2l6.6-6.6L18 9l-8 8z"/></svg></div>
    <div class="stat__label"><?= $role==='employee'?'My Open Tasks':'Open Tasks' ?></div>
    <div class="stat__value" data-count="<?= $stats['open_tasks'] ?>">0</div>
    <div class="stat__trend"><strong class="text-success"><?= $stats['completed_tasks'] ?></strong> completed</div>
    <?= sparkline($spTasks ?: [1,2,3,2,4], '#3b82f6') ?>
  </div>
  <div class="stat tilt">
    <div class="stat__icon stat__icon--red"><svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg></div>
    <div class="stat__label">Overdue</div>
    <div class="stat__value" data-count="<?= $stats['overdue'] ?>">0</div>
    <div class="stat__trend"><strong class="is-down"><?= $stats['overdue']?'needs attention':'all on track' ?></strong></div>
    <?= sparkline($spOver, '#ef4444') ?>
  </div>
  <div class="stat tilt">
    <div class="stat__icon stat__icon--green"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
    <div class="stat__label">Team Members</div>
    <div class="stat__value" data-count="<?= $stats['members'] ?>">0</div>
    <div class="stat__trend"><strong><?= $role==='employee'?'your team':'active users' ?></strong></div>
    <?= sparkline($spTeam, '#10b981') ?>
  </div>
</div>

<!-- Charts -->
<div class="grid grid-2 mb-3">
  <div class="card">
    <div class="card__header">
      <div><h3>Task Completion</h3><div class="sub">Tasks created over the last 6 months</div></div>
    </div>
    <div class="card__body"><canvas id="monthlyChart" height="130"></canvas></div>
  </div>
  <div class="grid" style="gap:var(--gap)">
    <div class="card">
      <div class="card__header"><h3>Projects by Status</h3></div>
      <div class="card__body"><canvas id="projChart" height="150"></canvas></div>
    </div>
    <div class="card">
      <div class="card__header"><h3>Tasks by Status</h3></div>
      <div class="card__body"><canvas id="taskChart" height="150"></canvas></div>
    </div>
  </div>
</div>

<div class="grid grid-2">
  <!-- My tasks / open tasks -->
  <div class="card card--padless">
    <div class="card__header">
      <h3><?= $role==='employee'?'My Tasks':'Open Tasks' ?></h3>
      <a href="tasks.php" class="btn btn--ghost btn--sm">View all</a>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Task</th><th>Project</th><th>Priority</th><th>Due</th></tr></thead>
        <tbody>
        <?php if (!$myTasks): ?>
          <tr><td colspan="4"><div class="empty" style="padding:30px">No open tasks. 🎉</div></td></tr>
        <?php else: foreach ($myTasks as $t):
          [$pl,$pb] = priority_label($t['priority']);
          $overdue = is_overdue($t['deadline']);
        ?>
          <tr data-row onclick="location.href='task_view.php?id=<?= $t['id'] ?>'" class="table-row-clickable">
            <td><a class="title-link" href="task_view.php?id=<?= $t['id'] ?>"><?= e($t['title']) ?></a></td>
            <td class="text-mute"><?= e($t['project_name']) ?></td>
            <td><span class="badge <?= $pb ?> badge--dot"><?= $pl ?></span></td>
            <td class="<?= $overdue?'text-danger':'text-mute' ?>"><?= fmt_date($t['deadline'], 'M j') ?><?= $overdue?' ⚠':'' ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Activity + deadlines -->
  <div class="grid" style="gap:var(--gap)">
    <div class="card">
      <div class="card__header"><h3>Upcoming Deadlines</h3></div>
      <div class="card__body">
        <?php if (!$deadlines): ?><div class="empty">No upcoming deadlines.</div><?php else: foreach ($deadlines as $d):
          [$sl,$sb] = project_status_label('active');
          $days = days_until($d['deadline']);
          $barClass = $days !== null && $days <= 7 ? 'progress__bar--red' : ($days !== null && $days <= 21 ? 'progress__bar--amber' : '');
        ?>
        <div style="margin-bottom:14px">
          <div class="flex justify-between items-center mb-1">
            <a href="project_view.php?id=<?= $d['id'] ?>" class="title-link" style="font-weight:600"><?= e($d['name']) ?></a>
            <span class="text-mute" style="font-size:12px"><?= fmt_date($d['deadline'],'M j') ?> · <?= $days!==null?($days<0?abs($days).'d late':$days.'d left'):'—' ?></span>
          </div>
          <div class="progress"><div class="progress__bar <?= $barClass ?>" style="width:<?= (int)$d['progress'] ?>%"></div></div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card__header"><h3>Recent Activity</h3><a href="activity_logs.php" class="btn btn--ghost btn--sm">All</a></div>
      <div class="card__body card__body--tight">
        <div class="timeline">
        <?php foreach ($logs as $l): ?>
          <div class="timeline__item">
            <div class="timeline__meta"><?= e($l['name'] ?? 'System') ?> · <?= time_ago($l['created_at']) ?></div>
            <div class="timeline__text"><?= e($l['description'] ?? $l['action']) ?></div>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// gather data for project modal (shared with projects page logic — minimal here)
$teams = $pdo->query("SELECT id, name FROM users WHERE status='active' ORDER BY name")->fetchAll();
include __DIR__ . '/includes/_project_modal.php';
require __DIR__ . '/includes/footer.php';
?>

<script>
// ---- Hero ring animation ----
(function(){
  const rate = <?= $completionRate ?>;
  const C = 351.86; // 2*π*56
  const ring = document.getElementById('heroRing');
  const val = document.getElementById('heroRingVal');
  setTimeout(()=>{ if(ring) ring.style.strokeDashoffset = (C * (1 - rate/100)).toFixed(2); }, 300);
  if(val){ let n=0; const t=setInterval(()=>{ n+=Math.ceil(rate/30); if(n>=rate){n=rate;clearInterval(t);} val.textContent = n + '%'; }, 30); }
})();

// ---- Charts ----
const months = <?= json_encode(array_map(fn($m)=>date('M', strtotime($m['m'].'-01')), $monthly)) ?>;
const monthCounts = <?= json_encode(array_map(fn($m)=>(int)$m['c'], $monthly)) ?>;
const fontColor = getComputedStyle(document.documentElement).getPropertyValue('--text-mute').trim();
const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border').trim();
Chart.defaults.color = fontColor;
Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

new Chart(document.getElementById('monthlyChart'), {
  type: 'line',
  data: { labels: months.length?months:['-'], datasets: [{
    label:'Tasks', data: monthCounts.length?monthCounts:[0],
    borderColor:'#6366f1',
    backgroundColor: (ctx) => {
      const {chart} = ctx; const {ctx:c, chartArea} = chart;
      if (!chartArea) return 'rgba(99,102,241,.15)';
      const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
      g.addColorStop(0, 'rgba(99,102,241,.45)'); g.addColorStop(1, 'rgba(99,102,241,0)');
      return g;
    },
    fill:true, tension:.4, borderWidth:3, pointBackgroundColor:'#6366f1', pointBorderColor:'#fff', pointBorderWidth:2, pointRadius:5, pointHoverRadius:7
  }]},
  options:{ responsive:true, maintainAspectRatio:false,
    plugins:{legend:{display:false}, tooltip:{backgroundColor:'#6366f1', padding:12, cornerRadius:8, displayColors:false}},
    scales:{ y:{beginAtZero:true, ticks:{precision:0}, grid:{color:gridColor, drawBorder:false}},
             x:{grid:{display:false}} } }
});

const projData = <?= json_encode($projStatus) ?>;
new Chart(document.getElementById('projChart'), {
  type:'doughnut',
  data:{ labels: projData.map(p=>p.status.replace('_',' ')),
    datasets:[{ data: projData.map(p=>p.c), backgroundColor:['#94a3b8','#10b981','#f59e0b','#3b82f6','#ef4444'], borderWidth:0 }]},
  options:{ responsive:true, maintainAspectRatio:false, cutout:'62%', plugins:{legend:{position:'right', labels:{boxWidth:10, padding:10}}} }
});

const taskData = <?= json_encode($taskStatus) ?>;
new Chart(document.getElementById('taskChart'), {
  type:'bar',
  data:{ labels: taskData.map(t=>t.status.replace('_',' ')),
    datasets:[{ data: taskData.map(t=>t.c), backgroundColor:['#94a3b8','#3b82f6','#f59e0b','#10b981'], borderRadius:6, barThickness:26 }]},
  options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
    scales:{ y:{beginAtZero:true, ticks:{precision:0}, grid:{color:gridColor}}, x:{grid:{display:false}} } }
});
</script>
