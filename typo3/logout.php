<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license 
*  from the author is found in LICENSE.txt distributed with these scripts.
*
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Logout script for the backend
 *
 * This script saves the interface positions and calls the closeTypo3Windows in the frameset
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 
require ("init.php");

// ***************************
// Script Classes
// ***************************
class SC_logout {
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$BE_USER->writelog(255,2,0,1,"User %s logged out from TYPO3 Backend",Array($BE_USER->user["username"]));	// Logout written to log
		$BE_USER->logoff();

		header("Location: ".t3lib_div::locationHeaderUrl(t3lib_div::GPvar("redirect")?t3lib_div::GPvar("redirect"):"index.php"));
	}
	function main()	{
	}
	function printContent()	{
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/logout.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/logout.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_logout");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
