<?php
require("prefetch.inc");

// Try the specified format first
if (isset($_GET["format"]) && $_GET["format"] == "csv") {
  require("report.inc");

  if (!$from_time || !$to_time) {
    die("A time range must be specified.");
  }
  generate_report($results, $from_time, $to_time);
  die;
} elseif (!isset($_GET["format"])) {
  // Don't do anything. Fall through and show pretty HTML UI.
} else {
  // Format not supported
  header("HTTP/1.1 400 Bad Request");
  die;
}

require_once "./templates/header.php";
?>
  <p>
    A report, only available in CSV format, is a more macro overview of PTOs 
    entries filed by employees. This type of report should be generated for 
    the time span of two weeks, which is the frequency at which pay days occur.
    The start and end of this time span should always be in the same year.
  </p>
  <ul id="views">
    <li id="range">
      For date from 
      <input type="text" id="from" size="10" value="yyyy/mm/dd" /> to 
      <input type="text" id="to" size="10" value="yyyy/mm/dd" />
      <button id="generate">Generate Report</button>
      <!--<span id="loading">Loading...</span>-->
    </li>
  </ul>

<script>
jQuery.noConflict();
(function($) {
  Number.prototype.toTimestamp = function() {
    return Math.round(this.valueOf() / 1000);
  };
  
  $(document).ready(function() {
    $('#generate').click(function() {
      var from = Date.parse($("#from").val());
      var to = Date.parse($("#to").val());
      isNaN(to) && $("#to").focus();
      isNaN(from) && $("#from").focus();
      if (isNaN(from) || isNaN(to)) {
        return;
      }
      from = from.toTimestamp();
      to = to.toTimestamp();
      window.location = '?format=csv&from=' + from + '&to=' + to;
    });
  });
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
  ul#views {
    -moz-border-radius: 0.5em;
  }
</style>

<?php require_once "./templates/footer.php"; ?>
