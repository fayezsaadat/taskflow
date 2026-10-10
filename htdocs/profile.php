<?php
/** TaskFlow — User profile */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$pdo = db();
$activeNav = '';
$pageTitle = APP_NAME . ' — Profile';

$id = (int)($_GET['id'] ?? $cu['id']);
$isOwn = $id === (int)$cu['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { set_flash('error','User not found'); redirect('team.php'); }

$pageTitle = APP_NAME . ' — ' . $user['name'];

// stats
$s = $pdo->prepare("SELECT COUNT(*) total, SUM(status='completed') done, SUM(status<>'completed') opn FROM tasks WHERE assigned_to=?");
$s->execute([$id]); $stats = $s->fetch();
// projects count
$pc = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE user_id=?");
$pc->execute([$id]); $projCount = (int)$pc->fetchColumn();

// recent tasks
$rt = $pdo->prepare("SELECT t.*, p.name project_name FROM tasks t JOIN projects p ON p.id=t.project_id WHERE t.assigned_to=? ORDER BY t.updated_at DESC LIMIT 5");
$rt->execute([$id]);
$recent = $rt->fetchAll();

// activity
$la = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 6");
$la->execute([$id]);
$activity = $la->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <?php if(!$isOwn): ?><a href="team.php" class="text-mute" style="font-size:13px">← Team</a><?php endif; ?>
  </div>
  <?php if ($isOwn): ?><div class="page-head__actions"><a href="settings.php" class="btn btn--ghost btn--sm"><svg viewBox="0 0 24 24" class="ic"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Edit profile</a></div><?php endif; ?>
</div>

<div class="grid grid-2">
  <div>
    <!-- Profile header card -->
    <div class="card mb-3">
      <div class="card__body" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
        <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;flex-shrink:0"><?= avatar_html($user['name'],$user['avatar'],'80') ?></div>
        <div style="flex:1;min-width:200px">
          <div class="flex items-center gap-2" style="flex-wrap:wrap">
            <h1 style="margin:0;font-size:22px"><?= e($user['name']) ?></h1>
            <span class="badge <?= role_badge($user['role']) ?>"><?= role_label($user['role']) ?></span>
          </div>
          <div class="text-soft"><?= e($user['job_title'] ?: 'Team member') ?></div>
          <div class="text-mute" style="font-size:13px;margin-top:4px"><?= e($user['email']) ?><?= $user['phone']?' · '.e($user['phone']):'' ?></div>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stat-grid" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat"><div class="stat__label">Projects</div><div class="stat__value"><?= $projCount ?></div></div>
      <div class="stat"><div class="stat__label">Total Tasks</div><div class="stat__value"><?= (int)$stats['total'] ?></div></div>
      <div class="stat"><div class="stat__label">Completed</div><div class="stat__value"><?= (int)$stats['done'] ?></div></div>
      <div class="stat"><div class="stat__label">Open</div><div class="stat__value"><?= (int)$stats['opn'] ?></div></div>
    </div>

    <?php if ($user['bio']): ?><div class="card mb-3"><div class="card__header"><h3>About</h3></div><div class="card__body"><p style="color:var(--text-soft)"><?= e($user['bio']) ?></p></div></div><?php endif; ?>

    <!-- Recent tasks -->
    <div class="card">
      <div class="card__header"><h3>Recent Tasks</h3></div>
      <div class="card__body card__body--tight">
        <?php if(!$recent): ?><div class="text-mute text-center" style="padding:16px">No tasks assigned.</div><?php else: foreach($recent as $t): [$sl,$sb]=status_label($t['status']); ?>
        <a href="task_view.php?id=<?= $t['id'] ?>" style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
          <div><div style="font-weight:600;font-size:13.5px"><?= e($t['title']) ?></div><div class="text-mute" style="font-size:12px"><?= e($t['project_name']) ?></div></div>
          <span class="badge <?= $sb ?>"><?= $sl ?></span>
        </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="card mb-3">
      <div class="card__header"><h3>Details</h3></div>
      <div class="card__body">
        <div class="detail-meta"><span>Department</span><strong><?= e($user['department'] ?: '—') ?></strong></div>
        <div class="detail-meta"><span>Job title</span><strong><?= e($user['job_title'] ?: '—') ?></strong></div>
        <div class="detail-meta"><span>Email</span><strong><?= e($user['email']) ?></strong></div>
        <div class="detail-meta"><span>Phone</span><strong><?= e($user['phone'] ?: '—') ?></strong></div>
        <div class="detail-meta"><span>Status</span><strong><span class="badge <?= $user['status']==='active'?'badge--green':'badge--red' ?>"><?= ucfirst($user['status']) ?></span></strong></div>
        <div class="detail-meta"><span>Joined</span><strong><?= fmt_date($user['created_at']) ?></strong></div>
      </div>
    </div>

    <?php if($activity): ?>
    <div class="card">
      <div class="card__header"><h3>Recent Activity</h3></div>
      <div class="card__body card__body--tight">
        <div class="timeline">
        <?php foreach($activity as $l): ?>
          <div class="timeline__item"><div class="timeline__meta"><?= time_ago($l['created_at']) ?></div><div class="timeline__text"><?= e($l['description'] ?: $l['action']) ?></div></div>
        <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
