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
		$start_date_from = isset($_REQUEST['start_date_from']) ? $_REQUEST['start_date_from'] : NULL;
		$start_date_to = isset($_REQUEST['start_date_to']) ? $_REQUEST['start_date_to'] : NULL;
		$end_date_from = isset($_REQUEST['end_date_from']) ? $_REQUEST['end_date_from'] : NULL;
		$end_date_to = isset($_REQUEST['end_date_to']) ? $_REQUEST['end_date_to'] : NULL;
		$filed_date_from = isset($_REQUEST['filed_date_from']) ? $_REQUEST['filed_date_from'] : NULL;
		$filed_date_to = isset($_REQUEST['filed_date_to']) ? $_REQUEST['filed_date_to'] : NULL;

		$sStart = self::_buildMysqlDateRangeClause('start', $start_date_from, $start_date_to);
		if ($sStart) { $aConditions[] = $sStart; }
		$sEnd   = self::_buildMysqlDateRangeClause('end',   $end_date_from,   $end_date_to);
		if ($sEnd) { $aConditions[] = $sEnd; }
		$sFiled = self::_buildMysqlDateRangeClause('added', $filed_date_from, $filed_date_to);
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
				if (isset($aRow[$sFieldName][0])) {
					$aRecords[$sMail][$sFieldName] = $aRow[$sFieldName][0];
				}
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
		$sCountry   = isset($_REQUEST['country']) ? strtolower(trim($_REQUEST['country'])) : NULL;
		$sFirstName = isset($_REQUEST['first_name']) ? strtolower(trim($_REQUEST['first_name'])) : NULL;
		$sLastName  = isset($_REQUEST['last_name']) ?strtolower(trim($_REQUEST['last_name'])) : NULL;
		
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
        $aRecords      = array();
        $sorted_ldap   = array();

        // Loop over all ldap accounts and build associative array
        // Idea here is most people will have taken PTO at some point
        // We're potentially adding records to the associative array
        // unnecessarily, but lookups are much faster

        foreach ($aLdapRecords as $sMail=>$aLdapRecord) {
            $sMail = strtolower($sMail);
            if (isset($aLdapRecord['physicaldeliveryofficename'])) {
            	list($sCity, $sCountry) = explode(':::', $aLdapRecord['physicaldeliveryofficename']);
	    } else {
		$sCity = NULL;
		$sCountry = NULL;
	    }
            $sorted_ldap[$sMail] = array(
                        'first_name' => $aLdapRecord['givenname'],
                        'last_name' => $aLdapRecord['sn'],
                        'city' => $sCity,
                        'country' => $sCountry
                    );
        }

        // Loop over all mysql records, merge in the $sorted_ldap data and append to aRecords
        foreach ($aMysqlRecords as $aMysqlRecord) {
            $sMail = strtolower($aMysqlRecord['person']);
            $ldap_contact = $sorted_ldap[$sMail];
            if ($ldap_contact) {
                $aRecords[] = array(
                        'id'         => $aMysqlRecord['id'],
                        'email'      => $sMail,
                        'start_date' => $aMysqlRecord['start'],
                        'end_date'   => $aMysqlRecord['end'],
                        'filed_date' => $aMysqlRecord['added'],
                        'pto_hours'  => $aMysqlRecord['hours'],
                        'details'    => $aMysqlRecord['details'],
                        'first_name' => $ldap_contact['first_name'],
                        'last_name' => $ldap_contact['last_name'],
                        'city' => $ldap_contact['city'],
                        'country' => $ldap_contact['country'],
                        'hours_daily'=> $aMysqlRecord['hours_daily']
                    );
            }
        }

        return self::_filterRecords($aRecords);
    }	

	public static function getCountries() {
		$aLdapRecords = self::_getLdapRecords();
		$aCountries = array();
		foreach ($aLdapRecords as $aRecord) {
			if (isset($aRecord['physicaldeliveryofficename'])) {
				list($sCity, $sCountry) = explode(':::', $aRecord['physicaldeliveryofficename']);
				if (!trim($sCountry)) { continue; }
				$aCountries[$sCountry] = $sCountry;
			}
		}
		return $aCountries;
	}
	
}

?>
