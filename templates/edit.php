  <form action="submit.php" method="post">
    <p>O HAI, <?= email_to_alias($notifier_email) ?>. 
    <?= $is_editing ? "<em>Edit</em>" : "Submit" ?> your PTO notification here. 
    <a href="https://intranet.mozilla.org/Paid_Time_Off_%28PTO%29">All your PTO are belong to us</a>.</p>
    <table><tbody>
<?php if (isset($edit["id"]) && $is_editing): ?>
    <tr>
      <td><label for="id">ID</label></td>
      <td>
        <?= $edit["id"] ?>
        <input type="hidden" name="id" value="<?= $edit["id"] ?>" />
        <input type="hidden" name="edit" value="1" />
      </td>
    </tr>
<?php endif; ?>
    <tr>
      <td><label for="hours">Total Hours</label></td>
      <td>
<?php $hours = fill($edit, "hours"); ?>
        <input type="text" id="hours" name="hours" size="2"<?= $hours ?> />
      </td>
    </tr>
    <tr>
      <td><label for="start">Start</label></td>
      <td>
<?php $start = fill($edit, "start"); ?>
        <input type="text" id="start" name="start" size="10"<?= $start ?> />
      </td>
    </tr>
    <tr>
      <td><label for="end">End</label></td>
      <td>
<?php $end = fill($edit, "end"); ?>
        <input type="text" id="end" name="end" size="10"<?= $end ?> />
      </td>
    </tr>
    <tr>
<?php $for_this_edit = $is_editing ? "<br />(for this edit)" : ''; ?>
      <td><label for="people">People to Notify</label><?= $for_this_edit ?></td>
      <td>
        <?= htmlentities(implode(", ", $notified_people)) ?><br />
        <textarea name="people" cols="80" rows="2"></textarea><br />
        <input type="checkbox" name="cc" id="cc" value="1" />
        <label for="cc">CC me</label>
      </td>
    </tr>
    <tr>
      <td><label for="details">Details</label><br />(optional)</td>
      <td>
<?php $details = isset($edit["details"]) ? $edit["details"] : ''; ?>
        <textarea name="details" cols="80" rows="10"><?= $details ?></textarea>
      </td>
    </tr>
    </tbody></table>
    <input type="submit" value="<?= $is_editing ? "Edit" : "Submit" ?>" />
  </form>
