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
 * Dummy document - displays nothing but background color.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant content
 */

require ('init.php');
require ('template.php');

// ***************************
// Script Classes
// ***************************
class SC_dummy {
	var $content;
	
	/**
	 * Create content
	 */
	function main()	{
		global $TBE_TEMPLATE;

			// Start page
		$TBE_TEMPLATE->docType = 'xhtml_trans';
		$this->content.=$TBE_TEMPLATE->startPage('Dummy document');

			// End page:
		$this->content.=$TBE_TEMPLATE->endPage();
	}
	
	/**
	 * Print output
	 */
	function printContent()	{
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/dummy.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/dummy.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('SC_dummy');
$SOBE->main();
$SOBE->printContent();

?>
