<?php

/********************************************/
// Validate step 1 variables
$aErrors = array();

// Input data whitelist
$step1_vars = array('id', 'start', 'end', 'people', 'cc', 'details', 'hours', 'hours_daily');
$s1data = array_fill_keys($step1_vars, '');  // Default to '' for all input data
// Fill only expected fields with request data.
$s1data = array_merge($s1data, array_intersect_key($_REQUEST, $s1data));

if (!$_REQUEST['start']) {
	$aErrors[] = 'Start date is missing';
}
if (!$_REQUEST['end']) {
	$aErrors[] = 'End date is missing';
}

if ($aErrors) {
	require_once('./templates/edit.php');
	die();
}
/********************************************/
?>

<form action="submit.php" method="post">

	<?php foreach ($s1data as $s1var => $s1value): ?>
		<input type="hidden" name="<?php echo $s1var ?>" value="<?php echo htmlspecialchars($s1value) ?>" id="<?php echo $s1var ?>" />
	<?php endforeach; ?>

	<div id="screen2">
		<p>Please specify amount of PTO you will take off for each of the following day(s):</p>
		<div id="date_discriminator">
		</div>
		<input type="submit" value="Submit" />
	</div>
	
</form>

<style>
	#date_discriminator {
		margin: 0 0 0 220px;
	}
	#date_discriminator_panel {
		float: left;
		margin-left: 40px;
	}
	#date_discriminator_panel h5 {
		font-size: 80px;
		font-weight: bold;
	}
	.date_discriminator td {
		padding: 5px;
	}
	.date_discriminator .date {
		font-size: 13px;
		font-weight: bold;
	}
	.date_discriminator .date_disabled {
		color: #CCC;
	}
	.date_discriminator .date_selector {
		margin-right: 1em;
		white-space: nowrap;
	}
	.date_discriminator .change {
		font-weight: bold;
		white-space: nowrap;
	}
	.date_discriminator .init {
		display: none;
	}
	
</style>

<script>
	var DateDiscriminator = function(oConfig) {
		
		var _oTable       		= $.TABLE({'class':'date_discriminator'});
		var _oOutputHours 		= $(oConfig.output_hours);
		var _oInputHours  		= $(oConfig.input_hours);
		var _oInputHoursDaily 	= $(oConfig.input_hours_daily);
		var _oThis        		= this;
		
		$('#'+oConfig.id).html(_oTable);
		
		function _isDateLegal(oDate) {
			var oDate = new Date(oDate);
			
			// is weekend?
			var nDay = oDate.getDay();
			if (nDay == 0 || nDay == 6) {
				return false;
			}
			return true;
		}
		function _getRadioGroup(oDate, nValue) {
			if (typeof nValue == 'undefined') {
				nValue = 8;
			}
			var oInputFullDay, oInputHalfDay, oInputEmptyDay;
			var sTime = oDate.getTime();
			var oInputFullDayParams = {
				'type'	  : 'radio',
				'id'	  : 'date8_' + sTime,
				'name'	  : sTime,
				'value'	  : 8
			};
			var oInputHalfDayParams = {
				'type'	: 'radio',
				'id'	: 'date4_' + sTime,
				'name'	: sTime,
				'value'	: 4
			};
			var oInputEmptyDayParams = {
				'type'	: 'radio',
				'id'	: 'date0_' + sTime,
				'name'	: sTime,
				'value'	: 0
			};
			
			if (nValue == 4) {
				oInputHalfDayParams['checked'] = 'checked';
			} else if (nValue == 8) {
				oInputFullDayParams['checked'] = 'checked';
			} else {
				oInputEmptyDayParams['checked'] = 'checked';
			}

			var oInputs = $.SPAN({'id' : sTime},
				$.SPAN({'class': 'date_selector'},
					$.LABEL({'for' : 'date8_' + sTime},
						'Full day'
					),
					$.NBSP,
					oInputFullDay = $.INPUT(oInputFullDayParams)
				),

				' ',

				$.SPAN({'class': 'date_selector'},
					$.LABEL({'for' : 'date4_' + sTime},
						'Half day'
					),
					$.NBSP,
					oInputHalfDay = $.INPUT(oInputHalfDayParams)
				),

				' ',

				$.SPAN({'id' : 'init_' + sTime,   'class' : 'init'}, nValue),
				$.SPAN({'id' : 'change_' + sTime, 'class' : 'change'}, '')
			);
			$(oInputFullDay).click(function() {
				_oThis.update();
				_recalculateChange(this);
			});
			$(oInputHalfDay).click(function() {
				_oThis.update();
				_recalculateChange(this);
			});
			$(oInputEmptyDay).click(function() {
				_oThis.update();
				_recalculateChange(this);
			});
			return oInputs;	
		}
		function _addRow(oDate, bEnabled, nValue) {
			var sDate = oDate.toDateString();
			$(_oTable).append(
				$.TR({},
					$.TD({'class': bEnabled 
						? 'date' 
						: 'date date_disabled' 
					}, sDate),
					$.TD({}, bEnabled 
						? _getRadioGroup(oDate, nValue)
						: ''
					)
				)
			);
		}
		
		function _recalculateHours() {
			var nHours = 0;
			$('.date_discriminator input[type=radio]').each(function() {
				if (this.checked) {
					nHours += parseInt(this.value);
				}
			});
			if (_oOutputHours) { _oOutputHours.html(nHours); }
			if (_oInputHours && _oInputHours[0])  { _oInputHours[0].value = nHours; }
		}

		function _recalculateChange(oTarget) {
			if (!oTarget || !oTarget.parentNode) { return; }
			var sTime = oTarget.parentNode.parentNode.id;
			var sChange = oTarget.value - $('#init_' + sTime).html();
		}

		function _updateHoursDaily() {
			var oData = {};
			$('.date_discriminator input[type=radio]').each(function() {
				if (this.checked) {
					oData[this.name] = parseInt(this.value);
				}
			});
			if (_oInputHoursDaily) { _oInputHoursDaily[0].value = encodeURIComponent($.toJSON(oData)); }
		}
		
		function _getDatesBetween(sDateStart, sDateEnd) {
			var oDates = {};
			var nMillisecondsInADay = 86400000;
			var nDateStart = Date.parse(sDateStart);
			var nDateEnd   = Date.parse(sDateEnd);
			var nDate = nDateStart;
			while (nDate < nDateEnd) {
				oDates[nDate] = new Date(nDate);
				nDate += nMillisecondsInADay;
			}
			oDates[nDate] = new Date(nDate);
			return oDates;
		}
		
		this.listDates = function(sDateStart, sDateEnd) {
			var oDates = _getDatesBetween(sDateStart, sDateEnd);
			var sInputDates = _oInputHoursDaily[0].value == '' ? '{}' : decodeURIComponent(_oInputHoursDaily[0].value);
			var oInputDates = $.evalJSON(sInputDates);
			for (var nDate in oDates) {
				_addRow(oDates[nDate], _isDateLegal(oDates[nDate]), oInputDates[nDate]);
			}
		}
		
		this.update = function() {
			_recalculateHours();
			_updateHoursDaily();
		}
		
		this.listDates(oConfig.start_date, oConfig.end_date);
		this.update();
	};
	$(document).ready(function() {
		$ = jQuery;
		var oDiscriminator = new DateDiscriminator({
			id				  : 'date_discriminator',
			start_date 		  : JSON.parse('<?php echo json_encode($s1data['start']) ?>'),
			end_date 		  : JSON.parse('<?php echo json_encode($s1data['end']) ?>'),
			output_hours	  : '#date_discriminator_hours',
			input_hours		  : '#hours',
			input_hours_daily : '#hours_daily'
		});
	});
</script>

