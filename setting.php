<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
checkcreator();
if(!isset($post_setting_submit)) {
	empty($get_action) && $get_action = '';
	$settings = array();
	$query = $db->query_by('*', 'settings');
	while($setting = $db->fetch_array($query)) {
		$settings[$setting['variable']] = $setting;
	}
	$str_settings = '';
	if($get_action == 'generally') {
		$str_settings .= table_start($lan['generallysetting']);
		$str_settings .= inputshow($settings, array('sitename', 'htmlexpand', 'statcachesize', 'menuwidth', 'defaultfilename', 'homepage', 'systemurl', 'storemethod', 'template2', 'storemethod2', 'template3', 'storemethod3', 'template4', 'storemethod4', 'pagetemplate', 'pagestoremethod', 'categoryhomemethod', 'sectionhomemethod', 'attachmethod', 'previewmethod', 'imagemethod', 'thumbmethod', 'defaultadmin'));
		$str_settings .= table_end();
	}
	if($get_action == 'functions') {
		$str_settings .= table_start($lan['functionssetting']);
		$str_settings .= inputshow($settings, array('ifhtml', 'usefilename', 'forbidstat', 'ifdraft'));
		$str_settings .= table_end();
	}
	if($get_action == 'front') {
		$str_settings .= table_start($lan['frontsetting']);
		$str_settings .= inputshow($settings, array('keywordslink', 'globalkeywordstemplate', 'statcode', 'ifcommentrehtml', 'uniqueurl', 'fore404'));
		$str_settings .= table_end();
	}
	if($get_action == 'attach') {
		$str_settings .= table_start($lan['attachsetting']);
		$str_settings .= inputshow($settings, array('keeporiginal', 'attachimagequality', 'attachwatermarkposition', 'maxattachsize'));
		$str_settings .= table_end();
	}
	if($get_action == 'frontswitch') {
		$str_settings .= table_start($lan['frontswitch']);
		$str_settings .= inputshow($settings, array('attachmentswitch', 'commentswitch', 'itemswitch', 'categoryswitch', 'sectionswitch', 'pageswitch', 'rounterswitch', 'incswitch', 'includeswitch', 'scoreswitch', 'appswitch'));
		$str_settings .= table_end();
	}
	if($get_action == 'custom') {
		$str_settings .= table_start($lan['custom']);
		$str_settings .= inputshow($settings, array('customquickoperate'));
		$str_settings .= table_end();
	}
	displaytemplate('admincp_setting.htm', array('action' => $get_action, 'str_settings' => $str_settings));
} else {
	$query = $db->query_by('variable,value', 'settings');
	$update = array();
	while($row = $db->fetch_array($query)) {
		$variable = $row['variable'];
		$setting = $row['value'];
		$post_variable = 'post_'.$variable;
		if(isset($$post_variable)) {
			$value = $$post_variable;
			if(is_array($value)) $value = implode(',', $value);
			if($setting != $value) $update[$variable] = array('value' => $value);
			
			if($variable == 'ifhtml') {
				if($value == '1') {
					if(strpos($settings['storemethod'], '?') !== false) $update['storemethod'] = array('value' => '[categorypath]/[f]');
					if(strpos($settings['storemethod2'], '?') !== false) $update['storemethod2'] = array('value' => '[categorypath]/2-[f]');
					if(strpos($settings['storemethod3'], '?') !== false) $update['storemethod3'] = array('value' => '[categorypath]/3-[f]');
					if(strpos($settings['storemethod4'], '?') !== false) $update['storemethod4'] = array('value' => '[categorypath]/4-[f]');
					if(strpos($settings['categoryhomemethod'], '?') !== false) $update['categoryhomemethod'] = array('value' => '[categorypath]/index.htm');
				} elseif($value == '0') {
					if(strpos($settings['storemethod'], '?') === false) $update['storemethod'] = array('value' => 'akcms_item.php?id=[id]');
					if(strpos($settings['categoryhomemethod'], '?') === false) $update['categoryhomemethod'] = array('value' => 'akcms_category.php?id=[id]');
				}
			}
		}
	}
	
	foreach($update as $k => $v) {
		$foretype = '';
		$foreswitch = array('attachmentswitch', 'categoryswitch', 'commentswitch', 'incswitch', 'includeswitch', 'itemswitch', 'pageswitch', 'postswitch', 'rounterswitch', 'scoreswitch', 'sectionswitch', 'appswitch');
		if(in_array($k, $foreswitch)) {
			$foretype = substr($k, 0, -6);
			if(empty($v['value'])) {
				removefore($foretype);
			} else {
				createfore($foretype);
			}
		}
		$db->update('settings', $v, "variable='$k'");
	}
	updatecache();
	adminmsg($lan['operatesuccess'], 'index.php?file=setting&action='.$post_action);
}
runinfo();
aexit();
?>
