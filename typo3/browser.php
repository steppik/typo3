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
 * This is the frameset to the file/record browser window
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 */
require ('init.php');
require ('template.php');








/**
 * Script Class, putting the frameset together.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_browser {
	var $content;

	/**
	 * Main function.
	 * Creates the header code in XHTML, the JavaScript, then the frameset for the two frames.
	 * 
	 * @return	void		
	 */
	function main()	{
		$mode =t3lib_div::GPvar('mode');

			// Set doktype:
		$GLOBALS["TBE_TEMPLATE"]->docType="xhtml_frames";
		$GLOBALS["TBE_TEMPLATE"]->JScode= '
			<script type="text/javascript">
				  /*<![CDATA[*/
				  	//
				function closing()	{
					if (parent.typoWin)	{
						if (parent.typoWin.clipBrd) {
							parent.typoWin.focus();
							parent.typoWin.clipBrd.detachBrowser();
						} else {
							parent.typoWin.browserWin="";
						}
					}
					close();
				}
					//
				function setParams(mode,params)	{
					parent.content.document.location = "browse_links.php?mode="+mode+"&bparams="+params;
				}
			
				if (!parent.typoWin)	{
					if (window.opener)	{
						parent.typoWin=window.opener;
					} else {
						alert("ERROR: Sorry, no link to main window... Closing");	// clipboard is opened
						close();
					}
				}
				//alert(parent.typoWin);
			
				if (parent.typoWin)	{
					window.typoWin = parent.typoWin;
					theBrowser = parent.typoWin.theBrowser;
				}

				/*]]>*/
			</script>
		';
		
		$this->content.=$GLOBALS["TBE_TEMPLATE"]->startPage('TYPO3 Element Browser');

			// Create the frameset for the window:
		$this->content.='
			<frameset rows="*,1" framespacing="0" frameborder="0" border="0" onunload="closing();">
				<frame name="content" src="'.htmlspecialchars('browse_links.php?mode='.rawurlencode($mode).'&bparams='.rawurlencode(t3lib_div::GPvar('bparams'))).'" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" onblur="closing();" />
				<frame name="menu" src="dummy.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
			</frameset>
		';

		$this->content.='
</html>';
	}

	/**
	 * Outputs the page content.
	 * 
	 * @return	void		
	 */
	function printContent()	{
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/browser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/browser.php']);
}










// Make instance:
$SOBE = t3lib_div::makeInstance('SC_browser');
$SOBE->main();
$SOBE->printContent();
?>