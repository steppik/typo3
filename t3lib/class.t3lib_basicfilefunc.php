<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Contains class with basic file management functions
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   80: class t3lib_basicFileFunctions
 *
 *              SECTION: Checking functions
 *  132:     function init($mounts, $f_ext)
 *  149:     function getTotalFileInfo($wholePath)
 *  169:     function is_allowed($iconkey,$type)
 *  194:     function checkIfFullAccess($theDest)
 *  208:     function is_webpath($path)
 *  228:     function checkIfAllowed($ext, $theDest, $filename='')
 *  238:     function checkFileNameLen($fileName)
 *  248:     function is_directory($theDir)
 *  265:     function isPathValid($theFile)
 *  280:     function getUniqueName($theFile, $theDest, $dontCheckForUnique=0)
 *  323:     function checkPathAgainstMounts($thePath)
 *  341:     function blindPath($thePath)
 *  357:     function findTempFolder()
 *
 *              SECTION: Cleaning functions
 *  386:     function cleanDirectoryName($theDir)
 *  396:     function rmDoubleSlash($string)
 *  406:     function slashPath($path)
 *  419:     function cleanFileName($fileName)
 *  430:     function formatSize($sizeInBytes)
 *
 * TOTAL FUNCTIONS: 18
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */




/**
 * Contains functions for management, validation etc of files in TYPO3, using the concepts of web- and ftp-space. Please see the comment for the init() function
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see t3lib_basicFileFunctions::init()
 */
class t3lib_basicFileFunctions	{
	var $getUniqueNamePrefix = '';	// Prefix which will be prepended the file when using the getUniqueName-function
	var $maxNumber = 20;			// This number decides the highest allowed appended number used on a filename before we use naming with unique strings
	var $uniquePrecision = 6;		// This number decides how many characters out of a unique MD5-hash that is appended to a filename if getUniqueName is asked to find an available filename.
	var $maxInputNameLen = 30;		// This is the maximum length of names treated by cleanFileName()
	var $tempFN = '_temp_';			// Temp-foldername. A folder in the root of one of the mounts with this name is regarded a TEMP-folder (used for upload from clipboard)

		// internal
	var $f_ext = Array();			// See comment in header
	var $mounts = Array();			// See comment in header
	var $webPath ='';				// Set to DOCUMENT_ROOT.
	var $isInit = 0;				// Set to true after init()/start();



	/**********************************
	 *
	 * Checking functions
	 *
	 **********************************/

	/**
	 * Constructor
	 * This function should be called to initialise the internal arrays $this->mounts and $this->f_ext
	 *
	 *  A typical example of the array $mounts is this:
	 * 		$mounts[xx][path] = (..a mounted path..)
	 * 	the 'xx'-keys is just numerical from zero. There are also a [name] and [type] value that just denotes the mountname and type. Not used for athentication here.
	 * 	$this->mounts is traversed in the function checkPathAgainstMounts($thePath), and it is checked that $thePath is actually below one of the mount-paths
	 * 	The mountpaths are with a trailing '/'. $thePath must be with a trailing '/' also!
	 * 	As you can see, $this->mounts is very critical! This is the array that decides where the user will be allowed to copy files!!
	 *  Typically the global var $WEBMOUNTS would be passed along as $mounts
	 *
	 * 	A typical example of the array $f_ext is this:
	 * 		$f_ext['webspace']['allow']='';
	 * 		$f_ext['webspace']['deny']='php3,php';
	 * 		$f_ext['ftpspace']['allow']='*';
	 * 		$f_ext['ftpspace']['deny']='';
	 * 	The control of fileextensions goes in two catagories. Webspace and Ftpspace. Webspace is folders accessible from a webbrowser (below TYPO3_DOCUMENT_ROOT) and ftpspace is everything else.
	 * 	The control is done like this: If an extension matches 'allow' then the check returns true. If not and an extension matches 'deny' then the check return false. If no match at all, returns true.
	 * 	You list extensions comma-separated. If the value is a '*' every extension is allowed
	 * 	The list is case-insensitive when used in this class (see init())
	 *  Typically TYPO3_CONF_VARS['BE']['fileExtensions'] would be passed along as $f_ext.
	 *
	 *  Example:
	 * 	$basicff->init($GLOBALS['FILEMOUNTS'],$TYPO3_CONF_VARS['BE']['fileExtensions']);
	 *
	 * @param	array		Contains the paths of the file mounts for the current BE user. Normally $GLOBALS['FILEMOUNTS'] is passed. This variable is set during backend user initialization; $FILEMOUNTS = $BE_USER->returnFilemounts(); (see typo3/init.php)
	 * @param	array		Array with information about allowed and denied file extensions. Typically passed: $TYPO3_CONF_VARS['BE']['fileExtensions']
	 * @return	void
	 * @see typo3/init.php, t3lib_userAuthGroup::returnFilemounts()
	 */
	function init($mounts, $f_ext)	{
		$this->f_ext['webspace']['allow'] = t3lib_div::uniqueList(strtolower($f_ext['webspace']['allow']));
		$this->f_ext['webspace']['deny'] = t3lib_div::uniqueList(strtolower($f_ext['webspace']['deny']));
		$this->f_ext['ftpspace']['allow'] = t3lib_div::uniqueList(strtolower($f_ext['ftpspace']['allow']));
		$this->f_ext['ftpspace']['deny'] = t3lib_div::uniqueList(strtolower($f_ext['ftpspace']['deny']));

		$this->mounts = $mounts;
		$this->webPath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT');
		$this->isInit = 1;
	}

	/**
	 * Returns an array with a whole lot of fileinformation.
	 *
	 * @param	string		Filepath to existing file. Should probably be absolute. Filefunctions are performed on this value.
	 * @return	array		Information about the file in the filepath
	 */
	function getTotalFileInfo($wholePath)	{
		$theuser = getmyuid();
		$info = t3lib_div::split_fileref($wholePath);
		$info['tstamp'] = @filectime($wholePath);
		$info['size'] = @filesize($wholePath);
		$info['type'] = @filetype($wholePath);
		$info['owner'] = @fileowner($wholePath);
		$info['perms'] = @fileperms($wholePath);
		$info['writeable'] = ($info['perms']&2 || ($theuser==$info['owner'] && $info['perms']&128));
		$info['readable'] = ($info['perms']&4 || ($theuser==$info['owner'] && $info['perms']&256));
		return $info;
	}

	/**
	 * Checks if a $iconkey (fileextension) is allowed according to $this->f_ext.
	 *
	 * @param	string		The extension to check, eg. "php" or "html" etc.
	 * @param	string		Either "webspage" or "ftpspace" - points to a key in $this->f_ext
	 * @return	boolean		True if file extension is allowed.
	 */
	function is_allowed($iconkey,$type)	{
		if (isset($this->f_ext[$type]))	{
			$ik = strtolower($iconkey);
			if ($ik)	{
					// If the extension is found amongst the allowed types, we return true immediately
				if ($this->f_ext[$type]['allow']=='*' || t3lib_div::inList($this->f_ext[$type]['allow'],$ik))	return true;
					// If the extension is found amongst the denied types, we return false immediately
				if ($this->f_ext[$type]['deny']=='*' || t3lib_div::inList($this->f_ext[$type]['deny'],$ik))	return false;
					// If no match we return true
				return true;
			} else {	// If no extension:
				if ($this->f_ext[$type]['allow']=='*')	return true;
				if ($this->f_ext[$type]['deny']=='*')	return false;
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns true if you can operate of ANY file ('*') in the space $theDest is in ('webspace' / 'ftpspace')
	 *
	 * @param	string		Absolute path
	 * @return	boolean
	 */
	function checkIfFullAccess($theDest)	{
		$type = $this->is_webpath($theDest)?'webspace':'ftpspace';
		if (isset($this->f_ext[$type]))	{
			if ((string)$this->f_ext[$type]['deny']=='' || $this->f_ext[$type]['allow']=='*')	return true;
		}
	}

	/**
	 * Checks if $this->webPath (should be TYPO3_DOCUMENT_ROOT) is in the first part of $path
	 * Returns true also if $this->init is not set or if $path is empty...
	 *
	 * @param	string		Absolute path to check
	 * @return	boolean
	 */
	function is_webpath($path)	{
		if ($this->isInit)	{
			$testPath = $this->slashPath($path);
			$testPathWeb = $this->slashPath($this->webPath);
			if ($testPathWeb && $testPath)	{
				return t3lib_div::isFirstPartOfStr($testPath,$testPathWeb);
			}
		}
		return true;	// Its more safe to return true (as the webpath is more restricted) if something went wrong...
	}

	/**
	 * If the filename is given, check it against the TYPO3_CONF_VARS[BE][fileDenyPattern] +
	 * Checks if the $ext fileextension is allowed in the path $theDest (this is based on whether $theDest is below the $this->webPath)
	 *
	 * @param	string		File extension, eg. "php" or "html"
	 * @param	string		Absolute path for which to test
	 * @param	string		Filename to check against TYPO3_CONF_VARS[BE][fileDenyPattern]
	 * @return	boolean		True if extension/filename is allowed
	 */
	function checkIfAllowed($ext, $theDest, $filename='')	{
		return t3lib_div::verifyFilenameAgainstDenyPattern($filename) && $this->is_allowed($ext,($this->is_webpath($theDest)?'webspace':'ftpspace'));
	}

	/**
	 * Returns true if the input filename string is shorter than $this->maxInputNameLen.
	 *
	 * @param	string		Filename, eg "somefile.html"
	 * @return	boolean
	 */
	function checkFileNameLen($fileName)	{
		return strlen($fileName) <= $this->maxInputNameLen;
	}

	/**
	 * Cleans $theDir for slashes in the end of the string and returns the new path, if it exists on the server.
	 *
	 * @param	string		Directory path to check
	 * @return	string		Returns the cleaned up directory name if OK, otherwise false.
	 */
	function is_directory($theDir)	{
		if ($this->isPathValid($theDir))	{
			$theDir=$this->cleanDirectoryName($theDir);
			if (@is_dir($theDir))	{
				return $theDir;
			}
		}
		return false;
	}

	/**
	 * Wrapper for t3lib_div::validPathStr()
	 *
	 * @param	string		Filepath to evaluate
	 * @return	boolean		True, if no '//', '..' or '\' is in the $theFile
	 * @see	t3lib_div::validPathStr()
	 */
	function isPathValid($theFile)	{
		return t3lib_div::validPathStr($theFile);
	}

	/**
	 * Returns the destination path/filename of a unique filename/foldername in that path.
	 * If $theFile exists in $theDest (directory) the file have numbers appended up to $this->maxNumber. Hereafter a unique string will be appended.
	 * This function is used by fx. TCEmain when files are attached to records and needs to be uniquely named in the uploads/* folders
	 *
	 * @param	string		The input filename to check
	 * @param	string		The directory for which to return a unique filename for $theFile. $theDest MUST be a valid directory. Should be absolute.
	 * @param	boolean		If set the filename is returned with the path prepended without checking whether it already existed!
	 * @return	string		The destination absolute filepath (not just the name!) of a unique filename/foldername in that path.
	 * @see t3lib_TCEmain::checkValue()
	 */
	function getUniqueName($theFile, $theDest, $dontCheckForUnique=0)	{
		$theDest = $this->is_directory($theDest);	// $theDest is cleaned up
		$origFileInfo = t3lib_div::split_fileref($theFile);		// Fetches info about path, name, extention of $theFile
		if ($theDest)	{
			if ($this->getUniqueNamePrefix)	{		// Adds prefix
				$origFileInfo['file']     = $this->getUniqueNamePrefix.$origFileInfo['file'];
				$origFileInfo['filebody'] = $this->getUniqueNamePrefix.$origFileInfo['filebody'];
			}

				// Check if the file exists and if not - return the filename...
			$fileInfo = $origFileInfo;
			$theDestFile = $theDest.'/'.$fileInfo['file'];		// The destinations file
			if (!@file_exists($theDestFile) || $dontCheckForUnique)		{	// If the file does NOT exist we return this filename
				return $theDestFile;
			}

				// Well the filename in its pure form existed. Now we try to append numbers / unique-strings and see if we can find an available filename...
			$theTempFileBody = ereg_replace('_[0-9][0-9]$','',$origFileInfo['filebody']);		// This removes _xx if appended to the file
			$theOrigExt = $origFileInfo['realFileext'] ? '.'.$origFileInfo['realFileext'] : '';

			for ($a=1; $a<=($this->maxNumber+1); $a++)	{
				if ($a<=$this->maxNumber)	{	// First we try to append numbers
					$insert = '_'.sprintf('%02d', $a);
				} else {		// .. then we try unique-strings...
					$insert = '_'.substr(md5(uniqId('')),0,$this->uniquePrecision);
				}
				$theTestFile = $theTempFileBody.$insert.$theOrigExt;
				$theDestFile = $theDest.'/'.$theTestFile;		// The destinations file
				if (!@file_exists($theDestFile))		{	// If the file does NOT exist we return this filename
					return $theDestFile;
				}
			}
		}
	}

	/**
	 * Checks if $thePath is a path under one of the paths in $this->mounts
	 * See comment in the header of this class.
	 *
	 * @param	string		$thePath MUST HAVE a trailing '/' in order to match correctly with the mounts
	 * @return	string		The key to the first mount found, otherwise nothing is returned.
	 * @see init()
	 */
	function checkPathAgainstMounts($thePath)	{
		if ($thePath && $this->isPathValid($thePath) && is_array($this->mounts))	{
			reset ($this->mounts);
			while(list($k,$val)=each($this->mounts))	{
				if (t3lib_div::isFirstPartOfStr($thePath,$val['path']))	{
					return $k;
				}
			}
		}
	}

	/**
	 * Removes filemount part of a path, thus blinding the position.
	 * Takes a path, $thePath, and removes the part of the path which equals the filemount.
	 *
	 * @param	string		$thePath is a path which MUST be found within one of the internally set filemounts, $this->mounts
	 * @return	string		The processed input path
	 */
	function blindPath($thePath)	{
		$k=$this->checkPathAgainstMounts($thePath);
		if ($k)	{
			$name='';
			$name.='['.$this->mounts[$k]['name'].']: ';
			$name.=substr($thePath,strlen($this->mounts[$k]['path']));
			return $name;
		}
	}

	/**
	 * Find temporary folder
	 * Finds the first $this->tempFN ('_temp_' usually) -folder in the internal array of filemounts, $this->mounts
	 *
	 * @return	string		Returns the path if found, otherwise nothing if error.
	 */
	function findTempFolder()	{
		if ($this->tempFN && is_array($this->mounts))	{
			reset ($this->mounts);
			while(list($k,$val)=each($this->mounts))	{
				$tDir = $val['path'].$this->tempFN;
				if (@is_dir($tDir))	{
					return $tDir;
				}
			}
		}
	}






	/*********************
	 *
	 * Cleaning functions
	 *
	 *********************/

	/**
	 * Removes all dots, slashes and spaces after a path...
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function cleanDirectoryName($theDir)	{
		return ereg_replace('[\/\. ]*$','',$this->rmDoubleSlash($theDir));
	}

	/**
	 * Converts any double slashes (//) to a single slash (/)
	 *
	 * @param	string		Input value
	 * @return	string		Returns the converted string
	 */
	function rmDoubleSlash($string)	{
		return str_replace('//','/',$string);
	}

	/**
	 * Returns a string which has a slash '/' appended if it doesn't already have that slash
	 *
	 * @param	string		Input string
	 * @return	string		Output string with a slash in the end (if not already there)
	 */
	function slashPath($path)	{
		if (substr($path,-1)!='/')	{
			return $path.'/';
		}
		return $path;
	}

	/**
	 * Returns a string where any character not matching [.a-zA-Z0-9_-] is substituted by '_'
	 *
	 * @param	string		Input string, typically the body of a filename
	 * @return	string		Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_'
	 */
	function cleanFileName($fileName)	{
		$theNewName = ereg_replace('[^.[:alnum:]_-]','_',trim($fileName));
		return $theNewName;
	}

	/**
	 * Formats an integer, $sizeInBytes, to Mb or Kb or just bytes
	 *
	 * @param	integer		Bytes to be formated
	 * @return	string		Formatted with M,K or &nbsp;&nbsp; appended.
	 */
	function formatSize($sizeInBytes)	{
		if ($sizeInBytes>900)	{
			if ($sizeInBytes>900000)	{	// MB
				$val = $sizeInBytes/(1024*1024);
				return number_format($val, (($val<20)?1:0), '.', '').' M';
			} else {	// KB
				$val = $sizeInBytes/(1024);
				return number_format($val, (($val<20)?1:0), '.', '').' K';
			}
		} else {	// Bytes
			return $sizeInBytes.'&nbsp;&nbsp;';
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_basicfilefunc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_basicfilefunc.php']);
}
?>