<?php
/** Shared Task create/edit modal.
 *  Expects $members (users assignable) to be available; falls back to all active users.
 *  Expects $projects (list of projects) for the dropdown; falls back to all. */
$pdo = db();
if (!isset($members))  $members  = $pdo->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetchAll();
if (!isset($projects)) $projects = $pdo->query("SELECT id,name FROM projects ORDER BY name")->fetchAll();
?>
<div class="modal-overlay" id="taskModal">
  <div class="modal modal--lg">
    <div class="modal__header">
      <h3 id="taskModalTitle">New Task</h3>
      <button class="modal__close" data-close-modal>&times;</button>
    </div>
    <div class="modal__body">
      <form id="taskForm" data-ajax action="api/task_save.php">
        <input type="hidden" name="id" id="task_id">
        <input type="hidden" name="project_id" id="task_project_id">
        <div class="form-grid">
          <div class="field full">
            <label>Task title *</label>
            <input type="text" name="title" id="task_title" class="input" required placeholder="e.g. Implement authentication">
          </div>
          <div class="field full">
            <label>Description</label>
            <textarea name="description" id="task_desc" class="textarea" placeholder="Add details…"></textarea>
          </div>
          <div class="field">
            <label>Project *</label>
            <select name="_project_select" id="task_project_select" class="select" onchange="document.getElementById('task_project_id').value=this.value">
              <?php foreach ($projects as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Assignee</label>
            <select name="assigned_to" id="task_assignee" class="select">
              <option value="0">— Unassigned —</option>
              <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Status</label>
            <select name="status" id="task_status_sel" class="select">
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="review">Review</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div class="field">
            <label>Priority</label>
            <select name="priority" id="task_priority" class="select">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="field">
            <label>Deadline</label>
            <input type="date" name="deadline" id="task_deadline" class="input">
          </div>
          <div class="field">
            <label>Estimated hours</label>
            <input type="number" name="estimated_hours" id="task_hours" class="input" step="0.5" min="0" value="0">
          </div>
          <div class="field full">
            <label>Progress: <span id="task_progress_out">0%</span></label>
            <input type="range" name="progress" id="task_progress" min="0" max="100" value="0" style="width:100%" data-progress-input="#task_progress_out">
          </div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn--ghost" data-close-modal>Cancel</button>
          <button type="submit" class="btn">Save Task</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
window.openTaskModal = function(taskId, projectId, data){
  document.getElementById('project_modal_init');
  const set=(id,v)=>{const el=document.getElementById(id); if(el) el.value=v ?? '';};
  document.getElementById('taskModalTitle').textContent = taskId ? 'Edit Task' : 'New Task';
  set('task_id', taskId);
  if (projectId) { set('task_project_id', projectId); const sel=document.getElementById('task_project_select'); if(sel) sel.value = projectId; }
  if (data) {
    set('task_title', data.title); set('task_desc', data.description);
    set('task_assignee', data.assigned_to||0);
    set('task_status_sel', data.status||'todo'); set('task_priority', data.priority||'medium');
    set('task_deadline', data.deadline); set('task_hours', data.estimated_hours||0);
    set('task_progress', data.progress||0); document.getElementById('task_progress_out').textContent=(data.progress||0)+'%';
  } else {
    set('task_id',''); set('task_title',''); set('task_desc','');
    set('task_status_sel','todo'); set('task_priority','medium');
    set('task_deadline',''); set('task_hours',0); set('task_progress',0);
    document.getElementById('task_progress_out').textContent='0%';
  }
  openModal('taskModal');
};
</script>
