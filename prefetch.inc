<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");

require_once("output.inc");

$c = mysql_connect($mysql["host"], $mysql["user"], $mysql["password"]);
mysql_select_db($mysql["database"]);

require_once("filtering.inc");
require_once("perms.inc");

$query = mysql_query(
  "SELECT $fields FROM pto $conditions ORDER BY added DESC;"
);


$user_cache = array();
$search = ldap_search(
  $connection, "o=com,dc=mozilla", "mail=*",
  array("mail", "givenName", "sn", "physicalDeliveryOfficeName")
);
$match = ldap_get_entries($connection, $search);
for ($i = 0; $i < $match["count"]; $i++) {
  $row = $match[$i];
  $user_cache[$row["mail"][0]] = $row;
}

$results = array();
while ($row = mysql_fetch_assoc($query)) {
  foreach (array("id", "added", "start", "end") as $field) {
    $row[$field] = (int)$row[$field];
  }
  $row["hours"] = (double)$row["hours"];

  $key = $row["person"];
  $row["sn"] = $user_cache[$key]["sn"][0];
  $row["givenName"] = $user_cache[$key]["givenname"][0];
  $row["location"] = $user_cache[$key]["physicaldeliveryofficename"][0];

  $results[] = $row;
}

