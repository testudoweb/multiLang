<?php

function tw_notification(){
	if($notifications = tw_getvar('notification', false)){
		foreach($notifications as $type => $text){
			echo '<div class="'.$type.'">'.$text.'</div>';
		}
	}
}

function tw_getvar($varname, $default = NULL){
	if(isset($_REQUEST[$varname])) return $_REQUEST[$varname];
		else return $default;
}

function tw_getset($varname, $default = NULL){
	global $ml_settings;
	if(isset($ml_settings[$varname])) return $ml_settings[$varname];
		else return $default;
}

/*************************
	THEME FUNCTIONS
*************************/

function ml_r($key){
	global $ml_tstrings;

	$key = strtoupper($key);

	if(isset($ml_tstrings[$key]) && !empty($ml_tstrings[$key])) return $ml_tstrings[$key];
	else return $key;
}

function ml_($key){
	global $ml_tstrings;

	echo ml_r($key);
}

function ml_meta_href($echo = true) {

	global $ml_map, $ml_langs, $pagesArray;

	$slug = (string)get_page_slug(FALSE);

	$output = '';

	foreach($ml_map['bypage'][$slug]['translations'] as $id => $translation){
		if($id == ML_LANG) continue;
		$output .= '<link rel="alternate" href="'.find_url($slug, $pagesArray[$slug]['parent']).'" hreflang="'.trim($ml_langs[$id]['locale']).'" />'."\n\t";
	}

	if($echo) echo $output;
	else return $output;

}

function ml_viewable($slug, $lang = false){
	global $ml_map;
	if(!$lang) if(defined('ML_LANG')) $lang = ML_LANG; else return false;
	
	if( 
		isset($ml_map['bypage'][$slug])
		&& $ml_map['bypage'][$slug]['mylang'] == $lang 
	) return true;
		return false;

}

function ml_get_translation($slug, $lang_id = false){
	global $ml_map, $ml_langs;

	if(!$lang_id && defined('ML_LANG')) $lang_id = ML_LANG;

	if(!is_numeric($lang_id)){
		foreach($ml_langs as $lid => $lang){
			if( strtolower($lang['code']) == strtolower($lang_id) ){
				$lang_id = $lid;
				break;
			}
		}
	}

	if(
		isset($ml_map['bypage'][$slug])
		&& !empty($ml_map['bypage'][$slug]['translations'][$lang_id])
	){
		return $ml_map['bypage'][$slug]['translations'][$lang_id];
	}else return 'false';
}

function ml_get_site_url($echo = true){
	global $pagesArray, $ml_map;

	$slug = $ml_map['bypage']['index']['translations'][ML_LANG];
	if(empty($slug)) $slug = 'index';

	$parent = $pagesArray[$slug]['parent'];

	$return = find_url($slug,$parent);
	if($echo) echo $return;
	else return $return;
}

function ml_get_navigation($currentpage,$classPrefix = "") {

	global $ml_map, $ml_langs;

	$menu = '';

	global $pagesArray;
	
	$pagesSorted = subval_sort($pagesArray,'menuOrder');
	if (count($pagesSorted) != 0) { 
		foreach ($pagesSorted as $page) {
			$sel = ''; $classes = '';
			$url_nav = $page['url'];
			if( $ml_map['bypage'][$url_nav]['mylang'] != ML_LANG) continue;
			
			if ($page['menuStatus'] == 'Y') { 
				$parentClass = !empty($page['parent']) ? $classPrefix.$page['parent'] . " " : "";
				$classes = trim( $parentClass.$classPrefix.$url_nav);
				if ("$currentpage" == "$url_nav") $classes .= " current active";
				if ($page['menu'] == '') { $page['menu'] = $page['title']; }
				if ($page['title'] == '') { $page['title'] = $page['menu']; }
				$menu .= '<li class="'. $classes .'"><a href="'. find_url($page['url'],$page['parent']) . '" title="'. encode_quotes(cl($page['title'])) .'">'.strip_decode($page['menu']).'</a></li>'."\n";
			}
		}
		
	}
	
	echo $menu;
}

function ml_get_translations_array($slug = false){
	global $ml_map, $ml_langs, $pagesArray;

	$slug = $slug ? $slug : (string)get_page_slug(FALSE);

	$return = array();

	foreach($ml_langs as $lid => $lang){
		if(!$lang['publish']) continue;

		$return[$lid]['lang_data'] = $lang;
		$return[$lid]['main_index'] = $pagesArray['index'];

		if(isset($ml_map['bypage']['index']['translations'][$lid]))
			$lang_index = $ml_map['bypage']['index']['translations'][$lid];
		else $lang_index = 'index';
		$return[$lid]['lang_index'] = $pagesArray[$lang_index];

		if(isset($ml_map['bypage'][$slug]['translations'][$lid]))
			$translation = $ml_map['bypage'][$slug]['translations'][$lid];
		else $translation = '';

		if(!empty($translation) && isset($pagesArray[$translation]))
			$return[$lid]['translation'] = $pagesArray[$translation];
		else $return[$lid]['translation'] = $return[$lid]['lang_index'];
	}

	return $return;
}

function ml_flags($echo = false){
	global $ml_langs, $pagesArray;

	$langs = ml_get_translations_array();

	$output = '<div class="language_flags">';
	foreach($langs as $lid => $lang){
		$output .= '<a class="lang_flag'.($lid == ML_LANG ? ' active' : '').'" href="'.find_url($lang['translation']['url'], $lang['translation']['parent']).'"><span class="flag flag-'.$lang['lang_data']['code'].'"></span></a>';
	}
	$output .= '</div>';

	if($echo) echo $output;
		else return $output;
}

function ml_select($echo = false){
	global $ml_langs, $pagesArray;

	$langs = ml_get_translations_array();

	$output = '<select class="language_flags">';
	foreach($langs as $lid => $lang){
		$output .= '<option'.($lid == ML_LANG ? ' selected' : '').' onclick="window.location.href=\''.find_url($lang['translation']['url'], $lang['translation']['parent']).'\'">'.$lang['lang_data']['name'].'</option>';
	}
	$output .= '</select>';

	if($echo) echo $output;
		else return $output;
}


function sort_langs(){
	global $ml_langs;

	$sort = array();
	foreach($ml_langs as $id=>$data){
		$sort[$id] = $data['order'];
	}

	asort($sort);
	$ordered = array();
	foreach($sort as $k=>$order){
		$ordered[$k] = $ml_langs[$k];
	}

	$ml_langs = $ordered;
}

function tw_save_settings(){
	global $ml_langs, $ml_settingsfile, $ml_settings;
	if(ML_ENV != 'admin') return;

	if(isset($_POST) && !empty($_POST) && tw_getvar('savesettings', false)){
		$json = json_encode($_POST);
							
		if (!file_put_contents($ml_settingsfile, $json)) {
			$_REQUEST['notification']['error'] = i18n_r('CHMOD_ERROR');
		} else {
			$_REQUEST['notification']['updated'] = i18n_r('SETTINGS_UPDATED');
		}

		$ml_settings = $_POST;
	}
}

function tw_save_strings(){
	global $ml_langs, $ml_stringfile, $ml_strings;
	if(ML_ENV != 'admin') return;

	if(isset($_POST['strings']) && !empty($_POST['strings'])){

		foreach($_POST['strings'] as $k => $val){
			$_POST['strings'][$k]['key'] = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $val['key']));
		}

		$json = json_encode($_POST['strings']);
							
		if (!file_put_contents($ml_stringfile, $json)) {
			$_REQUEST['notification']['error'] = i18n_r('CHMOD_ERROR');
		} else {
			$_REQUEST['notification']['updated'] = i18n_r('SETTINGS_UPDATED');
		}

		$ml_strings = $_POST['strings'];
	}
}

function tw_save_langs(){
	global $ml_langs, $ml_datafile, $mlf;
	if(ML_ENV != 'admin') return;

	if(isset($_POST) && !empty($_POST) && tw_getvar('savelang', false)){

		$fields = array('code', 'name', 'locale', 'direction', 'order', 'publish', 'gslang');
		$required = array('code', 'name', 'locale', 'direction', 'order', 'publish', 'gslang');
		$lastid = 0;

		$errors = array();
		foreach($required as $field){
			if(tw_getvar($field, false) === false){
				$errors['input_'.$field] = sprintf(i18n_r($mlf.'/ADMIN_LANGUAGES_ERROR_REQUIRED'), $field);
			}
			//check if alredy exists a pair code/locale
			foreach($ml_langs as $k=>$lang){
				if($lastid < $k) $lastid = $k; // searching for the last added id
				if( !tw_getvar('edit_id', false) && $lang['code'] == tw_getvar('code', false) && $lang['locale'] == tw_getvar('locale', false) ){
					$errors['duplicate'] = sprintf(i18n_r($mlf.'/ADMIN_LANGUAGES_ERROR_DUPLICATED'),tw_getvar('code', false) ,tw_getvar('locale', false) );
				}
			}
		}

		if(!$errors){
			
			$id = tw_getvar('edit_id', false);
			$id = empty($id) ? $lastid + 1 : $id;

			$bk = false;
			if(isset($ml_langs[$id])) $bk = $ml_langs[$id];

			foreach($fields as $field){
				$ml_langs[$id][$field] = tw_getvar($field);
			}

			if($bk){
				$ml_langs[$id]['default'] = $bk['default'];
				if($bk['default']) $ml_langs[$id]['publish'] = 1;
			}else{
				$ml_langs[$id]['default'] = count($ml_langs) == 1 ? 1 : 0;
				$ml_langs[$id]['publish'] = count($ml_langs) == 1 ? 1 : 0;
			}

		}else{
			$_REQUEST['notification']['error'] = implode('<br>', $errors);
			$_REQUEST['errors'] = $errors;
		}
	}

	sort_langs();
	tw_map_langs();

	$json = json_encode($ml_langs);
							
	if (!file_put_contents($ml_datafile, $json)) {
		$_REQUEST['notification']['error'] = i18n_r('CHMOD_ERROR');
	} else {
		$_REQUEST['notification']['updated'] = i18n_r('SETTINGS_UPDATED');
	}
} 

// function for assigning the default language
// to pages that has no language
function tw_map_langs(){
	global $pagesArray, $ml_map, $ml_langs;
	if(ML_ENV != 'admin' || !count($ml_langs)) return;

	//array by pages
	$bypage = $bylang = array();

	//default language
	$default = 1;
	foreach($ml_langs as $id=>$lang){
		if($lang['default']) $default = $id;
		$bylang[$id] = array();
	}
	reset($ml_langs);

	foreach($pagesArray as $slug => $page){
		$bypage[$slug]['mylang'] = isset($ml_map['bypage'][$slug]['mylang']) ? $ml_map['bypage'][$slug]['mylang'] : $default;
		$bypage[$slug]['translations'] = array();
		foreach($ml_langs as $id => $lang){
			if($id == $bypage[$slug]['mylang']) {
				$bylang[$id][] = $slug;
				$bypage[$slug]['translations'][$id] = $slug;
			}else{
				$bypage[$slug]['translations'][$id] = isset($ml_map['bypage'][$slug]['translations'][$id]) ? $ml_map['bypage'][$slug]['translations'][$id] : '';
			}
		}
	}
	$ml_map = array(
		'bylang' => $bylang, 
		'bypage' => $bypage
	);	

	tw_save_map();
}

function tw_save_map(){
	global $ml_map, $ml_mapfile;
	if(ML_ENV != 'admin') return;

	$json = json_encode($ml_map);
	file_put_contents($ml_mapfile, $json);
}

function tw_del_lang($del_id){
	global $ml_langs, $mlf, $ml_datafile;
	if(ML_ENV != 'admin') return;

	if(isset($ml_langs[$del_id])){
		if(!$ml_langs[$del_id]['default']){
			unset($ml_langs[$del_id]);
			tw_save_langs();
		}else{
			$_REQUEST['notification']['error'] = i18n_r($mlf.'/ADMIN_LANGUAGES_ERROR_DELETE_DEFAULT');
		}
	}else{
		$_REQUEST['notification']['error'] = i18n_r($mlf.'/ADMIN_LANGUAGES_ERROR_GENERIC');
	}
}

function tw_get_lang_files(){
	$return = array();
	//admin
	if(file_exists(GSADMINPATH.'lang/')){
		$files = scandir(GSADMINPATH.'lang/');
		//admin lang
		foreach($files as $file){
			$basename = basename($file, '.php');
			if($basename == $file || substr($file, 0,1) == '.' || is_dir($file)) continue;
			if(!in_array($basename, $return)) $return[] = $basename;
		}
	}
	//plugins
	if(file_exists(GSPLUGINPATH)){
		$folders = scandir(GSPLUGINPATH);
		//admin lang
		foreach($folders as $folder){
			if(!is_dir(GSPLUGINPATH.$folder)) continue;
			if(file_exists(GSPLUGINPATH.$folder.'/lang/')){
				$files = scandir(GSPLUGINPATH.$folder.'/lang/');
				//admin lang
				foreach($files as $file){
					$basename = basename($file, '.php');
					if($basename == $file || substr($file, 0,1) == '.' || is_dir($file)) continue;
					if(!in_array($basename, $return)) $return[] = $basename;
				}
			}
		}
	}

	return $return;
}

function tw_lang_make_default($def_id){
	global $ml_langs;
	if(ML_ENV != 'admin') return;

	foreach($ml_langs as $id=>$data){
		$ml_langs[$id]['default'] = ($id == $def_id) ? 1 : 0;
		$ml_langs[$id]['publish'] = ($id == $def_id) ? 1 : $ml_langs[$id]['publish'];
	}

	tw_save_langs();
	$_REQUEST['notification']['updated'] = i18n_r('SETTINGS_UPDATED');
}

function tw_lang_pub_unpub($pub_id){
	global $ml_langs, $mlf;
	if(ML_ENV != 'admin') return;

	if($ml_langs[$pub_id]['default'] && $ml_langs[$pub_id]['publish']){
		$_REQUEST['notification']['error'] = i18n_r($mlf.'/ADMIN_ERROR_UNPUBLISH_DEFAULT');
		return;
	}

	$ml_langs[$pub_id]['publish'] = $ml_langs[$pub_id]['publish'] ? 0 : 1;

	tw_save_langs();
	$_REQUEST['notification']['updated'] = i18n_r('SETTINGS_UPDATED');
}

function tw_extract_strings($lid){
	global $ml_strings;

	$return = array();

	foreach($ml_strings as $string){
		$key = $string['key'];
		if(!empty($key)){
			$return[$key] = $string['translations'][ML_LANG];
		}
	}

	return $return;
}

function tw_generate_sitemaps($lang_id = -1){
	if(getDef('GSNOSITEMAP',true)) return;
	if(ML_ENV != 'admin') return;

	if(!tw_getset('build_sitemap', false))return;

	// Variable settings
	global $SITEURL, $ml_map, $ml_langs, $mlf;
	$path = GSDATAPAGESPATH;
	
	global $pagesArray;
	getPagesXmlValues(false);
	$bkpages = $pagesArray;
	$pagesSorted = subval_sort($pagesArray,'menuStatus');

	foreach($pagesSorted as $k => $page){
		if(isset($ml_map['bypage'][$page['url']]) && $ml_map['bypage'][$page['url']]['mylang'] != $lang_id){
			unset($pagesSorted[$k]);
		}
	}
	reset($pagesSorted);

	$folder = GSROOTPATH .trim(tw_getset('sitemap_folder'),'/');
	if(!file_exists($folder)){
		mkdir($folder, 0777, true);
	}

	$filename = 'sitemap.'.$ml_langs[$lang_id]['code'].'.'.preg_replace('/[^A-Za-z0-9]/', '-', $ml_langs[$lang_id]['locale']).'.xml';
	$file = $folder.'/'.$filename;
	
	if (count($pagesSorted) != 0)
	{ 
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>');
		$xml->addAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd', 'http://www.w3.org/2001/XMLSchema-instance');
		//$xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$xml->addAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
		
		foreach ($pagesSorted as $page)
		{
			if ($page['url'] != '404')
			{		
				if ($page['private'] != 'Y')
				{
					// set <loc>
					$pageLoc = find_url($page['url'], $page['parent']);
					
					// set <lastmod>
					$tmpDate = date("Y-m-d H:i:s", strtotime($page['pubDate']));
					$pageLastMod = makeIso8601TimeStamp($tmpDate);
					
					// set <changefreq>
					$pageChangeFreq = 'weekly';
					
					// set <priority>
					if ($page['menuStatus'] == 'Y') {
						$pagePriority = '1.0';
					} else {
						$pagePriority = '0.5';
					}
					
					//add to sitemap
					$url_item = $xml->addChild('url');
					$url_item->addChild('loc', $pageLoc);
					$url_item->addChild('lastmod', $pageLastMod);
					$url_item->addChild('changefreq', $pageChangeFreq);
					$url_item->addChild('priority', $pagePriority);
					foreach($ml_map['bypage'][$page['url']]['translations'] as $l => $slug){
						if($l == $lang_id || empty($slug) ) continue;
						$xhtml = $url_item->addChild('xhtml:link', NULL, 'http://base.google.com/ns/1.0');
							$xhtml->addAttribute('rel', 'alternate');
							$xhtml->addAttribute('hreflang', $ml_langs[$l]['locale']);
							$url = find_url($bkpages[$slug]['url'], $bkpages[$slug]['parent']);
							$xhtml->addAttribute('href', $url);
					}
				}
			}
		}
		
		//create xml file
		XMLsave($xml, $file);
	}
	
	if (!defined('GSDONOTPING') && function_exists('pingGoogleSitemaps')) {
		if (file_exists($file)){
			$fname = basename($file);
			if( 200 === ($status=pingGoogleSitemaps($SITEURL.$folder.'/'.$fname)))	{
				#sitemap successfully created & pinged
				return true;
			} else {
				error_log(i18n_r('SITEMAP_ERRORPING'));
				return i18n_r('SITEMAP_ERRORPING');
			}
		} else {
			error_log(i18n_r('SITEMAP_ERROR'));
			return i18n_r('SITEMAP_ERROR');
		}
	} else {
		#sitemap successfully created - did not ping
		return true;
	}
}
