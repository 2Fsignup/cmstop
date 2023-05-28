<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
require_once CORE_ROOT.'include/image.func.php';
if(isset($_SERVER['HTTP_CONTENT_DISPOSITION']) && preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i',$_SERVER['HTTP_CONTENT_DISPOSITION'], $info)){
	$filename = fromutf8(urldecode($info[2]));
	if(fileext($filename) == 'php') aexit();
	$newfilename = get_upload_filename($filename, 0, 0, 'image');
	$a = file_get_contents("php://input");
	if(!checkuploadfile($a)) {
		uploaddanger($lan['danger']);
	} else {
		writetofile($a, FORE_ROOT.$newfilename);
	}
} else {
	$uptype = 'image';
	if(isset($get_attach)) $uptype = 'attach';
	$filename = $file_filedata['name'];
	if(fileext($filename) == 'php') aexit();
	if(!empty($get_utf8)) $filename = fromutf8($filename);
	$newfilename = get_upload_filename($filename, 0, 0, $uptype);
	uploadfile($file_filedata['tmp_name'], FORE_ROOT.$newfilename);
	$piccontent = file_get_contents(FORE_ROOT.$newfilename);
	if(!checkuploadfile($piccontent)) {
		akunlink(FORE_ROOT.$filename);
		uploaddanger($lan['danger']);
	}
}
$modules = getcache('modules');
$ispicture = 0;
if(ispicture($filename)) {
	operateuploadpicture(FORE_ROOT.$newfilename, $modules[akgetcookie('lastmoduleid')]);
	$ispicture = 1;
}
$picurl = $homepage.$newfilename;
$insertarray = array('itemid' => $get_id, 'filename' => $newfilename, 'ispicture' => $ispicture, 'filesize' => filesize(FORE_ROOT.$newfilename), 'dateline' => $thetime, 'originalname' => $filename);
$db->insert('attachments', $insertarray);
$count = $db->get_by('COUNT(*)', 'attachments', "itemid='$get_id'");
$db->update('items', array('attach' => $count), "id='$get_id'");
$msg = "{'url':'".$picurl."','localname':'".$newfilename."','id':'1'}";
aexit("{'err':'','msg':".$msg."}");

function uploaderror($msg) {
	aexit("{'err':'','msg':".$msg."}");
}

function uploaddanger($msg) {
	uploaderror($msg);
}
?>
