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
  if (preg_match('/[a-z.]+@(.+?)\.(.+)/', $user, $m)) {
    $o = "net";
    if (($m[1] == "mozilla" && $m[2] == "com") ||
        ($m[1] == "mozilla-japan" && $m[2] == "org")) {
      $o = "com";
    } elseif (($m[1] == "mozilla" && $m[2] == "org") ||
              ($m[1] == "mozillafoundation" && $m[2] == "org")) {
      $o = "org";
    }
    $dn = "mail=$user,o={$o},dc=mozilla";
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
