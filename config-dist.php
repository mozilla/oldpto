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

# Set the constants below to TRUE to avoid spamming / db junk.
define("DISABLE_MAIL", FALSE);
define("DISABLE_DB", FALSE);

# Set below to TRUE to see MySQL error messages and query strings sent.
define("DEBUG_ON", FALSE);

# Specify HR managers with email addresses only.
$hr_managers = array(
  "dportillo@mozilla.com",
  "karen@mozilla.com"
);

# Specified in RFC address format. One address per array element, please.
$notified_people = array(
  "Karen Prescott <karen@mozilla.com>",
  "Erica McClure <emcclure@mozilla.com>"
);

# Specify addesses that aren't allowed in the additional notified people field.
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

