<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");

$data = ldap_find(
  $connection,
  "mail=". $_SERVER["PHP_AUTH_USER"], 
  array("givenName", "sn", "manager")
);
$notifier_email = $_SERVER["PHP_AUTH_USER"];
$notifier_name = ldap_fullname($data[0]);

$manager_dn = $data[0]["manager"][0];
preg_match("/mail=([a-z]+@mozilla\\.com),/", $manager_dn, $matches);
$manager_email = $matches[1];

$data = ldap_find(
  $connection,
  "mail=". $manager_email,
  array("givenName", "sn")
);
$manager_name = ldap_fullname($data[0]);

$notified_people[] = $manager_name ." <". $manager_email .'>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">
  <head>
    <title>PTO Notification<title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <script src="./js/jquery-1.3.2.min.js" type="text/javascript"></script>
    <script src="./js/jquery-ui-1.7.2.custom.min.js" type="text/javascript"></script>
    <script src="./js/jquery.cookie.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="./css/style.css"/>
    <link rel="stylesheet" type="text/css" href="./css/redmond/jquery-ui-1.7.2.custom.css"/>
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" /> 

    <script type='text/javascript'>
    jQuery.noConflict();
    (function($) {
      $(document).ready(function() {
        $("#start, #end").datepicker();
      });
    })(jQuery);
    </script>
  </head>

  <body>
    <h1>PTO Notification</h1>
    <p>Herro, <?= str_replace("@mozilla.com", '', $notifier_email) ?>. Dis iz only fur testin. U can't haz real vakashen yeaht. I don't wantz too shpam ur managerz, so dey not maild.</p>
    <form action="submit.php" method="post" name="pto-notify">
      <table><tbody>
      <tr>
        <td><label for="start">Start</label></td>
        <td><input type="text" id="start" name="start" /></td>
      </tr>
      <tr>
        <td><label for="end">End</label></td>
        <td><input type="text" id="end" name="end" /></td>
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
        <td><label for="reason">Reason</label><br />(optional)</td>
        <td><textarea name="reason" id="reason" cols="80" rows="10"></textarea></td>
      </tr>
      </tbody></table>
      <input type="submit" value="Submit" />
    </form>
  </body>
</html>
