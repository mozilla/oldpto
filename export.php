<?php

	require("prefetch.inc");

	if (!in_array($_SERVER["PHP_AUTH_USER"], $export_users)) {
		include "./templates/header.php";
		echo "You are not permitted to view this page.";
		include "./templates/footer.php";
		exit;
        }

	// Try the specified format first
        if (isset($_GET["format"])) {
		$output_function = "output_". $_GET["format"];
	}
	if (isset($output_function) && function_exists($output_function)){
        	$results = Filtering::getRecords();
		call_user_func($output_function,
				$results,
				isset($from_time) ? $from_time : NULL,
				isset($to_time) ? $to_time : NULL);
	} elseif (!isset($_GET["format"])) {
		// Don't do anything. Fall through to exporting as pretty HTML.
        	$aLdapCountries = Filtering::getCountries();
	} else {
		// Format not supported
		header("HTTP/1.1 400 Bad Request");
		die;
	}

	require_once "./templates/header.php";
?>

<form id="filter_form" onsubmit="applyFilter();return false;">
<table id="filters">
    <tr>
		<td>
			Start date range:
		</td>
		<td>
			<input type="text" name="start_date_from" id="start_date_from" class="js-datepicker" autocomplete="off" size="10" /> -
	        <input type="text" name="start_date_to" id="start_date_to" class="js-datepicker" autocomplete="off" size="10" />
		</td>
		<td>
			Filed date range:
		</td>
		<td>
			<input type="text" name="filed_date_from" id="filed_date_from" class="js-datepicker" autocomplete="off" size="10" /> -
	        <input type="text" name="filed_date_to" id="filed_date_to" class="js-datepicker" autocomplete="off" size="10" />
		</td>
		<td>
			First Name
		</td>
		<td>
			<input type="text" name="first_name" id="first_name" size="20" />
		</td>
		<td rowspan="2">
			<button type="submit">Apply filters</button><br />
			<button type="reset">Clear filters</button>
		</td>
	</tr>
	<tr>
		<td>
			End date range:
		</td>
		<td>
			<input type="text" name="end_date_from" id="end_date_from" class="js-datepicker" autocomplete="off" size="10" /> -
	        <input type="text" name="end_date_to" id="end_date_to" class="js-datepicker" autocomplete="off" size="10" />
		</td>
		<td>
			Country:
		</td>
		<td>
			<select name="country" id="country">
				<option value="">Any country</option>
				<?php foreach ($aLdapCountries as $sCountry) : ?>
					<option value="<?php echo $sCountry; ?>"><?php echo $sCountry; ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			Last Name:
		</td>
		<td>
			<input type="text" name="last_name" id="last_name" size="20" />
		</td>
	</tr>
</table>
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
<div id="pto">
</div>

<div class="pto_table_container">
	<table id="pto_table" class="display">
		<thead>
			<tr>
				<th>Email</th>
				<th width="1%">Id</th>
				<th width="15%;">First name</th>
				<th width="15%;">Last name</th>
				<th width="80px">Date filed</th>
				<th width="50px">Hours</th>
				<th width="80px">Start</th>
				<th width="80px">End</th>
				<th width="1%">City</th>
				<th width="50px">Country</th>
				<th width="15%">Details</th>
				<th width="50px">Edit</th>
				<th width="50px">View hours</th>
			</tr>
		</thead>
		<tbody></tbody>
		<tfooter></tfooter>
	</table>
</div>
<div id="view_hours_daily"></div>

<link rel="stylesheet" href="./css/demo_table_jui.css" type="text/css" media="screen" charset="utf-8">
<style>
	#formats {
		font-size: 12px;
	}
	#formats a {
	    line-height: 21px;
	}
		#formats li.active {
			font-weight: bold;
		}
	.pto_table_container {
		background-color: #fafcfc;
		padding: 3px 10px 12px 10px !important;
	}
	#pto_table {
		margin: 3px 0 5px 0;
		background-color: #FFF;
		border: 1px solid #DDD;
		width: 100% !important;
	}
		#pto_table thead {
			background: #fcfcfe url(img/datatables_header_bg.jpg) bottom repeat-x;
		}
		#pto_table tr th {
			text-align: left;
			white-space: nowrap;
			font-weight: bold;
			border-bottom: 1px solid #FFF;
			border-right: 1px solid #FFF;
		}
		#pto_table tr td {
			border-right: 1px solid #FFF;
		}
	table#filters {
		background-color: rgba(102, 204, 255, 0.5);
		padding: 0.5em 0 0.5em 0;
		border-top-left-radius: 0.5em;
		border-top-right-radius: 0.5em;
		-moz-border-radius-topleft: 0.5em;
		-moz-border-radius-topright: 0.5em;
		-webkit-border-top-left-radius: 0.5em;
		-webkit-border-top-right-radius: 0.5em;
		cursor: default;
		height: 80px;
		width: 100%;
	}
		table#filters td {
			margin: 2px 10px;
		}
	.column2 {
		font-weight: bold;
	}
</style>

<script src="./js/jquery.strftime-minified.js"></script>
<script src="./js/jquery.tablesorter.js"></script>
<script src="./js/jquery.dom.js"></script>
<script src="./js/jquery.dataTables.min.js"></script>
<script src="./js/jquery.unserialize.js"></script>

<script>
	var _oldHash = '';
	function getAjaxSource(sFormat) {
		sFormat = sFormat || 'json';
		return 'export.php?format=' + 
			sFormat + 
			'&user=<?php echo $notifier_email; ?>&' + 
			location.hash.substr(1);
	}
	function reloadTableData() {
		$('#pto_table').dataTable().fnReloadAjax(getAjaxSource());
	}
	function updateFilterDataFromHash() {
		$('#filter_form').unserialize(location.hash.substr(1));
		_oldHash = location.hash;
	}
	function applyFilter() {
		location.hash = $('#filter_form').serialize();
	}
	function checkHash() {
		if (location.hash != _oldHash) {
			updateFilterDataFromHash();
			reloadTableData();
		}
		setTimeout('checkHash()', 1000);
	}
	function viewHoursDaily(sHoursDaily) {
		sHoursDaily = decodeURIComponent(sHoursDaily);
		var oHoursDaily = $.evalJSON(sHoursDaily);
		var oTable = $.TABLE({});
		for (var sDate in oHoursDaily) {
			var oDate = new Date(parseInt(sDate));
			$(oTable).append(
				$.TR({},
					$.TD({'class': 'column1'}, oDate.toDateString()),
					$.TD({'class': 'column2'}, oHoursDaily[sDate] + ' hours of PTO')
				)
			);
		}
		$("#view_hours_daily").html('');
		$("#view_hours_daily").append(oTable);
		$("#view_hours_daily").dialog('open');
	}
	$(document).ready(function() {
		updateFilterDataFromHash();
		$('#pto_table').dataTable({
			'bProcessing'		: true,
			'sAjaxSource'		: getAjaxSource(),
			'aaSorting'			: [[ 2, 'asc' ],[ 1, 'asc' ],[ 3, 'desc' ]],
			'aoColumnDefs'		: [{'bSearchable' : false, 'bVisible' : false, 'aTargets' : [ 0 ] }],
			'sPaginationType'	: 'full_numbers'			
		});
		$('#filter').click(function() {
			reloadTableData()
		});
		$('.js-datepicker').datepicker({
	    	onClose: function() {  }
	    });
		$('.format').click(function() {
			sFormat = this.id.replace('format-', '');
			location.replace(getAjaxSource(sFormat));
			return false;
		});
		checkHash();
		
		$("#view_hours_daily").dialog({ 
			autoOpen: false,
			width: 500
		});
	});
</script>
<style>
	#pto table {
		width: 100%;
	}
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
