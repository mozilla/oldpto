<?php

require_once("config.php");
require_once("class.Debug.php");
require_once("pto.inc");
require_once("auth.php");
require_once("output.inc");
require_once("perms.inc");
require_once('class.Filtering.php');
$results = Filtering::getRecords();
$aLdapCountries = Filtering::getCountries();