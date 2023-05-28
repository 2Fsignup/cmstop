<?php
if(!defined('CORE_ROOT')) exit();
require_once CORE_ROOT.'include/admin.inc.php';

if(isset($post_loginsubmit)) {
	
	$loginlog = CORE_ROOT.'cache/loginlog';
	if($fp = rfopen($loginlog)) {
		$count = 0;
		while(!rfeof($fp)) {
			$line = rfgets($fp);
			@list($ip, $time, $status) = explode("\t", $line);
			if(!isset($status)) continue;
			if($time < $thetime - 600) break;
			if($ip == $onlineip) {
				if($status == 'success') break;
				$count ++;
			}
		}
		if($count > 3) {
			adminmsg($lan['login_failed_brute'], 'index.php?file=login', 60, 1);
		}
	}
	
	if($editor = $db->get_by('*', 'admins', "editor='".$db->addslashes($post_username)."'")) {
		if(ak_md5($post_password, 0, 2) == $editor['password']) {
			if($editor['freeze'] == 1) adminmsg($lan['youarefreeze'], 'index.php', 3, 1);
			if(!empty($post_rememberlogin)) {
				setlogin($post_username, $thetime + 24 * 3600 * 365 * 10);
			} else {
				setlogin($post_username);
			}
			$target = 'index.php';
			if(ifthemeuninstalled()) $target = 'index.php?file=theme&action=themeinstall';
			error_log("$onlineip\t$thetime\tsuccess\n", 3, $loginlog);
			adminmsg($lan['login_success'], $target);
		} else {
			error_log("$onlineip\t$thetime\tfail\n", 3, $loginlog);
			adminmsg($lan['login_failed'], 'index.php?file=login', 3, 1);
		}
	} else {
		error_log("$onlineip\t$thetime\tfail\n", 3, $loginlog);
		adminmsg($lan['login_failed'], 'index.php?file=login', 3, 1);
	}
} else {
	displaytemplate('login.htm');
}
?>