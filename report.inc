<?php

function generate_report($results, $from_time, $to_time) {
  $consolidation = array();
  foreach ($results as $row) {
    $person = $row["person"];
    if (!isset($consolidation[$person])) {
      $consolidation[$person] = array(
        "givenName" => $row["givenName"], "sn" => $row["sn"], "hours" => 0
      );
    }
    $consolidation[$person]["hours"] += $row["hours"];
  }
  ksort($consolidation);

  // Push back the date to the beginning of the year.
  $s = getdate($from_time);
  $months = array(NULL);
  for ($i = 1; $i < 13; $i++) {
    $months[] = mktime($s["hours"], $s["minutes"], $s["seconds"], $i, 1, $s["year"]);
  }
  $s = $months[1];


  $conditions = array();
  $filters = array();
  $filters[] = "((end BETWEEN $s AND $to_time) OR (start BETWEEN $s AND $to_time))";
  $filters[] = "(($s BETWEEN start AND end) AND ($to_time BETWEEN start and END))";
  $conditions[] = "(". implode(" OR ", $filters) .")";
  
  $people = array_keys($consolidation);
  $queried_people = array();
  foreach ($people as $person) {
    $queried_people[]= "'$person'";
  }
  $queried_people = implode(", ", $queried_people);
  $conditions[] = "(person IN ($queried_people))";
  $is_single_person = count($people) == 1;

  // Join all WHERE clauses
  $conditions = implode(" AND ", $conditions);
  if (!empty($conditions)) {
    $conditions = "WHERE $conditions ";
  }

  $fields = LIMITED_FIELDS;
  $query = mysql_query(
    "SELECT $fields FROM pto $conditions ORDER BY added DESC;"
  );
  $running = array();
  $results = array();
  global $user_cache;
  while ($row = mysql_fetch_assoc($query)) {
    $person = $row["person"];
    if (!isset($running[$person])) {
      // 0 is the sum, the rest are individual months.
      $running[$person] = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
    }
    $date = getdate($row["start"]);
    $running[$person][$date["mon"]] += $row["hours"];
    $running[$person][0] += $row["hours"];

    $key = $person;
    $row["sn"] = $user_cache[$key]["sn"][0];
    $row["givenName"] = $user_cache[$key]["givenname"][0];
    $row["location"] = $user_cache[$key]["physicaldeliveryofficename"][0];
    $results[] = $row;
  }

  // header("Content-Type: text/plain");
  header("Content-Type: text/csv; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"PTOs.csv\"");

  $f = fopen("php://output", 'w');
  $sep = ",";
  $fmt = "m/d/y";

  $range = array('Report range', date($fmt, $from_time), date($fmt, $to_time));
  fputcsv($f, $range, $sep);
  fputcsv($f, array(), $sep);
  $columns = array("First name", "Last name", "Hrs in range", "", "YTD hrs taken");
  for ($i = 1; $i < count($months); $i++) {
    $columns[] = date("M", $months[$i]);
  }
  fputcsv($f, $columns, $sep);

  foreach ($people as $person) {
    fputcsv($f, array_merge(array(
      $consolidation[$person]["givenName"], $consolidation[$person]["sn"],
      $consolidation[$person]["hours"], ""
    ), $running[$person]), $sep);
  }

  if ($is_single_person) {
    fputcsv($f, array(), $sep);
    output_csv($results, $from_time, $to_time, FALSE);
  }
}

