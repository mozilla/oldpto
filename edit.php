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
// "OMG, not querying LDAP for the real email? That's cheating!"
preg_match("/mail=([a-z]+@mozilla.*),o=/", $manager_dn, $matches);
$manager_email = $matches[1];

$data = ldap_find(
  $connection,
  "mail=". $manager_email,
  array("cn")
);
$manager_name = $data[0]["cn"][0];
$is_hr = in_array($manager_email, $hr_managers);

$c = mysql_connect($mysql["host"], $mysql["user"], $mysql["password"]);
mysql_select_db($mysql["database"]);

$id = (int)$_GET["id"];
$query = mysql_query("SELECT id, person, details, hours, start, end FROM pto WHERE id = ". $id);
$results = array();
while ($row = mysql_fetch_assoc($query)) {
  foreach (array("id", "added", "start", "end") as $field) {
    $row[$field] = (int)$row[$field];
  }
  foreach (array("start", "end") as $field) {
    $row[$field] = date("m/d/Y", $row[$field]);
  }
  $results[] = $row;
}

require_once "./templates/header.php";
if (count($results) > 1) {
  print "<form>ID duplication.</form>\n";
  require_once "./templates/footer.php";
  die;
} elseif (!$is_hr && $notifier_email != $results[0]["person"]) {
  print "<form>Not authorized.</form>\n";
  require_once "./templates/footer.php";
  die;
} else {
  $is_editing = TRUE;
  $edit = $results[0];
  require_once "./templates/edit.php";
}
?>
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
