<?php
/** TaskFlow — Project detail (kanban + overview) */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$pdo = db();
$activeNav = 'projects';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.name owner_name FROM projects p JOIN users u ON u.id=p.created_by WHERE p.id=?");
$stmt->execute([$id]);
$project = $stmt->fetch();
if (!$project) { set_flash('error','Project not found'); redirect('projects.php'); }

$pageTitle = APP_NAME . ' — ' . $project['name'];

// tasks grouped by status
$taskStmt = $pdo->prepare("SELECT t.*, u.name assignee_name FROM tasks t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.project_id=? ORDER BY t.priority='urgent' DESC, t.priority='high' DESC, t.deadline IS NULL, t.deadline ASC");
$taskStmt->execute([$id]);
$allTasks = $taskStmt->fetchAll();
$byStatus = ['todo'=>[], 'in_progress'=>[], 'review'=>[], 'completed'=>[]];
foreach ($allTasks as $t) $byStatus[$t['status']][] = $t;

// stats
$taskCount = count($allTasks);
$doneCount = count($byStatus['completed']);
$autoProgress = $taskCount ? round($doneCount/$taskCount*100) : (int)$project['progress'];

// team
$teamStmt = $pdo->prepare("SELECT u.* FROM project_members pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=? ORDER BY u.name");
$teamStmt->execute([$id]);
$team = $teamStmt->fetchAll();

// assignable members for task modal
$members = $team ?: $pdo->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetchAll();

// project budget vs spent
$budgetPct = $project['budget'] > 0 ? min(100, round($project['spent']/$project['budget']*100)) : 0;

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <div class="flex items-center gap-2 mb-1">
      <a href="projects.php" class="text-mute" style="font-size:13px">← Projects</a>
    </div>
    <div class="flex items-center gap-2" style="flex-wrap:wrap">
      <h1 style="margin:0"><?= e($project['name']) ?></h1>
      <?php [$sl,$sb]=project_status_label($project['status']); echo '<span class="badge '.$sb.' badge--dot">'.$sl.'</span>'; ?>
      <?php [$pl,$pb]=priority_label($project['priority']); echo '<span class="badge '.$pb.'">'.$pl.' priority</span>'; ?>
    </div>
    <p class="text-soft mt-1"><?= e($project['description'] ?: 'No description provided.') ?></p>
  </div>
  <div class="page-head__actions">
    <?php if (is_manager()): ?>
    <button class="btn btn--ghost btn--sm" onclick="editProject(<?= $id ?>)"><svg viewBox="0 0 24 24" class="ic"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Edit</button>
    <button class="btn btn--sm" onclick="openTaskModal(0, <?= $id ?>)"><svg viewBox="0 0 24 24" class="ic"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg> Add Task</button>
    <?php endif; ?>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat"><div class="stat__label">Tasks</div><div class="stat__value"><?= $taskCount ?></div><div class="stat__trend"><strong class="text-success"><?= $doneCount ?></strong> done</div></div>
  <div class="stat"><div class="stat__label">Progress</div><div class="stat__value"><?= $autoProgress ?>%</div><div class="progress mt-1"><div class="progress__bar <?= $autoProgress>=100?'progress__bar--green':'' ?>" style="width:<?= $autoProgress ?>%"></div></div></div>
  <div class="stat"><div class="stat__label">Budget</div><div class="stat__value" style="font-size:22px"><?= money($project['budget']) ?></div><div class="stat__trend"><?= money($project['spent']) ?> spent (<?= $budgetPct ?>%)</div></div>
  <div class="stat"><div class="stat__label">Deadline</div><div class="stat__value" style="font-size:20px"><?= fmt_date($project['deadline'],'M j, Y') ?></div><div class="stat__trend"><?php
    $d=days_until($project['deadline']);
    if($d===null) echo 'No deadline set';
    elseif($d<0) echo '<strong class="is-down">'.abs($d).' days overdue</strong>';
    elseif($d===0) echo 'Due today';
    else echo '<strong>'.$d.' days left</strong>';
  ?></div></div>
</div>

<div class="tabs mb-3">
  <button class="tab is-active" onclick="switchTab(this,'tab-kanban')">Kanban</button>
  <button class="tab" onclick="switchTab(this,'tab-team')">Team (<?= count($team) ?>)</button>
  <button class="tab" onclick="switchTab(this,'tab-details')">Details</button>
</div>

<!-- Kanban -->
<div id="tab-kanban">
  <div class="grid grid-kanban">
    <?php foreach (['todo'=>['To Do','#94a3b8'],'in_progress'=>['In Progress','#3b82f6'],'review'=>['Review','#f59e0b'],'completed'=>['Completed','#10b981']] as $key=>[$label,$color]): ?>
    <div class="kanban-col">
      <div class="kanban-col__head">
        <h4><span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;display:inline-block"></span> <?= $label ?></h4>
        <span class="kanban-col__count"><?= count($byStatus[$key]) ?></span>
      </div>
      <?php foreach ($byStatus[$key] as $t): [$pl,$pb]=priority_label($t['priority']); $ov=is_overdue($t['deadline']) && $key!=='completed'; ?>
      <div class="kanban-card" onclick="location.href='task_view.php?id=<?= $t['id'] ?>'">
        <div class="kanban-card__tags">
          <span class="badge <?= $pb ?>"><?= $pl ?></span>
          <?php if ($ov): ?><span class="badge badge--red">Overdue</span><?php endif; ?>
        </div>
        <h5><?= e($t['title']) ?></h5>
        <?php if ($t['progress']>0 && $key!=='completed'): ?>
          <div class="progress" style="margin-bottom:6px"><div class="progress__bar" style="width:<?= (int)$t['progress'] ?>%;height:5px"></div></div>
        <?php endif; ?>
        <div class="kanban-card__meta">
          <span><?= fmt_date($t['deadline'],'M j') ?></span>
          <?= avatar_html($t['assignee_name'] ?? 'Unassigned', null, '22') ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Team -->
<div id="tab-team" class="hide">
  <div class="card card--padless">
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Member</th><th>Role</th><th>Department</th><th>Tasks</th></tr></thead>
      <tbody>
      <?php if(!$team): ?><tr><td colspan="4"><div class="empty">No team members assigned.</div></td></tr><?php else: foreach($team as $m):
        $tc=$pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=? AND assigned_to=?"); $tc->execute([$id,$m['id']]); $cnt=(int)$tc->fetchColumn();
      ?>
      <tr>
        <td><div class="flex items-center gap-2"><?= avatar_html($m['name'],$m['avatar'],36) ?><div><div style="font-weight:600"><?= e($m['name']) ?></div><div class="text-mute" style="font-size:12px"><?= e($m['email']) ?></div></div></div></td>
        <td><span class="badge <?= role_badge($m['role']) ?>"><?= role_label($m['role']) ?></span></td>
        <td class="text-mute"><?= e($m['department'] ?: '—') ?></td>
        <td><?= $cnt ?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </div>
</div>

<!-- Details -->
<div id="tab-details" class="hide">
  <div class="grid grid-2">
    <div class="card">
      <div class="card__header"><h3>Project Information</h3></div>
      <div class="card__body">
        <div class="detail-meta"><span>Owner: <strong><?= e($project['owner_name']) ?></strong></span></div>
        <div class="detail-meta"><span>Started: <strong><?= fmt_date($project['start_date']) ?></strong></span></div>
        <div class="detail-meta"><span>Deadline: <strong><?= fmt_date($project['deadline']) ?></strong></span></div>
        <div class="detail-meta"><span>Created: <strong><?= fmt_datetime($project['created_at']) ?></strong></span></div>
        <div class="divider"></div>
        <div class="detail-meta"><span>Budget: <strong><?= money($project['budget']) ?></strong></span></div>
        <div class="progress mb-1"><div class="progress__bar <?= $budgetPct>=100?'progress__bar--red':'' ?>" style="width:<?= $budgetPct ?>%"></div></div>
        <div class="text-mute" style="font-size:12px"><?= money($project['spent']) ?> of <?= money($project['budget']) ?> spent</div>
      </div>
    </div>
    <div class="card">
      <div class="card__header"><h3>All Tasks</h3></div>
      <div class="card__body card__body--tight">
        <?php if(!$allTasks):?><div class="empty">No tasks yet.</div><?php else:?>
        <div class="timeline">
        <?php foreach ($allTasks as $t): [$sl,$sb]=status_label($t['status']); ?>
          <div class="timeline__item" style="cursor:pointer" onclick="location.href='task_view.php?id=<?= $t['id'] ?>'">
            <div class="timeline__meta"><span class="badge <?= $sb ?>"><?= $sl ?></span> · <?= e($t['assignee_name'] ?? 'Unassigned') ?></div>
            <div class="timeline__text"><?= e($t['title']) ?></div>
          </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/includes/_project_modal.php';
include __DIR__ . '/includes/_task_modal.php';
require __DIR__ . '/includes/footer.php';
?>
<script>
function switchTab(btn, id){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('is-active'));
  btn.classList.add('is-active');
  ['tab-kanban','tab-team','tab-details'].forEach(t=>document.getElementById(t).classList.add('hide'));
  document.getElementById(id).classList.remove('hide');
}
function editProject(id){
  fetch('api/project_get.php?id='+id,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}})
    .then(r=>r.json()).then(d=>{ if(d.success){ fillProjectModal(d.project); openModal('projectModal'); } else toast(d.message,'error'); });
}
</script>
