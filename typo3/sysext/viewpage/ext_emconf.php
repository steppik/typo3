<?php

########################################################################
# Extension Manager/Repository config file for ext: "viewpage"
#
# Auto generated 11-03-2009 19:08
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>View',
	'description' => 'Shows the frontend webpage inside the backend frameset.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'view',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.1.1',
	'_md5_values_when_last_written' => 'a:10:{s:12:"ext_icon.gif";s:4:"eb74";s:14:"ext_tables.php";s:4:"a104";s:14:"view/clear.gif";s:4:"cc11";s:13:"view/conf.php";s:4:"33e4";s:15:"view/dummy.html";s:4:"e302";s:17:"view/frameset.php";s:4:"a100";s:14:"view/index.php";s:4:"1d3e";s:15:"view/layout.gif";s:4:"9730";s:22:"view/locallang_mod.xml";s:4:"ebf1";s:13:"view/view.gif";s:4:"e65c";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.3.0-4.3.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>