<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';

if(!isset($get_action) || $get_action == 'custom' || $get_action == 'admin') {
	if(!isset($get_action)) {
		$get_action = 'admin';
		if(ifcustomed() && empty($settings['defaultadmin'])) $get_action = 'custom';
	}
	if($get_action == 'custom') {
		$customlan = loadlan(AK_ROOT.'configs/language/custom.php');
		$lan = array_merge($lan, $customlan);
		$menudata = getmenus('custom');
		$usermode = 'editor';
	} else {
		$menudata = getmenus();
		$usermode = '';
	}
	$groups = $menudata['groups'];
	$favorite = "<b>{$groups['favorite']['title']}</b>";
	foreach($groups['favorite']['menus'] as $menu) {
		$favorite .= rendermenulink($menu);
	}
	unset($groups['favorite']);
	$logo = CORE_URL.'images/admin/logo.gif';
	if(file_exists(AK_ROOT.'configs/images/logo.gif')) $logo = 'configs/images/logo.gif';
	if(isset($menudata['homepage'])) $softhomepage = $menudata['homepage'];
	$customed = 0;
	if(ifcustomed()) $customed = 1;
	$menu = rendermenu($groups);
	$nav = rendernav($groups);
	$menuwidth = 80;
	if(!empty($setting_menuwidth)) $menuwidth = $setting_menuwidth;
	$w2 = $menuwidth + 5;
	$variable = array(
		'menu' => $menu,
		'menuwidth' => $menuwidth,
		'menuwidth2' => $w2,
		'nav' => $nav,
		'customed' => $customed,
		'usermode' => $usermode,
		'logo' => $logo,
		'favorite' => $favorite
	);
	displaytemplate('layout.htm', $variable);
	$_hookfile = actionhookfile('layout');
	if(file_exists($_hookfile)) include($_hookfile);
	aexit();
} elseif($get_action == 'noinstalltemplate') {
	noinstalltemplate();
	akheader('location:index.php');
} elseif($get_action == 'getinstalltemplatehtml') {
} elseif($get_action == 'categories') {
	checkcreator();
	displaytemplate('admincp_categories.htm', array('categoriestree' => rendercategorytree()));
} elseif($get_action == 'newcategory') {
	checkcreator();
	if(!empty($_POST)) {
		if(empty($post_category)) adminmsg($lan['nocategoryname'], 'back', 3, 1);
		$values = array(
			'categoryup' => $post_categoryup,
			'category' => $post_category,
			'module' => 1
		);		
		$db->insert('categories', $values);
		$categoryid = $db->insert_id();
		updatecache('category'.$categoryid);
		deletecache('categorytree');
		deletecache('categoryselect');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=categories');
	} else {
		if(empty($get_parent)) $get_parent = 0;
		displaytemplate('admincp_category_new.htm', array('parent' => $get_parent));
	}
} elseif($get_action == 'deletecategory') {
	checkcreator();
	vc();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], 'back', 3, 1);
	$item = $db->get_by('*', 'items', "category='$get_id'");
	if($item !== false) adminmsg($lan['delcategoryhasitem'], 'back', 3, 1);
	$category = $db->get_by('*', 'categories', "categoryup='$get_id'");
	if($category !== false) adminmsg($lan['delcategoryhassub'], 'back', 3, 1);
	$db->delete('categories', "id='$get_id'");
	deletecache('category'.$get_id);
	deletecache('categorytree');
	deletecache('categoryselect');
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=categories');
} elseif($get_action == 'editcategory') {
	checkcreator();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	$id = $get_id;
	if(empty($_POST)) {
		$category = $db->get_by('*', 'categories', "id='$id'");
		if($category == false) adminmsg($lan['parameterwrong'], '', 3, 1);
		$selectcategories = get_select('category');
		$selectmodules = get_select('modules');
		$selecttemplates = get_select_templates();
		$variables = $category;
		array_walk($variables, '_htmlspecialchars');
		$variables['selecttemplates'] = $selecttemplates;
		$variables['selectmodules'] = $selectmodules;
		$variables['selectcategories'] = $selectcategories;
		$variables['repicture'] = pictureurl($category['picture']);
		displaytemplate('admincp_category_edit.htm', $variables);
	} else {
		if(empty($post_category)) adminmsg($lan['nocategoryname'], 'back', 3, 1);
		if(!a_is_int($post_order)) $post_order = 0;
		if($get_id == $post_categoryup) adminmsg($lan['upperisself'], 'back', 3, 1);
		$category = $db->get_by('*', 'categories', "id='$id'");
		if($category['path'] != $post_path) {
			$pathchecked = checkcategorypath($post_path, $post_categoryup);
			if($pathchecked != '') adminmsg($pathchecked, 'back', 3, 1);
		}
		if(!empty($post_domain)) {
			$_c = $db->get_by('*', 'categories', "domain='$post_domain'");
			if($_c['id'] != $get_id) adminmsg('error', 'back', 3, 1);
		}
		
		if(!empty($file_uploadpicture['name'])) {
			$headpicext = fileext($file_uploadpicture['name']);
			if(!ispicture($file_uploadpicture['name'])) adminmsg($lan['pictureexterror'], 'back', 3, 1);
			$filename = get_upload_filename($file_uploadpicture['name'], 0, $post_category, 'preview');
			if(uploadfile($file_uploadpicture['tmp_name'], FORE_ROOT.$filename)) $picture = $filename;
			ak_ftpput(FORE_ROOT.$filename, $filename);
		} elseif(isset($post_data_pickpicture)) {
			$picture = pickpicture($post_data, $homepage);
		} else {
			$picture = isset($post_picture) ? $post_picture : false;
		}
		$value = array(
			'categoryup' => $post_categoryup,
			'category' => $post_category,
			'module' => $post_module,
			'alias' => $post_alias,
			'orderby' => $post_order,
			'description' => $post_description,
			'keywords' => $post_keywords,
			'path' => $post_path,
			'itemtemplate' => $post_itemtemplate,
			'defaulttemplate' => $post_defaulttemplate,
			'listtemplate' => $post_listtemplate,
			'html' => $post_html,
			'storemethod' => $post_storemethod,
			'categoryhomemethod' => $post_categoryhomemethod,
			'storemethod2' => $post_storemethod2,
			'storemethod3' => $post_storemethod3,
			'storemethod4' => $post_storemethod4,
			'pagetemplate' => $post_pagetemplate,
			'pagestoremethod' => $post_pagestoremethod,
			'template2' => $post_template2,
			'template3' => $post_template3,
			'template4' => $post_template4,
			'data' => $post_data,
			'picture' => $picture,
			'replacehome' => $post_replacehome
		);
		if(isset($post_domain)) $value['domain'] = $post_domain;
		$db->update('categories', $value, "id='$get_id'");
		updatecache('category'.$get_id);
		updatecategoryfilename($get_id);
		updatecache('modules');
		if($category['module'] != $post_module) updatecache('modules');
		deletecache('categorytree');
		deletecache('categoryselect');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=categories');
	}
} elseif($get_action == 'sections') {
	checkcreator();
	$query = $db->query_by('*', 'sections', '', 'id');
	$str_sections = '';
	while($section = $db->fetch_array($query)) {
		$str_sections .= "<tr><td>{$section['id']}</td><td><a href=\"index.php?file=admincp&action=editsection&id={$section['id']}\">{$section['section']}</a></td><td>{$section['description']}</td><td>{$section['items']}</td><td align=\"center\"><a href=\"index.php?file=admincp&action=createsection&id={$section['id']}\">{$lan['createdefault']}</a></td><td>{$section['orderby']}</td><td align='center'>".
		($section['id'] != 1 ? "<a href=\"javascript:deletesection({$section['id']})\">".alert($lan['delete'])."</a>" : $lan['delete'])."</td></tr>\r\n";
	}
	if($str_sections == '') $str_sections = '<tr><td colspan="10">'.$lan['section_no'].'</td></tr>';
	$variables = array(
		'str_sections' => $str_sections,
		'selecttemplates' => get_select_templates(),
		'setting_sectionhomemethod' => ak_htmlspecialchars($setting_sectionhomemethod)
	);
	displaytemplate('admincp_sections.htm', $variables);
} elseif($get_action == 'newsection') {
	checkcreator();
	if(empty($post_section)) adminmsg($lan['nosectionname'], 'back', 3, 1);
	if(!a_is_int($post_order)) $post_order = 0;
	$value = array(
		'section' => $post_section,
		'alias' => $post_alias,
		'orderby' => $post_order,
		'description' => $post_description,
		'keywords' => $post_keywords,
		'sectionhomemethod' => $post_sectionhomemethod,
		'html' => $post_html,
		'listtemplate' => $post_listtemplate,
		'defaulttemplate' => $post_defaulttemplate,
	);
	$db->insert('sections', $value);
	updatecache('sections');
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
} elseif($get_action == 'deletesection') {
	checkcreator();
	vc();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], 'index.php?file=admincp&action=sections', 3, 1);
	if(intval($get_id) == 1) adminmsg($lan['defaultsectionnodel'], 'index.php?file=admincp&action=sections', 3, 1);
	$db->delete('sections', "id='$get_id'");
	updatecache('sections');
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
} elseif($get_action == 'editsection') {
	checkcreator();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	if(!isset($post_section_edit_submit)) {
		$section = $db->get_by('*', 'sections', "id='$get_id'");
		if(empty($section)) adminmsg($lan['nosection'], '', 0, 1);
		$variables = $section;
		array_walk($variables, '_htmlspecialchars');
		$variables['selecttemplates'] = get_select_templates();
		$variables['setting_sectionhomemethod'] = ak_htmlspecialchars($setting_sectionhomemethod);
		displaytemplate('admincp_section_edit.htm', $variables);
	} else {
		if(empty($post_section)) adminmsg($lan['nosectionname'], 'back', 0, 1);
		if(!a_is_int($post_order)) $post_order = 0;
		$value = array(
			'section' => $post_section,
			'alias' => $post_alias,
			'orderby' => $post_order,
			'description' => $post_description,
			'keywords' => $post_keywords,
			'sectionhomemethod' => $post_sectionhomemethod,
			'html' => $post_html,
			'listtemplate' => $post_listtemplate,
			'defaulttemplate' => $post_defaulttemplate,
		);
		$db->update('sections', $value, "id='$get_id'");
		updatecache('sections');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
	}
} elseif($get_action == 'newitem') {
	$moduleid = 1;
	$item = array();
	if(isset($get_category)) {
		$category = $get_category;
		$item['category'] = $category;
		$categoryvalue = getcategorycache($category);
		if(!empty($categoryvalue['module'])) $moduleid = $categoryvalue['module'];
	} else {
		if(!empty($get_module)) $moduleid = $get_module;
		if(akgetcookie('lastcategory') != '') {
			$categoryvalue = getcategorycache(akgetcookie('lastcategory'));
			if($categoryvalue['module'] == $moduleid) $item['category'] = akgetcookie('lastcategory');
		}
	}
	aksetcookie('lastmoduleid', $moduleid);
	$_c = empty($item['category']) ? 0 : $item['category'];
	$categorylist = '';
	while($_c != 0) {
		$categorylist = $_c.','.$categorylist;
		$_categories[$_c] = getcategorycache($_c);
		$_c = $_categories[$_c]['categoryup'];
	}
	$categorylist = substr($categorylist, 0, -1);
	$item['id'] = $thetime;
	$htmlfields = renderitemfield($moduleid, $item);
	$drafttemplate = "";
	if(!empty($setting_ifdraft)) $drafttemplate="<input id='draft' name='drafts' type='submit' value='{$lan['savetodraft']}'>";
	if(empty($htmlfields)) adminmsg($lan['nocategorybinded'], 'index.php?file=admincp&action=categories', 3, 1);
	$variables = array();
	$variables['drafttemplate'] = $drafttemplate;
	$variables['moduleid'] = $moduleid;
	$variables['operate'] = $lan['add'];
	$variables['categorylist'] = $categorylist;
	$variables['action'] = 'index.php?file=admincp&action=newitem';
	$variables['htmlfields'] = $htmlfields;
	$variables['referer'] = 'index.php?file=admincp&action=items&module='.$moduleid;
	displaytemplate('admincp_moduleitem.htm', $variables);
} elseif($get_action == 'edititem') {
	$referer = '';
	if(isset($_SERVER['HTTP_REFERER'])) $referer = $_SERVER['HTTP_REFERER'];
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	$draft = "";
	if(!empty($setting_ifdraft)) $draft="<input id='draft' name='drafts' type='submit' value='{$lan['savetodraft']}'>";
	$item = $db->get_by('*', 'items', "id='$get_id'");
	$item['price'] = nb($item['price'] / 100);
	$_c = $item['category'];
	$categorylist = '';
	while($_c != 0) {
		$categorylist = $_c.','.$categorylist;
		$_categories[$_c] = getcategorycache($_c);
		$_c = $_categories[$_c]['categoryup'];
	}
	$categorylist = substr($categorylist, 0, -1);
	if($item === false) adminmsg($lan['parameterwrong'], '', 3, 1);
	if(empty($item['category'])) go("index.php?file=admincp&action=specialpages&id={$get_id}");
	$extdata = $db->get_by('value', 'item_exts', "id='$get_id'");
	$itemext = @unserialize($extdata);
	if(!empty($itemext)) $item = array_merge($item, $itemext);
	$text = $db->get_by('text', 'texts', "itemid='$get_id' AND page=0");
	$item['data'] = $text;
	$category = getcategorycache($item['category']);
	$moduleid = $category['module'];
	aksetcookie('lastmoduleid', $moduleid);
	$htmlfields = renderitemfield($moduleid, $item);
	displaytemplate('admincp_moduleitem.htm', array('id' => $get_id, 'drafttemplate' => $draft, 'categorylist' => $categorylist, 'moduleid' => $moduleid, 'operate' => $lan['edit'], 'htmlfields' => $htmlfields, 'referer' => urlencode($referer)));
} elseif($get_action == 'saveitem') {
	if(empty($post_title)) adminmsg($lan['notitle'], 'back', 3, 1);
	if(empty($post_category)) adminmsg($lan['nocategory'], 'back', 3, 1);
	aksetcookie('lastcategory', $post_category);
	$modules = getcache('modules');
	$cc = getcategorycache($post_category);
	if($setting_usefilename) {
		if(!empty($post_filename) && strpos($post_filename, '.') === false && substr($post_filename, strlen($setting_htmlexpand) * -1) != $setting_htmlexpand) $post_filename .= $setting_htmlexpand;
		if(!empty($post_filename)) {
			$filenamechecked = checkfilename($post_filename);
			if($filenamechecked != '') adminmsg($filenamechecked, 'back', 3, 1);
			$htmlfilename = itemhtmlname($post_id, 1, array('category' => $post_category, 'filename' => $post_filename, 'dateline' => $thetime), $cc);
			if($existfile = $db->get_by('*', 'filenames', "filename='$htmlfilename'")) {
				if($existfile['id'] != $post_id) {
					adminmsg($lan['fileexist'], 'back', 6, 1);
				}
			}
		}
	}
	$ext = 0;
	$extvalue = array();
	$module = $cc['module'];
	$config['itemurl'] = '';
	$config['category'] = $post_category;
	if(isset($post_data_copypic)) $post_data = copypicturetolocal($post_data, $config);
	$moduledata = $modules[$module]['data'];
	foreach($moduledata['fields'] as $_k => $_v) {
		if(substr($_k, 0, 1) != '_') continue;
		if(isset($_POST[$_k])) {
			$extvalue[$_k] = $_POST[$_k];
		}
	}
	if(!empty($extvalue)) {
		$ext = 1;
		$extvalue = serialize($extvalue);
	}
	$extinsertvalue = array('value' => $extvalue);

	if(!empty($file_uploadpicture['name'])) {
		$headpicext = fileext($file_uploadpicture['name']);
		if(!ispicture($file_uploadpicture['name'])) adminmsg($lan['pictureexterror'], 'back', 3, 1);
		$filename = get_upload_filename($file_uploadpicture['name'], 0, $post_category, 'preview');
		if(uploadfile($file_uploadpicture['tmp_name'], FORE_ROOT.$filename)) $picture = $filename;
		$piccontent = file_get_contents(FORE_ROOT.$filename);
		if(!checkuploadfile($piccontent)) {
			akunlink(FORE_ROOT.$filename);
			adminmsg($lan['danger'], 'back', 1, 1);
		}
		ak_ftpput(FORE_ROOT.$filename, $filename);
	} elseif(isset($post_data_pickpicture)) {
		$picture = pickpicture($post_data, $homepage);
	} else {
		$picture = isset($post_picture) ? $post_picture : false;
	}	
	if(!empty($post_dateline)) {
		$post_dateline = str_replace(' ', '-', $post_dateline);
		$post_dateline = str_replace(':', '-', $post_dateline);
		$_f = explode('-', $post_dateline);
		if(count($_f) == 6) {
			list($y, $m, $d, $h, $i, $s) = $_f;
			$dateline = mktime($h, $i, $s, $m, $d, $y);
		}
	}
	$filenames = array();
	$values = array(
		'title' => $post_title,
		'category' => $post_category,
		'editor' => $admin_id,
		'lastupdate' => $thetime,
		'ext' => $ext,
	);
	if(isset($post_shorttitle)) $values['shorttitle'] = $post_shorttitle;
	if(isset($post_section)) $values['section'] = $post_section;
	if(isset($post_source)) $values['source'] = $post_source;
	if(isset($post_orderby)) $values['orderby'] = $post_orderby;
	if(isset($post_orderby2)) $values['orderby2'] = $post_orderby2;
	if(isset($post_orderby3)) $values['orderby3'] = $post_orderby3;
	if(isset($post_orderby4)) $values['orderby4'] = $post_orderby4;
	if(isset($post_orderby5)) $values['orderby5'] = $post_orderby5;
	if(isset($post_orderby6)) $values['orderby6'] = $post_orderby6;
	if(isset($post_orderby7)) $values['orderby7'] = $post_orderby7;
	if(isset($post_orderby8)) $values['orderby8'] = $post_orderby8;
	if(isset($post_string1)) $values['string1'] = $post_string1;
	if(isset($post_string2)) $values['string2'] = $post_string2;
	if(isset($post_string3)) $values['string3'] = $post_string3;
	if(isset($post_string4)) $values['string4'] = $post_string4;
	if(isset($post_template)) $values['template'] = $post_template;
	if(isset($post_filename)) $values['filename'] = $post_filename;
	if(isset($post_keywords)) $values['keywords'] = $post_keywords;
	if(isset($post_digest)) $values['digest'] = $post_digest;
	if(isset($post_titlecolor)) $values['titlecolor'] = $post_titlecolor;
	if(isset($post_titlestyle)) $values['titlestyle'] = $post_titlestyle;
	if(isset($post_aimurl)) $values['aimurl'] = $post_aimurl;
	if(isset($post_price)) $values['price'] = ceil($post_price * 100);//小数变整形
	if(isset($post_tags)) $values['tags'] = $post_tags;
	if(!empty($post_draft)) {
		$values['draft'] = 1;
	} else {
		$values['publishtime'] = thetime();
	}
	if(isset($post_pageview)) $values['pageview'] = $post_pageview;
	if($picture !== false) $values['picture'] = $picture;
	if(isset($post_author)) $values['author'] = $post_author;
	if(isset($dateline)) $values['dateline'] = $dateline;
	$values['module'] = $module;
	$hookfunction = "hook_saveitem_$module";
	if(function_exists($hookfunction)) $values = $hookfunction($values, $post_data);
	$hookfunction = "hook_saveitemdata_$module";
	if(function_exists($hookfunction)) $post_data = $hookfunction($post_data, $values);
	if(empty($post_id)) {
		$values['latesthtml'] = 0;
		$newitempagenum = $db->get_by('COUNT(*)', 'texts', "itemid='$post_uploadid'");
		if(!isset($values['dateline'])) $values['dateline'] = $thetime;
		$db->insert('items', $values);
		$itemid = $db->insert_id();
		if($setting_usefilename) {
			$filename = itemhtmlname($itemid, 1, $values, $cc);
			if(!empty($filename)) {
				$values = array(
					'id' => $itemid,
					'filename' => $filename,
					'dateline' => $thetime,
					'type' => 'item'
				);
				$db->insert('filenames', $values);
			}
		}
		if(!empty($post_data)) {
			$data = array(
				'text' => $post_data,
			);
			$data['itemid'] = $itemid;
			$data['page'] = 0;
			$db->insert('texts', $data);
		}
		$db->update('items', array('pagenum' => $newitempagenum), "id='$itemid'");
		$db->update('attachments', array('itemid' => $itemid), "itemid='$post_uploadid'");
		$db->update('texts', array('itemid' => $itemid), "itemid='$post_uploadid'");
		refreshitemnum($post_category, 'category');
		if(isset($post_section)) refreshitemnum($post_section, 'section');
	} else {
		$item = $db->get_by('*', 'items', "id='$post_id'");
		if(!empty($item['publishtime'])) unset($values['publishtime']);
		if(!empty($item['editor'])) unset($values['editor']);
		$db->update('items', $values, "id='$post_id'");
		$itemid = $post_id;
		if(empty($post_data)) {
			$db->delete('texts', "itemid='$post_id' AND page='0'");
		} else {
			$data = array(
				'text' => $post_data,
			);
			if($db->get_by('*', 'texts', "itemid='$post_id' AND page='0'")) {
				$db->update('texts', $data, "itemid='$post_id' AND page='0'");
			} else {
				$data['itemid'] = $post_id;
				$data['page'] = 0;
				$db->insert('texts', $data);
			}
		}
		if(!empty($picture) && $item['picture'] != $picture) {
			if(preg_match('/^headpic\//', $item['picture'])) {
				akunlink(FORE_ROOT.$item['picture']);
			}
		}
		if($post_category != $item['category'] || (isset($post_filename) && $item['filename'] != $post_filename)) {
			$cc2 = getcategorycache($item['category']);
			akunlink(FORE_ROOT.itemhtmlname($post_id, 1, $item, $cc2));
		}
		if($setting_usefilename) {
			if(!empty($post_filename)) {
				$filename = itemhtmlname($post_id, 1, $values + $item, $cc);
				$values = array(
					'filename' => $filename,
					'dateline' => $thetime
				);
				$db->update('filenames', $values, "id='$post_id' AND type='item' AND page=0");
			}
		}
		if($item['category'] != $post_category) refreshitemnum(array($item['category'], $post_category), 'category');
		if(isset($post_section) && $item['section'] != $post_section) refreshitemnum(array($item['section'], $post_section), 'section');
	}
	if(!empty($ext)) {
		if($db->get_by('id', 'item_exts', "id='$itemid'")) {
			$db->update('item_exts', $extinsertvalue, "id='$itemid'");
		} else {
			$extinsertvalue['id'] = $itemid;
			$db->insert('item_exts', $extinsertvalue);
		}
	}
	if($attachnum = $db->get_by('COUNT(*)', 'attachments', "itemid='$itemid'")) {
		$db->update('items', array('attach' => $attachnum), "id='$itemid'");
	}
	batchhtml(array($itemid));
	$target = 'index.php?file=admincp&action=items';
	if(!empty($post_referer)) $target = urldecode($post_referer);
	$_hookfile = actionhookfile('saveitem');
	if(file_exists($_hookfile)) include($_hookfile);
	adminmsg($lan['operatesuccess'], $target);
} elseif($get_action == 'ajaxshow') {
	$itemid = httpget('id');
	$itemvalue = get_item_data($itemid);
	$field = httpget('field');
	$key = "{$field}_{$itemid}";
	$standby = $style = '';
	$value = $itemvalue[$field];
	$html = field_show($field, $itemid, $itemvalue);
	$html .= "";
	exit($html);
} elseif($get_action == 'ajaxedit') {
	$itemid = httpget('id');
	$itemvalue = get_item_data($itemid);
	$field = httpget('field');
	$key = "{$field}_{$itemid}";
	$standby = $style = $value = '';
	if(isset($itemvalue[$field])) $value = $itemvalue[$field];
	$html = field_input($field, $itemid, $itemvalue);
	$html .= " <input type='button' value='{$lan['save']}' onclick='saveajaxedit(\"$key\")' /> <input id='cancel' type='button' value='{$lan['cancel']}' onclick='resume(\"$key\")' />";
	exit($html);
} elseif($get_action == 'saveajaxedit') {
	if(!preg_match("/([_a-z0-9]+)_([0-9]+)/i", $_POST['key'], $match)) exit('error');
	$field = $match[1];
	$itemid = $match[2];
	if(preg_match("/^_/", $field)) {
		update_ext($itemid, $field, fromutf8($post_value));
	} else {
		$db->update('items', array($field => fromutf8($post_value)), "id='$itemid'");
	}
	exit('0');
} elseif($get_action == 'items') {
	if(isset($post_batchsubmit)) {
		if(isset($post_batch)) {
			if($post_batchtype == 'delete') {
				batchdeleteitem($post_batch);
				adminmsg($lan['operatesuccess'], 'back');
			} elseif($post_batchtype == 'createhtml') {
				batchhtml($post_batch);
				adminmsg($lan['operatesuccess'], 'back');
			} elseif($post_batchtype == 'setorder') {
				empty($post_neworder) && $post_neworder = 0;
				if(!a_is_int($post_neworder)) $post_neworder = 0;
				$ids = implode(',', $post_batch);
				$value = array(
					'orderby' => $post_neworder,
				);
				$db->update('items', $value, "id IN ($ids)");
				adminmsg($lan['operatesuccess'], 'back');
			} elseif($post_batchtype == 'setcategory') {
				empty($post_newcategory) && $post_newcategory = 1;
				if(!a_is_int($post_newcategory)) $post_newcategory = 1;
				$ids = implode(',', $post_batch);
				$value = array(
					'category' => $post_newcategory,
				);
				$db->update('items', $value, "id IN ($ids)");
				updateitemfilename($post_batch);
				adminmsg($lan['operatesuccess'], 'back');
			} elseif($post_batchtype == 'publish') {
				$ids = implode(',', $post_batch);
				$value = array(
					'draft' => 0,
					'publishtime' => thetime(),
				);
				$db->update('items', $value, "id IN ($ids)");
				adminmsg($lan['operatesuccess'], 'back');
			}
		} else {
			adminmsg($lan['noitembatch'], 'back', 3, 1);
		}
	}
	$sections = getcache('sections');
	$modules = getcache('modules');
	$selectsections = get_select('section');
	$sql_condition = 'category<>0 ';
	$url_condition = '';
	if(!empty($get_id)) {
		$ids = tidyitemlist($get_id);
		$sql_condition .= " AND id IN ({$ids})";
		$url_condition .= "&id={$get_id}";
	}
	if(!empty($get_key)) {
		$sql_condition .= " AND title LIKE '%{$get_key}%'";
		$url_condition .= "&key=".urlencode($get_key);
	}
	if(!empty($get_editor)) {
		$sql_condition .= " AND editor='{$get_editor}'";
		$url_condition .= "&editor={$get_editor}";
	}
	if(!empty($get_category)) {
		$sql_condition .= " AND category='$get_category'";
		$url_condition .= "&category={$get_category}";
		$categorycache = getcache('category-'.$get_category);
	}
	if(!empty($get_section)) {
		$sql_condition .= " AND section='{$get_section}'";
		$url_condition .= "&section={$get_section}";
	}
	if(isset($get_draft)) {
		$sql_condition .= " AND draft='{$get_draft}'";
		$url_condition .= "&draft={$get_draft}";
	}
	if(empty($get_module)) $get_module = 1;
	if(!empty($categorycache)) $get_module = $categorycache['module'];
	$module = $modules[$get_module];
	if($module == false) adminmsg($lan['parameterwrong'], '', 3, 1);
	if(empty($module['categories'])) adminmsg($lan['nocategorybinded'], 'index.php?file=admincp&action=categories', 3, 1);
	$categories = $module['categories'];
	$selectcategories = '';
	if(empty($nocategoryselect)) {
		$selectcategories = rendercategoryselect();
	}
	$data = $module['data'];
	$fields = array();
	$extflag = 0;
	$dataflag = 0;
	foreach($data['fields'] as $key => $value) {
		if(empty($value['listorder']) || $value['listorder'] <= 0) continue;
		$fields[$key] = $value['listorder'];
		if($extflag == 0 && !in_array($key, $itemfields)) $extflag = 1;
		if($dataflag == 0 && $key == 'data') $dataflag = 1;
	}
	arsort($fields);
	$fieldsheader = '';
	foreach($fields as $key => $field) {
		$alias = fieldname($key, $data['fields'][$key]);
		$fieldsheader .= "<td>{$alias}</td>";
	}
	$modulecategories = getcategoriesbymodule($get_module);
	if(!empty($modulecategories)) {
		if(empty($get_module) || $get_module == -1) {
			$sql_condition .= " AND category NOT IN (".implode(',', $modulecategories).")";
		} else {
			$sql_condition .= " AND category IN (".implode(',', $modulecategories).")";
		}
	}
	$url_condition .= "&module={$get_module}";
	empty($get_orderby) && $get_orderby = 'id';
	!in_array($get_orderby, array('id', 'orderby', 'pageview', 'dateline', 'commentnum', 'lastcomment', 'lastupdate')) && $get_orderby = 'id';
	$url_condition .= "&orderby={$get_orderby}";
	$ipp = empty($module['data']['numperpage']) ? 10 : $module['data']['numperpage'];
	if(isset($get_page)) {
		$page = $get_page;
		aksetcookie('itemspage', $page);
	} else {
		if(isset($cookie_itemspage)) {
			$page = $cookie_itemspage;
		} else {
			$page = 1;
		}
	}
	$page = max($page, 1);
	isset($post_page) && $page = $post_page;
	!a_is_int($page) && $page = 1;
	$start_id = ($page - 1) * $ipp;
	$url = 'index.php?file=admincp&action=items'.ak_htmlspecialchars($url_condition);
	$count = $db->get_by('COUNT(*)', 'items', $sql_condition);
	if($ipp * ($page - 1) > $count) {
		header('location:'.$currenturl.'&page='.ceil($count / $ipp));
		aexit();
	}
	$str_index = multi($count, $ipp, $page, $url);
	$query = $db->query_by('id', 'items', $sql_condition, " `$get_orderby` DESC", "$start_id,$ipp");
	$str_items = '';
	if(!empty($data['fields'])) {
		foreach($data['fields'] as $_k => $_v) {
			if(empty($_v['default'])) continue;
			if(strpos($_v['default'], ';')) {
				$_t = explode(';', $_v['default']);
				foreach($_t as $_t1) {
					if(strpos($_t1, ',') !== false) {
						$_t2 = explode(',', $_t1);
						$_d[$_k][$_t2[1]] = $_t2[0];
					}
				}
			}
		}
	}
	$items = array();
	while($item = $db->fetch_array($query)) {
		$items[$item['id']] = $item;
	}
	$itemids = array_keys($items);
	if(!empty($itemids)) {
		$itemids = implode(',', $itemids);
		$query = $db->query_by('*', 'items', "id IN ($itemids)");
		while($item = $db->fetch_array($query)) {
			$items[$item['id']] = $item;
		}
		if($extflag) {
			$query = $db->query_by('*', 'item_exts', "id IN ($itemids)");
			while($record = $db->fetch_array($query)) {
				if(!empty($record['value']) && $_value = @unserialize($record['value'])) {
					foreach($_value as $_k => $_v) {
						$items[$record['id']][$_k] = $_v;
					}
				}
			}
		}
		if($dataflag) {
			$query = $db->query_by('itemid,text', 'texts', "itemid IN ($itemids)");
			while($record = $db->fetch_array($query)) {
				$items[$record['itemid']]['data'] = $record['text'];
			}
		}
	}
	$html = $data['html'] + $setting_ifhtml;
	foreach($items as $item) {
		$item['price'] = nb($item['price'] / 100);
		if(!isset($categoriescache[$item['category']])) $categoriescache[$item['category']] = getcategorycache($item['category']);
		$attach = !empty($item['attach']) ? "<img src='".CORE_URL."/images/admin/attach.gif' title='{$lan['haveattach']}:{$item['attach']}'>&nbsp;" : '';
		$picture = pictureurl($item['picture'], $attachurl);
		$picture = $picture ? '<a href="'.$picture.'" target="_blank"><img src="'.CORE_URL.'/images/admin/picture.gif" alt="'.$lan['havepicture'].'" border="0"></a>&nbsp;' : '';
		$checkbox = "<input type=\"checkbox\" name=\"batch[]\" value=\"{$item['id']}\">";
		$category = isset($categoriescache[$item['category']]) ? $categoriescache[$item['category']]['category'] : '-';
		$section = isset($sections[$item['section']]) ? $sections[$item['section']]['section'] : '-';
		$title = htmltitle(ak_htmlspecialchars($item['title']), $item['titlecolor'], $item['titlestyle']);
		$draft = '';
		if($item['draft']) $draft = '['.green("{$lan['draft']}").']'."[<a id={$item['id']} href='#'>".green($lan['publish']).'</a>]';
		$str_moduleitems = '';
		foreach($fields as $key => $field) {
			if($field <= 0) continue;
			if($key == 'title') {
				$page = '';
				if($item['pagenum'] > 0) $page = " ({$item['pagenum']})";
				if($item['draft']) {
					$draft = '<img src="'.CORE_URL.'/images/admin/draft.gif" alt="'.$lan['havepicture'].'" border="0">';
					$str_moduleitems .= "<td class='item_title' id='title_{$item['id']}'>{$attach}{$picture}{$draft} <a href=\"index.php?file=admincp&action=edititem&id={$item['id']}\">{$title}</a>$page [<a id={$item['id']} href='#' class='publish'>".green($lan['publish']).'</a>]</td>';
				} else {
					$str_moduleitems .= "<td class='item_title' id='title_{$item['id']}'>{$attach}{$picture}<a href=\"index.php?file=admincp&action=edititem&id={$item['id']}\">{$title}</a>$page</td>";
				}
			} elseif($key == 'category') {
				$str_moduleitems .= "<td id='category_{$item['id']}'>{$category}</td>";
			} elseif($key == 'section') {
				$str_moduleitems .= "<td id='section_{$item['id']}'>{$section}</td>";
			} elseif($key == 'dateline' || $key == 'publishtime') {
				if($item[$key] == 0) {
					$str_moduleitems .= "<td id='{$item[$key]}_{$item['id']}'>-</td>";
				} else {
					$str_moduleitems .= "<td id='{$item[$key]}_{$item['id']}' class='mininum' title='".date('Y-m-d H:i:s', $item[$key])."'>".date('m-d H:i', $item[$key])."</td>";
				}
			} else {
				if(!empty($_d[$key])) {
					if(isset($item[$key]) && is_array($_d[$key])) {
						if(isset($_d[$key][$item[$key]])) {
							$td = $_d[$key][$item[$key]];
						} else {
							$td = $item[$key];
						}
					} else {
						$_key = $_d[$key];
						if(strpos($_key, '[id]') !== false) $_key = str_replace('[id]', $item['id'], $_key);
						if(isset($item[$key]) && strpos($_key, '[value]') !== false) {
							$_key = str_replace('[value]', $item[$key], $_key);
						}
						$td = $_key;
					}
				} elseif(isset($item[$key])) {
					$td = field_show($key, $item['id'], $item);
				} elseif($key == 'comment') {
					$td = $item['commentnum'];
				} else {
					$td = '-';
				}
				$str_moduleitems .= "<td id='{$key}_{$item['id']}'>$td</td>";
			}
		}
		$str_items .= "<tr id='item_{$item['id']}'><td class='item_id'>{$checkbox}&nbsp;{$item['id']}</td>{$str_moduleitems}</td><td align='center'><a href='index.php?file=admincp&action=deleteitem&id={$item['id']}&vc={$vc}' onclick='return confirmdelete()'>".alert($lan['delete'])."</a></td>";
		if($data['page'] > 0) {
			$str_items .= "<td align='center'><a href='{$homepage}akcms_item.php?id={$item['id']}' target='_blank'>{$lan['preview']}</a></td>";
			$realurl = itemurl($item['id'], 1, $item, $categoriescache[$item['category']]);
			$str_items .= "<td align='center'><a href='$realurl' target='_blank'>{$lan['realurl']}</a></td>";
		}
		if($data['page'] > 0 && $html > 0) {
			$str_items .= "<td align='center'><a href='index.php?file=admincp&action=createhtml&id={$item['id']}' target='work'>{$lan['createhtml']}</a></td>\n";
		}
		$str_items .= "</tr>";
	}
	if($str_items == '') $str_items = '<tr><td colspan="15">'.$lan['item_no'].'</td></tr>';
	$params = array();
	$params['selectsections'] = $selectsections;
	$params['moduleid'] = $get_module;
	$params['fieldsheader'] = $fieldsheader;
	$params['selectcategories'] = $selectcategories;
	$params['str_index'] = $str_index;
	$params['str_items'] = $str_items;
	$params['html'] = $html;
	$params['page'] = $data['page'];
	$params['indexurl'] = $url;
	$params['get'] = ak_htmlspecialchars($_GET);
	displaytemplate('admincp_items.htm', $params);
	if(file_exists(actionhookfile('items'))) include(actionhookfile('items'));
} elseif($get_action == 'publish') {
	vc();
	$item = $db->get_by('*', 'items', "id='$get_id'");
	$value = array(
		'draft' => 0
	);
	if($item['publishtime'] == 0) {
		$value['publishtime'] = thetime();
	}
	$db->update('items', $value, "id='$get_id'");
	aexit('true');
} elseif($get_action == 'emptydraft') {
	vc();
	$db->delete('items', "draft>=1");
	aexit('true');
} elseif($get_action == 'specialpages') {
	if(!isset($get_job) && !isset($get_id)) {
		$query = $db->query_by('*', 'items', 'category=0', 'id');
		$str_pages = '';
		while($page = $db->fetch_array($query)) {
			$createhtml_text = '<a href="index.php?file=admincp&action=createhtml&id='.$page['id'].'&category=0" target="work">'.$lan['createhtml'].'</a>';
			$delete_text = "<a href=\"index.php?file=admincp&action=deleteitem&id={$page['id']}&vc={$vc}\" onclick=\"return confirmdelete()\">".alert($lan['delete'])."</a>";
			$title = $page['title'];
			if($title == '') $title = 'Untitled Page';
			$str_pages .= "<tr><td>{$page['id']}</td><td><a href=\"index.php?file=admincp&action=specialpages&id={$page['id']}\">{$title}</a></td><td><a href=\"index.php?file=admincp&action=template&template=,{$page['template']}\">{$page['template']}</a></td><td>{$page['filename']}</td><td>{$page['pageview']}</td>";
			$str_pages .= "<td align='center'><a href='{$homepage}akcms_item.php?id={$page['id']}' target='_blank'>{$lan['preview']}</a></td>";
			if($page['filename'] != '') {
				$realurl = itemurl($page['id'], 1, $page);
				$str_pages .= "<td align='center'><a href='$realurl' target='_blank'>{$lan['realurl']}</a></td><td align='center'>{$createhtml_text}</td>";
			} else {
				$str_pages .= "<td></td><td></td>";
			}
			$str_pages .= "<td align='center'>{$delete_text}</td></tr>\n";
		}
		if($str_pages == '') $str_pages = '<tr><td colspan="10">'.$lan['specialpage_no'].'</td></tr>';
		$selecttemplates = get_select_templates();
		
		displaytemplate('admincp_specialpages.htm', array('str_pages' => $str_pages, 'str_templates' => $selecttemplates));
	} elseif(isset($get_job) && $get_job == 'newpage') {
		if(empty($post_pagename) || empty($post_template)) adminmsg($lan['allarerequired'], 'back', 3, 1);
		$value = array(
			'title' => $post_pagename,
			'template' => $post_template,
			'dateline' => $thetime,
			'lastupdate' => $thetime,
			'editor' => $admin_id
		);
		$db->insert('items', $value);
		$itemid = $db->insert_id();
		if(!empty($post_data)) $db->insert('texts', array('itemid' => $itemid, 'text' => $post_data));
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=specialpages');
	} elseif(!empty($get_id)) {
		if(!a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
		if(!isset($post_saveeditpage)) {
			$page = $db->get_by('*', 'items', "id='$get_id'");
			
			$page['pagename'] = $page['title'];
			$page['title'] = $page['aimurl'];
			
			if(empty($page)) adminmsg($lan['parameterwrong'], '', 3, 1);
			$page['data'] = $db->get_by('text', 'texts', "itemid='$get_id'");
			$selecttemplates = get_select_templates();
			$variables = $page;
			array_walk($variables, '_htmlspecialchars');
			$variables['str_templates'] = $selecttemplates;
			displaytemplate('admincp_specialpage.htm', $variables);
		} else {
			if(empty($post_pagename) || empty($post_template)) adminmsg($lan['allarerequired'], 'back', 3, 1);
			$page = $db->get_by('*', 'items', "id='$get_id'");
			if(empty($page)) adminmsg($lan['parameterwrong'], '', 3, 1);
			if($post_filename != '') {
				if(!preg_match('/^\//', $post_filename)) adminmsg($lan['pagepathroot'], 'back', 3, 1);
				if(!empty($post_filename) && strpos($post_filename, '.') === false) $post_filename .= $setting_htmlexpand;
				$filenamechecked = checkfilename($post_filename, 'noempty');
				if($filenamechecked != '') adminmsg($filenamechecked, 'back', 3, 1);
				$htmlfilename = substr($post_filename, 1);
				if($page = $db->get_by('id', 'filenames', "filename='$htmlfilename'")) {
					if($page != $get_id) adminmsg($lan['fileexist'], 'back', 6, 1);
				}
			}
			$value = array(
				'title' => $post_pagename,
				'aimurl' => $post_title,
				'filename' => $post_filename,
				'template' => $post_template,
				'keywords' => $post_keywords,
				'digest' => $post_digest
			);
			$db->update('items', $value, "id='$get_id'");
			if($db->get_by('*', 'texts', "itemid='$get_id'")) {
				if(empty($post_data)) {
					$db->delete('texts', "itemid='$get_id'");
				} else {
					$db->update('texts', array('text' => $post_data), "itemid='$get_id'");
				}
			} else {
				if(!empty($post_data)) {
					$db->insert('texts', array('text' => $post_data, 'itemid' => $get_id));
				}
			}
			if($post_filename != '') {
				if($db->get_by('*', 'filenames', "id='$get_id'")) {
					$value = array(
						'filename' => $htmlfilename
					);
					$db->update('filenames', $value, "id='$get_id' AND type='item'");
				} else {
					$value = array(
						'filename' => $htmlfilename,
						'id' => $get_id,
						'dateline' => $thetime,
						'type' => 'item',
						'page' => 0
					);
					$db->insert('filenames', $value);
				}
				batchhtml(array($get_id));
			}
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=specialpages');
		}
	}
} elseif($get_action == 'deleteitem') {
	vc();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	batchdeleteitem(array($get_id));
	if(!isset($get_returnlist)) {
		adminmsg($lan['operatesuccess'], 'back');
	} else {
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=items');
	}
} elseif($get_action == 'comments') {
	(empty($get_id) || !a_is_int($get_id)) && adminmsg($lan['parameterwrong'], '', 3, 1);
	$item = $db->get_by('*', 'items', "id='$get_id'");
	$query = $db->query_by('*', 'comments', "itemid='$get_id'", 'dateline');
	$str_comments = '';
	$i = 0;
	$commentsarr = array();
	while($comment = $db->fetch_array($query)) {
		if(array_key_exists('dateline',$comment)) $comment['dateline'] = date("Y-m-d h:i:s", $comment['dateline']);
		$comment = ak_htmlspecialchars($comment);
		$commentsarr[] = toutf8($comment);
		$i ++;
	}
	if($i != $item['commentnum']) {
		$value = array(
			'commentnum' => $i
		);
		$db->update('items', $value, "id='$get_id'");
	}
	aexit(json_encode($commentsarr));
} elseif($get_action == 'allcomments') {
	$where = '1';
	if(!empty($get_ip)) $where = "ip='$get_ip'";
	if(empty($get_page)) $get_page = 1;
	if(!empty($post_page)) $get_page = $post_page;
	$numperpage = 8;
	$count = $db->get_by('COUNT(id) as c', 'comments', $where);
	$str_index = multi($count, $numperpage, $get_page, 'index.php?file=admincp&action=allcomments');
	$query = $db->query_by('*', 'comments', $where, 'dateline DESC', (($get_page - 1) * $numperpage).",$numperpage");
	$str_comments = '';
	$i = 0;
	$items = array();
	while($comment = $db->fetch_array($query)) {
		$i ++;
		if(!in_array($comment['itemid'], $items)) $items[] = $comment['itemid'];
		$str_comments .= "<tr class='c".$comment['id']."' bgcolor='#FFFFFF'><td><div class='righttop'><a href='javascript:deletecomment({$comment['id']},{$comment['itemid']},\"{$vc}\");'>{$lan['delete']}</a>&nbsp;<a href='javascript:denyip(\"{$comment['ip']}\",{$comment['itemid']},\"{$vc}\");'>{$lan['denyip']}</a>&nbsp<a href='javascript:review({$comment['id']})'>{$lan['review']}</a>
		<a href='javascript:reviewcom({$comment['id']})'>{$lan['edit']}</a>
		</div>ID:{$comment['id']} | {$lan['title']}:".ak_htmlspecialchars($comment['title'])."&nbsp;|
		{$lan['name']}:".ak_htmlspecialchars($comment['username'])."&nbsp;|
		".date('Y-m-d H:i:s', $comment['dateline'])."&nbsp;|
		IP:<a href='index.php?file=admincp&action=allcomments&ip={$comment['ip']}'>{$comment['ip']}</a> @[{$comment['itemid']}]</td></tr>
		<tr class='c".$comment['id']."' bgcolor='#FFFFFF'><td style='border-bottom:1px solid #D6E0EF;'><span id='comspan".$comment['id']."'>".nl2br(ak_htmlspecialchars($comment['message'])).'&nbsp;</span>';
		$str_comments .= "<br />{$lan['review']}:<span id='revspan".$comment['id']."' class='red'>".ak_htmlspecialchars($comment['review']).'</span>';
		$str_comments .= "<div id='review{$comment['id']}' class='reviewdiv'><form action='index.php?file=admincp&action=reviewcomment' method='post'><input type='hidden' name='all' value='1'><input type='hidden' name='id' value='{$comment['id']}'><input type='hidden' name='itemid' value='{$comment['id']}'>
		<textarea id='textarea{$comment['id']}' name='review' cols='80' rows='2'>".ak_htmlspecialchars($comment['review'])."</textarea><br>
		<textarea id='textareames{$comment['id']}' name='reviewmes' cols='80' rows='2'>".ak_htmlspecialchars($comment['message'])."</textarea><br>
		<input type='submit' value='{$lan['save']}' onclick='savecommentchange({$comment['id']},{$comment['itemid']});return false;' id='save{$comment['id']}'></form></div></td></tr>";
	}
	if(!empty($items)) {
		$ids = implode(',', $items);
		$query = $db->query_by('*', 'items', "id IN ($ids)");
		while($item = $db->fetch_array($query)) {
			$_items[$item['id']] = $item;
		}
		foreach($items as $id) {
			$to = '';
			if(isset($_items[$id])) {
				$item = $_items[$id];
				$url = itemurl($id, 1, $item);
				$to = "@<a href='$url' target='_blank'>".$_items[$id]['title']."</a> <a href='index.php?file=admincp&action=edititem&id={$id}'>>></a>";
			}
			$str_comments = str_replace("@[{$id}]", $to, $str_comments);
		}
	}
	$str_comments == '' && $str_comments = "<tr bgcolor='#FFFFFF'><td>{$lan['commentempty']}</td></tr>";
	displaytemplate('admincp_allcomments.htm', array('comments' => $str_comments, 'str_index' => $str_index, 'num' => $i));
} elseif($get_action == 'deletecomment') {
	vc();
	if(empty($get_id) || !a_is_int($get_id)) aexit('no');
	$db->delete('comments', "id='{$get_id}'");
	refreshcommentnum($get_itemid);
	$commentnum = $db->get_by('commentnum', 'items', "id='{$get_itemid}'");
	aexit($commentnum);
} elseif($get_action == 'commentdenyip') {
	vc();
	$comment_deny_ip_dic = AK_ROOT.'configs/comment_deny_ips.txt';
	empty($get_ip) && aexit('no');
	$commentdenyips_data = readfromfile($comment_deny_ip_dic);
	$commentdenyips = explode("\n", $commentdenyips_data);
	if(!in_array($get_ip, $commentdenyips)) {
		if($commentdenyips_data == '') {
			$commentdenyips_data = $get_ip;
		} else {
			$commentdenyips_data = "\n".$get_ip;
		}
		writetofile($commentdenyips_data, $comment_deny_ip_dic);
	}
	deletecommentbyip($get_ip);
	refreshcommentnum($get_itemid);
	aexit('ok');
} elseif($get_action == 'createhtml') {
	if(empty($get_id)) debug($lan['parameterwrong'], 1, 1);
	if(empty($get_category)) $get_category = $db->get_by('category', 'items', "id='$get_id'");
	$category = getcategorycache($get_category);
	if($get_category != 0 && ($category['html'] == -1 || ($setting_ifhtml == 0 && $category['html'] == 0))) debug($lan['functiondisabled'], 1, 1);
	$result = batchhtml(array($get_id));
	if($result === false) {
		deletetask('indextaskitem');
		debug($lan['createhtmlerror'], 1, 1);
	}
	debug($lan['operatesuccess'], 1, 1);
} elseif($get_action == 'templates') {
	checkcreator();
	if(!isset($post_templatename)) {
		$str_maintemplates = '';
		$str_subtemplates = '';
		if(!$dh = opendir($templatedir)) adminmsg($lan['templatedirerror']."<br />({$templatedir})", '', 0, 1);
		$files = array();
		while(false !== ($filename = readdir($dh))) {
			if($filename != '.' && $filename != '..') $files[] = $filename;
		}
		$i = $j = 0;
		sort($files);
		foreach($files as $id => $file) {
			if(substr($file, -4) != '.htm') continue;
			if(substr($file, 0, 1) == '.') continue;
			if(substr($file, 0, 1) == ',') {
				$i ++;
				$file = substr($file, 1);
				$str_maintemplates .= "<tr><td>{$i}</td>
					<td><a href=\"index.php?file=admincp&action=template&template=,{$file}\">{$file}&nbsp;{$lan['edit']}</a>&nbsp;<a href=\"index.php?file=admincp&action=deletetemplate&vc={$vc}&template=,{$file}\" onclick=\"return confirmdelete()\">".alert($lan['delete'])."</a></td>";
			} else {
				$j ++;
				$str_subtemplates .= "<tr><td>{$j}</td>
					<td><a href=\"index.php?file=admincp&action=template&template={$file}\">{$file}&nbsp;{$lan['edit']}</a>&nbsp;<a href=\"index.php?file=admincp&action=deletetemplate&vc={$vc}&template={$file}\" onclick=\"return confirmdelete()\">".alert($lan['delete'])."</a></td>
				</tr>";
			}
		}
		displaytemplate('admincp_templates.htm', array('str_maintemplates' => $str_maintemplates, 'str_subtemplates' => $str_subtemplates));
	} else {
		if(empty($post_templatename) || !preg_match('/^[0-9a-zA-Z_]+$/i', $post_templatename)) adminmsg($lan['templatenameerror'], 'back', 3, 1);
		$prefix = $post_prefix;
		$filename = $templatedir.$prefix.$post_templatename.'.htm';
		if(file_exists($filename)) adminmsg($lan['templateexit'] , 'back', 3, 1);
		$text = $lan['newtemplate'];
		if(!writetofile($text, $filename)) adminmsg($lan['cantcreatetemplate'] , 'back', 3, 1);
		updatecache('templates');
		go('index.php?file=admincp&action=templates');
	}
}elseif($get_action == 'template') {
	checkcreator();
	if(!isset($get_job)) {
		if(!is_writable($templatedir.$get_template)) adminmsg($lan['templatenotwritable'], '', 3, 1);
		$str_template = ak_htmlspecialchars(readfromfile($templatedir.$get_template));
		displaytemplate('admincp_template.htm', array('str_template' => $str_template, 'template' => $get_template, 'language' => $language));
	} elseif($get_job == 'delete') {
		$filename = $templatedir.$get_template;
		if(preg_match('/^,/i', $get_template)) {
			$template = substr($get_template, 1);
			if($db->get_by('*', 'items', "template='$template'")) adminmsg($lan['deltemplatehasused'], 'index.php?file=admincp&action=templates', 3, 1);
		}
		if(!file_exists($filename)) adminmsg($lan['notemplate'] , 'index.php?file=admincp&action=templates');
		if(akunlink($filename) === false) {
			adminmsg($lan['cantdeltemplate'] , 'index.php?file=admincp&action=templates');
		} else {
			adminmsg($lan['operatesuccess'] , 'index.php?file=admincp&action=templates');
		}
	} elseif($get_job == 'save') {
		if(!is_writable($templatedir.$post_template)) adminmsg($lan['templatenotwritable'], '', 3, 1);
		if(!writetofile($post_html, $templatedir.$post_template)) adminmsg($lan['templatenotwritable'], '', 3, 1);
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=templates');
	}
	updatecache('templates');
} elseif($get_action == 'variables') {
	checkcreator();
	if(!isset($get_job)) {
		$query = $db->query_by('*', 'variables', '1', 'variable');
		$str_variables = '';
		$i = 0;
		$typeoptions = "<option value='string'>{$lan['string']}</option><option value='int'>{$lan['number']}</option><option value='pass'>{$lan['password']}</option><option value='radio'>{$lan['radio']}</option><option value='checkbox'>{$lan['checkbox']}</option><option value='text'>{$lan['text']}</option><option value='richtext'>{$lan['richtext']}</option><option value='category'>{$lan['category']}</option><option value='categories'>{$lan['category']}({$lan['multiple']})</option><option value='section'>{$lan['section']}</option><option value='picture'>{$lan['picture']}</option>";
		while($v = $db->fetch_array($query)) {
			$i ++;
			$str_variables .= "<tr><td width='30' valign='top'>{$i}</td>
			<td width='128' valign='top'>{$v['variable']}<input type='hidden' value='{$v['variable']}' name='variable_{$v['variable']}'></td>
			<td valign='top'><input type='text' style='width:158px;' name='description_{$v['variable']}' value='".ak_htmlspecialchars($v['description'])."' /></td>
			<td valign='top'><input type='text' style='width:88px;' name='standby_{$v['variable']}' value='".ak_htmlspecialchars($v['standby'])."' /></td>
			<td valign='top'><select id='type_{$v['variable']}' name='type_{$v['variable']}'>$typeoptions</select><script>$('#type_{$v['variable']}').val('{$v['type']}');</script></td>";
			$str_variables .= "<td>".renderinput($v['variable'], $v['type'], $v['standby'], $v['value'])."</td>";
			$str_variables .= "<td><input type='button' value='{$lan['delete']}' onclick=\"deletevariable('{$v['variable']}')\"></td>";
			$str_variables .= "</tr>";
		}
		displaytemplate('admincp_variables.htm', array('typeoptions' => $typeoptions, 'variables' => $str_variables));
	} elseif($get_job == 'delete') {
		vc();
		$db->delete('variables', "variable='$get_variable'");
		updatecache('globalvariables');
		go('index.php?file=admincp&action=variables');
	} elseif($get_job == 'save') {
		$query = $db->query_by('*', 'variables', "1");
		$variables = array();
		while($variable = $db->fetch_array($query)) {
			$variables[$variable['variable']] = $variable;
		}
		foreach($_POST as $k => $v) {
			if(strpos($k, 'variable_') === 0) {
				$key = substr($k, 9);
				if(a_is_int($key)) {
					$variable = $v;
					if($variable == '') continue;
				} else {
					$variable = $key;
				}
				if(!isset($_POST[$key])) $_POST[$key] = '';
				if(is_array($_POST[$key])) $_POST[$key] = implode(',', $_POST[$key]);
				if(!empty($_FILES[$key.'_upload']['name'])) {
					$_u = $_FILES[$key.'_upload'];
					$headpicext = fileext($_u['name']);
					if(!ispicture($_u['name'])) adminmsg($lan['pictureexterror'], 'back', 3, 1);
					$filename = get_upload_filename($_u['name'], 0, 0, 'preview');
					if(uploadfile($_u['tmp_name'], FORE_ROOT.$filename)) $_POST[$key] = $filename;
				}
				$value = array(
					'variable' => $variable,
					'type' => $_POST['type_'.$key]
				);
				$value['description'] = $_POST['description_'.$key];
				$value['standby'] = $_POST['standby_'.$key];
				$value['value'] = $_POST[$key];
				if(isset($variables[$key])) {
					$variable = $variables[$key];
					if($variable['description'] == $value['description'] && $variable['standby'] == $value['standby'] && $variable['type'] == $value['type'] && $variable['value'] == $value['value']) continue;
					$db->update('variables', $value, "variable='$v'");
				} else {
					$db->insert('variables', $value);
				}
			}
		}
		updatecache('globalvariables');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=variables');
	}
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
	displaytemplate('admincp_variable.htm', array('html' => $html));
} elseif($get_action == 'savevariable') {
	foreach($_POST as $k => $v) {
		if(empty($_FILES[$k.'_upload']['name'])) {
			if(is_array($v)) $v = implode(',', $v);
			$db->update('variables', array('value' => $v), "variable='$k'");
			continue;
		}
		$_u = $_FILES[$k.'_upload'];
		$headpicext = fileext($_u['name']);
		if(!ispicture($_u['name'])) adminmsg($lan['pictureexterror'], 'back', 3, 1);
		$filename = get_upload_filename($_u['name'], 0, 0, 'preview');
		if(uploadfile($_u['tmp_name'], FORE_ROOT.$filename)) $_POST[$k] = $filename;
		$db->update('variables', array('value' => $filename), "variable='$k'");
	}
	updatecache('globalvariables');
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'filters') {
	checkcreator();
	if(!isset($get_job)) {
		$query = $db->query_by('*', 'filters');
		$filters = '';
		$i = 0;
		while($f = $db->fetch_array($query)) {
			$i ++;
			if(!empty($f['ext'])) $f['ext'] = ak_unserialize($f['ext']);
			$remark = '';
			if(isset($f['ext']['remark'])) $remark = $f['ext']['remark'];
			$filters .= "<tr><td width=\"30\" valign='top'>{$f['id']}</td><td><a href='index.php?file=admincp&action=filters&job=edit&id={$f['id']}'>{$f['title']}</a></td><td>$remark</td><td>".nl2br(ak_htmlspecialchars($f['data']))."</td><td valign='top'><a href='index.php?file=admincp&action=filters&job=edit&id={$f['id']}'>{$lan['edit']}</a></td>";
			$filters .= "<td valign='top'><a href=\"javascript:deletefilter('{$f['id']}')\">{$lan['delete']}</a></td></tr>";
		}
		displaytemplate('admincp_filters.htm', array('filters' => $filters));
	} elseif($get_job == 'new') {
		if(empty($post_title) || a_is_int($post_title) || !preg_match("/^[a-z0-9_]+$/i", $post_title)) adminmsg($lan['filtertitlemessage'], 'back' ,3 , 1);
		if($row = $db->get_by('*', 'filters', "title='$post_title'"))  adminmsg($lan['filterexists'], 'back' ,3 , 1);
		if(!empty($post_remark)) $post_remark = serialize(array('remark' => $post_remark));
		$value = array('data' => $post_filter, 'title' => $post_title, 'ext' => $post_remark);
		$db->insert('filters', $value);
		updatecache('filters');
		go('index.php?file=admincp&action=filters');
	} elseif($get_job == 'edit') {
		if(empty($post_id)) {
			if(empty($get_id)) aexit();
			$filter = $db->get_by('*', 'filters', "id='$get_id'");
			if(empty($filter)) aexit();
			$filter['remark'] = '';
			if(!empty($filter['ext'])) $filter['ext'] = ak_unserialize($filter['ext']);
			if(isset($filter['ext']['remark'])) $filter['remark'] = $filter['ext']['remark'];
			array_walk($filter, '_htmlspecialchars');
			displaytemplate('admincp_filter.htm', $filter);
		} else {
			if(empty($post_title) || a_is_int($post_title) || !preg_match("/^[a-z0-9_]+$/i", $post_title)) adminmsg($lan['filtertitlemessage'], 'back' ,3 , 1);
			if(($row = $db->get_by('*', 'filters', "title='$post_title'")) && $row['id'] != $post_id)  adminmsg($lan['filterexists'], 'back' ,3 , 1);
			$value = array('data' => $post_filter, 'title' => $post_title, 'ext' => serialize(array('remark' => $post_remark)));
			$db->update('filters', $value, "id='$post_id'");
			updatecache('filters');
			go('index.php?file=admincp&action=filters');
		}
	} elseif($get_job == 'delete') {
		vc();
		$db->delete('filters', "id='$get_id'");
		updatecache('filters');
		go('index.php?file=admincp&action=filters');
	}
} elseif($get_action == 'createcategory') {
	if(empty($setting_ifhtml)) adminmsg($lan['createhtml'].$lan['functiondisabled'].'<br><br><a href="index.php?file=setting&action=functions">'.$lan['open'].'</a>', '', 0, 1);
	$do = httpget('do');
	if($do == '') {
		$modules = getcache('modules');
		if(empty($modules)) adminmsg('No modules exist!', '', 3, 1);
		$mids = array();
		foreach($modules as $mid => $module) {
			if(isset($module['data']['categorypage']) && $module['data']['categorypage'] == -1) continue;
			if(isset($module['data']['categoryhtml']) && $module['data']['categoryhtml'] == -1) continue;
			$mids[] = $mid;
		}
		if(empty($mids)) adminmsg($lan['nocategoryhtml'], '', 3, 1);
		$query = $db->query_by('id,category,html', 'categories', "module IN (".implode(',', $mids).")", 'id');
		$categorieslist = '';
		while($c = $db->fetch_array($query)) {
			$categorieslist .= "<tr><td><input type='checkbox' name='cid[]' checked='checked' value='{$c['id']}'></td>";
			$categorieslist .= "<td><a href='index.php?file=admincp&action=editcategory&id={$c['id']}'>{$c['id']}.{$c['category']}</a></td>";
			$categorieslist .= "<td><a href='index.php?file=admincp&action=createcategory&do=create&id={$c['id']}'>{$lan['createhtml']}</a></td></tr>";
		}
		displaytemplate('admincp_createcategory.htm', array('categorieslist' => $categorieslist));
	} elseif($do == 'create') {
		createcategoryhtml($get_id);
		adminmsg($lan['operatesuccess']);
	} elseif($do == 'batch') {
		foreach($post_cid as $id) {
			addtask('batch_createhtml_category', $id);
		}
		akheader('location:index.php?file=admincp&action=createcategory&do=frame');
	} elseif($do == 'frame') {
		showprocess($lan['running'], 'index.php?file=admincp&action=createcategory&do=process');
	} elseif($do == 'process') {
		$task = gettask('batch_createhtml_category');
		if(empty($task)) {
			aexit("100\t\t");
		} else {
			createcategoryhtml($task);
			$percent = gettaskpercent('batch_createhtml_category');
			aexit("$percent\t\t");
		}
	}
} elseif($get_action == 'createitem') {
	$taskkey = 'batchcreateitem';
	if(empty($setting_ifhtml)) adminmsg($lan['createhtml'].$lan['functiondisabled'].'<br><br><a href="index.php?file=setting&action=functions">'.$lan['open'].'</a>', '', 0, 1);
	if(isset($get_category)) {
		if($get_category > 0) {
			$where = "category='$get_category'";
		} elseif($get_category == 0) {
			$where = "category>0";
		} else {
			$where = '1';
		}
		$query = $db->query_by('id', 'items', $where);
		$items = array();
		while($item = $db->fetch_array($query)) {
			$items[] = $item['id'];
		}
		if(empty($items)) adminmsg($lan['noitem'], 'index.php?file=admincp&action=createitem');
		deletetask($taskkey);
		addtasks($taskkey, $items);
		showprocess($lan['running'], 'index.php?file=admincp&action=createitem&process=1&step='.$get_step.'&all='.count($items));
		$batchitemready = $lan['batchitemready'];
		$batchitemready = str_replace('(*1)', count($items), $batchitemready);
		adminmsg($batchitemready, 'index.php?file=admincp&action=createitem&process=1&step='.$get_step.'&all='.count($items), 3);
	} elseif(!empty($get_process)) {
		$tasks = gettask($taskkey, $get_step);
		if(empty($tasks)) aexit(100);
		batchhtml($tasks);
		$finishedpercent = gettaskpercent($taskkey);
		aexit("$finishedpercent\t\t");
	} else {
		$categories = get_select('category');
		displaytemplate('admincp_createitem.htm', array('categories' => $categories));
	}
} elseif($get_action == 'createsection') {
	if(isset($get_id)) {
		if(empty($get_id)) {
			$sections = getcache('sections');
			$query = $db->query_by('*', 'sections', '', 'id');
			$batchsections = array();
			foreach($categories as $c) {
				$batchsections[] = $c['id'];
			}
			batchsectionhtml($batchcategories);
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=createsection&job=process');
		} else {
			batchsectionhtml($get_id);
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=createsection&job=process');
		}
	} elseif(isset($post_cid)) {
		foreach($post_cid as $cid) {
			batchsectionhtml($cid);
		}
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
	} elseif(isset($get_job) && $get_job == 'process') {
		if(operatecreatesectionprocess() === true) {
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
		} else {
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=createsection&job=process');
		}
	}
} elseif($get_action == 'delattach') {
	if($attach = $db->get_by('*', 'attachments', "id='{$get_id}'")) {
		akunlink(FORE_ROOT.$attach['filename']);
		$db->delete('attachments', "id={$get_id}");
		if(!$db->get_by('*', 'attachments', "itemid='{$attach['itemid']}'")) {
			$db->update('items', array('attach' => 0), "id='{$attach['itemid']}'");
		}
		$attachcount = $db->get_by('attach', 'items', "id={$attach['itemid']}");
		if($attachcount == 0) {
			$db->update('items', array('attach' => $attachcount), "id='{$attach['itemid']}'");
		} else {
			$db->update('items', array('attach' => $attachcount-1), "id='{$attach['itemid']}'");
		}
		aexit('ok');
	} else {
		aexit('no');
	}
} elseif($get_action == 'modules') {
	checkcreator();
	if(empty($get_job)) {
		$query = $db->query_by('*', 'modules', '', 'id');
		$moduleslist = '';
		while($module = $db->fetch_array($query)) {
			$moduleslist .= "<tr><td>{$module['id']}</td><td><a href=\"index.php?file=admincp&action=modules&job=editmodule&id={$module['id']}\">{$module['modulename']}</a></td><td>";
			if($module['id'] > 1) $moduleslist .= "<a href=\"index.php?file=admincp&action=modules&job=del&vc={$vc}&id={$module['id']}\">{$lan['del']}</a>";
			$moduleslist .= "</td></tr>";
		}
		displaytemplate('admincp_modules.htm', array('moduleslist' => $moduleslist));
	} elseif($get_job == 'addmodule') {
		displaytemplate('admincp_module.htm', array('page' => 1, 'numperpage' => 10, 'fieldshtml' => modulefields()));
	} elseif($get_job == 'del') {
		vc();
		if(empty($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
		$_sql = "DELETE FROM {$tablepre}_modules WHERE id='$get_id'";
		$db->query($_sql);
		updatecache('modules');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=modules');
	} elseif($get_job == 'editmodule') {
		if(!isset($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
		$module = $db->get_by('*', 'modules', "id='{$get_id}'");
		$data = ak_unserialize($module['data']);
		$templates = get_select_templates();
		$fieldshtml = modulefields($data['fields']);
		$variables = array();
		$variables['modulename'] = $module['modulename'];
		$variables['fieldshtml'] = $fieldshtml;
		$variables['id'] = $get_id;
		$variables['templates'] = $templates;
		foreach($data as $k => $v) {
			if($k == 'id') continue;
			$variables[$k] = $v;
		}
		displaytemplate('admincp_module.htm', $variables);
	} elseif($get_job == 'savemodule') {
		if(empty($post_modulename)) debug('error', 1);
		$data = array();
		foreach($itemfields as $field) {
			$_alias = "post_{$field}_alias";
			$_order = "post_{$field}_order";
			$_listorder = "post_{$field}_listorder";
			$_description = "post_{$field}_description";
			$_default = "post_{$field}_default";
			$_size = "post_{$field}_size";
			$_type = "post_{$field}_type";
			$data['fields'][$field] = array(
				'alias' => $$_alias,
				'order' => $$_order,
				'listorder' => $$_listorder,
				'description' => $$_description,
				'default' => $$_default,
				'size' => isset($$_size) ? $$_size : '',
				'type' => isset($$_type) ? $$_type : '',
			);
			if($field == 'title') {
				$data['fields']['title']['iftitlestyle'] = !empty($post_iftitlestyle);
			}
		}
		foreach($_POST as $_k => $_v) {
			if(substr($_k, 0, 8) == 'extfield' && strlen($_k) < 11) {
				$_id = substr($_k, 8);
				$_key = "post_extfield{$_id}";
				$_alias = "post_extfield_alias{$_id}";
				if(empty($$_alias) || empty($$_key)) continue;
				$_order = "post_extfield_order{$_id}";
				$_listorder = "post_extfield_listorder{$_id}";
				$_description = "post_extfield_description{$_id}";
				$_default = "post_extfield_default{$_id}";
				$_size = "post_extfield_size{$_id}";
				$_type = "post_extfield_type{$_id}";
				$data['fields']['_'.$$_key] = array(
					'alias' => $$_alias,
					'order' => $$_order,
					'listorder' => $$_listorder,
					'description' => $$_description,
					'default' => $$_default,
					'size' => isset($$_size) ? $$_size : '',
					'type' => isset($$_type) ? $$_type : '',
				);
			}
		}
		foreach($_POST as $k => $v) {
			if(strpos($k, '_') === false) $data[$k] = $v;
		}
		$data = serialize($data);
		$value = array(
			'modulename' => $post_modulename,
			'data' => $data
		);
		if(!empty($post_id)) {
			$db->update('modules', $value, "id='$post_id'");
		} else {
			$db->insert('modules', $value);
		}
		updatecache('modules');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=modules');
	}
} elseif($get_action == 'attachments') {
	if(empty($get_id)) aexit('error');
	$attachments = array();
	$query = $db->query_by('*', 'attachments', "itemid='$get_id'", 'id');
	while($attach = $db->fetch_array($query)) {
		$url = $attach['filename'];
		if(strpos($url, '://') === false) $url = $homepage.$url;
		$attachments[] = array('id' => $attach['id'], 'originalname' => toutf8($attach['originalname']), 'url' => $url, 'dateitme' => date('Y-m-d', $attach['dateline']), 'desc' => toutf8($attach['description']), 'orderby' => $attach['orderby'], 'filesize' => ceil($attach['filesize'] / 1024));
	}
	aexit(json_encode($attachments));
} elseif($get_action == 'updateattach') {
	if(empty($get_id) || empty($_POST)) aexit('error');
	$value = array(
		'description' => fromutf8($_POST['desc']),
		'orderby' => fromutf8($_POST['orderby'])
	);
	$db->update('attachments', $value, "id='$get_id'");
	aexit('ok');
} elseif($get_action == 'selectcategories') {
	header("Cache-Control:");
	$where = "categoryup='$get_up'";
	if($get_module == -1 || $get_module == 0) {
		$where .= " AND module IN (-1,0)";
	} else {
		$where .= " AND module='$get_module'";
	}
	$query = $db->query_by('id,category', 'categories', $where);
	$i = 0;
	while($category = $db->fetch_array($query)) {
		$i ++;
		echo "$('#category{$get_level}').append(\"<option value='{$category['id']}'>{$category['category']}</option>\");\n";
	}
	if(!empty($get_defaultlist)) {
		$lists = explode(',', $get_defaultlist);
		if(isset($lists[$get_level])) {
			echo "$('#category{$get_level}').val({$lists[$get_level]});\n";
			echo "selectcategory($get_level + 1, $('#category{$get_level}').val());\n";
		}
	}
	if($i > 0) {
		echo "$(\"#category{$get_level}\").show();\n";
	} else {
		echo "$(\"#category{$get_level}\").hide();\n";
	}
} elseif($get_action == 'getreview') {
	if(empty($get_itemid)) aexit("no");
	$getreviewresult = $db->get_by('review', 'comments', "id=$get_itemid");
	aexit($getreviewresult);
} elseif($get_action == 'getcmessage') {
	if(empty($get_itemid)) aexit("no");
	$getmesresult = $db->get_by('message', 'comments', "id=$get_itemid");
	aexit($getmesresult);
} elseif($get_action == 'reviewcomment') {
	$value = array(
		'message' => fromutf8($_POST['message']),
		'review' => fromutf8($_POST['review']),
		'reviewtime' => $thetime,
	);
	$db->update('comments', $value, "id='{$_POST['id']}'");
	aexit('ok');
} elseif($get_action == 'refreshcategory') {
	$query = $db->query_by('id', 'categories', '1', 'categoryup DESC');
	while($c = $db->fetch_array($query)) {
		refreshitemnum($c['id'], 'category');
	}
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=categories', 1);
} elseif($get_action == 'deletetemplate') {
	vc();
	if(substr($get_template, -4) != '.htm') aexit();
	if(strpos($get_template, '/') !== false || strpos($get_template, '\\') !== false) aexit();
	$result = akunlink(AK_ROOT.'configs/templates/'.$template_path.'/'.$get_template);
	if($result) {
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=templates', 1);
	} else {
		adminmsg('error', 'index.php?file=admincp&action=templates', 1);
	}
} elseif($get_action == 'users') {
	$where = '1';
	$url_condition = '';
	if(!empty($get_id) && a_is_int($get_id)) {
		$where .= " AND id='$get_id'";
		$url_condition .= "&id={$get_id}";
	}
	if(!empty($get_username)) {
		$where .= " AND username='$get_username'";
		$url_condition .= "&username={$get_username}";
	}
	if(!empty($get_email)) {
		$where .= " AND email LIKE '%$get_email%'";
		$url_condition .= "&email={$get_email}";
	}
	if(!empty($get_ip)) {
		$where .= " AND ip='$get_ip'";
		$url_condition .= "&ip={$get_ip}";
	}
	if(isset($get_page)) {
		$page = $get_page;
	} else {
		$page = 1;
	}
	$page = max($page, 1);
	$ipp = 15;
	isset($post_page) && $page = $post_page;
	!a_is_int($page) && $page = 1;
	$start_id = ($page - 1) * $ipp;
	$query = $db->query_by('*', 'users', $where, 'id DESC', "$start_id,$ipp");
	$list = '';
	$url = 'index.php?file=admincp&action=users'.ak_htmlspecialchars($url_condition);
	$count = $db->get_by('COUNT(*)', 'users', $where);
	if($ipp * ($page - 1) > $count) {
		header('location:'.$currenturl.'&page='.ceil($count / $ipp));
		aexit();
	}
	$str_index = multi($count, $ipp, $page, $url);
	while($user = $db->fetch_array($query)) {
		$line = "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['email']}</td><td title='".date('H:i:s', $user['createtime'])."'>".date('Y-m-d', $user['createtime'])."</td><td>{$user['ip']}</td><td align='center'><a href='index.php?file=admincp&action=resetpassword&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'>{$lan['resetpassword']}</a></td><td align='center'>";
		if(empty($user['freeze'])) {
			$line .= "{$lan['available']}(<a href='index.php?file=admincp&action=freezeuser&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'>{$lan['freeze']}</a>)";
		} else {
			$line .= "{$lan['frozen']}(<a href='index.php?file=admincp&action=unfreezeuser&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'>{$lan['activate']}</a>)";
		}
		$line .= "</td><td align='center'><a href='index.php?file=admincp&action=deleteuser&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'><span style='color:red'>{$lan['delete']}</span></a></td></tr>";
		$list .= $line;
	}
	$params = array('str_index' => $str_index, 'list' => $list, 'get' => ak_htmlspecialchars($_GET));
	displaytemplate('admincp_users.htm', $params);
} elseif($get_action == 'resetpassword') {
	vc();
	$newpassword = random(8);
	changeuserpassword($get_uid, $newpassword);
	adminmsg($lan['newpassis'].':'.$newpassword);
} elseif($get_action == 'freezeuser') {
	vc();
	freezeuser($get_uid, 1);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'unfreezeuser') {
	vc();
	freezeuser($get_uid, 0);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'deleteuser') {
	vc();
	deleteuser($get_uid);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'paging') {
	$modules = getcache('modules');
	$count = $db->get_by('COUNT(*)', 'texts', "itemid='$get_id'");
	if($count == 0) aexit(0);
	$query = $db->query_by('subtitle,page,itemid,id', 'texts', "itemid='$get_id' AND page>0", 'page');
	$list = '';
	$i = 1;
	$pagingreturn = array();
	while($page = $db->fetch_array($query)) {
		$page['subtitle'] = ak_htmlspecialchars($page['subtitle']);
		if(empty($page['subtitle'])) $page['subtitle'] = $page['page'];
		$page['subtitle'] = toutf8($page['subtitle']);
		$pagingreturn[] = $page;
		$i ++;
	}
	aexit(json_encode($pagingreturn));
} elseif($get_action == 'pagingcontent'){
	$page = $db->get_by('subtitle,text,itemid', 'texts', "id='$get_id'");
	$page['subtitle'] = toutf8($page['subtitle']);
	$page['text'] = toutf8($page['text']);
	aexit(json_encode($page));
} elseif($get_action == 'savepaging') {
	$pagingconfig['itemurl'] = '';
	$pagingconfig['category'] = '0';
	if(isset($post_pdata_copypic) && $post_pdata_copypic == 1) {
		$post_pdata = copypicturetolocal($post_pdata, $pagingconfig);
	}
	$value = array(
		'subtitle' => fromutf8($post_subtitle),
		'text' => fromutf8($post_pdata)
	);
	$returnpidpnum = array();
	if(empty($post_id)) {
		$maxpage = getmaxpage($post_itemid);
		$value['itemid'] = $post_itemid;
		$value['page'] = $maxpage + 1;
		$db->insert('texts', $value);
		$currentid = $db->insert_id();
		$db->update('items', array('pagenum' => '+1'), "id='{$value['itemid']}'");
		$afteraddpagenum = $db->get_by('COUNT(*)', 'texts', "itemid='{$value['itemid']}'");
		$returnpidpnum['pid'] = $currentid;
		$returnpidpnum['pagenum'] = $afteraddpagenum;
		aexit(json_encode($returnpidpnum));
	} else {
		$db->update('texts', $value, "id='$post_id'");
		$text = $db->get_by('itemid,page', 'texts', "id=$post_id");
		$itemid = $text['itemid'];
		if(!empty($post_page) && $text['page'] != $post_page) {
			if($db->get_by('id', 'texts', "itemid='$itemid' AND page='$post_page'")) {
				if($text['page'] > $post_page) {
					$db->update('texts', array('page' => 32767), "id=$post_id");
					$db->query("UPDATE {$tablepre}_texts SET page=page+1 WHERE itemid='$itemid' AND page>='{$post_page}' AND page<'{$text['page']}' ORDER BY page DESC");
					$db->update('texts', array('page' => $post_page), "id=$post_id");
				} else {
					$db->update('texts', array('page' => 32767), "id=$post_id");
					$db->query("UPDATE {$tablepre}_texts SET page=page-1 WHERE itemid='$itemid' AND page>'{$text['page']}' AND page<='{$post_page}' ORDER BY page");
					$db->update('texts', array('page' => $post_page), "id=$post_id");
				}
			}
		}
		aexit("ok");
	}
} elseif($get_action == 'delpaging') {
	vc();
	$del_page = $db->get_by('page,itemid', 'texts', "id={$get_id}");
	$db->query("DELETE FROM {$tablepre}_texts WHERE id = $get_id");
	$db->query("UPDATE {$tablepre}_texts SET page=page-1 WHERE page>{$del_page['page']} AND itemid={$del_page['itemid']}");
	$pagenum = $db->get_by('COUNT(*) AS c ', 'texts', "itemid={$del_page['itemid']} AND page>0");
	$db->update('items', array('pagenum' => $pagenum), "id={$del_page['itemid']}");
	$returnpagenum = $db->get_by('pagenum', 'items', "id={$del_page['itemid']}");
	aexit($returnpagenum);
} elseif($get_action == 'ajaxtext') {
	$text = $db->get_by('text', 'texts', "id='$get_id'");
	exit($get_id.'#'.$text);
} elseif($get_action == 'ajaxcheckdomain') {
	$category = $db->get_by('*', 'categories', "domain='$get_domain'");
	if(!empty($category) && $category['id'] != $get_id) {
		aexit('error');
	}
} elseif($get_action == 'swfupload') {
	displaytemplate('swfupload.htm', array("itemid" => 1));
	aexit();
} elseif($get_action == 'jshook') {
	if(file_exists(actionhookfile('js'))) include(actionhookfile('js'));
} else {
	adminmsg($lan['nodefined'], '', 0, 1);
}
runinfo();
aexit();
?>