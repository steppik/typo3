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
 * Generating navigation / menus from TypoScript
 *
 * This file contains five classes, four of which are extensions to the main class, tslib_menu.
 * The main class, tslib_menu, is also extended by other external PHP scripts such as the GMENU_LAYERS and GMENU_FOLDOUT scripts which creates pop-up menus.
 * Notice that extension classes (like "tslib_tmenu") must have their suffix (here "tmenu") listed in $this->tmpl->menuclasses - otherwise they cannot be instantiated.
 *
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  140: class tslib_menu 
 *  183:     function start($tmpl,$sys_page,$id,$conf,$menuNumber)	
 *  232:     function makeMenu()	
 *  664:     function includeMakeMenu($conf,$altSortField)	
 *  681:     function procesItemStates($splitCount)	
 *  864:     function subMenu($uid,$mount_point=0)	
 *  906:     function link($key,$altTarget='',$typeOverride='') 
 *  962:     function isActive($uid)	
 *  973:     function isCurrent($uid)	
 *  986:     function isSubMenu($uid,$mount_point=0)	
 * 1010:     function isItemState($kind,$key)	
 * 1047:     function accessKey($title)	
 * 1073:     function userProcess($mConfKey,$passVar)	
 * 1088:     function setATagParts()	
 * 1101:     function getPageTitle($title,$nav_title)	
 *
 *
 * 1132: class tslib_tmenu extends tslib_menu 
 * 1141:     function generate()	
 * 1157:     function writeMenu()	
 * 1287:     function getBeforeAfter($pref)	
 * 1317:     function addJScolorShiftFunction()	
 * 1319:     function changeBGcolor(id,color) 
 * 1339:     function extProc_init()	
 * 1350:     function extProc_RO($key)	
 * 1361:     function extProc_beforeLinking($key)	
 * 1373:     function extProc_afterLinking($key)	
 * 1390:     function extProc_beforeAllWrap($item,$key)	
 * 1401:     function extProc_finish()	
 *
 *
 * 1437: class tslib_gmenu extends tslib_menu 
 * 1446:     function generate()	
 * 1484:     function makeGifs($conf, $resKey)	
 * 1681:     function findLargestDims($conf,$items,$Hobjs,$Wobjs,$minDim,$maxDim)	
 * 1753:     function writeMenu()	
 * 1851:     function extProc_init()	
 * 1862:     function extProc_RO($key)	
 * 1873:     function extProc_beforeLinking($key)	
 * 1886:     function extProc_afterLinking($key)	
 * 1903:     function extProc_beforeAllWrap($item,$key)	
 * 1914:     function extProc_finish()	
 *
 *
 * 1948: class tslib_imgmenu extends tslib_menu 
 * 1957:     function generate()	
 * 1975:     function makeImageMap($conf)	
 * 2136:     function writeMenu()	
 *
 *
 * 2179: class tslib_jsmenu extends tslib_menu 
 * 2186:     function generate()	
 * 2194:     function writeMenu()	
 * 2254:     function generate_level($levels,$count,$pid,$menuItemArray='')	
 *
 * TOTAL FUNCTIONS: 41
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


 















/**
 * Base class. The HMENU content object uses this (or more precisely one of the extension classes).
 * Amoung others the class generates an array of menuitems. Thereafter functions from the subclasses are called.
 * The class is ALWAYS used through extension classes (like tslib_gmenu or tslib_tmenu which are classics) and
 * 
 * Example of usage (from tslib_cObj):
 * 
 * $menu = t3lib_div::makeInstance('tslib_'.$cls);
 * $menu->parent_cObj = $this;
 * $menu->start($GLOBALS['TSFE']->tmpl,$GLOBALS['TSFE']->sys_page,'',$conf,1);
 * $menu->makeMenu();
 * $content.=$menu->writeMenu();
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @see tslib_cObj::HMENU()
 */
class tslib_menu {
	var $menuNumber = 1;				// tells you which menu-number this is. This is important when getting data from the setup
	var $entryLevel = 0;				// 0 = rootFolder
	var $subLevelClass = '';			// Points to the menu-class, that should be used for the next level
	var $spacerIDList = '199';			// The doktype-number that defines a spacer
	var $imgNamePrefix = 'img';
	var $imgNameNotRandom=0;
	var $debug = 0;
	var $parent_cObj ='';				// Loaded with the parent cObj-object when a new HMENU is made
	var $GMENU_fixKey='gmenu';
	var $MP_array=array();				// accumulation of mount point data
	
		// internal
	var $conf = Array();
	var $mconf = Array();
	var $tmpl;		// template-object
	var $sys_page;	// sys_page-object
	var $id;			// the base-id of the menu. 
	var $nextActive;
	var $menuNumber;
	var $menuArr;	// The array of menuItems which is built
	var $hash;
	var $result = Array();
	var $rL_uidList;		// Is filled with a list of page uid numbers which are in the current root line (used to evaluate whether a menu item is in active state)
	var $INPfixMD5;
	var $I;
	var $WMresult;
	var $WMfreezePrefix;
	var $WMmenuItems;
	var $WMextraScript;
	var $alternativeMenuTempArray='';		// Can be set to contain menu item arrays for sub-levels.

	/**
	 * The initialization of the object. This just sets some internal variables.
	 * 
	 * @param	object		The $GLOBALS['TSFE']->tmpl object
	 * @param	object		The $GLOBALS['TSFE']->sys_page object
	 * @param	integer		A starting point page id. This should probably be blank since the 'entryLevel' value will be used then.
	 * @param	Array		The TypoScript configuration for the HMENU cObject
	 * @param	integer		Menu number; 1,2,3. Should probably be '1'
	 * @return	boolean		Returns true on success
	 * @see tslib_cObj::HMENU()
	 */
	function start($tmpl,$sys_page,$id,$conf,$menuNumber)	{
		$this->conf = $conf;
		$this->mconf = $conf[$menuNumber.'.'];
		$this->debug=$GLOBALS['TSFE']->debug;

			// Sets the internal vars. $tmpl MUST be the template-object. $sys_page MUST be the sys_page object
		if ($this->conf[$menuNumber] && is_object($tmpl) && is_object($sys_page))	{
			$this->tmpl = $tmpl;
			$this->sys_page = $sys_page;

				// EntryLevel
			$this->entryLevel = tslib_cObj::getKey ($conf['entryLevel'],$this->tmpl->rootLine);
			$rL=$this->tmpl->rootLine;
			reset($rL);
			while(list(,$valA)=each($rL))	{
				$this->rL_uidList.=','.$valA['uid'];
			}

				// ID
			$this->id = $id ? $id : $this->tmpl->rootLine[$this->entryLevel]['uid'];		// If $id not stated with start() then the base-id will be found from rootLine[$this->entryLevel]
			$this->menuNumber = $menuNumber;
			$this->nextActive = $this->tmpl->rootLine[$this->entryLevel+$this->menuNumber]['uid'];	

				// imgNamePrefix
			if ($this->mconf['imgNamePrefix']) {
				$this->imgNamePrefix=$this->mconf['imgNamePrefix'];
			}
			$this->imgNameNotRandom = $this->mconf['imgNameNotRandom'];

				// subLevelClass
			$cls = strtolower($this->conf[$menuNumber+1]);
			if ($cls && t3lib_div::inList($this->tmpl->menuclasses,$cls))	{
				$this->subLevelClass = $cls;
			}
			$retVal = true;
		} else {
			$GLOBALS['TT']->setTSlogMessage('ERROR in menu',3);
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Creates the menu in the internal variables, ready for output.
	 * Basically this will read the page records needed and fill in the internal $this->menuArr
	 * Based on a hash of this array and some other variables the $this->result variable will be loaded either from cache OR by calling the generate() method of the class to create the menu for real.
	 * 
	 * @return	void		
	 */
	function makeMenu()	{
		if ($this->id)	{
			$temp = array();
			$altSortFieldValue = trim($this->mconf['alternativeSortingField']);
			$altSortField = $altSortFieldValue ? $altSortFieldValue : 'sorting';
			if ($this->menuNumber==1 && $this->conf['special'])	{		// ... only for the FIRST level of a HMENU
				$value = $this->conf['special.']['value'];

				switch($this->conf['special'])	{
					case 'userdefined':
						$temp = $this->includeMakeMenu($this->conf['special.'],$altSortField);
					break;
					case 'userfunction':
						$temp = $this->parent_cObj->callUserFunction(
							$this->conf['special.']['userFunc'], 
							array_merge($this->conf['special.'],array('_altSortField'=>$altSortField)),
							''
						);
						if (!is_array($temp))	$temp=array();
					break;
					case 'directory':
						if ($value=='') {
							$value=$GLOBALS['TSFE']->page['uid'];
						}
						$items=t3lib_div::intExplode(',',$value);
						reset($items);
						while(list(,$id)=each($items))	{
							$idPage = $GLOBALS['TSFE']->sys_page->getRawRecord('pages',$id);
							if (is_array($idPage) && $GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] && $idPage['mount_pid']>0)	{
								$MP=$idPage['mount_pid'].'-'.$idPage['uid'];
								$id=$idPage['mount_pid'];
							} else $MP=0;
							
							$query = $GLOBALS['TSFE']->cObj->getQuery('pages',Array('pidInList'=>$id,'orderBy'=>$altSortField));
							$res = mysql(TYPO3_db, $query);
							while ($row = mysql_fetch_assoc($res))	{
								$temp[$row['uid']]=$GLOBALS['TSFE']->sys_page->getPageOverlay($row);
								$temp[$row['uid']]['_MP_PARAM']=$MP;
							}
						}
					break;
					case 'list':
						if ($value=='') {
							$value=$this->id;
						}
						$loadDB = t3lib_div::makeInstance('FE_loadDBGroup');
						$loadDB->start($value, 'pages');
						$loadDB->additionalWhere['pages']=tslib_cObj::enableFields('pages');
						$loadDB->getFromDB();
	
						reset($loadDB->itemArray);
						$data = $loadDB->results;
	
						while(list(,$val)=each($loadDB->itemArray))	{
							$row = $data[$val['table']][$val['id']];
							if ($row)	{
								$temp[]=$GLOBALS['TSFE']->sys_page->getPageOverlay($row);
							}
						}
					break;
					case 'updated':
						if ($value=='') {
							$value=$GLOBALS['TSFE']->page['uid'];
						}
						$items=t3lib_div::intExplode(',',$value);
						if (t3lib_div::testInt($this->conf['special.']['depth']))	{
							$depth = t3lib_div::intInRange($this->conf['special.']['depth'],1,20);		// Tree depth
						} else {
							$depth=20;
						}				
						$limit = t3lib_div::intInRange($this->conf['special.']['limit'],0,100);	// max number of items
						$maxAge = intval(tslib_cObj::calc($this->conf['special.']['maxAge']));
						if (!$limit)	$limit=10;
						$mode = $this->conf['special.']['mode'];	// *'auto', 'manual', 'tstamp'
							// Get id's
						$id_list_arr = Array();
						reset($items);
						while(list(,$id)=each($items))	{
							$bA = t3lib_div::intInRange($this->conf['special.']['beginAtLevel'],0,100);
							$id_list_arr[]=tslib_cObj::getTreeList($id,$depth-1+$bA,$bA-1).($bA?0:$id);
						}
						$id_list = implode($id_list_arr, ',');
							// Get sortField (mode)
						switch($mode)	{
							case 'starttime':
								$sortField = 'starttime';
							break;						
							case 'lastUpdated':
							case 'manual':
								$sortField = 'lastUpdated';
							break;
							case 'tstamp':
								$sortField = 'tstamp';
							break;
							case 'crdate':
								$sortField = 'crdate';
							break;						
							default:
								$sortField = 'SYS_LASTCHANGED';
							break;
						}
							// Get 
						$extraWhere = ' AND pages.doktype NOT IN (5,6)';
	
						if ($this->conf['special.']['excludeNoSearchPages']) {
							$extraWhere.= ' AND pages.no_search=0';
						}
						if ($maxAge>0)	{
							$extraWhere.=' AND '.$sortField.'>'.($GLOBALS['SIM_EXEC_TIME']-$maxAge);
						}
	
						$query = $GLOBALS['TSFE']->cObj->getQuery('pages',Array('pidInList'=>'0', 'uidInList'=>$id_list, 'where'=>$sortField.'>=0'.$extraWhere, 'orderBy'=>($altSortFieldValue ? $altSortFieldValue : $sortField.' desc'),'max'=>$limit));
						$res = mysql(TYPO3_db, $query);
						while ($row = mysql_fetch_assoc($res))	{
							$temp[$row['uid']]=$GLOBALS['TSFE']->sys_page->getPageOverlay($row);
						}
					break;
					case 'keywords':
						list($value)=t3lib_div::intExplode(',',$value);
						if (!$value) {
							$value=$GLOBALS['TSFE']->page['uid'];
						}
						if ($this->conf['special.']['setKeywords'] || $this->conf['special.']['setKeywords.']) {
							$kw = $this->parent_cObj->stdWrap($this->conf['special.']['setKeywords'], $this->conf['special.']['setKeywords.']);
	 					} else {
		 					$value_rec=$this->sys_page->getPage($value);	// The page record of the 'value'.

							$kfieldSrc = $this->conf['special.']['keywordsField.']['sourceField'] ? $this->conf['special.']['keywordsField.']['sourceField'] : 'keywords';
							$kw = trim(tslib_cObj::keywords($value_rec[$kfieldSrc]));		// keywords.
	 					}

						$mode = $this->conf['special.']['mode'];	// *'auto', 'manual', 'tstamp'
						switch($mode)	{
							case 'starttime':
								$sortField = 'starttime';
							break;						
							case 'lastUpdated':
							case 'manual':
								$sortField = 'lastUpdated';
							break;
							case 'tstamp':
								$sortField = 'tstamp';
							break;
							case 'crdate':
								$sortField = 'crdate';
							break;						
							default:
								$sortField = 'SYS_LASTCHANGED';
							break;
						}

							// depth, limit, extra where
						if (t3lib_div::testInt($this->conf['special.']['depth']))	{
							$depth = t3lib_div::intInRange($this->conf['special.']['depth'],0,20);		// Tree depth
						} else {
							$depth=20;
						}				
						$limit = t3lib_div::intInRange($this->conf['special.']['limit'],0,100);	// max number of items
						$extraWhere = ' AND pages.uid!='.$value.' AND pages.doktype NOT IN (5,6)';
						if ($this->conf['special.']['excludeNoSearchPages']) {
							$extraWhere.= ' AND pages.no_search=0';
						}
							// start point
						$eLevel = tslib_cObj::getKey (intval($this->conf['special.']['entryLevel']),$this->tmpl->rootLine);
						$startUid = intval($this->tmpl->rootLine[$eLevel]['uid']);

							// which field is for keywords
						$kfield = 'keywords';
						if ( $this->conf['special.']['keywordsField'] ) {
							list($kfield) = explode(' ',trim ($this->conf['special.']['keywordsField']));
						}	
					
							// If there are keywords and the startuid is present.
						if ($kw && $startUid)	{
							$bA = t3lib_div::intInRange($this->conf['special.']['beginAtLevel'],0,100);
							$id_list=tslib_cObj::getTreeList($startUid,$depth-1+$bA,$bA-1).($bA?0:$startUid);

							$kwArr = explode(',',$kw);
							reset($kwArr);
							while(list(,$word)=each($kwArr))	{
								$word = trim($word);
								if ($word)	{
									$keyWordsWhereArr[]=$kfield.' LIKE "%'.addslashes($word).'%"';
								}
							}
							$query = $GLOBALS['TSFE']->cObj->getQuery('pages',Array('pidInList'=>'0', 'uidInList'=>$id_list, 'where'=>'('.implode($keyWordsWhereArr,' OR ').')'.$extraWhere, 'orderBy'=>($altSortFieldValue ? $altSortFieldValue : $sortField.' desc'),'max'=>$limit));
							$res = mysql(TYPO3_db, $query);
							while ($row = mysql_fetch_assoc($res))	{
								$temp[$row['uid']]=$GLOBALS['TSFE']->sys_page->getPageOverlay($row);
							}
						}
					break;
					case 'rootline':
						$begin_end = explode('|',$this->conf['special.']['range']);
						if (!t3lib_div::testInt($begin_end[0]))	{intval($begin_end[0]);}
						if (!t3lib_div::testInt($begin_end[1]))	{$begin_end[1]=-1;}
	
						$beginKey = tslib_cObj::getKey ($begin_end[0],$this->tmpl->rootLine);
						$endKey = tslib_cObj::getKey ($begin_end[1],$this->tmpl->rootLine);
						if ($endKey<$beginKey)	{$endKey=$beginKey;}
						
						reset($this->tmpl->rootLine);
						while(list($k_rl,$v_rl)=each($this->tmpl->rootLine))	{
							if ($k_rl>=$beginKey && $k_rl<=$endKey)	{
#								$temp_key=$v_rl['uid'];
								$temp_key=$k_rl;
								$temp[$temp_key]=$this->sys_page->getPage($v_rl['uid']);
								if (count($temp[$temp_key]))	{
									if (!$temp[$temp_key]['target'])	{	// If there are no specific target for the page, put the level specific target on.
										$temp[$temp_key]['target'] = $this->conf['special.']['targets.'][$k_rl];
									}
								} else unset($temp[$temp_key]);
							}
						}
					break;
					case 'browse':
						list($value)=t3lib_div::intExplode(',',$value);
						if (!$value) {
							$value=$GLOBALS['TSFE']->page['uid'];
						}
						if ($value!=$this->tmpl->rootLine[0]['uid'])	{	// Will not work out of rootline
		 					$recArr=array();
		 					$value_rec=$this->sys_page->getPage($value);	// The page record of the 'value'.
		 					if ($value_rec['pid'])	{	// 'up' page cannot be outside rootline
		 						$recArr['up']=$this->sys_page->getPage($value_rec['pid']);	// The page record of 'up'.
		 					}
		 					if ($recArr['up']['pid'] && $value_rec['pid']!=$this->tmpl->rootLine[0]['uid'])	{	// If the 'up' item was NOT level 0 in rootline...
		 						$recArr['index']=$this->sys_page->getPage($recArr['up']['pid']);	// The page record of "index".
		 					}
		 				
		 						// prev / next is found
		 					$prevnext_menu = $this->sys_page->getMenu($value_rec['pid'],'*',$altSortField);
		 					$lastKey=0;
		 					$nextActive=0;
		 					reset($prevnext_menu);
		 					while(list($k_b,$v_b)=each($prevnext_menu))	{
		 						if ($nextActive)	{
		 							$recArr['next']=$v_b;
		 							$nextActive=0;
								}
		 						if ($v_b['uid']==$value)	{
		 							if ($lastKey)	{
		 								$recArr['prev']=$prevnext_menu[$lastKey];
		 							}
		 							$nextActive=1;
								}
		 						$lastKey=$k_b;
		 					}
		 					reset($prevnext_menu);
							$recArr['first']=pos($prevnext_menu);
							end($prevnext_menu);
							$recArr['last']=pos($prevnext_menu);
	
		 						// prevsection / nextsection is found
							if (is_array($recArr['index']))	{	// You can only do this, if there is a valid page two levels up!
			 					$prevnextsection_menu = $this->sys_page->getMenu($recArr['index']['uid'],'*',$altSortField);
			 					$lastKey=0;
			 					$nextActive=0;
			 					reset($prevnextsection_menu);
			 					while(list($k_b,$v_b)=each($prevnextsection_menu))	{
			 						if ($nextActive)	{
										$sectionRec_temp = $this->sys_page->getMenu($v_b['uid'],'*',$altSortField);
										if (count($sectionRec_temp))	{
											reset($sectionRec_temp);
				 							$recArr['nextsection']=pos($sectionRec_temp);
											end ($sectionRec_temp);
				 							$recArr['nextsection_last']=pos($sectionRec_temp);
				 							$nextActive=0;
										}
									}
			 						if ($v_b['uid']==$value_rec['pid'])	{
			 							if ($lastKey)	{
											$sectionRec_temp = $this->sys_page->getMenu($prevnextsection_menu[$lastKey]['uid'],'*',$altSortField);
											if (count($sectionRec_temp))	{
												reset($sectionRec_temp);
					 							$recArr['prevsection']=pos($sectionRec_temp);
												end ($sectionRec_temp);
					 							$recArr['prevsection_last']=pos($sectionRec_temp);
											}
			 							}
			 							$nextActive=1;
									}
			 						$lastKey=$k_b;
			 					}
							}
							if ($this->conf['special.']['items.']['prevnextToSection'])	{
								if (!is_array($recArr['prev']) && is_array($recArr['prevsection_last']))	{
									$recArr['prev']=$recArr['prevsection_last'];
								}
								if (!is_array($recArr['next']) && is_array($recArr['nextsection']))	{
									$recArr['next']=$recArr['nextsection'];
								}
							}
							
		 					$items = explode('|',$this->conf['special.']['items']);
							$c=0;
		 					while(list($k_b,$v_b)=each($items))	{
		 						$v_b=strtolower(trim($v_b));
								if (intval($this->conf['special.'][$v_b.'.']['uid']))	{
									$recArr[$v_b] = $this->sys_page->getPage(intval($this->conf['special.'][$v_b.'.']['uid']));	// fetches the page in case of a hardcoded pid in template
								}
		 						if (is_array($recArr[$v_b]))	{
		 							$temp[$c]=$recArr[$v_b];
									if ($this->conf['special.'][$v_b.'.']['target'])	{
										$temp[$c]['target']=$this->conf['special.'][$v_b.'.']['target'];
									}
									if (is_array($this->conf['special.'][$v_b.'.']['fields.']))	{
										reset($this->conf['special.'][$v_b.'.']['fields.']);
										while(list($fk,$val)=each($this->conf['special.'][$v_b.'.']['fields.']))	{
											$temp[$c][$fk]=$val;
										}
									}
									$c++;
								}
		 					}
						}
					break;
				}
			} elseif (is_array($this->alternativeMenuTempArray))	{	// Setting $temp array if not level 1.
				$temp = $this->alternativeMenuTempArray;
			} elseif ($this->mconf['sectionIndex']) {
				if ($GLOBALS['TSFE']->sys_language_uid && count($GLOBALS['TSFE']->sys_page->getPageOverlay($this->id)))	{
					$sys_language_uid = intval($GLOBALS['TSFE']->sys_language_uid);
				} else $sys_language_uid=0;
				
				$selectSetup = Array(
					'pidInList'=>$this->id,
					'orderBy'=>$altSortField,
					'where' => 'colPos=0 AND sys_language_uid='.$sys_language_uid,
					'andWhere' => 'sectionIndex!=0'
					);
				switch($this->mconf['sectionIndex.']['type'])	{
					case 'all':
						unset($selectSetup['andWhere']);
					break;				
					case 'header':
						$selectSetup['andWhere']='header_layout!=100 AND header!=""';
					break;
				}
				$basePageRow=$this->sys_page->getPage($this->id);
				if (is_array($basePageRow))	{
					$query = $GLOBALS['TSFE']->cObj->getQuery('tt_content',	$selectSetup);
					$res = mysql(TYPO3_db, $query);
					while ($row = mysql_fetch_assoc($res))	{
						$temp[$row['uid']]=$basePageRow;
						$temp[$row['uid']]['title']=$row['header'];
						$temp[$row['uid']]['subtitle']=$row['subheader'];
						$temp[$row['uid']]['starttime']=$row['starttime'];
						$temp[$row['uid']]['endtime']=$row['endtime'];
						$temp[$row['uid']]['fe_group']=$row['fe_group'];
						$temp[$row['uid']]['media']=$row['media'];

						$temp[$row['uid']]['header_layout']=$row['header_layout'];
						$temp[$row['uid']]['bodytext']=$row['bodytext'];
						$temp[$row['uid']]['image']=$row['image'];

						$temp[$row['uid']]['sectionIndex_uid']=$row['uid'];
					}
				}
			} else {
				$temp = $this->sys_page->getMenu($this->id,'*',$altSortField);		// gets the menu
			}
			
			$this->menuArr = Array();
			reset($temp);
			$c=0;
			$c_b=0;

			$minItems=intval($this->conf['minItems']);
			$maxItems=intval($this->conf['maxItems']);
			$begin= tslib_cObj::calc($this->conf['begin']);

			$banUidArray=array();
			if (trim($this->conf['excludeUidList']))	{
				$banUidArray = t3lib_div::intExplode(',', $this->conf['excludeUidList']);
			}
			
			while(list(,$data)=each($temp))	{
				$uid=$data['uid'];
				$spacer = (t3lib_div::inList($this->spacerIDList,$data['doktype'])?1:0);		// if item is a spacer, $spacer is set
				if ($this->mconf['SPC'] || !$spacer)	{	// If the spacer-function is not enabled, spacers will not enter the $menuArr
					if (!t3lib_div::inList('5,6',$data['doktype']) && !t3lib_div::inArray($banUidArray,$uid))	{		// Page may not be 'not_in_menu' or 'Backend User Section' + not in banned uid's
						$c_b++;
						if ($begin<=$c_b)	{		// If the beginning item has been reached.
							$this->menuArr[$c]=$data;
							$this->menuArr[$c]['isSpacer']=$spacer;
							$c++;
							if ($maxItems && $c>=$maxItems)	{
								break;
							}
						}
					}
				}
			}
			if ($minItems)	{
				while($c<$minItems)	{
					$this->menuArr[$c] = Array(
						'title' => '...',
						'uid' => $GLOBALS['TSFE']->id
					);
					$c++;
				}
			}
				// Setting number of menu items
			$GLOBALS['TSFE']->register['count_menuItems'] = count($this->menuArr);
				//	Passing the menuArr through a user defined function:
			if ($this->mconf['itemArrayProcFunc'])	{
				if (!is_array($this->parentMenuArr)) {$this->parentMenuArr=array();}
				$this->menuArr = $this->userProcess('itemArrayProcFunc',$this->menuArr);
			}
			$this->hash = md5(serialize($this->menuArr).serialize($this->mconf).serialize($this->tmpl->rootLine));

			$serData = $this->sys_page->getHash($this->hash, 60*60*24);
			if (!$serData)	{
				$this->generate();
				$this->sys_page->storeHash($this->hash, serialize($this->result),'MENUDATA');
			} else {
				$this->result=unserialize($serData);
			}
		}
	}

	/**
	 * Includes the PHP script defined for the HMENU special type "userdefined".
	 * This script is supposed to populate the array $menuItemsArray with a set of page records comprising the menu.
	 * The "userdefined" type is depreciated since "userfunction" has arrived since and is a better choice for many reasons (like using classes/functions for rendering the menu)
	 * 
	 * @param	array		TypoScript parameters for "special.". In particular the property "file" is reserved and specifies the file to include. Seems like any other property can be used freely by the script.
	 * @param	string		The sorting field. Can be used from the script in the $incFile.
	 * @return	array		An array with the menu items
	 * @access private
	 */
	function includeMakeMenu($conf,$altSortField)	{
		$incFile = $GLOBALS['TSFE']->tmpl->getFileName($conf['file']);
		if ($incFile && $GLOBALS['TSFE']->checkFileInclude($incFile))	{
			include($incFile);
		}
		return is_array($menuItemsArray) ? $menuItemsArray : array();
	}

	/**
	 * Generating the per-menu-item configuration arrays based on the settings for item states (NO, RO, ACT, CUR etc) set in ->mconf (config for the current menu object)
	 * Basically it will produce an individual array for each menu item based on the item states. BUT in addition the "optionSplit" syntax for the values is ALSO evaluated here so that all property-values are "option-splitted" and the output will thus be resolved.
	 * Is called from the "generate" functions in the extension classes. The function is processor intensive due to the option split feature in particular. But since the generate function is not always called (since the ->result array may be cached, see makeMenu) it doesn't hurt so badly.
	 * 
	 * @param	integer		Number of menu items in the menu
	 * @return	array		An array with two keys: array($NOconf,$ROconf) - where $NOconf contains the resolved configuration for each item when NOT rolled-over and $ROconf contains the ditto for the mouseover state (if any)
	 * @access private
	 */
	function procesItemStates($splitCount)	{
			// Prepare normal settings
		$NOconf = $this->tmpl->splitConfArray($this->mconf['NO.'],$splitCount);
			// Prepare rollOver settings, overriding normal settings
		$ROconf=array();
		if ($this->mconf['RO'])	{
			$ROconf = $this->tmpl->splitConfArray($this->mconf['RO.'],$splitCount);
		}

			// Prepare IFSUB settings, overriding normal settings
			// IFSUB is true if there exist submenu items to the current item
		if ($this->mconf['IFSUB'])	{
			$IFSUBinit = 0;	// Flag: If $IFSUB is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{
				if ($this->isItemState('IFSUB',$key))	{
					if (!$IFSUBinit)	{	// if this is the first IFSUB element, we must generate IFSUB.
						$IFSUBconf = $this->tmpl->splitConfArray($this->mconf['IFSUB.'],$splitCount);
						if ($this->mconf['IFSUBRO'])	{
							$IFSUBROconf = $this->tmpl->splitConfArray($this->mconf['IFSUBRO.'],$splitCount);
						}
						$IFSUBinit = 1;
					}
					$NOconf[$key] = $IFSUBconf[$key];		// Substitute normal with ifsub
					if ($ROconf)	{	// If rollOver on normal, we must apply a state for rollOver on the active
						$ROconf[$key] = $IFSUBROconf[$key] ? $IFSUBROconf[$key] : $IFSUBconf[$key];		// If RollOver on active then apply this
					}
				}
			}
		}
			// Prepare active settings, overriding normal settings
		if ($this->mconf['ACT'])	{
			$ACTinit = 0;	// Flag: If $ACT is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{	// Find active
				if ($this->isItemState('ACT',$key))	{
					if (!$ACTinit)	{	// if this is the first active, we must generate ACT.
						$ACTconf = $this->tmpl->splitConfArray($this->mconf['ACT.'],$splitCount);
							// Prepare active rollOver settings, overriding normal active settings
						if ($this->mconf['ACTRO'])	{
							$ACTROconf = $this->tmpl->splitConfArray($this->mconf['ACTRO.'],$splitCount);
						}
						$ACTinit = 1;
					}
					$NOconf[$key] = $ACTconf[$key];		// Substitute normal with active
					if ($ROconf)	{	// If rollOver on normal, we must apply a state for rollOver on the active
						$ROconf[$key] = $ACTROconf[$key] ? $ACTROconf[$key] : $ACTconf[$key];		// If RollOver on active then apply this
					}
				}
			}
		}
			// Prepare active/IFSUB settings, overriding normal settings
			// ACTIFSUB is true if there exist submenu items to the current item and the current item is active
		if ($this->mconf['ACTIFSUB'])	{
			$ACTIFSUBinit = 0;	// Flag: If $ACTIFSUB is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{	// Find active
				if ($this->isItemState('ACTIFSUB',$key))	{
					if (!$ACTIFSUBinit)	{	// if this is the first active, we must generate ACTIFSUB.
						$ACTIFSUBconf = $this->tmpl->splitConfArray($this->mconf['ACTIFSUB.'],$splitCount);
							// Prepare active rollOver settings, overriding normal active settings
						if ($this->mconf['ACTIFSUBRO'])	{
							$ACTIFSUBROconf = $this->tmpl->splitConfArray($this->mconf['ACTIFSUBRO.'],$splitCount);
						}
						$ACTIFSUBinit = 1;
					}
					$NOconf[$key] = $ACTIFSUBconf[$key];		// Substitute normal with active
					if ($ROconf)	{	// If rollOver on normal, we must apply a state for rollOver on the active
						$ROconf[$key] = $ACTIFSUBROconf[$key] ? $ACTIFSUBROconf[$key] : $ACTIFSUBconf[$key];		// If RollOver on active then apply this
					}
				}
			}
		}
			// Prepare CUR (current) settings, overriding normal settings
			// CUR is true if the current page equals the item here!
		if ($this->mconf['CUR'])	{
			$CURinit = 0;	// Flag: If $CUR is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{
				if ($this->isItemState('CUR',$key))	{
					if (!$CURinit)	{	// if this is the first 'current', we must generate CUR. Basically this control is just inherited from the other implementations as current would only exist one time and thats it (unless you use special-features of HMENU)
						$CURconf = $this->tmpl->splitConfArray($this->mconf['CUR.'],$splitCount);
						if ($this->mconf['CURRO'])	{
							$CURROconf = $this->tmpl->splitConfArray($this->mconf['CURRO.'],$splitCount);
						}
						$CURinit = 1;
					}
					$NOconf[$key] = $CURconf[$key];		// Substitute normal with current
					if ($ROconf)	{	// If rollOver on normal, we must apply a state for rollOver on the active
						$ROconf[$key] = $CURROconf[$key] ? $CURROconf[$key] : $CURconf[$key];		// If RollOver on active then apply this
					}
				}
			}
		}
			// Prepare active settings, overriding normal settings
		if ($this->mconf['USR'])	{
			$USRinit = 0;	// Flag: If $USR is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{	// Find active
				if ($this->isItemState('USR',$key))	{
					if (!$USRinit)	{	// if this is the first active, we must generate USR.
						$USRconf = $this->tmpl->splitConfArray($this->mconf['USR.'],$splitCount);
							// Prepare active rollOver settings, overriding normal active settings
						if ($this->mconf['USRRO'])	{
							$USRROconf = $this->tmpl->splitConfArray($this->mconf['USRRO.'],$splitCount);
						}
						$USRinit = 1;
					}
					$NOconf[$key] = $USRconf[$key];		// Substitute normal with active
					if ($ROconf)	{	// If rollOver on normal, we must apply a state for rollOver on the active
						$ROconf[$key] = $USRROconf[$key] ? $USRROconf[$key] : $USRconf[$key];		// If RollOver on active then apply this
					}
				}
			}
		}
			// Prepare spacer settings, overriding normal settings
		if ($this->mconf['SPC'])	{
			$SPCinit = 0;	// Flag: If $SPC is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{	// Find spacers
				if ($this->isItemState('SPC',$key))	{
					if (!$SPCinit)	{	// if this is the first spacer, we must generate SPC.
						$SPCconf = $this->tmpl->splitConfArray($this->mconf['SPC.'],$splitCount);
						$SPCinit = 1;
					}
					$NOconf[$key] = $SPCconf[$key];		// Substitute normal with spacer
				}
			}
		}
			// Prepare Userdefined settings
		if ($this->mconf['USERDEF1'])	{
			$USERDEF1init = 0;	// Flag: If $USERDEF1 is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{	// Find active
				if ($this->isItemState('USERDEF1',$key))	{
					if (!$USERDEF1init)	{	// if this is the first active, we must generate USERDEF1.
						$USERDEF1conf = $this->tmpl->splitConfArray($this->mconf['USERDEF1.'],$splitCount);
							// Prepare active rollOver settings, overriding normal active settings
						if ($this->mconf['USERDEF1RO'])	{
							$USERDEF1ROconf = $this->tmpl->splitConfArray($this->mconf['USERDEF1RO.'],$splitCount);
						}
						$USERDEF1init = 1;
					}
					$NOconf[$key] = $USERDEF1conf[$key];		// Substitute normal with active
					if ($ROconf)	{	// If rollOver on normal, we must apply a state for rollOver on the active
						$ROconf[$key] = $USERDEF1ROconf[$key] ? $USERDEF1ROconf[$key] : $USERDEF1conf[$key];		// If RollOver on active then apply this
					}
				}
			}
		}
			// Prepare Userdefined settings
		if ($this->mconf['USERDEF2'])	{
			$USERDEF2init = 0;	// Flag: If $USERDEF2 is generated
			reset($NOconf);
			while (list($key,$val)=each($NOconf))	{	// Find active
				if ($this->isItemState('USERDEF2',$key))	{
					if (!$USERDEF2init)	{	// if this is the first active, we must generate USERDEF2.
						$USERDEF2conf = $this->tmpl->splitConfArray($this->mconf['USERDEF2.'],$splitCount);
							// Prepare active rollOver settings, overriding normal active settings
						if ($this->mconf['USERDEF2RO'])	{
							$USERDEF2ROconf = $this->tmpl->splitConfArray($this->mconf['USERDEF2RO.'],$splitCount);
						}
						$USERDEF2init = 1;
					}
					$NOconf[$key] = $USERDEF2conf[$key];		// Substitute normal with active
					if ($ROconf)	{	// If rollOver on normal, we must apply a state for rollOver on the active
						$ROconf[$key] = $USERDEF2ROconf[$key] ? $USERDEF2ROconf[$key] : $USERDEF2conf[$key];		// If RollOver on active then apply this
					}
				}
			}
		}
		
		return array($NOconf,$ROconf);
	}

	/**
	 * Creates a submenu level to the current level - if configured for.
	 * 
	 * @param	integer		Page id of the current page for which a submenu MAY be produced (if conditions are met)
	 * @param	integer		Mount-point UID (basically the mount_pid field of the page record is passed along)
	 * @return	string		HTML content of the submenu
	 * @access private
	 */
	function subMenu($uid,$mount_point=0)	{
			// starts a submenu...
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] && $mount_point>0)	{
			$MP=$mount_point.'-'.$uid;
			$uid=$mount_point;
		} else $MP=0;

			// Setting alternative menu item array if _SUB_MENU has been defined in the current ->menuArr		
		$altArray='';
		if (is_array($this->menuArr[$this->I['key']]['_SUB_MENU']) && count($this->menuArr[$this->I['key']]['_SUB_MENU']))	{
			$altArray = $this->menuArr[$this->I['key']]['_SUB_MENU'];
		}

		if ($this->subLevelClass && ($this->mconf['expAll'] || ($uid && $uid==$this->nextActive) || is_array($altArray)) && !$this->mconf['sectionIndex'])	{
			$submenu = t3lib_div::makeInstance('tslib_'.$this->subLevelClass);
			$submenu->entryLevel = $this->entryLevel+1;
			$submenu->MP_array = $this->MP_array;
			if ($MP)	$submenu->MP_array[]=$MP;

				// especially scripts that build the submenu needs the parent data
			$submenu->parentMenuArr = $this->menuArr;
			
				// Setting alternativeMenuTempArray (will be effective only if an array)
			if (is_array($altArray))	{
				$submenu->alternativeMenuTempArray = $altArray;
			}

			$submenu->start($this->tmpl,$this->sys_page,$uid,$this->conf,$this->menuNumber+1);
			$submenu->makeMenu();
			return $submenu->writeMenu();
		}
	}

	/**
	 * Creates the URL, target and onclick values for the menu item link. Returns them in an array as key/value pairs for <A>-tag attributes
	 * 
	 * @param	integer		Pointer to a key in the $this->menuArr array where the value for that key represents the menu item we are linking to (page record)
	 * @param	string		Alternative target
	 * @param	integer		Alternative type
	 * @return	array		Returns an array with A-tag attributes as key/value pairs (HREF, TARGET and onClick)
	 * @access private
	 */
	function link($key,$altTarget='',$typeOverride='') {
			// this function doesn't care about the url, because if we let the url be redirected, it will be logged in the stat!!!
		if ($this->mconf['overrideId'] || $this->menuArr[$key]['overrideId'])	{	// RF 22/10
			$overrideArray = array();
				// if a user script returned the value overrideId in the menu array we use that as page id
			$overrideArray['uid'] = $this->mconf['overrideId']?$this->mconf['overrideId']:$this->menuArr[$key]['overrideId'];	// RF 22/10
			$overrideArray['alias'] = '';
		} else {
			$overrideArray='';
		}
		$mainTarget = $altTarget ? $altTarget : $this->mconf['target'];

		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'])	{
			$localMP_array = $this->MP_array;
			if ($this->menuArr[$key]['_MP_PARAM'])	$localMP_array[]=$this->menuArr[$key]['_MP_PARAM'];
			$MP_params = count($localMP_array)?'&MP='.rawurlencode(implode(',',$localMP_array)):'';
		} else $MP_params ='';

		if ($this->mconf['collapse'] && $this->isActive($this->menuArr[$key]['uid']))	{
			$thePage = $this->sys_page->getPage($this->menuArr[$key]['pid']);
			$LD = $this->tmpl->linkData($thePage,$mainTarget,'','',$overrideArray, $this->mconf['addParams'].$MP_params, $typeOverride);
		} else {
			$LD = $this->tmpl->linkData($this->menuArr[$key],$mainTarget,'','',$overrideArray, $this->mconf['addParams'].$MP_params, $typeOverride);
		}
		
			// Overriding URL / Target if set to do so:
		if ($this->menuArr[$key]['_OVERRIDE_HREF'])	{
			$LD['totalURL'] = $this->menuArr[$key]['_OVERRIDE_HREF'];
			if ($this->menuArr[$key]['_OVERRIDE_TARGET'])	$LD['target'] = $this->menuArr[$key]['_OVERRIDE_TARGET'];
		}
		
			// OnClick open in windows.
		$onClick='';
		if ($this->mconf['JSWindow'])	{
			$conf=$this->mconf['JSWindow.'];
			$url=$LD['totalURL'];
			$LD['totalURL'] = '#';
			$onClick= 'openPic(\''.$url.'\',\''.($conf['newWindow']?md5($url):'theNewPage').'\',\''.$conf['params'].'\'); return false;';			
			$GLOBALS['TSFE']->setJS('openPic');
		}
			// out:
		$list = array();
		$list['HREF']=$LD['totalURL'];
		$list['TARGET']=$LD['target'];
		$list['onClick']=$onClick;
		
		return $list;
	}

	/**
	 * Returns true if the page with UID $uid is active (in the current rootline)
	 * 
	 * @param	integer		Page uid to evaluate.
	 * @return	boolean		True if page with $uid is active
	 * @access private
	 */
	function isActive($uid)	{
		if ($uid && t3lib_div::inList($this->rL_uidList,$uid))	{return 1;}
	}

	/**
	 * Returns true if the page with UID $uid is the CURRENT page (equals $GLOBALS['TSFE']->id)
	 * 
	 * @param	integer		Page uid to evaluate.
	 * @return	boolean		True if page $uid = $GLOBALS['TSFE']->id
	 * @access private
	 */
	function isCurrent($uid)	{
		if ($uid && $GLOBALS['TSFE']->id==$uid)	{return 1;}
	}

	/**
	 * Returns true if there is a submenu with items for the page id, $uid
	 * Used by the item states "IFSUB" and "ACTIFSUB" to check if there is a submenu
	 * 
	 * @param	integer		Page uid for which to search for a submenu
	 * @param	integer		"mount_pid" field for the page record which is evaluated (this may affect whether a submenu is found!)
	 * @return	boolean		Returns true if there was a submenu with items found
	 * @access private
	 */
	function isSubMenu($uid,$mount_point=0)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] && $mount_point>0)	{$uid=$mount_point;}

		$recs = $this->sys_page->getMenu($uid,'doktype,uid');		
		reset($recs);
		$flag=0;	// No menu by default;
		while(list(,$theRec)=each($recs))	{
			if (!t3lib_div::inList('5,6',$theRec['doktype']))	{	// If a menu item seems to be another type than 'Not in menu', then return true (there was items!)
				$flag=1;
				break;
			}
		}
		return $flag;
	}

	/**
	 * Used by procesItemStates() to evaluate if a menu item (identified by $key) is in a certain state.
	 * 
	 * @param	string		The item state to evaluate (SPC, IFSUB, ACT etc... but no xxxRO states of course)
	 * @param	integer		Key pointing to menu item from ->menuArr
	 * @return	boolean		True (integer!=0) if match, otherwise false (=0, zero)
	 * @access private
	 * @see procesItemStates()
	 */
	function isItemState($kind,$key)	{
		$natVal=0;
		if ($this->menuArr[$key]['ITEM_STATE'])	{		// If any value is set for ITEM_STATE the normal evaluation is discarded
			if (!strcmp($this->menuArr[$key]['ITEM_STATE'],$kind))	{$natVal=1;}
		} else {
			switch($kind)	{
				case 'SPC':
					$natVal = $this->menuArr[$key]['isSpacer'];
				break;
				case 'IFSUB':
					$natVal = $this->isSubMenu($this->menuArr[$key]['uid'],$this->menuArr[$key]['mount_pid']);
				break;
				case 'ACT':
					$natVal = $this->isActive($this->menuArr[$key]['uid']);
				break;		
				case 'ACTIFSUB':
					$natVal = $this->isActive($this->menuArr[$key]['uid']) && $this->isSubMenu($this->menuArr[$key]['uid'],$this->menuArr[$key]['mount_pid']);
				break;		
				case 'CUR':
					$natVal = $this->isCurrent($this->menuArr[$key]['uid']);
				break;
				case 'USR':
					$natVal = $this->menuArr[$key]['fe_group'];
				break;
			}
		}

		return $natVal;
	}

	/**
	 * Creates an access-key for a GMENU menu item based on the menu item titles first letter
	 * 
	 * @param	string		Menu item title.
	 * @return	array		Returns an array with keys "code" ("accesskey" attribute for the img-tag) and "alt" (text-addition to the "alt" attribute) if an access key was defined. Otherwise array was empty
	 * @access private
	 */
	function accessKey($title)	{
			// The global array ACCESSKEY is used to globally control if letters are already used!!
		$result = Array();
	
		$titleLen = strlen($title);
		for ($a=0;$a<$titleLen;$a++)	{
			$key = strtoupper(trim(substr($title,$a,1)));
			if ($key && !isset($GLOBALS['TSFE']->accessKey[$key]))	{
				$GLOBALS['TSFE']->accessKey[$key]=1;
				$result['code'] = ' accesskey="'.$key.'"';
				$result['alt'] = ' (ALT+'.$key.')';
				break;	
			}
		}
		return $result;
	}

	/**
	 * Calls a user function for processing of internal data.
	 * Used for the properties "IProcFunc" and "itemArrayProcFunc"
	 * 
	 * @param	string		Key pointing for the property in the current ->mconf array holding possibly parameters to pass along to the function/method. Currently the keys used are "IProcFunc" and "itemArrayProcFunc".
	 * @param	mixed		A variable to pass to the user function and which should be returned again from the user function. The idea is that the user function modifies this variable according to what you want to achieve and then returns it. For "itemArrayProcFunc" this variable is $this->menuArr, for "IProcFunc" it is $this->I
	 * @return	mixed		The processed $passVar
	 * @access private
	 */
	function userProcess($mConfKey,$passVar)	{
		if ($this->mconf[$mConfKey])	{
			$funcConf = $this->mconf[$mConfKey.'.'];
			$funcConf['parentObj']=&$this;
			$passVar = $GLOBALS['TSFE']->cObj->callUserFunction($this->mconf[$mConfKey], $funcConf, $passVar);
		}
		return $passVar;
	}

	/**
	 * Creates the <A> tag parts for the current item (in $this->I, [A1] and [A2]) based on other information in this array (like $this->I['linkHREF'])
	 * 
	 * @return	void		
	 * @access private
	 */
	function setATagParts()	{
		$this->I['A1'] = '<a '.t3lib_div::implodeParams($this->I['linkHREF'],1).$this->I['val']['ATagParams'].$this->I['addATagParams'].$this->I['accessKey']['code'].'>';
		$this->I['A2'] = '</a>';
	}

	/**
	 * Returns the title for the navigation
	 * 
	 * @param	string		The current page title
	 * @param	string		The current value of the naviation title
	 * @return	string		Returns the navigation title if it is NOT blank, otherwise the page title.
	 * @access private
	 */
	function getPageTitle($title,$nav_title)	{
		return strcmp(trim($nav_title),'') ? $nav_title : $title;
	}
}



















/**
 * Extension class creating text based menus
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=387&cHash=73a3116ab8
 */
class tslib_tmenu extends tslib_menu {

	/**
	 * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
	 * Sets the result for the new "normal state" in $this->result
	 * 
	 * @return	Void		
	 * @see tslib_menu::procesItemStates()
	 */
	function generate()	{
		$splitCount = count($this->menuArr);
		if ($splitCount)	{
			list($NOconf) = $this->procesItemStates($splitCount);
		}
		if ($this->mconf['debugItemConf'])	{echo '<h3>$NOconf:</h3>';	debug($NOconf);	}
		$this->result = $NOconf;
	}

	/**
	 * Traverses the ->result array of menu items configuration (made by ->generate()) and renders each item.
	 * During the execution of this function many internal methods prefixed "extProc_" from this class is called and many of these are for now dummy functions. But they can be used for processing as they are used by the TMENU_LAYERS
	 * An instance of tslib_cObj is also made and for each menu item rendered it is loaded with the record for that page so that any stdWrap properties that applies will have the current menu items record available.
	 * 
	 * @return	string		The HTML for the menu (returns result through $this->extProc_finish(); )
	 */
	function writeMenu()	{
		if (is_array($this->result) && count($this->result))	{
			$this->WMcObj =t3lib_div::makeInstance('tslib_cObj');	// Create new tslib_cObj for our use
			$this->WMresult='';
			$this->INPfixMD5 = substr(md5(microtime().'tmenu'),0,4);
			$this->WMmenuItems = count($this->result);
			$this->extProc_init();
			reset($this->result);
			while (list($key,$val)=each($this->result))	{
				$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ']++;
				$GLOBALS['TSFE']->register['count_MENUOBJ']++;
					
				$this->I=array();
				$this->WMcObj->start($this->menuArr[$key],'pages');		// Initialize the cObj with the page record of the menu item
				$this->I['key'] = $key;
				$this->I['INPfix']= $this->imgNameNotRandom?'':'_'.$this->INPfixMD5.'_'.$key;
				$this->I['val'] = $val;
				$this->I['title'] = $this->WMcObj->stdWrap($this->getPageTitle($this->menuArr[$key]['title'],$this->menuArr[$key]['nav_title']),$this->I['val']['stdWrap.']);
				$this->I['uid'] = $this->menuArr[$key]['uid'];
				$this->I['mount_pid'] = $this->menuArr[$key]['mount_pid'];
				$this->I['pid'] = $this->menuArr[$key]['pid'];
				$this->I['spacer'] = $this->menuArr[$key]['isSpacer'];
			
					// Make link tag
				$this->I['val']['ATagParams'] = $this->I['val']['ATagParams'] ? ' '.$this->I['val']['ATagParams'] : '';
				$this->I['linkHREF'] =  $this->link($key,$this->I['val']['altTarget'],$this->mconf['forceTypeValue']);

				if (!$this->mconf['noBlur'])	{
					$this->I['linkHREF']['onFocus']='blurLink(this);';
				}

					// Make link:
				if ($this->I['val']['RO'])	{
					$this->I['theName'] = $this->imgNamePrefix.$this->I['uid'].$this->I['INPfix'];
					$over='';
					$out ='';
					if ($this->I['val']['beforeROImg'])	{
						$over.= $this->WMfreezePrefix."over('".$this->I['theName']."before');";
						$out.= $this->WMfreezePrefix."out('".$this->I['theName']."before');";
					}
					if ($this->I['val']['afterROImg'])	{
						$over.= $this->WMfreezePrefix."over('".$this->I['theName']."after');";
						$out.= $this->WMfreezePrefix."out('".$this->I['theName']."after');";
					}
					$this->I['linkHREF']['onMouseover']=$over;
					$this->I['linkHREF']['onMouseout']=$out;
					if ($over || $out)	$GLOBALS['TSFE']->setJS('mouseOver');

						// Change background color:
					if ($this->I['val']['RO_chBgColor'])	{
						$this->addJScolorShiftFunction();
						$chBgP = t3lib_div::trimExplode('|',$this->I['val']['RO_chBgColor']);
						$this->I['linkHREF']['onMouseover'].="changeBGcolor('".$chBgP[2].$this->I['uid']."','".$chBgP[0]."');";
						$this->I['linkHREF']['onMouseout'].="changeBGcolor('".$chBgP[2].$this->I['uid']."','".$chBgP[1]."');";
					}

					$this->extProc_RO($key);
				}
				
				

					// Calling extra processing function
				$this->extProc_beforeLinking($key);
				
					// Compile link tag
				if (!$this->I['val']['doNotLinkIt']) {$this->I['val']['doNotLinkIt']=0;}
				if (!$this->I['val']['isSpacer'] && $this->I['val']['doNotLinkIt']!=1)	{
					$this->setATagParts();
				} else {
					$this->I['A1'] = '';
					$this->I['A2'] = '';
				}

					// ATAGBeforeWrap processing:
				if ($this->I['val']['ATagBeforeWrap'])	{
					$wrapPartsBefore = explode('|',$this->I['val']['linkWrap']);
					$wrapPartsAfter = array('','');
				} else {
					$wrapPartsBefore = array('','');
					$wrapPartsAfter = explode('|',$this->I['val']['linkWrap']);
				}
				if ($this->I['val']['stdWrap2'] || isset($this->I['val']['stdWrap2.']))	{
					$wrapPartsStdWrap = explode($this->I['val']['stdWrap2']?$this->I['val']['stdWrap2']:'|',$this->WMcObj->stdWrap('|',$this->I['val']['stdWrap2.']));
				} else {$wrapPartsStdWrap = array('','');}
								
					// Make before, middle and after parts
				$this->I['parts'] = array();
				$this->I['parts']['before']=$this->getBeforeAfter('before');
				$this->I['parts']['stdWrap2_begin']=$wrapPartsStdWrap[0];
				if (!$this->I['val']['doNotShowLink']) {
					$this->I['parts']['notATagBeforeWrap_begin'] = $wrapPartsAfter[0];
					$this->I['parts']['ATag_begin'] = $this->I['A1'];
					$this->I['parts']['ATagBeforeWrap_begin'] = $wrapPartsBefore[0];
					$this->I['parts']['title'] = $this->I['title'];
					$this->I['parts']['ATagBeforeWrap_end'] = $wrapPartsBefore[1];
					$this->I['parts']['ATag_end'] = $this->I['A2'];
					$this->I['parts']['notATagBeforeWrap_end'] = $wrapPartsAfter[1];
				}
				$this->I['parts']['stdWrap2_end']=$wrapPartsStdWrap[1];
				$this->I['parts']['after']=$this->getBeforeAfter('after');				

					// Passing I to a user function
				if ($this->mconf['IProcFunc'])	{
					$this->I = $this->userProcess('IProcFunc',$this->I);
				}

					// Merge parts + beforeAllWrap
				$this->I['theItem']= implode('',$this->I['parts']);
				$this->I['theItem']= $this->extProc_beforeAllWrap($this->I['theItem'],$key);
				
					// allWrap:
				$allWrap = $this->WMcObj->stdWrap($this->I['val']['allWrap'],$this->I['val']['allWrap.']);
				$this->I['theItem'] = $this->tmpl->wrap($this->I['theItem'],$allWrap);
				
				if ($this->I['val']['subst_elementUid'])	$this->I['theItem'] = str_replace('{elementUid}',$this->I['uid'],$this->I['theItem']);

					// Calling extra processing function
				$this->extProc_afterLinking($key);
			}
			return $this->extProc_finish();
		}
	}

	/**
	 * Generates the before* and after* images for TMENUs
	 * 
	 * @param	string		Can be "before" or "after" and determines which kind of image to create (basically this is the prefix of the TypoScript properties that are read from the ->I['val'] array
	 * @return	string		The resulting HTML of the image, if any.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=388&cHash=a7486044cd
	 */
	function getBeforeAfter($pref)	{
		$res = '';
		if ($imgInfo = $this->WMcObj->getImgResource($this->I['val'][$pref.'Img'],$this->I['val'][$pref.'Img.']))	{
			$imgInfo[3] = t3lib_div::png_to_gif_by_imagemagick($imgInfo[3]);
			if ($this->I['val']['RO'] && $this->I['val'][$pref.'ROImg'] && !$this->I['val']['isSpacer'])	{
				$imgROInfo = $this->WMcObj->getImgResource($this->I['val'][$pref.'ROImg'],$this->I['val'][$pref.'ROImg.']);
				$imgROInfo[3] = t3lib_div::png_to_gif_by_imagemagick($imgROInfo[3]);
				if ($imgROInfo)	{
					$theName = $this->imgNamePrefix.$this->I['uid'].$this->I['INPfix'].$pref;
					$name = ' name="'.$theName.'"';
					$GLOBALS['TSFE']->JSImgCode.= chr(10).$theName.'_n=new Image(); '.$theName.'_n.src = "'.$GLOBALS['TSFE']->absRefPrefix.$imgInfo[3].'"; ';
					$GLOBALS['TSFE']->JSImgCode.= chr(10).$theName.'_h=new Image(); '.$theName.'_h.src = "'.$GLOBALS['TSFE']->absRefPrefix.$imgROInfo[3].'"; ';
				}
			}
			$GLOBALS['TSFE']->imagesOnPage[]=$imgInfo[3];
			$res='<img src="'.$GLOBALS['TSFE']->absRefPrefix.$imgInfo[3].'" width="'.$imgInfo[0].'" height="'.$imgInfo[1].'"'.$name.($this->I['val'][$pref.'ImgTagParams']?" ".$this->I['val'][$pref.'ImgTagParams']:'').' border="0"';
			if (!strstr($res,'alt="'))	$res.=' alt=""';	// Adding alt attribute if not set.
			$res.=' />';
			if ($this->I['val'][$pref.'ImgLink'])	{$res=$this->I['A1'].$res.$this->I['A2'];}
		}
		return $this->tmpl->wrap($res.$this->WMcObj->stdWrap($this->I['val'][$pref],$this->I['val'][$pref.'.']), $this->I['val'][$pref.'Wrap']);
	}

	/**
	 * Adds a JavaScript function to the $GLOBALS['TSFE']->additionalJavaScript array
	 * 
	 * @return	void		
	 * @access private
	 * @see writeMenu()
	 */
	function addJScolorShiftFunction()	{
		$GLOBALS['TSFE']->additionalJavaScript['TMENU:changeBGcolor()']='
			function changeBGcolor(id,color) {
				if (document.getElementById && document.getElementById(id))	{
					document.getElementById(id).style.background = color;
					return true;
				} else if (document.layers && document.layers[id]) {
			        document.layers[id].bgColor = color;
					return true;
			    }
			}	
		';
	}

	/**
	 * Called right before the traversing of $this->result begins.
	 * Can be used for various initialization
	 * 
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_init()
	 */
	function extProc_init()	{
	}

	/**
	 * Called after all processing for RollOver of an element has been done.
	 * 
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_RO()
	 */
	function extProc_RO($key)	{
	}

	/**
	 * Called right before the creation of the link for the menu item
	 * 
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_beforeLinking()
	 */
	function extProc_beforeLinking($key)	{
	}

	/**
	 * Called right after the creation of links for the menu item. This is also the last function call before the while-loop traversing menu items goes to the next item.
	 * This function MUST set $this->WMresult.=[HTML for menu item] to add the generated menu item to the internal accumulation of items.
	 * 
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_afterLinking()
	 */
	function extProc_afterLinking($key)	{
			// Add part to the accumulated result + fetch submenus
		if (!$this->I['spacer'])	{
			$this->I['theItem'].= $this->subMenu($this->I['uid'],$this->I['mount_pid']);
		}
		$this->WMresult.= $this->I['val']['wrapItemAndSub'] ? $this->tmpl->wrap($this->I['theItem'],$this->I['val']['wrapItemAndSub']) : $this->I['theItem'];
	}

	/**
	 * Called before the "allWrap" happens on the menu item.
	 * 
	 * @param	string		The current content of the menu item, $this->I['theItem'], passed along.
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	string		The modified version of $item, going back into $this->I['theItem']
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_beforeAllWrap()
	 */
	function extProc_beforeAllWrap($item,$key)	{
		return $item;
	}

	/**
	 * Called before the writeMenu() function returns (only if a menu was generated)
	 * 
	 * @return	string		The total menu content should be returned by this function
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_finish()
	 */
	function extProc_finish()	{
		return $this->tmpl->wrap($this->WMresult,$this->mconf['wrap']).$this->WMextraScript;
	}
}
























/**
 * Extension class creating graphic based menus (PNG or GIF files)
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=384&cHash=93a7644cba
 */
class tslib_gmenu extends tslib_menu {

	/**
	 * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
	 * Calls makeGifs() for all "normal" items and if configured for, also the "rollover" items.
	 * 
	 * @return	void		
	 * @see tslib_menu::procesItemStates(), makeGifs()
	 */
	function generate()	{
		$splitCount = count($this->menuArr);
		if ($splitCount)	{
			list($NOconf,$ROconf) = $this->procesItemStates($splitCount);

				//store initial count value
			$temp_HMENU_MENUOBJ = $GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'];
			$temp_MENUOBJ = $GLOBALS['TSFE']->register['count_MENUOBJ'];
				// Now we generate the giffiles:
			$this->makeGifs($NOconf,'NO');
				// store count from NO obj
			$tempcnt_HMENU_MENUOBJ = $GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'];
			$tempcnt_MENUOBJ = $GLOBALS['TSFE']->register['count_MENUOBJ'];

			if ($this->mconf['debugItemConf'])	{echo '<h3>$NOconf:</h3>';	debug($NOconf);	}
			if ($ROconf)	{		// RollOver
					//start recount for rollover with initial values
				$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ']= $temp_HMENU_MENUOBJ;
				$GLOBALS['TSFE']->register['count_MENUOBJ']= $temp_MENUOBJ;
				$this->makeGifs($ROconf,'RO');
				if ($this->mconf['debugItemConf'])	{echo '<h3>$ROconf:</h3>';	debug($ROconf);	}
			}
				// use count from NO obj
			$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'] = $tempcnt_HMENU_MENUOBJ;
			$GLOBALS['TSFE']->register['count_MENUOBJ'] = $tempcnt_MENUOBJ;
		}
	}

	/**
	 * Will traverse input array with configuratoin per-item and create corresponding GIF files for the menu.
	 * The data of the files are stored in $this->result
	 * 
	 * @param	array		Array with configuration for each item.
	 * @param	string		Type of images: normal ("NO") or rollover ("RO"). Valid values are "NO" and "RO"
	 * @return	void		
	 * @access private
	 * @see generate()
	 */
	function makeGifs($conf, $resKey)	{
		$isGD = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'];
	
		if (!is_array($conf)) {
			$conf = Array();
		}
		
		$totalWH=array();
		$items = count($conf);
		if ($isGD)	{
				// generate the gif-files. the $menuArr is filled with some values like output_w, output_h, output_file
			$Hcounter = 0;
			$Wcounter = 0;
			$Hobjs = $this->mconf['applyTotalH'];
			if ($Hobjs)	{$Hobjs = t3lib_div::intExplode(',',$Hobjs);}
			$Wobjs = $this->mconf['applyTotalW'];
			if ($Wobjs)	{$Wobjs = t3lib_div::intExplode(',',$Wobjs);}
			$minDim = $this->mconf['min'];
			if ($minDim) {$minDim = tslib_cObj::calcIntExplode(',',$minDim.',');}
			$maxDim = $this->mconf['max'];
			if ($maxDim) {$maxDim = tslib_cObj::calcIntExplode(',',$maxDim.',');}
		
			if ($minDim)	{
				$conf[$items]=$conf[$items-1];
				$this->menuArr[$items]=Array();
				$items = count($conf);
			}
	
			// TOTAL width 
			if ($this->mconf['useLargestItemX'] || $this->mconf['useLargestItemY'] || $this->mconf['distributeX'] || $this->mconf['distributeY'])	{
				$totalWH = $this->findLargestDims($conf,$items,$Hobjs,$Wobjs,$minDim,$maxDim);
			}
		}

		$c=0;
		$maxFlag=0;
		$distributeAccu=array('H'=>0,'W'=>0);
		reset($conf);
		while (list($key,$val)=each($conf))	{
			$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ']++;
			$GLOBALS['TSFE']->register['count_MENUOBJ']++;

			if ($items==($c+1) && $minDim)	{
				$Lobjs = $this->mconf['removeObjectsOfDummy'];
				if ($Lobjs)	{
					$Lobjs = t3lib_div::intExplode(',',$Lobjs);
					reset($Lobjs);
					while(list(,$remItem)=each($Lobjs))	{
						unset($val[$remItem]);
						unset($val[$remItem.'.']);
					}
				}
				
				$flag =0;
				$tempXY = explode(',',$val['XY']);
				if ($Wcounter<$minDim[0])	{$tempXY[0]=$minDim[0]-$Wcounter; $flag=1;}
				if ($Hcounter<$minDim[1])	{$tempXY[1]=$minDim[1]-$Hcounter; $flag=1;}
				$val['XY'] = implode($tempXY,',');
				if (!$flag){break;}
			}
			$c++;


			if ($isGD)	{
					// Pre-working the item
				$gifCreator = t3lib_div::makeInstance('tslib_gifBuilder');
				$gifCreator->init();
				$gifCreator->start($val,$this->menuArr[$key]);

					// If useLargestItemH/W is specified
				if (count($totalWH) && ($this->mconf['useLargestItemX'] || $this->mconf['useLargestItemY']))	{
					$tempXY = explode(',',$gifCreator->setup['XY']);
					if ($this->mconf['useLargestItemX'])	{$tempXY[0] = max($totalWH['W']);}
					if ($this->mconf['useLargestItemY'])	{$tempXY[1] = max($totalWH['H']);}
						// regenerate the new values...
					$val['XY'] = implode($tempXY,',');
					$gifCreator = t3lib_div::makeInstance('tslib_gifBuilder');
					$gifCreator->init();
					$gifCreator->start($val,$this->menuArr[$key]);
				}

					// If distributeH/W is specified
				if (count($totalWH) && ($this->mconf['distributeX'] || $this->mconf['distributeY']))	{
					$tempXY = explode(',',$gifCreator->setup['XY']);
					
					if ($this->mconf['distributeX'])	{
						$diff = $this->mconf['distributeX']-$totalWH['W_total']-$distributeAccu['W'];
						$compensate = round($diff /($items-$c+1));
						$distributeAccu['W']+=$compensate;
						$tempXY[0] = $totalWH['W'][$key]+$compensate;
					}
					if ($this->mconf['distributeY'])	{
						$diff = $this->mconf['distributeY']-$totalWH['H_total']-$distributeAccu['H'];
						$compensate = round($diff /($items-$c+1));
						$distributeAccu['H']+=$compensate;
						$tempXY[1] = $totalWH['H'][$key]+$compensate;
					}
						// regenerate the new values...
					$val['XY'] = implode($tempXY,',');
					$gifCreator = t3lib_div::makeInstance('tslib_gifBuilder');
					$gifCreator->init();
					$gifCreator->start($val,$this->menuArr[$key]);
				}

					// If max dimensions are specified
				if ($maxDim)	{
					$tempXY = explode(',',$val['XY']);
					if ($maxDim[0] && $Wcounter+$gifCreator->XY[0]>=$maxDim[0])	{$tempXY[0]==$maxDim[0]-$Wcounter; $maxFlag=1;}
					if ($maxDim[1] && $Hcounter+$gifCreator->XY[1]>=$maxDim[1])	{$tempXY[1]=$maxDim[1]-$Hcounter; $maxFlag=1;}
					if ($maxFlag)	{
						$val['XY'] = implode($tempXY,',');
						$gifCreator = t3lib_div::makeInstance('tslib_gifBuilder');
						$gifCreator->init();
						$gifCreator->start($val,$this->menuArr[$key]);
					}
				}
	
	
	
	
				// displace
				if ($Hobjs)	{
					reset($Hobjs);
					while(list(,$index)=each($Hobjs))	{
						if ($gifCreator->setup[$index] && $gifCreator->setup[$index.'.'])	{
							$oldOffset = explode(',',$gifCreator->setup[$index.'.']['offset']);
							$gifCreator->setup[$index.'.']['offset'] = implode($gifCreator->applyOffset($oldOffset,Array(0,-$Hcounter)), ',');
						}
					}
				}
	
				if ($Wobjs)	{
					reset($Wobjs);
					while(list(,$index)=each($Wobjs))	{
						if ($gifCreator->setup[$index] && $gifCreator->setup[$index.'.'])	{
							$oldOffset = explode(',',$gifCreator->setup[$index.'.']['offset']);
							$gifCreator->setup[$index.'.']['offset'] = implode($gifCreator->applyOffset($oldOffset,Array(-$Wcounter,0)), ',');
						}
					}
				}
			}
			
				// Finding alternative GIF names if any (by altImgResource)
			$gifFileName='';
			if ($conf[$key]['altImgResource'] || is_array($conf[$key]['altImgResource.']))	{
				if (!is_object($cObj)) {$cObj=t3lib_div::makeInstance('tslib_cObj');}
				$cObj->start($this->menuArr[$key],'pages');
				$altImgInfo = $cObj->getImgResource($conf[$key]['altImgResource'],$conf[$key]['altImgResource.']);
				$gifFileName=$altImgInfo[3];
			}

				// If an alternative names was NOT given, find the GIFBUILDER name.
			if (!$gifFileName && $isGD)	{
				$gifFileName = $gifCreator->fileName('m_');
			}
			// generation
			if (@file_exists($gifFileName))	{		// File exists
				$info = @getimagesize($gifFileName);
				$this->result[$resKey][$key]['output_w']=intval($info[0]);
				$this->result[$resKey][$key]['output_h']=intval($info[1]);
				$this->result[$resKey][$key]['output_file']=$gifFileName;
			} elseif ($isGD) {		// file is generated
				$gifCreator->make();
				$this->result[$resKey][$key]['output_w']=$gifCreator->w;
				$this->result[$resKey][$key]['output_h']=$gifCreator->h;
				$this->result[$resKey][$key]['output_file'] = $gifFileName;
				$gifCreator->output($this->result[$resKey][$key]['output_file']);
				$gifCreator->destroy();
			}
			$this->result[$resKey][$key]['output_file'] = t3lib_div::png_to_gif_by_imagemagick($this->result[$resKey][$key]['output_file']);
			$this->result[$resKey][$key]['wrap']=$conf[$key]['wrap'];
			$this->result[$resKey][$key]['noLink']=$conf[$key]['noLink'];
			$this->result[$resKey][$key]['altTarget']=$conf[$key]['altTarget'];
			$this->result[$resKey][$key]['imgParams']=$conf[$key]['imgParams'];
				
			$Hcounter+=$this->result[$resKey][$key]['output_h'];		// counter is increased
			$Wcounter+=$this->result[$resKey][$key]['output_w'];		// counter is increased

			if ($maxFlag){break;}
		}
	}

	/**
	 * Function searching for the largest width and height of the menu items to be generated.
	 * Uses some of the same code as makeGifs and even instantiates some gifbuilder objects BUT does not render the images - only reading out which width they would have.
	 * Remember to upgrade the code in here if the makeGifs function is updated.
	 * 
	 * @param	array		Same configuration array as passed to makeGifs()
	 * @param	integer		The number of menu items
	 * @param	array		Array with "applyTotalH" numbers
	 * @param	array		Array with "applyTotalW" numbers
	 * @param	array		Array with "min" x/y
	 * @param	array		Array with "max" x/y
	 * @return	array		Array with keys "H" and "W" which are in themselves arrays with the heights and widths of menu items inside. This can be used to find the max/min size of the menu items.
	 * @access private
	 * @see makeGifs()
	 */
	function findLargestDims($conf,$items,$Hobjs,$Wobjs,$minDim,$maxDim)	{
		$totalWH = array(
			'W' => array(),
			'H' => array(),
			'W_total' => 0,
			'H_total' => 0
		);

		$Hcounter = 0;
		$Wcounter = 0;
		$c=0;
		$maxFlag=0;
		reset($conf);
		while (list($key,$val)=each($conf))	{
			// SAME CODE AS makeGifs()! BEGIN
			if ($items==($c+1) && $minDim)	{
				$Lobjs = $this->mconf['removeObjectsOfDummy'];
				if ($Lobjs)	{
					$Lobjs = t3lib_div::intExplode(',',$Lobjs);
					reset($Lobjs);
					while(list(,$remItem)=each($Lobjs))	{
						unset($val[$remItem]);
						unset($val[$remItem.'.']);
					}
				}
				
				$flag =0;
				$tempXY = explode(',',$val['XY']);
				if ($Wcounter<$minDim[0])	{$tempXY[0]=$minDim[0]-$Wcounter; $flag=1;}
				if ($Hcounter<$minDim[1])	{$tempXY[1]=$minDim[1]-$Hcounter; $flag=1;}
				$val['XY'] = implode($tempXY,',');
				if (!$flag){break;}
			}
			$c++;

			$gifCreator = t3lib_div::makeInstance('tslib_gifBuilder');
			$gifCreator->init();
			$gifCreator->start($val,$this->menuArr[$key]);
			if ($maxDim)	{
				$tempXY = explode(',',$val['XY']);
				if ($maxDim[0] && $Wcounter+$gifCreator->XY[0]>=$maxDim[0])	{$tempXY[0]==$maxDim[0]-$Wcounter; $maxFlag=1;}
				if ($maxDim[1] && $Hcounter+$gifCreator->XY[1]>=$maxDim[1])	{$tempXY[1]=$maxDim[1]-$Hcounter; $maxFlag=1;}
				if ($maxFlag)	{
					$val['XY'] = implode($tempXY,',');
					$gifCreator = t3lib_div::makeInstance('tslib_gifBuilder');
					$gifCreator->init();
					$gifCreator->start($val,$this->menuArr[$key]);
				}
			}
			// SAME CODE AS makeGifs()! END

				// Setting the width/height
			$totalWH['W'][$key]=$gifCreator->XY[0];
			$totalWH['H'][$key]=$gifCreator->XY[1];
			$totalWH['W_total']+=$gifCreator->XY[0];
			$totalWH['H_total']+=$gifCreator->XY[1];
				// ---- // 
			
			$Hcounter+=$gifCreator->XY[1];		// counter is increased
			$Wcounter+=$gifCreator->XY[0];		// counter is increased

			if ($maxFlag){break;}
		}
		return $totalWH;
	}

	/**
	 * Traverses the ->result['NO'] array of menu items configuration (made by ->generate()) and renders the HTML of each item (the images themselves was made with makeGifs() before this. See ->generate())
	 * During the execution of this function many internal methods prefixed "extProc_" from this class is called and many of these are for now dummy functions. But they can be used for processing as they are used by the GMENU_LAYERS
	 * 
	 * @return	string		The HTML for the menu (returns result through $this->extProc_finish(); )
	 */
	function writeMenu()	{
		if (is_array($this->menuArr) && is_array($this->result) && count($this->result) && is_array($this->result['NO']))	{
			$this->WMresult='';
			$this->INPfixMD5 = substr(md5(microtime().$this->GMENU_fixKey),0,4);
			$this->WMmenuItems = count($this->result['NO']);
			$this->extProc_init();
			for ($key=0;$key<$this->WMmenuItems;$key++)	{
				if ($this->result['NO'][$key]['output_file'])	{
					$this->I =array();
					$this->I['key'] = $key;
					$this->I['INPfix']= $this->imgNameNotRandom?'':'_'.$this->INPfixMD5.'_'.$key;
					$this->I['val'] = $this->result['NO'][$key];
					$this->I['title'] = $this->getPageTitle($this->menuArr[$key]['title'],$this->menuArr[$key]['nav_title']);
					$this->I['uid'] = $this->menuArr[$key]['uid'];
					$this->I['mount_pid'] = $this->menuArr[$key]['mount_pid'];
					$this->I['pid'] = $this->menuArr[$key]['pid'];
					$this->I['spacer'] = $this->menuArr[$key]['isSpacer'];
					if (!$this->I['uid'] && !$this->menuArr[$key]['_OVERRIDE_HREF']) {$this->I['spacer']=1;}
					$this->I['noLink'] = ($this->I['spacer'] || $this->I['val']['noLink'] || !count($this->menuArr[$key]));		// !count($this->menuArr[$key]) means that this item is a dummyItem
					$this->I['name']='';

						// Get link.
					$this->I['linkHREF'] = $this->link($key,$this->I['val']['altTarget'],$this->mconf['forceTypeValue']);
	
						// Set noBlur
					if (!$this->mconf['noBlur'])	{
						$this->I['linkHREF']['onFocus']='blurLink(this);';
					}
	
						// Set rollover
					if ($this->result['RO'][$key] && !$this->I['noLink'])	{
						$this->I['theName'] = $this->imgNamePrefix.$this->I['uid'].$this->I['INPfix'];
						$this->I['name'] = ' name="'.$this->I["theName"].'"';
						$this->I['linkHREF']['onMouseover']=$this->WMfreezePrefix.'over(\''.$this->I['theName'].'\');';
						$this->I['linkHREF']['onMouseout']=$this->WMfreezePrefix.'out(\''.$this->I['theName'].'\');';
						$GLOBALS['TSFE']->JSImgCode.= chr(10).$this->I['theName'].'_n=new Image(); '.$this->I['theName'].'_n.src = "'.$GLOBALS['TSFE']->absRefPrefix.$this->I['val']['output_file'].'"; ';
						$GLOBALS['TSFE']->JSImgCode.= chr(10).$this->I['theName'].'_h=new Image(); '.$this->I['theName'].'_h.src = "'.$GLOBALS['TSFE']->absRefPrefix.$this->result['RO'][$key]['output_file'].'"; ';
						$GLOBALS['TSFE']->imagesOnPage[]=$this->result['RO'][$key]['output_file'];
						$GLOBALS['TSFE']->setJS('mouseOver');
						$this->extProc_RO($key);
					}
						// Set access key
					if ($this->mconf['accessKey'])	{
						$this->I['accessKey'] = $this->accessKey($this->I['title']);
					} else {
						$this->I['accessKey']=Array();
					}
	
						// Set altText
					$this->I['altText'] = $this->mconf['disableAltText'] ? '' : $this->I['title'].$this->I['accessKey']['alt'];
	
						// Calling extra processing function
					$this->extProc_beforeLinking($key);
					
						// Set linking
					if (!$this->I['noLink'])	{
						$this->setATagParts();
					} else {
						$this->I['A1'] = '';
						$this->I['A2'] = '';
					}
					$this->I['IMG'] = '<img src="'.$GLOBALS['TSFE']->absRefPrefix.$this->I['val']['output_file'].'" width="'.$this->I['val']['output_w'].'" height="'.$this->I['val']['output_h'].'" border="0" alt="'.htmlspecialchars($this->I['altText']).'"'.$this->I['name'].($this->I['val']['imgParams']?' '.$this->I['val']['imgParams']:'').' />';
					
						// Make before, middle and after parts
					$this->I['parts'] = array();
					$this->I['parts']['ATag_begin'] = $this->I['A1'];
					$this->I['parts']['image'] = $this->I['IMG'];
					$this->I['parts']['ATag_end'] = $this->I['A2'];
	
						// Passing I to a user function
					if ($this->mconf['IProcFunc'])	{
						$this->I = $this->userProcess('IProcFunc',$this->I);
					}

						// Putting the item together.
						// Merge parts + beforeAllWrap
					$this->I['theItem']= implode('',$this->I['parts']);
					$this->I['theItem']= $this->extProc_beforeAllWrap($this->I['theItem'],$key);
	
						// 'allWrap' (for GMENU's this is just the 'wrap' property)
					$this->I['theItem']= $this->tmpl->wrap($this->I['theItem'],$this->I['val']['wrap']);
					$GLOBALS['TSFE']->imagesOnPage[]=$this->I['val']['output_file'];
	
					$this->extProc_afterLinking($key);
				}
			}
			return $this->extProc_finish();
		}
	}

	/**
	 * Called right before the traversing of $this->result begins.
	 * Can be used for various initialization
	 * 
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_init()
	 */
	function extProc_init()	{
	}

	/**
	 * Called after all processing for RollOver of an element has been done.
	 * 
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found OR $this->result['RO'][$key] where the configuration for that elements RO version is found!
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_RO()
	 */
	function extProc_RO($key)	{
	}

	/**
	 * Called right before the creation of the link for the menu item
	 * 
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_beforeLinking()
	 */
	function extProc_beforeLinking($key)	{
	}

	/**
	 * Called right after the creation of links for the menu item. This is also the last function call before the for-loop traversing menu items goes to the next item.
	 * This function MUST set $this->WMresult.=[HTML for menu item] to add the generated menu item to the internal accumulation of items.
	 * Further this calls the subMenu function in the parent class to create any submenu there might be.
	 * 
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void		
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_afterLinking(), tslib_menu::subMenu()
	 */
	function extProc_afterLinking($key)	{
		$this->WMresult.=$this->I['theItem'];
		if (!$this->I['spacer'])	{
			$this->WMresult.= $this->subMenu($this->I['uid'],$this->I['mount_pid']);
		}
	}


	/**
	 * Called before the "wrap" happens on the menu item.
	 * 
	 * @param	string		The current content of the menu item, $this->I['theItem'], passed along.
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	string		The modified version of $item, going back into $this->I['theItem']
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_beforeAllWrap()
	 */
	function extProc_beforeAllWrap($item,$key)	{
		return $item;
	}

	/**
	 * Called before the writeMenu() function returns (only if a menu was generated)
	 * 
	 * @return	string		The total menu content should be returned by this function
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_finish()
	 */
	function extProc_finish()	{
		return $this->tmpl->wrap($this->WMresult,$this->mconf['wrap']).$this->WMextraScript;
	}
}






















/**
 * ImageMap based menus
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=389&cHash=fcf18c5d9f
 */
class tslib_imgmenu extends tslib_menu {

	/**
	 * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
	 * Calls makeImageMap() to generate the image map image-file
	 * 
	 * @return	void		
	 * @see tslib_menu::procesItemStates(), makeImageMap()
	 */
	function generate()	{
		$splitCount = count($this->menuArr);
		if ($splitCount)	{
			list($NOconf) = $this->procesItemStates($splitCount);
		}
		if ($this->mconf['debugItemConf'])	{echo '<h3>$NOconf:</h3>';	debug($NOconf);	}
		$this->makeImageMap($NOconf);
	}

	/**
	 * Will traverse input array with configuratoin per-item and create corresponding GIF files for the menu.
	 * The data of the files are stored in $this->result
	 * 
	 * @param	array		Array with configuration for each item.
	 * @return	void		
	 * @access private
	 * @see generate()
	 */
	function makeImageMap($conf)	{
		if (!is_array($conf)) {
			$conf = Array();
		}
		if (is_array($this->mconf['main.']))	{
			$gifCreator = t3lib_div::makeInstance('tslib_gifBuilder');
			$gifCreator->init();
			
			$itemsConf = $conf;
			$conf = $this->mconf['main.'];
			if (is_array($conf))	{
				$gifObjCount = 0;

				$sKeyArray=t3lib_TStemplate::sortedKeyList($conf);
				$gifObjCount=intval(end($sKeyArray));

				$lastOriginal = $gifObjCount;

					// Now we add graphical objects to the gifbuilder-setup
				reset($itemsConf);
				$waArr = Array();
				while (list($key,$val)=each($itemsConf))	{
					if (is_array($val))	{
						$gifObjCount++;
						$waArr[$key]['free']=$gifObjCount;

						$sKeyArray=t3lib_TStemplate::sortedKeyList($val);
						reset($sKeyArray);
						while(list(,$theKey)=each($sKeyArray))	{
							$theValue=$val[$theKey];
							
							
							if (intval($theKey) && $theValArr=$val[$theKey.'.'])	{
								$cObjData = $this->menuArr[$key] ? $this->menuArr[$key] : Array();
							
								$gifObjCount++;
								if ($theValue=='TEXT') {
									$waArr[$key]['textNum']=$gifObjCount;
									
									$gifCreator->data = $cObjData;
									$theValArr = $gifCreator->checkTextObj($theValArr);
									unset($theValArr['text.']);	// if this is not done it seems that imageMaps will be rendered wrong!!
										// check links
										
									$LD = $this->tmpl->linkData($this->menuArr[$key],$this->mconf['target'],'','',array(),'',$this->mconf['forceTypeValue']);
									if ($theValArr['imgMap.']['url']=='')	{
										$theValArr['imgMap.']['url'] = $LD['totalURL'];
									}
									if ($theValArr['imgMap.']['target']=='')	{
										$theValArr['imgMap.']['target'] = $LD['target'];
									}
									if ($theValArr['imgMap.']['noBlur']=='')	{
										$theValArr['imgMap.']['noBlur'] = $this->mconf['noBlur'];
									}
								}
									// This code goes one level in if the object is an image. If 'file' and/or 'mask' appears to be GIFBUILDER-objects, they are both searched for TEXT objects, and if a textobj is found, it's checked with the currently loaded record!!
								if ($theValue=='IMAGE')	{
									if ($theValArr['file']=='GIFBUILDER')	{
										$temp_sKeyArray=t3lib_TStemplate::sortedKeyList($theValArr['file.']);
										reset($temp_sKeyArray);
										while(list(,$temp_theKey)=each($temp_sKeyArray))	{
											if ($theValArr['mask.'][$temp_theKey]=='TEXT')	{
												$gifCreator->data = $this->menuArr[$key] ? $this->menuArr[$key] : Array();
												$theValArr['mask.'][$temp_theKey.'.'] = $gifCreator->checkTextObj($theValArr['mask.'][$temp_theKey.'.']);
												unset($theValArr['mask.'][$temp_theKey.'.']['text.']);	// if this is not done it seems that imageMaps will be rendered wrong!!
											}
										}
									}
									if ($theValArr['mask']=='GIFBUILDER')	{
										$temp_sKeyArray=t3lib_TStemplate::sortedKeyList($theValArr['mask.']);
										reset($temp_sKeyArray);
										while(list(,$temp_theKey)=each($temp_sKeyArray))	{
											if ($theValArr['mask.'][$temp_theKey]=='TEXT')	{
												$gifCreator->data = $this->menuArr[$key] ? $this->menuArr[$key] : Array();
												$theValArr['mask.'][$temp_theKey.'.'] = $gifCreator->checkTextObj($theValArr['mask.'][$temp_theKey.'.']);
												unset($theValArr['mask.'][$temp_theKey.'.']['text.']);	// if this is not done it seems that imageMaps will be rendered wrong!!
											}
										}
									}
								}
								
									// Checks if disabled is set...
								$setObjFlag=1;
								if ($theValArr['if.'])	{
									$cObj =t3lib_div::makeInstance('tslib_cObj');
									$cObj->start($cObjData,'pages');
									if (!$cObj->checkIf($theValArr['if.']))	{
										$setObjFlag=0;
									}
									unset($theValArr['if.']);
								}
									// Set the object!
								if ($setObjFlag)	{
									$conf[$gifObjCount] = $theValue;
									$conf[$gifObjCount.'.'] = $theValArr;
								}
							}
						}
					}
				}

				$gifCreator->start($conf,$GLOBALS['TSFE']->page);
					// calculations			

				$sum=Array(0,0,0,0);
				reset($waArr);
				while (list($key,$val)=each($waArr))	{
					if ($dConf[$key] =$itemsConf[$key]['distrib'])	{
						$textBB = $gifCreator->objBB[$val['textNum']];
						$dConf[$key] = str_replace('textX',$textBB[0],$dConf[$key]);
						$dConf[$key] = str_replace('textY',$textBB[1],$dConf[$key]);
						$dConf[$key] = t3lib_div::intExplode(',',$gifCreator->calcOffset($dConf[$key]));
					}
				}
				$workArea = t3lib_div::intExplode(',',$gifCreator->calcOffset($this->mconf['dWorkArea']));
				reset($waArr);
				while (list($key,$val)=each($waArr))	{
					$index = $val['free'];
					$gifCreator->setup[$index] = 'WORKAREA';
					$workArea[2] = $dConf[$key][2] ? $dConf[$key][2] : $dConf[$key][0];
					$workArea[3] = $dConf[$key][3] ? $dConf[$key][3] : $dConf[$key][1];

					$gifCreator->setup[$index.'.']['set'] = implode($workArea,',');
					$workArea[0]+=$dConf[$key][0];
					$workArea[1]+=$dConf[$key][1];
				}

				if ($this->mconf['debugRenumberedObject'])	{echo '<h3>Renumbered GIFBUILDER object:</h3>';	debug($gifCreator->setup);}
				
				$gifFileName = $gifCreator->fileName('m_');
					// Gets the ImageMap from the cache...
				$imgHash = md5($gifFileName);
				$imgMap = $this->sys_page->getHash($imgHash, 0);
			
				if ($imgMap && @file_exists($gifFileName))	{		// File exists
					$info = @getimagesize($gifFileName);
					$w=$info[0];
					$h=$info[1];
				} else {		// file is generated
					$gifCreator->make();
					$w=$gifCreator->w;
					$h=$gifCreator->h;
					$gifCreator->output($gifFileName);
					$gifCreator->destroy();
					$imgMap=$gifCreator->map;
					$this->sys_page->storeHash($imgHash, $imgMap, 'MENU IMAGEMAP');
				}
				$imgMap.=$this->mconf['imgMapExtras'];

				$gifFileName = t3lib_div::png_to_gif_by_imagemagick($gifFileName);
				$this->result = Array('output_file'=>$gifFileName, 'output_w'=>$w, 'output_h'=>$h, 'imgMap'=>$imgMap);
			}
		}
	}

	/**
	 * Returns the HTML for the image map menu.
	 * If ->result is true it will create the HTML for the image map menu.
	 * 
	 * @return	string		The HTML for the menu
	 */
	function writeMenu()	{
		if ($this->result)	{
			$res = $this->result;
			$menuName = 'menu_'.t3lib_div::shortMD5($res['imgMap']);	// shortMD5 260900  
			$result = '<img src="'.$GLOBALS['TSFE']->absRefPrefix.$res['output_file'].'" width="'.$res['output_w'].'" height="'.$res['output_h'].'" usemap="#'.$menuName.'" border="0" '.$this->mconf['params'];
			if (!strstr($result,'alt="'))	$result.=' alt="Menu Image Map"';	// Adding alt attribute if not set.
			$result.= ' /><map name="'.$menuName.'">'.$res['imgMap'].'</map>';

			$GLOBALS['TSFE']->imagesOnPage[]=$res['output_file'];

			return $this->tmpl->wrap($result,$this->mconf['wrap']);
		}
	}
}





















/**
 * JavaScript/Selectorbox based menus
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=391&cHash=563435abbc
 */
class tslib_jsmenu extends tslib_menu {

	/**
	 * Dummy. Should do nothing, because we don't use the result-array here!
	 * 
	 * @return	void		
	 */
	function generate()	{
	}

	/**
	 * Creates the HTML (mixture of a <form> and a JavaScript section) for the JavaScript menu (basically an array of selector boxes with onchange handlers)
	 * 
	 * @return	string		The HTML code for the menu
	 */
	function writeMenu()	{
		if ($this->id)	{
				// Making levels:
			$levels = t3lib_div::intInRange($this->mconf['levels'],1,5);
			$this->levels = $levels;
			$this->JSVarName='eid';
			$this->JSMenuName= $this->mconf['menuName'] ? $this->mconf['menuName'] : 'JSmenu';
			
			$JScode="\n var ".$this->JSMenuName." = new JSmenu(".$levels.",'".$this->JSMenuName."Form');";

			for ($a=1;$a<=$levels;$a++)	{
				$JScode.="\n var ".$this->JSVarName.$a."=0;";
			}
			$JScode.= $this->generate_level($levels,1,$this->id,$this->menuArr)."\n";

			$GLOBALS['TSFE']->additionalHeaderData['JSMenuCode']='<script type="text/javascript" src="'.$GLOBALS['TSFE']->absRefPrefix.'t3lib/jsfunc.menu.js"></script>';
			$GLOBALS['TSFE']->JSCode.=$JScode;

				// Printing:
			$allFormCode="";
			for ($a=1;$a<=$this->levels;$a++)	{
				$formCode='';
				$levelConf = $this->mconf[$a.'.'];
				$length = $levelConf['width'] ? $levelConf['width'] : 14;
				$lenghtStr='';
				for ($b=0;$b<$length;$b++)	{
					$lenghtStr.='_';
				}
				$height = $levelConf['elements'] ? $levelConf['elements'] : 5;
	
				$formCode.= '<select name="selector'.$a.'" onchange="'.$this->JSMenuName.'.act('.$a.');"'.($levelConf['additionalParams']?' '.$levelConf['additionalParams']:'').'>';
				for ($b=0;$b<$height;$b++)	{
					$formCode.= '<option value="0">';
					if ($b==0)	{
						$formCode.= $lenghtStr;
					}
					$formCode.='</option>';
				}
				$formCode.= '</select>';
				$allFormCode.=$this->tmpl->wrap($formCode,$levelConf['wrap']);
			}
			$formCode = $this->tmpl->wrap($allFormCode,$this->mconf['wrap']);
	
			$formCode= '<form action="" method="post" name="'.$this->JSMenuName.'Form">'.$formCode.'</form>';
			$formCode.='<script type="text/javascript"> /*<![CDATA[*/ '.$this->JSMenuName.'.writeOut(1,'.$this->JSMenuName.'.openID,1); /*]]>*/ </script>';
			return $this->tmpl->wrap($formCode,$this->mconf['wrapAfterTags']);	
		}
	}

	/**
	 * Generates a number of lines of JavaScript code for a menu level.
	 * Calls itself recursively for additional levels.
	 * 
	 * @param	integer		Number of levels to generate
	 * @param	integer		Current level being generated - and if this number is less than $levels it will call itself recursively with $count incremented
	 * @param	integer		Page id of the starting point.
	 * @param	array		$this->menuArr passed along
	 * @return	string		JavaScript code lines.
	 * @access private
	 */
	function generate_level($levels,$count,$pid,$menuItemArray='')	{
		$levelConf = $this->mconf[$count.'.'];
		
		$temp = is_array($menuItemArray) ? $menuItemArray : $this->sys_page->getMenu($pid);
		reset($temp);

		$var = $this->JSVarName;
		$menuName = $this->JSMenuName;

		$banUidArray=array();
		if (trim($this->conf['excludeUidList']))	{
			$banUidArray = t3lib_div::intExplode(',', $this->conf['excludeUidList']);
		}

		$parent = $count==1 ? 0 : $var.($count-1);
		$prev=0;
		$c=0;
		reset($temp);
		while(list($uid,$data)=each($temp))	{
			$spacer = (t3lib_div::inList($this->spacerIDList,$data['doktype'])?1:0);		// if item is a spacer, $spacer is set
			if ($this->mconf['SPC'] || !$spacer)	{	// If the spacer-function is not enabled, spacers will not enter the $menuArr
				if (!t3lib_div::inList('5,6',$data['doktype']) && !t3lib_div::inArray($banUidArray,$uid))	{		// Page may not be 'not_in_menu' or 'Backend User Section' + not in banned uid's
					if ($count<$levels)	{
						$addLines = $this->generate_level($levels,$count+1,$data['uid']);
					} else {
						$addLines = '';
					}
					$title=rawurlencode($data['title']);
					$url='';
					$target='';
					if ((!$addLines && !$levelConf['noLink']) || $levelConf['alwaysLink']) {
						$LD = $this->tmpl->linkData($data,$this->mconf['target'],'','',array(),'',$this->mconf['forceTypeValue']);
						$url = rawurlencode($LD['totalURL']);
						$target = rawurlencode($LD['target']);
					}
					$codeLines.="\n".$var.$count."=".$menuName.".add(".$parent.",".$prev.",0,'".$title."','".$url."','".$target."');";
						// If the active one should be chosen...
					$active = ($levelConf['showActive'] && $data['uid'] == $this->tmpl->rootLine[$count]['uid']);
						// If the first item should be shown
					$first = (!$c && $levelConf['showFirst']);
						// do it...
					if ($active || $first)	{
						if ($count==1)	{
							$codeLines.="\n".$menuName.".openID = ".$var.$count.";";
						} else {
							$codeLines.="\n".$menuName.".entry[".$parent."].openID = ".$var.$count.";";
						}
					}
						// Add submenu...
					$codeLines.=$addLines;
	
					$prev=$var.$count;
					$c++;
				}
			}
		}
		if ($this->mconf['firstLabelGeneral'] && !$levelConf['firstLabel'])	{$levelConf['firstLabel'] = $this->mconf['firstLabelGeneral'];}
		if ($levelConf['firstLabel'] && $codeLines)	{	$codeLines.="\n".$menuName.".defTopTitle[".$count."] = unescape('".rawurlencode($levelConf['firstLabel'])."');";	}
		return trim($codeLines);
	}	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_menu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_menu.php']);
}

?>