<?php

/********************************************/
// Validate step 1 variables
$aErrors = array();
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
	
	<input type="hidden" name="id" 			value="<?= $_REQUEST['id'] ?>" 			/>
	<input type="hidden" name="start" 		value="<?= $_REQUEST['start'] ?>" 		/>
	<input type="hidden" name="end" 		value="<?= $_REQUEST['end'] ?>" 		/>
	<input type="hidden" name="people" 		value="<?= $_REQUEST['people'] ?>" 		/>
	<input type="hidden" name="cc" 			value="<?= $_REQUEST['cc'] ?>" 			/>
	<input type="hidden" name="details" 	value="<?= $_REQUEST['details'] ?>" 	/>
	<input type="hidden" name="hours" 		value="<?= $_REQUEST['hours'] ?>" 		id="hours" 		 />
	<input type="hidden" name="hours_daily" value="<?= $_REQUEST['hours_daily'] ?>" id="hours_daily" />
	
	<div id="screen2">
		<p>Please specify amount of PTO hours for each day:</p>
		<div id="date_discriminator_panel">
			<h5 id="date_discriminator_hours">24</h5>
			<div>Hours of PTO</div>
			<br />
			<input type="submit" value="Submit" />
		</div>
		<div id="date_discriminator">
		</div>
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
						'Full day (8hrs)'
					),
					$.NBSP,
					oInputFullDay = $.INPUT(oInputFullDayParams)
				),

				' ',

				$.SPAN({'class': 'date_selector'},
					$.LABEL({'for' : 'date4_' + sTime},
						'Half day (4hrs)'
					),
					$.NBSP,
					oInputHalfDay = $.INPUT(oInputHalfDayParams)
				),

				' ',

				$.SPAN({'class': 'date_selector'},
					$.LABEL({'for' : 'date0_' + sTime},
						'Work day (0hrs)'
					),
					$.NBSP,
					oInputEmptyDay = $.INPUT(oInputEmptyDayParams)
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
			$('#change_' + sTime).html('(Change: ' + sChange + ' hours)');
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
			start_date 		  : '<?= $_REQUEST['start'] ?>',
			end_date 		  : '<?= $_REQUEST['end'] ?>',
			output_hours	  : '#date_discriminator_hours', 
			input_hours		  : '#hours', 
			input_hours_daily : '#hours_daily'
		});
	});
</script>

	