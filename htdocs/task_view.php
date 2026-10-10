<?php
/** TaskFlow — Task detail (comments, attachments, history) */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$pdo = db();
$activeNav = 'tasks';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT t.*, p.name project_name, p.id project_id, u.name assignee_name, u.avatar assignee_avatar, c.name creator_name FROM tasks t JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.assigned_to LEFT JOIN users c ON c.id=t.created_by WHERE t.id=?");
$stmt->execute([$id]);
$task = $stmt->fetch();
if (!$task) { set_flash('error','Task not found'); redirect('tasks.php'); }

$pageTitle = APP_NAME . ' — ' . $task['title'];
[$sl,$sb] = status_label($task['status']);
[$pl,$pb] = priority_label($task['priority']);
$ov = is_overdue($task['deadline']) && $task['status']!=='completed';

// comments
$cStmt = $pdo->prepare("SELECT cm.*, u.name, u.avatar FROM comments cm JOIN users u ON u.id=cm.user_id WHERE cm.task_id=? ORDER BY cm.created_at ASC");
$cStmt->execute([$id]);
$comments = $cStmt->fetchAll();

// attachments
$aStmt = $pdo->prepare("SELECT a.*, u.name FROM attachments a JOIN users u ON u.id=a.uploaded_by WHERE a.task_id=? ORDER BY a.created_at DESC");
$aStmt->execute([$id]);
$attachments = $aStmt->fetchAll();

$projects = $pdo->query("SELECT id,name FROM projects ORDER BY name")->fetchAll();
$members  = $pdo->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetchAll();
$canEdit = is_manager() || (int)$task['assigned_to'] === (int)$cu['id'];

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <a href="project_view.php?id=<?= $task['project_id'] ?>" class="text-mute" style="font-size:13px">← <?= e($task['project_name']) ?></a>
    <div class="flex items-center gap-2" style="flex-wrap:wrap;margin-top:4px">
      <h1 style="margin:0;font-size:20px"><?= e($task['title']) ?></h1>
      <span class="badge <?= $sb ?> badge--dot"><?= $sl ?></span>
      <span class="badge <?= $pb ?>"><?= $pl ?></span>
      <?php if ($ov): ?><span class="badge badge--red">Overdue</span><?php endif; ?>
    </div>
  </div>
  <div class="page-head__actions">
    <?php if ($canEdit): ?>
    <button class="btn btn--ghost btn--sm" onclick="openTaskModal(<?= $id ?>, <?= (int)$task['project_id'] ?>, <?= htmlspecialchars(json_encode(['title'=>$task['title'],'description'=>$task['description'],'project_id'=>$task['project_id'],'assigned_to'=>$task['assigned_to']?:0,'status'=>$task['status'],'priority'=>$task['priority'],'deadline'=>$task['deadline'],'estimated_hours'=>$task['estimated_hours'],'progress'=>$task['progress']]), ENT_QUOTES) ?>)"><svg viewBox="0 0 24 24" class="ic"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Edit</button>
    <?php endif; ?>
    <?php if (is_manager() || (int)$task['created_by']===$cu['id']): ?>
    <button class="btn btn--danger btn--sm" data-delete-url="api/task_delete.php" data-confirm-title="Delete task" data-confirm-msg="Delete this task permanently?"><input type="hidden" name="id" value="<?= $id ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-2">
  <!-- Left: description + comments -->
  <div>
    <?php if ($task['description']): ?>
    <div class="card mb-3"><div class="card__header"><h3>Description</h3></div><div class="card__body"><p style="white-space:pre-wrap;color:var(--text-soft)"><?= e($task['description']) ?></p></div></div>
    <?php endif; ?>

    <!-- Attachments -->
    <div class="card mb-3">
      <div class="card__header"><h3>Attachments (<?= count($attachments) ?>)</h3>
        <label class="btn btn--soft btn--sm" style="cursor:pointer">
          <svg viewBox="0 0 24 24" class="ic"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg> Upload
          <input type="file" id="attInput" style="display:none" onchange="uploadAttachment()">
        </label>
      </div>
      <div class="card__body">
        <?php if (!$attachments): ?><div class="text-mute" style="font-size:13px;padding:10px 0">No files attached yet.</div><?php else: foreach ($attachments as $a):
          $ext=strtolower(pathinfo($a['file_name'],PATHINFO_EXTENSION)); ?>
          <div class="att">
            <div class="att__icon"><svg viewBox="0 0 24 24" class="ic"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></div>
            <div style="flex:1;min-width:0">
              <div class="att__name"><a href="<?= e($a['file_path']) ?>" target="_blank" style="color:var(--text)"><?= e($a['file_name']) ?></a></div>
              <div class="att__meta"><?= file_size_label((int)$a['file_size']) ?> · <?= e($a['name']) ?> · <?= time_ago($a['created_at']) ?></div>
            </div>
            <a href="<?= e($a['file_path']) ?>" download class="btn btn--ghost btn--icon btn--sm" title="Download"><svg viewBox="0 0 24 24" class="ic"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg></a>
            <?php if (is_manager()||(int)$a['uploaded_by']===$cu['id']): ?>
            <button class="btn btn--ghost btn--icon btn--sm" title="Remove" data-delete-url="api/attachment_delete.php" data-confirm-title="Remove file" data-confirm-msg="Remove this attachment?"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
            <?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Comments -->
    <div class="card">
      <div class="card__header"><h3>Comments (<?= count($comments) ?>)</h3></div>
      <div class="card__body">
        <form id="commentForm" data-ajax action="api/comment_save.php" style="margin-bottom:16px">
          <input type="hidden" name="task_id" value="<?= $id ?>">
          <div class="flex gap-2">
            <?= avatar_html($cu['name'], $cu['avatar'], '36') ?>
            <div style="flex:1"><textarea name="comment" class="textarea" placeholder="Write a comment…" required style="min-height:60px"></textarea></div>
          </div>
          <div class="form-actions" style="margin-top:8px"><button type="submit" class="btn btn--sm">Post comment</button></div>
        </form>

        <?php foreach ($comments as $c): ?>
          <div class="comment" id="comment-<?= $c['id'] ?>">
            <?= avatar_html($c['name'], $c['avatar'], '36') ?>
            <div class="comment__body">
              <div class="comment__head">
                <span class="comment__author"><?= e($c['name']) ?></span>
                <span class="comment__time"><?= time_ago($c['created_at']) ?></span>
                <?php if (is_manager()||(int)$c['user_id']===$cu['id']): ?>
                <button class="btn btn--ghost btn--icon btn--sm" style="margin-left:auto;width:26px;height:26px" data-delete-url="api/comment_delete.php" data-confirm-title="Delete comment" data-confirm-msg="Delete this comment?" data-ok-text="Delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><svg viewBox="0 0 24 24" class="ic"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
                <?php endif; ?>
              </div>
              <div class="comment__text" style="white-space:pre-wrap"><?= e($c['comment']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$comments): ?><div class="text-mute text-center" style="padding:14px">No comments yet. Start the conversation.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: sidebar details -->
  <div>
    <div class="card mb-3">
      <div class="card__header"><h3>Details</h3></div>
      <div class="card__body">
        <div class="field">
          <label>Status</label>
          <select class="select" data-task-status="<?= $id ?>" <?= !$canEdit?'disabled':'' ?>>
            <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','completed'=>'Completed'] as $k=>$v): ?><option value="<?= $k ?>" <?= $task['status']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Progress</label>
          <div class="flex items-center gap-2"><div class="progress" style="flex:1"><div class="progress__bar <?= (int)$task['progress']>=100?'progress__bar--green':'' ?>" style="width:<?= (int)$task['progress'] ?>%"></div></div><strong><?= (int)$task['progress'] ?>%</strong></div>
        </div>
        <div class="divider"></div>
        <div class="detail-meta"><span>Assignee</span><strong><?= $task['assignee_name'] ? e($task['assignee_name']) : '<span class="text-mute">Unassigned</span>' ?></strong></div>
        <div class="detail-meta"><span>Created by</span><strong><?= e($task['creator_name']) ?></strong></div>
        <div class="detail-meta"><span>Priority</span><strong><span class="badge <?= $pb ?>"><?= $pl ?></span></strong></div>
        <div class="detail-meta"><span>Deadline</span><strong class="<?= $ov?'text-danger':'' ?>"><?= fmt_date($task['deadline']) ?><?= $ov?' (overdue)':'' ?></strong></div>
        <div class="detail-meta"><span>Estimated</span><strong><?= (float)$task['estimated_hours'] ?>h</strong></div>
        <div class="detail-meta"><span>Created</span><strong><?= time_ago($task['created_at']) ?></strong></div>
      </div>
    </div>

    <?php
    // sibling tasks in same project
    $sib = $pdo->prepare("SELECT id,title,status FROM tasks WHERE project_id=? AND id<>? ORDER BY status='completed', deadline IS NULL, deadline ASC LIMIT 6");
    $sib->execute([$task['project_id'],$id]);
    $siblings = $sib->fetchAll();
    if ($siblings): ?>
    <div class="card">
      <div class="card__header"><h3>More in this project</h3></div>
      <div class="card__body card__body--tight">
        <?php foreach ($siblings as $s): [$sl2,$sb2]=status_label($s['status']); ?>
        <a href="task_view.php?id=<?= $s['id'] ?>" style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
          <span style="font-size:13px;font-weight:500"><?= e($s['title']) ?></span>
          <span class="badge <?= $sb2 ?>" style="font-size:10px"><?= $sl2 ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
include __DIR__ . '/includes/_task_modal.php';
require __DIR__ . '/includes/footer.php';
?>
<script>
function uploadAttachment(){
  const input=document.getElementById('attInput');
  if(!input.files.length) return;
  const fd=new FormData();
  fd.append('task_id','<?= $id ?>');
  fd.append('file',input.files[0]);
  fd.append('csrf_token',window.CSRF_TOKEN);
  toast('Uploading…','info');
  fetch('api/attachment_upload.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(res=>{
      if(res.success){ toast(res.message,'success'); setTimeout(()=>location.reload(),600); }
      else toast(res.message||'Upload failed','error');
    }).catch(()=>toast('Network error','error'));
}
</script>
