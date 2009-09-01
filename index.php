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
preg_match("/mail=([a-z]+@mozilla.*),o=/", $manager_dn, $matches);
$manager_email = $matches[1];

$data = ldap_find(
  $connection,
  "mail=". $manager_email,
  array("cn")
);
$manager_name = $data[0]["cn"][0];

if (ENABLE_MANAGER_NOTIFYING) {
  $notified_people[] = $manager_name ." <". $manager_email .'>';
}

require_once "./templates/header.php";
require_once "./templates/edit.php";
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
