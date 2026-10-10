<?php
/** Shared Project create/edit modal (embedded on dashboard & projects pages).
 *  Expects $teams (array of users) to be set. */
if (!isset($teams)) { $teams = db()->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetchAll(); }
?>
<div class="modal-overlay" id="projectModal">
  <div class="modal modal--lg">
    <div class="modal__header">
      <h3 id="projectModalTitle">New Project</h3>
      <button class="modal__close" data-close-modal>&times;</button>
    </div>
    <div class="modal__body">
      <form id="projectForm" data-ajax action="api/project_save.php" data-reset-on-success="1">
        <input type="hidden" name="id" id="proj_id">
        <div class="form-grid">
          <div class="field full">
            <label>Project name *</label>
            <input type="text" name="name" id="proj_name" class="input" required placeholder="e.g. Mobile App Redesign">
          </div>
          <div class="field full">
            <label>Description</label>
            <textarea name="description" id="proj_desc" class="textarea" placeholder="Short summary of the project goals…"></textarea>
          </div>
          <div class="field">
            <label>Status</label>
            <select name="status" id="proj_status" class="select">
              <option value="planning">Planning</option>
              <option value="active" selected>Active</option>
              <option value="on_hold">On Hold</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="field">
            <label>Priority</label>
            <select name="priority" id="proj_priority" class="select">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="field">
            <label>Start date</label>
            <input type="date" name="start_date" id="proj_start" class="input">
          </div>
          <div class="field">
            <label>Deadline</label>
            <input type="date" name="deadline" id="proj_deadline" class="input">
          </div>
          <div class="field">
            <label>Budget ($)</label>
            <input type="number" name="budget" id="proj_budget" class="input" step="0.01" min="0" value="0">
          </div>
          <div class="field">
            <label>Spent ($)</label>
            <input type="number" name="spent" id="proj_spent" class="input" step="0.01" min="0" value="0">
          </div>
          <div class="field full">
            <label>Team members</label>
            <select name="members[]" id="proj_members" class="select" multiple style="min-height:90px">
              <?php foreach ($teams as $u): ?>
                <option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="hint">Hold Ctrl/Cmd to select multiple team members.</div>
          </div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn--ghost" data-close-modal>Cancel</button>
          <button type="submit" class="btn">Save Project</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
// project modal fill helper
window.fillProjectModal = function(data){
  const set=(id,v)=>{const el=document.getElementById(id); if(el) el.value=v ?? '';};
  document.getElementById('projectModalTitle').textContent = data && data.id ? 'Edit Project' : 'New Project';
  set('proj_id', data?.id); set('proj_name', data?.name); set('proj_desc', data?.description);
  set('proj_status', data?.status || 'active'); set('proj_priority', data?.priority || 'medium');
  set('proj_start', data?.start_date); set('proj_deadline', data?.deadline);
  set('proj_budget', data?.budget ?? 0); set('proj_spent', data?.spent ?? 0);
  const m=document.getElementById('proj_members');
  Array.from(m.options).forEach(o=>o.selected = data && Array.isArray(data.members) && data.members.map(String).includes(o.value));
  if(!data?.id){ document.getElementById('projectForm').reset(); set('proj_status','active'); set('proj_priority','medium'); }
};
</script>
