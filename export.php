<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");

require_once("output.inc");

$c = mysql_connect($mysql["host"], $mysql["user"], $mysql["password"]);
mysql_select_db($mysql["database"]);

require_once("filtering.inc");

$notifier_email = $_SERVER["PHP_AUTH_USER"];
$data = ldap_find($connection, "mail=". $notifier_email, array("manager"));
$notifier_name = ldap_fullname($data[0]);

$manager_dn = $data[0]["manager"][0];
// "OMG, not querying LDAP for the real email? That's cheating!"
preg_match("/mail=([a-z]+@mozilla\\.com),/", $manager_dn, $matches);
$manager_email = $matches[1];
$is_hr = in_array($manager_email, $hr_managers);
// Exclude details from non-HR personnel
$fields = $is_hr ? '*' : "id, person, added, hours, start, end";

$query = mysql_query(
  "SELECT ". $fields ." FROM pto ". $conditions ."ORDER BY added DESC;"
);
$results = array();
while ($row = mysql_fetch_assoc($query)) {
  foreach (array("id", "added", "start", "end") as $field) {
    $row[$field] = (int)$row[$field];
  }
  $results[] = $row;
}

// Try the specified format first
$output_function = "output_". $_GET["format"];
if (function_exists($output_function)){
  call_user_func($output_function, $results);
} elseif (!isset($_GET["format"])) {
  // Don't do anything. Fall through to exporting as pretty HTML.
} else {
  // Format not supported
  header("HTTP/1.1 400 Bad Request");
  die;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">
  <head>
    <title>PTO Notifications<title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <script src="./js/jquery-1.3.2.min.js" type="text/javascript"></script>
    <script src="./js/jquery.tablesorter.js" type="text/javascript"></script>
    <script src="./js/jquery.strftime-minified.js" type="text/javascript"></script>
    <script src="./js/jquery-ui-1.7.2.custom.min.js" type="text/javascript"></script>
    <script src="./js/jquery.cookie.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="./css/style.css"/>
    <link rel="stylesheet" type="text/css" href="./css/redmond/jquery-ui-1.7.2.custom.css"/>
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" /> 

    <script type='text/javascript'>
    jQuery.noConflict();
    (function($) {
      $(document).ready(function() {
        $("#view-all").click(function() fetch()).click(); // Fire "View All"

        $("#view-today").click(function() {
          var [from, to] = [new Date(), new Date()];
          zero({from: from, to: to});
          to = to.valueOf() + (1000 * 60 * 60 * 24);
          fetch({from: from, to: to});
        });

        $("#view-week").click(function() {
          var [from, to] = [new Date(), new Date()];
          zero({from: from, to: to});
          // The midnight between Sunday and Monday is the cutoff.
          // getDay() returns 0 for Sunday, 1 for Monday, etc.
          from = from.valueOf() - (1000 * 60 * 60 * 24 * (from.getDay() - 1));
          to = to.valueOf() + (1000 * 60 * 60 * 24 * (7 - (to.getDay() - 1)));
          fetch({from: from, to: to});
        });

        $("#view-month").click(function() {
          var [from, to] = [new Date(), new Date()];
          zero({from: from, to: to, methods: ["Date"]});
          to.setMonth(to.getMonth() + 1);
          fetch({from: from, to: to});
        });

        $("#view-year").click(function() {
          var [from, to] = [new Date(), new Date()];
          zero({from: from, to: to, methods: ["Date", "Month"]});
          to.setFullYear(to.getFullYear() + 1);
          fetch({from: from, to: to});
        });
      });

      
      function zero(opts) {
        opts.methods = opts.methods || [];
        var methods = "Hours|Minutes|Seconds|Milliseconds".split('|');
        methods.concat.apply(methods, opts.methods).forEach(function(method) {
          if (opts.from) { opts.from["set" + method](0); }
          if (opts.to) { opts.to["set" + method](0); }
        });
      }
          
      function fetch(options) {
        options = options || {};
        if (options.from) {
          options.from = Math.floor(options.from.valueOf() / 1000);
        }
        if (options.to) {
          options.to = Math.floor(options.to.valueOf() / 1000);
        }
        $.getJSON("export.php", $.extend({format: "json"}, options), inject);
      }

      function inject(data) {
        var preferredOrder = "id|person|added|hours|start|end|details".split('|');
        var fieldNames = {
          id: "ID", person: "Who", added: "Added on", hours: "Hours",
          start: "Start", end: "End", details: "Details"
        };
        var presentFields = [];
        for (var field in data[0]) { presentFields.push(field); }
        var fields = [];
        preferredOrder.forEach(function(field) {
          if (presentFields.indexOf(field) != -1) { fields.push(field); }
        });

        var fdate = function(x) {
          return $.strftime({format: '%Y-%m-%d', dateTime: new Date(x * 1000)});
        };
        
        var K = function(x) { return x; };
        var formatters = {
          id: K, person: function(x) x.replace(/@mozilla\.com$/, ''), hours: K,
          added: fdate, start: fdate, end: fdate, details: K
        };

        $("#pto table").remove();

        var code = ["<table><thead><tr>"];
        fields.forEach(function(field) {
          code.push("<th>" + fieldNames[field] + "</th>");
        });
        code.push("</tr></thead><tbody></tbody></table>");
        
        $(code.join('')).appendTo("#pto");
        code = [];

        data.forEach(function(e) {
          code.push("<tr>");
          fields.forEach(function(field) {
            code.push("<td>" + formatters[field](e[field]) + "</td>")
          });
          code.push("</tr>");
        });

        if (data.length == 0) {
          code.push('<tr><td colspan="6" id="no-match">No matching data.</td></tr>');
        }

        $("#pto tbody").html(code.join(''));
        if (data.length != 0) {
          $("#pto table").tablesorter({ sortList: [[0, 1]] });
        }
      }

    })(jQuery);
</script>
</head>

<body>
<h1>PTO Notifications</h1>
<p>Herro thar, <?= str_replace("@mozilla.com", '', $notifier_email) ?>.</p>
<ul id="views">
<li><a id="view-all">All</a></li>
<li><a id="view-today">Today</a></li>
<li><a id="view-week">This Week</a></li>
<li><a id="view-month">This Month</a></li>
<li><a id="view-year">This Year</a></li>
</ul>
<div id="pto"><div>
</body>
</html>
