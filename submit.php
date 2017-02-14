<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");
require_once("class.Debug.php");

//Debug::showAndDie($_REQUEST);

// Validate the input format for various fields.
$validations = array(
  "hours" => '/^[1-9]\d*$|^\d*\.\d$/',
  "start" => '/^[01]\d\/[0-3]\d\/\d{4}$/',
  "end" => '/^[01]\d\/[0-3]\d\/\d{4}$/'
);
$failures = array();
foreach ($validations as $field => $pattern) {
  if (!preg_match($pattern, $_REQUEST[$field])) {
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
if (parse_date($_REQUEST["end"]) < parse_date($_REQUEST["start"])) {
  require_once "./templates/header.php";
  print "<form><p>Temporal paradox! Your PTO ends before it starts!</p></form>";
  require_once "./templates/footer.php";
  die;
}

// Pick off puny, insignificant PTOs.
if (((int)$_REQUEST["hours"]) < 4) {
  require_once "./templates/header.php";
  print "<form><p>A PTO entry needs to be at least 4 hours.</p></form>";
  require_once "./templates/footer.php";
  die;
}

if (isset($_REQUEST["id"]) && $_REQUEST["id"]) {
	$is_editing = true;
	$id = (int)$_REQUEST["id"];
}


$notifier_email = $GLOBAL_AUTH_USERNAME;
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
    print "  <li>The PTO you submitted occurs in the past.</li>";
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
if (!empty($_REQUEST["people"])) {
  $people = array_map("trim", explode(",", $_REQUEST["people"]));
  $notified_people = array_merge($notified_people, $people);
}

// Optionally "cc" the notifier. Yes, it's not real CC.
if (isset($_REQUEST["cc"]) && $_REQUEST["cc"] == "1") {
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

$hours = (float)$_REQUEST["hours"];
$hours_daily = isset($_REQUEST['hours_daily']) && $_REQUEST['hours_daily'] ? urldecode($_REQUEST['hours_daily']) : '{}';
# $start_time = isset($_REQUEST["start_time"]) ? $_REQUEST["start_time"] : "00:00 am";
# $end_time = isset($_REQUEST["end_time"]) ? $_REQUEST["end_time"] : "00:00 am";
$start = maketime($_REQUEST["start"]);
$end = maketime($_REQUEST["end"]);

if ($from == "submitter") {
  $from = $notifier_name .' <'. $notifier_email .'>';
}

$tokens = array(
  "%id%" => $id,
  "%notifier%" => $notifier_name,
  "%editor%" => $notifier_name,
  "%hours%" => $hours,
  "%start%" => reformat_date($_REQUEST["start"], "M j, Y"),
  "%end%" => reformat_date($_REQUEST["end"], "M j, Y"),
  "%details%" => $_REQUEST["details"]
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

$subject .= $single_day_fix ? " (%start%)" : " (%start% - %end%)";

foreach ($tokens as $token => $replacement) {
  $subject = str_replace($token, $replacement, $subject);
  $body = str_replace($token, $replacement, $body);
}

if (ENABLE_DB) {
  /*if ($is_editing) {
    $query_string =
      "UPDATE pto SET ".
      'person = "'. $notifier_email .'", '.
      'details = "'. mysql_real_escape_string($_REQUEST["details"]) .'", '.
      'hours = '. (string)$hours .', '.
	  'hours_daily = "'.mysql_real_escape_string($hours_daily) .'", '.
      'start = '. (string)$start .', '.
      'end = '. (string)$end .' '.
      'WHERE id = '. (string)$id .
      ';'
    ;
  } else {*/
    $query_string =
      "INSERT INTO pto (person, details, hours, hours_daily, start, end, added) VALUES(".
      '"'. $notifier_email .'", '.
      '"'. mysql_real_escape_string($_REQUEST["details"]) .'", '.
      (string)$hours .', '.
	  '"'. mysql_real_escape_string($hours_daily) .'", '.
      (string)$start .', '.
      (string)$end .', '.
      (string)time() .
      ");"
    ;
 // }
//Debug::showAndDie($query_string);
  $query = mysql_query($query_string);
}

if (ENABLE_MAIL) {
  $mail_headers = array(
    'From: ' . $from,
    'Content-Type: text/plain;charset=utf-8'
  );
  $enc_subject = "=?utf-8?b?" . base64_encode($subject) . "?=";

  $mail_result = mail(implode(", ", $notified_people), $enc_subject, $body, implode("\r\n", $mail_headers));
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
        print "Your PTO has been put into the database, and the email has been sent :)";
      } elseif ($query && !$mail_result) {
        print "Your PTO has been put into the database. Unfortunately, the email was NOT sent due to an error.";
      } elseif (!$query && $mail_result) {
        print "Your PTO was NOT put into the database, due to an error, EVEN THOUGH an email was sent. Please resubmit your PTO :(";
      } else /* if (!$query && !$mail_result) */ {
        print "Your PTO was NOT put into the database, nor was an email sent. Please resubmit your PTO :(";
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
