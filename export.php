<?php
require_once("config.php");
require_once("pto.inc");
require_once("auth.php");

require_once("output.inc");

$c = mysql_connect($mysql["host"], $mysql["user"], $mysql["password"]);
mysql_select_db($mysql["database"]);

require_once("filtering.inc");

$notifier_email = $_SERVER["PHP_AUTH_USER"];
$data = ldap_find($connection, "mail=". $notifier_email, array("manager", "cn"));
$notifier_name = $data[0]["cn"][0];

$manager_dn = $data[0]["manager"][0];
// "OMG, not querying LDAP for the real email? That's cheating!"
preg_match("/mail=([a-z]+@mozilla.*),o=/", $manager_dn, $matches);
$manager_email = $matches[1];
$is_hr = in_array($manager_email, $hr_managers);
// Exclude details from non-HR personnel
$fields = $is_hr ? '*' : "id, person, added, hours, start, end";

$query = mysql_query(
  "SELECT ". $fields ." FROM pto ". $conditions ."ORDER BY added DESC;"
);


$user_cache = array();
$search = ldap_search(
  $connection, "o=com,dc=mozilla", "mail=*",
  array("mail", "givenName", "sn", "physicalDeliveryOfficeName")
);
$match = ldap_get_entries($connection, $search);
for ($i = 0; $i < $match["count"]; $i++) {
  $row = $match[$i];
  $user_cache[$row["mail"][0]] = $row;
}

$results = array();
while ($row = mysql_fetch_assoc($query)) {
  foreach (array("id", "added", "start", "end") as $field) {
    $row[$field] = (int)$row[$field];
  }
  $row["hours"] = (double)$row["hours"];

  $key = $row["person"];
  $row["sn"] = $user_cache[$key]["sn"][0];
  $row["givenName"] = $user_cache[$key]["givenname"][0];
  $row["location"] = $user_cache[$key]["physicaldeliveryofficename"][0];

  $results[] = $row;
}

// Try the specified format first
$output_function = "output_". $_GET["format"];
if (function_exists($output_function)){
  call_user_func($output_function, $results, $from_time, $to_time);
} elseif (!isset($_GET["format"])) {
  // Don't do anything. Fall through to exporting as pretty HTML.
} else {
  // Format not supported
  header("HTTP/1.1 400 Bad Request");
  die;
}

require_once "./templates/header.php";
?>
  <p>Herro thar, <span id="user"><?= email_to_alias($notifier_email) ?></span>.
     We've got all your PTOs right here™.</p>
  <ul id="views">
    <li class="view"><a id="view-year">This Year</a></li>
    <li class="view"><a id="view-month">This Month</a></li>
    <li class="view"><a id="view-week">This Week</a></li>
    <li class="view"><a id="view-today">Today</a></li>
    <li class="view"><a id="view-all">All</a></li>
    <li id="range"><input type="text" id="from" size="10" /> -
                   <input type="text" id="to" size="10" />
                   <button id="filter">Filter</button>
                   <span id="loading">Loading...</span></li>
  </ul>
  <div id="formats">
    Formats:
    <ul>
    <li class="active" title="You're lookin' at it">Table</li>
    <li><a class="format" href="?format=csv" id="format-csv" title="Good for spreadsheet software">CSV / Excel</a></li>
    <li><a class="format" href="?format=atom" id="format-atom" title="Good for feed readers">Atom</a></li>
    <li><a class="format" href="?format=ical" id="format-ical" title="Good for calendar apps">iCal</a></li>
    <li><a class="format" href="?format=json" id="format-json" title="Good for mash-ups">JSON</a></li>
    <li><a class="format" href="?format=sql" id="format-sql" title="Good for importing test data">SQL</a></li>
    </ul>
  </div>
  <div id="pto"></div>

  <script type="text/javascript" src="./js/jquery.strftime-minified.js"></script>
  <script type="text/javascript" src="./js/jquery.tablesorter.js"></script>
  <script type="text/javascript">
  window.isHR = <?= json_encode($is_hr) ?>;
  jQuery.noConflict();
  (function($) {
    Number.prototype.toTimestamp = function() {
      return Math.round(this.valueOf() / 1000);
    };

    $(document).ready(function() {
      $("#loading").ajaxStart(function() {
        $(this).addClass("loading");
      }).ajaxStop(function() {
        $(this).removeClass("loading");
      });

      $("#filter").click(function() {
        fire({
          from: Date.parse($("#from").val()).toTimestamp(),
          to: Date.parse($("#to").val()).toTimestamp()
        });
      });
      $("#from, #to").keypress(function(e) {
        if (e.which == 13) {
          $("#filter").click();
        }
      });

      $("#view-all").click(function() { fetch(); });

      $("#view-today").click(function() {
        var d = makeZeroedDates();
        var from = d[0], to = d[1];
        to = to.valueOf() + (1000 * 60 * 60 * 24);
        fetch({from: from, to: to});
      });

      $("#view-week").click(function() {
        var d = makeZeroedDates();
        var from = d[0], to = d[1];
        // The midnight between Sunday and Monday is the cutoff.
        // getDay() returns 0 for Sunday, 1 for Monday, etc.
        from = from.valueOf() - (1000 * 60 * 60 * 24 * (from.getDay() - 1));
        to = to.valueOf() + (1000 * 60 * 60 * 24 * (7 - (to.getDay() - 1)));
        fetch({from: from, to: to});
      });

      $("#view-month").click(function() {
        var d = makeZeroedDates({methods: ["Date"]});
        var from = d[0], to = d[1];
        to.setMonth(to.getMonth() + 1);
        fetch({from: from, to: to});
      });

      $("#view-year").click(function() {
        var d = makeZeroedDates({methods: ["Date", "Month"]});
        var from = d[0], to = d[1];
        to.setFullYear(to.getFullYear() + 1);
        fetch({from: from, to: to});
      });

      var match;
      if (match = window.location.search.match(/^\?id=(\d+)/)) {
        fetch({id: match[1]});
      } else if (window.location.hash != "") {
        var opts = {};
        $.each(window.location.hash.substring(1).split('&'), function() {
          var pair = this.split('=');
          var k = pair[0], v = pair.slice(1).join('=');
          if (!opts[k]) {
            opts[k] = v;
          } else if (opts[k] && !$.isArray(opts[k])) {
            opts[k] = [opts[k]];
            opts[k].push(v);
          } else {
            opts[k].push(v);
          }
        });
        opts.from && (opts.from += "000");
        opts.to && (opts.to += "000");
        fetch("all" in opts ? {} : opts);
      } else {
        $("#view-month").click(); // Fire "View This Month"
      }
    });

    function makeZeroedDates(opts) {
      opts = opts || {};
      opts.from = opts.from || new Date();
      opts.to = opts.to || new Date();
      opts.methods = opts.methods || [];
      var methods = "Hours|Minutes|Seconds|Milliseconds".split('|');
      methods.concat.apply(methods, opts.methods).forEach(function(method) {
        var val = (method == "Date") ? 1 : 0;
        if (opts.from) { opts.from["set" + method](val); }
        if (opts.to) { opts.to["set" + method](val); }
      });
      return [opts.from, opts.to];
    }

    function fetch(options) {
      options = options || {};
      if (options.from) {
        options.from = Math.round(options.from.valueOf() / 1000);
      }
      if (options.to) {
        options.to = Math.round(options.to.valueOf() / 1000);
      }
      var from = options.from ? fdate(options.from) : '';
      var to = options.to ? fdate(options.to) : '';
      $("#from").val(from);
      $("#to").val(to);
      fire(options);
    }

    function fire(options) {
      var opts = $.param(options);
      $("#formats a.format").each(function() {
        var url = "?format=" + $(this).attr("id").replace(/^format-/, '');
        $(this).attr("href", url + (opts ? '&' + opts : ''));
      });
      window.location.hash = (opts == "") ? "all" : opts;
      $.getJSON("export.php", $.extend({format: "json"}, options), inject);
    }

    function fdate(x) {
      return $.strftime({format: '%Y/%m/%d', dateTime: new Date(x * 1000)});
    };

    function inject(data) {
      var preferredOrder = "id|givenName|sn|added|hours|start|end|location|details".split('|');
      var fieldNames = {
        id: "ID", givenName: "First name", sn: "Last name", added: "Date filed",
        hours: "Hours", start: "Start", end: "End", details: "Details",
        location: "Location"
      };
      var presentFields = [];
      for (var field in data[0]) { presentFields.push(field); }
      var fields = [];
      preferredOrder.forEach(function(field) {
        if (presentFields.indexOf(field) != -1) { fields.push(field); }
      });

      var K = function(x) { return x; };
      var NA = function(x) { return x ? x : "<em>N/A</em>"; };
      var formatters = {
        id: K, person: function(x) { return x.replace(/@mozilla.*$/, ''); },
        hours: K, added: fdate, start: fdate, end: fdate, details: K,
        givenName: NA, sn: NA, location: function(s) {
          return !s ? NA(s) : s.replace(':::', '/');
        }
      };

      $("#pto table").remove();

      var code = ["<table><thead><tr>"];
      fields.forEach(function(field) {
        code.push("<th>" + fieldNames[field] + "</th>");
      });
      if (data.length != 0) {
        // Add action header
        code.push('<th class="action">Action</th>');
      }
      code.push("</tr></thead><tbody></tbody></table>");

      $(code.join('')).appendTo("#pto");
      code = [];

      var user = $("#user").html();
      data.forEach(function(e) {
        code.push("<tr>");
        fields.forEach(function(field) {
          code.push("<td>" + formatters[field](e[field]) + "</td>")
        });
        // Add edit field
        code.push('<td class="action">');
        if (window.isHR || formatters.person(e.person) == user) {
          code.push('<a href="./edit.php?id=' + e.id + '">Edit</a>');
        }
        code.push("</td>");
        code.push("</tr>");
      });

      if (data.length == 0) {
        code.push(
          '<tr><td colspan="' + preferredOrder.length + 
          '" id="no-match">No matching data.</td></tr>'
        );
      }

      $("#pto tbody").html(code.join(''));
      if (data.length != 0) {
        opts = {sortList: [[0, 1]], headers: {}};
        opts.headers[presentFields.length] = {sorter: false};
        $("#pto table").tablesorter(opts);
      }
    }

  })(jQuery);
</script>
<style type="text/css">
  section {
    -moz-border-radius: none;
    background-color: transparent;
    margin-top: 0;
    padding: 0;
  }
  section p {
    -moz-border-radius: 0.5em;
    background-color: white;
    margin-top: 1em;
    padding: 1em;
  }
</style>

<?php require_once "./templates/footer.php"; ?>
