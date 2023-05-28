<?php
if(!defined('AK_ROOT')) {
	if(file_exists('configs/config.inc.php')) require_once 'configs/config.inc.php';
	if(!isset($core_root)) $core_root = './';
	define('CORE_ROOT', $core_root);
	define('AK_ROOT', substr(__FILE__, 0, -9));
}
require CORE_ROOT.'include/common.inc.php';
if(isset($get_app)) {
	$app = $get_app;
	include AK_ROOT.'include/admin.inc.php';
	include AK_ROOT.'include/app.inc.php';
	$file = AK_ROOT.'configs/apps/'.$app.'/program/index.php';
	if(file_exists($file)) require $file;
} else {
	$file = 'install';
	if(ifinstalled()) $file = 'admincp';
	if(!empty($_GET['file'])) $file = $_GET['file'];
	if(!in_array($file, array('account', 'admincp', 'db', 'customer', 'install', 'login', 'setting', 'upload', 'welcome', 'upgrade', 'repair', 'theme', 'app'))) exit;
	require CORE_ROOT.$file.'.php';
}