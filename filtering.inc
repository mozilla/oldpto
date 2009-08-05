<?php
function safeint($str) {
  return (string)((int)$str);
}

$conditions = array();

// Strict start time / end time filtering
if (!empty($_GET["start"])) {
  $conditions[] = "start <= ". safeint($_GET["start"]);
}
if (!empty($_GET["end"])) {
  $conditions[] = "end >= ". safeint($_GET["end"]);
}

// Actual, useful `from` / `to` querying
$from_time = NULL;
$to_time = NULL;
if (!empty($_GET["from"])) {
  $from_time = safeint($_GET["from"]);
}
if (!empty($_GET["to"])) {
  $to_time = safeint($_GET["to"]);
}
if ($from_time || $to_time) {
  if (!$from_time) { $from_time = 0; }
  if (!$to_time) { $to_time = time(); }
  $filters = array();
  $filters[] = "((end BETWEEN ". $from_time ." AND ". $to_time .") OR (start BETWEEN ". $from_time ." AND ". $to_time ."))";
  $filters[] = "((". $from_time ." BETWEEN start AND end) AND (". $to_time ." BETWEEN start and END))";
  $conditions[] = implode(" OR ", $filters);
}

// Person filtering / querying
if (!empty($_GET["person"])) {
  $conditions[] = 'person LIKE "%'. htmlspecialchars($_GET["person"], ENT_QUOTES) .'%mozilla%"';
}

// ID overrides everything else
if (!empty($_GET["id"])) {
  $conditions = array("id = " . (int)$_GET["id"]);
}

// Join all WHERE clauses
$conditions = implode(" AND ", $conditions);
if (!empty($conditions)) {
  $conditions = "WHERE ". $conditions .' ';
}
