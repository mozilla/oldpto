<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");

$data = ldap_find(
  $connection,
  "mail=". $_SERVER["PHP_AUTH_USER"], 
  array("cn", "manager")
);
$notifier_email = $_SERVER["PHP_AUTH_USER"];
$notifier_name = $data[0]["cn"][0];

$manager_dn = $data[0]["manager"][0];
preg_match("/mail=([a-z]+@mozilla\\.com),/", $manager_dn, $matches);
$manager_email = $matches[1];

$data = ldap_find(
  $connection,
  "mail=". $manager_email,
  array("cn")
);
$manager_name = $data[0]["cn"][0];

$notified_people[] = $manager_name ." <". $manager_email .'>';

require_once "./templates/header.php";
?>
    <h1>PTO Notification</h1>
    <p>O hai, <?= str_replace("@mozilla.com", '', $notifier_email) ?>. Submit your PTO notification here. <a href="https://intranet.mozilla.org/Paid_Time_Off_%28PTO%29">All your PTO are belong to us</a>.</p>
    <form action="submit.php" method="post" name="pto-notify">
      <table><tbody>
      <tr>
        <td><label for="hours">Total Hours</label></td>
        <td>
          <input type="text" id="hours" name="hours" size="2" />
        </td>
      </tr>
      <tr>
        <td><label for="start">Start</label></td>
        <td>
          <input type="text" id="start" name="start" size="10" /><!-- at 
          <input type="text" id="start-time" name="start_time" size="8" value="00:00 am" />-->
        </td>
      </tr>
      <tr>
        <td><label for="end">End</label></td>
        <td>
          <input type="text" id="end" name="end" size="10" /><!-- at
          <input type="text" id="end-time" name="end_tme" size="8" value="00:00 am" />-->
        </td>
      </tr>
      <tr>
        <td><label for="people">People to Notify</label></td>
        <td>
          <?= htmlentities(implode(", ", $notified_people)) ?><br />
          <textarea name="people" id="people" cols="80" rows="2"></textarea><br />
          <input type="checkbox" name="cc" id="cc" value="1" /><label for="cc">CC me</label>
        </td>
      </tr>
      <tr>
        <td><label for="details">Details</label><br />(optional)</td>
        <td><textarea name="details" id="details" cols="80" rows="10"></textarea></td>
      </tr>
      </tbody></table>
      <input type="submit" value="Submit" />
    </form>

  <script type='text/javascript'>
  jQuery.noConflict();
  (function($) {
    $(document).ready(function() {
      $("#start, #end").datepicker({
        onClose: function() { $(this).focus(); }
      });
      // $("#start-time, #end-time").timepickr({convention: 12});
    });
  })(jQuery);
  </script>

<?php require_once "./templates/footer.php"; ?>
