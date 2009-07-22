<?php

require_once("FirePHPCore/fb.php");
ob_start();

function ldap_fullname($ldap_entry) {
  return $ldap_entry["givenname"][0] .' '. $ldap_entry["sn"][0];
}

function ldap_find($connection, $filter, $attributes) {
  $search = ldap_search($connection, "o=com, dc=mozilla", $filter, $attributes);
  return ldap_get_entries($connection, $search);
}

function maketime($slashed_date) {
  $t = strptime($slashed_date, "%m/%d/%Y");
  return mktime($t["tm_hour"], $t["tm_min"], $t["tm_sec"],
                $t["tm_mon"] + 1, $t["tm_mday"], $t["tm_year"] + 1900);
}
