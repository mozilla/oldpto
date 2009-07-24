<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");

$notifier_email = $_SERVER["PHP_AUTH_USER"];
$data = ldap_find(
  $connection, "mail=". $notifier_email, array("givenName", "sn", "manager")
);
$notifier_name = ldap_fullname($data[0]);

$manager_dn = $data[0]["manager"][0];
// "OMG, not querying LDAP for the real email? That's cheating!"
preg_match("/mail=([a-z]+@mozilla\\.com),/", $manager_dn, $matches);
$manager_email = $matches[1];

$data = ldap_find(
  $connection,
  "mail=". $manager_email,
  array("givenName", "sn")
);
$manager_name = ldap_fullname($data[0]);

// Add the manager
# $notified_people[] = $manager_name ." <". $manager_email .'>';
// Merge additional inputted people to notify
if (!empty($_POST["people"])) {
  $people = array_map("trim", explode(",", $_POST["people"]));
  $notified_people = array_merge($notified_people, $people);
}

// Optionally "cc" the notifier. Yes, it's not real CC.
if (isset($_POST["cc"]) && $_POST["cc"] == "1") {
  $notified_people[] = $notifier_name .' <'. $notifier_email .'>';
}

$banned = array();
$allowed = array();
while ($check = array_pop($notified_people)) {
  $match = null;
  preg_match("/all.*@mozilla\\.com/", $check, $match);
  if (empty($match)) {
    $allowed[] = $check;
  } else {
    $banned[] = $check;
  }
}
$notified_people = $allowed;

$start_time = isset($_POST["start_time"]) ? $_POST["start_time"] : "00:00";
$end_time = isset($_POST["end_time"]) ? $_POST["end_time"] : "00:00";
$start = maketime($_POST["start"] . $start_time);
$end = maketime($_POST["end"] . $end_time);

if ($from == "submitter") {
  $from = $notifier_name .' <'. $notifier_email .'>';
}

$tokens = array(
  "%notifier%" => $notifier_name,
  "%start%" => $_POST["start"],
  "%end%" => $_POST["end"],
  "%reason%" => $_POST["reason"]
);

foreach ($tokens as $token => $replacement) {
  $subject = str_replace($token, $replacement, $subject);
  $body = str_replace($token, $replacement, $body);
}

$c = mysql_connect($mysql["host"], $mysql["user"], $mysql["password"]);
mysql_select_db($mysql["database"]);
if (!DISABLE_DB) {
  $query_string = 
    "INSERT INTO pto (person, reason, start, end, added) VALUES(".
    '"'. $notifier_email .'", '.
    '"'. mysql_real_escape_string($_POST["reason"]) .'", '.
    (string)$start .', '.
    (string)$end .', '.
    (string)time() .
    ");"
  ;
  $query = mysql_query($query_string);
}

if (!DISABLE_MAIL) {
  $mail_result = mail(implode(", ", $notified_people), $subject, $body, "From: ". $from);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">
  <head>
    <title>PTO Submitted<title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <script src="./js/jquery-1.3.2.min.js" type="text/javascript"></script>
    <script src="./js/jquery-ui-1.7.2.custom.min.js" type="text/javascript"></script>
    <script src="./js/jquery.cookie.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="./css/style.css"/>
    <link rel="stylesheet" type="text/css" href="./css/redmond/jquery-ui-1.7.2.custom.css"/>
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" /> 

    <script type='text/javascript'>
    </script>
  </head>

  <body>
    <h1>PTO Notification</h1>
    <p>
    <?php
      if ($query && $mail_result) {
        print "Great success! The PTO notification was mailed to peoples and put into database. It's nice!";
      } elseif ($query && !$mail_result) {
        print "OH NOES! I CAN'T SENDZ OUT MAILZ.";
      } elseif (!$query && $mail_result) {
        print "I SENTZ MAIL BUT SQL FAIL :(";
      } else /* if (!$query && !$mail_result) */ {
        print "<em>Someone set up us DB and mail fail! We get signal.</em> How are you, gentlemen!! All your PTO are belong to us. You have no chance to vacation make your time. <em>Mail kourge@mozilla.com. For great justice.</em";
      }

      if (!$query && DEBUG_ON) {
        fb(mysql_errno() .": ". mysql_error());
        fb($query_string);
      }
    ?>
    </p>
    <?php
      if (!empty($banned)) {
        print "<p>You also attempted to email the following addresses, which are banned. Remember, everytime you annoy every single individual about your PTO, a kitten or puppy dies! And you don't want that, <em>do you?</em></p>";
        print "<pre>". htmlspecialchars(implode(", ", $banned)) ."</pre>";
      }
    ?>
  </body>
</html>
