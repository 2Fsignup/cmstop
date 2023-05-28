<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
require CORE_ROOT.'include/install.func.php';
checkcreator();
$action = '';
if(isset($_GET['action'])) $action = $_GET['action'];
if($action == 'install') {
	vc();
	setcache('themeinstalling', $get_theme);
	$publicip = publicip();
} elseif($action == 'reinstall') {
	$theme = getcache('themeinstalling');
	if($theme != $get_theme) aexit('error');
	if(!downloadtheme($get_theme, $get_cdkey)) aexit('error2');
	emptyalltables();
	installinitialdata();
	clearfiles();
	aklocation('index.php?file=theme');
} elseif($action == 'frame') {
	showprocess($lan['importing'], 'index.php?file=theme&action=importdb&r='.random(6), 'index.php?file=theme&action=themeinstall&r='.random(6), 100, $lan['finished']);
} elseif(empty($action)) {
	if(!file_exists(AK_ROOT.'install/custom.config.php')) {
		aklocation('index.php?new=1');
	}
	require_once(AK_ROOT.'install/custom.config.php');
	$template_path = $themekey;
	createconfig(array('template_path' => $themekey));
	ak_copy(AK_ROOT.'install/trunk', FORE_ROOT);
	ak_copy(AK_ROOT.'install/templates', AK_ROOT.'configs/templates/'.$themekey);
	ak_copy(AK_ROOT.'install/systemplates', AK_ROOT.'configs/templates');
	ak_copy(AK_ROOT.'install/configs', AK_ROOT.'configs');
	if(file_exists(AK_ROOT.'install/apps')) ak_copy(AK_ROOT.'install/apps', AK_ROOT.'apps');
	$dbsize = filesize(AK_ROOT.'install/db/db.ak');
	setcache('_themedbsize', $dbsize);
	setcache('_themedboffset', 0);
	setcache('_themedbbatch', 0);
	noinstalltemplate();
	deletecache('categoryselect');
	aklocation('index.php?file=theme&action=frame&r='.random(6));
} elseif($action == 'importdb') {
	$dbsize = getcache('_themedbsize');
	$dboffset = getcache('_themedboffset');
	$dbbatch = getcache('_themedbbatch');
	$fp = fopen(AK_ROOT.'install/db/db.ak', 'r');
	fseek($fp, $dboffset);
	while(!feof($fp)) {
		$row = fgets($fp, 1024000);
		$dboffset = ftell($fp);
		setcache('_themedboffset', $dboffset);
		if($row == '') continue;
		$value = unserialize(base64_decode($row));
		if(empty($value)) continue;
		if($value['table'] == 'categories' || $value['table'] == 'modules') {
			$db->replaceinto($value['table'], $value['value'], 'id');
		} elseif($value['table'] == 'settings') {
			$db->replaceinto('settings', $value['value'], 'variable');
		} else {
			$db->insert($value['table'], $value['value']);
		}
		
		if($dboffset >= $dbbatch) {
			if($dboffset >= $dbsize) break;
			setcache('_themedbbatch', $dbbatch + 100000);
			$percent = nb($dboffset * 100/ $dbsize);
			fclose($fp);
			aexit($percent."\t0\t".nb($dboffset / 1024).'KB');
		}
	}
	fclose($fp);
	setsetting('theme', $template_path);
	deletecache('_themedbbatch');
	deletecache('_themedboffset');
	updatecache();
	aexit('100');
} elseif($action == 'themeinstall') {
	if(!file_exists(AK_ROOT.'install/install.php')) adminmsg($lan['importsuccess'], 'index.php?file=theme&action=finish&r='.random(6));
	require(AK_ROOT.'install/install.php');
} elseif($action == 'finish') {
	noinstalltemplate();
	finishtheme();
	themeinstalled();
	updatecache();
	aklocation("index.php");
}

function clearfiles() {
	akunlink(AK_ROOT.'configs/cphook.php');
	akunlink(AK_ROOT.'configs/forehook.php');
	akunlink(AK_ROOT.'configs/appmenu.php');
	akunlink(AK_ROOT.'configs/custom.menu.xml');
	ak_rmdir(AK_ROOT.'configs/apps/_dependhook/');
	ak_rmdir(AK_ROOT.'configs/hooks');
	ak_rmdir(AK_ROOT.'cache/');
	ak_rmdir(AK_ROOT.'configs/apps/');
	ak_rmdir(AK_ROOT.'configs/images');
	ak_rmdir(AK_ROOT.'configs/language');
	emptyfileindir(AK_ROOT.'configs/templates/ak/');
}
?>