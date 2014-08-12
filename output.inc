<?php

function output_json($data, $from, $to, $send_headers=TRUE) {
	$correct_mime = TRUE;
	if ($send_headers) {
		header("Content-Type: ". ($correct_mime ? "application/json" : "application/javascript"));
	}
	if (isset($_GET['user']) && $_GET['user']) {
		$sUserEmail = $_GET['user'];
	}
	
	// Format for dataTable
	$aFormattedData = array();
	foreach ($data as $aRecord) {
		$sEditLink = ($aRecord['email'] == $sUserEmail
			? '<a href="./edit.php?id='.$aRecord['id'].'">Edit</a>'
			: ''
		);
		
		$sViewHoursDailyLink = (strlen($aRecord['hours_daily']) > 3 
			? '<a href="javascript:viewHoursDaily(\''.urlencode($aRecord['hours_daily']).'\')">More</a>'
			: ''
		);
		
		$aFormattedData[] = array(
			$aRecord['email'],
			$aRecord['id'],
			$aRecord['first_name'],
			$aRecord['last_name'],
			date('Y-m-d', $aRecord['filed_date']),
			$aRecord['pto_hours'],
			date('Y-m-d', $aRecord['start_date']),
			date('Y-m-d', $aRecord['end_date']),
			str_replace(' ', '&nbsp;', $aRecord['city']), 
			$aRecord['country'],
			$aRecord['details'],
			$sEditLink,
			$sViewHoursDailyLink
		);
	}
	print json_encode(array('aaData'=>$aFormattedData));
	
	exit;
}

function output_sql($data, $from, $to, $send_headers=TRUE) {
  if ($send_headers) {
    header("Content-Type: text/plain");
  }
  print "INSERT INTO `pto` (`id`, `person`, `added`, `hours`, `details`, `start`, `end`) VALUES\n";
  $buffer = array();
  foreach ($data as $e) {
    if (!isset($e["details"])) {
      $e["details"] = '';
    }
    $buffer[] = sprintf("(%d, '%s', %d, %F, '%s', %d, %d)",
                        $e["id"], $e["email"], $e["filed_date"], $e["pto_hours"],
                        $e["details"], $e["start_date"], $e["end_date"]);
  }
  print implode(", \n", $buffer);
  print ";";
  die;
}

function output_csv($data, $from, $to, $send_headers=TRUE) {
	if (!isset($data[0])) {
		print "No information matches your query";
		die();
	}
	
	if ($send_headers) {
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=\"PTOs.csv\"");
	}
	$aFields = array(
		'Id',
		'Email',
		'Start Date',
		'End Date',
		'Filed Date',
		'PTO Hours',
		'Details'
	);
	$sFields = '';
	for ($n=0; $n<count($aFields); $n++) {
		$aFields[$n] = ucwords(str_replace('_',' ',$aFields[$n]));
	}
	print implode(', ', $aFields)."\n";
	foreach ($data as $aRecord) {
		$aFormattedRecord = array();
		foreach ($aRecord as $sKey=>$sValue) {
			switch ($sKey) {
				case 'start_date':
				case 'end_date':
				case 'filed_date':
					$aFormattedRecord[] = '"'.date('Y-m-d',$sValue).'"';
					break;
				default:
					$aFormattedRecord[] = '"'.$sValue.'"';
			}
		}
		print implode(',', $aFormattedRecord)."\n";
	} 
  die;
}

function output_xml($data, $from, $to, $send_headers=TRUE) {
  if ($send_headers) {
    header("Content-Type: text/xml");
  }
  print '<?xml version="1.0"?><AreYouKiddingMe><WhateverHappenedToAtom/></AreYouKiddingMe>';
  die;
}

function output_atom($data, $from, $to, $send_headers=TRUE) {
  $uuid = "e2f436c4-791d-4045-a6e5-52e981bb3db7";
  $protocol = $_SERVER["HTTPS"] ? "https" : "http";
  $path = $protocol ."://". $_SERVER["HTTP_HOST"];
  $self = $path . $_SERVER["REQUEST_URI"];
  $document = $path . $_SERVER["PHP_SELF"];
  if ($send_headers) {
    header("Content-Type: application/atom+xml");
  }
  print '<?xml version="1.0" encoding="utf-8"?>';
  print '<feed xmlns="http://www.w3.org/2005/Atom">';
  print "<title>PTO Search</title>";
  print "<id>url:uuid:$uuid</id>";
  print '<link rel="self" href="'. $self .'" />';
  print "<updated>". date(DATE_ATOM) ."</updated>";
  print "<author><name>Mozilla PTO</name></author>";
  foreach ($data as $pto) {
    $fn = $pto["first_name"];
    $sn = $pto["last_name"];
    $start = date("m/d/y", $pto["start_date"]);
    $end = date("m/d/y", $pto["end_date"]);
    $range = ($start == $end) ? "on $start" : "from $start to $end";
    print "<entry>";
      print "<title>$fn $sn $range</title>";
      print "<id>$document?id={$pto["id"]}</id>";
      print "<link href=\"$document?id={$pto["id"]}\" />";
      print "<author><name>$fn $sn</name><email>{$pto['email']}</email></author>";
      print "<updated>". date(DATE_ATOM, $pto["filed_date"]) ."</updated>";
      print "<summary>". htmlspecialchars($pto["details"]) ."</summary>";
    print "</entry>";
  }
  print '</feed>';
  die;
}

function output_ics($data, $from, $to, $send_headers=TRUE) {
  output_ical($data, $from, $to, $send_headers);
}

function fold($string, $octets=75, $whitespace=' ') {
  $results = ""; $length = strlen($string);
  for ($i = 0; $i <= $length; $i += $octets) {
    $results .= substr($string, $i, $octets) ."\r\n". $whitespace;
  }
  return rtrim($results);
}

function output_ical($data, $from, $to, $send_headers=TRUE) {
  if ($send_headers) {
    header("Content-Type: text/calendar");
    header("Content-Disposition: attachment; filename=\"PTOs.ics\"");
  }
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
    $fn = $pto["first_name"];
    $sn = $pto["last_name"];
    # $pto["person"] = email_to_alias($pto["person"]);
    // Line breaks and commas must be escaped.
    $pto["details"] = str_replace(", ", '\, ', $pto["details"]);
    $pto["details"] = str_replace("\n", '\n', $pto["details"]);
    print "BEGIN:VEVENT\n";
    print "SEQUENCE:0\n";
    print empty($pto["details"]) ? '' : "DESCRIPTION:{$pto['details']}\n";
    // Outlook 2003 requires both UID and DTSTAMP.
    print "UID:{$pto['id']}\n";
    print "TRANSP:OPAQUE\n";
    print "DTSTART:". date($format, $pto["start_date"]) ."\n";
    print "DTSTAMP:". date($format, $pto["field_date"]) ."\n";
    print "SUMMARY:PTO of $fn $sn\n";
    print "CREATED:". date($format, $pto["filed_date"]) ."\n";
    print "DTEND:". date($format, $pto["end_date"]) ."\n";
    print "END:VEVENT\n";
  }
  print "END:VCALENDAR";
  // Per RFC 2445 Section 4.1, lines longer than 75 octets should be folded.
  $out = explode("\n", ob_get_clean());
  print implode("\r\n", array_map("fold", $out));
  die;
}

