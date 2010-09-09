<?php
/*#######################################################################
	PRITLOG
	Pritlog is a lightweight blog app powered by PHP and Sqlite
	prit@pritlog.com
	PRITLOG now uses the MIT License
	http://www.opensource.org/licenses/mit-license.php
	Version: 0.813
#######################################################################*/
/* Enable error logging if required to display notices and warnings */
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

// Loading an extension based on OS

require_once('./includes/pritlog_functions.php');
require_once('./includes/pritlog_user.php');
require_once('./includes/pritlog_admin.php');
require_once('./includes/pritlog_posts.php');

if (!extension_loaded('sqlite')) {
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		@dl('php_sqlite.dll');
	} else {
		@dl('sqlite.so');
	}
}
$multi_user = false;  // 'true' - multi blog pritlog installation; 'false' - single blog pritlog installation
$domain = $_SERVER['HTTP_HOST'];
$domain = $_SERVER['HTTP_HOST'];
$domain_parts = explode('.',$domain);
if ($multi_user) {
	if (count($domain_parts) == 3 && $domain_parts[0]!= "www") { // make sure a subdomain is called
		$user  = $domain_parts[0];
		$user1 = $domain_parts[1];
	}
}
else $user="user";

  require("includes/secure_session.php");
  if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start("ob_gzhandler"); else ob_start();
  session_start();
  $ss = new SecureSession();
  $ss->check_browser = true;
  $ss->check_ip_blocks = 2;
  $ss->secure_word = 'SALT_aJyeiuLioRhjlP';
  $ss->regenerate_id = true;
  $debugMode    = "off";    // Turn this on for debugging displays. But is not fully functional yet.
  $separator    = "#~#";    // Separator used between fields when the entry files are created.
  $config       = array();
  $authors      = array();
  $authorsPass  = array();
  $authorsEmail = array();
  $authorsActCode = array();
  $authorsActStatus = array();
  $tags         = array();
  $priv 		= "" ;
  
  readConfig();                                            /* Read the config file and load it into the array */
  if ($config['cleanUrl'] == 0) $config['cleanIndex'] = "/index.php";
  else $config['cleanIndex'] = "";
  require("lang/".$config['blogLanguage'].".php");         /* Load the language file */
  $postdb               = getcwd()."/data/".$user."/postdb.sqlite";
  $config['postdb']     = $postdb;
  $config['authorFile'] = getcwd(). "/data/".$user."/authors.php";
  $_SESSION['user'] = $user;
  //$config['theme']      = "skyblue";
  if (!file_exists(getcwd().'/themes/'.$config['theme'])) {
      $config['theme'] = 'default';
      if (!file_exists(getcwd().'/themes/'.$config['theme'])) {
          die('Error in the themes directory');
      }
  }
  readAuthors();
  $morepriv	= " status = 1 and ";
  if (@$_SESSION['logged_in']) {
    $author		= $_SESSION['username'];
	$morepriv	= " (status = 1 or author = '$author') and ";
  }
  getPrivacy();
  $firstTime = false;
  if (!file_exists($postdb)) { $firstTime = true; }
  if (function_exists('sqlite_open')) {
       if ($config['db'] = sqlite_open($postdb, 0666, $sqliteerror)) {
          if ( $firstTime ) {
              @sqlite_query($config['db'], 'DROP TABLE posts');
              @sqlite_query($config['db'], 'DROP TABLE comments');
              @sqlite_query($config['db'], 'DROP TABLE stats');
              @sqlite_query($config['db'], 'DROP TABLE active_guests');
              @sqlite_query($config['db'], 'DROP TABLE active_users');
              @sqlite_query($config['db'], 'DROP TABLE plugins');
              @sqlite_query($config['db'], 'DROP TABLE ipban');
              @sqlite_query($config['db'], 'DROP TABLE logs');
              sqlite_query($config['db'], 'CREATE TABLE posts (title CHAR(100), content CHAR(4500), date DATETIME, postid PRIMARY KEY, category CHAR(20), type CHAR(5), stick CHAR(5), allowcomments CHAR(4), visits INTEGER, author CHAR(25), status INTEGER);');
              sqlite_query($config['db'], 'CREATE TABLE comments (commentid INTEGER PRIMARY KEY, postid CHAR(6), sequence INTEGER, author CHAR(25), title CHAR(100), content CHAR(4500), date DATETIME, ip CHAR(16), url CHAR(50), email CHAR(50), status CHAR(10));');
              sqlite_query($config['db'], 'CREATE TABLE stats (statid INTEGER PRIMARY KEY, stattype CHAR(10), statcount INTEGER);');
              sqlite_query($config['db'], 'CREATE TABLE active_guests (id INTEGER PRIMARY KEY, ip CHAR(16), logtime DATETIME);');
              sqlite_query($config['db'], 'CREATE TABLE active_users (id INTEGER PRIMARY KEY, ip CHAR(16), logtime DATETIME);');
              sqlite_query($config['db'], 'CREATE TABLE plugins (id PRIMARY KEY, name CHAR(50), author CHAR(50), url CHAR(80), description CHAR(300), status INTEGER);');
              sqlite_query($config['db'], 'CREATE TABLE ipban (id INTEGER PRIMARY KEY, ip CHAR(16));');
              sqlite_query($config['db'], 'CREATE TABLE logs (id INTEGER PRIMARY KEY, ip CHAR(16), action CHAR(30), date DATE);');
              $stattype  = "total";
              $statcount = 0;
              sqlite_query($config['db'], "INSERT INTO plugins (id, name, author, url, description, status) VALUES('nicedit', 'nicEdit Plugin', 'Prit', 'http://hardkap.net/forums', 'Plugin to add nicEdit WYSIWYG editor to Pritlog', 1);");
              sqlite_query($config['db'], "INSERT INTO stats (stattype, statcount) VALUES('$stattype', '$statcount');");
          }
       }
  }
  else { die("Your server does not seem to have Sqlite enabled. This version of Pritlog will not work without Sqlite."); }
  $ip = getRealIpAddr();
  $result = sqlite_query($config['db'], "select * from ipban WHERE ip = '$ip'");
  if (sqlite_num_rows($result) > 0) die($lang['errorNotAuthorized']);
  require("includes/init_plugins.php");
  loadCategories();
  $result = sqlite_query($config['db'], 'select MAX(postid) as mymax from posts');
  while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
       $lastEntry = $row['mymax'] + 1;
  }
  $newPostNumber    =$lastEntry;                      /* Assign a new post number if a new post will be created */
  $newFullPostNumber=str_pad($newPostNumber, 5, "0", STR_PAD_LEFT);
  $newPostFile      =$newFullPostNumber.$config['dbFilesExtension'];
  $op = 0;
  $path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
  if (trim($path, '/') != '' && $path != "/".$_SERVER['PHP_SELF']) { $op = 1; $path1 = $path; }
  $path =  (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
  if (trim($path, '/') != '') { $op = 2;	$path1 = $path; }
  $path = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
  if (trim($path, '/') != '' && $path != "/".SELF) { $op = 3; $path1 = str_replace($_SERVER['SCRIPT_NAME'], '', $path); }
  $path = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
  if ($op == 0) { $op = 4; $path1 = $path . basename($_SERVER['SCRIPT_NAME']); }
  //echo basename($_SERVER['SCRIPT_NAME']).' Option selected = '.$op.' Path = '.$path1.'<br>';
  //var_dump($_SERVER);
  //$path1 = str_replace("index.php","",$path1);
  //echo $path1.'<br>';

  $serverName='http://'.$_SERVER['SERVER_NAME'];
  $serverPort=($_SERVER['SERVER_PORT']=='80')?'':':'.$_SERVER['SERVER_PORT'];
  //$serverName.=':'.$serverPort;
  $scriptName=$_SERVER["SCRIPT_NAME"];
  $blogPath=dirname($serverName.$serverPort.$scriptName);  /* Detect the absolute path to Pritlog */
  if ($config['blogPath'] !== $blogPath) {                 /* Update the absolute path to Pritlog in the config file */
      $config['blogPath'] = $blogPath;
      writeConfig(false);
  }
  $fullpath  = "http://" . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];
  $remaining = str_replace($blogPath,"",$fullpath);
  $data = explode("/",$remaining);
  if ($config['cleanUrl'] == 0) $optionIndex = 2;
  else $optionIndex = 1;	
  $baseScript=basename($scriptName);
  //echo $blogPath.'1<br>';
  $i=0;

  $option = isset($data[$optionIndex])?$data[$optionIndex]:"mainPage";
  @$option = (trim($data[$optionIndex]) == "")?"mainPage":$data[$optionIndex];
  $option = str_replace("index.php","",$option); // PritlogMU
  if (isset($_REQUEST["func"])) $option = $_REQUEST["func"];
  $nicEditType = "default";
  $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  /* To get the query string from the pretty urls */
  $optionValue = str_replace("index.php","",isset($data[$optionIndex+1])?$data[$optionIndex+1]:"");
  $optionValue2= str_replace("index.php","",isset($data[$optionIndex+2])?$data[$optionIndex+2]:"");
  $optionValue3= str_replace("index.php","",isset($data[$optionIndex+3])?$data[$optionIndex+3]:"");
  //echo $optionIndex."<br>";
  //echo $option."<br>";
  //echo $optionValue."<br>";
  //echo $optionValue2."<br>";
  //echo $optionValue3."<br>";
  // In seconds. User will be logged out after this
  $inactive = $config['timeoutDuration'];
  // check to see if $_SESSION['timeout'] is set
  if ($inactive != 0) {
      $_SESSION['notice'] = "";
	  if( isset($_SESSION['timeout'])) {
		   $session_life  = time() - $_SESSION['timeout'];
		   if ($session_life > $inactive && isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
			   $_SESSION['notice']=$lang['loggedOut'];
			   $option="logoutPage";
		   }
	  }
	  $_SESSION['timeout'] = time();
  }
  //echo '<pre>'; print_r($_SESSION); echo '</pre>';
  if (isset($_SESSION['start'])) $_SESSION['start']   = false;
  else $_SESSION['start']   = true;
   $mypath  = isset($_SERVER['PATH_INFO'])?str_replace("/index.php","",$_SERVER['PATH_INFO']):"";
	if (isset($_SESSION['growlmsg'])) {$growlmsg = '$.jGrowl("'.$_SESSION['growlmsg'].'");'; unset($_SESSION['growlmsg']);}
	else $growlmsg = '';
   $referrer=$serverName.$_SERVER['REQUEST_URI'];
   if ($option == "mainPage") { $_SESSION['url']=$referrer; }
   $accessArray=array('newEntry', 'newEntryForm', 'newEntrySubmit', 'newEntrySuccess', 'deleteEntry', 'editEntry', 'editEntryForm', 'editEntrySubmit', 'deleteComment', 'myProfile', 'myProfileSubmit');
   if (in_array($option,$accessArray)) {
      if (!$ss->Check() || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
           $_SESSION['notice']="";
           $_SESSION['url']=$referrer;
           $_SESSION['access_type']="regular";
           header('Location: '.$config['blogPath'].$config['cleanIndex'].'/loginPage');
           die;
      }
   }
   $adminAccessArray=array('adminPage', 'adminPageBasic', 'adminPageBasicSubmit', 'adminPageAdvanced', 'adminPageAdvancedSubmit', 'adminPageAuthors', 'adminAuthorsAdd', 'adminAuthorsEdit', 'adminPagePlugins', 'adminPluginsSubmit');
   if (in_array($option, $adminAccessArray)) {
      if (!$ss->Check() || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !$_SESSION['isAdmin']) {
           $_SESSION['notice']="";
           $_SESSION['url']=$referrer;
           $_SESSION['access_type']="admin";
           header('Location: '.$config['blogPath'].$config['cleanIndex'].'/loginPage');
           die;
      }
   }
   if ($option == 'loginPageSubmit') {
       loginPageSubmit();
       die;
   }
   if ($option == 'logoutPage') {
       logout();
       //die;
   }
  
  
  //execute hooks only if there are hooks to execute
  if ($SHP->hooks_exist('hook-head-before')) {
  	$SHP->execute_hooks('hook-head-before');
  }
if($option == 'RSS')
{
      createRSS();
      exit();
}
if (trim($optionValue2) == "") { $postTitle=""; $postTitleSave="";}
else {
  $postTitle    =str_replace("  "," ",str_replace("-"," ",$optionValue2))." &laquo; ";
  $postTitleSave=str_replace("  "," ",str_replace("-"," ",$optionValue2))." | ";
}
$theme_header['title']       = $postTitle.$config['blogTitle'];
$theme_header['script']      = $blogPath; //$_SERVER["SCRIPT_NAME"];
$theme_header['optionValue'] = $optionValue;
$theme_header['blogPath']    = $blogPath;
$theme_header['errorGeneral']  = $lang['errorGeneral'];
$theme_header['blogTitle']   = $config['blogTitle'];
$theme_header['metaKeywords']= $config['metaKeywords'];
$theme_header['metaDesc']    = $config['metaDescription'];
$theme_header['loc_head']   = "";
$theme_header['loc_top']    = "";
$theme_header['loc_title_after'] = "";
$theme_header['growlmsg']     = $growlmsg;
//execute hooks only if there are hooks to execute
if ($SHP->hooks_exist('hook-head')) {
    $SHP->execute_hooks('hook-head');
}
$theme_main['loc_sidebar_top']    = "";
$theme_main['loc_sidebar_bottom'] = "";
$theme_main['loc_menu_top']       = "";
$theme_main['loc_menu_bottom']    = "";
$theme_main['loc_main_after']     = "";
$theme_main['loc_main_top']       = "";
$theme_main['loc_main_bottom']    = "";
if ($SHP->hooks_exist('hook-main')) {
    $SHP->execute_hooks('hook-main');
}
$theme_post['test'] = "";
$theme_new['test']  = "";
$public_data['test'] = "";
$theme_edit['test'] = "";
$theme_adminbasic['test'] = "";
$theme_main['blogPath']    = $blogPath;
$theme_main['tagCloud'] = "";
if (is_array($tags) && count($tags) > 0 && $config['showCategoryCloud'] == 1) {
    $theme_main['tagCloud'] = printTagCloudAgain($tags);
}
/* This function does the main logic to direct to other functions as required */
mainLogic();

if($option !== 'RSS') {
$theme_main['shareme'] = '<style>
#shareButton {font:12px Verdana, Helvetica, Arial; height: 30px;width:100px;}
#shareDrop {position:absolute; padding:10px; display: none; z-index: 100; top:-900px; left:0px; width: 200px;float:left;background: #E9E9E9;border:1px solid black;}
#shareButton img, #shareDrop img {border:0} #shareDrop a {color:#008DC2; padding:0px 5px;display:block;text-decoration:none;} #shareDrop a:hover {background-color: #999999; color: #fff; text-decoration:none;}
#shareshadow{position: absolute;left: 0; top: 0; z-index: 99; background: black; visibility: hidden;}
div.sharefoot {position: absolute; top: 172px; height:15px; width: 200px; text-align: center; background-color: #999999; color: #fff;}
div.sharefoot a{display:inline; color:#fff; background-color:#999999; } div.sharefoot a:hover{text-decoration:none; background: #00adef; color: #fff}
</style>';
$theme_main['shareme'] .= '<script type="text/javascript">';
$theme_main['shareme'] .=  'var bPath="'.$blogPath.'/images/bookmarks";';
$theme_main['shareme'] .=  'var u1   =encodeURIComponent(document.location.href);';
$theme_main['shareme'] .=  'var t1   ="'.urlencode($postTitleSave.$config['blogTitle']).'";';
$theme_main['shareme'] .= '</script>';
$theme_main['shareme'] .= '<style>';
$theme_main['shareme'] .= 'div #shareButton a { background: url("'.$blogPath.'/images/shareme.gif") no-repeat; }';
$theme_main['shareme'] .= 'div .share a span { cursor:pointer; display:block; margin-left:15px; color:#008DC2; padding:0px 5px; height:16px; width: 60px; text-decoration:none;}';
$theme_main['shareme'] .= 'div .share a span:hover {background-color: #999999; color: #fff; text-decoration:none;}';
$theme_main['shareme'] .= 'div .shareit { background: url("'.$blogPath.'/images/shareme.gif") no-repeat;}';
$theme_main['shareme'] .= '</style>';
$theme_main['shareme'] .=  '<script type="text/javascript" src="'.$blogPath.'/javascripts/shareme.js"></script>';
$theme_main['script']  = $config['blogPath'].$config['cleanIndex'];
/* Latest Entries */
$theme_main['latestEntriesHeader'] = $lang['sidebarHeadLatestEntries'];
sidebarListEntries();
/* Menu */
$theme_main['menuHeader'] = $lang['sidebarHeadMainMenu'];
$theme_main['blogTitle']  = $config['blogTitle'];
$theme_main['home']       = $lang['sidebarLinkHome'];
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath; //$_SERVER["SCRIPT_NAME"].'/mainPage';
$theme_menu['linktext'] = $lang['sidebarLinkHome'];
$theme_main['menu']	    = "";
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/archives';
$theme_menu['linktext'] = $lang['sidebarLinkArchive'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/RSS';
$theme_menu['linktext'] = $lang['sidebarLinkRSSFeeds'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && (isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)) {
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/adminPage';
$theme_menu['linktext'] = $lang['sidebarLinkAdmin'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/newEntry';
$theme_menu['linktext'] = $lang['sidebarLinkNewEntry'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/logoutPage';
$theme_menu['linktext'] = $lang['sidebarLinkLogout'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
} else {
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/newEntry';
$theme_menu['linktext'] = $lang['sidebarLinkNewEntry'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/myProfile';
$theme_menu['linktext'] = $lang['pageMyProfile'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/logoutPage';
$theme_menu['linktext'] = $lang['sidebarLinkLogout'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
} else {
$theme_menu['link']     = $theme_menu['linktext'] = "";
$theme_menu['link']     = $blogPath.$config['cleanIndex'].'/loginPage';
$theme_menu['linktext'] = $lang['sidebarLinkLogin'];
$theme_main['menu']    .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_menu["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/menu.tpl"));
}
}
$theme_main['categoriesHeader'] = $lang['postFtTags'];
$theme_main['categories']       = sidebarCategories();
$theme_main['pagesHeader'] = $lang['sidebarHeadPages'];
$theme_main['pages']       = sidebarPageEntries();
$theme_main['linksHeader'] = $lang['sidebarHeadLinks'];
$theme_main['links']       = sidebarLinks();

$theme_main['statsHeader'] = $lang['sidebarHeadStats'];
$theme_main['stats']       = sidebarStats();
$theme_main['popularHeader'] = $lang['sidebarHeadPopularEntries'];
$theme_main['popular']       = sidebarPopular();
$theme_main['commentsHeader'] = $lang['sidebarHeadLatestComments'];
$theme_main['comments']       = sidebarListComments();
$theme_listcomments['link']     = $config['blogPath'].$config['cleanIndex'].'/listAllComments';
$theme_listcomments['linktext'] = $lang['sidebarLinkListComments'];
$theme_main['comments']      .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_listcomments["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/listcomments.tpl"));
$theme_main['aboutHeader']    = $lang['pageBasicConfigAbout'];
$theme_main['about']          = $config['about'];
$theme_main['footer']    = $lang['footerCopyright'].' '.$config['blogTitle'].' '.date('Y').' - '.$lang['footerRightsReserved'].' - Powered by <a href="http://hardkap.net/pritlog/">Pritlog</a></div>';
if (!isset($_REQUEST["func"])) {
   print @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_header["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/header.tpl"));
   print @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_main["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/main.tpl"));
}
sqlite_close($config['db']);

}
?>

<?php

  function mainLogic() {
      global $debugMode,$option,$requestCategory,$optionValue,$serverName;
      //$category = $data[4];
      switch ($option) {
      case "newEntry":
          if ($debugMode=="on") {echo "Calling newEntryPass()";}
          newEntryForm();
		  $referrer=$serverName.$_SERVER['REQUEST_URI'];
	      $_SESSION['referrer'] = $referrer; 
          break;
      case "newEntryForm":
          if ($debugMode=="on") {echo "Calling newEntryForm()";}
          newEntryForm();
		  break;
      case "newEntrySubmit":
          newEntrySubmit();
          break;
      case "newEntrySuccess":
          newEntrySuccess();
          break;
      case "mainPage":
          $requestCategory = '';
          listPosts();
		  $referrer=$serverName.$_SERVER['REQUEST_URI'];
	      $_SESSION['referrer'] = $referrer;
          break; 
      case "deleteEntry":
          if ($debugMode=="on") {echo "deleteEntry  ".$_POST['process']."<br>";}
          //deleteEntrySubmit();
          if ($_POST['process']!=="deleteEntrySubmit") {
              deleteEntryForm();
          }
          else {
              deleteEntrySubmit();
          }
          break;
	  case "deleteEntrySubmit":
          if ($debugMode=="on") {echo "deleteEntry  ".$_POST['process']."<br>";}
		  deleteEntrySubmit();
          break;		
      case "editEntry":
          if ($debugMode=="on") {echo "editEntry  ".$_POST['process']."<br>";}
          editEntryForm();
		  $referrer=$serverName.$_SERVER['REQUEST_URI'];
	      $_SESSION['referrer'] = $referrer; 
          break;
      case "editEntryForm";
          editEntryForm();
          break;
      case "editEntrySubmit";
          editEntrySubmit();
          break;
      case "posts":
          viewEntry();
          break;
      case "archives":
          viewArchive();
          break;
      case "month":
          viewArchiveMonth();
          break;
      case "category":
          $requestCategory=$optionValue;
          listPosts();
		  $referrer=$serverName.$_SERVER['REQUEST_URI'];
	      $_SESSION['referrer'] = $referrer;
          break;
      case "searchPosts":
          searchPosts();
          break;
      case "sendComment":
          sendComment();
          break;
      case "sendCommentSuccess":
          sendCommentSuccess();
          break;
	  case "listAllComments":
          listAllComments();
          break;
      case "deleteComment":
          if ($debugMode=="on") {echo "deleteEntry  ".$_POST['process']."<br>";}
          $process=isset($_POST['process'])?$_POST['process']:"";
          if ($process !=="deleteCommentSubmit") {
              deleteCommentForm();
          }
          else {
              deleteCommentSubmit();
          }
          break;
	  case "deleteCommentSubmit":	 
		  if ($debugMode=="on") {echo "deleteEntrySubmit  ".$_POST['process']."<br>";}
		  deleteCommentSubmit();
          break;	  
      case "loginPage":
           loginPage();
           break;
      case "logoutPage":
           logoutPage();
           break;
      case "registerPage":
           registerPage();
           break;
      case "registerPageSubmit":
           registerPageSubmit();
           break;
      case "forgotPass":
           forgotPass();
           break;
      case "forgotPassSubmit":
           forgotPassSubmit();
           break;
      case "activation":
           activation();
           break;
      case "myProfile":
           myProfile();
           break;
      case "myProfileSubmit":
           myProfileSubmit();
           break;
      case "adminPage":
          adminPage();
          break;
      case "adminPageBasic":
          if ($debugMode=="on") {echo "adminPageBasic  ".$_POST['process']."<br>";}
          adminPageBasic();
          break;
      case "adminPageBasicSubmit":
          if ($debugMode=="on") {echo "adminPageBasicSubmit  ".$_POST['process']."<br>";}
          adminPageBasicSubmit();
          break;
      case "adminPageAdvanced":
          if ($debugMode=="on") {echo "adminPageAdvanced  ".$_POST['process']."<br>";}
          adminPageAdvanced();
          break;
      case "adminPageAdvancedSubmit":
          if ($debugMode=="on") {echo "adminPageAdvancedSubmit  ".$_POST['process']."<br>";}
          adminPageAdvancedSubmit();
          break;
      case "adminPageAuthors":
          if ($debugMode=="on") {echo "adminPageAuthors  ".$_POST['process']."<br>";}
          adminPageAuthors();
          break;
      case "adminAuthorsAdd":
          if ($debugMode=="on") {echo "adminAuthorsAdd  ".$_POST['process']."<br>";}
          adminAuthorsAdd();
          break;
      case "adminAuthorsEdit":
          if ($debugMode=="on") {echo "adminAuthorsEdit  ".$_POST['process']."<br>";}
          adminAuthorsEdit();
          break;
      case "adminPluginsSubmit":
      case "adminPagePlugins":
          adminPagePlugins();
          break;
      case "adminPageModerate":
      case "adminModerateSubmit":
          adminPageModerate();
          break; 
      case "pluginFunction1":
           pluginFunction1();
           break;
      case "pluginFunction2":
           pluginFunction2();
           break;
      case "pluginFunction3":
           pluginFunction3();
           break;
      case "pluginFunction4":
           pluginFunction4();
           break;
      case "pluginFunction5":
           pluginFunction5();
           break;
      }
  }

?>

