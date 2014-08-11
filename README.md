Libraries Used
--------------

* [jQuery](http://jquery.com/), license: http://docs.jquery.com/Licensing
* [jQuery UI](http://jqueryui.com/), license: http://jqueryui.com/about
* [FirePHP](http://firephp.org/), license: http://www.firephp.org/Wiki/Main/License

Installation
------------

* copy config-dist.php to config.php
* import schema.sql into your own database
* fill in the blanks in config.php

Note: The app requires LDAP server access. You probably need a VPN connection up and running.

LDAP Assumptions
----------------

* `manager` field contains a dn pointing to manager's record
* everyone has a `manager` (not true for mitchell and lilly)
* /^.*@mozilla.*$/ can match everyone's email address
