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

function output_csv($data) {
  $correct_mime = FALSE;
  if (strpos($_SERVER["HTTP_ACCEPT"], "text/csv") !== FALSE ||
      $_GET["valid_mime"] == '1') {
    $correct_mime = TRUE;
  }
  
  header("Content-Type: ". ($correct_mime ? "text/csv" : "text/plain"));
  $f = fopen("php://output", 'w');
  $fields = array("id", "person", "added", "reason", "start", "end");
  $dates = array("added", "start", "end");
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
  print '<?xml version="1.0"?><AreYouKiddingMe/>';
  die;
}

