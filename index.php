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
#	Version: 0.7                                                    #
#######################################################################*/


  $debugMode    = "off";    // Turn this on for debugging displays. But is not fully functional yet.
  $separator    = "#~#";    // Separator used between fields when the entry files are created.

  $config       = array();
  $authors      = array();
  $authorsPass  = array();
  $authorsEmail = array();
  $tags         = array();

  readConfig();
  require("lang/".$config['blogLanguage'].".php");
  readAuthors();

  $entries=getPosts();
  $categories=loadCategories();
  $lastEntry=explode($separator,$entries[0]);
  $newPostNumber    =$lastEntry[3]+1;
  $newFullPostNumber=str_pad($newPostNumber, 5, "0", STR_PAD_LEFT);
  $newPostFile      =$newFullPostNumber.$config['dbFilesExtension'];
  $data = explode("/",$_SERVER['PATH_INFO']);
  $serverName='http://'.$_SERVER['SERVER_NAME'];
  $serverPort=($_SERVER['SERVER_PORT']=='80')?'':':'.$_SERVER['SERVER_PORT'];
  $scriptName=$_SERVER["SCRIPT_NAME"];
  $blogPath=dirname($serverName.$serverPort.$scriptName);
  if ($config['blogPath'] !== $blogPath) {
      $config['blogPath'] = $blogPath;
      writeConfig(false);
  }

  $baseScript=basename($scriptName);
  $i=0;
  $optionIndex=1;
  foreach ($data as $value) {
      if (strcmp($value,$baseScript) == 0) {
        $optionIndex=$i+1;
      }
      $i++;
  }
  $option = isset($data[$optionIndex])?$data[$optionIndex]:"mainPage";
  if (file_exists(getcwd().'/nicFile/nicEditorIcons.gif')) {
       $nicEditType = "nicFile";
       $nicEditUrl  = '<script src="'.$blogPath.'/nicFile/nicEdit.js" type="text/javascript"></script>';
       if (!file_exists(getcwd().'/sessions')) { mkdir(getcwd().'/sessions',0755); }
       session_save_path(getcwd(). '/sessions');
       session_start();
       unset($_SESSION['auth']);
  }
  elseif (file_exists(getcwd().'/nicUpload.php')) {
       $nicEditType = "nicUpload";
       $nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
       //$nicEditUrl  = '<script src="'.$blogPath.'/nicEdit/nicEdit.js" type="text/javascript"></script>';
       if (!file_exists(getcwd().'/sessions')) { mkdir(getcwd().'/sessions',0755); }
       session_save_path(getcwd(). '/sessions');
       session_start();
       unset($_SESSION['auth']);
  }
  else {
       $nicEditType = "default";
       $nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
       //$nicEditUrl  = '<script src="'.$blogPath.'/nicEdit/nicEdit.js" type="text/javascript"></script>';
  }
  $optionValue = $data[$optionIndex+1];
  $optionValue2= $data[$optionIndex+2];
  $optionValue3= $data[$optionIndex+3];
  //echo $optionIndex."<br>";
  //echo $option."<br>";
  //echo $optionValue."<br>";
  //echo $optionValue2."<br>";
  //echo $optionValue3."<br>";

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
if (trim($optionValue2) == "") { $postTitle=""; }
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

</head>

<body>

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

<div id="topbar" class="span-5">

<!-- Left Sidebar -->

<?php

if (strlen($config['about']) > 10) {

?>

<div  class="span-5  last">
<h3><?php echo $lang['pageBasicConfigAbout']; ?></h3>
<?php echo $config['about']; ?>
</div>

<?php
}
?>

<div  class="span-5  last">
<h3><?php echo $lang['sidebarHeadMainMenu']; ?></h3>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/mainPage>'.$lang['sidebarLinkHome']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/newEntry>'.$lang['sidebarLinkNewEntry']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/viewArchive>'.$lang['sidebarLinkArchive']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/RSS>'.$lang['sidebarLinkRSSFeeds']; ?></a>
<a href=<?php echo $_SERVER["SCRIPT_NAME"].'/adminPage>'.$lang['sidebarLinkAdmin']; ?></a>
</div>

<div  class="span-5  last">
<h3><?php echo $lang['sidebarHeadCategories']; ?></h3>
<?php sidebarCategories(); ?>
</div>

<div class="span-5  last">
<h3><?php echo $lang['sidebarHeadPages']; ?></h3>
<?php sidebarPageEntries() ?>
</div>

<div  class="span-5  last">
<h3><?php echo $lang['sidebarHeadLinks']; ?></h3>
<?php sidebarLinks(); ?>
</div>

<div  class="span-5  last">
<h3><?php echo $lang['sidebarHeadStats']; ?></h3>
<?php sidebarStats() ?>
</div>

</div>

<!-- Main content - that has the posts begins here -->

<div id="content" class="span-12">
<?php

}

/* This function does the main logic to direct to other functions as required */
mainLogic();

if($option !== 'RSS') {
?>
</div>

<!-- Right sidebar -->

<div class="span-5">

<div id="menu" class="span-5">

<div class="span-5  last">
<h3><?php echo $lang['sidebarHeadShare']; ?></h3>
<style>
#shareButton {font:12px Verdana, Helvetica, Arial; height: 30px;width:100px;}
#shareDrop {position:absolute; padding:10px; display: none; z-index: 100; top:-900px; left:0px; width: 200px;float:left;background: #E9E9E9;border:1px solid black;}
#shareButton img, #shareDrop img {border:0} #shareDrop a {color:#008DC2; padding:0px 5px;display:block;text-decoration:none;} #shareDrop a:hover {background-color: #999999; color: #fff; text-decoration:none;}
#shareshadow{position: absolute;left: 0; top: 0; z-index: 99; background: black; visibility: hidden;}
div.sharefoot {position: absolute; top: 182px; height:15px; width: 200px; text-align: center; background-color: #999999; color: #fff;}
div.sharefoot a{display:inline; color:#fff; background-color:#999999; } div.sharefoot a:hover{text-decoration:none; background: #00adef; color: #fff}
</style>
<script type="text/javascript">
<?php
echo 'var bPath="'.$blogPath.'/images/bookmarks";';
echo 'var u1   ="'.urlencode($blogPath).'";';
echo 'var t1   ="'.urlencode($postTitleSave.$config['blogTitle']).'";';
?>
</script>
<script type="text/javascript" src="<?php echo $blogPath.'/javascripts/shareme.js' ?>"></script>
</div>

<div class="span-5  last">
<h3><?php echo $lang['sidebarHeadSearch']; ?></h3>
    <form name="form1" method="post" action=<?php echo $_SERVER['SCRIPT_NAME']; ?>/searchPosts>
    <input type="text" name="searchkey">
    <input type="hidden" name="do" value="search">
    <input type="submit" name="Submit" value="Search"><br />
    </form>
</div>

<div  class="span-5  last">
<h3><?php echo $lang['sidebarHeadPopularEntries']; ?></h3>
    <?php sidebarPopular();  ?>
</div>

<div  class="span-5  last">
<h3><?php echo $lang['sidebarHeadLatestEntries']; ?></h3>
    <?php sidebarListEntries(); ?>
</div>


<div  class="span-5  last">
<h3><?php echo $lang['sidebarHeadLatestComments']; ?></h3>
<?php
      sidebarListComments();
      echo '<a href="'.$_SERVER['SCRIPT_NAME'].'/listAllComments">'.$lang['sidebarLinkListComments'].'</a>';
?>
</div>

</div>

</div>

</div>

<?php /* PLEASE DONT REMOVE THIS COPYRIGHT WITHOUT PERMISSION FROM THE AUTHOR */ ?>
<?php echo '<div id="footer">'.$lang['footerCopyright'].' '.$config['blogTitle'].' '.date('Y').' - '.$lang['footerRightsReserved'].' - Powered by <a href="http://hardkap.net/pritlog/">Pritlog</a></div>'; ?>

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
          newEntryPass();
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
          if ($debugMode=="on") {echo "adminPage  ".$_POST['process']."<br>";}
          if ($_POST['process']!=="adminPage") {
              adminPass();
          }
          else {
              adminPage();
          }
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
          if ($_POST['process']!=="deleteEntrySubmit") {
              deleteEntryForm();
          }
          else {
              deleteEntrySubmit();
          }
          break;
      case "editEntry":
          if ($debugMode=="on") {echo "editEntry  ".$_POST['process']."<br>";}
          editEntryPass();
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
          if ($_POST['process']!=="deleteCommentSubmit") {
              deleteCommentForm();
          }
          else {
              deleteCommentSubmit();
          }
          break;
      }
  }

  function adminPass() {
      global $debugMode, $optionValue, $config, $lang;
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      echo "<form name=\"form1\" method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminPage>";
      echo "<table><td>Pass</td>";
      echo "<td><input name=\"pass\" type=\"password\" id=\"pass\">";
      echo "<input name=\"process\" type=\"hidden\" id=\"process\" value=\"adminPage\">";
      echo "</tr><tr><td>&nbsp;</td><td><input type=\"submit\" name=\"Submit\" value=\"Submit\"></td>";
      echo "</tr></table></form>";
  }

  function adminPage() {
      global $debugMode, $optionValue, $config, $lang;
      echo "<h3>".$lang['titleAdminPage']."</h3>";

      if (md5($config['randomString'].$_POST['pass'])===$config['Password']) {
          echo '<p><form method="post" action="'.$_SERVER['SCRIPT_NAME'].'/adminPageBasic">';
          echo '<input type="submit" value="'.$lang['pageBasicConfig'].'">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '</form><br>';
          echo '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'/adminPageAdvanced">';
          echo '<input type="submit" value="'.$lang['pageAdvancedConfig'].'">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '</form><br>';
          echo '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'/adminPageAuthors">';
          echo '<input type="submit" value="'.$lang['pageAuthorsManage'].'">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '</form></p>';
          echo '<br><br><h3>Pritlog Version</h3>';
          //echo 'You are using a beta release of Pritlog<br>Thank you for testing!<br>';
          echo '<script type="text/javascript">';
          echo 'var clientVersion=0.7;';
          echo '</script>';
          echo '<script src="http://hardkap.net/pritlog/checkversion.7.js" type="text/javascript"></script>';
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br>';
       }
  }

  function adminPageBasic() {
      global $debugMode, $optionValue, $config, $lang;
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      if ($_POST['pass']===$config['Password']) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminPageBasicSubmit>";
          echo "<fieldset>";
          echo '<legend>'.$lang['pageBasicConfig'].'</legend>';
          echo '<p><label for="title">'.$lang['pageBasicConfigTitle'].'</label><br>';
          echo '<input type="text" class="ptitle" name="title" id="title" value="'.$config['blogTitle'].'"></p>';
          echo '<p><label for="newpass1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>';
          echo '<p><label for="newpass2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>';
          echo '<p><label for="adminEmail">'.$lang['pageBasicConfigAdminEmail'].'</label><br>';
          echo '<input type="text" class="ptext" name="adminEmail" id="adminEmail" value="'.$config['sendMailWithNewCommentMail'].'"></p>';
          echo '<p><label for="posts">'.$lang['pageBasicConfigAbout'].'</label><br>';
          nicEditStuff();
          echo '<textarea name="posts" id="posts">'.$config['about'].'</textarea></p>';  /* this is actually about. not posts. Dont be mislead */
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '<p><input type="submit" value="'.$lang['pageBasicConfigSubmit'].'"></p>';
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
      if ($_POST['pass']===$config['Password']) {
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


      if ($_POST['pass']===$config['Password']) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminPageAdvancedSubmit>";
          echo "<fieldset>";
          echo '<legend>'.$lang['pageAdvancedConfig'].'</legend>';
          echo '<p><label for="postdir">'.$lang['pageAdvancedConfigPostsDir'].'</label><br>';
          echo '<input type="text" class="ptext" name="postdir" id="postdir" value="'.$config['postDirOrig'].'"></p>';
          echo '<p><label for="commentdir">'.$lang['pageAdvancedConfigCommentsDir'].'</label><br>';
          echo '<input type="text" class="ptext" name="commentdir" id="commentdir" value="'.$config['commentDirOrig'].'"></p>';
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
                  /*
                  rsort($file_array_unsorted);
                  foreach ($file_array_unsorted as $value) {
                      $filename=$config['postDir'].$value;
                      if ((file_exists($filename)) && ($filename !== $config['postDir'].".") && ($filename !== $config['postDir']."..")) {
                        $fp = fopen($filename, "rb");
                        array_push($file_array_sorted,fread($fp, filesize($filename)));
                        fclose($fp);
                      }
                  } */
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
          echo '<input type="checkbox" name="sendMailComments" value="1" '.$checking.'">'.$lang['pageAdvancedConfigSendMail'].'</a><br>';
          if ($config['commentsSecurityCode'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="commentsSecurityCode" value="1" '.$checking.'">'.$lang['pageAdvancedConfigSecCode'].'</a><br>';
          if ($config['authorEditPost'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="authorEditPost" value="1" '.$checking.'">'.$lang['pageAdvancedConfigAuthorEdit'].'</a><br>';
          if ($config['authorDeleteComment'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="authorDeleteComment" value="1" '.$checking.'">'.$lang['pageAdvancedConfigAuthorComment'].'</a><br>';
          if ($config['showCategoryCloud'] == 1) {
              $checking='checked="checked"';
          }
          else {
              $checking='';
          }
          echo '<input type="checkbox" name="showCategoryCloud" value="1" '.$checking.'">'.$lang['pageAdvancedConfigCatCloud'].'</a></p>';
          echo '<input name="process" type="hidden" id="process" value="adminPageAdvancedSubmit">';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '<p><input type="submit" value="'.$lang['pageAdvancedConfigSubmit'].'"></p>';
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
      if ($_POST['pass']===$config['Password']) {
          if (isset($_POST['postdir']) && trim($_POST['postdir']) != "") {
               $config['postDirOrig'] = $_POST['postdir'];
               $config['postDir']     = getcwd().'/'.$_POST['postdir'].'/';
          }
          if (isset($_POST['commentdir']) && trim($_POST['commentdir']) != "") {
               $config['commentdirOrig'] = $_POST['commentDir'];
               $config['commentdir']     = getcwd().'/'.$_POST['commentDir'].'/';
          }
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
      if ($_POST['pass']===$config['Password']) {
          echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminAuthorsAdd>";
          echo "<fieldset>";
          echo '<legend>'.$lang['pageAuthorsAdd'].'</legend>';
          echo '<p><label for="addAuthor">'.$lang['pageAuthorsNew'].'</label><br>';
          echo '<input type="text" class="ptext" name="addAuthor" id="addAuthor" value=""></p>';
          echo '<p><label for="newpass1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>';
          echo '<p><label for="newpass2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
          echo '<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>';
          echo '<p><label for="authorEmail">'.$lang['pageAuthorsNewEmail'].'</label><br>';
          echo '<input type="text" class="ptext" name="authorEmail" id="authorEmail" value=""></p>';
          echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
          echo '<p><input type="submit" value="'.$lang['pageAuthorsAdd'].'"></p>';
          echo '</fieldset>';
          echo '</form>';

          if (is_array($authors)) {
              foreach ($authors as $value) {
                  echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/adminAuthorsEdit>";
                  echo "<fieldset>";
                  echo '<legend>'.$lang['pageAuthorsManage'].'</legend>';
                  echo '<table>';
                  echo '<tr><td><strong>Author: </strong>'.$value.'<br><br></td>';
                  echo '<td><label for="authorEmail">'.$lang['pageAuthorsNewEmail'].'</label><br>';
                  echo '<input type="text" name="authorEmail" id="authorEmail" value="'.$authorsEmail[$value].'"></td></tr>';
                  echo '<tr><td><label for="newpass1">'.$lang['pageBasicConfigNewpass1'].'</label><br>';
                  echo '<input type="password" name="newpass1" id="newpass1" value=""></td>';
                  echo '<td><label for="newpass2">'.$lang['pageBasicConfigNewpass2'].'</label><br>';
                  echo '<input type="password" name="newpass2" id="newpass2" value=""></td></tr>';
                  echo '</table>';
                  echo '<input type="submit" value="'.$lang['postFtEdit'].'">&nbsp;&nbsp;';
                  echo '<input type="checkbox" name="deleteAuthor" value="1">'.$lang['pageAuthorsDelete'];
                  echo '<input name="author" type="hidden" id="author" value="'.$value.'">';
                  echo '<input name="pass" type="hidden" id="pass" value="'.$config['Password'].'">';
                  echo '</fieldset>';
                  echo '</form>';
              }
          }
       }
       else {
          echo $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }


  function adminAuthorsAdd() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $separator, $authorsPass;
      $authorFileName=getcwd(). "/authors.php";
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      $do = 1;
      if ($_POST['pass']===$config['Password']) {
          $addAuthor=$_POST['addAuthor'];
          if (isset($authorsPass[$addAuthor])) {
               echo $lang['errorDuplicateAuthor'].'<br>';
               $do = 0;
          }
          if (trim($addAuthor) == "") {
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
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value]."\n";
                   fwrite($fp,$authorLine);
              }
              $addAuthor=$_POST['addAuthor'];
              $addPass  =md5($config['randomString'].$_POST['newpass1']);
              $addEmail =$_POST['authorEmail'];
              $authorLine=$addAuthor.$separator.$addPass.$separator.$addEmail."\n";
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
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail, $authorsPass, $separator;
      $authorFileName=getcwd(). "/authors.php";
      echo "<h3>".$lang['titleAdminPage']."</h3>";
      $do = 1;
      if ($_POST['pass']===$config['Password']) {
          $editAuthor=$_POST['author'];
          if ($_POST['deleteAuthor'] != 1) {
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
                        if ($_POST['deleteAuthor'] == 1) {
                            $authorsDelete = true;
                        }

                   }
                   $authorLine=$value.$separator.$authorsPass[$value].$separator.$authorsEmail[$value]."\n";
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
    global $authors, $separator, $authorsPass, $authorsEmail, $config;

    $tempAuthors = file( getcwd()."/".'authors.php' );
    $i=0;
    foreach ($tempAuthors as $value) {
        if (!strstr($value,'<?php') && !strstr($value,'?>') && (trim($value) != "" )) {
             $value=str_replace("\n","",$value);
             $authorLine=explode($separator,$value);
             $authors[$i]=$authorLine[0];
             $authorsPass[$authors[$i]]=$authorLine[1];
             $authorsEmail[$authors[$i]]=$authorLine[2];
             //echo $authors[$i].'  '.$authorPass[$authors[$i]].'  '.$authorEmail[$authors[$i]].'<br>';
             $i++;
        }
    }
    $authorsPass['admin'] = $config['Password'];
 }


  function readConfig() {
    /* Read config information from file. */
    global $config;

    $contents = file( getcwd()."/".'config_admin.php' );
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
                  'showCategoryCloud');

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
  }

  function writeConfig($message=true) {
        global $config, $lang;
        $configFile=getcwd()."/".'config_admin.php';
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
                          $config['showCategoryCloud'];
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

	if($config['entriesOnRSS'] == 0)
	{
		$limit = count($entries);
	}
	else
	{
		$limit = $config['entriesOnRSS'];
	}

	for($i = 0; $i < $limit; $i++)
	{
		$entry      = explode($separator, $entries[$i]);
		$rssTitle   =$entry[0];
                $rssTitleModified=titleModify($rssTitle);

                $rssContent =explode("*readmore*",$entry[1]);
                $rssEntry   =$entry[3];
                $rssCategory=$entry[4];
                if ($optionValue === $rssCategory || trim($optionValue) == "") {
                   echo '<item><link>'.$base.htmlentities('viewEntry/'.$rssEntry."/".$rssTitleModified).'</link>';
    		   echo '<title>'.$rssTitle.'</title><category>'.$rssCategory.'</category>';
    		   echo '<description>'.htmlentities($rssContent[0]).'</description></item>';
                }
	}

	echo '</channel></rss>';
  }

  function titleModify($myTitle)
  {
          //$myTitle=str_replace('"','',str_replace("'","",$myTitle));
          $myTitle=str_replace('"','',str_replace("'","",html_entity_decode($myTitle,ENT_QUOTES)));
          $myTitleMod1=preg_replace("/[^a-z\d\'\"]/i", "-", substr($myTitle,0,strlen($myTitle)-1));
	  $myTitleMod2=preg_replace("/[^a-z\d]/i", "", substr($myTitle,strlen($myTitle)-1,1));
          $myTitleModified=rtrim($myTitleMod1.$myTitleMod2,'-');
          return $myTitleModified;
  }

  function getPosts() {
      global $config;
      if (file_exists($config['postDir'])) {
          if ($handle = opendir($config['postDir'])) {
              $file_array_unsorted = array();
              $file_array_sorted   = array();
              while (false !== ($file = readdir($handle))) {
                  array_push($file_array_unsorted,$file);
              }
              rsort($file_array_unsorted);
              foreach ($file_array_unsorted as $value) {
                  $filename=$config['postDir'].$value;
                  if ((file_exists($filename)) && ($filename !== $config['postDir'].".") && ($filename !== $config['postDir']."..")) {
                    $fp = fopen($filename, "rb");
                    array_push($file_array_sorted,fread($fp, filesize($filename)));
                    fclose($fp);
                  }
              }
              closedir($handle);
          }
      }
      return $file_array_sorted;
  }


  function sidebarStats() {
      global $config, $separator, $lang;
      $ip=$_SERVER['REMOTE_ADDR'];
      $currentTime=time();
      $statsContent=$ip.$separator.$currentTime."\n";
      $statsFile=$config['commentDir']."online".$config['dbFilesExtension'].".dat";
      $logThis=0;
      $statsDontLog=explode(',',$config['statsDontLog']);
      foreach ($statsDontLog as $value) {
          if ($ip == $value ) {
              $logThis=1;
          }
      }
      if ($logThis != 1) {
          if (file_exists($config['commentDir'])) {
              $fp=fopen($statsFile,"a");
              fwrite($fp,$statsContent);
              fclose($fp);
          }
      }
      if (file_exists($statsFile)) {$statsRead=file($statsFile);}
      $hits=0;
      $online=0;
      $already=array();
      if (is_array($statsRead)) {
          foreach ($statsRead as $value) {
              $log=explode($separator,$value);
              $logIP=$log[0];
              $logTime=$log[1];
              $timeOnline=$currentTime-$logTime;
              if ($timeOnline < $config['usersOnlineTimeout']) {
                   if (array_search($logIP,$already)===FALSE) {$online++;}
                   array_push($already,$logIP);
              }
              $hits++;
          }
      }
      echo $lang['sidebarStatsUsersOnline'].': '.$online.'<br>';
      echo $lang['sidebarStatsHits'].': '.$hits.'<br>';
  }


  function listPosts() {
      global $separator, $entries, $config, $requestCategory;
      global $userFileName, $optionValue3, $lang;
      $config_Teaser=0;
      $filterEntries=array();
      $totalEntries=0;
      $totalPosts=0;
      if (is_array($entries)) {
          foreach ($entries as $value)
          {
               $entry   =explode($separator,$value);
               $title   =$entry[0];
               $content =$entry[1];
               $date1   =$entry[2];
               $fileName=$entry[3];
               $category=$entry[4];
               $postType=$entry[5];
               if ($requestCategory == "") {
                   if ($postType!="page") {
                        array_push($filterEntries,$value);
                        $totalEntries++;
                        $totalPosts++;
                   }
               }
               else {
                   if ($category == $requestCategory) {
                        array_push($filterEntries,$value);
                        $totalEntries++;
                   }
               }
          }
      }

      if ($totalEntries == 0) {
          $justCommentsDir=str_replace("/","",(substr($config['commentDir'],strlen(getcwd()),strlen($config['commentDir']))));
          $justPostsDir=str_replace("/","",(substr($config['postDir'],strlen(getcwd()),strlen($config['postDir']))));
          $errorMessage="<br><span style=\"color: rgb(204, 0, 51);\">Unable to create posts and comments directories<br>Please create them manually. <br>";
          $errorMessage=$errorMessage.$lang['errorDirectoryNames'].': <br>- '.$justPostsDir.'<br>- '.$justCommentsDir.'<br>';
          $errorMessage=$errorMessage.'<br>'.$lang['errorPermissions'].'.<br></span>';


          if (is_writable(getcwd())) {
              if (!file_exists($config['commentDir']) || !file_exists($config['postDir'])) {  /* Looks like first time running */
                  mkdir($config['commentDir'],0755) or die($errorMessage);
                  mkdir($config['postDir'],0755) or die($errorMessage);
              }

              echo '<br><br>'.$lang['msgNoPosts'].' <a href="'.$_SERVER['SCRIPT_NAME'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
          }
          else {
              exit($errorMessage);
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

      while($i<=$arrayEnd)
      {
           $entry  =explode($separator,$filterEntries[$i]);
           $title  =$entry[0];
           $titleModified=titleModify($title);
           $date1   =$entry[2];
           $fileName=$entry[3];
           $category=$entry[4];
           $postType=$entry[5];
           $visits  =$entry[7];
           $author  =(trim($entry[8])== "")?'admin':$entry[8];
           if (trim($visits) == "") { $visits=0; }
           if (strstr($entry[1],"*readmore*")) { $readmore='<br><br><a href="'.$_SERVER["SCRIPT_NAME"].'/viewEntry/'.$fileName."/".$titleModified.'">'.$lang['pageViewFullPost'].' &raquo;</a>'; }
           else { $readmore=""; }
           $content =explode("*readmore*",$entry[1]); 

           echo "<h3><a class=\"postTitle\" href=".$_SERVER["SCRIPT_NAME"]."/viewEntry/".$fileName."/".$titleModified.">".$title."</a></h3>";
           echo $content[0].$readmore;
           $categoryText=str_replace("."," ",$category);
           echo "<br><center><i>".$lang['pageAuthorsNew'].": ".$author."&nbsp;-&nbsp; ".$lang['postFtPosted'].": ".$date1."<br>".$lang['postFtCategory'].": <a href=".$_SERVER['SCRIPT_NAME']."/viewCategory/".$category.">".$categoryText."</a>&nbsp;-&nbsp; ".$lang['postFtVisits'].": ".$visits."</i><br>";
           $commentFile=$config['commentDir'].$fileName.$config['dbFilesExtension'];
           if (file_exists($commentFile)) {
               $commentLines=file($commentFile);
               $commentText=count($commentLines)." ".$lang['postFtComments'];
           }
           else {$commentText=$lang['postFtNoComments'];}
           echo "<a href=".$_SERVER["SCRIPT_NAME"]."/viewEntry/".$fileName."/".$titleModified."#Comments>".$commentText."</a>";
           echo "&nbsp;-&nbsp;<a href=".$_SERVER['SCRIPT_NAME']."/editEntry/".$fileName.">".$lang['postFtEdit']."</a>";
           echo "&nbsp;-&nbsp;<a href=".$_SERVER['SCRIPT_NAME']."/deleteEntry/".$fileName.">".$lang['postFtDelete']."</a></center><br/>";
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
                                $categoryText='/viewCategory/'.$requestCategory;
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
      if (is_array($entries)) {
          foreach ($entries as $value) {
              if ($i < $config['menuEntriesLimit']) {
                  $entry  =explode($separator,$value);
                  $title  =$entry[0];
                  $titleModified=titleModify($title);
                  $content=$entry[1];
                  $date1  =$entry[2];
                  $fileName=$entry[3];
                  $postType=$entry[5];
                  if ($postType!="page") {
                     echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a>";
                     $i++;
                  }
              }
          }
      }

  }

  function sidebarPopular() {
      global $separator, $entries, $config;
      $i=1;
      $multiArray= Array();
      if (is_array($entries)) {
          foreach ($entries as $value) {
               $entry  =explode($separator,$value);
               $title  =$entry[0];
               $titleModified=titleModify($title);
               $content=$entry[1];
               $date1  =$entry[2];
               $fileName=$entry[3];
               $postType=$entry[5];
               $visits  =$entry[7];
               if (trim($visits) == "") { $visits=0; }
               if ($postType!="page") {
                  $multiArray[$i] = $visits.$separator.$fileName.$separator.$titleModified.$separator.$title;
                  $i++;
               }
           }
           $i=0;
           rsort($multiArray, SORT_NUMERIC);
           foreach ($multiArray as $value) {
               if ($i < $config['menuEntriesLimit']) {
                   $popularEntry=explode($separator,$value);
                   echo '<a href="'.$_SERVER['SCRIPT_NAME'].'/viewEntry/'.$popularEntry[1].'/'.$popularEntry[2].'">'.$popularEntry[3].'</a>';
                   $i++;
               }
           }
       }
  }


  function getTitleFromFilename($fileName1) {
      global $entries, $separator;
      $limit = count($entries);
      for($i = 0; $i < $limit; $i++)
      {
      	     $entry      = explode($separator, $entries[$i]);
	     $fileTitle  =$entry[0];
             $fileEntry  =$entry[3];
             $titleText  = titleModify($fileTitle);
             if ($fileEntry == $fileName1) { return $titleText; }
      }
  }

  function sidebarListComments() {
      global $separator, $entries, $config;
      $latestCommentsFile=$config['commentDir']."latest".$config['dbFilesExtension'];
      if (file_exists($latestCommentsFile)) {
          $allComments=file($latestCommentsFile);
          $allCommentsReversed=array_reverse($allComments);
          $i=0;
          foreach ($allCommentsReversed as $value) {
              if ($i < $config['menuEntriesLimit']) {
                  $entry  =explode($separator,$value);
                  $commentFileName=$entry[0];
                  $postTitle=getTitleFromFilename($commentFileName);
                  $commentTitle   =$entry[1];
                  $commentNum     =$entry[2];
                  echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$commentFileName."/".$postTitle."#".$commentNum.">".$commentTitle."</a>";
                  $i++;
              }
          }
      }
  }

  function sidebarPageEntries() {
      global $separator, $entries, $config;
      $i=0;
      if (is_array($entries)) {
          foreach ($entries as $value) {
              if ($i < $config['menuEntriesLimit']) {
                  $entry  =explode($separator,$value);
                  $title  =$entry[0];
                  $titleModified=titleModify($title);
                  $content=$entry[1];
                  $date1  =$entry[2];
                  $fileName=$entry[3];
                  $postType=$entry[5];
                  if ($postType=="page") {
                     echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a>";
                     $i++;
                  }
              }
          }
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
            echo '<li><a href="'.$_SERVER['SCRIPT_NAME'].'/viewCategory/'.str_replace(" ",".",$key).'" class="tag" style="font-size:'.$size.'px; color:'.$colors[$rand_colors].'" onmouseout="this.style.color=\''.$colors[$rand_colors].'\'" onmouseover="this.style.color=\'#fff\'" title="'.$value.' things tagged with '.$key.'">'.$key.'</a>';
            echo ' <span class="count">('.$value.')</span></li>';
        }
        echo '</ul>';
  }


  function loadCategories() {
      global $separator, $entries, $config, $tags;
      $category_array_unsorted=array();
      if (is_array($entries)) {
          foreach ($entries as $value) {
             $entry  =explode($separator,$value);
             $category=$entry[4];
             array_push($category_array_unsorted,$category);
          }
          $category_array_unique = array_unique($category_array_unsorted);
          foreach ($category_array_unique as $value) {
             $categoryText=str_replace("."," ",$value);
             $tags[$categoryText]=0;
             foreach ($category_array_unsorted as $subvalue) {
                  if (str_replace("."," ",$subvalue) == $categoryText) {
                       $tags[$categoryText]++;
                  }
             }
          }
      }
      return $category_array_unique;
  }

  function sidebarCategories() {
      global $separator, $entries, $config, $tags, $categories;
      if (is_array($categories)) {
          foreach ($categories as $value) {
             $categoryText=str_replace("."," ",$value);
             echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewCategory/".$value.">".$categoryText."</a>";
          }
      }
  }

  function sidebarLinks() {
      global $config;
      $config['menuLinksOrig']=$config['menuLinks'];
      $config['menuLinksArray']=explode(';',$config['menuLinks']);
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
      if ($handle = opendir($config['commentDir'])) {
          $file_array_unsorted = array();
          $file_array_sorted   = array();
          while (false !== ($file = readdir($handle))) {
              array_push($file_array_unsorted,$file);
          }
          $file_array_sorted=array_reverse($file_array_unsorted);
          $commentCount=count($file_array_sorted)-4;
          if ($commentCount > 0) {
              echo '<table><tr><th>'.$lang['pageAllCommentsTitle'].'</th><th>'.$lang['pageAllCommentsDate'].'</th><th>'.$lang['pageAllCommentsBy'].'</th></tr>';
          }
          else {
              echo $lang['pageAllCommentsNo'].'!<br>';
          }

          $statsFile=$config['commentDir']."online".$config['dbFilesExtension'].".dat";
          foreach ($file_array_sorted as $value) {
              $filename=$config['commentDir'].$value;
              if ((file_exists($filename)) && ($filename !== $config['commentDir'].".") && ($filename !== $config['commentDir']."..") && ($filename !== $latestCommentsFile) && ($filename !== $userFileName) && ($filename !== $statsFile)){
                $fileContents = file($filename);
                foreach ($fileContents as $commentContents) {
                    $commentSplit=explode($separator,$commentContents);
                    $commentTitle=$commentSplit[0];
                    $commentAuthor=$commentSplit[1];
                    $commentDate=explode(" ",$commentSplit[3]);
                    $commentDateFormatted=$commentDate[0]." ".$commentDate[1]." ".$commentDate[2];
                    $commentFile=$commentSplit[4];
                    $titleModified=getTitleFromFilename($commentFile);
                    echo "<tr><td><a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$commentFile."/".$titleModified."#Comments>".$commentTitle."</a></td>";
                    echo "<td>".$commentDateFormatted."</td><td>".$commentAuthor."</td></tr>";
                }
              }
          }
          echo "</table>";
          closedir($handle);
      }
      return $file_array_sorted;
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
            $nicPanel="          new nicEditor({fullPanel : true}).panelInstance('posts');";
            break;
      }
      echo '<script type="text/javascript">';
      echo '    bkLib.onDomLoaded(function(){';
      echo $nicPanel;
      echo "          });";
      echo "</script>";
  }

  function newEntryPass() {
      global $debugMode, $optionValue, $lang;
      $fileName = $optionValue;
      echo '<h3>'.$lang['pageNew'].'...</h3>';
      echo "<form name=\"form1\" method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/newEntryForm>";
      echo "<table>";
      echo "<tr><td>".$lang['pageAuthorsNew']."</td>";
      echo "<td><input name=\"author\" type=\"text\" id=\"author\">&nbsp;&nbsp;('admin' for master user)</td></tr>";
      echo "<tr><td>".$lang['pageDeletePass']."</td>";
      echo "<td><input name=\"pass\" type=\"password\" id=\"pass\"></td></tr>";
      echo '</tr><tr><td>&nbsp;</td><td><input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'"></td>';
      echo "</tr></table></form>";
  }


  function newEntryForm() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $blogPath, $lang, $authors, $authorsPass;
      $newPostFileName=$config['postDir'].$newPostFile;
      echo '<h3>'.$lang['pageNew'].'...</h3>';
      if ($debugMode=="on") {
         echo $_SERVER['PHP_SELF']."<br>";
         echo "Post will be written to ".$newPostFileName."  ".$newFullPostNumber;
      }
      $thisAuthor = $_POST['author'];
      $thisPass   = $_POST['pass'];
      $do = 1;
      if (trim($thisAuthor) == "" || trim($thisPass) =="") {
           $lang['errorAllFields'].'<br>';
           $do = 0;
      }
      if ($do == 1) {
          if (is_array($authors)) {
              if (isset($authorsPass[$thisAuthor])) {
                   if ($authorsPass[$thisAuthor] === md5($config['randomString'].$thisPass)) {
                        nicEditStuff();
                        echo "<form method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/newEntrySubmit>";
                        echo "<fieldset>";
                        echo '<legend>'.$lang['pageNewForm'].'</legend>';
                        echo '<p><label for="title">'.$lang['pageNewTitle'].'</label><br>';
                        echo '<input type="text" class="ptitle" name="title" id="title" value=""></p>';
                        echo '<br><label for="posts">'.$lang['pageNewContent'].'</label><br>('.$lang['pageNewReadmore'].')<br>';
                        echo '<textarea name="posts" cols="'.$config['textAreaCols'].'" rows="'.$config['textAreaRows'].'"';
                        echo ' style="height: 400px; width: 400px;" id="posts"></textarea><br><br>';
                        echo '<p><label for="category">'.$lang['pageNewCategory'].'</label><br>';
                        echo '<input type="text" class="ptext" id="category" name="category" value=""></p>';
                        echo '<p><label>'.$lang['pageNewOptions'].'</label><br>';
                        echo '<input type="checkbox" name="allowComments" value="yes" checked="checked">'.$lang['pageNewAllowComments'].'<br>';
                        echo '<input type="checkbox" name="isPage" value="1">'.$lang['pageNewIsPage'].' <a href="javascript:alert(\''.$lang['pageNewIsPageDesc'].'\')">(?)</a></p>';
                        echo '<input name="process" type="hidden" id="process" value="newEntry">';
                        echo '<input name="pass" type="hidden" id="pass" value="'.$authorsPass[$thisAuthor].'">';
                        echo '<input name="author" type="hidden" id="author" value="'.$thisAuthor.'">';
                        echo '<p><input type="submit" value="'.$lang['pageNewSubmit'].'"></p>';
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
      $postTitle=str_replace("\\","",$_POST["title"]);
      $postContent=$_POST["posts"];
      $postDate=date("d M Y h:i");
      $isPage=$_POST["isPage"];
      $allowComments=isset($_POST["allowComments"])?$_POST["allowComments"]:"no";
      $thisAuthor = $_POST['author'];
      $thisPass=$_POST['pass'];
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

      $checkExistingTitle=getfileNameFromTitle($postTitle);
      if ($checkExistingTitle != 0) {
           echo $lang['errorDuplicatePost'].'.<br>';
           $do = 0;
      }
      if ($do == 1) {
          if ($authorsPass[$thisAuthor] === $thisPass) {
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
              $content=$postTitle.$separator.str_replace("\\","",$postContent).$separator.$postDate.$separator.$newFullPostNumber.$separator.$postCategory.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$thisAuthor;
              $fp = fopen($newPostFileName,"w") or die($errorMessage);
              fwrite($fp, $content) or die($errorMessage);
              fclose($fp);
              echo $lang['msgNewPost'];
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
      echo "<table>";
      echo "<tr><td>".$lang['pageAuthorsNew']."</td>";
      echo "<td><input name=\"author\" type=\"text\" id=\"author\">&nbsp;&nbsp;('admin' for master user)</td></tr>";
      echo "<tr><td>".$lang['pageDeletePass']."</td>";
      echo "<td><input name=\"pass\" type=\"password\" id=\"pass\"></td></tr>";
      echo '<input name="process" type="hidden" id="process" value="deleteEntrySubmit">';
      echo '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'"></td>';
      echo '</tr><tr><td>&nbsp;</td><td><input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'"></td>';
      echo "</tr></table></form>";
  }

  function deleteEntrySubmit() {
       global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $optionValue, $lang, $authors, $authorsPass;
       if ($debugMode=="on") {echo "Inside deleteEntrySubmit ..<br>";}
       $entryName= $_POST['fileName'];
       $fileName = $config['postDir'].$entryName.$config['dbFilesExtension'];
       $fp = fopen($fileName, "rb");
       $fullpost=explode($separator,fread($fp, filesize($fileName)));
       fclose($fp);
       $author=$fullpost[8];
       echo "<h3>".$lang['pageDelete']."...</h3>";
       $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errorDeleteEntry'].'<br>';
       $errorMessage=$errorMessage.$lang['errorReportBug'].'<br>';
       $thisAuthor = $_POST['author'];
       $thisPass = md5($config['randomString'].$_POST['pass']);
       if ((($config['authorEditPost'] == 1) && ($thisPass === $authorsPass[$thisAuthor])) ||
           (($config['authorEditPost'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && ($thisPass === $authorsPass[$thisAuthor]))) {
          if (file_exists($fileName)) {unlink($fileName);}
          if (deleteEntryComment($entryName)) {
             echo $lang['msgDeleteSuccess'].'...<br/>';
          }
          else { exit($errorMessage); }
       }
       else {
          echo $lang['errorNotAuthorized'].' .. <br>';
       }
  }

  function deleteEntryComment($entryName) {
       global $config,$separator, $lang;
       $commentFullName=$config['commentDir'].$entryName.$config['dbFilesExtension'];
       $thisCommentFileName=$entryName;
       if (file_exists($commentFullName)) {unlink($commentFullName);}
       $latestFileName=$config['commentDir']."/latest".$config['dbFilesExtension'];
       if (file_exists($latestFileName)) {
             $latestLines= file($latestFileName);
             $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errorLatestFile'].'<br>';
             $errorMessage=$errorMessage.$lang['errorReportBug'].'<br>';
             $fp=fopen($latestFileName, "w") or die($errorMessage);
             $i=0;
             foreach ($latestLines as $value) {
                 $latestSplit=explode($separator,$value);
                 $commentFileName=trim($latestSplit[0]);
                 if (trim($value) != "") {
                     if (($commentFileName == $thisCommentFileName)){
                         //echo "Deleted Indeed!<br>";
                     }
                     else {
                         fwrite($fp,$value);
                     }
                  }
                  $i++;
             }
             fclose($fp);
         }
         if (file_exists($commentFullName)) {return false;}
         else {return true;}
  }

  function editEntryPass() {
      global $debugMode, $optionValue, $lang;
      global $optionValue, $blogPath, $lang;
      $fileName = $optionValue;
      echo '<h3>'.$lang['pageEdit'].'...</h3>';
      echo "<form name=\"form1\" method=\"post\" action=".$_SERVER['SCRIPT_NAME']."/editEntryForm>";
      echo "<table>";
      echo "<tr><td>".$lang['pageAuthorsNew']."</td>";
      echo "<td><input name=\"author\" type=\"text\" id=\"author\">&nbsp;&nbsp;('admin' for master user)</td></tr>";
      echo "<tr><td>".$lang['pageDeletePass']."</td>";
      echo "<td><input name=\"pass\" type=\"password\" id=\"pass\"></td></tr>";
      echo '<input type="hidden" name="fileName" id="fileName" value="'.$fileName.'">';
      echo '<tr><td>&nbsp;</td><td><input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'"></td>';
      echo "</tr></table></form>";
  }


  function editEntryForm() {
      global $separator, $newPostFile, $newFullPostNumber, $debugMode, $config, $authors, $authorsPass;
      global $optionValue, $blogPath, $lang;
      $fileName = $_POST['fileName'];
      $editFileName=$config['postDir'].$fileName.$config['dbFilesExtension'];
      echo "<h3>".$lang['pageEdit']."...</h3>";
      if ($debugMode=="on") {echo "Editing .. ".$editFileName."<br>";}
      $thisAuthor = $_POST['author'];
      $thisPass = md5($config['randomString'].$_POST['pass']);

      if (file_exists($editFileName)) {
        $fp = fopen($editFileName, "rb");
        $fullpost=explode($separator,fread($fp, filesize($editFileName)));
        fclose($fp);
        $title=$fullpost[0];
        $content=$fullpost[1];
        $category=$fullpost[4];
        $postType=$fullpost[5];
        $allowComments=$fullpost[6];
        $visits=$fullpost[7];
        $author=$fullpost[8];
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
        if ((($config['authorEditPost'] == 1) && ($thisPass === $authorsPass[$thisAuthor])) ||
            (($config['authorEditPost'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && ($thisPass === $authorsPass[$thisAuthor]))) {
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
            echo '<br><label for="posts">'.$lang['pageNewContent'].'</label><br>('.$lang['pageNewReadmore'].')<br>';
            echo '<textarea name="posts" cols="'.$config['textAreaCols'].'" rows="'.$config['textAreaRows'].'"';
            echo ' style="height: 400px; width: 400px;" id="posts">';
            echo $content;
            echo '</textarea><br><br>';
            echo '<p><label for="category">'.$lang['pageNewCategory'].'</label><br>';
            $category=str_replace("."," ",$category);
            echo '<input type="text" class="ptext" id="category" name="category" value="'.$category.'"></p>';
            echo '<p><label>'.$lang['pageNewOptions'].'</label><br>';
            echo '<input type="checkbox" name="allowComments" value="yes" '.$checkAllowComments.'>'.$lang['pageNewAllowComments'].'<br>';
            echo '<input type="checkbox" name="isPage" value="1" '.$checking.'>'.$lang['pageNewIsPage'].' <a href="javascript:alert(\''.$lang['pageNewIsPageDesc'].'\')">(?)</a></p>';
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
      else {echo $lang['errorFileNA'].'...<br>';}
  }

  function editEntrySubmit() {
      global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $authors, $authorsPass;
      global $optionValue, $lang;
      if ($debugMode=="on") {echo "Inside editEntrySubmit ..".$_POST['fileName']."<br>";}
      echo "<h3>".$lang['pageEdit']."...</h3>";
      $entryName= $_POST['fileName'];
      $fileName = $config['postDir'].$entryName.$config['dbFilesExtension'];
      $postTitle=str_replace("\\","",$_POST["title"]);
      $postContent=$_POST["posts"];
      $postDate=date("d M Y h:i");
      $isPage=isset($_POST["isPage"])?$_POST["isPage"]:$_GET["isPage"];
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

      $checkExistingTitle=getfileNameFromTitle($postTitle);
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
          if (!file_exists($fileName)) { die($errorMessage); }
          if ($thisPass === $authorsPass[$thisAuthor]) {
              $postCategory=str_replace(" ",".",$postCategory);
              $content=$postTitle.$separator.str_replace("\\","",$postContent).$separator.$postDate.$separator.$entryName.$separator.$postCategory.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$thisAuthor;
              $fp = fopen($fileName,"w") or die($errorMessage);
              fwrite($fp, $content) or die($errorMessage);
              fclose($fp);
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

  function getfileNameFromTitle($fileName1) {
      global $entries, $separator;
      $limit = count($entries);
      for($i = 0; $i < $limit; $i++)
      {
      	     $entry      = explode($separator, $entries[$i]);
	     $fileTitle  =$entry[0];
             $fileEntry  =$entry[3];
             $titleText  = str_replace("-"," ",$fileName1);
             if (strcmp($titleText,$fileTitle) == 0) { return $fileEntry; }
      }
      return 0;
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
        echo '<br>'.$lang['errorInvalidURL'].'<br>';
      }
      if (file_exists($viewFileName) && $cool) {
          $fp = fopen($viewFileName, "rb");
          $entry   =explode($separator,fread($fp, filesize($viewFileName)));
          fclose($fp);
          $title   =$entry[0];
          $titleModified=titleModify($title);
          $contentOrig=$entry[1];
          $content =(str_replace("*readmore*","",$entry[1]));
          $date1   =$entry[2];
          $fileName=$entry[3];
          $category=$entry[4];
          $postType=$entry[5];
          $allowComments=$entry[6];
          $visits  =$entry[7];
          $author  =(trim($entry[8])== "")?'admin':$entry[8];
          if (trim($visits) == "") { $visits=1; }
          else { $visits++; }
          $fileContent=$title.$separator.str_replace("\\","",$contentOrig).$separator.$date1.$separator.$fileName.$separator.$category.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$author;
          $fp = fopen($viewFileName,"w");
          fwrite($fp, $fileContent);
          fclose($fp);
          
          $categoryText=str_replace("."," ",$category);

          echo "<h3>".$title."</h3>";
          echo $content;
          echo '<br><center><i>'.$lang['pageAuthorsNew'].': '.$author.'&nbsp;-&nbsp; '.$lang['postFtPosted'].': '.$date1.'<br>'.$lang['postFtCategory'].': <a href='.$_SERVER['SCRIPT_NAME'].'/viewCategory/'.$category.'>'.$categoryText.'</a>&nbsp;-&nbsp; '.$lang['postFtVisits'].': '.$visits.'</i><br>';
          $commentFile=$config['commentDir'].$fileName.$config['dbFilesExtension'];
          if (file_exists($commentFile)) {
              $commentLines=file($commentFile);
              $commentText=count($commentLines)." ".$lang['postFtComments'];
          }
          else {$commentText=$lang['postFtNoComments'];}

          echo "<a href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified."#Comments>".$commentText."</a>";
          echo "&nbsp;-&nbsp;<a href=".$_SERVER['SCRIPT_NAME']."/editEntry/".$fileName.">".$lang['postFtEdit']."</a>";
          echo "&nbsp;-&nbsp;<a href=".$_SERVER['SCRIPT_NAME']."/deleteEntry/".$fileName.">".$lang['postFtDelete']."</a><br/><br/></center>";


          $commentFullName=$config['commentDir'].$fileName.$config['dbFilesExtension'];
          $i=0;
          echo "<a name='Comments'></a><h3>".$lang['pageViewComments'].":</h3>";

          if($allowComments == "yes")
          {
                if (file_exists($commentFullName)) {
                    $fp = fopen($commentFullName, "rb");
                    $allcomments=explode("\n",fread($fp, filesize($commentFullName)));
                    fclose($fp);
                    foreach ($allcomments as $value) {
                         if (trim($value) != "") {
                             $comment = explode($separator,$value);
                             $title   = $comment[0];
                             $author  = $comment[1];
                             $content = $comment[2];
                             $date    = $comment[3];
                             echo '<a name="'.$comment[5].'">'.$lang['postFtPosted'].' <b>'.$date.'</b> '.$lang['pageViewCommentsby'].' <b>'.$author.'</b><br /><i>'.$title.'</i><br /></a>';
                             echo $content;
                             echo '<br><a href="'.$_SERVER['SCRIPT_NAME'].'/deleteComment/'.$fileName.'/'.$i.'">'.$lang['postFtDelete'].'</a><br><br><br>';
                             $i++;
                         }
                    }
        	}
                else {echo $lang['pageViewCommentsNo']."<br>";}

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
                echo '<p><label for="commentTitle">'.$lang['pageCommentsTitle'].'</label><br>';
                echo '<input type="text" class="ptext" name="commentTitle" id="commentTitle" value=""></p>';
                echo '<p><label for="author">'.$lang['pageCommentsAuthor'].'</label><br>';
                echo '<input type="text" class="ptext" id="author" name="author" value=""></p>';
                echo '<br><label for="comment">'.$lang['pageCommentsContent'].'</label><br>';
                echo '<textarea name="comment" cols="'.$config['textAreaCols'].'" rows="'.$config['textAreaRows'].'"';
                echo ' id="comment"></textarea><br><br>';
		if($config['commentsSecurityCode'] == 1)
		{
			$code = '';
			if($config['onlyNumbersOnCAPTCHA'] == 1)
			{
				$code = substr(rand(0,999999),1,$config['CAPTCHALength']);
			}
			else
			{
				$code = strtoupper(substr(crypt(rand(0,999999), $config['randomString']),1,$config['CAPTCHALength']));
			}
			echo '<p><label for="code">'.$lang['pageCommentsCode'].'</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;('.$code.')</font><br>';
			echo '<input name="code" type="text" id="code"></p>';
                        echo '<input name="originalCode" value="'.$code.'" type="hidden" id="originalCode">';
		}
                /* Commenter password - removed for version 0.7
                echo '<p><label for="pass">'.$lang['pageDeletePass'].'</label><br><font face="Verdana, Arial, Helvetica, sans-serif" size="2">First time? - remember the password you enter here<br>Otherwise - use the same password you used before</br></font><br>';
                echo '<input type="password" class="ptext" id="pass" name="pass" value=""></p>';
                */
                echo '<input name="sendComment" value="'.$fileName.'" type="hidden" id="sendComment">';
                echo '<p><input type="submit" value="'.$lang['pageCommentsSubmit'].'">&nbsp;&nbsp;<input type="reset" value="'.$lang['pageCommentsReset'].'"></p>';
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
        $commentFileName= isset($_POST['sendComment'])?$_POST['sendComment']:$_GET['sendComment'];
	$commentTitle   = isset($_POST['commentTitle'])?$_POST['commentTitle']:$_GET['commentTitle'];
	$author  = isset($_POST['author'])?$_POST['author']:$_GET['author'];
	$comment = isset($_POST['comment'])?$_POST['comment']:$_GET['comment'];
	$pass    = isset($_POST['pass'])?$_POST['pass']:$_GET['pass'];
	$date    = getdate($config_gmt);
	$code    = $_POST['code'];
	$originalCode = $_POST['originalCode'];
	$do = 1;
	$triedAsAdmin = 0;

	if(trim($commentTitle) == '' || trim($author) == '' || trim($comment) == '')
	{
		echo $lang['errorAllFields'].'<br>';
		$do = 0;
	}
        /* Again commenter password commented for version 0.7
        if (strlen($pass) < 5)
        {
                echo $lang['errorPassLength'].'<br>';
                $do = 0;
        }
        */

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

	# Start of author checking, for identity security
	/* Below code commented for version 0.7
        $userFileName=$config['commentDir']."users".$config['dbFilesExtension'].".dat";
	$newUser = 1;
	if (file_exists($userFileName)) {
            $users=file($userFileName);
  	    $data = '';
  	    if ($do == 1) {
                foreach($users as $value)
                {
        		$userLine=explode($separator,$value);
        		if ($userLine[0] == $author) {
                            $newUser=0;
                            if (crypt($pass,trim($userLine[1])) !== trim($userLine[1])) {echo $lang['errorPasswordIncorrect'];$do=0;}
                            else {$do=1;}
                        }
                }
	    }
	}

	if ($newUser == 1 && $do ==1)
	{
                $fp = fopen($userFileName, "a");
		$userContent=$author.$separator.crypt($pass)."\n";
		fwrite($fp,$userContent);
		fclose($fp);
		echo $lang['msgNewUser'].'<br>';
	}
	*/

	if($do == 1)
	{
		if(strlen($comment) > $config['commentsMaxLength'])
		{
		     echo $lang['errorLongComment1'].' '.$config['commentsMaxLength'].' '.$lang['errorLongComment2'].' '.strlen($comment);
		}
                else
		{
                     $commentFullName=$config['commentDir'].$commentFileName.$config['dbFilesExtension'];
 		     if (file_exists($commentFullName)) {$commentLines=file($commentFullName);}
 		     if (trim($commentLines[0])=="") {
                         $thisCommentSeq=1;
                     }
                     else {
 		         $thisCommentSeq=count($commentLines)+1;
                     }
                     $comment=str_replace("\n","",$comment);
                     $commentContent = $commentTitle.$separator.$author.$separator.str_replace("\\","",$comment).$separator.date("d M Y h:i").$separator.$commentFileName.$separator.$thisCommentSeq."\n";
 		     #  Add comment
 		     $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errorCommentFile'].'<br>';
 		     $errorMessage=$errorMessage."<br>If this problem continues, please report as a bug to the author of PRITLOG<br>";
 		     $fp = fopen($commentFullName, "a") or die($errorMessage);
		     fwrite($fp,$commentContent) or die($errorMessage);
		     fclose($fp);

                     # Add coment number to a file with latest comments
                     $errorMessage='<br><span style="color: rgb(204, 0, 51);">Error opening or writing to commentFileName '.$commentFileName.'. <br>Please check the folder permissions<br>';
                     $errorMessage=$errorMessage.'<br>'.$lang['errorReportBug'].'<br>';
		     $fp=fopen($config['commentDir']."/latest".$config['dbFilesExtension'],"a") or die($errorMessage);
                     fwrite($fp,$commentFileName.$separator.$commentTitle.$separator.$thisCommentSeq."\n") or die($errorMessage);
		     fclose($fp);
                     echo $lang['msgCommentAdded'].' '.$author.'!<br />';

                     # If Comment Send Mail is active
		     if($config['sendMailWithNewComment'] == 1)
                     {
		 	 $subject = "PRITLOG: ".$lang['msgMail7'];
		 	 $message = "
                                  <html>
                                  <head>
                                  <title>".$subject."</title>
                                  </head>
                                  <body>".
                                  '<p>'.$lang['msgMail1'].' '.$author.' '.$lang['msgMail2'].'</p>'.
                                  '<p>'.$lang['msgMail3'].': '.$commentTitle.'<br>'.$lang['msgMail4'].': '.str_replace("\\","",$comment).'<br>'.
                                  $lang['msgMail5'].': '.date("d M Y h:i").'</p><p>'.$lang['msgMail6'].'</p>'.
                                  "</body>
                                  </html>
                                  ";

                         // To send HTML mail, the Content-type header must be set
                         $headers  = 'MIME-Version: 1.0' . "\r\n";
                         $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                         // Additional headers
                         $headers .= 'To: '.$config['sendMailWithNewCommentMail']. "\r\n";
                         $headers .= 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
		 	 if (mail($config['sendMailWithNewCommentMail'],
                         $subject,
                         $message,
                         $headers)) {
                               echo '<br>'.$lang['msgMail8'].'.';
                         }
                         else {
                             echo '<br>'.$lang['msgMail9'].'.<br>';
                         }
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
      echo "<table>";
      echo "<tr><td>".$lang['pageAuthorsNew']."</td>";
      echo "<td><input name=\"author\" type=\"text\" id=\"author\">&nbsp;&nbsp;('admin' for master user)</td></tr>";
      echo "<tr><td>".$lang['pageDeletePass']."</td>";
      echo "<td><input name=\"pass\" type=\"password\" id=\"pass\"></td></tr>";
      echo '<input name="process" type="hidden" id="process" value="deleteCommentSubmit">';
      echo '<input name="fileName" type="hidden" id="fileName" value="'.$fileName.'"></td>';
      echo '</tr><tr><td>&nbsp;</td><td><input type="submit" name="Submit" value="'.$lang['pageBasicConfigSubmit'].'"></td>';
      echo "</tr></table></form>";
  }

  function deleteCommentSubmit() {
       global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $lang, $authors, $authorsPass;
       global $fileName;
       if ($debugMode=="on") {echo "Inside deleteCommentSubmit ..<br>";}
       $fileName = $_POST['fileName'];
       $postFile = $config['postDir'].$fileName.$config['dbFilesExtension'];
       $fp = fopen($postFile, "rb");
       $fullpost=explode($separator,fread($fp, filesize($postFile)));
       fclose($fp);
       $author=$fullpost[8];
       $thisAuthor = $_POST['author'];
       $thisPass   = md5($config['randomString'].$_POST['pass']);
       echo "<h3>".$lang['pageCommentDel']."...</h3>";
       $commentNum=$_POST['commentNum'];
       if ((($config['authorDeleteComment'] == 1) && ($thisPass === $authorsPass[$thisAuthor])) ||
           (($config['authorDeleteComment'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && ($thisPass === $authorsPass[$thisAuthor]))) {
            $commentFullName=$config['commentDir'].$fileName.$config['dbFilesExtension'];
            $i=0;
            $j=0;
            if (file_exists($commentFullName)) {
            $allcomments=file($commentFullName);
            $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errorCommentFile'].'<br>';
            $errorMessage=$errorMessage.'<br>'.$lang['errorReportBug'].'<br>';
            $fp=fopen($commentFullName, "w");
            foreach ($allcomments as $value) {
                if (trim($value) != "") {
                    if ($commentNum != $i) {
                        if (fwrite($fp,$value)===FALSE) {
                             echo $lang['errorCommentFile']."<br>";
                        }
                        else { $j++;}
                    }
                    else {
                        $commentSplit=explode($separator,$value);
                        $thisCommentFileName=$commentSplit[4];
                        $thisCommentSeq=$commentSplit[5];
                        echo $lang['msgCommentDeleted']." ...<br>";
                    }
                }
                $i++;
             }
             fclose($fp);
             $i=$i-2;
             if ($j == 0) {unlink($commentFullName);}
             $latestFileName=$config['commentDir']."/latest".$config['dbFilesExtension'];
             if (file_exists($latestFileName)) {
                   $latestLines= file($latestFileName);
                   $errorMessage='<br><span style="color: rgb(204, 0, 51);">'.$lang['errorLatestFile'].'<br>';
                   $errorMessage=$errorMessage.$lang['errorReportBug'].'<br>';
                   $fp=fopen($latestFileName, "w") or die($errorMessage);
                   $i=0;
                   foreach ($latestLines as $value) {
                        $latestSplit=explode($separator,$value);
                        $commentFileName=trim($latestSplit[0]);
                        $commentSeq     =trim($latestSplit[2]);
                        if (trim($value) != "") {
                           if (($commentFileName == $thisCommentFileName) && ($commentSeq == trim($thisCommentSeq))){
                               //echo "Deleted Indeed!<br>";
                           }
                           else {
                               fwrite($fp,$value);
                           }
                        }
                        $i++;
                   }
                   fclose($fp);
               }
    	  }
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
      foreach ($entries as $value) {
          $entry  =explode($separator,$value);
          $title  =$entry[0];
          $titleModified=str_replace(" ","-",$title);
          $content=$entry[1];
          $date1  =explode(",",$entry[2]);
          $date2  =explode(" ",$date1[0]);
          $monthYear=$date2[2]." ".$date2[1];
          array_push($archiveArray,$monthYear);
          $fileName=$entry[3];
          $postType=$entry[5];
      }
      $archiveArrayUnique=array_unique($archiveArray);
      foreach ($archiveArrayUnique as $archiveMonthYear) {
          echo "<a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewArchiveMonth/".str_replace(" ","-",$archiveMonthYear).">".$archiveMonthYear."</a><br>";
      }

  }


  function viewArchiveMonth() {
      global $separator, $entries, $config, $optionValue, $lang;
      $i=0;
      echo "<h3>".$lang['pageArchiveFor']." ".str_replace("-"," ",$optionValue)."</h3>";
      echo "<table>";
      foreach ($entries as $value) {
          $entry  =explode($separator,$value);
          $title  =$entry[0];
          $titleModified=titleModify($title);
          $content=$entry[1];
          $date1  =explode(",",$entry[2]);
          $date2  =explode(" ",$date1[0]);
          $postDate=$entry[2];
          $monthYear=$date2[2]."-".$date2[1];
          $fileName=$entry[3];
          $postType=$entry[5];
          if (strcmp($monthYear,$optionValue) == 0) {
            echo "<tr><td>".$postDate.":&nbsp;</td><td><a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a></td></tr>";
          }
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
          foreach ($entries as $value) {
              $entry  =explode($separator,$value);
              $title  =$entry[0];
              $titleModified=titleModify($title);
              $content=$entry[1];
              $date1  =$entry[2];
              $fileName=$entry[3];
              $category=$entry[4];
              $postType=$entry[5];
              if ((stristr($title,$searchkey)) || (stristr($content,$searchkey))) {
                  echo "<a style='font-style:normal' href=".$_SERVER['SCRIPT_NAME']."/viewEntry/".$fileName."/".$titleModified.">".$title."</a><br/>";
                  $i++;
              }
          }
          if ($i == 0) {echo $lang['errorSearchEmptyResult'];}
      }
  }


?>