<?php
function wail_and_bail() {
  header('HTTP/1.0 401 Unauthorized');
  print "<h1>401 Unauthorized</h1>";
  die;
}

if (!isset($_SERVER["PHP_AUTH_USER"])) {
  header('WWW-Authenticate: Basic realm="Mozilla Corporation - LDAP Login"');
  wail_and_bail();
} else {
  // Check for validity of login
  if (preg_match("/[a-z]+@mozilla\\.com/", $_SERVER["PHP_AUTH_USER"])) {
    $dn = "mail=". $_SERVER["PHP_AUTH_USER"] .",o=com,dc=mozilla";
    $password = $_SERVER["PHP_AUTH_PW"];
  } else {
    wail_and_bail();
  }
}

$connection = ldap_connect($ldap["host"], $ldap["port"]);
// Actually perform authentication
if (!ldap_bind($connection, $dn, $password)) {
  wail_and_bail();
}
