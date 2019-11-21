<?php
require_once('config.php');

$connection = ldap_connect($ldap["host"], $ldap["port"]);
ldap_start_tls($connection);

if (!ldap_bind($connection, $LDAP_BIND_USER, $LDAP_BIND_PASS)) {
    echo "LDAP connection failed";
    die;
}
