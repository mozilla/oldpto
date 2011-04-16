<?php

require_once("FirePHPCore/fb.php");
ob_start();

function ldap_find($connection, $filter, $attributes) {
  $search = ldap_search($connection, "dc=mozilla", $filter, $attributes);
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

/* Why yes, this name is confusing enough. */
function parse_date($date_to_parse) {
  $p = date_parse($date_to_parse);
  return mktime(0, 0, 0, $p["month"], $p["day"], $p["year"]);
}

function reformat_date($date_to_parse, $format, $f="date") {
  return $f($format, parse_date($date_to_parse));
}

function email_to_alias($email) {
  $alias = explode('@', $email);
  return $alias[0];
}
