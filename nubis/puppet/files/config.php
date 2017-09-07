<?php
require_once "/etc/nubis-config/pto.php";

$ldap = array(
 "host" => "$ldap_host",
 "port" => $ldap_port,
);
$connection = null;

$LDAP_BIND_USER = $ldap_bind_user;
$LDAP_BIND_PASS = $ldap_bind_pass;

# Fill out MySQL server info.
$mysql = array(
  "host" => "$Server",
  "user" => "$User",
  "password" => "$Password",
  "database" => "$Name"
);
$GLOBAL_AUTH_USERNAME = $_SERVER['PHP_AUTH_USER'];

# Set the constants below to FALSE to do various debugging.
define("ENABLE_MAIL", FALSE);
define("ENABLE_DB", TRUE);
define("ENABLE_MANAGER_NOTIFYING", FALSE);

# Set below to TRUE to see MySQL error messages and query strings sent.
define("DEBUG_ON", FALSE);

# Specify HR managers with email addresses only.
$hr_managers = array(
  "dcoleman@mozilla.com",
);

# Only these people are able to view the export and report pages
$export_users = array(
  "dcoleman@mozilla.com",
  "jaguilera@mozilla.com"
);

# Specified in RFC address format. One address per array element, please.
$notified_people = array(
  "Joel Aguilera <jaguilera@mozilla.com>",
  "Doris Coleman <dcoleman@mozilla.com>"
);


# Specify addesses that are not  allowed in the additional notified people field.
$mail_blacklist = array(
  "all@mozilla.com",
  "all-mv@mozilla.com"
);

# Set to "submitter" to mail on behalf of the person submitting the notification
$from = "submitter";

# The following template variables are available:
# %notifier%, %hours%, %start%, %end%, %details%
$subject = "PTO notification from %notifier%";
$body = <<<EOD
%notifier% has submitted %hours% hours of PTO from %start% to %end% with the details:
%details%

- The Happy PTO Managing Intranet App
EOD;

$single_day_body = <<<EOD
%notifier% has submitted %hours% hours of PTO on %start% with the details:
%details%

- The Happy PTO Managing Intranet App
EOD;

$edit_subject = "Edit of PTO by %editor%";


$edit_body = <<<EOD
%editor% has edited PTO entry #%id% to %hours% hours of PTO from %start% to %end% with the details:
%details%

- The Happy PTO Managing Intranet App
EOD;


$edit_single_day_body = <<<EOD
%editor% has edited PTO entry #%id% to %hours% hours of PTO on %start% with the details:
%details%

- The Happy PTO Managing Intranet App
EOD;
