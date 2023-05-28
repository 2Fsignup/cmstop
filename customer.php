<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
if(empty($get_action)) {
	displaytemplate('customer.htm');
} elseif($get_action == 'variable') {
	$variables = explode(',', $get_variable);
	$input = '';
	foreach($variables as $v) {
		$variable = $db->get_by('*', 'variables', "variable='$v'");
		if(empty($variable)) continue;
		if(strpos($variable['description'], '|') === false) {
			$title = $variable['description'];
			$description = '';
		} else {
			list($title, $description) = explode('|', $variable['description']);
		}
		$input .= "<tr><td valign=\"top\"><b>{$title}</b><br>{$description}</td><td valign=\"top\" width=\"300\">".renderinput($v, $variable['type'], $variable['standby'], $variable['value'])."</td></tr>\n";
	}
	displaytemplate('admincp_variable.htm', array('html' => $input));
} elseif($get_action == 'savevariable') {
	foreach($_POST as $k => $v) {
		if(is_array($v)) $v = implode(',', $v);
		$db->update('variables', array('value' => $v), "variable='$k'");
	}
	updatecache('globalvariables');
	adminmsg($lan['operatesuccess'], 'back');
}
runinfo();
aexit();
?>