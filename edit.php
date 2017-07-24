<?php

require_once("config.php");
require_once("pto.inc");
require_once("auth.php");
require_once("class.Debug.php");
require_once("./templates/header.php");

/****************************************/

$aFormValues = NULL;
$aErrors = NULL;
$is_editing = false;

$data = ldap_find(
  	$connection,
	"mail=". $GLOBAL_AUTH_USERNAME,
	array("cn", "manager")
);
$notifier_email = $GLOBAL_AUTH_USERNAME;
$notifier_name = $data[0]["cn"][0];

$manager_dn = $data[0]["manager"][0];
preg_match("/mail=(.+),o=/", $manager_dn, $matches);
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
/****************************************/

if (isset($_REQUEST['id']) && $_REQUEST['id']) {
	$data = ldap_find(
	  	$connection,
		"mail=". $GLOBAL_AUTH_USERNAME,
		array("cn", "manager")
	);
	
	$notifier_email = $GLOBAL_AUTH_USERNAME;
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
	$is_hr = in_array($manager_email, $hr_managers);

	$c = mysql_connect(
		$mysql["host"], 
		$mysql["user"], 
		$mysql["password"]
	);
	
	mysql_select_db($mysql["database"]);

	$id = (int)$_REQUEST["id"];
	$query = mysql_query("SELECT id, person, details, hours, hours_daily, start, end FROM pto WHERE id = ". $id);
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
	
	if (count($results) > 1) {
		print "<form>ID duplication.</form>\n";
		require_once "./templates/footer.php";
		die;
	} elseif (!$is_hr && $notifier_email != $results[0]["person"]) {
		print "<form>Not authorized.</form>\n";
		require_once "./templates/footer.php";
		die;
	} 
	
	$is_editing = TRUE;
	$aFormValues = $results[0];
	
}

if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
	require_once "./templates/edit1.php";
} else {
	require_once "./templates/edit.php";
}

require_once "./templates/footer.php"; 

?>
