<?php
if(!defined('CORE_ROOT')) {
	header("location:index.php");
	exit();
}
define('INSTALLSTEPFILE', AK_ROOT.'cache/_installstep');
require CORE_ROOT.'include/install.func.php';
$nodb = 1;
require CORE_ROOT.'include/admin.inc.php';
if(substr_count($_SERVER['SCRIPT_NAME'], '/') < 2) {
}
if(ifinstalled()) {
	exit($lan['reinstallerror']);
}
$softname = "AKCMS{$sysedition}";
if(file_exists(AK_ROOT.'install/custom.config.php')) {
	include(AK_ROOT."install/custom.config.php");
	$softname = $themename;
}

if(!empty($get_language)) $language = $get_language;
if(!empty($post_language)) $language = $post_language;
if(!empty($post_charset)) $charset = $post_charset;

$array_files = array(
	'..',
	'cache',
	'cache/tasks',
	'cache/templates',
	'cache/foretemplates',
	'configs',
	'configs/templates',
	'configs/templates/ak',
	'logs',
	'data',
	'index'
);
$message = '';
foreach($array_files as $file) {
	$_file = AK_ROOT.$file;
	$result = true;
	if(file_exists($_file)) {
		if(!is_writable($_file)) $result = false;
	} else {
		$result = ak_mkdir($_file);
	}
	if($result === false) $message .= '"'.$file.'"'.$lan['isunwritable'].'<br>';
}
if(!empty($message)) aexit($message);
$dbenv = checkdbenv();
$drivers = array(
	'mysql' => 'MySQL'."({$lan['currenthost']}{$lan['nosupport']})",
	'sqlite' => 'SQLite'."({$lan['currenthost']}{$lan['nosupport']})",
	'sqlite3' => 'SQLite3'."({$lan['currenthost']}{$lan['nosupport']})",
	'pdo:mysql' => 'pdo:mysql'."({$lan['currenthost']}{$lan['nosupport']})",
	'pdo:sqlite' => 'pdo:sqlite'."({$lan['currenthost']}{$lan['nosupport']})"
);
foreach($dbenv as $key => $value) {
	$drivers[$key] = $value;
}
$str = '';
foreach($drivers as $key => $value) {
	if(strpos($value,'(') === false){
		$str .= "<option value='".$key."'>".$value.'</option>';
	} else {
		$str .= '<optgroup label="'.$value.'"></optgroup>';
	}
}
$variables = array();
$variables['servertime'] = date('Y-m-d H:i:s');
$variables['drivers'] = $str;
$variables['cppath'] = $systemurl;
$variables['cfpath'] = $homepage;
$languagecharset = array(
	'key' => 'gbk',
	'value' => 'GBK'
);
if(file_exists(AK_ROOT."configs/config.inc.php")) {
	include(AK_ROOT."configs/config.inc.php");
	$variables['charset'] = $charset;
}

$variables['languagecharset'] = $languagecharset;
$variables['sqlitedbname'] = 'data/'.random(6).'.db.php';
$variables['sysedition'] =  $sysedition;
if(empty($get_action)) {
	beforeinstallclear();
	$variables['hastheme'] = '';
	$variables['charsethtml'] = "<select id='charset'><option value='gbk'>GBK</option><option value='utf8'>UTF-8</option></select>";
	$variables['themename'] = $softname;
	if(file_exists(AK_ROOT.'install/custom.config.php')) {
		include(AK_ROOT."install/custom.config.php");
		$variables['hastheme'] = 1;
		$variables['charsethtml'] = "$charset<input type='hidden' id='charset' name='charset' value='{$charset}' />";
	}
	$checkinstallpath = $lan['checkinstallpath'];
	$checkinstallpath = str_replace('[1]', $systemurl, $checkinstallpath);
	$checkinstallpath = str_replace('[2]', $homepage, $checkinstallpath);
	$checkinstallpath = str_replace('[3]', $systemurl, $checkinstallpath);
	$variables['checkinstallpath'] = $checkinstallpath;
	displaytemplate('install.htm', $variables);
} elseif($get_action == 'install') {
	$installstep = getinstallstep();
	$db = db();
	if($installstep == 1) {
		$task = gettask("coretabletask");
		if(empty($task)) {
			installinitialdata();
			updatecache();
			createfore();
			setinstallstep(2);
			aexit("100\t1\t".$lan['dbinitcomplete']);
		}
		$tablename = $tablepre.'_'.$task['tablename'];
		if(strpos($post_dbtype, 'mysql') !== false) {
			$db->selectdb($post_dbname);
			$task['table']['charset'] = $charset;
			$createtablesql = mysql_createtable($tablename, $task['table']);
			$_sqls = explode(";\n", $createtablesql);
			foreach($_sqls as $_sql) {
				$db->query($_sql);
			}
		} elseif(strpos($post_dbtype, 'sqlite') !== false) {
			$createtablesql = sqlite_createtable($tablename, $task['table']);
			$db->query($createtablesql);
		}
		$cpercent = gettaskpercent("coretabletask");
		aexit($cpercent."\t1\t".$lan['creating'].$tablename.$lan['table']);
	} elseif($installstep == 2) {
		if(!file_exists(AK_ROOT.'install/db/db.ak')) aexit("100\t2\t".$lan['allcomplete']);
		include(AK_ROOT.'install/custom.config.php');
		if($template_path != $themekey) {
			themeinit();
			aexit("0\t2\t".$lan['initthemecomplete']);
		} else {
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
				if($value['table'] == 'categories' || $value['table'] == 'modules' || $value['table'] == 'sections') {
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
					aexit($percent."\t2\t".$lan['importthemedatacompleted'].nb($dboffset / 1024).'KB');
				}
			}
			fclose($fp);
			setsetting('theme', $template_path);
			deletecache('_themedbbatch');
			deletecache('_themedboffset');
			updatecache();
			if(file_exists(AK_ROOT.'install/custom.config.php')) {
				finishtheme();
				setcache('installtemplate', 1);
			}
			aexit("100\t2\t".$lan['importthemedatacomplete']);
		}
	}
} elseif($get_action == 'checkdbconnect') {
	if(!preg_match("/^[0-9a-zA-Z\.\-_\/]+$/i", $post_dbname)) aexit('dbname');
	if(!preg_match('/^[0-9a-zA-Z_]+$/i', $post_tablepre)) aexit('no');
	if($post_dbtype == 'mysql') {
		if(!$connect = mysql_connect($post_dbhost, $post_dbuser, $post_dbpw)) {
			$error = mysql_error();
			aexit($error);
		}
	}
	if($post_dbtype == 'pdo:mysql') {
		$dsn = "mysql:host={$post_dbhost}";
		try {
    			$connect = new PDO($dsn, $post_dbuser, $post_dbpw);
		} catch (PDOException $e) {
			aexit('no');
		}
	}
	if(strpos($post_dbtype, 'sqlite') !== false) {
		if(fileext($post_dbname) !== 'php') aexit('no');
		if(!is_writable(AK_ROOT.'data')) aexit('no');
	}
	createconfig($_POST);
	aexit('ok');
} elseif($get_action == 'prepareinstall') {
	$db = db();
	include CORE_ROOT.'/install/install.sql.php';
	foreach($createtablesql as $tablename => $table) {
		addtask("coretabletask", array('tablename' => $tablename, 'table' => $table));
	}
	if(strpos($post_dbtype, 'mysql') !== false && empty($db->dbexist)) {
		$createdatabasesql = 'CREATE DATABASE `'.$post_dbname.'`';
		if($db->version > '4.1') {
			if($charset == 'utf8') {
				$mysql_charset = ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
			} elseif($charset == 'gbk') {
				$mysql_charset = ' DEFAULT CHARACTER SET gbk COLLATE gbk_chinese_ci';
			} elseif($charset == 'english') {
				$mysql_charset = ' DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci';
			}
			$createdatabasesql = $createdatabasesql.$mysql_charset;
		}
		$db->query($createdatabasesql);
	}
	aexit('ok');
} elseif($get_action == 'finishlock') {
	$db = db();
	setinstalled();
	resetsiteid();
	aexit('ok');
} elseif($get_action == 'movedir') {
	$olddir = substr(AK_ROOT, 0, -1);
	$newdir = $olddir.'/akcms';
	$r = copydir($olddir, $newdir, 1);
	header('location:'.$systemurl.'akcms/');
}

function getinstallstep() {
	if(!file_exists(INSTALLSTEPFILE)) return 1;
	return readfromfile(INSTALLSTEPFILE);
}

function setinstallstep($step) {
	writetofile($step, INSTALLSTEPFILE);
}

function clearstep() {
	akunlink(INSTALLSTEPFILE);
}

function beforeinstallclear() {
	setcookie ('installcore', "", time() - 3600);
	setcookie ('installstep', "", time() - 3600);
	deletetask('coretabletask');
	clearstep();
}

function themeinit() {
	global $db, $tablepre, $lan;
	include(AK_ROOT.'install/custom.config.php');
	$template_path = $themekey;
	createconfig(array('template_path' => $themekey));
	ak_copy(AK_ROOT.'install/trunk', FORE_ROOT);
	ak_copy(AK_ROOT.'install/templates', AK_ROOT.'configs/templates/'.$themekey);
	ak_copy(AK_ROOT.'install/systemplates', AK_ROOT.'configs/templates');
	ak_copy(AK_ROOT.'install/configs', AK_ROOT.'configs');
	setcache('_themedboffset', 0);
	setcache('_themedbbatch', 0);
	$dbsize = filesize(AK_ROOT.'install/db/db.ak');
	setcache('_themedbsize', $dbsize);
	deletecache('categoryselect');
}

runinfo();
aexit();
?>