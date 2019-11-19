<?php

require_once('config.php');
require_once('auth.php');

function pretty_die() {
    global $GLOBAL_AUTH_USERNAME;
    include './templates/header.php';
    echo 'There was a problem getting your PTO records. Please try again later.';
    include './templates/footer.php';
    exit;
}

$me = $GLOBAL_AUTH_USERNAME;

$conn =  @mysql_connect($mysql['host'], $mysql['user'], $mysql['password']) 
             or pretty_die();

@mysql_select_db($mysql['database'], $conn) or pretty_die();

$query = "select
              added,
              start,
              end,
              details
          from pto
          where person = '".mysql_real_escape_string($me, $conn)."'
          order by start desc";

$result = @mysql_query($query, $conn) or pretty_die();

$mypto = array();
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $row['added'] = date('D, d M Y', $row['added']);
    $row['start'] = date('D, d M Y', $row['start']);
    $row['end'] = date('D, d M Y', $row['end']);
    $mypto[] = $row;
}

mysql_free_result($result);
mysql_close($conn);

$i = 0;
$mypto_table_contents = '';
foreach ($mypto as $row) {
    // adding some row colours
    $i++;
    if ($i%2 == 0) {
        $mypto_table_contents .= '<tr class="highlight">';
    }
    else {
        $mypto_table_contents .= '<tr>';
    }

    foreach($row as $value) {
        $mypto_table_contents .= '<td>'.$value.'</td>';
    }
    $mypto_table_contents .= '</tr>';
}

include './templates/header.php';
?>
<div class='pto_table_container'>
<h2>My Reported PTO</h2>
    <table id='mypto_table' class='display'>
        <thead>
            <tr>
                <th class='datetime'>Added</th>
                <th class='datetime'>Start</th>
                <th class='datetime'>End</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
	<?php echo $mypto_table_contents; ?>
        </tbody>
    </table>
</div>

<?php
include './templates/footer.php';
