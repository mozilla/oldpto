<?php

require_once('config.php');
require_once('class.Debug.php');
require_once('pto.inc');
require_once('auth.php');
require_once('output.inc');

class Filtering {
	private static $_aLdapRecords = array();
	public static $aMysqlFields = array(
	    'id', 'person', 'added', 'hours', 'hours_daily', 'start', 'end', 'details'
  	);
	public static $aLdapFields = array(
	    'givenname', 'sn', 'physicaldeliveryofficename', 'mail'
	);
	
	/************************ PRIVATE *************************/
	
	private static function _buildMysqlDateRangeClause($sLabel, $sFrom, $sTo) {
		$nFrom	= strtotime($sFrom);
		$nTo	= strtotime($sTo);
		if ($nFrom && $nTo) {
			return "$sLabel BETWEEN $nFrom AND $nTo";
		}
		return null;
	}

	private static function _buildMysqlQuery() {
		$aConditions = array();
		$aFields = implode(', ', self::$aMysqlFields);

		$sStart = self::_buildMysqlDateRangeClause('start', $_REQUEST['start_date_from'], $_REQUEST['start_date_to']);
		if ($sStart) { $aConditions[] = $sStart; }
		$sEnd   = self::_buildMysqlDateRangeClause('end',   $_REQUEST['end_date_from'],   $_REQUEST['end_date_to']);
		if ($sEnd) { $aConditions[] = $sEnd; }
		$sFiled = self::_buildMysqlDateRangeClause('added', $_REQUEST['filed_date_from'], $_REQUEST['filed_date_to']);
		if ($sFiled) { $aConditions[] = $sFiled; }

		$sConditions = implode(' AND ', $aConditions);
		if (!trim($sConditions)) { $sConditions = '1'; }
		return "SELECT $aFields FROM `pto` WHERE $sConditions;";
	}
	private static function _getLdapRecords() {
		if (self::$_aLdapRecords) {
			return self::$_aLdapRecords;
		}
		global $connection;
		$aRecords = array();
		
		$oLdapSearch = ldap_search(
			$connection, 
			'o=com,dc=mozilla', 
			'mail=*',
		  	self::$aLdapFields
		);

		$aMatches = ldap_get_entries($connection, $oLdapSearch);
		
		for ($n = 0; $n < $aMatches['count']; $n++) {
			$aRow = $aMatches[$n];
			$sMail = $aRow['mail'][0];
			$aRecords[$sMail] = array();
			foreach (self::$aLdapFields as $sFieldName) {
				$aRecords[$sMail][$sFieldName] = $aRow[$sFieldName][0];
			}
		}
		self::$_aLdapRecords = $aRecords;
		return self::$_aLdapRecords;
	}
	private static function _getMysqlRecords() {
		global $mysql;
		
		$oConnection = mysql_connect($mysql['host'], $mysql['user'], $mysql['password']);
		mysql_select_db($mysql['database']);
		$oResult = mysql_query(self::_buildMysqlQuery(), $oConnection);
		$aRecordSet = array();
		while ($aRow = mysql_fetch_assoc($oResult)) {
			$aRecordSet[] = $aRow;
		}
		
		mysql_free_result($oResult);
		mysql_close($oConnection);
		
		return $aRecordSet;
	}
	private static function _filterRecords($aRecords) {
		$sCountry   = strtolower(trim($_REQUEST['country']));
		$sFirstName = strtolower(trim($_REQUEST['first_name']));
		$sLastName  = strtolower(trim($_REQUEST['last_name']));
		
		$aFilteredRecords = array();
		foreach ($aRecords as $aRecord) {
			if ($sCountry   && $sCountry != strtolower($aRecord['country']))           { continue; }
			if ($sFirstName && stristr($aRecord['first_name'], $sFirstName) === FALSE) { continue; }
			if ($sLastName  && stristr($aRecord['last_name'],  $sLastName ) === FALSE) { continue; }
			$aFilteredRecords[] = $aRecord;
		}
		return $aFilteredRecords;
	}
	
	/************************ PUBLIC *************************/
	
	public static function getRecords() {
		$aMysqlRecords = self::_getMysqlRecords();
		$aLdapRecords  = self::_getLdapRecords();
		$aRecords 	   = array();
		
		foreach ($aLdapRecords as $sMail=>$aLdapRecord) {
			$sMail = strtolower($sMail);
			list($sCity, $sCountry) = explode(':::', $aLdapRecord['physicaldeliveryofficename']);
			foreach ($aMysqlRecords as $aMysqlRecord) {
				if ($sMail == strtolower($aMysqlRecord['person'])) {
					$aRecords[] = array(
						'id' 		 => $aMysqlRecord['id'],
						'first_name' => $aLdapRecord['givenname'],
						'last_name'  => $aLdapRecord['sn'],
						'email'      => $sMail,
						'start_date' => $aMysqlRecord['start'],
						'end_date'   => $aMysqlRecord['end'],
						'filed_date' => $aMysqlRecord['added'],
						'pto_hours'  => $aMysqlRecord['hours'],
						'city'		 => $sCity,
						'country'	 => $sCountry,
						'details'	 => $aMysqlRecord['details'],
						'hours_daily'=> $aMysqlRecord['hours_daily']
					);
				}
			}
		}
		
		return self::_filterRecords($aRecords);
	}
	
	public static function getCountries() {
		$aLdapRecords = self::_getLdapRecords();
		$aCountries = array();
		foreach ($aLdapRecords as $aRecord) {
			list($sCity, $sCountry) = explode(':::', $aRecord['physicaldeliveryofficename']);
			if (!trim($sCountry)) { continue; }
			$aCountries[$sCountry] = $sCountry;
		}
		return $aCountries;
	}
	
}

?>