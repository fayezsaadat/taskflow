<?php
/** TaskFlow — Calendar (deadlines) */
require_once __DIR__ . '/includes/auth.php';
require_login();
$cu = current_user();
$activeNav = 'calendar';
$pageTitle = APP_NAME . ' — Calendar';
$pdo = db();

$month = (int)($_GET['m'] ?? (int)date('n'));
$year  = (int)($_GET['y'] ?? (int)date('Y'));
if ($month < 1 || $month > 12) $month = (int)date('n');
if ($year < 2000 || $year > 2100) $year = (int)date('Y');

$firstDay = mktime(0,0,0,$month,1,$year);
$daysInMonth = (int)date('t',$firstDay);
$startWeekday = (int)date('w',$firstDay); // 0 = Sun
$monthName = date('F Y',$firstDay);

$monthStart = date('Y-m-01',$firstDay);
$monthEnd = date('Y-m-t',$firstDay);

// events: tasks (assigned to me for employees) + project deadlines
$params = [$monthStart,$monthEnd];
$taskWhere = "AND t.deadline BETWEEN ? AND ?";
if ($cu['role']==='employee') { $taskWhere .= " AND t.assigned_to = ?"; $params[]=$cu['id']; }
$sql = "SELECT t.id, t.title, t.deadline, t.priority, t.status, 'task' kind, t.assigned_to FROM tasks t WHERE t.deadline IS NOT NULL $taskWhere";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// project deadlines (managers/admins see all; employees see projects they're members of)
$projParams = [$monthStart,$monthEnd];
$projWhere = "AND p.deadline BETWEEN ? AND ?";
if ($cu['role']==='employee') {
  $projWhere .= " AND p.id IN (SELECT project_id FROM project_members WHERE user_id=?)";
  $projParams[]=$cu['id'];
}
$pstmt = $pdo->prepare("SELECT p.id, p.name title, p.deadline, p.priority, p.status, 'project' kind FROM projects p WHERE p.deadline IS NOT NULL $projWhere");
$pstmt->execute($projParams);
foreach ($pstmt->fetchAll() as $pe) $events[] = $pe;

// group by date
$byDate = [];
foreach ($events as $ev) $byDate[$ev['deadline']][] = $ev;

// prev/next
$prevM = $month===1?12:$month-1; $prevY = $month===1?$year-1:$year;
$nextM = $month===12?1:$month+1; $nextY = $month===12?$year+1:$year;

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>Calendar</h1><p>Task and project deadlines at a glance</p></div>
  <div class="page-head__actions">
    <a class="btn btn--ghost btn--sm" href="?m=<?= $prevM ?>&y=<?= $prevY ?>">‹ <?= date('M',mktime(0,0,0,$prevM,1)) ?></a>
    <span style="font-weight:700;padding:0 12px"><?= $monthName ?></span>
    <a class="btn btn--ghost btn--sm" href="?m=<?= $nextM ?>&y=<?= $nextY ?>"><?= date('M',mktime(0,0,0,$nextM,1)) ?> ›</a>
    <a class="btn btn--soft btn--sm" href="?m=<?= (int)date('n') ?>&y=<?= (int)date('Y') ?>">Today</a>
  </div>
</div>

<div class="card card--padless no-print">
  <div class="table-wrap">
    <table class="calendar">
      <thead><tr>
        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?><th><?= $d ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <tr>
        <?php
        // leading blanks
        for ($i=0;$i<$startWeekday;$i++) echo '<td class="cal-day--other"><div class="cal-day"></div></td>';
        $today = date('Y-m-d');
        for ($d=1;$d<=$daysInMonth;$d++):
            $date = sprintf('%04d-%02d-%02d',$year,$month,$d);
            $isToday = $date===$today;
            $dayEvents = $byDate[$date] ?? [];
        ?>
          <td class="cal-day <?= $isToday?'cal-day--today':'' ?>">
            <div class="cal-day">
              <div class="cal-day__num"><?= $d ?></div>
              <?php foreach (array_slice($dayEvents,0,3) as $ev):
                $cls = $ev['kind']==='project' ? 'cal-event--med' : (
                  $ev['status']==='completed' ? 'cal-event--done' :
                  ($ev['priority']==='urgent' ? 'cal-event--urgent' :
                  ($ev['priority']==='high' ? 'cal-event--high' : 'cal-event--med')));
                $href = $ev['kind']==='project' ? 'project_view.php?id='.$ev['id'] : 'task_view.php?id='.$ev['id'];
              ?>
                <a class="cal-event <?= $cls ?>" href="<?= $href ?>" title="<?= e($ev['title']) ?>"><?= e($ev['kind']==='project'?'📦 ':'') . e(mb_strlen($ev['title'])>22?mb_substr($ev['title'],0,22).'…':$ev['title']) ?></a>
              <?php endforeach; ?>
              <?php if (count($dayEvents)>3): ?><div class="text-mute" style="font-size:10px;padding:0 7px">+<?= count($dayEvents)-3 ?> more</div><?php endif; ?>
            </div>
          </td>
        <?php
          if ((($startWeekday+$d)%7)===0 && $d!==$daysInMonth) echo '</tr><tr>';
        endfor;
        // trailing blanks
        $trail = (7 - (($startWeekday+$daysInMonth)%7))%7;
        for ($i=0;$i<$trail;$i++) echo '<td class="cal-day--other"><div class="cal-day"></div></td>';
        ?>
      </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3 flex gap-3 flex-wrap" style="font-size:12px;color:var(--text-soft)">
  <span class="flex items-center gap-1"><span class="cal-event cal-event--urgent" style="cursor:default">Urgent</span></span>
  <span class="flex items-center gap-1"><span class="cal-event cal-event--high" style="cursor:default">High</span></span>
  <span class="flex items-center gap-1"><span class="cal-event cal-event--med" style="cursor:default">Task / Project</span></span>
  <span class="flex items-center gap-1"><span class="cal-event cal-event--done" style="cursor:default">Completed</span></span>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
