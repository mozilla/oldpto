<?php
function output_json($data) {
  $correct_mime = FALSE;
  if (strpos($_SERVER["HTTP_ACCEPT"], "application/json") !== FALSE ||
      $_GET["valid_mime"] == '1') {
    $correct_mime = TRUE;
  }
  
  header("Content-Type: ". ($correct_mime ? "application/json" : "application/javascript"));
  print json_encode($data);
  die;
}

function output_sql($data) {
  header("Content-Type: text/plain");
  print "INSERT INTO `pto` (`id`, `person`, `added`, `hours`, `details`, `start`, `end`) VALUES\n";
  $buffer = array();
  foreach ($data as $e) {
    if (!isset($e["details"])) {
      $e["details"] = '';
    }
    $buffer[] = sprintf("(%d, '%s', %d, %F, '%s', %d, %d)",
                        $e["id"], $e["person"], $e["added"], $e["hours"],
                        $e["details"], $e["start"], $e["end"]);
  }
  print implode(", \n", $buffer);
  print ";";
  die;
}

function output_csv($data) {
  $correct_mime = TRUE;
  if ($_GET["as_text"] == '1') {
    $correct_mime = FALSE;
  }
  
  header("Content-Type: ". ($correct_mime ? "text/csv" : "text/plain"));
  if ($correct_mime) {
    header("Content-Disposition: attachment; filename=\"PTOs.csv\"");
  }
  $f = fopen("php://output", 'w');
  $fields = array("id", "person", "added", "hours", "details", "start", "end");
  fputcsv($f, array_map("ucwords", $fields));
  foreach ($data as $pto) {
    $row = array();
    foreach ($fields as $field) {
      // Format the timestamp as an Excel date
      if (in_array($field, array("added", "start", "end"))) {
        $pto[$field] = date("m/d/y", $pto[$field]);
      }
      $row[] = $pto[$field];
    }
    fputcsv($f, $row);
  }
  fclose($f);
  die;
}

function output_xml($data) {
  header("Content-Type: text/xml");
  print '<?xml version="1.0"?><AreYouKiddingMe><WhateverHappenedToAtom/></AreYouKiddingMe>';
  die;
}

function output_atom($data) {
  $uuid = "e2f436c4-791d-4045-a6e5-52e981bb3db7";
  $protocol = $_SERVER["HTTPS"] ? "https" : "http";
  $path = $protocol ."://". $_SERVER["HTTP_HOST"];
  $self = $path . $_SERVER["REQUEST_URI"];
  $document = $path . $_SERVER["PHP_SELF"];
  header("Content-Type: application/atom+xml");
  print '<?xml version="1.0" encoding="utf-8"?>';
  print '<feed xmlns="http://www.w3.org/2005/Atom">';
  print "<title>PTO Search</title>";
  print "<id>url:uuid:$uuid</id>";
  print '<link rel="self" href="'. $self .'" />';
  print "<updated>". date(DATE_ATOM) ."</updated>";
  print "<author><name>Mozilla PTO</name></author>";
  foreach ($data as $pto) {
    $pto["email"] = $pto["person"];
    $pto["person"] = email_to_alias($pto["person"]);
    $start = date("m/d/y", $pto["start"]);
    $end = date("m/d/y", $pto["end"]);
    $range = ($start == $end) ? "on $start" : "from $start to $end";
    print "<entry>";
      print "<title>{$pto['person']} $range</title>";
      print "<id>$document?id={$pto["id"]}</id>";
      print "<link href=\"$document?id={$pto["id"]}\" />";
      print "<author><name>{$pto['person']}</name><email>{$pto['email']}</email></author>";
      print "<updated>". date(DATE_ATOM, $pto["added"]) ."</updated>";
      print "<summary>". htmlspecialchars($pto["details"]) ."</summary>";
    print "</entry>";
  }
  print '</feed>';
  die;
}

function output_ics($data) {
  output_ical($data);
}

function fold($string, $octets=75, $whitespace=' ') {
  $results = ""; $length = strlen($string);
  for ($i = 0; $i <= $length; $i += $octets) {
    $results .= substr($string, $i, $octets) ."\r\n". $whitespace;
  }
  return rtrim($results);
}

function output_ical($data) {
  header("Content-Type: text/calendar");
  header("Content-Disposition: attachment; filename=\"PTOs.ics\"");
  $format = 'Ymd\THis\Z';
  ob_start();
  print <<<EOD
BEGIN:VCALENDAR
METHOD:PUBLISH
X-WR-TIMEZONE:US/Pacific
VERSION:2.0
PRODID:-//Mozilla//PTO//EN
CALSCALE:GEORGIAN
X-WR-CALNAME:PTOs

EOD;
  foreach ($data as $pto) {
    $pto["person"] = email_to_alias($pto["person"]);
    // Line breaks and commas must be escaped.
    $pto["details"] = str_replace(", ", '\, ', $pto["details"]);
    $pto["details"] = str_replace("\n", '\n', $pto["details"]);
    print "BEGIN:VEVENT\n";
    print "SEQUENCE:0\n";
    print empty($pto["details"]) ? '' : "DESCRIPTION:{$pto['details']}\n";
    // Outlook 2003 requires both UID and DTSTAMP.
    print "UID:{$pto['id']}\n";
    print "TRANSP:OPAQUE\n";
    print "DTSTART:". date($format, $pto["start"]) ."\n";
    print "DTSTAMP:". date($format, $pto["added"]) ."\n";
    print "SUMMARY:PTO of {$pto['person']}\n";
    print "CREATED:". date($format, $pto["added"]) ."\n";
    print "DTEND:". date($format, $pto["end"]) ."\n";
    print "END:VEVENT\n";
  }
  print "END:VCALENDAR";
  // Per RFC 2445 Section 4.1, lines longer than 75 octets should be folded.
  $out = explode("\n", ob_get_clean());
  print implode("\r\n", array_map("fold", $out));
  die;
}

