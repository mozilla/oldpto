<?php
require_once('config.php');

$connection = ldap_connect($ldap["host"], $ldap["port"]);
if (!ldap_bind($connection, $LDAP_BIND_USER, $LDAP_BIND_PASS)) {
    echo "LDAP conneciton failed";
    die;
}
