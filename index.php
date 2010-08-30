<?php
/*#######################################################################
	PRITLOG
	Pritlog is a lightweight blog app powered by PHP and Sqlite
	prit@pritlog.com
	PRITLOG now uses the MIT License
	http://www.opensource.org/licenses/mit-license.php
	Version: 0.813
#######################################################################*/
/* Enable all kinds of errors - to eliminate as many warnings and errors as possible */
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

// Example loading an extension based on OS
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
  //echo getcwd().'/themes/'.$config['theme'].'<br>';
  readAuthors();
  $morepriv	= " status = 1 and ";
  //echo "More Priv = ".$morepriv."<br>";
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
  /*
  sqlite_query($config['db'], 'CREATE TABLE posts1 (title CHAR(100), content CHAR(4500), date DATETIME, postid PRIMARY KEY, category CHAR(20), type CHAR(5), stick CHAR(5), allowcomments CHAR(4), visits INTEGER, author CHAR(25), status INTEGER);');
  sqlite_query($config['db'], 'INSERT INTO posts1 SELECT *,1 FROM posts;');
  sqlite_query($config['db'], 'DROP TABLE posts;');
  sqlite_query($config['db'], 'CREATE TABLE posts (title CHAR(100), content CHAR(4500), date DATETIME, postid PRIMARY KEY, category CHAR(20), type CHAR(5), stick CHAR(5), allowcomments CHAR(4), visits INTEGER, author CHAR(25), status INTEGER);');
  sqlite_query($config['db'], 'INSERT INTO posts SELECT * FROM posts1;');
  sqlite_query($config['db'], 'DROP TABLE posts1');
  */
  $ip = getRealIpAddr();
  //echo $ip.'<br>';
  //phpinfo();
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
  //$data = explode("/",$path1);
  //echo $blogPath.'  '.$fullpath.'  '.$remaining.'<br>';
  $data = explode("/",$remaining);
  if ($config['cleanUrl'] == 0) $optionIndex = 2;
  else $optionIndex = 1;	
  $baseScript=basename($scriptName);
  //echo $blogPath.'1<br>';
  //echo "http://" . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'].'<br>';
  
  //echo $remaining.'<br>';
  $i=0;
  //$optionIndex=1;
  //if (is_array($data)) {
  //    foreach ($data as $value) {
  //        if (strcmp($value,$baseScript) == 0) {
  //          $optionIndex=$i+1;
  //        }
  //        $i++;
  //    }
  //}

  $option = isset($data[$optionIndex])?$data[$optionIndex]:"mainPage";
  @$option = (trim($data[$optionIndex]) == "")?"mainPage":$data[$optionIndex];
  $option = str_replace("index.php","",$option); // PritlogMU
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
  //echo $inactive.'<br/>';
  if ($inactive != 0) {
      $_SESSION['notice'] = "";
	  if( isset($_SESSION['timeout'])) {
		   $session_life  = time() - $_SESSION['timeout'];
		   if ($session_life > $inactive && isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
			  /* session_unset();
			   session_destroy();
			   header("Location: ".$blogPath); */
			   $_SESSION['notice']=$lang['loggedOut'];
			   $option="logoutPage";
		   }
	  }
	  //echo "Logging out ..<br>";
	  $_SESSION['timeout'] = time();
  }
  if (isset($_SESSION['start'])) $_SESSION['start']   = false;
  else $_SESSION['start']   = true;
   $mypath  = isset($_SERVER['PATH_INFO'])?str_replace("/index.php","",$_SERVER['PATH_INFO']):"";
	if (isset($_SESSION['growlmsg'])) {$growlmsg = '$.jGrowl("'.$_SESSION['growlmsg'].'");'; unset($_SESSION['growlmsg']);}
	else $growlmsg = '';
   //$referrer=$blogPath.'/index.php'.$mypath;
   $referrer=$serverName.$_SERVER['REQUEST_URI'];
   if ($option == "mainPage") { $_SESSION['url']=$referrer; }
   //echo $_SESSION['referrer'].'<br>';
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
//print @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_header["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/header.tpl"));
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
//echo 'var u1   ="'.urlencode($blogPath).'";';
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
print @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_header["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/header.tpl"));
print @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_main["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/main.tpl"));
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

  function getRealIpAddr()
  {
      if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
      {
        $ip=$_SERVER['HTTP_CLIENT_IP'];
      }
      elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
      {
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
      }
      else
      {
        $ip=$_SERVER['REMOTE_ADDR'];
      }
      return $ip;
  }

  function pluginFunction1() {
      global $optionValue, $theme_main, $SHP;
      if ($SHP->hooks_exist('hook-pluginFunction1')) {
          $SHP->execute_hooks('hook-pluginFunction1');
      }
  }

  function pluginFunction2() {
      global $optionValue, $theme_main, $SHP;
      if ($SHP->hooks_exist('hook-pluginFunction2')) {
          $SHP->execute_hooks('hook-pluginFunction2');
      }
  }

  function pluginFunction3() {
      global $optionValue, $theme_main, $SHP;
      if ($SHP->hooks_exist('hook-pluginFunction3')) {
          $SHP->execute_hooks('hook-pluginFunction3');
      }
  }

  function pluginFunction4() {
      global $optionValue, $theme_main, $SHP;
      if ($SHP->hooks_exist('hook-pluginFunction4')) {
          $SHP->execute_hooks('hook-pluginFunction4');
      }
  }

  function pluginFunction5() {
      global $optionValue, $theme_main, $SHP;
      if ($SHP->hooks_exist('hook-pluginFunction5')) {
          $SHP->execute_hooks('hook-pluginFunction5');
      }
  }

  function logout() {
      global $blogPath, $lang, $SHP;
      unset($_SESSION['logged_in']);
      unset($_SESSION['username']);
      unset($_SESSION['isAdmin']);
      unset($_SESSION['access_type']);
      unset($_SESSION['loginError']);
      unset($_SESSION['ss_fprint']);
      if ($SHP->hooks_exist('hook-logout')) {
          $SHP->execute_hooks('hook-logout');
      }
	  $_SESSION['growlmsg'] = 'Logged Out';
      //header('Location: '.$_SESSION['url']);
      header('Location: '.$blogPath);
      die();
      //unset($_SESSION['url']);
  }

  function logoutPage() {
      global $blogPath, $lang, $theme_main;
      $theme_main['content'] = "<h3>".$lang['titleLogoutPage']."</h3>";
      $theme_main['content'].= $lang['loggedOut'].'<br>';
  }

  function loginPage() {
      global $debugMode, $optionValue, $config, $lang, $theme_main, $SHP;
      if ($SHP->hooks_exist('hook-login-replace')) {
          $SHP->execute_hooks('hook-login-replace');
      }
      else {
          $theme_login['header'] = $lang['titleLoginPage'];
          $theme_login['action'] = $config['blogPath'].$config['cleanIndex']."/loginPageSubmit";
          $theme_login['user']   = $lang['pageAuthorsNew'];
          $theme_login['userValidate'] = '<script>';
          $theme_login['userValidate'].= 'var author = new LiveValidation( "author", {onlyOnSubmit: true } );';
          $theme_login['userValidate'].= 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_login['userValidate'].= '</script>';
          $theme_login['password']     = $lang['pageDeletePass'];
          $theme_login['passValidate'] = '<script>';
          $theme_login['passValidate'].= 'var pass = new LiveValidation( "pass", {onlyOnSubmit: true } );';
          $theme_login['passValidate'].= 'pass.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_login['passValidate'].= '</script>';
          $theme_login['submit']       = $lang['pageBasicConfigSubmit'];
          $theme_login['forgotPassLink'] = '<a href="'.$config['blogPath'].$config['cleanIndex'].'/forgotPass">'.$lang['titleForgotPass'].'?</a>';
          $theme_login['register']     = "";
          $theme_login['loc_form']    = '';
          $theme_login['loc_bottom']  = '';
          if ($SHP->hooks_exist('hook-login')) {
              $SHP->execute_hooks('hook-login');
          }
          if ($config['allowRegistration'] == 1) {
              $theme_login['register'].= '<br>Not registered?&nbsp;<a href="'.$config['blogPath'].$config['cleanIndex'].'/registerPage">'.$lang['titleRegisterPageSubmit'].'!</a><br>';
          }
          $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_login["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/login.tpl"));
      }
      if (isset($_SESSION["loginError"])) {
           $theme_main['content'].= '<br>'.$_SESSION["loginError"].'<br>';
           unset($_SESSION["loginError"]);
      }
  }

  function loginPageSubmit() {
      global $debugMode, $optionValue, $config, $lang, $baseScript, $authorsPass, $ss, $authorsActStatus, $ip;
      global $SHP;
      $loginError=false;
      $authorsActStatus['admin'] = 1;
      //echo 'yes - '.$_SESSION['url'].' - '.$_POST['author'].' - '.$_POST['pass'].' - '.$authorsPass[$_POST['author']].' - '.$authorsActStatus[$_POST['author']]; die();
      if ((md5($config['randomString'].$_POST['pass']) === $authorsPass[$_POST['author']]) && ($authorsActStatus[$_POST['author']] == 1)) {
         $ss->Open();
         $_SESSION['logged_in'] = true;
		 $_SESSION['growlmsg'] = 'Logged In';
         $_SESSION['username']  = $_POST['author'];
         @sqlite_query($config['db'], "DELETE FROM logs WHERE ip = '$ip'");
         @sqlite_query($config['db'], "DELETE FROM ipban WHERE ip = '$ip'");
         if ($SHP->hooks_exist('hook-login-valid')) {
             $SHP->execute_hooks('hook-login-valid');
         }
         if ($_POST['author'] == "admin") {
             $_SESSION['isAdmin'] = true;
             $_SESSION['access_type'] = "admin";
         }
         else {
             if ($_SESSION['access_type'] == "admin" || $authorsActStatus[$_POST['author']] == 0) {
                 $_SESSION["loginError"] = $lang['errorUserPassIncorrect'];
				 $_SESSION['growlmsg'] = $_SESSION["loginError"];
                 $loginError=true;
             }
             $_SESSION['access_type'] = "regular";
             $_SESSION["loginError"]  = $lang['errorNotAuthorized'];
			 $_SESSION['growlmsg'] = $_SESSION["loginError"];
             if ($SHP->hooks_exist('hook-login-invalid')) {
                 $SHP->execute_hooks('hook-login-invalid');
             }
         }
         header('Location: '.$_SESSION['url']);
      }
      else {
         $action = "loginerror";
         $date   = date("Y-m-d H:i");
         sqlite_query($config['db'], "INSERT INTO logs (ip, action, date) VALUES ('$ip', '$action', '$date')");
         $result = sqlite_query($config['db'], "select * from logs WHERE ip = '$ip' AND action = 'loginerror'");
         if (sqlite_num_rows($result) > $config['limitLogins']) {
            $result = sqlite_query($config['db'], "select * from ipban WHERE ip = '$ip'");
            if (sqlite_num_rows($result) == 0) sqlite_query($config['db'], "INSERT INTO ipban (ip) VALUES ('$ip')");
         }
         $_SESSION["loginError"] = $lang['errorUserPassIncorrect'];
		 $_SESSION['growlmsg'] = $_SESSION["loginError"];
         header('Location: '.$config['blogPath'].$config['cleanIndex'].'/loginPage');
      }
  }

  function myProfile() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_profile['header'] = $lang['pageMyProfile'];
      if ((isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) && !(isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)) {
          if ($SHP->hooks_exist('hook-myprofile-replace')) {
              $SHP->execute_hooks('hook-myprofile-replace');
          }
          else {
              $theme_profile['action'] = $config['blogPath'].$config['cleanIndex']."/myProfileSubmit";
              $theme_profile['legend'] = $lang['pageMyProfile'];
              $theme_profile['currentPass'] = $lang['pageMyProfileCurrentPass'];
              $theme_profile['currentPassValidate'] = '<script>';
              $theme_profile['currentPassValidate'].= 'var origpass = new LiveValidation( "origpass", {onlyOnSubmit: true } );';
              $theme_profile['currentPassValidate'].= 'origpass.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
              $theme_profile['currentPassValidate'].= '</script>';
              $theme_profile['newPass1'] = $lang['pageBasicConfigNewpass1'];
              $theme_profile['newPass1Validate'] = '<script>';
              $theme_profile['newPass1Validate'].= 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
              $theme_profile['newPass1Validate'].= 'pass1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
              $theme_profile['newPass1Validate'].= '</script>';
              $theme_profile['newPass2'] = $lang['pageBasicConfigNewpass2'];
              $theme_profile['newPass2Validate'].= '<script>';
              $theme_profile['newPass2Validate'].= 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
              $theme_profile['newPass2Validate'].= 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
              $theme_profile['newPass2Validate'].= '</script>';
              $theme_profile['authorEmailLabel']    = $lang['pageAuthorsNewEmail'];
              $theme_profile['authorEmail']         = $authorsEmail[$_SESSION['username']];
              $theme_profile['authorEmailValidate'].= '<script>';
              $theme_profile['authorEmailValidate'].= 'var email = new LiveValidation( "authorEmail", {onlyOnSubmit: true } );';
              $theme_profile['authorEmailValidate'].= 'email.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
              $theme_profile['authorEmailValidate'].= 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
              $theme_profile['authorEmailValidate'].= '</script>';
              $theme_profile['hidden']              = '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
              $theme_profile['loc_form']           = '';
              if ($SHP->hooks_exist('hook-myprofile')) {
                  $SHP->execute_hooks('hook-myprofile');
              }
              $theme_profile['submit']              = $lang['pageAdvancedConfigSubmit'];
              $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_profile["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/myprofile.tpl"));
          }
      }
      else { $theme_main['content'].= $lang['errorInvalidRequest'].'<br>'; }
  }

  function myProfileSubmit() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $authorsActCode, $authorsActStatus, $theme_main;
      global $SHP, $public_data;
      $authorFileName=$config['authorFile'];
      $theme_main['content'] = "";
      $theme_main['content'].= "<h3>".$lang['pageMyProfile']."</h3>";
      $do = 1;
      if ((isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) && !(isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)) {
          $authorEmail=$_POST['authorEmail'];
          $addAuthor  =$_SESSION['username'];
          if (!$SHP->hooks_exist('hook-myprofile-replace')) {
              if (trim($authorEmail) == "") {
                   $theme_main['content'].= $lang['errorAllFields'].'<br>';
                   $do = 0;
              }
              if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($authorEmail))) {
                   $theme_main['content'].= $lang['errorInvalidAdminEmail'].'<br>';
                   $do = 0;
              }
              if ($_POST['newpass1'] != $_POST['newpass2']) {
                   $theme_main['content'].= $lang['errorNewPasswordsMatch']."<br>";
                   $do = 0;
              }
              if (strtolower(trim($addAuthor)) == "admin") {
                   $theme_main['content'].= $lang['errorForbiddenAuthor'].'<br>';
                   $do = 0;
              }
              if (isset($_POST['newpass1']) && trim($_POST['newpass1']) != "" && strlen($_POST['newpass1']) < 5) {
                  $theme_main['content'].= $lang['errorPassLength'].'<br>';
                  $do = 0;
              }
              if (md5($config['randomString'].$_POST['origpass']) !== $authorsPass[$addAuthor]) {
                  $theme_main['content'].= $lang['errorPasswordIncorrect'].'<br>';
                  $do = 0;
              }
          }
          unset($GLOBALS['$public_data']);
          $public_data['do'] = $do;
          if ($SHP->hooks_exist('hook-myprofile-validate')) {
              $SHP->execute_hooks('hook-myprofile-validate', $public_data);
          }
          $do = $public_data['do'];
          if ($do == 1) {
              $fp = fopen($authorFileName, "w");
              fwrite($fp,'<?php /*');
              fwrite($fp,"\n");
              foreach ($authors as $value) {
                   if (strcmp($value,$addAuthor) == 0) {
                        $authorsPassText      = $_POST['newpass1'];
                        if (trim($_POST['newpass1']) != "") { $authorsPass[$value]  = md5($config['randomString'].$authorsPassText); }
                        $authorsEmail[$value] = $authorEmail;
                   }
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value].$separator.$authorsActCode[$value].$separator.$authorsActStatus[$value]."\n";
                   fwrite($fp,$authorLine);
              }
              fwrite($fp,'*/ ?>');
              fclose($fp);
              $theme_main['content'].= '<br>'.$lang['msgConfigSaved'].'.';
              $theme_main['content'].= '<br>'.$lang['msgConfigLoginAgain'].'.';
              if ($SHP->hooks_exist('hook-myprofile-success')) {
                  $SHP->execute_hooks('hook-myprofile-success');
              }
          }
      }
      else { 
          if ($SHP->hooks_exist('hook-myprofile-fail')) {
              $SHP->execute_hooks('hook-myprofile-fail');
          }
          $theme_main['content'].= $lang['errorInvalidRequest'].'<br>';
      }
  }

  function registerPage() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      global $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_register['header'] = "<h3>".$lang['titleRegisterPage']."</h3>";
      if (!isset($_SESSION['logged_in']) && !(isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) && ($config['allowRegistration'] == 1)) {
          if ($SHP->hooks_exist('hook-register-replace')) {
              $SHP->execute_hooks('hook-register-replace');
          }
          else {
              $theme_register['action'] = $config['blogPath'].$config['cleanIndex']."/registerPageSubmit";
              $theme_register['legend'] = $lang['titleRegisterPage'];
              $theme_register['authorLabel'] = $lang['pageAuthorsNew'];
              $authorsList="";
              foreach ($authors as $value) {
                  $authorsList.='"'.$value.'" , ';
              }
              $theme_register['authorValidate'] = '<script>';
              $theme_register['authorValidate'].= 'var author = new LiveValidation( "addAuthor", {onlyOnSubmit: true } );';
              $theme_register['authorValidate'].= 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
              $theme_register['authorValidate'].= 'author.add( Validate.Exclusion, { within: [ '.$authorsList.' ] , failureMessage: "'.$lang['errorDuplicateAuthor'].'"  } );';
              $theme_register['authorValidate'].= '</script>';
              $theme_register['newPass1']       = $lang['pageBasicConfigNewpass1'];
              $theme_register['newPass1Validate'] = '<script>';
              $theme_register['newPass1Validate'].= 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
              $theme_register['newPass1Validate'].= 'pass1.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
              $theme_register['newPass1Validate'].= 'pass1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
              $theme_register['newPass1Validate'].= '</script>';
              $theme_register['newPass2']       = $lang['pageBasicConfigNewpass2'];
              $theme_register['newPass2Validate'] = '<script>';
              $theme_register['newPass2Validate'].= 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
              $theme_register['newPass2Validate'].= 'pass2.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
              $theme_register['newPass2Validate'].= 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
              $theme_register['newPass2Validate'].= '</script>';
              $theme_register['authorEmail'] = $lang['pageAuthorsNewEmail'];
              $theme_register['authorEmailValidate'] = '<script>';
              $theme_register['authorEmailValidate'].= 'var email = new LiveValidation( "authorEmail", {onlyOnSubmit: true } );';
              $theme_register['authorEmailValidate'].= 'email.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
              $theme_register['authorEmailValidate'].= 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
              $theme_register['authorEmailValidate'].= '</script>';
              $theme_register['securityCode'] = "";
              if($config['commentsSecurityCode'] == 1)
      	  {
    	       $code = '';
    	       if($config['onlyNumbersOnCAPTCHA'] == 1)
    	       {
    		   $code = substr(rand(0,999999),1,$config['CAPTCHALength']);
                   }
    	       else
    	       {
    	  	   //$code = strtoupper(substr(crypt(rand(0,999999), $config['randomString']),1,$config['CAPTCHALength']));
    	  	   $code = genRandomString($config['CAPTCHALength']);
    	       }
    	       $theme_register['securityCode'].= '<p><label for="code">'.$lang['pageCommentsCode'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$code.')</font><br>';
    	       $theme_register['securityCode'].= '<input name="code" class="s" type="text" id="code"></p>';
                   $theme_register['securityCode'].= '<input name="originalCode" value="'.$code.'" type="hidden" id="originalCode">';
              }
              $theme_register['loc_form'] = '';
              if ($SHP->hooks_exist('hook-register')) {
                  $SHP->execute_hooks('hook-register');
              }
              $theme_register['submit'] = $lang['titleRegisterPageSubmit'];
              $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_register["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/register.tpl"));
          }
      }
      else { $theme_main['content'].= $lang['errorInvalidRequest'].'<br>'; }
  }

  function registerPageSubmit() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $blogPath, $authorsActCode, $authorsActStatus;
      global $theme_main, $public_data, $SHP;
      $theme_main['content'] = "";
      $authorFileName=$config['authorFile'];
      $theme_main['content'].= "<h3>".$lang['titleRegisterPage']."</h3>";
      $do = 1;
      if (!isset($_SESSION['logged_in']) && !$_SESSION['logged_in'] && ($config['allowRegistration'] == 1)) {
          $authorEmail=$_POST['authorEmail'];
          $addAuthor=strtolower($_POST['addAuthor']);
          if (!$SHP->hooks_exist('hook-register-replace')) {
              if (isset($authorsPass[$addAuthor])) {
                   $theme_main['content'].= $lang['errorDuplicateAuthor'].'<br>';
                   $do = 0;
              }
              if (trim($addAuthor) == "" || trim($authorEmail) == "") {
                   $theme_main['content'].= $lang['errorAllFields'].'<br>';
                   $do = 0;
              }
              if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($authorEmail))) {
                   $theme_main['content'].= $lang['errorInvalidAdminEmail'].'<br>';
                   $do = 0;
              }
              if ($_POST['newpass1'] != $_POST['newpass2']) {
                   $theme_main['content'].= $lang['errorNewPasswordsMatch']."<br>";
                   $do = 0;
              }
              if (strtolower(trim($addAuthor)) == "admin") {
                   $theme_main['content'].= $lang['errorForbiddenAuthor'].'<br>';
                   $do = 0;
              }
              if (strlen($_POST['newpass1']) < 5) {
                  $theme_main['content'].= $lang['errorPassLength'].'<br>';
                  $do = 0;
              }
              if($config['commentsSecurityCode'] == 1)
    	      {
      	          $code         = isset($_POST['code'])?$_POST['code']:"";
       	          $originalCode = isset($_POST['originalCode'])?$_POST['originalCode']:"";
      	          if ($code !== $originalCode)
      	          {
      	   	     $theme_main['content'].= $lang['errorSecurityCode'].'<br>';
      		     $do = 0;
                  }
    	      }
          }
          unset($GLOBALS['$public_data']);
          $public_data['do'] = $do;
          if ($SHP->hooks_exist('hook-register-validate')) {
              $SHP->execute_hooks('hook-register-validate', $public_data);
          }
          $do = $public_data['do'];

          if ($do == 1) {
              $fp = fopen($authorFileName, "w");
              fwrite($fp,'<?php /*');
              fwrite($fp,"\n");
              foreach ($authors as $value) {
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value].$separator.$authorsActCode[$value].$separator.$authorsActStatus[$value]."\n";
                   fwrite($fp,$authorLine);
              }
              $addAuthor=$_POST['addAuthor'];
              $addPass  =md5($config['randomString'].$_POST['newpass1']);
              $addEmail =$_POST['authorEmail'];
              $activ_code = rand(50001,99999);
              $active = "0";
              $authorLine=$addAuthor.$separator.$addPass.$separator.$addEmail.$separator.$activ_code.$separator.$active."\n";
              //echo $authorLine.'<br>';
              $activeLink=$blogPath.'/index.php/activation/'.$addAuthor.'/'.$activ_code;
              $subject = "PRITLOG: ".$lang['titleRegisterPage'];
               $message = $addAuthor.",\n\n"
                      .$lang['msgMail10']."...\n\n"
                      .$lang['pageAuthorsNewEmail'].": ".$addEmail."\n"
                      .$lang['pageAuthorsNew'].": ".$addAuthor."\n"
                      .$lang['pageNewPassword'].": ".$_POST['newpass1']."\n\n"
                      .$lang['titleRegisterActLink']."\n".$activeLink."\n\n"
                      .$lang['titleRegisterAutoMsg']."\n\n";
             // To send HTML mail, the Content-type header must be set
             $headers  = 'MIME-Version: 1.0' . "\r\n";
             $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
             // Additional headers
             //$headers .= 'To: '.$addEmail. "\r\n";
             //$headers .= 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
             $headers = 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
             //echo $authorLine.'<br>';
	     if (mail($addEmail,
                 $subject,
                 $message,
                 $headers)) {
                   fwrite($fp,$authorLine);
                   $theme_main['content'].= '<br>'.$lang['titleRegisterThank'].'.';
                   if ($SHP->hooks_exist('hook-register-success')) {
                       $SHP->execute_hooks('hook-register-success');
                   }
             }
             else {
                 $theme_main['content'].= '<br>'.$lang['msgMail9'].'.<br>';
             }
             fwrite($fp,'*/ ?>');
             fclose($fp);
             if ($config['sendRegistMail'] == 1) {
	         $subject = "PRITLOG: ".$lang['titleRegisterPage'];
    	         $message = $lang['msgMail11']."\n\n"
                 .$lang['pageAuthorsNew'].": ".$addAuthor."\n"
                 .$lang['pageAuthorsNewEmail'].": ".$addEmail."\n"
                 .$lang['msgMail5'].": ".date("d M Y h:i A")."\n\n"
                 .$lang['msgMail6']."\n\n";
                 // To send HTML mail, the Content-type header must be set
                 //$headers  = 'MIME-Version: 1.0' . "\r\n";
                 //$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                 // Additional headers
                 //$headers .= 'To: '.$config['sendMailWithNewCommentMail']. "\r\n";
                 $headers = 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
		 mail($config['sendMailWithNewCommentMail'],
                      $subject,
                      $message,
                      $headers);
             }
          }
          else {
              if ($SHP->hooks_exist('hook-register-fail')) {
                  $SHP->execute_hooks('hook-register-fail');
              }
              $theme_main['content'].= $lang['errorPleaseGoBack'];
          }

       }
       else { $theme_main['content'].= $lang['errorInvalidRequest'].'<br>'; }
  }

  function forgotPass() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      global $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_forgotpass['header'] = $lang['titleForgotPass'];
      if (!isset($_SESSION['logged_in']) && !(isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false)) {
          if ($SHP->hooks_exist('hook-forgotpass-replace')) {
              $SHP->execute_hooks('hook-forgotpass-replace');
          }
          else {
              $theme_forgotpass['action'] = $config['blogPath'].$config['cleanIndex']."/forgotPassSubmit";
              $theme_forgotpass['legend'] = $lang['titleForgotPass'];
              $theme_forgotpass['author'] = $lang['pageAuthorsNew'];
              $authorsList="";
              foreach ($authors as $value) {
                  $authorsList.='"'.$value.'" , ';
              }
              $theme_forgotpass['authorValidate'] = '<script>';
              $theme_forgotpass['authorValidate'].= 'var author = new LiveValidation( "addAuthor", {onlyOnSubmit: true } );';
              $theme_forgotpass['authorValidate'].= 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
              $theme_forgotpass['authorValidate'].= 'author.add( Validate.Inclusion, { within: [ '.$authorsList.' ] , failureMessage: "'.$lang['errorUserNotFound'].'"  } );';
              $theme_forgotpass['authorValidate'].= '</script>';
              if ($SHP->hooks_exist('hook-forgotpass')) {
                  $SHP->execute_hooks('hook-forgotpass');
              }
              $theme_forgotpass['submit'] = $lang['pageBasicConfigSubmit'];
              $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_forgotpass["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/forgotpass.tpl"));
          }
      }
      else { $theme_main['content'].= $lang['errorInvalidRequest'].'<br>'; }
  }

  function createRandomPassword() {
    $chars = "abcdefghijkmnopqrstuvwxyz023456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;
    while ($i <= 7) {
        $num = rand() % 33;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return $pass;
  }

  function forgotPassSubmit() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $blogPath, $authorsActCode, $authorsActStatus;
      global $theme_main, $public_data, $SHP;
      $theme_main['content'] = "";
      $authorFileName=$config['authorFile'];
      $theme_main['content'].= "<h3>".$lang['titleForgotPass']."</h3>";
      $do = 1;
      if (!isset($_SESSION['logged_in']) && !in_array('"'.$_POST['addAuthor'].'"', $authors)) {
          if (!$SHP->hooks_exist('hook-forgotpass-replace')) {
              $addAuthor=$_POST['addAuthor'];
              $addEmail =$authorsEmail[$addAuthor];
              if (trim($addAuthor) == "") {
                   $theme_main['content'].= $lang['errorAllFields'].'<br>';
                   $do = 0;
              }
              if (strtolower(trim($addAuthor)) == "admin") {
                   $theme_main['content'].= $lang['errorForbiddenAuthor'].'<br>';
                   $do = 0;
              }
          }
          unset($GLOBALS['$public_data']);
          $public_data['do'] = $do;
          if ($SHP->hooks_exist('hook-forgotpass-validate')) {
              $SHP->execute_hooks('hook-forgotpass-validate', $public_data);
          }
          $do = $public_data['do'];

          if ($do == 1) {
              $fp = fopen($authorFileName, "w");
              fwrite($fp,'<?php /*');
              fwrite($fp,"\n");
              foreach ($authors as $value) {
                   $authorsDelete = false;
                   if (strcmp($value,$addAuthor) == 0) {
                        $authorsPassText      = createRandomPassword();
                        $authorsPass[$value]  = md5($config['randomString'].$authorsPassText);
                   }
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value].$separator.$authorsActCode[$value].$separator.$authorsActStatus[$value]."\n";
                   fwrite($fp,$authorLine);
              }
              fwrite($fp,'*/ ?>');
              fclose($fp);
              $subject = "PRITLOG: ".$lang['titleForgotPassSub'];
	      $message = $addAuthor.",\n\n"
                      .$lang['msgMail10']."...\n\n"
                      .$lang['pageAuthorsNewEmail'].": ".$addEmail."\n"
                      .$lang['pageAuthorsNew'].": ".$addAuthor."\n"
                      .$lang['pageNewPassword'].": ".$authorsPassText."\n\n"
                      .$lang['titleRegisterAutoMsg']."\n\n";
             // To send HTML mail, the Content-type header must be set
             $headers  = 'MIME-Version: 1.0' . "\r\n";
             $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
             // Additional headers
             //$headers .= 'To: '.$addEmail. "\r\n";
             $headers = 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
             if (mail($addEmail,
                 $subject,
                 $message,
                 $headers)) {
                   $theme_main['content'].= '<br>'.$lang['titleForgotPassMsg'];
             }
             else {
                 $theme_main['content'].= '<br>'.$lang['msgMail9'].'.<br>';
             }
             if ($SHP->hooks_exist('hook-forgotpass-success')) {
                 $SHP->execute_hooks('hook-forgotpass-success');
             }
          }
          else {
              $theme_main['content'].= $lang['errorPleaseGoBack'];
              if ($SHP->hooks_exist('hook-forgotpass-fail')) {
                  $SHP->execute_hooks('hook-forgotpass-fail');
              }
          }
       }
       else {
          echo $lang['errorInvalidRequest']."<br>";
       }
  }


  function activation() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $serverName, $authorsActCode, $authorsActStatus;
      global $optionValue, $optionValue2, $theme_main, $SHP;
      $authorFileName=$config['authorFile'];
      $author  = trim($optionValue);
      $actCode = trim($optionValue2);
      $theme_main['content'] = "";
      $theme_main['content'].= "<h3>".$lang['titleRegisterPage']."</h3>";
      $do = 1;
      if (!isset($_SESSION['logged_in']) && !$_SESSION['logged_in'] && ($config['allowRegistration'] == 1)) {
          if ($authorsActCode[$author] == $actCode) {
              if ($authorsActStatus[$author] == 0) {
                  $authorsActStatus[$author] = 1;
                  $fp = fopen($authorFileName, "w");
                  fwrite($fp,'<?php /*');
                  fwrite($fp,"\n");
                  foreach ($authors as $value) {
                       $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value].$separator.$authorsActCode[$value].$separator.$authorsActStatus[$value]."\n";
                       fwrite($fp,$authorLine);
                  }
                  fwrite($fp,'*/ ?>');
                  fclose($fp);
                  $theme_main['content'].= $lang['titleRegisterActive'].'<br>';
                  if ($SHP->hooks_exist('hook-activation-success')) {
                      $SHP->execute_hooks('hook-activation-success');
                  }
              }
              else { $theme_main['content'].= $lang['titleRegisterAlready'].'<br>'; }
          }
          else {
             $theme_main['content'].= $lang['errorInvalidRequest'].'<br>';
             if ($SHP->hooks_exist('hook-activation-fail')) {
                 $SHP->execute_hooks('hook-activation-fail');
             }
          }

      }
      else { $theme_main['content'].= $lang['errorInvalidRequest'].'<br>'; }
  }

  function adminPage() {
      global $debugMode, $optionValue, $config, $lang, $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_admin['header'] = $lang['titleAdminPage'];

      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $theme_admin['tabs'].= adminPageTabs();  
		  $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_admin["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/admin.tpl"));
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br>';
       }
  }

  function adminPageBasic() {
      global $debugMode, $optionValue, $config, $lang, $theme_main, $theme_adminbasic;
      $theme_main['content'] = "";
      $theme_adminbasic['header'] = $lang['pageBasicConfig'];
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $theme_adminbasic['tabs'] = adminPageTabs();
		  $msgtext    = "";
		  $msgclass   = "hide";
		  if (isset($_POST['submitted'])) {
			  $submit_result = adminPageBasicSubmit();
			  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  header('Location: '.$config['blogPath'].$config['cleanIndex'].'/adminPageBasic');
			  die();
			  /*
			  if ($submit_result === true) {
				  $msgtext = $lang['msgConfigSaved'];
				  $msgclass= "success";
			  }
			  else {
				  $msgtext = $submit_result;
				  $msgclass= "error"; 
			  }
			  */
		  }		
		  $theme_adminbasic['msgtext']  = $msgtext;
		  $theme_adminbasic['msgclass'] = $msgclass;
		  $theme_adminbasic['action'] = $config['blogPath'].$config['cleanIndex']."/adminPageBasic";
          $theme_adminbasic['legend'] = $lang['pageBasicConfig'];
          $theme_adminbasic['titleLabel'] = $lang['pageBasicConfigTitle'];
          $theme_adminbasic['title'] = $config['blogTitle'];
          $theme_adminbasic['titleValidate'] = '<script>';
          $theme_adminbasic['titleValidate'].= 'var title = new LiveValidation( "title", {onlyOnSubmit: true } );';
          $theme_adminbasic['titleValidate'].= 'title.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_adminbasic['titleValidate'].= '</script>';
          $theme_adminbasic['pass1']         = $lang['pageBasicConfigNewpass1'];
          $theme_adminbasic['pass1Validate'].= '<script>';
          $theme_adminbasic['pass1Validate'].= 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
          $theme_adminbasic['pass1Validate'].= '</script>';
          $theme_adminbasic['pass2'] = $lang['pageBasicConfigNewpass2'];
          $theme_adminbasic['pass2Validate'] = '<script>';
          $theme_adminbasic['pass2Validate'].= 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
          $theme_adminbasic['pass2Validate'].= 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
          $theme_adminbasic['pass2Validate'].= '</script>';
          $theme_adminbasic['emailLabel'] = $lang['pageBasicConfigAdminEmail'];
          $theme_adminbasic['email'] = $config['sendMailWithNewCommentMail'];
          $theme_adminbasic['emailValidate'] = '<script>';
          $theme_adminbasic['emailValidate'].= 'var email = new LiveValidation( "adminEmail", {onlyOnSubmit: true } );';
          $theme_adminbasic['emailValidate'].= 'email.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_adminbasic['emailValidate'].= 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
          $theme_adminbasic['emailValidate'].= '</script>';
          $theme_adminbasic['aboutLabel'] = $lang['pageBasicConfigAbout'];
          $theme_adminbasic['about'] = $config['about'];
          $theme_adminbasic['submit'] = $lang['pageBasicConfigSubmit'];
          $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_adminbasic["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/adminbasic.tpl"));
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }


  function adminPageBasicSubmit() {
       global $config, $lang, $theme_main;
	   $msgtext = "";
      $do=1;
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          if (trim($_POST['title']) == "" || trim($_POST['adminEmail']) == "" || trim($_POST['posts']) == "") {
              $msgtext .= $lang['errorCannotBeSpaces'].'<br>';
              $msgtext.= '<li>'.$lang['pageBasicConfigTitle'].'</li>';
              $msgtext.= '<li>'.$lang['pageBasicConfigAdminEmail'].'</li>';
              $msgtext.= '<li>'.$lang['pageBasicConfigAbout'].'</li>';
              $do=0;
          }
          if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($_POST['adminEmail']))) {
              $msgtext.= '<br>'.$lang['errorInvalidAdminEmail'].'<br>';
              $do=0;
          }
          if (trim($_POST['newpass1'])!="" || trim($_POST['newpass2'])!="") {
              if (strcmp($_POST['newpass1'],$_POST['newpass2']) != 0) {
                  $msgtext.= '<br>'.$lang['errorNewPasswordsMatch'].'<br>';
                  $do=0;
              }
              else {
                  $config['Password']=md5($config['randomString'].$_POST['newpass1']);
              }
          }
          if ($do == 0) {
			  $_SESSION['growlmsg'] = $msgtext;
			  return $msgtext;
          }
          else {
              $config['blogTitle']=trim(str_replace("\\","",$_POST['title']));
              $config['sendMailWithNewCommentMail']=trim($_POST['adminEmail']);
              $config['about']=str_replace("\n","<br/>",str_replace("\\","",trim($_POST['posts'])));
              writeConfig(false);
			  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  return true;
          }
       }
       else {
          $msgtext.= $lang['errorPasswordIncorrect'].' .. <br/>';
		  return $msgtext;
       }
  }

  function adminPageAdvanced() {
      global $debugMode, $optionValue, $config, $lang, $theme_main;
      $theme_adminadvanced['header'] = $lang['pageAdvancedConfig'];
      $theme_main['content'] = "";

      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $theme_adminadvanced['tabs'] = adminPageTabs();
          
		  $msgtext    = "";
		  $msgclass   = "hide";
		  if (isset($_POST['submitted'])) {
			  $submit_result = adminPageAdvancedSubmit();
			  if ($submit_result === true) {
				  //$msgtext = $lang['msgConfigSaved'];
				  //$msgclass= "success";
				  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  }
			  else {
				  //$msgtext = $submit_result;
				  //$msgclass= "error"; 
				  $_SESSION['growlmsg'] = $submit_result;
			  }

			  header('Location: '.$config['blogPath'].$config['cleanIndex'].'/adminPageAdvanced');
			  die();
		  }		
		  $theme_adminadvanced['msgtext']  = $msgtext;
		  $theme_adminadvanced['msgclass'] = $msgclass;
		  
		  $theme_adminadvanced['action'] = $config['blogPath'].$config['cleanIndex']."/adminPageAdvanced";
          $theme_adminadvanced['legend'] = $lang['pageAdvancedConfig'];
          $theme_adminadvanced['metaDescLabel'] = $lang['pageAdvancedConfigMetaDesc'];
          $theme_adminadvanced['metaDesc'] = $config['metaDescription'];
          $theme_adminadvanced['metaKeywordsLabel'] = $lang['pageAdvancedConfigMetaKey'];
          $theme_adminadvanced['metaKeywords'] = $config['metaKeywords'];

          $theme_adminadvanced['commentsMaxLengthLabel'] = $lang['pageAdvancedConfigCommentsLen'];
          $theme_adminadvanced['commentsMaxLength'] = $config['commentsMaxLength'];
          $theme_adminadvanced['commentsForbiddenAuthorsLabel'] = $lang['pageAdvancedConfigCommentsFor'];
          $theme_adminadvanced['commentsForbiddenAuthors'] = $config['commentsForbiddenAuthors'];
          $theme_adminadvanced['statsDontLogLabel'] = $lang['pageAdvancedConfigDontLog'];
          $theme_adminadvanced['statsDontLog'] = $config['statsDontLog'];
          $theme_adminadvanced['entriesOnRSSLabel'] = $lang['pageAdvancedConfigEntriesRSS'];
          $theme_adminadvanced['entriesOnRSS'] = $config['entriesOnRSS'];
          $theme_adminadvanced['entriesPerPageLabel'] = $lang['pageAdvancedConfigPostsperPage'];
          $theme_adminadvanced['entriesPerPage'] = $config['entriesPerPage'];
          $theme_adminadvanced['menuEntriesLimitLabel'] = $lang['pageAdvancedConfigMenuEntries'];
          $theme_adminadvanced['menuEntriesLimit'] = $config['menuEntriesLimit'];
          $theme_adminadvanced['timeoutDurationLabel'] = $lang['timeoutDuration'];
          $theme_adminadvanced['timeoutDuration'] = $config['timeoutDuration'];
          $theme_adminadvanced['limitLoginsLabel'] = $lang['limitLogins'];
          $theme_adminadvanced['limitLogins'] = $config['limitLogins'];

          $theme_adminadvanced['language'] = '<p><label for="blogLanguage">'.$lang['pageAdvancedConfigLanguage'].'</label><br>';
          $theme_adminadvanced['language'].= '<select name="blogLanguage" id="blogLanguage">';
          $languageDir=getcwd()."/lang";
          if (file_exists($languageDir)) {
              if ($handle = opendir($languageDir)) {
                  $file_array_unsorted = array();
                  while (false !== ($file = readdir($handle))) {
                      if (substr($file,strlen($file)-4) == ".php") {
                          $language=substr($file,0,strlen($file)-4);
                          //echo substr($file,0,strlen($file)-4).'<br>';
                          ($config['blogLanguage'] == $language)?$selected="selected":$selected="";
                          $theme_adminadvanced['language'] .= '<option value="'.$language.'" '.$selected.' >'.$language;
                      }
                  }
                  closedir($handle);
              }
          }

          $theme_adminadvanced['language'] .= '</select>';

          $theme_adminadvanced['theme'] = '<p><label for="theme">'.$lang['pageAdvancedConfigTheme'].'</label><br>';
          $theme_adminadvanced['theme'].= '<select name="theme" id="theme">';
          $themeDir=getcwd()."/themes";
          if (file_exists($themeDir)) {
              if ($handle = opendir($themeDir)) {
                  $file_array_unsorted = array();
                  while (false !== ($file = readdir($handle))) {
                      $fullname = $themeDir."/".$file;
                      if (is_dir($fullname) && $file != ".." && $file != ".") {
                          $theme=$file;
                          ($config['theme'] == $theme)?$selected="selected":$selected="";
                          $theme_adminadvanced['theme'] .= '<option value="'.$theme.'" '.$selected.' >'.$theme;
                      }
                  }
                  closedir($handle);
              }
          }

          $theme_adminadvanced['theme'] .= '</select>';

          $theme_adminadvanced['privacy'] = '<p><label for="privacy">'.$lang['pageAdvancedConfigPrivacy'].'</label><br>';
          $theme_adminadvanced['privacy'].= '<select name="privacy" id="privacy">';
		  //$config['privacy'] = 2;
		  $privacyArray = array(0, 1, 2);
		  if (is_array($privacyArray)) {
              foreach ($privacyArray as $value) {
                  $privacy=$value;
				  ($config['privacy'] == $privacy)?$selected="selected":$selected="";
				  switch ($privacy) {
					case 0: 
						$privacyText = "0 - Only the author can read the posts";
						break;
					case 1:
						$privacyText = "1 - All registered authors can read posts";
						break;
					case 2:
						$privacyText = "2 - Everybody can read all posts";
						break;
				  } 
				  $theme_adminadvanced['privacy'] .= '<option value="'.$privacy.'" '.$selected.' >'.$privacyText;
              }
          }		  
          $theme_adminadvanced['privacy'] .= '</select>';		 
		  
          $menuLinks="";
          if (is_array($config['menuLinksArray'])) {
              foreach ($config['menuLinksArray'] as $value) {
                  $menuLinks=$menuLinks."\n".$value;
              }
          }
          $theme_adminadvanced['menuLinksLabel'] = $lang['pageAdvancedConfigMenuLinks'];
          $theme_adminadvanced['menuLinks'] = $menuLinks;
          
          $ipBan="";
          $ipsep="";
          $result = sqlite_query($config['db'], "select * from ipban");
          while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
             $ipBan .= $ipsep.$row['ip'];
             $ipsep="\n";
          }
          $theme_adminadvanced['ipBanLabel'] = $lang['pageAdvancedConfigIpBan'];
          $theme_adminadvanced['ipBan'] = $ipBan;

          if ($config['sendMailWithNewComment'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['sendMailComments'] = '<input type="checkbox" name="sendMailComments" value="1" '.$checking.'>'.$lang['pageAdvancedConfigSendMail'].'</a><br>';
          if ($config['commentsSecurityCode'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['commentsSecurityCode'] = '<input type="checkbox" name="commentsSecurityCode" value="1" '.$checking.'>'.$lang['pageAdvancedConfigSecCode'].'</a><br>';
          if ($config['authorEditPost'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['authorEditPost'] = '<input type="checkbox" name="authorEditPost" value="1" '.$checking.'>'.$lang['pageAdvancedConfigAuthorEdit'].'</a><br>';
          if ($config['authorDeleteComment'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['authorDeleteComment'] = '<input type="checkbox" name="authorDeleteComment" value="1" '.$checking.'>'.$lang['pageAdvancedConfigAuthorComment'].'</a><br>';
          if ($config['showCategoryCloud'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['showCategoryCloud'] = '<input type="checkbox" name="showCategoryCloud" value="1" '.$checking.'>'.$lang['pageAdvancedConfigCatCloud'].'</a><br>';
          if ($config['allowRegistration'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['allowRegistration'] = '<input type="checkbox" name="allowRegistration" value="1" '.$checking.'>'.$lang['pageAdvancedConfigRegister'].'</a><br>';
          if ($config['sendRegistMail'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['sendRegistMail'] = '<input type="checkbox" name="sendRegistMail" value="1" '.$checking.'>'.$lang['pageAdvancedConfigRegistMail'].'</a><br>';

          if ($config['cleanUrl'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['cleanUrl'] = '<input type="checkbox" name="cleanUrl" value="1" '.$checking.'>'.$lang['pageAdvancedConfigCleanUrl'].'</a><br>';

          if ($config['commentModerate'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          $theme_adminadvanced['commentModerate'] = '<input type="checkbox" name="commentModerate" value="1" '.$checking.'>'.$lang['pageAdvancedCommentModerate'].'</a></p>';
          $theme_adminadvanced['hidden'] = '<input name="process" type="hidden" id="process" value="adminPageAdvancedSubmit">';
          $theme_adminadvanced['submit'] = $lang['pageAdvancedConfigSubmit'];
          
		  $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_adminadvanced["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/adminadvanced.tpl"));
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br>';
       }
  }



  function adminPageAdvancedSubmit() {
       global $config, $lang, $theme_main;
      $do=1;
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          if (isset($_POST['metaDesc']) && trim($_POST['metaDesc']) != "") {
               $config['metaDescription'] = $_POST['metaDesc'];
          }
          if (isset($_POST['metaKeywords']) && trim($_POST['metaKeywords']) != "") {
               $config['metaKeywords'] = $_POST['metaKeywords'];
          }
          if (isset($_POST['commentsMaxLength']) && trim($_POST['commentsMaxLength']) != "") {
               $config['commentsMaxLength'] = $_POST['commentsMaxLength'];
          }
          if (isset($_POST['commentsForbiddenAuthors']) && trim($_POST['commentsForbiddenAuthors']) != "") {
               $config['commentsForbiddenAuthors'] = $_POST['commentsForbiddenAuthors'];
          }
          if (isset($_POST['statsDontLog']) && trim($_POST['statsDontLog']) != "") {
               $config['statsDontLog'] = $_POST['statsDontLog'];
          }
          if (isset($_POST['entriesOnRSS']) && trim($_POST['entriesOnRSS']) != "") {
               $config['entriesOnRSS'] = $_POST['entriesOnRSS'];
          }
          if (isset($_POST['entriesPerPage']) && trim($_POST['entriesPerPage']) != "") {
               $config['entriesPerPage'] = $_POST['entriesPerPage'];
          }
          if (isset($_POST['menuEntriesLimit']) && trim($_POST['menuEntriesLimit']) != "") {
               $config['menuEntriesLimit'] = $_POST['menuEntriesLimit'];
          }
          if (isset($_POST['timeoutDuration']) && trim($_POST['timeoutDuration']) != "") {
               $config['timeoutDuration'] = $_POST['timeoutDuration'];
          }
          if (isset($_POST['limitLogins']) && trim($_POST['limitLogins']) != "") {
               $config['limitLogins'] = $_POST['limitLogins'];
          }

          if ($_POST['sendMailComments'] == 1) { $config['sendMailWithNewComment'] = 1; }
          else { $config['sendMailWithNewComment'] = 0; }

          if ($_POST['commentsSecurityCode'] == 1) { $config['commentsSecurityCode'] = 1; }
          else { $config['commentsSecurityCode'] = 0; }

          if ($_POST['authorEditPost'] == 1) { $config['authorEditPost'] = 1; }
          else { $config['authorEditPost'] = 0; }

          if ($_POST['authorDeleteComment'] == 1) { $config['authorDeleteComment'] = 1; }
          else { $config['authorDeleteComment'] = 0; }

          if ($_POST['showCategoryCloud'] == 1) { $config['showCategoryCloud'] = 1; }
          else { $config['showCategoryCloud'] = 0; }

          if ($_POST['allowRegistration'] == 1) { $config['allowRegistration'] = 1; }
          else { $config['allowRegistration'] = 0; }

          if ($_POST['sendRegistMail'] == 1) { $config['sendRegistMail'] = 1; }
          else { $config['sendRegistMail'] = 0; }

          if ($_POST['commentModerate'] == 1) { $config['commentModerate'] = 1; }
          else { $config['commentModerate'] = 0; }
          
		  if ($_POST['cleanUrl'] == 1) { $config['cleanUrl'] = 1; }
          else { $config['cleanUrl'] = 0; }

          if (isset($_POST['menuLinks'])) {
               $config['menuLinks']=$config['menuLinksOrig']=str_replace("\r\n",";",$_POST['menuLinks']);
          }
          
          if (isset($_POST['ipBan'])) {
               $ipBanArray=explode("\r\n", $_POST['ipBan']);
               @sqlite_query($config['db'], "DELETE FROM ipban");
               foreach ($ipBanArray as $ipBan) {
                  $ipBan = trim($ipBan);
                  sqlite_query($config['db'], "INSERT INTO ipban (ip) VALUES ('$ipBan')");
               }
          }

          if (isset($_POST['blogLanguage'])) {
               $config['blogLanguage']=$_POST['blogLanguage'];
          }

          if (isset($_POST['theme'])) {
               $config['theme']=$_POST['theme'];
          }

          if (isset($_POST['privacy'])) {
               $config['privacy']=$_POST['privacy'];
          }

          setConfigDefaults();
          writeConfig(false);
		  return true;
       }
       else {
		  return $lang['errorPasswordIncorrect'];
       }
  }

  function adminPageAuthors() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      global $theme_main;
      $theme_main['content'] = "";
      $theme_authoradd['header'] = $lang['pageAuthorsManage'];
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
		  
		  $theme_authoradd['tabs']	 = adminPageTabs();
		  
		  $msgtext    = "";
		  $msgclass   = "hide";
		  if (isset($_POST['authoradd'])) {
			  $submit_result = adminAuthorsAdd();
			  if ($submit_result === true) {
				  //$msgtext = $lang['msgConfigSaved'];
				  //$msgclass= "success";
				  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  }
			  else {
				  //$msgtext = $submit_result;
				  //$msgclass= "error"; 
				  $_SESSION['growlmsg'] = $submit_result;
			  }
			  header('Location: '.$config['blogPath'].$config['cleanIndex'].'/adminPageAuthors');
			  die();
		  }	
		  else if (isset($_POST['authoredit'])) {	
		      $submit_result = adminAuthorsEdit();
			  if ($submit_result === true) {
				  //$msgtext = $lang['msgConfigSaved'];
				  //$msgclass= "success";
				  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  }
			  else {
				  //$msgtext = $submit_result;
				  //$msgclass= "error"; 
				  $_SESSION['growlmsg'] = $submit_result;
			  }
			  
			  header('Location: '.$config['blogPath'].$config['cleanIndex'].'/adminPageAuthors');
			  die();
		  }
		  $theme_authoradd['msgtext']  = $msgtext;
		  $theme_authoradd['msgclass'] = $msgclass;
		  
		  $theme_authoradd['action'] = $config['blogPath'].$config['cleanIndex']."/adminPageAuthors";
          $theme_authoradd['legend'] = $lang['pageAuthorsAdd'];
          $theme_authoradd['author'] = $lang['pageAuthorsNew'];
          $authorsList="";
          foreach ($authors as $value) {
              $authorsList.='"'.$value.'" , ';
          }
          $authorsList.='"admin"';
          $theme_authoradd['authorValidate'] = '<script>';
          $theme_authoradd['authorValidate'].= 'var author = new LiveValidation( "addAuthor", {onlyOnSubmit: true } );';
          $theme_authoradd['authorValidate'].= 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_authoradd['authorValidate'].= 'author.add( Validate.Exclusion, { within: [ '.$authorsList.' ] , failureMessage: "'.$lang['errorDuplicateAuthor'].'"  } );';
          $theme_authoradd['authorValidate'].= '</script>';
          $theme_authoradd['pass1'] = $lang['pageBasicConfigNewpass1'];
          $theme_authoradd['pass1Validate'] = '<script>';
          $theme_authoradd['pass1Validate'].= 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
          $theme_authoradd['pass1Validate'].= 'pass1.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_authoradd['pass1Validate'].= 'pass1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
          $theme_authoradd['pass1Validate'].= '</script>';
          $theme_authoradd['pass2'] = $lang['pageBasicConfigNewpass2'];
          $theme_authoradd['pass2Validate'] = '<script>';
          $theme_authoradd['pass2Validate'].= 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
          $theme_authoradd['pass2Validate'].= 'pass2.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_authoradd['pass2Validate'].= 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
          $theme_authoradd['pass2Validate'].= '</script>';
          $theme_authoradd['email'] = $lang['pageAuthorsNewEmail'];
          $theme_authoradd['emailValidate'] = '<script>';
          $theme_authoradd['emailValidate'].= 'var email = new LiveValidation( "authorEmail", {onlyOnSubmit: true } );';
          $theme_authoradd['emailValidate'].= 'email.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          $theme_authoradd['emailValidate'].= 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
          $theme_authoradd['emailValidate'].= '</script>';
          $theme_authoradd['submit'] = $lang['pageAuthorsAdd'];
		  
		  $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_authoradd["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/authoradd.tpl"));
	       
		  readAuthors(); 	
		  
          if (is_array($authors)) {
              $i = 0;
              foreach ($authors as $value) {
                  $theme_authoredit['action'] = $config['blogPath'].$config['cleanIndex']."/adminPageAuthors";
                  $theme_authoredit['legend'] = $lang['pageAuthorsManage'];
                  $theme_authoredit['author'] = $value;
                  $theme_authoredit['emailLabel'] = $lang['pageAuthorsNewEmail'];
                  $theme_authoredit['email'] = $authorsEmail[$value];
                  $theme_authoredit['pass1Label'] = $lang['pageBasicConfigNewpass1'];
                  $theme_authoredit['pass1']      = 'newpass'.$i.'1';
                  $theme_authoredit['pass1Validate'] = '<script>';
                  $theme_authoredit['pass1Validate'].= 'var pass'.$i.'1 = new LiveValidation( "newpass'.$i.'1", {onlyOnSubmit: true } );';
                  $theme_authoredit['pass1Validate'].= 'pass'.$i.'1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
                  $theme_authoredit['pass1Validate'].= '</script>';
                  $theme_authoredit['pass2Label'] = $lang['pageBasicConfigNewpass2'];
                  $theme_authoredit['pass2']      = 'newpass'.$i.'2';
                  $theme_authoredit['pass2Validate'] = '<script>';
                  $theme_authoredit['pass2Validate'].= 'var pass'.$i.'2 = new LiveValidation( "newpass'.$i.'2", {onlyOnSubmit: true } );';
                  $theme_authoredit['pass2Validate'].= 'pass'.$i.'2.add( Validate.Confirmation,{ match: "newpass'.$i.'1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
                  $theme_authoredit['pass2Validate'].= '</script>';
                  $theme_authoredit['submit'] = $lang['postFtEdit'];
                  $theme_authoredit['delete'] = $lang['pageAuthorsDelete'];
                  $theme_authoredit['authornum'] = $i;
                  $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_authoredit["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/authoredit.tpl"));
                  $i++;
              }
          }
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }

  function adminAuthorsAdd() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $authorsActCode, $authorsActStatus;
      global $theme_main;
      $authorFileName=$config['authorFile'];
	  $do = 1;
	  $msgtext = "";
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $addAuthor=strtolower($_POST['addAuthor']);
          $authorEmail=$_POST['authorEmail'];
          if (isset($authorsPass[$addAuthor])) {
               $msgtext.= $lang['errorDuplicateAuthor'].'<br>';
               $do = 0;
          }
          if (trim($addAuthor) == "" || trim($authorEmail) == "") {
               $msgtext.= $lang['errorAllFields'].'<br>';
               $do = 0;
          }
          if ($_POST['newpass1'] != $_POST['newpass2']) {
               $msgtext.= $lang['errorNewPasswordsMatch']."<br>";
               $do = 0;
          }
          if (strtolower(trim($addAuthor)) == "admin") {
               $msgtext.= $lang['errorForbiddenAuthor'].'<br>';
               $do = 0;
          }
          if (strlen($_POST['newpass1']) < 5) {
              $msgtext.= $lang['errorPassLength'].'<br>';
              $do = 0;
          }
          if ($do == 1) {
              $fp = fopen($authorFileName, "w");
              fwrite($fp,'<?php /*');
              fwrite($fp,"\n");
              foreach ($authors as $value) {
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value].$separator.$authorsActCode[$value].$separator.$authorsActStatus[$value]."\n";
                   fwrite($fp,$authorLine);
              }
              $addAuthor=strtolower($_POST['addAuthor']);
              $addPass  =md5($config['randomString'].$_POST['newpass1']);
              $addEmail =$_POST['authorEmail'];
              $authorLine=$addAuthor.$separator.$addPass.$separator.$addEmail.$separator."11111".$separator."1"."\n";
              fwrite($fp,$authorLine);
              fwrite($fp,'*/ ?>');

              fclose($fp);
			  return true;
          }
          else {
			  return $msgtext;
          }

       }
       else {
		  return $lang['errorPasswordIncorrect'];
       }
  }


  function adminAuthorsEdit() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $authorsPass, $separator, $authorsActCode, $authorsActStatus;
      global $theme_main;
      $authorFileName=$config['authorFile'];
	  $do = 1;
	  $msgtext = "";
      $deleteAuthor = (isset($_POST['deleteAuthor']))?$_POST['deleteAuthor']:0;
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $editAuthor=$_POST['author'];
          if ($deleteAuthor != 1) {
              $newpass1 = 'newpass'.$_POST['authornum'].'1';
              $newpass2 = 'newpass'.$_POST['authornum'].'2';
              if ($_POST[$newpass1] != $_POST[$newpass2]) {
                   $msgtext.= $lang['errorNewPasswordsMatch']."<br>";
                   $do = 0;
              }
              if (strlen($_POST[$newpass1]) < 5) {
                  $msgtext.= $lang['errorPassLength'].'<br>';
                  $do = 0;
              }
          }
          if ($do == 1) {
              $fp = fopen($authorFileName, "w");
              fwrite($fp,'<?php /*');
              fwrite($fp,"\n");
              foreach ($authors as $value) {
                   $authorsDelete = false;
                   if (strcmp($value,$editAuthor) == 0) {
                        $authorsPass[$value]  = md5($config['randomString'].$_POST[$newpass1]);
                        $authorsEmail[$value] = $_POST['authorEmail'];
                        if ($deleteAuthor == 1) {
                            $authorsDelete = true;
                        }
                   }
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value].$separator.$authorsActCode[$value].$separator.$authorsActStatus[$value]."\n";
                   if (!$authorsDelete) {
                      fwrite($fp,$authorLine);
                   }
              }
              fwrite($fp,'*/ ?>');
              fclose($fp);
			  return true;
          }
          else {
			  return $msgtext;
          }

       }
       else {
		  return $lang['errorPasswordIncorrect'];
       }
  }

  
  function plugin_cleanup() {
      global $SHP, $config;
      $result = sqlite_query($config['db'], "select id from plugins;");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $pluginid  = $row['id'];
          $plugin_id = $pluginid.'.plugin.php';
          if (!isset($SHP->plugins[$plugin_id]['file']) && ($SHP->plugins[$plugin_id]['file'] !== $plugin_id)) {
              @sqlite_query($config['db'], "DELETE FROM plugins WHERE id = '$pluginid'");
          }
      }
  }

  function list_plugins($plugin_folder = './plugins/') {
        global $SHP, $theme_main, $config;
        if ($handle = @opendir($plugin_folder)) {
		while (false !== ($file = readdir($handle))) {
			if (is_file($plugin_folder . $file)) {
				if ((strpos($plugin_folder . $file,'.plugin.php') != false) && 
				    (strpos($from_folder . $file,'.svn-base') == false)) {
					$plugin_array = $SHP->get_plugin_data($file);
					$pluginid1 = explode(".",$plugin_array['file']);
					$pluginid  = $pluginid1[0];
					//echo '-> '.$pluginid.'  '.$_POST[$pluginid].'<br>';
					$plugin_checked = false;
					if ($_POST[$pluginid] == 1) {$status = 1; }
					else { $status = 0; }
					$active = false;
                                        if (!isset($_POST['notfirst']) && $plugin_array['active']) {
                                            $active = true;
                                        }
                                        if (isset($_POST['notfirst'])) {
                                            sqlite_query($config['db'], "UPDATE plugins SET status = '$status' WHERE id = '$pluginid';");
                                        }
                                        if (($_POST[$pluginid] == 1) || $active) {
                                            $checking='checked="checked"';
                                        }
                                        else {
                                            $checking='';
                                        }
					$theme_main['content'] .= '<tr><td><input type="checkbox" name="'.$pluginid.'" value="1" '.$checking.'></td><td><a href="'.$plugin_array['url'].'">'.$plugin_array['name'].'</a></td><td>'.$plugin_array['author'].'</td><td>'.$plugin_array['desc'].'</td></tr>';
				}
			}
			else if ((is_dir($plugin_folder . $file)) && ($file != '.') && ($file != '..')) {
				list_plugins($plugin_folder . $file . '/');
			}
		}
		closedir($handle);
        }
        plugin_cleanup();
  }

  function list_comments_moderate() {
      global $SHP, $theme_main, $config, $optionValue, $lang;
      $message = "";
      $result = sqlite_query($config['db'], "select count(commentid) AS view from comments WHERE status = 'pending'");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentCount  = $row['view'];
          if ($commentCount > 0) {
              $theme_main['content'].= '<table><tr><th>Select</th><th>Comment Title</th><th>'.$lang['pageAllCommentsBy'].'</th></tr>';
              $result = sqlite_query($config['db'], "select * from comments WHERE status = 'pending' ORDER BY date DESC");
              while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
                  $commentid     = $row['commentid'];
                  $postid        = $row['postid'];
                  $sequence      = $row['sequence'];
                  $author        = $row['author'];
                  $title         = $row['title'];
                  $content       = $row['content'];
                  $date          = $row['date'];
                  $ip            = $row['ip'];
                  $url           = $row['url'];
                  $email         = $row['email'];
                  $titleModified=getTitleFromFilename($postid);
                  if ($_POST[$commentid] == 1) {$status = 'approved'; }
    	          else { $status = 'pending'; }
                  if (isset($_POST['notfirst']) || ($optionValue == 'delete')) {
                      //echo $optionValue.'<br>';
                      $message = $lang['pageModerateMessage'];
                      if ($optionValue !== 'delete') {
                          sqlite_query($config['db'], "UPDATE comments SET status = '$status' WHERE commentid = '$commentid';");
                      }
                      else {
                          sqlite_query($config['db'], "DELETE FROM comments WHERE commentid = '$commentid';");
                      }
                  }
              }
              $result = sqlite_query($config['db'], "select * from comments WHERE status = 'pending' ORDER BY date DESC");
              while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
                  $commentid     = $row['commentid'];
                  $postid        = $row['postid'];
                  $sequence      = $row['sequence'];
                  $author        = $row['author'];
                  $title         = $row['title'];
                  $content       = $row['content'];
                  $date          = $row['date'];
                  $ip            = $row['ip'];
                  $url           = $row['url'];
                  $email         = $row['email'];
                  $titleModified=getTitleFromFilename($postid);
                  $theme_main['content'].= '<tr><td><input type="checkbox" name="'.$commentid.'" value="1"></td><td><a style="font-style:normal" href="'.$config['blogPath'].$config['cleanIndex'].'/posts/'.$postid.'/'.$titleModified.'#'.$sequence.'">'.$title.'</a></td><td>'.$author.'</td></tr>';
              }
             $theme_main['content'].= "</table>";
             //$theme_main['content'].= "<br>".$message."<br>";
          }
          else {
              $theme_main['content'].= $lang['pageModerateEmpty'].'!<br>';
          }
      }

  }

  function adminPageTabs() {
	  global $config, $lang;
	  $theme_admintabs['actionBasic']    = $config['blogPath'].$config['cleanIndex'].'/adminPageBasic';
	  $theme_admintabs['basic'] 		 = $lang['tabsBasic'];
	  $theme_admintabs['actionAdvanced'] = $config['blogPath'].$config['cleanIndex'].'/adminPageAdvanced';
	  $theme_admintabs['advanced'] 		 = $lang['tabsAdvanced'];
	  $theme_admintabs['actionAuthor'] 	 = $config['blogPath'].$config['cleanIndex'].'/adminPageAuthors';
	  $theme_admintabs['manageAuthors']  = $lang['tabsAuthors'];
	  $theme_admintabs['actionPlugins']  = $config['blogPath'].$config['cleanIndex'].'/adminPagePlugins';
	  $theme_admintabs['managePlugins']  = $lang['tabsPlugins'];
	  $theme_admintabs['actionModerate'] = $config['blogPath'].$config['cleanIndex'].'/adminPageModerate';
	  $theme_admintabs['manageModerate'] = $lang['tabsModerate'];	
	  return @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_admintabs["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/admintabs.tpl"));
  }
  
  function adminPagePlugins() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      global $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_main['content'].= '<h3>'.$lang['pagePlugins'].'</h3>';
      $theme_main['content'].= adminPageTabs();
	  if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
		  $msgclass = "hide";
		  $msgtext	= "";
          if (isset($_POST['notfirst'])) {
			  //$msgtext = $lang['msgConfigSaved'];
			  //$msgclass= "success";
			  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  header('Location: '.$config['blogPath'].$config['cleanIndex'].'/adminPagePlugins');
			  die();
		  }	
		  $theme_main['content'] .= "<div class='$msgclass'>$msgtext</div>";
		  $theme_main['content'] .= '<br><form method="post" action="'.$config['blogPath'].$config['cleanIndex'].'/adminPluginsSubmit">';
          $theme_main['content'] .= '<table>';
          $theme_main['content'] .= '<tr><th>Active</th><th>Plugin</th><th>Author</th><th>Description</th></tr>';
          $plugin_folder = './plugins/';
          list_plugins($plugin_folder);
          $theme_main['content'] .= '</table>';
          $theme_main['content'] .= '<input type="hidden" id="notfirst" name="notfirst" value="notfirst">';
          $theme_main['content'] .= '<br><input type="submit" id="submit" name="submit" value="'.$lang['pageAdvancedConfigSubmit'].'">';
          $theme_main['content'] .= '</form>';
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }


  function adminPageModerate() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      global $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_main['content'].= '<h3>'.$lang['pageModerate'].'</h3>';
	  $theme_main['content'].= adminPageTabs();
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
	  	  $msgclass = "hide";
		  $msgtext	= "";
          if (isset($_POST['notfirst'])) {
			  //$msgtext = $lang['pageModerateMessage'];
			  //$msgclass= "success";
			  $_SESSION['growlmsg'] = $lang['pageModerateMessage'];
			  header('Location:'.$config['blogPath'].$config['cleanIndex'].'/adminPageModerate');
			  die();
		  }	
		  $theme_main['content'] .= "<div class='$msgclass'>$msgtext</div>"; 
	      $theme_main['content'] .= '<br><form method="post" action="'.$config['blogPath'].$config['cleanIndex'].'/adminModerateSubmit">';
          $theme_main['content'] .= '<table>';
          //$theme_main['content'] .= '<tr><th>Active</th><th>Plugin</th><th>Author</th><th>Description</th></tr>';
          //$plugin_folder = './plugins/';
		  list_comments_moderate();
          $theme_main['content'] .= '</table>';
          $theme_main['content'] .= '<input type="hidden" id="notfirst" name="notfirst" value="notfirst">';
          $theme_main['content'] .= '<br><input type="submit" id="submit" name="submit" value="'.$lang['pageModerateApprove'].'">&nbsp;&nbsp;<a href="'.$config['blogPath'].$config['cleanIndex'].'/adminModerateSubmit/delete"><input type="button" value="'.$lang['pageModerateDelete'].'"></a>';
          $theme_main['content'] .= '</form>';
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }


  function readAuthors() {
    /* Read Author information from file. */
    global $authors, $separator, $authorsPass, $authorsEmail, $config, $authorsActCode, $authorsActStatus;

    $tempAuthors = file( $config['authorFile'] );
    $i=0;
    foreach ($tempAuthors as $value) {
        if (!strstr($value,'<?php') && !strstr($value,'?>') && (trim($value) != "" )) {
             $value=str_replace("\n","",$value);
             $authorLine=explode($separator,$value);
             $authors[$i]=$authorLine[0];
             $authorsPass[$authors[$i]]=$authorLine[1];
             $authorsEmail[$authors[$i]]=$authorLine[2];
             $authorsActCode[$authors[$i]]=(trim($authorLine[3]) == "")?11111:$authorLine[3];
             $authorsActStatus[$authors[$i]]=(trim($authorLine[4]) == "")?0:$authorLine[4];
             $i++;
        }
    }
    $authorsPass['admin'] = $config['Password'];
    //echo $config['Password'].'<br>';
 }

 function getPrivacy() {
	global $priv, $config, $morepriv;
	
	$author = @$_SESSION['username'];
	//echo $config['privacy']."<br>";
	switch ($config['privacy']) {
		case 0:
			if ($author == 'admin')
				$priv = ""; 	
			elseif (trim($author) == '')
				$priv = "author = 'a87890KJLii10101zbUyTrUU' and "; 	
			else	
				$priv = "author = '$author' and ";
			break;
		case 1:
			if (!$_SESSION['logged_in'])
				$priv = "author = 'a87890KJLii10101zbUyTrUU' and "; 	
			break;
		case 2:
			$priv = ""; 	
			break;
	}
	$priv = $priv.$morepriv;
  }	

  function readConfig() {
    /* Read config information from file. */
    global $config,$user,$user1;
    //echo $user.' '.$user1.'<br>';
    $configFile = getcwd()."/data/".$user."/config.php";
	//$configFile = "/home/tipsfor1/public_html/pritlog.com/labs/pritlog8/data/user/config.php";
	//echo $configFile.'<br>';
	//die();
    if (!file_exists($configFile)) {
        $configFile = getcwd()."/data/".$user1."/config.php";
        $user = $user1;
        if (!file_exists($configFile)) {
            //@header("Location: index.html");
            //die("Please contact prit@pritlog.com to setup an account for you");
			die("Your config file does not exist");
        }
    }
    $contents = file( $configFile );
    $contents[0]=trim(str_replace("<?php /*","",$contents[0]));
    $contents[0]=trim(str_replace("*/ ?>","",$contents[0]));
    if ( $contents[0] ) {
      $tempConfigs = explode('|', $contents[0]);
      $configKeys = array(   'blogTitle',
                  'Password',
                  'sendMailWithNewComment',
                  'sendMailWithNewCommentMail',
                  'about',
                  'postDir',
                  'commentDir',
                  'menuEntriesLimit',
                  'textAreaCols',
                  'textAreaRows',
                  'entriesPerPage',
                  'maxPagesDisplayed',
                  'commentsMaxLength',
                  'commentsSecurityCode',
                  'onlyNumbersOnCAPTCHA',
                  'CAPTCHALength',
                  'randomString',
                  'commentsForbiddenAuthors',
                  'statsDontLog',
                  'dbFilesExtension',
                  'usersOnlineTimeout',
                  'entriesOnRSS',
                  'authorEditPost',
                  'authorDeleteComment',
                  'metaDescription',
                  'metaKeywords',
                  'menuLinks',
                  'blogLanguage',
                  'blogPath',
                  'showCategoryCloud',
                  'allowRegistration',
                  'sendRegistMail',
                  'timeoutDuration',
                  'theme',
                  'commentModerate',
                  'limitLogins',
                  'privacy',
                  'cleanUrl');

      for ( $i = 0; $i < count( $tempConfigs ); $i++ ) {
        $key = $configKeys[ $i ];
        $config[ $key ] = $tempConfigs[ $i ];
      }
      setConfigDefaults();
    }
  }

  function setConfigDefaults() {
        global $config;
        if ( !isset( $config[ 'blogTitle' ] ) )                  { $config[ 'blogTitle' ]                  = 'My Pritlog'; }
        if ( !isset( $config[ 'randomString' ] ) )               { $config[ 'randomString' ]               = 'ajhd092nmbd20dbJASDK1BFGAB1'; }
        if ( !isset( $config[ 'Password' ] ) )                   { $config[ 'Password' ]                   = md5($config['randomString'].'password'); }
        if ( !isset( $config[ 'about' ] ) )                      { $config[ 'about' ]                      = 'Lorem ipsum duo officiis percipitur ut. Sed te puto sonet euripidis, odio doming lobortis id usu, utinam legimus mediocrem ex duo.'; }
        if ( !isset( $config[ 'sendMailWithNewComment' ] ))      { $config[ 'sendMailWithNewComment' ]     = 1; }
        if ( !isset( $config[ 'sendMailWithNewCommentMail' ] ))  { $config[ 'sendMailWithNewCommentMail' ] = 'you@mail.com'; }
        if ( !isset( $config[ 'postDir' ] ) )                    { $config[ 'postDirOrig' ]                = 'posts';
                                                                   $config[ 'postDir' ]                    = getcwd().'/posts/'; }
                                                       else      { if ( !isset( $config[ 'postDirOrig' ] ) ) { $config[ 'postDirOrig' ] = $config[ 'postDir' ]; }
                                                                   $config[ 'postDir' ]                    = getcwd().'/'.$config[ 'postDir' ].'/';}
        if ( !isset( $config[ 'commentDir' ] ) )                 { $config[ 'commentDirOrig' ]             = 'comments';
                                                                   $config[ 'commentDir' ]                 = getcwd().'/comments/'; }
                                                       else      { if ( !isset( $config[ 'commentDirOrig' ] ) ) { $config[ 'commentDirOrig' ] = $config[ 'commentDir' ]; }
                                                                   $config[ 'commentDir' ]                 = getcwd().'/'.$config[ 'commentDir' ].'/'; }
        if ( !isset( $config[ 'menuEntriesLimit' ] ) )           { $config[ 'menuEntriesLimit' ]           = 7; }
        if ( !isset( $config[ 'textAreaCols' ] ) )               { $config[ 'textAreaCols' ]               = 50; }
        if ( !isset( $config[ 'textAreaRows' ] ) )               { $config[ 'textAreaRows' ]               = 10; }
        if ( !isset( $config[ 'entriesPerPage' ] ) )             { $config[ 'entriesPerPage' ]             = 5; }
        if ( !isset( $config[ 'maxPagesDisplayed' ] ) )          { $config[ 'maxPagesDisplayed' ]          = 5; }
        if ( !isset( $config[ 'commentsMaxLength' ] ) )          { $config[ 'commentsMaxLength' ]          = 1000; }
        if ( !isset( $config[ 'commentsSecurityCode' ] ) )       { $config[ 'commentsSecurityCode' ]       = 1; }
        if ( !isset( $config[ 'onlyNumbersOnCAPTCHA' ] ) )       { $config[ 'onlyNumbersOnCAPTCHA' ]       = 0; }
        if ( !isset( $config[ 'CAPTCHALength' ] ) )              { $config[ 'CAPTCHALength' ]              = 8; }
        if ( !isset( $config[ 'commentsForbiddenAuthors' ] ) )   { $config[ 'commentsForbiddenAuthors' ]   = 'admin,Admin'; }
        if ( !isset( $config[ 'statsDontLog' ] ) )               { $config[ 'statsDontLog' ]               = '127.0.0.1,192.168.0.1'; }
        if ( !isset( $config[ 'dbFilesExtension' ] ) )           { $config[ 'dbFilesExtension' ]           = '.prt'; }
        if ( !isset( $config[ 'usersOnlineTimeout' ] ) )         { $config[ 'usersOnlineTimeout' ]         = 120; }
        if ( !isset( $config[ 'entriesOnRSS' ] ) )               { $config[ 'entriesOnRSS' ]               = 0; }
        if ( !isset( $config[ 'authorEditPost' ] ) )             { $config[ 'authorEditPost' ]             = 1; }
        if ( !isset( $config[ 'authorDeleteComment' ] ) )        { $config[ 'authorDeleteComment' ]        = 1; }
        if ( !isset( $config[ 'metaDescription' ] ) )            { $config[ 'metaDescription' ]            = "Pritlog"; }
        if ( !isset( $config[ 'metaKeywords' ] ) )               { $config[ 'metaKeywords' ]               = 'Pritlog, my blog, pplog'; }
        if ( !isset( $config[ 'menuLinks' ] ) )                  { $config[ 'menuLinksOrig' ]              = 'http://google.com,Google;http://pplog.infogami.com/,Get PPLOG;http://hardkap.net/pritlog,Get PRITLOG';
                                                                   $config[ 'menuLinks' ]                  = 'http://google.com,Google;http://pplog.infogami.com/,Get PPLOG;http://hardkap.net/pritlog,Get PRITLOG'; }
        if ( !isset( $config[ 'blogLanguage' ] ) )               { $config[ 'blogLanguage' ]               = 'english-us'; }
        if ( !isset( $config[ 'theme' ] ) )                      { $config[ 'theme' ]                      = 'default'; }
        if ( !isset( $config[ 'privacy' ] ) )                    { $config[ 'privacy' ]                    = '2'; }
        if ( !isset( $config[ 'blogPath' ] ) )                   { $config[ 'blogPath' ]                   = 'http://localhost'; }
        if ( !isset( $config[ 'showCategoryCloud' ] ) )          { $config[ 'showCategoryCloud' ]          = 1; }
        if ( !isset( $config[ 'allowRegistration' ] ) )          { $config[ 'allowRegistration' ]          = 0; }
        if ( !isset( $config[ 'sendRegistMail' ] ) )             { $config[ 'sendRegistMail' ]             = 1; }
        if ( !isset( $config[ 'commentModerate' ] ) )            { $config[ 'commentModerate' ]            = 0; }
        if ( !isset( $config[ 'cleanUrl' ] ) )                   { $config[ 'cleanUrl' ]                   = 0; }
        if ( !isset( $config[ 'timeoutDuration' ] ) )            { $config[ 'timeoutDuration' ]            = 0; }
        if ( !isset( $config[ 'limitLogins' ] ) )                { $config[ 'limitLogins' ]                = 10; }
        $config['menuLinksOrig']=$config['menuLinks'];
        $config['menuLinksArray']=explode(';',$config['menuLinks']);

  }

  function writeConfig($message=true) {
        global $config, $lang, $user;
        $configFile=getcwd()."/data/".$user.'/config.php';
        /*$configContent='<?php /* ';*/
		//echo 'Writing Config .. <br/>';
		$configContent = '';
        if (file_exists($configFile)) {
            $configContent=$configContent.
                          $config['blogTitle'].'|'.
                          $config['Password'].'|'.
                          $config['sendMailWithNewComment'].'|'.
                          $config['sendMailWithNewCommentMail'].'|'.
                          $config['about'].'|'.
                          $config['postDirOrig'].'|'.
                          $config['commentDirOrig'].'|'.
                          $config['menuEntriesLimit'].'|'.
                          $config['textAreaCols'].'|'.
                          $config['textAreaRows'].'|'.
                          $config['entriesPerPage'].'|'.
                          $config['maxPagesDisplayed'].'|'.
                          $config['commentsMaxLength'].'|'.
                          $config['commentsSecurityCode'].'|'.
                          $config['onlyNumbersOnCAPTCHA'].'|'.
                          $config['CAPTCHALength'].'|'.
                          $config['randomString'].'|'.
                          $config['commentsForbiddenAuthors'].'|'.
                          $config['statsDontLog'].'|'.
                          $config['dbFilesExtension'].'|'.
                          $config['usersOnlineTimeout'].'|'.
                          $config['entriesOnRSS'].'|'.
                          $config['authorEditPost'].'|'.
                          $config['authorDeleteComment'].'|'.
                          $config['metaDescription'].'|'.
                          $config['metaKeywords'].'|'.
                          $config['menuLinks'].'|'.
                          $config['blogLanguage'].'|'.
                          $config['blogPath'].'|'.
                          $config['showCategoryCloud'].'|'.
                          $config['allowRegistration'].'|'.
                          $config['sendRegistMail'].'|'.
                          $config['timeoutDuration'].'|'.
                          $config['theme'].'|'.
                          $config['commentModerate'].'|'.
                          $config['limitLogins'].'|'.
                          $config['privacy'].'|'.
                          $config['cleanUrl'];
            //echo $configContent.'<br/>';
			$configContent='<?php /* '.$configContent.' */ ?>';
            $fp=fopen($configFile,"w");
			$fwrite = fwrite($fp,$configContent);
            if ($fwrite === false) {echo 'Error updating config<br/>';}
            fclose($fp);
            if ($message) {echo '<br>'.$lang['msgConfigSaved'].'<br>';}
			//die('dying');
        }
  }


  function createRSS() {
    global $config, $separator, $entries, $optionValue, $lang, $priv;
	$base = 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],0,-3);

	echo header('Content-type: text/xml').'<?xml version="1.0" encoding="ISO-8859-1"?><rss version="2.0">';
	echo '<channel><title>'.$config['blogTitle'].'</title><description>'.$config['metaDescription'].'</description>';
	echo '<link>http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],0,strlen($_SERVER['REQUEST_URI'])-7).'</link>';
       
        $result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE ".$priv." type = 'post';");
        while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
            $totalEntries = $row['view'];
        }

	if($config['entriesOnRSS'] == 0)
	{
		$limit = $totalEntries;
	}
	else
	{
		$limit = $config['entriesOnRSS'];
	}

        $limit = ($limit > 200) ? 200 : $limit;
        $result = sqlite_query($config['db'], "select * from posts where ".$priv." 1 ORDER BY postid DESC LIMIT $limit;");
        while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
            $rssTitle      = $row['title'];
            $rssTitleModified=titleModify($rssTitle);
            $rssContent    = explode("*readmore*",$row['content']);
            if (trim($rssContent[1]) !== "") $readmore = '<br><br><a href="'.$link.'">'.$lang['pageViewFullPost'].'</a>';
            else $readmore = "";
            $date1         = $row['date'];
            $rssEntry      = $row['postid'];
            $rssCategory   = $row['category'];
            $postType      = $row['type'];
            $allowComments = $row['allowcomments'];
            $visits        = $row['visits'];
            $link          = $base.htmlspecialchars('posts/'.$rssEntry."/".$rssTitleModified);
            if (trim($visits) == "") { $visits=0; }
            if ($optionValue === $rssCategory || trim($optionValue) == "") {
                   echo '<item><link>'.$link.'</link>';
    		   echo '<title>'.$rssTitle.'</title><category>'.$rssCategory.'</category>';
    		   echo '<description>'.htmlspecialchars(html_entity_decode($rssContent[0].$readmore)).'</description></item>';
    		   //echo '<description>'.htmlspecialchars($rssContent[0]).'<br><a href="'.$link.'">'.$lang['pageViewFullPost'].'</a></description></item>';
            }
        }

	echo '</channel></rss>';
  }

  function titleModify($myTitle)
  {
      $myTitle=removeAccent(str_replace('"','',str_replace("'","",html_entity_decode($myTitle,ENT_QUOTES))));
      $myTitleMod1=@preg_replace("/[^a-z\d\'\"]/i", "-", substr($myTitle,0,strlen($myTitle)-1));
	  $myTitleMod2=@preg_replace("/[^a-z\d]/i", "", substr($myTitle,strlen($myTitle)-1,1));
      $myTitleModified=rtrim($myTitleMod1.$myTitleMod2,'-');
      return $myTitleModified;
  }

  function getPosts($start, $end, $requestCategory = "") {
      global $config, $postdb, $separator, $priv;
	   $file_array_sorted   = array();
	   if (trim($requestCategory) == "") {
		   $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." (type = 'post' or (type = 'page' AND stick = 'yes')) ORDER BY stick desc, postid desc LIMIT $start, $end;");
       }
       else {
			$result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." type = 'post' AND (category = '$requestCategory' or category like '$requestCategory,%' or category like '%,$requestCategory' or category like '%,$requestCategory,%') ORDER BY stick desc, postid desc LIMIT $start, $end;");
       }
       while ($row = @sqlite_fetch_array($result, SQLITE_ASSOC)) {
		  $status		 = $row['status'];
		  $mystatus		 = ($status == 1)?"":"(draft)";
		  $title         = $row['title'].' '.$mystatus;
          $content       = $row['content'];
          $date          = $row['date'];
          $fileName      = $row['postid'];
          $category      = $row['category'];
          $postType      = $row['type'];
          $allowComments = $row['allowcomments'];
          $visits        = $row['visits'];
          if (trim($visits) == "") { $visits=1; }
          //echo $title.'<br>';
          $author=(trim($row['author'])=="")?'admin':$row['author'];
          $line=$title.$separator.$content.$separator.$date.$separator.$fileName.$separator.$category.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$author;
          array_push($file_array_sorted, $line);
      }
      //return $result;
      return $file_array_sorted;
  }


  function sidebarStats() {
      global $config, $separator, $lang;
      $ip=$_SERVER['REMOTE_ADDR'];
      $currentTime=time();
      //$currentDateTime=date("d M Y H:i");
      $currentDateTime=date("Y-m-d H:i:s");
      $statsContent=$ip.$separator.$currentTime."\n";
      $statsFile=$config['commentDir']."online".$config['dbFilesExtension'].".dat";
      $logThis=0;
      $statsDontLog=explode(',',$config['statsDontLog']);
      $stattype = "total";
      foreach ($statsDontLog as $value) {
          if ($ip == $value ) {
              $logThis=1;
          }
      }
      $result = sqlite_query($config['db'], "SELECT * FROM stats WHERE stattype = '$stattype';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $statcount = $row['statcount'] + 1;
      }

      if (($logThis != 1) && ($_SESSION['start'])) {
          sqlite_query($config['db'], "UPDATE stats SET statcount = '$statcount' WHERE stattype = '$stattype';");
      }
      if (!(isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false)) {
         @sqlite_query($config['db'], "DELETE FROM active_guests WHERE ip = '$ip';");
         @sqlite_query($config['db'], "DELETE FROM active_users WHERE ip = '$ip';");
         //echo 'Logging Guest: '.$currentDateTime.' '.$ip.'<br>';
         sqlite_query($config['db'], "INSERT INTO active_guests (ip, logtime) VALUES('$ip', '$currentDateTime');");
      }
      /* Update users last active timestamp */
      else{
         @sqlite_query($config['db'], "DELETE FROM active_guests WHERE ip = '$ip';");
         @sqlite_query($config['db'], "DELETE FROM active_users WHERE ip = '$ip';");
         sqlite_query($config['db'], "INSERT INTO active_users (ip, logtime) VALUES('$ip','$currentDateTime');");
      }
      $guests = 0;
      $users  = 0;
      $result = sqlite_query($config['db'], "SELECT * FROM active_guests;");
      $date1 = date("Y-m-d H:i");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $logID   = $row['id'];
          $logIP   = $row['ip'];
          $logTime = strtotime($row['logtime']);
          $timeOnline=$currentTime-$logTime;
          //echo $logIP.' '.$currentTime.' '.$logTime.' '.$timeOnline.' '.$config['usersOnlineTimeout'].'<br>';
          //echo $row['date1'].'<br>';

          if ($timeOnline > $config['usersOnlineTimeout']) {
               sqlite_query($config['db'], "DELETE FROM active_guests WHERE id = '$logID';");
          }
          else { $guests++; }
      }
      $result = sqlite_query($config['db'], "SELECT * FROM active_users;");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $logID   = $row['id'];
          $logIP   = $row['ip'];
          $logTime = strtotime($row['logtime']);
          $timeOnline=$currentTime-$logTime;
          //echo $logIP.'  '.$timeOnline.'2<br>';

          if ($timeOnline > $config['usersOnlineTimeout']) {
               sqlite_query($config['db'], "DELETE FROM active_users WHERE id = '$logID';");
          }
          else { $users++; }
      }
      $online = $users + $guests;
      $stats = "";
      //if ($_SESSION['start']) { $stats = 'Session Start<br>'; }
      //echo $lang['sidebarStatsUsersOnline'].': '.$online.'<br>';
      $stats .= $users  . ' '.$lang['sidebarStatsMembersOnline'].'<br>';
      $stats .= $guests . ' '.$lang['sidebarStatsGuestsOnline'].'<br>';
      $stats .= $lang['sidebarStatsHits'].': '.$statcount.'<br>';
      return $stats;
  }


  function listPosts() {
      global $separator, $entries, $config, $requestCategory, $priv;
      global $userFileName, $optionValue3, $lang, $theme_main, $SHP, $theme_post;
      $config_Teaser=0;
      $filterEntries=array();
	  $totalEntries = 0;
	  //echo $priv."<br/>";
      if (trim($requestCategory) == "") {
          //$result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE type = 'post' or (type = 'page' AND stick = 'yes');");
		  $result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE ".$priv." (type = 'post' or (type = 'page' AND stick = 'yes'));");
		  while ($row = @sqlite_fetch_array($result, SQLITE_ASSOC)) {
              $totalEntries = $row['view'];
              if ($row['view'] == 0) {
                 $theme_main['content'] = '<br><br>'.$lang['msgNoPosts'].' <a href="'.$config['blogPath'].$config['cleanIndex'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
              }
          }
      }
      else {
          //$result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE type = 'post' AND category = '$requestCategory';");
          //$result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE type = 'post' AND (category = '$requestCategory' or category like '$requestCategory,%'or category like '%,$requestCategory' or category like '%,$requestCategory,%');");
			$result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE ".$priv." type = 'post' AND (category = '$requestCategory' or category like '$requestCategory,%'or category like '%,$requestCategory' or category like '%,$requestCategory,%');");
		    while ($row = @sqlite_fetch_array($result, SQLITE_ASSOC)) {
              $totalEntries = $row['view'];
          }
      }

      if ($totalEntries == 0) {
          $theme_main['content'] = '<br><br>'.$lang['msgNoPosts'].' <a href="'.$config['blogPath'].$config['cleanIndex'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
      }
	  
	  
      # Pagination - This is the so called Pagination
      $page=$optionValue3;
      if($page == ''){ $page = 1; }

      # What part of the array should i show in the page?
      $arrayEnd = ($config['entriesPerPage']*$page);
      $arrayStart = $arrayEnd-($config['entriesPerPage']-1);
      # As arrays start from 0, i will lower 1 to these values
      $arrayEnd--;
      $arrayStart--;
      $totalEntries--;

      $i = $arrayStart;
      if ($arrayEnd > $totalEntries) {$arrayEnd = $totalEntries;}
      //echo 'a '.$i.' '.$arrayStart.' '.$arrayEnd.'<br>';
      $filterEntries = getPosts($arrayStart, $config['entriesPerPage'], $requestCategory);

      $j = 0;
      while($i<=$arrayEnd)
      {
           $entry  =explode($separator,$filterEntries[$j++]);
           $title  =$entry[0];
           $titleModified=titleModify($title);
           $date1   =date("d M Y H:i",strtotime($entry[2]));
           $postMonth=date("M",strtotime($entry[2]));
           $postDay  =date("d",strtotime($entry[2]));
           $postYear =date("Y",strtotime($entry[2]));
           $fileName=$entry[3];
           $category=html_entity_decode($entry[4]);
           $postType=$entry[5];
           $visits  =$entry[7];
           $author  =(trim($entry[8])== "")?'admin':$entry[8];
           if (trim($visits) == "") { $visits=0; }
           if (strstr($entry[1],"*readmore*")) { $readmore='<br><br><a href="'.$config['blogPath'].$config['cleanIndex'].'/posts/'.$fileName."/".$titleModified.'">'.$lang['pageViewFullPost'].' &raquo;</a>'; }
           else { $readmore=""; }
           $content =explode("*readmore*",$entry[1]);

           $theme_post['loc_top']           = "";
           $theme_post['loc_title_after']   = "";
           $theme_post['loc_content_after'] = "";
           $theme_post['loc_footer']        = "";
           $theme_post['loc_bottom']        = "";
           $theme_post['postLink'] = $config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified;
           $theme_post['title']    = $title;
           $theme_post['content']  = html_entity_decode($content[0].$readmore);
           $categoryText=str_replace("_"," ",$category);
           $theme_post['authorLabel']    = $lang['pageAuthorsNew1'];
           $theme_post['author']         = $author;
           $theme_post['dateLabel']     = $lang['postFtPosted'];
           $theme_post['date']          = $date1;
           $theme_post['postMonth']     = $postMonth;
           $theme_post['postDay']       = $postDay;
           $theme_post['postYear']      = $postYear;
           //$theme_post['categoryLabel'] = $lang['postFtCategory'];
           $theme_post['categoryLabel'] = $lang['postFtTags'];
           $theme_post['category'] = "";
           unset($listcats);
           foreach (explode(",",$category) as $singlecat) $listcats[$singlecat]="1";
           $catsep="";
           foreach ($listcats as $catkey => $catvalue)
           {
              $categoryText=str_replace("_"," ",$catkey);
              $theme_post['category'] .= $catsep."<a href=".$config['blogPath'].$config['cleanIndex']."/category/".$catkey.">".$categoryText."</a>";
              $catsep=",";
           }
           //$theme_post['category']      = "<a href=".$_SERVER['SCRIPT_NAME']."/viewCategory/".urlencode($category).">".$categoryText."</a>";
           $theme_post['visitsLabel']   = $lang['postFtVisits'];
           $theme_post['visits']        = $visits;

           if ($SHP->hooks_exist('hook-post')) {
           	$SHP->execute_hooks('hook-post');
           }

           $commentFile=$config['commentDir'].$fileName.$config['dbFilesExtension'];
           $result = sqlite_query($config['db'], "select count(*) AS view from comments WHERE postid='$fileName' AND status='approved';");
           $commentCount = sqlite_fetch_array($result);
           if ($commentCount['view'] > 0) {
               $commentText=$lang['postFtComments'].": ".$commentCount['view'];
           }
           else {$commentText=$lang['postFtNoComments'];}
           $theme_post['comments']        = "<a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified."#Comments>".$commentText."</a>";
           $theme_post['edit'] = $theme_post['delete'] = "";
           if (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) {
               $theme_post['edit']       = "<a href=".$config['blogPath'].$config['cleanIndex']."/editEntry/".$fileName.">".$lang['postFtEdit']."</a>";
               $theme_post['delete']    = '&nbsp;&nbsp;<a href="#" onclick="'.'confirm_delete(\''.$config['blogPath'].$config['cleanIndex']."/deleteEntrySubmit/".$fileName.'\')'.'">'.$lang['postFtDelete']."</a></center><br/>";
           }
           if ($postType == "page") 
				$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_post["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/page.tpl"));
		   else
				$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_post["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/post.tpl"));
           $i++;
      }
      $totalEntries++;
      $totalPages = ceil(($totalEntries)/($config['entriesPerPage']));
      if($totalPages >= 1)
      {
	   $theme_main['content'] .= '<center> '.$lang['msgPages'].': ';
      }
      else
      {
  	   //echo '<center> No more posts under this category.';
      }
      $startPage = $page == 1 ? 1 : ($page-1);
      $displayed = 0;
      for($i = $startPage; $i <= (($page-1)+$config['maxPagesDisplayed']); $i++)
      {
      	   if($i <= $totalPages)
	   {
	 	if($page != $i)
		{
                        if (trim($requestCategory) == "")
                        {
                                $categoryText='/mainPage/allCategories';
                        }
                        else
                        {
                                $categoryText='/category/'.urlencode($requestCategory);
                        }

                        if($i == (($page-1)+$config['maxPagesDisplayed']) && (($page-1)+$config['maxPagesDisplayed']) < $totalPages)
			{
				$theme_main['content'] .=  '<a href='.$config['blogPath'].$config['cleanIndex'].$categoryText.'/page/'.$i.'>['.$i.']</a> ...';
			}
			elseif($startPage > 1 && $displayed == 0)
			{
				$theme_main['content'] .= '... <a href='.$config['blogPath'].$config['cleanIndex'].$categoryText.'/page/'.$i.'>['.$i.']</a> ';
	 			$displayed = 1;
			}
			else
			{
				$theme_main['content'] .= '<a href='.$config['blogPath'].$config['cleanIndex'].$categoryText.'/page/'.$i.'>['.$i.']</a> ';
			}
		}
		else
		{
			$theme_main['content'] .= '['.$i.'] ';
		}
	    }
	}
	$theme_main['content'] .= '</center>';
  }


  function sidebarListEntries() {
      global $separator, $entries, $config, $theme_main, $priv;
      $i=0;
      $limit = $config['menuEntriesLimit'];
  	  $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." type = 'post' ORDER BY postid desc LIMIT $limit;");
	  //$result = sqlite_query($config['db'], "select * from posts ORDER BY postid DESC LIMIT $limit;");
      $latest="";
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
            $title         = $row['title'];
            $titleModified = titleModify($title);
            $fileName      = $row['postid'];
            $postType      = $row['type'];
            if ($postType!="page") {
                $theme_latest['link']     = $config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified;
                $theme_latest['linktext'] = $title;
                $latest .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_latest["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/latestentries.tpl"));
                $i++;
            }
      }
      $theme_main['latestEntries'] = $latest;
  }

  function sidebarPopular() {
      global $separator, $entries, $config, $priv;
      $i=1;
      $multiArray= Array();
      $limit = $config['menuEntriesLimit'];
      $popular = "";
	  $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." type = 'post' ORDER BY visits desc LIMIT $limit;");
	  //$result = sqlite_query($config['db'], "select * from posts WHERE type = 'post' ORDER BY visits DESC LIMIT $limit;");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $content       = (str_replace("*readmore*","",$row['content']));
          $date1         = $row['date'];
          $fileName      = $row['postid'];
          $category      = $row['category'];
          $postType      = $row['type'];
          $allowComments = $row['allowcomments'];
          $visits        = $row['visits'];
          if (trim($visits) == "") { $visits=0; }
          $theme_popular['link']     = $config['blogPath'].$config['cleanIndex'].'/posts/'.$fileName.'/'.$titleModified;
          $theme_popular['linktext'] = $title;
          $popular .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_popular["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/popularentries.tpl"));
      }
      return $popular;
  }


  function getTitleFromFilename($fileName1) {
      global $entries, $separator, $config, $priv;
      $limit  = count($entries);
      $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." postid = '$fileName1';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
	  $fileTitle  = $row['title'];
          $titleText  = titleModify($fileTitle);
          return $titleText;
      }
  }

  function sidebarListComments() {
      global $separator, $entries, $config, $priv;
      $latestCommentsFile=$config['commentDir']."latest".$config['dbFilesExtension'];
      $limit = $config['menuEntriesLimit'];
      $comments = "";
	  $author = @$_SESSION['username'];
      $result = sqlite_query($config['db'], "select * from comments WHERE status = 'approved' and postid in (select postid from posts where ".$priv." 1) ORDER BY date DESC LIMIT $limit;");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentFileName = $row['postid'];
          $commentNum      = $row['sequence'];
          $commentTitle    = $row['title'];
          $postTitle=getTitleFromFilename($commentFileName);
          $theme_listcomment['link']     = $config['blogPath'].$config['cleanIndex']."/posts/".$commentFileName."/".$postTitle."#".$commentNum;
          $theme_listcomment['linktext'] = $commentTitle;
          $comments .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_listcomment["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/listcomments.tpl"));
      }
      return $comments;
  }

  function sidebarPageEntries() {
      global $separator, $entries, $config, $priv;
      $i=0;
      $limit = $config['menuEntriesLimit'];
      $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." type = 'page' ORDER BY date DESC LIMIT $limit;");
      $pages = "";
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
            $title         = $row['title'];
            $titleModified = titleModify($title);
            $fileName         = $row['postid'];
            $postType         = $row['type'];
            $theme_listpages['link']     = $config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified;
            $theme_listpages['linktext'] = $title;
            $pages .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_listpages["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/listpages.tpl"));;
            $i++;
      }
      return $pages;
  }
  
  function removeAccent($string="") {
     $search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u");
     $replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u");
     $string = str_replace($search, $replace, $string);
     return $string;
  }

  function printTagCloudAgain($tags) {
        // $tags is the array
        //arsort($tags);
        //shuffle($tags);
        global $config;

        $max_size = 42; // max font size in pixels
        $min_size = 16; // min font size in pixels

        // largest and smallest array values
        $max_qty = max(array_values($tags));
        $min_qty = min(array_values($tags));

        // find the range of values
        $spread = $max_qty - $min_qty;
        if ($spread == 0) { // we don't want to divide by zero
                $spread = 1;
        }

        // set the font-size increment
        $step = ($max_size - $min_size) / ($spread);

        /*$colors = array("#ACC1F3", "#86A0DC", "#607EC5", "#4C6DB9", "#395CAE", "#264CA2", "#133B97", "#002A8B");*/
        /*$colors = array("#FFCC00", "#ADFF2F", "#ECA500", "#FFDAB9", "#BCE9DB", "#715A75");*/
        $colors = array("#D65421", "#000000", "#D5B94C", "#D92178", "#20A0CF", "#7777A7");

        // loop through the tag array
        $tagCloud = '<ul class="tagcloud">';
        foreach ($tags as $key => $value) {
            $size = $min_size + (($value - $min_qty) * $step);
            $rand_colors = array_rand($colors);
            //$tagCloud .= '<li><a href="'.$_SERVER['SCRIPT_NAME'].'/viewCategory/'.urlencode(str_replace(" ",".",$key)).'" class="tag" style="font-size:'.$size.'px; color:'.$colors[$rand_colors].'" onmouseout="this.style.color=\''.$colors[$rand_colors].'\'" onmouseover="this.style.color=\'#fff\'" title="'.$value.' things tagged with '.$key.'">'.$key.'</a>';
            $tagCloud .= '<li><a href="'.$config['blogPath'].$config['cleanIndex'].'/category/'.removeAccent(str_replace(" ","_",$key)).'" class="tag" style="font-size:'.$size.'px; color:'.$colors[$rand_colors].'" onmouseout="this.style.color=\''.$colors[$rand_colors].'\'" onmouseover="this.style.color=\'#fff\'" title="'.$value.' things tagged with '.$key.'">'.$key.'</a>';
            $tagCloud .= '<span class="count"> ('.$value.')</span></li>';
        }
        $tagCloud .= '</ul>';
        return $tagCloud;
  }


  function loadCategories() {
      global $separator, $entries, $config, $tags, $priv;
      $category_array_unsorted=array();
      $result = sqlite_query($config['db'], "select DISTINCT category from posts WHERE ".$priv." type = 'post';");
      unset($listcats);
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          //$category = $row['category'];
          foreach (explode(",",$row['category']) as $singlecat) $listcats[$singlecat]="1";
      }
      if (@is_array($listcats)) {
          foreach ($listcats as $catkey => $catvalue)
          {
              $category = $catkey;
              $categoryText=str_replace("_"," ",$category);
              //$result1 = sqlite_query($config['db'], "select count(category) AS view from posts WHERE category = '$category';");
              $result1 = sqlite_query($config['db'], "select count(category) AS view from posts WHERE ".$priv." (category = '$category' or category like '$category,%' or category like '%,$category' or category like '%,$category,%') and type = 'post';");
              while ($row1 = sqlite_fetch_array($result1, SQLITE_ASSOC)) {
                  $catcount = $row1['view'];
                  $tags[$categoryText] = $catcount;
              }
          }
      }
  }

  function sidebarCategories() {
      global $separator, $entries, $config, $tags, $categories, $priv;
      $result = sqlite_query($config['db'], "select DISTINCT category from posts WHERE ".$priv." type = 'post' ORDER BY category;");
      unset($listcats);
      $categories = "";
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          //$categoryText=str_replace("_"," ",$row['category']);
          foreach (explode(",",$row['category']) as $singlecat) $listcats[$singlecat]="1";
      }
      if (@is_array($listcats)) {
          ksort($listcats);
          foreach ($listcats as $catkey => $catvalue)
          {
              $categoryText=str_replace("_"," ",$catkey);
              $theme_categories['link']     = $config['blogPath'].$config['cleanIndex']."/category/".urlencode($catkey);
              $theme_categories['linktext'] = $categoryText;
              $categories .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_categories["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/categories.tpl"));
          }
      }
      return $categories;
  }

  function sidebarLinks() {
      global $config;
      $links = "";
      foreach ($config['menuLinksArray'] as $value) {
          $fullLink=explode(",",$value);
          $theme_links['link']     = $fullLink[0];
          $theme_links['linktext'] = $fullLink[1];
          $links .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_links["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/links.tpl"));
      }
      return $links;
  }

  function listAllComments() {
      global $config, $separator, $lang, $theme_main, $priv;
      $theme_main['content'] = "";
      $latestCommentsFile=$config['commentDir']."latest".$config['dbFilesExtension'];
      $userFileName=$config['commentDir']."users".$config['dbFilesExtension'].".dat";
      $theme_main['content'].= '<h3>'.$lang['pageAllComments'].'</h3>';
      $result = sqlite_query($config['db'], "select count(commentid) AS view from comments WHERE status = 'approved' and postid in (select postid from posts where ".$priv." 1)");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentCount  = $row['view'];
          if ($commentCount > 0) {
              $theme_main['content'].= '<table><tr><th>'.$lang['pageAllCommentsTitle'].'</th><th>'.$lang['pageAllCommentsDate'].'</th><th>'.$lang['pageAllCommentsBy'].'</th></tr>';
          }
          else {
              $theme_main['content'].= $lang['pageAllCommentsNo'].'!<br>';
          }
          $result = sqlite_query($config['db'], "select * from comments WHERE status = 'approved' and postid in (select postid from posts where ".$priv." 1) ORDER BY date DESC");
          while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
              $postid        = $row['postid'];
              $sequence      = $row['sequence'];
              $author        = $row['author'];
              $title         = $row['title'];
              $content       = $row['content'];
              $date          = $row['date'];
              $ip            = $row['ip'];
              $url           = $row['url'];
              $email         = $row['email'];
              $titleModified=getTitleFromFilename($postid);
              $theme_main['content'].= "<tr><td><a style='font-style:normal' href=".$config['blogPath'].$config['cleanIndex']."/posts/".$postid."/".$titleModified."#Comments>".$title."</a></td>";
              $theme_main['content'].= "<td>".$date."</td><td>".$author."</td></tr>";
          }
          $theme_main['content'].= "</table>";
      }
  }


  function newEntryForm() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $blogPath, $lang, $authors, $authorsPass, $ss;
      global $theme_main, $SHP, $theme_new;
      $newPostFileName=$config['postDir'].$newPostFile;
      $theme_new['newEntryHeader'] = $lang['pageNew'];
      $theme_main['content'] = "";
      if ($debugMode=="on") {
         $theme_main['content'] .= $_SERVER['PHP_SELF']."<br>";
         $theme_main['content'] .= "Post will be written to ".$newPostFileName."  ".$newFullPostNumber;
      }
      $thisAuthor = $_SESSION['username'];
      $do = 1;
      if (trim($thisAuthor) == "") {
           $theme_main['content'] .= $lang['errorAllFields'].'<br>';
           $do = 0;
      }
      if ($do == 1) {
          if (is_array($authors)) {
              if (isset($authorsPass[$thisAuthor])) {
                   if ($_SESSION['logged_in']) {
                        $theme_new['loc_top']            = "";
                        $theme_new['loc_form_top']       = "";
                        $theme_new['loc_content_before'] = "";
                        $theme_new['loc_content_after']  = "";
                        $theme_new['loc_form_bottom']    = "";
                        $theme_new['loc_bottom']         = "";
                        if ($SHP->hooks_exist('hook-new')) {
                         	$SHP->execute_hooks('hook-new');
                        }


                        $theme_new['script']           = $config['blogPath'].$config['cleanIndex'];
                        $theme_new['pageLegend']       = $lang['pageNewForm'];
                        $theme_new['title']            = $lang['pageNewTitle'];
						$theme_new['titleValidate']    = '<script>';
                        $theme_new['titleValidate']   .= 'var title = new LiveValidation( "title", {onlyOnSubmit: true } );';
                        $theme_new['titleValidate']   .= 'title.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
                        $theme_new['titleValidate']   .= '</script>';
                        $theme_new['content']          = $lang['pageNewContent'];
                        $theme_new['readmore']         = $lang['pageNewReadmore'];
                        $theme_new['textAreaCols']     = $config['textAreaCols'];
                        $theme_new['textAreaRows']     = $config['textAreaRows'];
                        $theme_new['category']         = $lang['pageNewCategory'];
                        $theme_new['categoryValidate'] = '<script>';
                        $theme_new['categoryValidate'].= 'var category = new LiveValidation( "category", {onlyOnSubmit: true } );';
                        $theme_new['categoryValidate'].= 'category.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
                        $theme_new['categoryValidate'].= '</script>';
                        $theme_new['options']          = $lang['pageNewOptions'];
                        $theme_new['allowComments']    = $lang['pageNewAllowComments'];
                        $theme_new['isPage']           = $lang['pageNewIsPage'];
                        $theme_new['isPageHelp']       = $lang['pageNewIsPageDesc'];
                        $theme_new['isSticky']         = $lang['pageNewIsSticky'];
						$theme_new['isDraft']          = $lang['pageNewIsDraft'];
                        $theme_new['hidden']           = '<input name="process" type="hidden" id="process" value="newEntry">';
                        $theme_new['hidden']          .= '<input name="author" type="hidden" id="author" value="'.$thisAuthor.'">';
                        $theme_new['submit']           = $lang['pageNewSubmit'];
                        $theme_main['content']        .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_new["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/newentry.tpl"));
                   }
                   else {
                        $theme_main['content'] .= $lang['errorUserPassIncorrect'].'<br>';
                   }
              }
              else {
                   $theme_main['content'] .= $lang['errorUserPassIncorrect'].'<br>';
              }
          }
      }
      else {
           $theme_main['content'] .= $lang['errorPleaseGoBack'].'<br>';
      }

  }

  function newEntrySubmit() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $lang, $authors, $authorsPass;
      global $theme_main, $SHP, $public_data, $blogPath;
      $newPostFileName=$config['postDir'].$newPostFile;
      unset($GLOBALS['$public_data']);
      $public_data['postTitle']     = $postTitle=htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["title"])));
      $public_data['postContent']   = $postContent=htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["posts"])));
      $public_data['postDate']      = $postDate=date("Y-m-d H:i:s");
      $public_data['isPage']        = $isPage=isset($_POST["isPage"])?$_POST["isPage"]:0;
      $public_data['stick']         = $stick=isset($_POST["isSticky"])?$_POST["isSticky"]:"no";
	  $public_data['status']        = $status=isset($_POST["isDraft"])?$_POST["isDraft"]:1;
      $public_data['allowComments'] = $allowComments=isset($_POST["allowComments"])?$_POST["allowComments"]:"no";
      $public_data['thisAuthor']    = $thisAuthor = $_POST['author'];
      $visits=0;
      $public_data['postCategory'] = $postCategory=htmlentities(sqlite_escape_string(removeAccent(strtolower($_POST["category"]))));
      $theme_main['content'] = "<h3>".$lang['pageNew']."...</h3>";
      $do = 1;
      unset($listcats);
      foreach (explode(",",$postCategory) as $singlecat) $listcats[$singlecat]="1";
      $catsep="";
      $postCategory="";
      foreach ($listcats as $catkey => $catvalue)
      {
         $postCategory .= $catsep.trim($catkey);
         $catsep=",";
      }
      if ($SHP->hooks_exist('hook-newsubmit-before')) {
         $SHP->execute_hooks('hook-newsubmit-before');
      }
      $postTitle     = $public_data['postTitle'];
      $postContent   = $public_data['postContent'];
      $postDate      = $public_data['postDate'];
      $isPage        = $public_data['isPage'];
      $stick         = $public_data['stick'];
      $allowComments = $public_data['allowComments'];
      $thisAuthor    = $public_data['thisAuthor'];
	  $msglog		 = "";

      if(trim($postTitle) == '' || trim($postContent) == '' || trim($postCategory) == '' || strstr($postCategory,'.'))
      {
      	   $msglog .= $lang['errorAllFields'].'.<br>';
      	   $msglog .= $lang['errorCatName'].'<br>';
           $do = 0;
      }
      if ($SHP->hooks_exist('hook-newsubmit-validate')) {
         $SHP->execute_hooks('hook-newsubmit-validate', $public_data);
      }
      $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." title = '$postTitle';");
      $dupMsg = "";
      if (sqlite_num_rows($result) > 0) {
           $dupMsg = $dupMsg.$lang['errorDuplicatePost'].'.<br>';
           $do = 1;
      }
      if ($do == 1) {
          if ($authorsPass[$thisAuthor] === $authorsPass[$_SESSION['username']] && (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false)) {
              $postCategory=str_replace(" ","_",$postCategory);
              if ($debugMode=="on") {$theme_main['content'] .= "Writing to ".$newPostFileName;}
              $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errornewPostFile'].'<br>';
              $errorMessage=$errorMessage.'<br>'.$lang['errorReportBug'].'<br>';
              if ($isPage == 1) {
                  $postType="page";
              }
              else {
                  $postType="post";
              }
              $postContent=str_replace("\\","",$postContent);
              $content=$postTitle.$separator.str_replace("\\","",$postContent).$separator.$postDate.$separator.$newFullPostNumber.$separator.$postCategory.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$thisAuthor;
              sqlite_query($config['db'], "INSERT INTO posts (postid, title, content, date, category, type, stick, allowcomments, visits, author, status) VALUES('$newFullPostNumber', '$postTitle', '$postContent', '$postDate', '$postCategory', '$postType', '$stick', '$allowComments','$visits', '$thisAuthor', '$status');");
              if ($SHP->hooks_exist('hook-newsubmit-after')) {
                 $SHP->execute_hooks('hook-newsubmit-after');
              }
              //$theme_main['content'] .= $dupMsg.$lang['msgNewPost'].'&nbsp;&nbsp;<a href="'.$blogPath.'">'.$lang['msgGoBack'].'</a>';
			  $msglog .= $dupMsg.$lang['msgNewPost'];
			  $_SESSION['growlmsg'] = $msglog;
			  //header('Location: '.$config['blogPath'].$config['cleanIndex'].'/newEntrySuccess');
			  header('Location: '.$config['blogPath'].$config['cleanIndex']);
			  die();
          }
          else {
              $msglog .= $lang['errorPasswordIncorrect'].'<br>';
          }
      }
      $_SESSION['growlmsg'] = $msglog;
	  header('Location: '.$_SESSION['referrer']);
  }
  
  function newEntrySuccess() {
  	  global $config, $lang, $blogPath, $theme_main;
	  $theme_main['content'] = "<h3>".$lang['pageNew']."...</h3>";
	  if (trim($_SESSION['newSuccess']) != '') $theme_main['content'] .= $_SESSION['newSuccess'];
	  else header('Location: '.$config['blogPath']);
	  $_SESSION['newSuccess'] = '';
  }  

  function deleteEntryForm() {
      global $debugMode, $optionValue, $lang, $theme_main,$config;
      $fileName = $optionValue;
      $theme_main['content'] = "";
      $theme_main['content'] .= "<h3>".$lang['pageDelete']."...</h3>";
      $theme_main['content'] .= "<form name=\"form1\" method=\"post\" action=".$config['blogPath'].$config['cleanIndex']."/deleteEntry>";
      $theme_main['content'] .= $lang['msgSure'].'<br><br>';
      $theme_main['content'] .= '<input name="process" type="hidden" id="process" value="deleteEntrySubmit">';
      $theme_main['content'] .= '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'">';
      $theme_main['content'] .= '<input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'">';
	  //$theme_main['content'] .= '<a href="'.$_SESSION['referrer'].'"><button>'.$lang['msgGoBack'].'</button></a>';
      $theme_main['content'] .= "</form>";
  }

  function deleteEntrySubmit() {
       global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $optionValue, $lang, $authors, $authorsPass;
       global $theme_main, $SHP, $priv;
       $theme_main['content'] = "";
       if ($debugMode=="on") {$theme_main['content'] .=  "Inside deleteEntrySubmit ..<br>";}
       $entryName= $optionValue;
       $fileName = $config['postDir'].$entryName.$config['dbFilesExtension'];
       $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." postid = '$entryName';");
       while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
           //echo $row['postid'].'<br>';
           $author=$row['author'];
           $category=$row['category'];
       }
       $theme_main['content'] .=  "<h3>".$lang['pageDelete']."...</h3>";
       $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errorDeleteEntry'].'<br>';
       $errorMessage=$errorMessage.$lang['errorReportBug'].'<br>';
       $thisAuthor = $_SESSION['username'];
       if ((($config['authorEditPost'] == 1) && ($_SESSION['logged_in'])) ||
           (($config['authorEditPost'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && $_SESSION['logged_in'])) {
          if (@sqlite_query($config['db'], "DELETE FROM posts WHERE ".$priv." postid = '$entryName';"))
			@sqlite_query($config['db'], "DELETE FROM comments WHERE postid = '$entryName';");
          $msglog .= $lang['msgDeleteSuccess'];
          if ($SHP->hooks_exist('hook-delete-entry')) {
              $SHP->execute_hooks('hook-delete-entry');
          }
       }
       else {
          $msglog .= $lang['errorNotAuthorized'].' .. <br>';
       }
	   $_SESSION['growlmsg'] = $msglog;
	   header('Location: '.$_SESSION['url']);
  }

  function editEntryForm() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $authors, $authorsPass;
      global $optionValue, $blogPath, $lang, $theme_main, $SHP, $theme_edit, $priv;
      $fileName = $optionValue;
      $editFileName=$config['postDir'].$fileName.$config['dbFilesExtension'];
      $theme_main['content'] = "";
      $theme_edit['header']  = $lang['pageEdit'];
      if ($debugMode=="on") {$theme_main['content'] .= "Editing .. ".$editFileName."<br>";}
      $thisAuthor = $_SESSION['username'];
      //$thisPass = md5($config['randomString'].$_POST['pass']);
      $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." postid = '$fileName';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $content       = $row['content'];
          $date1         = $row['date'];
          $fileName      = $row['postid'];
          $category      = $row['category'];
          $postType      = $row['type'];
          $allowComments = $row['allowcomments'];
          $visits        = $row['visits'];
          $stick         = $row['stick'];
		  $status		 = $row['status'];
          $author=(trim($row['author'])=="")?'admin':$row['author'];
        if ($postType == "page") {
            $checking='checked="checked"';
        }
        else {
            $checking='';
        }
        if ($allowComments == "yes") {
            $checkAllowComments='checked="checked"';
        }
        else {
            $checkAllowComments='';
        }
        if ($stick == "yes") {
            $checkStick='checked="checked"';
        }
        else {
            $checkStick='';
        }
		if ($status == "0") {
            $checkDraft='checked="checked"';
        }
        else {
            $checkDraft='';
        }
        if ((($config['authorEditPost'] == 1) && ($_SESSION['logged_in'])) ||
            (($config['authorEditPost'] == 0) && ($_SESSION['isAdmin'] || $thisAuthor == $author) && ($_SESSION['logged_in']))) {
            if ($thisAuthor == 'admin' && $thisAuthor != $author && trim($author) != "") {
                $thisAuthor = $author;
                $thisPass   = $authorsPass[$thisAuthor];
            }

            $theme_edit['loc_top']            = "";
            $theme_edit['loc_form_top']       = "";
            $theme_edit['loc_content_before'] = "";
            $theme_edit['loc_content_after']  = "";
            $theme_edit['loc_form_bottom']    = "";
            $theme_edit['loc_bottom']         = "";
            if ($SHP->hooks_exist('hook-edit')) {
             	$SHP->execute_hooks('hook-edit');
            }

            $theme_edit['script'] = $config['blogPath'].$config['cleanIndex'];
            $theme_edit['pageLegend'] = $lang['pageEditForm'];
            $theme_edit['labelTitle'] = $lang['pageNewTitle'];
            $theme_edit['title'] = $title;
            $theme_edit['titleValidate']    = '<script>';
            $theme_edit['titleValidate']   .= 'var title = new LiveValidation( "title", {onlyOnSubmit: true } );';
            $theme_edit['titleValidate']   .= 'title.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
            $theme_edit['titleValidate']   .= '</script>';
            $theme_edit['labelContent'] = $lang['pageNewContent'];
            $theme_edit['readmore']     = $lang['pageNewReadmore'];
            $theme_edit['textAreaCols']     = $config['textAreaCols'];
            $theme_edit['textAreaRows']     = $config['textAreaRows'];
            $theme_edit['content']     = $content;
            $theme_edit['labelCategory']     = $lang['pageNewCategory'];
            $category=str_replace("_"," ",$category);
            $theme_edit['category']     = $category;
            $theme_edit['categoryValidate'] = '<script>';
            $theme_edit['categoryValidate'].= 'var category = new LiveValidation( "category", {onlyOnSubmit: true } );';
            $theme_edit['categoryValidate'].= 'category.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
            $theme_edit['categoryValidate'].= '</script>';
            $theme_edit['options'] = $lang['pageNewOptions'];
            $theme_edit['checkAllowComments'] = $checkAllowComments;
            $theme_edit['allowComments'] = $lang['pageNewAllowComments'];
            $theme_edit['checkIsPage'] = $checking;
            $theme_edit['isPage'] = $lang['pageNewIsPage'];
            $theme_edit['isPageHelp'] = $lang['pageNewIsPageDesc'];
            $theme_edit['checkSticky'] = $checkStick;
            $theme_edit['isSticky'] = $lang['pageNewIsSticky'];
			$theme_edit['checkDraft'] = $checkDraft;
            $theme_edit['isDraft'] = $lang['pageNewIsDraft'];
            $theme_edit['hidden'] = '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'">';
            $theme_edit['hidden'].= '<input name="visits" type="hidden" id="visits" value="'.$visits.'">';
            $theme_edit['hidden'].= '<input name="process" type="hidden" id="process" value="editEntrySubmit">';
            $theme_edit['hidden'].= '<input name="author" type="hidden" id="author" value="'.$thisAuthor.'">';
            $theme_edit['hidden'].= '<input name="pass" type="hidden" id="pass" value="'.$thisPass.'">';
            $theme_edit['submit'] = $lang['pageEditSubmit'];
            $theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_edit["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/editentry.tpl"));
        }
        else {
          $theme_main['content'] .= $lang['errorNotAuthorized'].' .. <br>';
       }

      }
      //else {echo $lang['errorFileNA'].'...<br>';}
  }

  function editEntrySubmit() {
      global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $authors, $authorsPass;
      global $optionValue, $lang, $theme_main, $SHP, $public_data, $blogPath;
      $theme_main['content'] = "";
      if ($debugMode=="on") {$theme_main['content'] .= "Inside editEntrySubmit ..".$_POST['fileName']."<br>";}
      $theme_main['content'] .= "<h3>".$lang['pageEdit']."...</h3>";
      unset($GLOBALS['$public_data']);
      $public_data['entryName']     = $entryName= $_POST['fileName'];
      $public_data['postTitle']     = $postTitle=htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["title"])));
      $public_data['postContent']   = $postContent=htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["posts"])));
      $public_data['postDate']      = $postDate=date("Y-m-d H:i:s");
      $public_data['isPage']        = $isPage=isset($_POST["isPage"])?$_POST["isPage"]:0;
      $public_data['stick']         = $stick=isset($_POST["isSticky"])?$_POST["isSticky"]:"no";
	  $public_data['status']        = $status=isset($_POST["isDraft"])?$_POST["isDraft"]:1;
      $public_data['allowComments'] = $allowComments=isset($_POST["allowComments"])?$_POST["allowComments"]:"no";
      $public_data['visits']        = $visits=isset($_POST["visits"])?$_POST["visits"]:0;
      $public_data['postCategory']  = $postCategory=htmlentities(sqlite_escape_string(removeAccent(strtolower($_POST["category"]))));
      $public_data['thisAuthor']    = $thisAuthor = $_POST['author'];
      $thisPass   = $_POST['pass'];
      $do = 1;
      unset($listcats);
      foreach (explode(",",$postCategory) as $singlecat) $listcats[$singlecat]="1";
      $catsep="";
      $postCategory="";
      foreach ($listcats as $catkey => $catvalue)
      {
         $postCategory .= $catsep.trim($catkey);
         $catsep=",";
      }
      if ($SHP->hooks_exist('hook-editsubmit-before')) {
         $SHP->execute_hooks('hook-editsubmit-before');
      }
      if(trim($postTitle) == '' || trim($postContent) == '' || trim($postCategory) == '' || strstr($postCategory,'.'))
      {
      	   $msglog .= $lang['errorAllFields'].'.<br>';
      	   $msglog .= $lang['errorCatName'].'<br>';
	       $do = 0;
      }

      if ($do == 1) {
          if ($isPage == 1) {
              $public_data['postType'] = $postType="page";
          }
          else {
              $public_data['postType'] = $postType="post";
          }
          if ($debugMode=="on") {echo "Writing to ".$fileName;}
          $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errornewPostFile'].'<br>';
          $errorMessage=$errorMessage.'<br>'.$lang['errorReportBug'].'<br>';
          if ($_SESSION['logged_in']) {
              if ($SHP->hooks_exist('hook-editsubmit-validate')) {
                 $SHP->execute_hooks('hook-editsubmit-validate', $public_data);
              }
              $postCategory=str_replace(" ","_",$postCategory);
              $content=$postTitle.$separator.str_replace("\\","",$postContent).$separator.$postDate.$separator.$entryName.$separator.$postCategory.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$thisAuthor;
              sqlite_query($config['db'], "UPDATE posts SET title='$postTitle', content='$postContent', category='$postCategory', type='$postType', stick='$stick', status = '$status', allowcomments='$allowComments', visits='$visits', author='$thisAuthor' WHERE postid='$entryName';");
              $msglog .= $lang['msgEditSuccess'];
              if ($SHP->hooks_exist('hook-editsubmit-after')) {
                 $SHP->execute_hooks('hook-editsubmit-after');
              }
          }
          else {
              $msglog .= $lang['errorNotAuthorized'].' .. <br>';
          }
      }
	  $_SESSION['growlmsg'] = $msglog;
	  header('Location: '.$_SESSION['referrer']);
  }
  
  function genRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = '';

    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }

    return $string;
  }

  function viewEntry() {
      global $optionValue, $blogPath, $lang, $nicEditUrl, $SHP, $theme_post, $priv;
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $theme_main, $public_data;
      $fileName=$optionValue;
      $viewFileName=$config['postDir'].$fileName.$config['dbFilesExtension'];
      $cool=true;
      $theme_main['content'] = "";
      if ($debugMode=="on") {echo "Editing .. ".$viewFileName."<br>";}
      if (strstr($fileName,'%') || strstr($fileName,'.')) {
          $cool=false;
          echo '<br>'.$lang['errorInvalidRequest'].'<br>';
      }
      $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." postid = '$fileName';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $content       = html_entity_decode(str_replace("*readmore*","",$row['content']));
          $date1         = date("d M Y H:i",strtotime($row['date']));
          $postMonth     = date("M",strtotime($row['date']));
          $postDay       = date("d",strtotime($row['date']));
          $postYear      = date("Y",strtotime($row['date']));
          $fileName      = $row['postid'];
          $category      = html_entity_decode($row['category']);
          $postType      = $row['type'];
          $allowComments = $row['allowcomments'];
          $visits        = $row['visits'];
          if (trim($visits) == "") { $visits=1; }
          else { $visits++; }
          $author=(trim($row['author'])=="")?'admin':$row['author'];
          sqlite_query($config['db'], "UPDATE posts SET visits = '$visits' WHERE postid = '$fileName';");
          $categoryText=str_replace("_"," ",$category);

          $theme_post['loc_top']           = "";
          $theme_post['loc_title_after']   = "";
          $theme_post['loc_content_after'] = "";
          $theme_post['loc_footer']        = "";
          $theme_post['loc_bottom']        = "";

          $theme_post['postLink'] = $config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified;
          $theme_post['title']    = $title;
          $theme_post['content']  = $content;
          $categoryText=str_replace("_"," ",$category);
          $theme_post['authorLabel']    = $lang['pageAuthorsNew1'];
          $theme_post['author']         = $author;
          $theme_post['dateLabel']    = $lang['postFtPosted'];
          $theme_post['date']         = $date1;
          $theme_post['postMonth']     = $postMonth;
          $theme_post['postDay']       = $postDay;
          $theme_post['postYear']      = $postYear;
          //$theme_post['categoryLabel'] = $lang['postFtCategory'];
          $theme_post['categoryLabel'] = $lang['postFtTags'];
          $theme_post['category'] = "";
          unset($listcats);
          foreach (explode(",",$category) as $singlecat) $listcats[$singlecat]="1";
          $catsep="";
	  foreach ($listcats as $catkey => $catvalue)
	  {
	     $categoryText=str_replace("_"," ",$catkey);
	     $theme_post['category'] .= $catsep."<a href=".$config['blogPath'].$config['cleanIndex']."/category/".urlencode($catkey).">".$categoryText."</a>";
             $catsep=",";
	  }
          //$theme_post['category']      = "<a href=".$config['blogPath'].$config['cleanIndex']."/viewCategory/".urlencode($category).">".$categoryText."</a>";
          $theme_post['visitsLabel']   = $lang['postFtVisits'];
          $theme_post['visits']        = $visits;

          if ($SHP->hooks_exist('hook-post')) {
           	$SHP->execute_hooks('hook-post');
          }

          $commentFile=$config['commentDir'].$fileName.$config['dbFilesExtension'];
          if (isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)
               $result = sqlite_query($config['db'], "select count(*) AS view from comments WHERE postid='$fileName';");
          else
               $result = sqlite_query($config['db'], "select count(*) AS view from comments WHERE postid='$fileName' AND status = 'approved';");
          $commentCount = sqlite_fetch_array($result);
          if ($commentCount['view'] > 0) {
              $commentText=$lang['postFtComments'].": ".$commentCount['view'];
          }
          else {$commentText=$lang['postFtNoComments'];}

          $theme_post['comments']        = "<a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified."#Comments>".$commentText."</a>";
          $theme_post['edit'] = $theme_post['delete'] = "";
          if (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) {
              $theme_post['edit'] = "<a href=".$config['blogPath'].$config['cleanIndex']."/editEntry/".$fileName.">".$lang['postFtEdit']."</a>";
              $theme_post['delete'] = "&nbsp;-&nbsp;<a href=".$config['blogPath'].$config['cleanIndex']."/deleteEntry/".$fileName.">".$lang['postFtDelete']."</a>";
          }

          if ($postType == "page") {
			$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_post["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/page.tpl"));
		  }
		  else {	
			  $theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_post["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/post.tpl"));
			  $commentFullName=$config['commentDir'].$fileName.$config['dbFilesExtension'];
			  $i=0;
			  $theme_main['content'] .= "<a name='Comments'></a><h3>".$lang['pageViewComments'].":</h3>";

			  if($allowComments == "yes")
			  {
					unset($GLOBALS['$public_data']);
					$public_data['postid'] = $fileName;
					if ($SHP->hooks_exist('hook-comment-replace')) {
						 $SHP->execute_hooks('hook-comment-replace', $public_data);
					}
					else {
						if (isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)
							 $result = sqlite_query($config['db'], "select * from comments WHERE postid = '$fileName';");
						else
							 $result = sqlite_query($config['db'], "select * from comments WHERE postid = '$fileName' AND status = 'approved';");
						while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
							$postid        = $row['postid'];
							$sequence      = $row['sequence'];
							$author        = $row['author'];
							$title         = $row['title'];
							$content       = $row['content'];
							$date          = $row['date'];
							$ip            = $row['ip'];
							$url           = $row['url'];
							$email         = $row['email'];
							$authorLink    = (trim($url) == "")?$author:'<a href="'.$url.'">'.$author.'</a>';
							$theme_comment['sequence']    = $sequence;
							$theme_comment['commentsBy']  = $lang['pageCommentsBy'];
							$theme_comment['authorLink']  = $authorLink;
							$theme_comment['commentDate'] = $lang['pageViewCommentsOn'];
							$theme_comment['date'] = $date;
							$theme_comment['content'] = $content;
							if (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) {
								$theme_comment['delete'] = '<a href="'.$config['blogPath'].$config['cleanIndex'].'/deleteComment/'.$fileName.'/'.$sequence.'">'.$lang['postFtDelete'].'</a>';
								if (isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false) {
									$theme_comment['ip'] =  '&nbsp;&nbsp;-&nbsp;&nbsp;'.$ip;
								}
							}
							$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_comment["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/comment.tpl"));
							$i++;
						}
						if ($i == 0) {$theme_main['content'] .= $lang['pageViewCommentsNo']."<br>";}
					}

					if ($SHP->hooks_exist('hook-commentform-replace')) {
						$SHP->execute_hooks('hook-commentform-replace');
					}
					else {
						$theme_commentform['nicEdit']  = '';
						$theme_commentform['nicEdit'] .= $nicEditUrl;
						$theme_commentform['nicEdit'] .= '<br /><br /><h3>'.$lang['pageComments'].'</h3>';
						$theme_commentform['nicEdit'] .= '<script type="text/javascript">';
						$theme_commentform['nicEdit'] .= '    bkLib.onDomLoaded(function(){';
						$theme_commentform['nicEdit'] .= "          new nicEditor({buttonList : ['bold','italic','underline','link','unlink'], iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('comment');";
						$theme_commentform['nicEdit'] .= "          });";
						$theme_commentform['nicEdit'] .= "</script>";
		
		
						$theme_commentform['commentAction'] = $config['blogPath'].$config['cleanIndex']."/sendComment";
						$theme_commentform['legend']        = $lang['pageCommentsForm'];
						$theme_commentform['authorLabel']   = $lang['pageCommentsAuthor'];
						$theme_commentform['required']      = $lang['pageCommentsRequired'];
						$theme_commentform['authorValidate'] = '<script>';
						$theme_commentform['authorValidate'].= 'var author = new LiveValidation( "author", {onlyOnSubmit: true } );';
						$theme_commentform['authorValidate'].= 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
						$theme_commentform['authorValidate'].= '</script>';
						$theme_commentform['emailLabel']     = $lang['pageAuthorsNewEmail'];
						$theme_commentform['optional']       = $lang['pageCommentsOptionalEmail'];
						$theme_commentform['emailValidate'] = '<script>';
						$theme_commentform['emailValidate'].= 'var commentEmail = new LiveValidation( "commentEmail", {onlyOnSubmit: true } );';
						$theme_commentform['emailValidate'].= 'commentEmail.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
						$theme_commentform['emailValidate'].= '</script>';
						$theme_commentform['url']           = $lang['pageCommentsUrl'];
						$theme_commentform['optionalUrl']   = $lang['pageCommentsOptionalUrl'];
						$theme_commentform['urlValidate'] = '<script>';
						$theme_commentform['urlValidate'].= 'var commentUrl = new LiveValidation( "commentUrl", {onlyOnSubmit: true } );';
						$theme_commentform['urlValidate'].= 'commentUrl.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i,  failureMessage: "'.$lang['errorInvalidUrl'].'" } );';
						$theme_commentform['urlValidate'].= '</script>';
						$theme_commentform['contentLabel'] = $lang['pageCommentsContent'];
						$theme_commentform['textAreaCols'] = $config['textAreaCols'];
						$theme_commentform['textAreaRows'] = $config['textAreaRows'];
					if($config['commentsSecurityCode'] == 1)
					{
					$code = '';
					if($config['onlyNumbersOnCAPTCHA'] == 1)
					{
						$code = substr(rand(0,999999),1,$config['CAPTCHALength']);
					}
					else
					{
						//$code = strtoupper(substr(crypt(rand(0,999999), $config['randomString']),1,$config['CAPTCHALength']));
						$code = genRandomString($config['CAPTCHALength']);
					}
					$theme_commentform['securityCode'] = '<p><label for="code">'.$lang['pageCommentsCode'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$code.')</font><br>';
					$theme_commentform['securityCode'].= '<input name="code" class="s" type="text" id="code"><p>';
								$theme_commentform['securityCode'].= '<input name="originalCode" value="'.$code.'" type="hidden" id="originalCode">';
					}

						$theme_commentform['hidden'] = '<input name="sendComment" value="'.$fileName.'" type="hidden" id="sendComment">';
						$theme_commentform['loc_form'] = '';
						if ($SHP->hooks_exist('hook-commentform')) {
							$SHP->execute_hooks('hook-commentform');
						}
						$theme_commentform['submit'] = $lang['pageCommentsSubmit'];
						$theme_commentform['reset']  = $lang['pageCommentsReset'];
						$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_commentform["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/commentform.tpl"));
					}
			  }
			  else {$theme_main['content'] .= $lang['pageCommentsDisabled'].'!<br>';}
			}
      }
  }

  function sendComment() {
	# Send Comment Process
    global $separator, $config, $lang, $theme_main, $SHP, $public_data;
    unset($GLOBALS['$public_data']);
    $theme_main['content'] = "";
	$theme_main['content'] .= "<h3>".$lang['pageViewComments']."</h3>";
    $public_data['commentFileName'] = $commentFileName = isset($_POST['sendComment'])?$_POST['sendComment']:$_GET['sendComment'];
	$public_data['author']          = $author          = isset($_POST['author'])?$_POST['author']:"";
	$public_data['commentTitle']    = $commentTitle    = $lang['pageCommentsBy'].' '.$author;
	$public_data['comment']         = $comment         = isset($_POST['comment'])?$_POST['comment']:"";
	$public_data['url']             = $url             = isset($_POST['commentUrl'])?$_POST['commentUrl']:"";
	$public_data['email']           = $email           = isset($_POST['commentEmail'])?$_POST['commentEmail']:"";
	$public_data['code']            = $code            = $_POST['code'];
	$public_data['originalCode']    = $originalCode    = $_POST['originalCode'];
	$do              = 1;
	$triedAsAdmin    = 0;
	if($config['onlyNumbersOnCAPTCHA'] == 1)
	{
		$code1 = substr(rand(0,999999),1,$config['CAPTCHALength']);
	}
	else
	{
		$code1 = genRandomString($config['CAPTCHALength']);
	}
	$_POST['code'] = $code1;

	if (!$SHP->hooks_exist('hook-commentform-replace')) {

			if(trim($commentTitle) == '' || trim($author) == '' || trim($comment) == '')
    	    {
				$theme_main['content'] .= $lang['errorAllFields'].'<br>';
				$do = 0;
    	    }

    	    if($config['commentsSecurityCode'] == 1)
    	    {
				//$code = $_POST['code'];
				$originalCode = $_POST['originalCode'];
				if ($code !== $originalCode)
				{
					$theme_main['content'] .= $lang['errorSecurityCode'].'<br>';
					$do = 0;
				}
    	    }

    	    $hasPosted = 0;

            $forbiddenAuthors=explode(',',$config['commentsForbiddenAuthors']);
            foreach($forbiddenAuthors as $value)
    	    {
    		if($value == $author)
    		{
				$theme_main['content'] .= $lang['errorCommentUser1']." ".$author." ".$lang['errorCommentUser2'];
				$do=0;
    	 	}
    	    }
	}
        $public_data['do'] = $do;
        if ($SHP->hooks_exist('hook-comment-validate')) {
            $SHP->execute_hooks('hook-comment-validate', $public_data);
        }
        $do = $public_data['do'];

	if($do == 1)
	{
		if(strlen($comment) > $config['commentsMaxLength'])
		{
		     $theme_main['content'] .= $lang['errorLongComment1'].' '.$config['commentsMaxLength'].' '.$lang['errorLongComment2'].' '.strlen($comment);
		}
        else
		{
			 $commentFullName=$config['commentDir'].$commentFileName.$config['dbFilesExtension'];
			 $result = sqlite_query($config['db'], "select count(sequence) AS view from comments WHERE postid='$commentFileName';");
			 $commentCount = sqlite_fetch_array($result);
			 $result = sqlite_query($config['db'], "select * from comments WHERE postid = '$commentFileName' ORDER BY sequence DESC LIMIT 1;");
			 while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
				 $maxseq = $row['sequence'];
			 }
 		     if ($commentCount['view'] == 0) {
                         $thisCommentSeq=1;
                     }
                     else { $thisCommentSeq = $maxseq + 1; }
                     $comment = sqlite_escape_string(str_replace("\\","",str_replace("\n","",$comment)));
                     $date    = date("Y-m-d H:i:s");
                     $ip      = $_SERVER["REMOTE_ADDR"];
                     if ($config['commentModerate'] == 1) {
                         $status = 'pending';
                         $message = $lang['msgCommentModerate'];
                     }
                     else {
                         $status = 'approved';
                         $message = $lang['msgCommentAdded'];
                     }
					 sqlite_query($config['db'], "INSERT INTO comments (postid, sequence, title, author, content, date, ip, url, email, status) VALUES('$commentFileName', '$thisCommentSeq', '$commentTitle', '$author', '$comment', '$date', '$ip', '$url', '$email', '$status');");
					 $_SESSION['message'] = $message;
                     $theme_main['content'] .= $message.' '.$author.'!<br />';

                     # If Comment Send Mail is active
		    if($config['sendMailWithNewComment'] == 1)
            {
				$subject = "PRITLOG: ".$lang['msgMail7'];
				$message = $lang['msgMail1']." ".$author." ".$lang['msgMail2']."\n\n"
                                  .$lang['msgMail3'].": ".$commentTitle."\n"
                                  .$lang['msgMail4'].": ".str_replace("\\","",$comment)."\n"
                                  .$lang['msgMail5'].": ".date("d M Y h:i A")."\n\n"
                                  .$lang['msgMail6']."\n\n";

				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				// Additional headers
				$headers .= 'To: '.$config['sendMailWithNewCommentMail']. "\r\n";
				$headers = 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
				@mail($config['sendMailWithNewCommentMail'], $subject, $message, $headers);
			}
			header('Location: '.$config['blogPath'].$config['cleanIndex'].'/sendCommentSuccess');
		}
		//unset($_POST);
		if ($SHP->hooks_exist('hook-comment-success')) {
            $SHP->execute_hooks('hook-comment-success');
        }
	}
	else {
            $theme_main['content'] .= $lang['errorPleaseGoBack'];
            if ($SHP->hooks_exist('hook-comment-fail')) {
                $SHP->execute_hooks('hook-comment-fail');
            }
        }
  }
  
  function sendCommentSuccess() {
		global $separator, $config, $lang, $theme_main, $SHP, $public_data;
		$theme_main['content'] = "";
	    $theme_main['content'] .= "<h3>".$lang['pageViewComments']."</h3>";
		if (trim($_SESSION['message']) != '') $theme_main['content'] .= $_SESSION['message'];
		else header('Location: '.$config['blogPath']);
		$_SESSION['message'] = '';
		
  }
  
  function deleteCommentForm() {
      global $debugMode, $optionValue, $optionValue2, $lang, $theme_main, $config;
      $fileName = $optionValue;
      $commentNum = $optionValue2;
      $theme_main['content'] = "";
      $theme_main['content'] .= "<h3>".$lang['pageCommentDel']."...</h3>";
      $theme_main['content'] .= "<form name=\"form1\" method=\"post\" action=".$config['blogPath'].$config['cleanIndex']."/deleteComment>";
      $theme_main['content'] .= $lang['msgSure'].'<br><br>';
      $theme_main['content'] .= '<input name="process" type="hidden" id="process" value="deleteCommentSubmit">';
      $theme_main['content'] .= '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'">';
      $theme_main['content'] .= '<input name="commentNum" type="hidden" id="commentNum" value="'.$commentNum.'">';
      $theme_main['content'] .= '<input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'">';
      $theme_main['content'] .= "</form>";
  }

  function deleteCommentSubmit() {
       global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $lang, $authors, $authorsPass;
       global $fileName, $theme_main, $SHP, $public_data, $priv;
       $theme_main['content'] = "";
       if ($debugMode=="on") {echo "Inside deleteCommentSubmit ..<br>";}
       $public_data['fileName'] = $fileName   = $_POST['fileName'];
       $public_data['commentNum'] = $commentNum = $_POST['commentNum'];
       $postFile = $config['postDir'].$fileName.$config['dbFilesExtension'];
       $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." postid = '$fileName';");
       while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
           $author = $row['author'];
       }
       $public_data['thisAuthor'] = $thisAuthor = $_SESSION['username'];
       $theme_main['content'] .= "<h3>".$lang['pageCommentDel']."...</h3>";
       $commentNum=$_POST['commentNum'];
       if ((($config['authorDeleteComment'] == 1) && ($_SESSION['logged_in'])) ||
           (($config['authorDeleteComment'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && ($_SESSION['logged_in']))) {
            sqlite_query($config['db'], "delete from comments WHERE postid = '$fileName' and sequence = '$commentNum';");
            //$theme_main['content'] .= $lang['msgCommentDeleted']." ...<br>";
			$theme_main['content'] .= $lang['msgCommentDeleted'].'...<a href="'.$_SESSION['referrer'].'">'.$lang['msgGoBack'].'</a>';
            unset($GLOBALS['$public_data']);
            if ($SHP->hooks_exist('hook-deletecomment')) {
                $SHP->execute_hooks('hook-deletecomment', $public_data);
            }
       }
       else {
          $theme_main['content'] .= $lang['errorNotAuthorized'].' .. <br>';
       }
  }


  function viewArchive() {
      global $separator, $entries, $config, $lang, $theme_main, $priv;
      $theme_main['content'] = "";
      $i=0;
	  $theme_main['content'].= "<h3>".$lang['pageArchive']."</h3>";
      $archiveArray   = array();
      $archiveArrayUnique = array();
      $archiveArrayFormat = array();
      $result = sqlite_query($config['db'], "select * from posts where ".$priv." 1 ORDER BY date;");
      if (sqlite_num_rows($result) == 0) {
          $theme_main['content'].= '<br><br>'.$lang['msgNoPosts'].' <a href="'.$config['blogPath'].$config['cleanIndex'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
      }
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($rssTitle);
          $date          = $row['date'];
          $monthYear     = date("Y-m",strtotime($date));
          array_push($archiveArray,$monthYear);
          $archiveArrayFormat[$monthYear] = date("M Y",strtotime($date));
          //echo $date.'<br>';
      }
      $archiveArrayUnique=array_unique($archiveArray);
      foreach ($archiveArrayUnique as $archiveMonthYear) {
          $theme_main['content'].= "<a style='font-style:normal' href=\"".$config['blogPath'].$config['cleanIndex']."/month/".str_replace(" ","-",$archiveMonthYear)."\">".$archiveArrayFormat[$archiveMonthYear]."</a><br>";
      }
  }


  function viewArchiveMonth() {
      global $separator, $entries, $config, $optionValue, $lang, $theme_main, $priv;
      $i=0;
      $theme_main['content'] = "";
      $theme_main['content'].= "<h3>".$lang['pageArchiveFor']." ".date("M Y",strtotime($optionValue))."</h3>";
      //$requestMonth = str_replace("-"," ",$optionValue);
      $requestMonth = $optionValue;
      //echo $requestMonth.'<br>';
      $theme_main['content'].= "<table>";
      $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." date LIKE '%$requestMonth%';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $postDate      = $row['date'];
          $fileName      = $row['postid'];
          $theme_main['content'].= "<tr><td>".$postDate.":&nbsp;</td><td><a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified.">".$title."</a></td></tr>";
      }
      $theme_main['content'].= "</table>";
  }


  function searchPosts() {
      global $separator, $config, $entries, $lang, $theme_main, $SHP, $public_data, $priv;
      $theme_main['content'] = "";
      $searchkey   = isset($_POST['searchkey'])?$_POST['searchkey']:$_GET['searchkey'];
      $theme_main['content'].= "<h3>".$lang['pageSearch']."</h3>";
      $i=0;
      $searchResults = array();
      unset($GLOBALS['$public_data']);
      if (trim($searchkey) == "") {
          $theme_main['content'].= $lang['errorSearchNothing'].'<br>';
      }
      else {
          $public_data['searchkey'] = $searchkey;
          if ($SHP->hooks_exist('hook-searchposts-replace')) {
              $SHP->execute_hooks('hook-searchposts-replace', $public_data);
          }
          else {
              $searcharray=explode(" ",$searchkey);
              $searchexpr="";
              foreach ($searcharray as $singlekey) {
                  if ($searchexpr!="") $searchexpr.=" and ";
                  $searchexpr.="(lower(title) like lower('%$singlekey%') or lower(content) like lower('%$singlekey%'))";
              }
              //$result = sqlite_query($config['db'], "select * from posts WHERE title LIKE '%$searchkey%' OR content LIKE '%$searchkey%';");
              $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." $searchexpr;");
              while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
                  $title         = $row['title'];
                  $titleModified = titleModify($title);
                  $content       = $row['content'];
                  $date          = $row['date'];
                  $fileName      = $row['postid'];
                  $category      = $row['category'];
                  $postType      = $row['type'];
                  $allowComments = $row['allowcomments'];
                  $visits        = $row['visits'];
                  $theme_main['content'].= "<a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified.">".$title."</a><br/>";
                  $i++;
                  array_push($searchResults, $fileName);
              }
              $searchexpr="";
              foreach ($searcharray as $singlekey) {
                  if ($searchexpr!="") $searchexpr.=" or ";
                  $searchexpr.="(lower(title) like lower('%$singlekey%') or lower(content) like lower('%$singlekey%'))";
              }
              //$result = sqlite_query($config['db'], "select * from posts WHERE title LIKE '%$searchkey%' OR content LIKE '%$searchkey%';");
              $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." $searchexpr;");
              while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
                  $title         = $row['title'];
                  $titleModified = titleModify($title);
                  $content       = $row['content'];
                  $date          = $row['date'];
                  $fileName      = $row['postid'];
                  $category      = $row['category'];
                  $postType      = $row['type'];
                  $allowComments = $row['allowcomments'];
                  $visits        = $row['visits'];
                  if (!in_array($fileName, $searchResults)) {
                      $theme_main['content'].= "<a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified.">".$title."</a><br/>";
                      $i++;
                  }
              }
          }
          if ($i == 0) {$theme_main['content'].= $lang['errorSearchEmptyResult'];}
      }
  }


?>


