<?php
$ldap = array(
  "host" => "",
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

# Set the constants below to FALSE to do various debugging.
define("ENABLE_MAIL", FALSE);
define("ENABLE_DB", TRUE);
define("ENABLE_MANAGER_NOTIFYING", TRUE);

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
