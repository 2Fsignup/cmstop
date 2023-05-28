<?php
if(!defined('AK_ROOT')) {
	define('AK_ROOT', dirname(__FILE__).'/');
	if(file_exists(AK_ROOT.'configs/config.inc.php')) require_once AK_ROOT.'configs/config.inc.php';
	if(!isset($core_root)) $core_root = AK_ROOT;
	define('CORE_ROOT', $core_root);
}
require_once CORE_ROOT.'include/common.inc.php';
if(empty($file)) exit;
if(!in_array($file, array('attachment', 'captcha', 'category', 'comment', 'inc', 'include', 'item', 'keyword', 'page', 'post', 'rounter', 'score', 'section', 'user.baidu', 'user', 'user.txwb', 'do', 'app'))) exit;
require CORE_ROOT.'fore/'.$file.'.php';
?>