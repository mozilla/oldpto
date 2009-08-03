<?php

require_once("FirePHPCore/fb.php");
ob_start();

function ldap_find($connection, $filter, $attributes) {
  $search = ldap_search($connection, "o=com, dc=mozilla", $filter, $attributes);
  return ldap_get_entries($connection, $search);
}

function in_string($haystack, $needle) {
  return (strpos($haystack, $needle) !== FALSE);
}

function maketime($slashed_date) {
  $t = strtotime($slashed_date);
  if (DEBUG_ON) {
    fb($t);
  }
  return $t;
}
