<?php

/*#######################################################################
# 	PRITLOG		                                                #
#	                                                                #
#       The idea of this blog, is directly taken from a	                #
#	very simple yet powerful blog software called PPLOG             #
#       (Perl Powered Blog). PPLOG is a creation of			#
#	Federico Ramírez (fedekun) - fedekiller@gmail.com               #
#   	                                                                #
#       I just wanted to experiment with creating		        #
#	a similar blog in PHP. Hence PRITLOG.		                #
#	pritlog@hardkap.com				                #
#							                #
#	PRITLOG now uses the MIT License                                #
#	http://www.opensource.org/licenses/mit-license.php              #
#							                #
#	Powered by YAGNI (You Ain't Gonna Need It)	                #
#	YAGNI: Only add things, when you actually 	                #
#	need them, not because you think you will.	                #
#							                #
#	Version: 0.8                                                    #
#######################################################################*/



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

  readConfig();                                            /* Read the config file and load it into the array */
  require("lang/".$config['blogLanguage'].".php");         /* Load the language file */

  $postdb               = getcwd()."/data/postdb.sqlite";
  $config['postdb']     = $postdb;
  $config['authorFile'] = getcwd(). "/data/authors.php";
  readAuthors();
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
              sqlite_query($config['db'], 'CREATE TABLE posts (title CHAR(100), content CHAR(4500), date DATETIME, postid PRIMARY KEY, category CHAR(20), type CHAR(5), stick CHAR(5), allowcomments CHAR(4), visits INTEGER, author CHAR(25));');
              sqlite_query($config['db'], 'CREATE TABLE comments (commentid INTEGER PRIMARY KEY, postid CHAR(6), sequence INTEGER, author CHAR(25), title CHAR(100), content CHAR(4500), date DATETIME, ip CHAR(16), url CHAR(50), email CHAR(50));');
              sqlite_query($config['db'], 'CREATE TABLE stats (statid INTEGER PRIMARY KEY, stattype CHAR(10), statcount INTEGER);');
              sqlite_query($config['db'], 'CREATE TABLE active_guests (id INTEGER PRIMARY KEY, ip CHAR(16), logtime DATETIME);');
              sqlite_query($config['db'], 'CREATE TABLE active_users (id INTEGER PRIMARY KEY, ip CHAR(16), logtime DATETIME);');
              $stattype  = "total";
              $statcount = 0;
              sqlite_query($config['db'], "INSERT INTO stats (stattype, statcount) VALUES('$stattype', '$statcount');");
          }
       }
  }
  else { die(); }

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
  $data = explode("/",$path1);

  $serverName='http://'.$_SERVER['SERVER_NAME'];
  $serverPort=($_SERVER['SERVER_PORT']=='80')?'':':'.$_SERVER['SERVER_PORT'];
  $scriptName=$_SERVER["SCRIPT_NAME"];
  $blogPath=dirname($serverName.$serverPort.$scriptName);  /* Detect the absolute path to Pritlog */
  if ($config['blogPath'] !== $blogPath) {                 /* Update the absolute path to Pritlog in the config file */
      $config['blogPath'] = $blogPath;
      writeConfig(false);
  }

  $baseScript=basename($scriptName);
  $i=0;
  $optionIndex=1;
  if (is_array($data)) {
      foreach ($data as $value) {
          if (strcmp($value,$baseScript) == 0) {
            $optionIndex=$i+1;
          }
          $i++;
      }
  }
  $option = isset($data[$optionIndex])?$data[$optionIndex]:"mainPage";
  $nicEditType = "default";
  $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  if (file_exists(getcwd().'/nicFile/nicEditorIcons.gif')) {
       $nicEditType = "nicFile";
       $nicEditUrl  = '<script src="'.$blogPath.'/nicFile/nicEdit.js" type="text/javascript"></script>';
  }
  elseif (file_exists(getcwd().'/nicUpload.php')) {
       $nicEditType = "nicUpload";
       $nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
  }
  else {
       $nicEditType = "default";
       //$nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
       $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  }
  /* To get the query string from the pretty urls */
  $optionValue = isset($data[$optionIndex+1])?$data[$optionIndex+1]:"";
  $optionValue2= isset($data[$optionIndex+2])?$data[$optionIndex+2]:"";
  $optionValue3= isset($data[$optionIndex+3])?$data[$optionIndex+3]:"";
  //echo $optionIndex."<br>";
  //echo $option."<br>";
  //echo $optionValue."<br>";
  //echo $optionValue2."<br>";
  //echo $optionValue3."<br>";

  // In seconds. User will be logged out after this
  $inactive = $config['timeoutDuration'];
  // check to see if $_SESSION['timeout'] is set
  $ip = $_SERVER['REMOTE_ADDR'];
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
  $_SESSION['timeout'] = time();

   $mypath  =isset($_SERVER['PATH_INFO'])?str_replace("/index.php","",$_SERVER['PATH_INFO']):"";
   //$referrer=$blogPath.'/index.php'.$mypath;
   $referrer=$serverName.$_SERVER['REQUEST_URI'];

   if ($option == "mainPage") { $_SESSION['url']=$referrer; }
   $accessArray=array('newEntry', 'newEntryForm', 'newEntrySubmit', 'deleteEntry', 'editEntry', 'editEntryForm', 'editEntrySubmit', 'deleteComment', 'myProfile', 'myProfileSubmit');
   if (in_array($option,$accessArray)) {
      if (!$ss->Check() || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
           $_SESSION['notice']="";
           $_SESSION['url']=$referrer;
           $_SESSION['access_type']="regular";
           header('Location: '.$_SERVER["SCRIPT_NAME"].'/loginPage');
           die;
      }
   }
   $adminAccessArray=array('adminPage', 'adminPageBasic', 'adminPageBasicSubmit', 'adminPageAdvanced', 'adminPageAdvancedSubmit', 'adminPageAuthors', 'adminAuthorsAdd', 'adminAuthorsEdit');
   if (in_array($option, $adminAccessArray)) {
      if (!$ss->Check() || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !$_SESSION['isAdmin']) {
           $_SESSION['notice']="";
           $_SESSION['url']=$referrer;
           $_SESSION['access_type']="admin";
           header('Location: '.$_SERVER["SCRIPT_NAME"].'/loginPage');
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


if($option == 'RSS')
{
      createRSS();
}
else
{

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="Keywords" content="<?php echo $config['metaKeywords']; ?>"/>
<meta name="Description" content="<?php echo $config['metaDescription']; ?>"/>
<title>
<?php
if (trim($optionValue2) == "") { $postTitle=""; $postTitleSave="";}
else {
  $postTitle    =str_replace("  "," ",str_replace("-"," ",$optionValue2))." &laquo; ";
  $postTitleSave=str_replace("  "," ",str_replace("-"," ",$optionValue2))." | ";
}

echo $postTitle.$config['blogTitle'];

?>
</title>
<link rel="alternate" type="application/rss+xml" title="Recent Posts" href="<?php echo $_SERVER["SCRIPT_NAME"].'/RSS/'.$optionValue ?>" />

<!-- Blueprint CSS Framework -->
	<link rel="stylesheet" href="<?php echo $blogPath.'/css/screen.css' ?>" type="text/css" media="screen, projection">
	<link rel="stylesheet" href="<?php echo $blogPath.'/css/print.css' ?>" type="text/css" media="print">
<!--[if IE]><link rel="stylesheet" href="<?php echo $blogPath.'/css/ie.css' ?>" type="text/css" media="screen, projection"><![endif]-->

<link href="<?php echo $blogPath.'/css/style.css' ?>" rel=stylesheet type=text/css>
<?php
  echo '<script type="text/javascript">var blogPath="'.$blogPath.'";</script>';
?>
<script src="<?php echo $blogPath.'/javascripts/livevalidation.js' ?>" type="text/javascript"></script>

</head>

<body id="noticebd">

<div class="container">

<!-- Below is the header portion of the blog -->

<div id="myhead" class="span-24">
<h1><a href=<?php echo $_SERVER["SCRIPT_NAME"].'/mainPage>' ?><?php echo $config['blogTitle']; ?></a></h1>
</div>

<div class="span-24">
     <?php 
           if (is_array($tags) && count($tags) > 0 && $config['showCategoryCloud'] == 1) {
                printTagCloudAgain($tags);
           }
     ?>
</div>


<div id="all" class="span-24">


<!-- Left Sidebar -->

<?php

if (trim($_SESSION['notice']) !== "") {
?>

<script src="<?php echo $blogPath.'/javascripts/addremove.js' ?>" type="text/javascript"></script>
<script type="text/javascript">
Event.add(window, 'load', function() {
  var i = 0;
  var el = document.createElement('p');
  el.innerHTML = "<strong><?php echo $_SESSION['notice'] ?></strong>";
  el.setAttribute("id","notice");
  el.setAttribute("class","error");
  Dom.add(el, 'noticehd');
  var t=setTimeout("Dom.remove('notice');",3300);
  /*
  Event.add('add-element', 'click', function() {
    var el = document.createElement('p');
    el.innerHTML = 'Remove This Element (' + ++i + ')';
    Dom.add(el, 'h3');
    Event.add(el, 'click', function(e) {
      Dom.remove(this);
    });
  }); */
});
</script>
<?php 
unset($_SESSION['notice']);
} 
?>



<!-- Main content - that has the posts begins here -->

<div id="content" class="span-16">

<div id="noticehd"></div>
<?php

}

/* This function does the main logic to direct to other functions as required */
mainLogic();

if($option !== 'RSS') {
?>
</div>

<!-- Right sidebar -->

<div class="span-6">

<div id="menu" class="span-6">

<div class="span-6  last">
<br>
<style>
#shareButton {font:12px Verdana, Helvetica, Arial; height: 30px;width:100px;}
#shareDrop {position:absolute; padding:10px; display: none; z-index: 100; top:-900px; left:0px; width: 200px;float:left;background: #E9E9E9;border:1px solid black;}
#shareButton img, #shareDrop img {border:0} #shareDrop a {color:#008DC2; padding:0px 5px;display:block;text-decoration:none;} #shareDrop a:hover {background-color: #999999; color: #fff; text-decoration:none;}
#shareshadow{position: absolute;left: 0; top: 0; z-index: 99; background: black; visibility: hidden;}
div.sharefoot {position: absolute; top: 172px; height:15px; width: 200px; text-align: center; background-color: #999999; color: #fff;}
div.sharefoot a{display:inline; color:#fff; background-color:#999999; } div.sharefoot a:hover{text-decoration:none; background: #00adef; color: #fff}
</style>
<script type="text/javascript">
<?php
echo 'var bPath="'.$blogPath.'/images/bookmarks";';
//echo 'var u1   ="'.urlencode($blogPath).'";';
echo 'var u1   =encodeURIComponent(document.location.href);';
echo 'var t1   ="'.urlencode($postTitleSave.$config['blogTitle']).'";';
?>
</script>

<style>

div #shareButton a { background: url("<?php echo $blogPath.'/images/shareme.gif'; ?>") no-repeat; }
div .share a span { cursor:pointer; display:block; margin-left:15px; color:#008DC2; padding:0px 5px; height:16px; width: 60px; text-decoration:none;}
div .share a span:hover {background-color: #999999; color: #fff; text-decoration:none;}

div .shareit { background: url("<?php echo $blogPath.'/images/shareme.gif'; ?>") no-repeat;}

</style>

<?php echo '<script type="text/javascript" src="'.$blogPath.'/javascripts/shareme.js"></script>'; ?>


</div>

<div class="span-6  last">
    <br/>
    <form name="form1" method="post" id="searchform" action=<?php echo $_SERVER['SCRIPT_NAME']; ?>/searchPosts>
    <input type="text" class="s" name="searchkey">
    <input type="hidden" name="do" value="search">
    <input type="submit" class="submit" name="Submit" value="Search"><br />
    </form>
</div>

<div  class="span-6 last">
<h3><?php echo $lang['sidebarHeadLatestEntries']; ?></h3>
    <?php sidebarListEntries(); ?>
</div>

<div  class="span-6  last">
<h3><?php echo $lang['sidebarHeadMainMenu']; ?></h3>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/mainPage>'.$lang['sidebarLinkHome']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/viewArchive>'.$lang['sidebarLinkArchive']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/RSS>'.$lang['sidebarLinkRSSFeeds']; ?></a>
<?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && (isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)) { ?>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/adminPage>'.$lang['sidebarLinkAdmin']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/newEntry>'.$lang['sidebarLinkNewEntry']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/logoutPage>'.$lang['sidebarLinkLogout']; ?></a>
<?php } else { ?>
<?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) { ?>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/newEntry>'.$lang['sidebarLinkNewEntry']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/myProfile>'.$lang['pageMyProfile']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/logoutPage>'.$lang['sidebarLinkLogout']; ?></a>
<?php } else { ?>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/loginPage>'.$lang['sidebarLinkLogin']; ?></a>
<?php } ?>
<?php } ?>
</div>

<div  class="span-6  last">
<h3><?php echo $lang['sidebarHeadCategories']; ?></h3>
<?php sidebarCategories(); ?>
</div>

<div class="span-6  last">
<h3><?php echo $lang['sidebarHeadPages']; ?></h3>
<?php sidebarPageEntries() ?>
</div>

<div  class="span-6  last">
<h3><?php echo $lang['sidebarHeadLinks']; ?></h3>
<?php sidebarLinks(); ?>
</div>

<div  class="span-6  last">
<h3><?php echo $lang['sidebarHeadStats']; ?></h3>
<?php sidebarStats() ?>
</div>


</div>

</div>

<div id="foot" class="span-24">

<div  class="span-7  prepend-1 append-1">
<h3><?php echo $lang['sidebarHeadPopularEntries']; ?></h3>
    <?php sidebarPopular();  ?>
</div>

<div  class="span-7 append-1">
<h3><?php echo $lang['sidebarHeadLatestComments']; ?></h3>
<?php
      sidebarListComments();
      echo '<a href="'.$_SERVER['SCRIPT_NAME'].'/listAllComments">'.$lang['sidebarLinkListComments'].'</a>';
?>
</div>

<?php if (strlen($config['about']) > 10) { ?>
<div  class="span-6">
<h3><?php echo $lang['pageBasicConfigAbout']; ?></h3>
<?php echo $config['about']; ?>
</div>
<?php } ?>

</div>

</div>



<?php /* PLEASE DONT REMOVE THIS COPYRIGHT WITHOUT PERMISSION FROM THE AUTHOR */ ?>
<?php sqlite_close($config['db']); echo '<div id="footer">'.$lang['footerCopyright'].' '.$config['blogTitle'].' '.date('Y').' - '.$lang['footerRightsReserved'].' - Powered by <a href="http://hardkap.net/pritlog/">Pritlog</a></div>'; ?>

</div>

</body>
</html>
<?php } ?>



<?php

  function mainLogic() {

      global $debugMode,$option,$requestCategory,$optionValue;

      //$category = $data[4];

      switch ($option) {
      case "newEntry":
          if ($debugMode=="on") {echo "Calling newEntryPass()";}
          newEntryForm();
          break;
      case "newEntryForm":
          if ($debugMode=="on") {echo "Calling newEntryForm()";}
          newEntryForm();
          break;
      case "newEntrySubmit":
          newEntrySubmit();
          break;
      case "mainPage":
          $requestCategory = '';
          listPosts();
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
      case "editEntry":
          if ($debugMode=="on") {echo "editEntry  ".$_POST['process']."<br>";}
          editEntryForm();
          break;
      case "editEntryForm";
          editEntryForm();
          break;
      case "editEntrySubmit";
          editEntrySubmit();
          break;
      case "viewEntry":
          viewEntry();
          break;
      case "viewArchive":
          viewArchive();
          break;
      case "viewArchiveMonth":
          viewArchiveMonth();
          break;
      case "viewCategory":
          $requestCategory=$optionValue;
          listPosts();
          break;
      case "searchPosts":
          searchPosts();
          break;
      case "sendComment":
          sendComment();
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
      }
  }

  function logout() {
      global $blogPath, $lang;
      unset($_SESSION['logged_in']);
      unset($_SESSION['username']);
      unset($_SESSION['isAdmin']);
      unset($_SESSION['access_type']);
      unset($_SESSION['loginError']);
      unset($_SESSION['ss_fprint']);
      header('Location: '.$_SESSION['url']);
      die();
      //unset($_SESSION['url']);
  }

  function logoutPage() {
      global $blogPath, $lang;
      echo "<h3>".$lang['titleLogoutPage']."</h3>";
      echo $lang['loggedOut'].'<br>';
  }

  function loginPage() {
      global $debugMode, $optionValue, $config, $lang;
      echo "<h3>".$lang['titleLoginPage']."</h3>";
      echo "<form name=\"form1\" method=\"post\" action=\"".$_SERVER['SCRIPT_NAME']."/loginPageSubmit\">";
      echo "<table>";
      echo "<tr><td>".$lang['pageAuthorsNew']."</td>";
      echo "<td><input class=\"s\" name=\"author\" type=\"text\" id=\"author\">&nbsp;&nbsp;('admin' for master user)</td></tr>";
      echo '<script>';
      echo 'var author = new LiveValidation( "author", {onlyOnSubmit: true } );';
      echo 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
      echo '</script>';
      echo "<tr><td>".$lang['pageDeletePass']."</td>";
      echo "<td><input class=\"s\" name=\"pass\" type=\"password\" id=\"pass\"></td></tr>";
      echo '<script>';
      echo 'var pass = new LiveValidation( "pass", {onlyOnSubmit: true } );';
      echo 'pass.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
      echo '</script>';
      echo '</tr><tr><td>&nbsp;</td><td><input type="submit" class="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'"></td>';
      echo "</tr></table></form>";
      echo '[<a href="'.$_SERVER['SCRIPT_NAME'].'/forgotPass">'.$lang['titleForgotPass'].'?</a>]';
      if ($config['allowRegistration'] == 1) {
          echo '<br>Not registered?&nbsp;<a href="'.$_SERVER['SCRIPT_NAME'].'/registerPage">'.$lang['titleRegisterPageSubmit'].'!</a><br>';
      }
      if (isset($_SESSION["loginError"])) {
           echo '<br>'.$_SESSION["loginError"].'<br>';
           unset($_SESSION["loginError"]);
      }
  }

  function loginPageSubmit() {
      global $debugMode, $optionValue, $config, $lang, $baseScript, $authorsPass, $ss, $authorsActStatus;
      $loginError=false;
      $authorsActStatus['admin'] = 1;
      //echo 'yes - '.$_SESSION['url'].' - '.$_POST['author'].' - '.$_POST['pass'].' - '.$authorsPass[$_POST['author']].' - '.$authorsActStatus[$_POST['author']]; die();
      if ((md5($config['randomString'].$_POST['pass']) === $authorsPass[$_POST['author']]) && ($authorsActStatus[$_POST['author']] == 1)) {
         $ss->Open();
         $_SESSION['logged_in'] = true;
         $_SESSION['username']  = $_POST['author'];
         if ($_POST['author'] == "admin") {
            $_SESSION['isAdmin'] = true;
            $_SESSION['access_type'] = "admin";
         }
         else {
           if ($_SESSION['access_type'] == "admin" || $authorsActStatus[$_POST['author']] == 0) {
               $_SESSION["loginError"] = $lang['errorUserPassIncorrect'];
               $loginError=true;
           }
           $_SESSION['access_type'] = "regular";
           $_SESSION["loginError"]  = $lang['errorNotAuthorized'];
         }
         header('Location: '.$_SESSION['url']);
      }
      else {
         $_SESSION["loginError"] = $lang['errorUserPassIncorrect'];
         header('Location: '.$baseScript.'/loginPage');
      }
  }

  function myProfile() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      echo "<h3>".$lang['pageMyProfile']."</h3>";
      if ((isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) && !(isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/myProfileSubmit>";
          echo "<fieldset>";
          echo '<legend>'.$lang['pageMyProfile'].'</legend>';
          echo '<p><label for="origpass">'.$lang['pageMyProfileCurrentPass'].'</label><br>';
          echo '<input type="password" class="ptext" name="origpass" id="origpass" value=""></p>';
          echo '<script>';
          echo 'var origpass = new LiveValidation( "origpass", {onlyOnSubmit: true } );';
          echo 'origpass.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo '</script>';
          echo '<p><label for="newpass1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>';
          echo '<script>';
          echo 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
          echo 'pass1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
          echo '</script>';
          echo '<p><label for="newpass2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>';
          echo '<script>';
          echo 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
          echo 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
          echo '</script>';
          echo '<p><label for="authorEmail">'.$lang['pageAuthorsNewEmail'].'</label><br>';
          echo '<input type="text" class="ptext" name="authorEmail" id="authorEmail" value="'.$authorsEmail[$_SESSION['username']].'"></p>';
          echo '<script>';
          echo 'var email = new LiveValidation( "authorEmail", {onlyOnSubmit: true } );';
          echo 'email.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
          echo '</script>';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '<p><input type="submit" value="'.$lang['pageAdvancedConfigSubmit'].'"></p>';
          echo '</fieldset>';
          echo '</form>';
      }
      else { echo $lang['errorInvalidRequest'].'<br>'; }
  }

  function myProfileSubmit() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $authorsActCode, $authorsActStatus;
      $authorFileName=$config['authorFile'];
      echo "<h3>".$lang['pageMyProfile']."</h3>";
      $do = 1;
      if ((isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) && !(isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)) {
          $authorEmail=$_POST['authorEmail'];
          $addAuthor  =$_SESSION['username'];
          if (trim($authorEmail) == "") {
               echo $lang['errorAllFields'].'<br>';
               $do = 0;
          }
          if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($authorEmail))) {
               echo $lang['errorInvalidAdminEmail'].'<br>';
               $do = 0;
          }
          if ($_POST['newpass1'] != $_POST['newpass2']) {
               echo $lang['errorNewPasswordsMatch']."<br>";
               $do = 0;
          }
          if (strtolower(trim($addAuthor)) == "admin") {
               echo $lang['errorForbiddenAuthor'].'<br>';
               $do = 0;
          }
          if (isset($_POST['newpass1']) && trim($_POST['newpass1']) != "" && strlen($_POST['newpass1']) < 5) {
              echo $lang['errorPassLength'].'<br>';
              $do = 0;
          }
          if (md5($config['randomString'].$_POST['origpass']) !== $authorsPass[$addAuthor]) {
              echo $lang['errorPasswordIncorrect'].'<br>';
              $do = 0;
          }
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
              echo '<br>'.$lang['msgConfigSaved'].'.';
              echo '<br>'.$lang['msgConfigLoginAgain'].'.';
          }
      }
      else { echo $lang['errorInvalidRequest'].'<br>'; }
  }

  function registerPage() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      echo "<h3>".$lang['titleRegisterPage']."</h3>";
      if (!isset($_SESSION['logged_in']) && !(isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) && ($config['allowRegistration'] == 1)) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/registerPageSubmit>";
          echo "<fieldset>";
          echo '<legend>'.$lang['titleRegisterPage'].'</legend>';
          echo '<p><label for="addAuthor">'.$lang['pageAuthorsNew'].'</label><br>';
          echo '<input type="text" class="ptext" name="addAuthor" id="addAuthor" value=""></p>';
          $authorsList="";
          foreach ($authors as $value) {
              $authorsList.='"'.$value.'" , ';
          }
          echo '<script>';
          echo 'var author = new LiveValidation( "addAuthor", {onlyOnSubmit: true } );';
          echo 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'author.add( Validate.Exclusion, { within: [ '.$authorsList.' ] , failureMessage: "'.$lang['errorDuplicateAuthor'].'"  } );';
          echo '</script>';
          echo '<p><label for="newpass1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>';
          echo '<script>';
          echo 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
          echo 'pass1.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'pass1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
          echo '</script>';
          echo '<p><label for="newpass2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>';
          echo '<script>';
          echo 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
          echo 'pass2.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
          echo '</script>';
          echo '<p><label for="authorEmail">'.$lang['pageAuthorsNewEmail'].'</label><br>';
          echo '<input type="text" class="ptext" name="authorEmail" id="authorEmail" value=""></p>';
          echo '<script>';
          echo 'var email = new LiveValidation( "authorEmail", {onlyOnSubmit: true } );';
          echo 'email.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
          echo '</script>';
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
	       echo '<p><label for="code">'.$lang['pageCommentsCode'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$code.')</font><br>';
	       echo '<input name="code" class="s" type="text" id="code"></p>';
               echo '<input name="originalCode" value="'.$code.'" type="hidden" id="originalCode">';
          }
          echo '<p><input type="submit" class="submit" value="'.$lang['titleRegisterPageSubmit'].'"></p>';
          echo '</fieldset>';
          echo '</form>';
      }
      else { echo $lang['errorInvalidRequest'].'<br>'; }
  }

  function registerPageSubmit() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $blogPath, $authorsActCode, $authorsActStatus;
      $authorFileName=$config['authorFile'];
      echo "<h3>".$lang['titleRegisterPage']."</h3>";
      $do = 1;
      if (!isset($_SESSION['logged_in']) && !$_SESSION['logged_in'] && ($config['allowRegistration'] == 1)) {
          $authorEmail=$_POST['authorEmail'];
          $addAuthor=$_POST['addAuthor'];
          if (isset($authorsPass[$addAuthor])) {
               echo $lang['errorDuplicateAuthor'].'<br>';
               $do = 0;
          }
          if (trim($addAuthor) == "" || trim($authorEmail) == "") {
               echo $lang['errorAllFields'].'<br>';
               $do = 0;
          }
          if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($authorEmail))) {
               echo $lang['errorInvalidAdminEmail'].'<br>';
               $do = 0;
          }
          if ($_POST['newpass1'] != $_POST['newpass2']) {
               echo $lang['errorNewPasswordsMatch']."<br>";
               $do = 0;
          }
          if (strtolower(trim($addAuthor)) == "admin") {
               echo $lang['errorForbiddenAuthor'].'<br>';
               $do = 0;
          }
          if (strlen($_POST['newpass1']) < 5) {
              echo $lang['errorPassLength'].'<br>';
              $do = 0;
          }
          if($config['commentsSecurityCode'] == 1)
	  {
	      $code         = isset($_POST['code'])?$_POST['code']:"";
 	      $originalCode = isset($_POST['originalCode'])?$_POST['originalCode']:"";
	      if ($code !== $originalCode)
	      {
	   	  echo $lang['errorSecurityCode'].'<br>';
		  $do = 0;
              }
	  }
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
                   echo '<br>'.$lang['titleRegisterThank'].'.';
             }
             else {
                 echo '<br>'.$lang['msgMail9'].'.<br>';
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
              echo $lang['errorPleaseGoBack'];
          }

       }
       else { echo $lang['errorInvalidRequest'].'<br>'; }
  }

  function forgotPass() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      echo "<h3>".$lang['titleForgotPass']."</h3>";
      if (!isset($_SESSION['logged_in']) && !(isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false)) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/forgotPassSubmit>";
          echo "<fieldset>";
          echo '<legend>'.$lang['titleForgotPass'].'</legend>';
          echo '<p><label for="addAuthor">'.$lang['pageAuthorsNew'].'</label><br>';
          echo '<input type="text" class="ptext" name="addAuthor" id="addAuthor" value=""></p>';
          $authorsList="";
          foreach ($authors as $value) {
              $authorsList.='"'.$value.'" , ';
          }
          echo '<script>';
          echo 'var author = new LiveValidation( "addAuthor", {onlyOnSubmit: true } );';
          echo 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'author.add( Validate.Inclusion, { within: [ '.$authorsList.' ] , failureMessage: "'.$lang['errorUserNotFound'].'"  } );';
          echo '</script>';
          echo '<p><input type="submit" class="submit" value="'.$lang['pageBasicConfigSubmit'].'"></p>';
          echo '</fieldset>';
          echo '</form>';
      }
      else { echo $lang['errorInvalidRequest'].'<br>'; }
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
      $authorFileName=$config['authorFile'];
      echo "<h3>".$lang['titleForgotPass']."</h3>";
      $do = 1;
      if (!isset($_SESSION['logged_in']) && !in_array('"'.$_POST['addAuthor'].'"', $authors)) {
          $addAuthor=$_POST['addAuthor'];
          $addEmail =$authorsEmail[$addAuthor];
          if (trim($addAuthor) == "") {
               echo $lang['errorAllFields'].'<br>';
               $do = 0;
          }
          if (strtolower(trim($addAuthor)) == "admin") {
               echo $lang['errorForbiddenAuthor'].'<br>';
               $do = 0;
          }
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
                   echo '<br>'.$lang['titleForgotPassMsg'];
             }
             else {
                 echo '<br>'.$lang['msgMail9'].'.<br>';
             }
          }
          else {
              echo $lang['errorPleaseGoBack'];
          }
       }
       else {
          echo $lang['errorInvalidRequest']."<br>";
       }
  }


  function activation() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $serverName, $authorsActCode, $authorsActStatus;
      global $optionValue, $optionValue2;
      $authorFileName=$config['authorFile'];
      $author  = trim($optionValue);
      $actCode = trim($optionValue2);
      echo "<h3>".$lang['titleRegisterPage']."</h3>";
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
                  echo $lang['titleRegisterActive'].'<br>';
              }
              else { echo $lang['titleRegisterAlready'].'<br>'; }
          }
          else { echo $lang['errorInvalidRequest'].'<br>'; }

      }
      else { echo $lang['errorInvalidRequest'].'<br>'; }
  }

  function adminPage() {
      global $debugMode, $optionValue, $config, $lang;
      echo "<h3>".$lang['titleAdminPage']."</h3>";

      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          echo '<p><form method="post" class="adminPage" action="'.$_SERVER['SCRIPT_NAME'].'/adminPageBasic">';
          echo '<input type="submit" class="submit" value="'.$lang['pageBasicConfig'].'">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '</form><br>';
          echo '<form method="post" class="adminPage" action="'.$_SERVER['SCRIPT_NAME'].'/adminPageAdvanced">';
          echo '<input type="submit" class="submit" value="'.$lang['pageAdvancedConfig'].'">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '</form><br>';
          echo '<form method="post" class="adminPage" action="'.$_SERVER['SCRIPT_NAME'].'/adminPageAuthors">';
          echo '<input type="submit" class="submit" value="'.$lang['pageAuthorsManage'].'">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '</form></p>';
          echo '<br><br><h3>Pritlog Version</h3>';
          //echo 'You are using Pritlog 0.8 Beta 1<br>Thank you for testing!<br>';
          echo '<script type="text/javascript">';
          echo 'var clientVersion=0.8;';
          echo '</script>';
          echo '<script src="http://hardkap.net/pritlog/checkversion.8.js" type="text/javascript"></script>';
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br>';
       }
  }

  function adminPageBasic() {
      global $debugMode, $optionValue, $config, $lang;
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminPageBasicSubmit>";
          echo "<fieldset>";
          echo '<legend>'.$lang['pageBasicConfig'].'</legend>';
          echo '<p><label for="title">'.$lang['pageBasicConfigTitle'].'</label><br>';
          echo '<input type="text" class="ptitle" name="title" id="title" value="'.$config['blogTitle'].'"></p>';
          echo '<script>';
          echo 'var title = new LiveValidation( "title", {onlyOnSubmit: true } );';
          echo 'title.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo '</script>';
          echo '<p><label for="newpass1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>';
          echo '<script>';
          echo 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
          echo '</script>';
          echo '<p><label for="newpass2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>';
          echo '<script>';
          echo 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
          echo 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
          echo '</script>';
          echo '<p><label for="adminEmail">'.$lang['pageBasicConfigAdminEmail'].'</label><br>';
          echo '<input type="text" class="ptext" name="adminEmail" id="adminEmail" value="'.$config['sendMailWithNewCommentMail'].'"></p>';
          echo '<script>';
          echo 'var email = new LiveValidation( "adminEmail", {onlyOnSubmit: true } );';
          echo 'email.add( Validate.Presence, { failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
          echo '</script>';
          echo '<br><label for="posts">'.$lang['pageBasicConfigAbout'].'</label><br>';
          nicEditStuff();
          echo '<textarea name="posts" id="posts">'.$config['about'].'</textarea><br><br>';  /* this is actually about. not posts. Dont be mislead */
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '<p><input type="submit" class="submit" value="'.$lang['pageBasicConfigSubmit'].'"></p>';
          echo '</fieldset>';
          echo '</form>';
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }


  function adminPageBasicSubmit() {
       global $config, $lang;
       echo "<h3>".$lang['titleAdminPage']."</h3>";

      $do=1;
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          if (trim($_POST['title']) == "" || trim($_POST['adminEmail']) == "" || trim($_POST['posts']) == "") {
              echo $lang['errorCannotBeSpaces'].'<br>';
              echo '<li>'.$lang['pageBasicConfigTitle'].'</li>';
              echo '<li>'.$lang['pageBasicConfigAdminEmail'].'</li>';
              echo '<li>'.$lang['pageBasicConfigAbout'].'</li>';
              $do=0;
          }
          if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($_POST['adminEmail']))) {
              echo '<br>'.$lang['errorInvalidAdminEmail'].'<br>';
              $do=0;
          }
          if (trim($_POST['newpass1'])!="" || trim($_POST['newpass2'])!="") {
              if (strcmp($_POST['newpass1'],$_POST['newpass2']) != 0) {
                  echo '<br>'.$lang['errorNewPasswordsMatch'].'<br>';
                  $do=0;
              }
              else {
                  $config['Password']=md5($config['randomString'].$_POST['newpass1']);
              }
          }
          if ($do == 0) {
              echo '<br>'.$lang['errorPleaseGoBack'].'<br>';
          }
          else {
              $config['blogTitle']=trim($_POST['title']);
              $config['sendMailWithNewCommentMail']=trim($_POST['adminEmail']);
              $config['about']=str_replace("\\","",trim($_POST['posts']));
              writeConfig();
          }
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }

  function adminPageAdvanced() {
      global $debugMode, $optionValue, $config, $lang;
      echo "<h3>".$lang['titleAdminPage']."</h3>";


      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminPageAdvancedSubmit>";
          echo "<fieldset>";
          echo '<legend>'.$lang['pageAdvancedConfig'].'</legend>';
          echo '<p><label for="metaDesc">'.$lang['pageAdvancedConfigMetaDesc'].'</label><br>';
          echo '<input type="text" class="ptext" name="metaDesc" id="metaDesc" value="'.$config['metaDescription'].'"></p>';
          echo '<p><label for="metaKeywords">'.$lang['pageAdvancedConfigMetaKey'].'</label><br>';
          echo '<input type="text" class="ptext" name="metaKeywords" id="metaKeywords" value="'.$config['metaKeywords'].'"></p>';

          echo '<p><label for="commentsMaxLength">'.$lang['pageAdvancedConfigCommentsLen'].'</label><br>';
          echo '<input type="text" class="ptext" name="commentsMaxLength" id="commentsMaxLength" value="'.$config['commentsMaxLength'].'"></p>';
          echo '<p><label for="commentsForbiddenAuthors">'.$lang['pageAdvancedConfigCommentsFor'].'</label><br>';
          echo '<input type="text" class="ptext" name="commentsForbiddenAuthors" id="commentsForbiddenAuthors" value="'.$config['commentsForbiddenAuthors'].'"></p>';
          echo '<p><label for="statsDontLog">'.$lang['pageAdvancedConfigDontLog'].'</label><br>';
          echo '<input type="text" class="ptext" name="statsDontLog" id="statsDontLog" value="'.$config['statsDontLog'].'"></p>';
          echo '<p><label for="entriesOnRSS">'.$lang['pageAdvancedConfigEntriesRSS'].'</label><br>';
          echo '<input type="text" class="ptext" name="entriesOnRSS" id="entriesOnRSS" value="'.$config['entriesOnRSS'].'"></p>';
          echo '<p><label for="entriesPerPage">'.$lang['pageAdvancedConfigPostsperPage'].'</label><br>';
          echo '<input type="text" class="ptext" name="entriesPerPage" id="entriesPerPage" value="'.$config['entriesPerPage'].'"></p>';
          echo '<p><label for="menuEntriesLimit">'.$lang['pageAdvancedConfigMenuEntries'].'</label><br>';
          echo '<input type="text" class="ptext" name="menuEntriesLimit" id="menuEntriesLimit" value="'.$config['menuEntriesLimit'].'"></p>';
          echo '<p><label for="timeoutDuration">'.$lang['timeoutDuration'].'</label><br>';
          echo '<input type="text" class="ptext" name="timeoutDuration" id="timeoutDuration" value="'.$config['timeoutDuration'].'"></p>';


          echo '<p><label for="blogLanguage">'.$lang['pageAdvancedConfigLanguage'].'</label><br>';
          echo '<select name="blogLanguage" id="blogLanguage">';
          $languageDir=getcwd()."/lang";
          if (file_exists($languageDir)) {
              if ($handle = opendir($languageDir)) {
                  $file_array_unsorted = array();
                  while (false !== ($file = readdir($handle))) {
                      if (substr($file,strlen($file)-4) == ".php") {
                          $language=substr($file,0,strlen($file)-4);
                          //echo substr($file,0,strlen($file)-4).'<br>';
                          ($config['blogLanguage'] == $language)?$selected="selected":$selected="";
                          echo '<option value="'.$language.'" '.$selected.' >'.$language;
                      }
                  }
                  closedir($handle);
              }
          }

          echo '</select>';

          $menuLinks="";
          if (is_array($config['menuLinksArray'])) {
              foreach ($config['menuLinksArray'] as $value) {
                  $menuLinks=$menuLinks."\n".$value;
              }
          }
          echo '<p><label for="menuLinks">'.$lang['pageAdvancedConfigMenuLinks'].'</label><br>';
          echo '<textarea name="menuLinks" id="menuLinks" rows="5" cols="25" value="">'.$menuLinks.'</textarea></p>';
          if ($config['sendMailWithNewComment'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="sendMailComments" value="1" '.$checking.'>'.$lang['pageAdvancedConfigSendMail'].'</a><br>';
          if ($config['commentsSecurityCode'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="commentsSecurityCode" value="1" '.$checking.'>'.$lang['pageAdvancedConfigSecCode'].'</a><br>';
          if ($config['authorEditPost'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="authorEditPost" value="1" '.$checking.'>'.$lang['pageAdvancedConfigAuthorEdit'].'</a><br>';
          if ($config['authorDeleteComment'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="authorDeleteComment" value="1" '.$checking.'>'.$lang['pageAdvancedConfigAuthorComment'].'</a><br>';
          if ($config['showCategoryCloud'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="showCategoryCloud" value="1" '.$checking.'>'.$lang['pageAdvancedConfigCatCloud'].'</a><br>';
          if ($config['allowRegistration'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="allowRegistration" value="1" '.$checking.'>'.$lang['pageAdvancedConfigRegister'].'</a><br>';
          if ($config['sendRegistMail'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="sendRegistMail" value="1" '.$checking.'>'.$lang['pageAdvancedConfigRegistMail'].'</a></p>';
          echo '<input name="process" type="hidden" id="process" value="adminPageAdvancedSubmit">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '<br><br><p><input type="submit" class="submit" value="'.$lang['pageAdvancedConfigSubmit'].'"></p>';
          echo '</fieldset>';
          echo '</form>';
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br>';
       }
  }



  function adminPageAdvancedSubmit() {
       global $config, $lang;
       echo "<h3>".$lang['titleAdminPage']."</h3>";

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

          if (isset($_POST['menuLinks'])) {
               $config['menuLinks']=$config['menuLinksOrig']=str_replace("\r\n",";",$_POST['menuLinks']);
          }

          if (isset($_POST['blogLanguage'])) {
               $config['blogLanguage']=$_POST['blogLanguage'];
          }
          setConfigDefaults();
          writeConfig();
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }

  function adminPageAuthors() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          echo "<form method=\"post\" class=\"adminPage\" action=".$_SERVER['SCRIPT_NAME']."/adminAuthorsAdd>";
          echo "<fieldset>";
          echo '<legend>'.$lang['pageAuthorsAdd'].'</legend>';
          echo '<p><label for="addAuthor">'.$lang['pageAuthorsNew'].'</label><br>';
          echo '<input type="text" class="ptext" name="addAuthor" id="addAuthor" value=""></p>';
          $authorsList="";
          foreach ($authors as $value) {
              $authorsList.='"'.$value.'" , ';
          }
          $authorsList.='"admin"';
          echo '<script>';
          echo 'var author = new LiveValidation( "addAuthor", {onlyOnSubmit: true } );';
          echo 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'author.add( Validate.Exclusion, { within: [ '.$authorsList.' ] , failureMessage: "'.$lang['errorDuplicateAuthor'].'"  } );';
          echo '</script>';
          echo '<p><label for="newpass1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>';
          echo '<script>';
          echo 'var pass1 = new LiveValidation( "newpass1", {onlyOnSubmit: true } );';
          echo 'pass1.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'pass1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
          echo '</script>';
          echo '<p><label for="newpass2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>';
          echo '<script>';
          echo 'var pass2 = new LiveValidation( "newpass2", {onlyOnSubmit: true } );';
          echo 'pass2.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'pass2.add( Validate.Confirmation,{ match: "newpass1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
          echo '</script>';
          echo '<p><label for="authorEmail">'.$lang['pageAuthorsNewEmail'].'</label><br>';
          echo '<input type="text" class="ptext" name="authorEmail" id="authorEmail" value=""></p>';
          echo '<script>';
          echo 'var email = new LiveValidation( "authorEmail", {onlyOnSubmit: true } );';
          echo 'email.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
          echo 'email.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
          echo '</script>';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '<p><input type="submit" class="submit" value="'.$lang['pageAuthorsAdd'].'"></p>';
          echo '</fieldset>';
          echo '</form>';

          if (is_array($authors)) {
              $i = 0;
              foreach ($authors as $value) {
                  echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminAuthorsEdit>";
                  echo "<fieldset>";
                  echo '<legend>'.$lang['pageAuthorsManage'].'</legend>';
                  echo '<table>';
                  echo '<tr><td><strong>Author: </strong>'.$value.'<br><br></td>';
                  echo '<td><label for="authorEmail">'.$lang['pageAuthorsNewEmail'].'</label><br>';
                  echo '<input type="text" name="authorEmail" id="authorEmail" value="'.$authorsEmail[$value].'"></td></tr>';
                  echo '<tr><td><label for="newpass'.$i.'1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
                  echo '<input type="password" name="newpass'.$i.'1" id="newpass'.$i.'1" value=""></td>';
                  echo '<script>';
                  echo 'var pass'.$i.'1 = new LiveValidation( "newpass'.$i.'1", {onlyOnSubmit: true } );';
                  //echo 'pass'.$i.'1.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
                  echo 'pass'.$i.'1.add( Validate.Length, { minimum: 5 , failureMessage: "'.$lang['errorPassLength'].'" } );';
                  echo '</script>';
                  echo '<td><label for="newpass'.$i.'2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
                  echo '<input type="password" name="newpass'.$i.'2" id="newpass'.$i.'2" value=""></td></tr>';
                  echo '<script>';
                  echo 'var pass'.$i.'2 = new LiveValidation( "newpass'.$i.'2", {onlyOnSubmit: true } );';
                  echo 'pass'.$i.'2.add( Validate.Confirmation,{ match: "newpass'.$i.'1", failureMessage: "'.$lang['errorNewPasswordsMatch'].'" } );';
                  echo '</script>';
                  echo '</table>';
                  echo '<input type="submit" value="'.$lang['postFtEdit'].'">&nbsp;&nbsp;';
                  echo '<input type="checkbox" name="deleteAuthor" value="1">'.$lang['pageAuthorsDelete'];
                  echo '<input name="author" type="hidden" id="author" value="'.$value.'">';
                  echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
                  echo '</fieldset>';
                  echo '</form>';
                  $i++;
              }
          }
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }


  function adminAuthorsAdd() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass, $authorsActCode, $authorsActStatus;
      $authorFileName=$config['authorFile'];
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      $do = 1;
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $addAuthor=$_POST['addAuthor'];
          $authorEmail=$_POST['authorEmail'];
          if (isset($authorsPass[$addAuthor])) {
               echo $lang['errorDuplicateAuthor'].'<br>';
               $do = 0;
          }
          if (trim($addAuthor) == "" || trim($authorEmail) == "") {
               echo $lang['errorAllFields'].'<br>';
               $do = 0;
          }
          if ($_POST['newpass1'] != $_POST['newpass2']) {
               echo $lang['errorNewPasswordsMatch']."<br>";
               $do = 0;
          }
          if (strtolower(trim($addAuthor)) == "admin") {
               echo $lang['errorForbiddenAuthor'].'<br>';
               $do = 0;
          }
          if (strlen($_POST['newpass1']) < 5) {
              echo $lang['errorPassLength'].'<br>';
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
              $addAuthor=$_POST['addAuthor'];
              $addPass  =md5($config['randomString'].$_POST['newpass1']);
              $addEmail =$_POST['authorEmail'];
              $authorLine=$addAuthor.$separator.$addPass.$separator.$addEmail.$separator."11111".$separator."1"."\n";
              fwrite($fp,$authorLine);
              fwrite($fp,'*/ ?>');

              fclose($fp);
              echo $lang['msgConfigSaved'].'<br>';
          }
          else {
              echo $lang['errorPleaseGoBack'];
          }

       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }


  function adminAuthorsEdit() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $authorsPass, $separator, $authorsActCode, $authorsActStatus;
      $authorFileName=$config['authorFile'];
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      $do = 1;
      $deleteAuthor = (isset($_POST['deleteAuthor']))?$_POST['deleteAuthor']:0;
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $editAuthor=$_POST['author'];
          if ($deleteAuthor != 1) {
              if ($_POST['newpass1'] != $_POST['newpass2']) {
                   echo $lang['errorNewPasswordsMatch']."<br>";
                   $do = 0;
              }
              if (strlen($_POST['newpass1']) < 5) {
                  echo $lang['errorPassLength'].'<br>';
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
                        $authorsPass[$value]  = md5($config['randomString'].$_POST['newpass1']);
                        $authorsEmail[$value] = $_POST['authorEmail'];
                        if ($deleteAuthor == 1) {
                            $authorsDelete = true;
                        }
                   }
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value].$separator.$authorsActCode[$value].$separator.$authorsActStatus[$value]."\n";
                   //echo $authorLine.'<br>';
                   if (!$authorsDelete) {
                      fwrite($fp,$authorLine);
                   }
              }
              fwrite($fp,'*/ ?>');
              fclose($fp);
              echo $lang['msgConfigSaved'].'<br>';
          }
          else {
              echo $lang['errorPleaseGoBack'];
          }

       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br/>';
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
             //echo $authorLine[0].' - '.$authorsActCode[$authors[$i]].'<br>';
             //echo $authors[$i].'  '.$authorsPass[$authors[$i]].'  '.$authorsEmail[$authors[$i]].'<br>';
             $i++;
        }
    }
    $authorsPass['admin'] = $config['Password'];
    //echo $config['Password'].'<br>';
 }


  function readConfig() {
    /* Read config information from file. */
    global $config;

    $contents = file( getcwd()."/data/".'config_admin.php' );
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
                  'timeoutDuration');

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
        if ( !isset( $config[ 'blogPath' ] ) )                   { $config[ 'blogPath' ]                   = 'http://localhost'; }
        if ( !isset( $config[ 'showCategoryCloud' ] ) )          { $config[ 'showCategoryCloud' ]          = 1; }
        if ( !isset( $config[ 'allowRegistration' ] ) )          { $config[ 'allowRegistration' ]          = 0; }
        if ( !isset( $config[ 'sendRegistMail' ] ) )             { $config[ 'sendRegistMail' ]             = 1; }
        if ( !isset( $config[ 'timeoutDuration' ] ) )            { $config[ 'timeoutDuration' ]            = 900; }
        $config['menuLinksOrig']=$config['menuLinks'];
        $config['menuLinksArray']=explode(';',$config['menuLinks']);

  }

  function writeConfig($message=true) {
        global $config, $lang;
        $configFile=getcwd()."/data/".'config_admin.php';
        $configContent='<?php /* ';
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
                          $config['timeoutDuration'];
            $configContent=$configContent.' */ ?>';
            $fp=fopen($configFile,"w");
            fwrite($fp,$configContent);
            fclose($fp);
            if ($message) {echo '<br>'.$lang['msgConfigSaved'].'<br>';}
        }
  }


  function createRSS() {
    global $config, $separator, $entries, $optionValue;
	$base = 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],0,-3);

	echo header('Content-type: text/xml').'<?xml version="1.0" encoding="ISO-8859-1"?><rss version="2.0">';
	echo '<channel><title>'.$config['blogTitle'].'</title><description>'.$config['metaDescription'].'</description>';
	echo '<link>http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],0,strlen($_SERVER['REQUEST_URI'])-7).'</link>';
       
        $result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE type = 'post';");
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
        $result = sqlite_query($config['db'], "select * from posts ORDER BY postid DESC LIMIT $limit;");
        while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
            $rssTitle      = $row['title'];
            $rssTitleModified=titleModify($rssTitle);
            $rssContent    = explode("*readmore*",$row['content']);
            $date1         = $row['date'];
            $rssEntry      = $row['postid'];
            $rssCategory   = $row['category'];
            $postType      = $row['type'];
            $allowComments = $row['allowcomments'];
            $visits        = $row['visits'];
            if (trim($visits) == "") { $visits=0; }
            if ($optionValue === $rssCategory || trim($optionValue) == "") {
                   echo '<item><link>'.$base.htmlspecialchars('viewEntry/'.$rssEntry."/".$rssTitleModified).'</link>';
    		   echo '<title>'.$rssTitle.'</title><category>'.$rssCategory.'</category>';
    		   echo '<description>'.htmlspecialchars($rssContent[0]).'</description></item>';
            }
        }

	echo '</channel></rss>';
  }

  function titleModify($myTitle)
  {
          $myTitle=str_replace('"','',str_replace("'","",html_entity_decode($myTitle,ENT_QUOTES)));
          $myTitleMod1=preg_replace("/[^a-z\d\'\"]/i", "-", substr($myTitle,0,strlen($myTitle)-1));
	  $myTitleMod2=preg_replace("/[^a-z\d]/i", "", substr($myTitle,strlen($myTitle)-1,1));
          $myTitleModified=rtrim($myTitleMod1.$myTitleMod2,'-');
          return $myTitleModified;
  }

  function getPosts($start, $end, $requestCategory = "") {
      global $config, $postdb, $separator;

       $file_array_sorted   = array();
       //echo $start.' '.$end.'<br>';
       //echo $requestCategory.'<Br>';
       if (trim($requestCategory) == "") {
           $result = sqlite_query($config['db'], "select * from posts WHERE type = 'post' ORDER BY stick desc, postid desc LIMIT $start, $end;");
       }
       else {
           $result = sqlite_query($config['db'], "select * from posts WHERE type = 'post' AND category = '$requestCategory' ORDER BY stick desc, postid desc LIMIT $start, $end;");
       }
       //sqlite_seek($result,11);
       while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
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

      if ($logThis != 1) {
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
      //echo $lang['sidebarStatsUsersOnline'].': '.$online.'<br>';
      echo $users  . ' '.$lang['sidebarStatsMembersOnline'].'<br>';
      echo $guests . ' '.$lang['sidebarStatsGuestsOnline'].'<br>';
      echo $lang['sidebarStatsHits'].': '.$statcount.'<br>';
  }


  function listPosts() {
      global $separator, $entries, $config, $requestCategory;
      global $userFileName, $optionValue3, $lang;
      $config_Teaser=0;
      $filterEntries=array();

      if (trim($requestCategory) == "") {
          $result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE type = 'post';");
          while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
              $totalEntries = $row['view'];
              if ($row['view'] == 0) {
                 echo '<br><br>'.$lang['msgNoPosts'].' <a href="'.$_SERVER['SCRIPT_NAME'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
              }
          }
      }
      else {
          $result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE type = 'post' AND category = '$requestCategory';");
          while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
              $totalEntries = $row['view'];
          }
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
           $fileName=$entry[3];
           $category=$entry[4];
           $postType=$entry[5];
           $visits  =$entry[7];
           $author  =(trim($entry[8])== "")?'admin':$entry[8];
           if (trim($visits) == "") { $visits=0; }
           if (strstr($entry[1],"*readmore*")) { $readmore='<br><br><a href="'.$_SERVER["SCRIPT_NAME"].'/viewEntry/'.$fileName."/".$titleModified.'">'.$lang['pageViewFullPost'].' &raquo;</a>'; }
           else { $readmore=""; }
           $content =explode("*readmore*",$entry[1]);

           echo "<h2><a class=\"postTitle\" href=".$_SERVER["SCRIPT_NAME"]."/viewEntry/".$fileName."/".$titleModified.">".$title."</a></h2>";
           echo $content[0].$readmore;
           $categoryText=str_replace("."," ",$category);
           echo "<br><center><i>".$lang['pageAuthorsNew1'].": ".$author."&nbsp;-&nbsp; ".$lang['postFtPosted'].": ".$date1."<br>".$lang['postFtCategory'].": <a href=".$_SERVER['SCRIPT_NAME']."/viewCategory/".urlencode($category).">".$categoryText."</a>&nbsp;-&nbsp; ".$lang['postFtVisits'].": ".$visits;
           $commentFile=$config['commentDir'].$fileName.$config['dbFilesExtension'];
           $result = sqlite_query($config['db'], "select count(*) AS view from comments WHERE postid='$fileName';");
           $commentCount = sqlite_fetch_array($result);
           if ($commentCount['view'] > 0) {
               $commentText=$lang['postFtComments'].": ".$commentCount['view'];
           }
           else {$commentText=$lang['postFtNoComments'];}
           echo "&nbsp;-&nbsp; <a href=".$_SERVER["SCRIPT_NAME"]."/viewEntry/".$fileName."/".$titleModified."#Comments>".$commentText."</a></i><br></center>";
           if (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) {
               echo "<center><a href=".$_SERVER['SCRIPT_NAME']."/editEntry/".$fileName.">".$lang['postFtEdit']."</a>";
               echo "&nbsp;-&nbsp;<a href=".$_SERVER['SCRIPT_NAME']."/deleteEntry/".$fileName.">".$lang['postFtDelete']."</a></center><br/>";
           }
           echo "<br/><br/>";
           $i++;
      }
      $totalEntries++;
      $totalPages = ceil(($totalEntries)/($config['entriesPerPage']));
      if($totalPages >= 1)
      {
	   echo '<center> '.$lang['msgPages'].': ';
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
                                $categoryText='/viewCategory/'.urlencode($requestCategory);
                        }

                        if($i == (($page-1)+$config['maxPagesDisplayed']) && (($page-1)+$config['maxPagesDisplayed']) < $totalPages)
			{
				echo  '<a href='.$_SERVER['SCRIPT_NAME'].$categoryText.'/page/'.$i.'>['.$i.']</a> ...';
			}
			elseif($startPage > 1 && $displayed == 0)
			{
				echo '... <a href='.$_SERVER['SCRIPT_NAME'].$categoryText.'/page/'.$i.'>['.$i.']</a> ';
	 			$displayed = 1;
			}
			else
			{
				echo '<a href='.$_SERVER['SCRIPT_NAME'].$categoryText.'/page/'.$i.'>['.$i.']</a> ';
			}
		}
		else
		{
			echo '['.$i.'] ';
		}
	    }
	}
	print '</center>';
  }


  function sidebarListEntries() {
      global $separator, $entries, $config;
      $i=0;
      $limit = $config['menuEntriesLimit'];
      $result = sqlite_query($config['db'], "select * from posts ORDER BY postid DESC LIMIT $limit;");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
            $title         = $row['title'];
            $titleModified = titleModify($title);
            $fileName      = $row['postid'];
            $postType      = $row['type'];
            if ($postType!="page") {
                     echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a>";
                     $i++;
            }
      }
  }

  function sidebarPopular() {
      global $separator, $entries, $config;
      $i=1;
      $multiArray= Array();
      $limit = $config['menuEntriesLimit'];
      $result = sqlite_query($config['db'], "select * from posts WHERE type = 'post' ORDER BY visits DESC LIMIT $limit;");
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
          echo '<a href="'.$_SERVER['SCRIPT_NAME'].'/viewEntry/'.$fileName.'/'.$titleModified.'">'.$title.'</a>';
      }
  }


  function getTitleFromFilename($fileName1) {
      global $entries, $separator, $config;
      $limit  = count($entries);
      $result = sqlite_query($config['db'], "select * from posts WHERE postid = '$fileName1';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
	  $fileTitle  = $row['title'];
          $titleText  = titleModify($fileTitle);
          return $titleText;
      }
  }

  function sidebarListComments() {
      global $separator, $entries, $config;
      $latestCommentsFile=$config['commentDir']."latest".$config['dbFilesExtension'];
      $limit = $config['menuEntriesLimit'];
      $result = sqlite_query($config['db'], "select * from comments ORDER BY date DESC LIMIT $limit;");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentFileName = $row['postid'];
          $commentNum      = $row['sequence'];
          $commentTitle    = $row['title'];
          $postTitle=getTitleFromFilename($commentFileName);
          echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$commentFileName."/".$postTitle."#".$commentNum.">".$commentTitle."</a>";
      }
  }

  function sidebarPageEntries() {
      global $separator, $entries, $config;
      $i=0;
      $limit = $config['menuEntriesLimit'];
      $result = sqlite_query($config['db'], "select * from posts WHERE type = 'page' ORDER BY date DESC LIMIT $limit;");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
            $title         = $row['title'];
            $titleModified = titleModify($title);
            $fileName         = $row['postid'];
            $postType         = $row['type'];
            echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a>";
            $i++;
      }
  }

  function printTagCloudAgain($tags) {
        // $tags is the array
        //arsort($tags);
        //shuffle($tags);

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
        echo '<ul class="tagcloud">';
        foreach ($tags as $key => $value) {
            $size = $min_size + (($value - $min_qty) * $step);
            $rand_colors = array_rand($colors);
            echo '<li><a href="'.$_SERVER['SCRIPT_NAME'].'/viewCategory/'.urlencode(str_replace(" ",".",$key)).'" class="tag" style="font-size:'.$size.'px; color:'.$colors[$rand_colors].'" onmouseout="this.style.color=\''.$colors[$rand_colors].'\'" onmouseover="this.style.color=\'#fff\'" title="'.$value.' things tagged with '.$key.'">'.$key.'</a>';
            echo '<span class="count"> ('.$value.')</span></li>';
        }
        echo '</ul>';
  }


  function loadCategories() {
      global $separator, $entries, $config, $tags;
      $category_array_unsorted=array();
      $result = sqlite_query($config['db'], 'select DISTINCT category from posts;');
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $category = $row['category'];
          $categoryText=str_replace("."," ",$category);
          $result1 = sqlite_query($config['db'], "select count(category) AS view from posts WHERE category = '$category';");
          while ($row1 = sqlite_fetch_array($result1, SQLITE_ASSOC)) {
              $catcount = $row1['view'];
              $tags[$categoryText] = $catcount;
          }
      }
  }

  function sidebarCategories() {
      global $separator, $entries, $config, $tags, $categories;
      $result = sqlite_query($config['db'], 'select DISTINCT category from posts ORDER BY category;');
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $categoryText=str_replace("."," ",$row['category']);
          echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewCategory/".urlencode($row['category']).">".$categoryText."</a>";
      }
  }

  function sidebarLinks() {
      global $config;
      foreach ($config['menuLinksArray'] as $value) {
          $fullLink=explode(",",$value);
          echo '<a href="'.$fullLink[0].'">'.$fullLink[1].'</a>';
      }
  }

  function listAllComments() {
      global $config, $separator, $lang;
      $latestCommentsFile=$config['commentDir']."latest".$config['dbFilesExtension'];
      $userFileName=$config['commentDir']."users".$config['dbFilesExtension'].".dat";
      echo '<h3>'.$lang['pageAllComments'].'</h3>';
      $result = sqlite_query($config['db'], 'select count(commentid) AS view from comments');
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentCount  = $row['view'];
          if ($commentCount > 0) {
              echo '<table><tr><th>'.$lang['pageAllCommentsTitle'].'</th><th>'.$lang['pageAllCommentsDate'].'</th><th>'.$lang['pageAllCommentsBy'].'</th></tr>';
          }
          else {
              echo $lang['pageAllCommentsNo'].'!<br>';
          }
          $result = sqlite_query($config['db'], 'select * from comments ORDER BY date DESC');
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
              echo "<tr><td><a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$postid."/".$titleModified."#Comments>".$title."</a></td>";
              echo "<td>".$date."</td><td>".$author."</td></tr>";
          }
          echo "</table>";
      }
  }

  function nicEditStuff() {
      global $nicEditType, $blogPath, $nicEditUrl;
      switch ($nicEditType) {
        case "nicFile":
            echo $nicEditUrl;
            echo '<script type="text/javascript">';
            $_SESSION['auth'] = "allow";
            echo 'prit="?'.SID.'";';
            echo '</script>';
            $nicPanel="          new nicEditor({fullPanel : true, iconsPath : '".$blogPath."/nicFile/nicEditorIcons.gif'}).panelInstance('posts');";
            break;
        case "nicUpload":
            echo $nicEditUrl;
            $_SESSION['auth'] = "allow";
            $nicPanel="          new nicEditor({fullPanel : true, uploadURI : '".$blogPath."/nicUpload.php?".SID."'}).panelInstance('posts');";
            break;
        case "default":
            echo $nicEditUrl;
            $nicPanel="          new nicEditor({fullPanel : true, iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('posts');";
            break;
      }
      echo '<script type="text/javascript">';
      echo '    bkLib.onDomLoaded(function(){';
      echo $nicPanel;
      echo "          });";
      echo "</script>";
  }


  function newEntryForm() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $blogPath, $lang, $authors, $authorsPass, $ss;
      $newPostFileName=$config['postDir'].$newPostFile;
      echo '<h3>'.$lang['pageNew'].'...</h3>';
      if ($debugMode=="on") {
         echo $_SERVER['PHP_SELF']."<br>";
         echo "Post will be written to ".$newPostFileName."  ".$newFullPostNumber;
      }
      $thisAuthor = $_SESSION['username'];
      $do = 1;
      if (trim($thisAuthor) == "") {
           $lang['errorAllFields'].'<br>';
           $do = 0;
      }
      if ($do == 1) {
          if (is_array($authors)) {
              if (isset($authorsPass[$thisAuthor])) {
                   if ($_SESSION['logged_in']) {
                        nicEditStuff();
                        echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/newEntrySubmit>";
                        echo "<fieldset>";
                        echo '<legend>'.$lang['pageNewForm'].'</legend>';
                        echo '<p><label for="title">'.$lang['pageNewTitle'].'</label><br>';
                        echo '<input type="text" class="ptitle" name="title" id="title" value=""></p>';
                        echo '<script>';
                        echo 'var title = new LiveValidation( "title", {onlyOnSubmit: true } );';
                        echo 'title.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
                        echo '</script>';
                        echo '<br><label for="posts">'.$lang['pageNewContent'].'</label><br>('.$lang['pageNewReadmore'].')<br>';
                        echo '<textarea name="posts" cols="'.$config['textAreaCols'].'" rows="'.$config['textAreaRows'].'"';
                        echo ' style="height: 400px; width: 550px;" id="posts"></textarea><br><br>';
                        echo '<p><label for="category">'.$lang['pageNewCategory'].'</label><br>';
                        echo '<input type="text" class="ptext" id="category" name="category" value=""></p>';
                        echo '<script>';
                        echo 'var category = new LiveValidation( "category", {onlyOnSubmit: true } );';
                        echo 'category.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
                        echo '</script>';
                        echo '<p><label>'.$lang['pageNewOptions'].'</label><br>';
                        echo '<input type="checkbox" name="allowComments" value="yes" checked="checked">'.$lang['pageNewAllowComments'].'<br>';
                        echo '<input type="checkbox" name="isPage" value="1">'.$lang['pageNewIsPage'].' <a href="javascript:alert(\''.$lang['pageNewIsPageDesc'].'\')">(?)</a><br>';
                        echo '<input type="checkbox" name="isSticky" value="yes">'.$lang['pageNewIsSticky'].'</p>';
                        echo '<input name="process" type="hidden" id="process" value="newEntry">';
                        echo '<input name="author" type="hidden" id="author" value="'.$thisAuthor.'">';
                        echo '<p><input type="submit" class="submit" style="width:100px;" value="'.$lang['pageNewSubmit'].'"></p>';
                        echo '</fieldset>';
                        echo '</form>';
                   }
                   else {
                        echo $lang['errorUserPassIncorrect'].'<br>';
                   }
              }
              else {
                   echo $lang['errorUserPassIncorrect'].'<br>';
              }
          }
      }
      else {
           echo $lang['errorPleaseGoBack'].'<br>';
      }

  }

  function newEntrySubmit() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $lang, $authors, $authorsPass;
      $newPostFileName=$config['postDir'].$newPostFile;
      $postTitle=sqlite_escape_string(str_replace("\\","",$_POST["title"]));
      $postContent=sqlite_escape_string(str_replace("\\","",$_POST["posts"]));
      $postDate=date("Y-m-d H:i:s");
      $isPage=isset($_POST["isPage"])?$_POST["isPage"]:0;
      $stick=isset($_POST["isSticky"])?$_POST["isSticky"]:"no";
      $allowComments=isset($_POST["allowComments"])?$_POST["allowComments"]:"no";
      $thisAuthor = $_POST['author'];
      $visits=0;
      $postCategory=strtolower($_POST["category"]);
      echo "<h3>".$lang['pageNew']."...</h3>";
      $do = 1;
      if(trim($postTitle) == '' || trim($postContent) == '' || trim($postCategory) == '' || strstr($postCategory,'.'))
      {
      	   echo $lang['errorAllFields'].'.<br>';
      	   echo $lang['errorCatName'].'<br>';
		   $do = 0;
      }

      $result = sqlite_query($config['db'], "select * from posts WHERE title = '$postTitle';");
      $dupMsg = "";
      if (sqlite_num_rows($result) > 0) {
           $dupMsg = $dupMsg.$lang['errorDuplicatePost'].'.<br>';
           $do = 1;
      }
      if ($do == 1) {
          if ($authorsPass[$thisAuthor] === $authorsPass[$_SESSION['username']] && (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false)) {
              $postCategory=str_replace(" ",".",$postCategory);
              if ($debugMode=="on") {echo "Writing to ".$newPostFileName;}
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
              sqlite_query($config['db'], "INSERT INTO posts (postid, title, content, date, category, type, stick, allowcomments, visits, author) VALUES('$newFullPostNumber', '$postTitle', '$postContent', '$postDate', '$postCategory', '$postType', '$stick', '$allowComments','$visits', '$thisAuthor');");
              echo $dupMsg.$lang['msgNewPost'];
          }
          else {
              echo $lang['errorPasswordIncorrect'].'<br>';
              echo $lang['errorPleaseGoBack'];
          }
      }
      else {
       	   echo $lang['errorPleaseGoBack'];
      }
  }

  function deleteEntryForm() {
      global $debugMode, $optionValue, $lang;
      $fileName = $optionValue;
      echo "<h3>".$lang['pageDelete']."...</h3>";
      echo "<form name=\"form1\" method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/deleteEntry>";
      echo $lang['msgSure'].'<br><br>';
      echo '<input name="process" type="hidden" id="process" value="deleteEntrySubmit">';
      echo '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'">';
      echo '<input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'">';
      echo "</form>";
  }

  function deleteEntrySubmit() {
       global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $optionValue, $lang, $authors, $authorsPass;
       if ($debugMode=="on") {echo "Inside deleteEntrySubmit ..<br>";}
       $entryName= $_POST['fileName'];
       $fileName = $config['postDir'].$entryName.$config['dbFilesExtension'];
       $result = sqlite_query($config['db'], "select * from posts WHERE postid = '$entryName';");
       while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
           //echo $row['postid'].'<br>';
           $author=$row['author'];
           $category=$row['category'];
       }
       echo "<h3>".$lang['pageDelete']."...</h3>";
       $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errorDeleteEntry'].'<br>';
       $errorMessage=$errorMessage.$lang['errorReportBug'].'<br>';
       $thisAuthor = $_SESSION['username'];
       if ((($config['authorEditPost'] == 1) && ($_SESSION['logged_in'])) ||
           (($config['authorEditPost'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && $_SESSION['logged_in'])) {
          @sqlite_query($config['db'], "DELETE FROM posts WHERE postid = '$entryName';");
          @sqlite_query($config['db'], "DELETE FROM comments WHERE postid = '$entryName';");
          echo $lang['msgDeleteSuccess'].'...<br/>';
       }
       else {
          echo $lang['errorNotAuthorized'].' .. <br>';
       }
  }

  function editEntryForm() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $authors, $authorsPass;
      global $optionValue, $blogPath, $lang;
      $fileName = $optionValue;
      $editFileName=$config['postDir'].$fileName.$config['dbFilesExtension'];
      echo "<h3>".$lang['pageEdit']."...</h3>";
      if ($debugMode=="on") {echo "Editing .. ".$editFileName."<br>";}
      $thisAuthor = $_SESSION['username'];
      //$thisPass = md5($config['randomString'].$_POST['pass']);
      $result = sqlite_query($config['db'], "select * from posts WHERE postid = '$fileName';");
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
        if ((($config['authorEditPost'] == 1) && ($_SESSION['logged_in'])) ||
            (($config['authorEditPost'] == 0) && ($_SESSION['isAdmin'] || $thisAuthor == $author) && ($_SESSION['logged_in']))) {
            if ($thisAuthor == 'admin' && $thisAuthor != $author && trim($author) != "") {
                $thisAuthor = $author;
                $thisPass   = $authorsPass[$thisAuthor];
            }
            nicEditStuff();

            echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/editEntrySubmit>";
            echo "<fieldset>";
            echo '<legend>'.$lang['pageEditForm'].'</legend>';
            echo '<p><label for="title">'.$lang['pageNewTitle'].'</label><br>';
            echo '<input type="text" class="ptitle" name="title" id="title" value="'.$title.'"></p>';
            echo '<script>';
            echo 'var title = new LiveValidation( "title", {onlyOnSubmit: true } );';
            echo 'title.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
            echo '</script>';
            echo '<br><label for="posts">'.$lang['pageNewContent'].'</label><br>('.$lang['pageNewReadmore'].')<br>';
            echo '<textarea name="posts" cols="'.$config['textAreaCols'].'" rows="'.$config['textAreaRows'].'"';
            echo ' style="height: 400px; width: 550px;" id="posts">';
            echo $content;
            echo '</textarea><br><br>';
            echo '<p><label for="category">'.$lang['pageNewCategory'].'</label><br>';
            $category=str_replace("."," ",$category);
            echo '<input type="text" class="ptext" id="category" name="category" value="'.$category.'"></p>';
            echo '<script>';
            echo 'var category = new LiveValidation( "category", {onlyOnSubmit: true } );';
            echo 'category.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
            echo '</script>';
            echo '<p><label>'.$lang['pageNewOptions'].'</label><br>';
            echo '<input type="checkbox" name="allowComments" value="yes" '.$checkAllowComments.'>'.$lang['pageNewAllowComments'].'<br>';
            echo '<input type="checkbox" name="isPage" value="1" '.$checking.'>'.$lang['pageNewIsPage'].' <a href="javascript:alert(\''.$lang['pageNewIsPageDesc'].'\')">(?)</a><br>';
            echo '<input type="checkbox" name="isSticky" value="yes" '.$checkStick.'>'.$lang['pageNewIsSticky'].'</p>';
            echo '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'">';
            echo '<input name="visits" type="hidden" id="visits" value="'.$visits.'">';
            echo '<input name="process" type="hidden" id="process" value="editEntrySubmit">';
            echo '<input name="author" type="hidden" id="author" value="'.$thisAuthor.'">';
            echo '<input name="pass" type="hidden" id="pass" value="'.$thisPass.'">';
            echo '<p><input type="submit" value="'.$lang['pageEditSubmit'].'"></p>';
            echo '</fieldset>';
            echo '</form>';
        }
        else {
          echo $lang['errorNotAuthorized'].' .. <br>';
       }

      }
      //else {echo $lang['errorFileNA'].'...<br>';}
  }

  function editEntrySubmit() {
      global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $authors, $authorsPass;
      global $optionValue, $lang;
      if ($debugMode=="on") {echo "Inside editEntrySubmit ..".$_POST['fileName']."<br>";}
      echo "<h3>".$lang['pageEdit']."...</h3>";
      $entryName= $_POST['fileName'];
      $fileName = $config['postDir'].$entryName.$config['dbFilesExtension'];
      $postTitle=sqlite_escape_string(str_replace("\\","",$_POST["title"]));
      $postContent=sqlite_escape_string(str_replace("\\","",$_POST["posts"]));
      $postDate=date("Y-m-d H:i:s");
      $isPage=isset($_POST["isPage"])?$_POST["isPage"]:0;
      $stick=isset($_POST["isSticky"])?$_POST["isSticky"]:"no";
      $allowComments=isset($_POST["allowComments"])?$_POST["allowComments"]:"no";
      $visits=isset($_POST["visits"])?$_POST["visits"]:0;
      $postCategory=strtolower($_POST["category"]);
      $thisAuthor = $_POST['author'];
      $thisPass   = $_POST['pass'];
      $do = 1;
      if(trim($postTitle) == '' || trim($postContent) == '' || trim($postCategory) == '' || strstr($postCategory,'.'))
      {
      	   echo $lang['errorAllFields'].'.<br>';
      	   echo $lang['errorCatName'].'<br>';
	   $do = 0;
      }

      if ($do == 1) {
          if ($isPage == 1) {
              $postType="page";
          }
          else {
              $postType="post";
          }
          if ($debugMode=="on") {echo "Writing to ".$fileName;}
          $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errornewPostFile'].'<br>';
          $errorMessage=$errorMessage.'<br>'.$lang['errorReportBug'].'<br>';
          if ($_SESSION['logged_in']) {
              $postCategory=str_replace(" ",".",$postCategory);
              $content=$postTitle.$separator.str_replace("\\","",$postContent).$separator.$postDate.$separator.$entryName.$separator.$postCategory.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$thisAuthor;
              sqlite_query($config['db'], "UPDATE posts SET title='$postTitle', content='$postContent', category='$postCategory', type='$postType', stick='$stick', allowcomments='$allowComments', visits='$visits', author='$thisAuthor' WHERE postid='$entryName';");
              echo $lang['msgEditSuccess'].' .. <br>';
          }
          else {
              echo $lang['errorNotAuthorized'].' .. <br>';
          }
      }
      else {
           echo $lang['errorPleaseGoBack'];
      }
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
      global $optionValue, $blogPath, $lang, $nicEditUrl;
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config;
      $fileName=$optionValue;
      $viewFileName=$config['postDir'].$fileName.$config['dbFilesExtension'];
      $cool=true;
      if ($debugMode=="on") {echo "Editing .. ".$viewFileName."<br>";}
      if (strstr($fileName,'%') || strstr($fileName,'.')) {
          $cool=false;
          echo '<br>'.$lang['errorInvalidRequest'].'<br>';
      }
      $result = sqlite_query($config['db'], "select * from posts WHERE postid = '$fileName';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $content       = (str_replace("*readmore*","",$row['content']));
          $date1         = date("d M Y H:i",strtotime($row['date']));
          $fileName      = $row['postid'];
          $category      = $row['category'];
          $postType      = $row['type'];
          $allowComments = $row['allowcomments'];
          $visits        = $row['visits'];
          if (trim($visits) == "") { $visits=1; }
          else { $visits++; }
          $author=(trim($row['author'])=="")?'admin':$row['author'];
          sqlite_query($config['db'], "UPDATE posts SET visits = '$visits' WHERE postid = '$fileName';");
          $categoryText=str_replace("."," ",$category);

          echo "<h2>".$title."</h2>";
          echo $content;
          echo '<br><center><i>'.$lang['pageAuthorsNew1'].': '.$author.'&nbsp;-&nbsp; '.$lang['postFtPosted'].': '.$date1.'<br>'.$lang['postFtCategory'].': <a href='.$_SERVER['SCRIPT_NAME'].'/viewCategory/'.urlencode($category).'>'.$categoryText.'</a>&nbsp;-&nbsp; '.$lang['postFtVisits'].': '.$visits;
          $commentFile=$config['commentDir'].$fileName.$config['dbFilesExtension'];
          $result = sqlite_query($config['db'], "select count(*) AS view from comments WHERE postid='$fileName';");
          $commentCount = sqlite_fetch_array($result);
          if ($commentCount['view'] > 0) {
              $commentText=$lang['postFtComments'].": ".$commentCount['view'];
          }
          else {$commentText=$lang['postFtNoComments'];}

          echo "&nbsp;-&nbsp; <a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified."#Comments>".$commentText."</a></i><br></center>";
          if (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) {
              echo "<center><a href=".$_SERVER['SCRIPT_NAME']."/editEntry/".$fileName.">".$lang['postFtEdit']."</a>";
              echo "&nbsp;-&nbsp;<a href=".$_SERVER['SCRIPT_NAME']."/deleteEntry/".$fileName.">".$lang['postFtDelete']."</a></center>";
          }
          echo "<br><br>";


          $commentFullName=$config['commentDir'].$fileName.$config['dbFilesExtension'];
          $i=0;
          echo "<a name='Comments'></a><h3>".$lang['pageViewComments'].":</h3>";

          if($allowComments == "yes")
          {
                $result = sqlite_query($config['db'], "select * from comments WHERE postid = '$fileName';");
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
                    echo '<a name="'.$sequence.'">'.$lang['pageCommentsBy'].'</a>&nbsp;<strong>'.$authorLink.'</strong>&nbsp;'.$lang['pageViewCommentsOn'].'&nbsp;<b>'.$date.'</b><br>';
                    echo $content;
                    echo '<br>';
                    if (isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) {
                        echo '<a href="'.$_SERVER['SCRIPT_NAME'].'/deleteComment/'.$fileName.'/'.$sequence.'">'.$lang['postFtDelete'].'</a>';
                        if (isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false) {
                            echo '&nbsp;&nbsp;-&nbsp;&nbsp;'.$ip;
                        }
                    }
                    echo '<br><br>';
                    $i++;
                }
                if ($i == 0) {echo $lang['pageViewCommentsNo']."<br>";}
                echo $nicEditUrl;
                echo '<br /><br /><h3>'.$lang['pageComments'].'</h3>';
	 	echo '<script type="text/javascript">';
                echo '    bkLib.onDomLoaded(function(){';
                echo "          new nicEditor({buttonList : ['bold','italic','underline','link','unlink'], iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('comment');";
                echo "          });";
                echo "</script>";

                echo "<form name=\"submitform\" method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/sendComment>";
                echo "<fieldset>";
                echo '<legend>'.$lang['pageCommentsForm'].'</legend>';
                echo '<p><label for="author">'.$lang['pageCommentsAuthor'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$lang['pageCommentsRequired'].')</font><br>';
                echo '<input type="text" class="ptext" id="author" name="author" value=""></p>';
                echo '<script>';
                echo 'var author = new LiveValidation( "author", {onlyOnSubmit: true } );';
                echo 'author.add( Validate.Presence,{ failureMessage: "'.$lang['errorRequiredField'].'" } );';
                echo '</script>';
                echo '<p><label for="commentEmail">'.$lang['pageAuthorsNewEmail'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$lang['pageCommentsOptionalEmail'].')</font><br>';
                echo '<input type="text" class="ptext" name="commentEmail" id="commentEmail" value=""></p>';
                echo '<script>';
                echo 'var commentEmail = new LiveValidation( "commentEmail", {onlyOnSubmit: true } );';
                echo 'commentEmail.add( Validate.Email, { failureMessage: "'.$lang['errorInvalidAdminEmail'].'" } );';
                echo '</script>';
                echo '<p><label for="commentUrl">'.$lang['pageCommentsUrl'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$lang['pageCommentsOptionalUrl'].')</font><br>';
                echo '<input type="text" class="ptext" name="commentUrl" id="commentUrl" value=""></p>';
                echo '<script>';
                echo 'var commentUrl = new LiveValidation( "commentUrl", {onlyOnSubmit: true } );';
                echo 'commentUrl.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i,  failureMessage: "'.$lang['errorInvalidUrl'].'" } );';
                echo '</script>';
                echo '<label for="comment">'.$lang['pageCommentsContent'].'</label><br>';
                echo '<textarea name="comment" cols="'.$config['textAreaCols'].'" rows="'.$config['textAreaRows'].'"';
                echo ' style="height: 200px; width: 400px;" id="comment"></textarea><br>';
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
			echo '<p><label for="code">'.$lang['pageCommentsCode'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$code.')</font><br>';
			echo '<input name="code" class="s" type="text" id="code"></p>';
                        echo '<input name="originalCode" value="'.$code.'" type="hidden" id="originalCode">';
		}

                echo '<input name="sendComment" value="'.$fileName.'" type="hidden" id="sendComment">';
                echo '<p><input type="submit" class="submit" style="width:100px" value="'.$lang['pageCommentsSubmit'].'">&nbsp;&nbsp;<input type="reset" class="submit" value="'.$lang['pageCommentsReset'].'"></p>';
                echo '</fieldset>';
                echo '</form>';
          }
          else {echo $lang['pageCommentsDisabled'].'!<br>';}
      }
  }

  function sendComment() {
	# Send Comment Process
        global $separator, $config, $lang;
	echo "<h3>".$lang['pageViewComments']."</h3>";
        $commentFileName = isset($_POST['sendComment'])?$_POST['sendComment']:$_GET['sendComment'];
	$author          = isset($_POST['author'])?$_POST['author']:"";
	$commentTitle    = $lang['pageCommentsBy'].' '.$author;
	$comment         = isset($_POST['comment'])?$_POST['comment']:"";
	$url             = isset($_POST['commentUrl'])?$_POST['commentUrl']:"";
	$email           = isset($_POST['commentEmail'])?$_POST['commentEmail']:"";
	$code            = $_POST['code'];
	$originalCode    = $_POST['originalCode'];
	$do              = 1;
	$triedAsAdmin    = 0;

	if(trim($commentTitle) == '' || trim($author) == '' || trim($comment) == '')
	{
		echo $lang['errorAllFields'].'<br>';
		$do = 0;
	}

	if($config['commentsSecurityCode'] == 1)
	{
		$code = $_POST['code'];
		$originalCode = $_POST['originalCode'];
		if ($code !== $originalCode)
		{
			echo $lang['errorSecurityCode'].'<br>';
			$do = 0;
		}
	}

	$hasPosted = 0;

        $forbiddenAuthors=explode(',',$config['commentsForbiddenAuthors']);
        foreach($forbiddenAuthors as $value)
	{
		if($value == $author)
		{
                     echo $lang['errorCommentUser1']." ".$author." ".$lang['errorCommentUser2'];
  		     $do=0;
		}
	}

	if($do == 1)
	{
		if(strlen($comment) > $config['commentsMaxLength'])
		{
		     echo $lang['errorLongComment1'].' '.$config['commentsMaxLength'].' '.$lang['errorLongComment2'].' '.strlen($comment);
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
                     //echo $date;
                     //die();
                     $ip      = $_SERVER["REMOTE_ADDR"];
                     sqlite_query($config['db'], "INSERT INTO comments (postid, sequence, title, author, content, date, ip, url, email) VALUES('$commentFileName', '$thisCommentSeq', '$commentTitle', '$author', '$comment', '$date', '$ip', '$url', '$email');");

                     echo $lang['msgCommentAdded'].' '.$author.'!<br />';

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
		}
	}
	else {
             echo $lang['errorPleaseGoBack'];
        }
  }

  function deleteCommentForm() {
      global $debugMode, $optionValue, $optionValue2, $lang;
      $fileName = $optionValue;
      $commentNum = $optionValue2;
      echo "<h3>".$lang['pageCommentDel']."...</h3>";
      echo "<form name=\"form1\" method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/deleteComment>";
      echo $lang['msgSure'].'<br><br>';
      echo '<input name="process" type="hidden" id="process" value="deleteCommentSubmit">';
      echo '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'">';
      echo '<input name="commentNum" type="hidden" id="commentNum" value="'.$commentNum.'">';
      echo '<input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'">';
      echo "</form>";
  }

  function deleteCommentSubmit() {
       global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $lang, $authors, $authorsPass;
       global $fileName;
       if ($debugMode=="on") {echo "Inside deleteCommentSubmit ..<br>";}
       $fileName   = $_POST['fileName'];
       $commentNum = $_POST['commentNum'];
       $postFile = $config['postDir'].$fileName.$config['dbFilesExtension'];
       $result = sqlite_query($config['db'], "select * from posts WHERE postid = '$fileName';");
       while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
           $author = $row['author'];
       }
       $thisAuthor = $_SESSION['username'];
       echo "<h3>".$lang['pageCommentDel']."...</h3>";
       $commentNum=$_POST['commentNum'];
       if ((($config['authorDeleteComment'] == 1) && ($_SESSION['logged_in'])) ||
           (($config['authorDeleteComment'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && ($_SESSION['logged_in']))) {
            sqlite_query($config['db'], "delete from comments WHERE postid = '$fileName' and sequence = '$commentNum';");
            echo $lang['msgCommentDeleted']." ...<br>";
       }
       else {
          echo $lang['errorNotAuthorized'].' .. <br>';
       }
  }


  function viewArchive() {
      global $separator, $entries, $config, $lang;
      $i=0;
      echo "<h3>".$lang['pageArchive']."</h3>";
      $archiveArray   = array();
      $archiveArrayUnique = array();
      $archiveArrayFormat = array();
      $result = sqlite_query($config['db'], "select * from posts ORDER BY date;");
      if (sqlite_num_rows($result) == 0) {
          echo '<br><br>'.$lang['msgNoPosts'].' <a href="'.$_SERVER['SCRIPT_NAME'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
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
          echo "<a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewArchiveMonth/".str_replace(" ","-",$archiveMonthYear).">".$archiveArrayFormat[$archiveMonthYear]."</a><br>";
      }
  }


  function viewArchiveMonth() {
      global $separator, $entries, $config, $optionValue, $lang;
      $i=0;
      echo "<h3>".$lang['pageArchiveFor']." ".date("M Y",strtotime($optionValue))."</h3>";
      //$requestMonth = str_replace("-"," ",$optionValue);
      $requestMonth = $optionValue;
      //echo $requestMonth.'<br>';
      echo "<table>";
      $result = sqlite_query($config['db'], "select * from posts WHERE date LIKE '%$requestMonth%';");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $postDate      = $row['date'];
          $fileName      = $row['postid'];
          echo "<tr><td>".$postDate.":&nbsp;</td><td><a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a></td></tr>";
      }
      echo "</table>";
  }


  function searchPosts() {
      global $separator, $config, $entries, $lang;
      $searchkey   = isset($_POST['searchkey'])?$_POST['searchkey']:$_GET['searchkey'];
      echo "<h3>".$lang['pageSearch']."</h3>";
      $i=0;
      if (trim($searchkey) == "") {
          echo $lang['errorSearchNothing'].'<br>';
      }
      else {
          $result = sqlite_query($config['db'], "select * from posts WHERE title LIKE '%$searchkey%' OR content LIKE '%$searchkey%';");
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
              echo "<a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a><br/>";
              $i++;
          }
          if ($i == 0) {echo $lang['errorSearchEmptyResult'];}
      }
  }


?>

