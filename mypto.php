<?php

require_once('config.php');

$me = $_SERVER['PHP_AUTH_USER'];

/* Turn on exceptions instead of errors for mysqli */
$driver = new mysqli_driver();
$driver->report_mode = MYSQLI_REPORT_STRICT;

try {
    $conn =  new mysqli($mysql['host'], $mysql['user'], $mysql['password'], $mysql['database']);

    $stmt = $conn->prepare('select
                                added,
                                hours,
                                start,
                                end,
                                details
                            from pto
                            where person = ?
                            order by start desc
                           ');
    $stmt->bind_param("s", $me);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
	$row['added'] = date('D, d M Y', $row['added']);
	$row['start'] = date('D, d M Y', $row['start']);
	$row['end'] = date('D, d M Y', $row['end']);
	$mypto[] = $row;
    }

} catch (mysqli_sql_exception $e) {
    include "./templates/header.php";
    echo 'There was a problem getting your PTO records. Please try again later.';
    include "./templates/footer.php";
    exit;
}

$mypto_table_contents = '';
foreach ($mypto as $row) {
    $mypto_table_contents .= '<tr>';
    foreach($row as $value) {
        $mypto_table_contents .= '<td>'.$value.'</td>';
    }
    $mypto_table_contents .= '</tr>';
}


include "./templates/header.php";
?>
<div class='pto_table_container'>
<h2>My Reported PTO</h2>
    <table id='mypto_table' class='display'>
        <thead>
            <tr>
                <th width='140px'>Added</th>
                <th width='50px'>Hours</th>
                <th width='140px'>Start</th>
                <th width='140px'>End</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
	<?=$mypto_table_contents; ?>
        </tbody>
    </table>
</div>

<style>
.pto_table_container {
    background-color: #fafcfc;
    padding: 3px 10px 12px 10px !important;
}

#mypto_table {
    margin: 3px 0 5px 0;
    background-color: #FFF;
    border: 1px solid #DDD;
    width: 100% !important;
}

#mypto_table thead {
    background: #fcfcfe url(img/datatables_header_bg.jpg) bottom repeat-x;
}

#mypto_table tr th {
    text-align: left;
    white-space: nowrap;
    font-weight: bold;
    border-bottom: 1px solid #FFF;
    border-right: 1px solid #FFF;
}

#mypto_table tr td {
    border-right: 1px solid #FFF;
}
</style>
<?php
include "./templates/footer.php";
