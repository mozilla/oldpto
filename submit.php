<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");

// Validate the input format for various fields.
$validations = array(
  "hours" => '/^[1-9]\d*$|^\d*\.\d$/',
  "start" => '/^[01]\d\/[0-3]\d\/\d{4}$/',
  "end" => '/^[01]\d\/[0-3]\d\/\d{4}$/'
);
$failures = array();
foreach ($validations as $field => $pattern) {
  if (!preg_match($pattern, $_POST[$field])) {
    $failures[] = $field;
  }
}
if (!empty($failures)) {
  require_once "./templates/header.php";
  print "<form>";
  print "<p>Oh noes! The following fields weren't in the right formats!</p>";
  print "<pre>". implode(", ", $failures) ."</pre>";
  print "</form>";
  require_once "./templates/footer.php";
  die;
}

// Dismantle attempts to create a temporal paradox.
if (parse_date($_POST["end"]) < parse_date($_POST["start"])) {
  require_once "./templates/header.php";
  print "<form><p>Temporal paradox! Your PTO ends before it starts!</p></form>";
  require_once "./templates/footer.php";
  die;
}

$is_editing = $_POST["edit"] == "1";
$id = isset($_POST["id"]) ? (int)$_POST["id"] : NULL;


$notifier_email = $_SERVER["PHP_AUTH_USER"];
$data = ldap_find(
  $connection, "mail=". $notifier_email, array("manager", "cn")
);
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

if ($is_editing && !$is_hr) {
  // Can the user edit it?
  $query_string =
    "SELECT id FROM pto WHERE ".
    "id = ". (string)$id ." AND ".
    'person = "'. $notifier_email .'" AND '.
    "end >= ". (string)time() .
    ';';
  $query = mysql_query($query_string);
  $id = mysql_result($query, 0);
  if ($id === FALSE) {
    require_once "./templates/header.php";
    print "<form>";
    print "<p>You cannot edit this PTO entry due to one of the following:</p>";
    print "<ul>";
    print "  <li>You are not the one who submitted this PTO entry.</li>";
    print "  <li>It is now past the end of the PTO.</li>";
    print "  <li>You just don't have enough power. Ask someone from HR.</li>";
    print "</ul>";
    print "</form>";
    require_once "./templates/footer.php";
    die;
  } else {
    $id = (int)$id;
  }
}

// Add the manager
if (ENABLE_MANAGER_NOTIFYING) {
  $notified_people[] = $manager_name ." <". $manager_email .'>';
}
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
  $check = trim($check);
  if (in_string($check, '<') && in_string($check, '>')) {
    $check = explode('>', $check);
    $check = explode('<', $check[0]);
    $check = $check[1];
  }
  $address = $check;
  if (in_array($address, $mail_blacklist)) {
    $banned[] = $check;
  } else {
    $allowed[] = $check;
  }
}
$notified_people = $allowed;

$hours = (float)$_POST["hours"];
# $start_time = isset($_POST["start_time"]) ? $_POST["start_time"] : "00:00 am";
# $end_time = isset($_POST["end_time"]) ? $_POST["end_time"] : "00:00 am";
$start = maketime($_POST["start"]);
$end = maketime($_POST["end"]);

if ($from == "submitter") {
  $from = $notifier_name .' <'. $notifier_email .'>';
}

$tokens = array(
  "%id%" => $id,
  "%notifier%" => $notifier_name,
  "%editor%" => $notifier_name,
  "%hours%" => $hours,
  "%start%" => reformat_date($_POST["start"], "M j, Y"),
  "%end%" => reformat_date($_POST["end"], "M j, Y"),
  "%details%" => $_POST["details"]
);

$single_day_fix = FALSE;
// Single day PTO
if ($start == $end) {
  $single_day_fix = TRUE;
  // Special case of "on MM/DD/YYYY" instead of "from MM/DD/YYYY to MM/DD/YYYY".
  $body = $single_day_body;
  // Expand single day to a timerange of a whole day.
  $end += (1 * 60 * 60 * 24) - 1;
}
if ($is_editing) {
  $subject = $edit_subject;
  $body = $single_day_fix ? $edit_single_day_body : $edit_body;
}
foreach ($tokens as $token => $replacement) {
  $subject = str_replace($token, $replacement, $subject);
  $body = str_replace($token, $replacement, $body);
}

if (ENABLE_DB) {
  if ($is_editing) {
    $query_string =
      "UPDATE pto SET ".
      'person = "'. $notifier_email .'", '.
      'details = "'. mysql_real_escape_string($_POST["details"]) .'", '.
      'hours = '. (string)$hours .', '.
      'start = '. (string)$start .', '.
      'end = '. (string)$end .' '.
      'WHERE id = '. (string)$id .
      ';'
    ;
  } else {
    $query_string =
      "INSERT INTO pto (person, details, hours, start, end, added) VALUES(".
      '"'. $notifier_email .'", '.
      '"'. mysql_real_escape_string($_POST["details"]) .'", '.
      (string)$hours .', '.
      (string)$start .', '.
      (string)$end .', '.
      (string)time() .
      ");"
    ;
  }
  $query = mysql_query($query_string);
}

if (ENABLE_MAIL) {
  $mail_result = mail(implode(", ", $notified_people), $subject, $body, "From: ". $from);
} elseif (DEBUG_ON) {
  $mail_result = FALSE;
  fb("To: ". implode(", ", $notified_people));
  fb("Subject: ". $subject);
  fb("Body: ". $body);
  fb("From: ". $from);
}

require_once "./templates/header.php";
?>
    <form>
    <p>
    <?php
      if ($query && $mail_result) {
        print "Great success! The PTO notification was mailed to peoples and put into database. It's nice!";
      } elseif ($query && !$mail_result) {
        print "OH NOES! I CAN'T SENDZ OUT MAILZ.";
      } elseif (!$query && $mail_result) {
        print "I SENTZ MAIL BUT SQL FAIL :(";
      } else /* if (!$query && !$mail_result) */ {
        print "<em>Someone set up us DB and mail fail! We get signal.</em> How are you, gentlemen!! All your PTO are belong to us. You have no chance to vacation make your time. <em>Mail kourge@mozilla.com. For great justice.</em>";
      }

      if (!$query && DEBUG_ON) {
        fb("is_editing?");
        fb($is_editing);
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
    </form>

<?php require_once "./templates/footer.php"; ?>
