<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';



$db->get_by('*', 'settings');


!isset($get_action) && $get_action = '';
if($get_action == 'phpinfo') {
	if(function_exists('phpinfo')) {
		phpinfo();exit;
	} else {
		exit('phpinfo() is disabled');
	}
} elseif($get_action == 'checkwritable') {
	$array_files = array(
		FORE_ROOT,
		'cache',
		'templates',
		'cache/templates',
		'cache/foretemplates',
		'configs',
	);
	$message = '';
	foreach($array_files as $file) {
		if(!is_writable($file)) $message .= '"'.$file.'"'.$lan['isunwritable'].'<br>';
	}
	if(!empty($message)) {
		adminmsg($lan['writableerror'].'<br>'.$message, 'index.php?file=welcome', 3, 1);
	} else {
		adminmsg($lan['writableok'], 'index.php?file=welcome');
	}
} elseif($get_action == 'updatecache') {
	updatecache();
	deletecache('categoryselect');
	
	ak_rmdir(AK_ROOT.'cache/templates');
	ak_rmdir(AK_ROOT.'cache/foretemplates');
	
	adminmsg($lan['operatesuccess'], 'index.php?file=welcome');
} elseif($get_action == 'copyfront') {
	createfore();
	adminmsg($lan['operatesuccess'], 'index.php?file=welcome');
} elseif($get_action == 'checknew') {
	include(CORE_ROOT.'repair.php');
} elseif($get_action == 'checkmessage') {
	$message = array();
	$result = $db->get_by('password', 'admins' , "editor='admin'");
	if($result == 'd7afde3e7059cd0a0fe09eec4b0008cd') $message[] = $lan['simplepw'];
	if(!empty($ifdebug)) $message[] = $lan['opendebugmode'];
	foreach($message as $k => $v) {
		$message[$k] = toutf8($v);
	}
	aexit(json_encode($message));
} else {
	if(!empty($_GET['updated'])) writetofile("<?php//{$sysedition}?>", AK_ROOT.'configs/version.php');
	$theme = '';
	if(isset($setting_theme)) $theme = $setting_theme;
	$infos = getcache('infos');
	$servertime = date('Y-m-d H:i:s', time());
	$correcttime = date('Y-m-d H:i:s', $thetime);
	isset($_ENV['TERM']) && $os = $_ENV['TERM'];
	$max_upload = ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'Disabled';
	$maxexetime = ini_get('max_execution_time');
	
	$modules = getcache('modules');
	$moduleoperate = '';
	foreach($modules as $module) {
		if($module['id'] <= 0) continue;
		$moduleoperate .= "<a href='index.php?file=admincp&action=items&module={$module['id']}'>{$lan['manage']}{$module['modulename']}</a>&nbsp;<a href='index.php?file=admincp&action=newitem&module={$module['id']}'>{$lan['add']}{$module['modulename']}</a>";
	}
	
	$query = $db->query_by('*', 'apps');
	$apphtml = '';
	while($app = $db->fetch_array($query)) {
		$apphtml .= "<a href='index.php?app={$app['key']}' target='_self'>{$app['app']}</a>&nbsp;";
	}
	$siteid = isset($settings['siteid']) ? $settings['siteid'] : '';
	$variables = array(
		'items' => $infos['items'],
		'pvs' => $infos['pvs'],
		'editors' => $infos['editors'],
		'attachmentsizes' => empty($infos['attachmentsizes']) ? 0 : nb($infos['attachmentsizes'] / 1048576),
		'attachments' => $infos['attachments'],
		'admin_id' => $admin_id,
		'os' => $os,
		'phpversion' => PHP_VERSION,
		'dbversion' => $db->version(),
		'akversion' => $sysedition,
		'theme' => $theme,
		'iscreator' => iscreator(),
		'maxupload' => $max_upload,
		'maxexetime' => $maxexetime,
		'servertime' => $servertime,
		'correcttime' => $correcttime,
		'dbtype' => $dbtype,
		'app' => $apphtml,
		'siteid' => $siteid,
		'moduleoperate' => $moduleoperate,
		'customquickoperate' => str_replace('[vc]', $vc, $settings['customquickoperate'])
	);
	displaytemplate('admincp_welcome.htm', $variables);
}
runinfo();
aexit();
?>
