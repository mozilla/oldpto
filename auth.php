<?php

function ask() {
  header('WWW-Authenticate: Basic realm="Mozilla Corporation - LDAP Login"');
}

function wail_and_bail() {
  header('HTTP/1.0 401 Unauthorized');
  ask();
  print "<h1>401 Unauthorized</h1>";
  die;
}

if (!isset($_SERVER["PHP_AUTH_USER"])) {
  ask();
  wail_and_bail();
} else {
  // Check for validity of login
  $user = $_SERVER["PHP_AUTH_USER"];
  if (preg_match('/[a-z]+@(mozilla.*)\.(.{3})/', $user, $m)) {
    if ($m[1] == "mozillamessaging" && $m[2] == "com") {
      $m[1] = "mozilla";
      $m[2] = "net";
    }
    $dn = "mail=$user,o={$m[2]},dc={$m[1]}";
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
