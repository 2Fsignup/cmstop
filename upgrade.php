<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
$uf = AK_ROOT.'configs/upgrade.flag';
$ck = 'upgradepercent';

if(empty($get_sure)) {
	if(empty($get_vc)) { 
		if(empty($admin_id)) go($systemurl."index.php?file=login");
		displaytemplate('upgrade.htm', array('to' => $get_to));
	} else {
		vc();
		writetofile($onlineip, $uf);
		header('location:index.php?file=upgrade&sure=1&to='.$get_to);
		deletecache($ck);
		aexit();
	}
} elseif($get_sure == '1') {
	if(!file_exists($uf) || $onlineip != readfromfile($uf)) {
		aexit('upgrade from illegal IP');
	}
	if(empty($get_process)) {
		showprocess($lan['upgrade'], 'index.php?file=upgrade&to='.$get_to.'&sure=1&process=1');
	} else {
		$steps = array(50, 80);
		$step = getcache($ck);
		if(empty($step)) {

		} elseif($step == '1') {
			unzip(AK_ROOT.'cache/_akcms.zip');
			akunlink(AK_ROOT.'cache/_akcms.zip');
			setcache($ck, 2);
			aexit($steps[1]);
		} elseif($step == '2') {
			akunlink(AK_ROOT.'cache/update');
			akunlink(AK_ROOT.'cache/_akcms.php');
			$caches = readpathtoarray(AK_ROOT.'cache/templates');
			foreach($caches as $cache) {
				if(substr($cache, -8) != '.htm.php') continue;
				akunlink($cache);
			}
			$caches = readpathtoarray(AK_ROOT.'cache/foretemplates');
			foreach($caches as $cache) {
				if(substr($cache, -8) != '.htm.php') continue;
				akunlink($cache);
			}
			akunlink(AK_ROOT.'configs/upgrade.flag');
			deletecache($ck);
			aexit('100');
		}
	}
}
runinfo();
aexit();