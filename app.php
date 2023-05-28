<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
require CORE_ROOT.'include/app.func.php';
checkcreator();
if($get_action == 'installed') {
	updatecache('apps');
	$html = '';
	$alreadyinstalled = $db->query_by('*', 'apps');
	while($v = $db->fetch_array($alreadyinstalled)) {
		$installtime = date('y-m-d', $v['updatetime']);
		$html .= "<div class='app'>
			<div class='appicon'><a href='index.php?app={$v['key']}'><img src='{$v['picture']}' /></a></div>
			<ul class='appdetail'>
				<li class='appname'>{$v['app']}</li>
				<li class='productor'>{$lan['version']}:{$v['ver']}</li>
			</ul>
			<div class='appbotton'>
				<div class='installbotton'><a class='uninstall' href='#'>{$lan['appuninstall']}</a></div><div class='detailbotton'></div>
			</div>
			<div class='ajaxkey' style='display:none;'>{$v['key']}</div>
		</div>";
	}
	if(empty($html)) $html="{$lan['noappinstalled']}";
	displaytemplate('admincp_installedapp.htm', array('html' => $html, 'apppagename' => $lan['alreadyinstalled']));
} elseif($get_action == 'uninstall') {
	$uninstallpath = CORE_ROOT.'configs/apps/'.$get_key;
	ak_rmdir($uninstallpath);
	updatecache('apps');
} elseif($get_action == 'refresh') {
	updatecache('apps');
	header('location:index.php?file=app&action=installed');
	aexit();
} elseif($get_action == 'startinstall') {
	$app = getcache('appinstalling');
	if($app != $get_key) aexit('error');
	if(!isset($get_cdkey)) $get_cdkey = '';
	$result = downloadapp($get_key, $get_cdkey);
	if($result === false) adminmsg($lan['installapperror'], '', 0, 1);
	
	scanapps();
	
	if(!empty($get_cdkey)) $db->update('apps', array('cdkey' => $get_cdkey), "`key`='$get_key'");
	
	updatecache('templateplugins');
	
	if(akgetcookie('install_app_autorun') == $get_key) {
		adminmsg($lan['operatesuccess'], 'index.php?app='.$get_key);
	} else {
		adminmsg($lan['installappsuccessclose']);
	}
}
runinfo();
aexit();