<?php
/** TaskFlow — Settings */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$activeNav = 'settings';
$pageTitle = APP_NAME . ' — Settings';

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>Settings</h1><p>Manage your profile, appearance and security</p></div></div>

<div class="tabs mb-3">
  <button class="tab is-active" onclick="settingsTab(this,'tab-profile')">Profile</button>
  <button class="tab" onclick="settingsTab(this,'tab-security')">Security</button>
  <button class="tab" onclick="settingsTab(this,'tab-prefs')">Preferences</button>
</div>

<!-- PROFILE -->
<div id="tab-profile">
  <div class="card">
    <div class="card__header"><h3>Profile Information</h3></div>
    <div class="card__body">
      <form id="profileForm" data-ajax action="api/user_save.php" enctype="multipart/form-data">
        <div class="flex gap-3 mb-3" style="align-items:center">
          <div style="width:72px;height:72px;border-radius:50%;overflow:hidden"><?= avatar_html($cu['name'],$cu['avatar'],'72') ?></div>
          <div>
            <label class="btn btn--soft btn--sm" style="cursor:pointer"><svg viewBox="0 0 24 24" class="ic"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-3.3 0-8 1.7-8 5v3h16v-3c0-3.3-4.7-5-8-5z"/></svg> Change avatar<input type="file" name="avatar" accept="image/*" style="display:none"></label>
            <div class="text-mute" style="font-size:12px;margin-top:6px">JPG, PNG or GIF. Max 2MB.</div>
          </div>
        </div>
        <div class="form-grid">
          <div class="field"><label>Full name *</label><input type="text" name="name" class="input" value="<?= e($cu['name']) ?>" required></div>
          <div class="field"><label>Email *</label><input type="email" name="email" class="input" value="<?= e($cu['email']) ?>" required></div>
          <div class="field"><label>Phone</label><input type="text" name="phone" class="input" value="<?= e($cu['phone'] ?? '') ?>"></div>
          <div class="field"><label>Department</label><input type="text" name="department" class="input" value="<?= e($cu['department'] ?? '') ?>"></div>
          <div class="field"><label>Job title</label><input type="text" name="job_title" class="input" value="<?= e($cu['job_title'] ?? '') ?>"></div>
          <div class="field"><label>Role</label><input type="text" class="input" value="<?= role_label($cu['role']) ?>" disabled></div>
          <div class="field full"><label>Bio</label><textarea name="bio" class="textarea" placeholder="Tell us about yourself…"><?= e($cu['bio'] ?? '') ?></textarea></div>
        </div>
        <div class="form-actions"><button type="submit" class="btn">Save changes</button></div>
      </form>
    </div>
  </div>
</div>

<!-- SECURITY -->
<div id="tab-security" class="hide">
  <div class="card" style="max-width:520px">
    <div class="card__header"><h3>Change Password</h3></div>
    <div class="card__body">
      <form id="profileForm" data-ajax action="api/user_save.php" onsubmit="return setPass(this)">
        <div class="field"><label>New password</label><input type="password" name="password" class="input" placeholder="Min. 6 characters"></div>
        <div class="field"><label>Confirm new password</label><input type="password" id="confirmPass" class="input" placeholder="Re-enter password"></div>
        <input type="hidden" name="name" value="<?= e($cu['name']) ?>">
        <input type="hidden" name="email" value="<?= e($cu['email']) ?>">
        <input type="hidden" name="phone" value="<?= e($cu['phone'] ?? '') ?>">
        <input type="hidden" name="department" value="<?= e($cu['department'] ?? '') ?>">
        <input type="hidden" name="job_title" value="<?= e($cu['job_title'] ?? '') ?>">
        <input type="hidden" name="bio" value="<?= e($cu['bio'] ?? '') ?>">
        <div class="form-actions"><button type="submit" class="btn">Update password</button></div>
      </form>
    </div>
  </div>
</div>

<!-- PREFERENCES -->
<div id="tab-prefs" class="hide">
  <div class="card" style="max-width:520px">
    <div class="card__header"><h3>Appearance</h3></div>
    <div class="card__body">
      <label style="display:block;font-weight:600;font-size:13px;margin-bottom:10px">Theme</label>
      <div class="flex gap-2">
        <label class="role-card <?= $cu['theme']!=='dark'?'is-active':'' ?>" style="flex:1;text-align:center;padding:18px">
          <input type="radio" name="themetoggle" value="light" <?= $cu['theme']!=='dark'?'checked':'' ?> style="display:none">
          <div style="font-size:24px">☀️</div><div>Light</div>
        </label>
        <label class="role-card <?= $cu['theme']==='dark'?'is-active':'' ?>" style="flex:1;text-align:center;padding:18px">
          <input type="radio" name="themetoggle" value="dark" <?= $cu['theme']==='dark'?'checked':'' ?> style="display:none">
          <div style="font-size:24px">🌙</div><div>Dark</div>
        </label>
      </div>
      <div class="text-mute" style="font-size:12px;margin-top:12px">Your preference is saved automatically.</div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script>
function settingsTab(btn,id){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('is-active'));
  btn.classList.add('is-active');
  ['tab-profile','tab-security','tab-prefs'].forEach(t=>document.getElementById(t).classList.add('hide'));
  document.getElementById(id).classList.remove('hide');
}
function setPass(f){
  const p=f.password.value, c=document.getElementById('confirmPass').value;
  if(p!==c){ toast('Passwords do not match','error'); return false; }
  if(p.length<6){ toast('Password must be 6+ characters','error'); return false; }
  return true;
}
// theme radios (preferences tab)
document.querySelectorAll('input[name=themetoggle]').forEach(r=>{
  r.addEventListener('change',()=>{
    document.querySelectorAll('input[name=themetoggle]').forEach(o=>o.closest('.role-card').classList.toggle('is-active',o.checked));
    const theme=r.value;
    document.documentElement.setAttribute('data-theme',theme);
    fetch('api/user_save.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':window.CSRF_TOKEN},body:'theme='+theme}).then(()=>toast('Theme updated','success')).catch(()=>{});
  });
});
</script>
