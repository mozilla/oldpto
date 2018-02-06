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
  "host" => "$Database_Server",
  "user" => "$Database_User",
  "password" => "$Database_Password",
  "database" => "$Database_Name"
);
$GLOBAL_AUTH_USERNAME = $_SERVER['PHP_AUTH_USER'];

# SMTP Settings
if ($SMTP_Server) {
  error_log("SMTP Enabled");
  define("ENABLE_SMTP", TRUE);
  define("SMTP_HOST",$SMTP_Server);
  define("SMTP_PORT", 465);
  define("SMTP_USERNAME", $SMTP_User);
  define("SMTP_PASSWORD", $SMTP_Password);
}

# Set the constants below to FALSE to do various debugging.
define("ENABLE_MAIL", isset($Enable_Mail) ? $Enable_Mail : TRUE);
define("ENABLE_DB", isset($Enable_DB) ? $Enable_DB: TRUE);
define("ENABLE_MANAGER_NOTIFYING", isset($Enable_Manager_Notifying) ? $Enable_Manager_Notifying : FALSE);

# Set below to TRUE to see MySQL error messages and query strings sent.
define("DEBUG_ON", isset($Debug) ? $Debug : FALSE);

# Specify HR managers with email addresses only.
$hr_managers = preg_split('/[\n\,]+/', $hr_managers, 0, PREG_SPLIT_NO_EMPTY);

# Only these people are able to view the export and report pages
$export_users = preg_split('/[\n\,]+/', $export_users, 0, PREG_SPLIT_NO_EMPTY);

# Specified in RFC address format. One address per array element, please.
$notified_people = preg_split('/[\n\,]+/', $notified_people, 0, PREG_SPLIT_NO_EMPTY);

# Specify addesses that are not allowed in the additional notified people field.
$mail_blacklist = preg_split('/[\n\,]+/', $mail_blacklist, 0, PREG_SPLIT_NO_EMPTY);

# Set to "submitter" to mail on behalf of the person submitting the notification
$from = isset($mail_submitter) ? $mail_submitter : "submitter";

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
