<?php
$ldap = array(
  "host" => "pm-ns.mozilla.org",
  "port" => 389
);
$connection = null;

# Fill out MySQL server info.
$mysql = array(
  "host" => "",
  "user" => "",
  "password" => "",
  "database" => ""
);

$LDAP_BIND_USER = 'id=username@domain.com,ou=logins,dc=foo';
$LDAP_BIND_PASS = 'LDAPBINDPASS';

// No longer rely on PHP_AUTH_USER blindly
// $_SERVER['OIDC_CLAIM_email'] would work for Open ID Connect
$GLOBAL_AUTH_USERNAME = $_SERVER['PHP_AUTH_USER'];

# Set the constants below to FALSE to do various debugging.
define("ENABLE_MAIL", TRUE);
define("ENABLE_DB", TRUE);
define("ENABLE_MANAGER_NOTIFYING", TRUE);

# Set below to TRUE to see MySQL error messages and query strings sent.
define("DEBUG_ON", FALSE);

# Specify HR managers with email addresses only.
$hr_managers = array(
  //"hrmanager@example.com",
);

# Only these people are able to view the export and report pages
$export_users = array(
  //"payrollperson@example.com",
);

# Specified in RFC address format. One address per array element, please.
$notified_people = array(
  //"John Doe <johndoe@example.com>"
);

# Specify addesses that aren't allowed in the additional notified people field.
$mail_blacklist = array(
  //"entirecompany@example.com",
);

# Set to "submitter" to mail on behalf of the person submitting the notification
$from = "submitter";

# The following template variables are available:
# %id%, %notifier%, %editor%, %hours%, %start%, %end%, %details%
$subject = "PTO: %notifier%";
$edit_subject = "PTO Edit: %editor%";
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

