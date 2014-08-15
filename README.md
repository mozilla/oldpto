Old PTO
=======
Mozilla's old PTO app. Though I prefer the term "vintage".


Libraries Used
--------------

* [jQuery](http://jquery.com/), license: http://docs.jquery.com/Licensing
* [jQuery UI](http://jqueryui.com/), license: http://jqueryui.com/about
* [FirePHP](http://firephp.org/), license: http://www.firephp.org/Wiki/Main/License

Installation
------------

* copy config-dist.php to config.php
* import schema.sql into your own database
* copy config-dist.php to config.php and fill in the blanks

Note: The app requires LDAP server access. You probably need a VPN connection up and running.

LDAP Assumptions
----------------

* ``manager`` field contains a dn pointing to manager's record
* everyone has a `manager`, with the exception of known tree roots such as the CEO
* ``/^.*@mozilla.*$/`` can match everyone's email address

Contributing
------------

If you feel so inclined, feel free to contribute:

* Bugs in Bugzilla: [Webtools :: PTO](https://bugzilla.mozilla.org/buglist.cgi?component=PTO&product=Webtools&resolution=---)
* Code via github pull request
* Empathy via any appropriate medium.
