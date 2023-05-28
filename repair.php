<?php
if(!defined('CORE_ROOT')) exit();
include(CORE_ROOT.'install/install.sql.php');
$dataversion = $db->get_by('value', 'settings', "variable='dataversion'");
if($dataversion === false) {
	$db->insert('settings', array('variable' => 'dataversion', 'value' => $sysedition));
}
if($dataversion < '4.1.2') {
	$db->addfield('categories', 'template2', 'varchar(30)');
	$db->addfield('categories', 'template3', 'varchar(30)');
	$db->addfield('categories', 'template4', 'varchar(30)');
	
	$db->addfield('categories', 'storemethod2', 'varchar(50)');
	$db->addfield('categories', 'storemethod3', 'varchar(50)');
	$db->addfield('categories', 'storemethod4', 'varchar(50)');
	
	$db->addfield('categories', 'pagetemplate', 'varchar(30)');
	$db->addfield('categories', 'pagestoremethod', 'varchar(50)');
	
	$db->addfield('items', 'initial', 'varchar(1)');
	$db->addfield('items', 'pv1', 'int(9)');
	$db->addfield('items', 'pv2', 'int(9)');
	$db->addfield('items', 'pv3', 'int(9)');
	$db->addfield('items', 'pv4', 'int(9)');
	$db->addfield('items', 'pagenum', 'int(9)');
	$db->addfield('items', 'tags', 'varchar(255)');
	$db->addfield('items', 'orderby5', 'int(9)');
	$db->addfield('items', 'orderby6', 'int(9)');
	$db->addfield('items', 'orderby7', 'int(9)');
	$db->addfield('items', 'orderby8', 'int(9)');
	
	$db->addfield('texts', 'subtitle', 'varchar(255)');
	
	$db->insert('settings', array('variable' => 'template2'));
	$db->insert('settings', array('variable' => 'template3'));
	$db->insert('settings', array('variable' => 'template4'));
	$db->insert('settings', array('variable' => 'storemethod2'));
	$db->insert('settings', array('variable' => 'storemethod3'));
	$db->insert('settings', array('variable' => 'storemethod4'));
	$db->insert('settings', array('variable' => 'pagetemplate'));
	$db->insert('settings', array('variable' => 'pagestoremethod'));

	$db->createtable('keys', $createtablesql['keys']);
}
if($dataversion < '4.1.3') {
	$db->addfield('categories', 'domain', 'varchar(255)');
}
if($dataversion < '4.1.7') {
	$db->addfield('categories', 'picture', 'varchar(255)');
}
if($dataversion < '4.2.2') {
	$db->addfield('items', 'aimurl', 'varchar(255)');
}
if($dataversion < '4.2.5') {
	$db->addfield('items', 'draft', 'tinyint(4) default 0');
}
if($dataversion < '5.0.1') {
	$db->createtable('apps', $createtablesql['apps']);
	$db->addfield('attachments', 'ispicture', 'tinyint(4) default 0');
	$db->addfield('items', 'string1', 'VARCHAR(255)');
	$db->addfield('items', 'string2', 'VARCHAR(255)');
	$db->addfield('items', 'string3', 'VARCHAR(255)');
	$db->addfield('items', 'string4', 'VARCHAR(255)');
	$plugins = readpathtoarray(AK_ROOT.'plugins');
	$templatepath = AK_ROOT."configs/apps/old_templateplugin/templateplugin/";
	foreach($plugins as $plugin) {
		if(is_dir($plugin)) continue;
		if(!is_readable($plugin)) continue;
		$filename = calfilenamefromurl($plugin);
		if(strrpos($filename, '.template.php') === false) continue;
		$filename = substr($filename, 0, -13).'.php';
		ak_copy($plugin, $templatepath.$filename);
	}
}
if($dataversion < '5.0.4') {
	$db->addfield('items', 'avgscore', 'VARCHAR(9)');
	$db->addfield('items', 'price', 'VARCHAR(9)');
}
if($dataversion < '5.1') {
	if(file_exists(AK_ROOT.'configs/language/menu.php')) {
		rename(AK_ROOT.'configs/language/menu.php', AK_ROOT.'configs/language/custom.php');
	}
}
if($dataversion < '5.1.1') {
	$db->addfield('apps', 'cdkey', 'VARCHAR(25)');
}
if($dataversion < '5.1.2') {
	if(!verifysiteid()) resetsiteid();
	if(getcache('installtemplate')) {
		deletecache('installtemplate');
		noinstalltemplate();
	}
}
if($dataversion < '5.1.4') {
	setsetting('customquickoperate', fromgbk("<a href=\"index.php?action=newitem&module=1\">Ìí¼ÓÄÚÈÝ</a>\n<a href=\"index.php?file=account&action=logout&vc=[vc]\" target=\"_parent\">ÍË³ö</a>"));
}
if($dataversion < '5.3.5') {
	setsetting('statcachesize', 0);
}
if($dataversion < '5.3.8') {
	$db->addfield('categories', 'replacehome', 'varchar(255)');
}
if($dataversion < '5.3.9') {
	$db->addfield('items', 'module', 'smallint(4)');
	$db->query("UPDATE {$tablepre}_items SET module=(SELECT module FROM {$tablepre}_categories WHERE id={$tablepre}_items.category) WHERE category>0");
}
if($dataversion < '6.0') {
	$db->addfield('attachments', 'fromdata', 'tinyint(4)');
	$db->addfield('items', 'outid', 'varchar(32)');
	$db->addfield('attachments', 'filename', 'varchar(255)');
	$db->addfield('attachments', 'orderby', 'mediumint(8)');
	$db->addfield('attachments', 'title', 'varchar(255)');
	$db->addfield('attachments', 'picture', 'varchar(255)');
	$db->addfield('attachments', 'outid', 'varchar(32)');
	$db->addfield('texts', 'outid', 'varchar(32)');
	$db->addfield('comments', 'outid', 'varchar(32)');

	$db->query("ALTER TABLE `{$tablepre}_attachments` ADD INDEX ( `outid` )");
	$db->query("ALTER TABLE `{$tablepre}_items` ADD INDEX ( `outid` )");
	$db->query("ALTER TABLE `{$tablepre}_texts` ADD INDEX ( `outid` )");
	$db->query("ALTER TABLE `{$tablepre}_comments` ADD INDEX ( `outid` )");
}

if($dataversion < '6.1') {
	$db->addfield('admins', 'permission', 'longtext');
	$db->addfield('attachments', 'ext', 'longtext');
	$db->addfield('categories', 'value', 'longtext');
	$db->addfield('categories', 'data', 'longtext');
	$db->addfield('item_exts', 'value', 'longtext');
	$db->addfield('comments', 'message', 'longtext');
	$db->addfield('comments', 'review', 'longtext');
	$db->addfield('settings', 'value', 'longtext');
	$db->addfield('texts', 'text', 'longtext');
	$db->addfield('variables', 'standby', 'longtext');
	$db->addfield('variables', 'description', 'longtext');
	$db->addfield('variables', 'value', 'longtext');
	$db->addfield('modules', 'data', 'longtext');
	$db->addfield('filters', 'data', 'longtext');
	$db->addfield('filters', 'ext', 'longtext');
	$db->addfield('keys', 'value', 'longtext');
	$db->addfield('admins', 'permission', 'longtext');
	$db->addfield('admins', 'permission', 'longtext');
	
	$db->query("ALTER TABLE `{$tablepre}_items` ADD INDEX ( `title` )");
	$db->addfield('items', 'price', 'int(9)');
	$db->query("UPDATE {$tablepre}_items SET price=price*100");
}

if($dataversion < '6.1.1') {
	$db->addfield('items', 'publishtime', 'int(9)');
}

if($dataversion < $sysedition) {
	setsetting('dataversion', $sysedition);
	updatecache();
	createfore();
}
?>
