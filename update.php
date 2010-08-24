<?php

/*#######################################################################
# 	PRITLOG		                                                #
#	                                                                #
#	Version: 0.7                                                    #
#######################################################################*/


  $separator    = "#~#";                                        // Separator used between fields when the entry files are created.

  $config       = array();

  if (file_exists(getcwd().'/config.php')) { require("config.php"); }
  else {die("Old config file not found"); }

  $serverName='http://'.$_SERVER['SERVER_NAME'];
  $serverPort=($_SERVER['SERVER_PORT']=='80')?'':':'.$_SERVER['SERVER_PORT'];
  $scriptName=$_SERVER["SCRIPT_NAME"];
  $blogPath=dirname($serverName.$serverPort.$scriptName);

  $config['blogPath'] = $blogPath;

  $action = (isset($_GET['action']))?$_GET['action']:"startProcess";

  $config['blogTitle']                     =     $config_blogTitle;
  $config['Password']                      =     $configPass;
  $config['sendMailWithNewComment']        =     $config_sendMailWithNewComment;
  $config['sendMailWithNewCommentMail']    =     $config_sendMailWithNewCommentMail;

  $config['postDir']                       =     substr($postdir,strlen(getcwd())+1,-1);
  $config['commentDir']                    =     substr($commentdir,strlen(getcwd())+1,-1);
  $config['menuEntriesLimit']              =     $config_menuEntriesLimit;
  $config['entriesPerPage']                =     $config_entriesPerPage;
  $config['maxPagesDisplayed']             =     $config_maxPagesDisplayed;

  $config['commentsMaxLength']             =     $config_commentsMaxLength;
  $config['commentsSecurityCode']          =     $config_commentsSecurityCode;
  $config['commentsForbiddenAuthors']      =     '';
  if (is_array($config_commentsForbiddenAuthors)) {
      foreach ($config_commentsForbiddenAuthors as $value) {
            $config['commentsForbiddenAuthors'].=','.$value;
      }
  }
  $config['commentsForbiddenAuthors']      =     substr($config['commentsForbiddenAuthors'],1);
  $config['statsDontLog']                  =     '';
  if (is_array($config_statsDontLog)) {
      foreach ($config_statsDontLog as $value) {
            $config['statsDontLog'].=','.$value;
      }
  }
  $config['statsDontLog']                  =     substr($config['statsDontLog'],1);
  $config['entriesOnRSS']                  =     $config_entriesOnRSS;
  $config['metaDescription']               =     $config_metaDescription;
  $config['metaKeywords']                  =     $config_metaKeywords;
  $config['menuLinks']                     =     '';
  if (is_array($config_menuLinks)) {
      foreach ($config_menuLinks as $value) {
            $config['menuLinks'].=';'.$value;
      }
  }
  $config['menuLinks']                     =     substr($config['menuLinks'],1);
  
  if ($config_allowComments == 1) { $commentAllow = "yes"; }
  else { $commentAllow = "no"; }

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">
<head>
<title>
Pritlog Update to 0.412
</title>
<meta name="Keywords" content="Pritlog Update"/>
<meta name="Description" content="Pritlog Update"/>

<style type="text/css">
	body {background:#ccc; font:normal normal 12px Verdana, Arial, Helvetica, sans-serif;}
	#container {width:580px; padding:10px; border:#333 3px solid; background:#fff; color:#333; margin:0 auto; position:relative; padding-bottom:5px;}
	#myhead h1{font-size:26px;}
        h1 {font-size:1.8em; text-align:center;}
		h1 span {color:#aaa;}
	h2 {font-size:1.4em; color:#555; margin:20px 0 5px;}
        .script {display:block; background:#ddd; border:#aaa 1px solid; margin:5px 0; padding:4px;}
	.indent {display:block; padding-left:10px;}
	li {margin-left:20px;padding-left:10px;}
	#myhead {margin-bottom:10px;}
	#topmenu {padding:20px;}
        #topmenu a {padding:5px 10px;border:1px solid silver;color:#008DC2;text-decoration:none;}
	#topmenu a:hover {background-color:#008DC2; color:white;}
	#footer {margin:20px 0px;}
	#content {margin-top:30px;}
	a {color:#008DC2; cursor:pointer;}
	a:hover {color:#333;}
</style>

</head>

<body>

<div id="container">

<h1>Updating Pritlog to 0.7</h1>
<hr>

<p>
<?php mainContent(); ?>
</p>

</div>

</body>

</html>


<?php

  function mainContent() {
      global $action;

      switch ($action) {
        case "startProcess":
             startProcess();
             break;
        case "performProcess":
             performProcess();
             break;
      }
  }

  function startProcess() {
         echo '<p>You are about to update Pritlog to the latest version 0.7.</p>';
         echo '<p><strong>Before you start:</strong><br>';
         echo '<li>Make sure you have copied the latest Pritlog files to your blog path - overwriting any old ones</li></p>';
         echo '<p><strong>All this script does is: </strong><br>';
         echo '<li>Create a new config using your existing config file</li>';
         echo '<li>Convert your posts file to include the new fields added for this release</li></p>';
         echo '<p>It is recommended that you take a backup of your posts and comments directories before the update process</p>';
         echo '<form action="'.$_SERVER['SCRIPT_NAME'].'?action=performProcess" method="post">';
         echo '<p><strong>Ready to start the update process?</strong></p>';
         echo '<p><input type="submit" value="Start update"></p>';
         echo '</form>';
  }

  function performProcess() {
         global $blogPath;
         echo '<p>Starting the process ... </p>';
         createConfig();
         convertPosts();
         echo '<p><strong>You may now delete the following files from your blog path: </strong>';
         echo '<li>bg.gif</li>';
         echo '<li>dog.gif</li>';
         echo '<li>hd.jpg</li>';
         echo '<li>style.css</li>';
         echo '<li>update.php</li>';
         echo '<li>config.php</li></p>';
         echo '<p><strong>CONGRATULATIONS !! Your Pritlog installation has been updated to the latest Beta release</strong></p>';
         echo '<p><a href="'.$blogPath.'">Home page</a></p>';
  }

  function createConfig() {
        global $config;
        echo '<p>Old config entries have been moved to the new config ... </p>';
        setConfigDefaults();
        echo '<p>Other new config values have been populated ... </p>';
        writeConfig();
        echo '<p>New config file has been created ... </p>';
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

  function writeConfig() {
        global $config, $lang;
        $configFile=getcwd()."/".'config_admin.php';
        $configContent='<?php /* ';
        if (file_exists($configFile)) {
            $configContent=$configContent.
                          $config['blogTitle'].'|'.
                          md5($config['randomString'].$config['Password']).'|'.
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
        }
  }

  function convertPosts() {
      global $config, $separator, $commentAllow;
      $fullPostDir = $config['postDir'];
      //echo $fullPostDir.'<br>';
      if (file_exists($fullPostDir)) {
          if ($handle = opendir($fullPostDir)) {
              $file_array_unsorted = array();
              $file_array_sorted   = array();
              while (false !== ($file = readdir($handle))) {
                  array_push($file_array_unsorted,$file);
              }
              rsort($file_array_unsorted);
              foreach ($file_array_unsorted as $value) {
                  $filename=$fullPostDir.$value;
                  //echo $filename.'<br>';
                  if ((file_exists($filename)) && ($filename !== $fullPostDir.".") && ($filename !== $fullPostDir."..")) {
                    $fp = fopen($filename, "rb");
                    $fullContent   = explode($separator,fread($fp, filesize($filename)));
                    fclose($fp);
                    $title         = $fullContent[0];
                    $content       = $fullContent[1];
                    $date          = $fullContent[2];
                    $postid        = $fullContent[3];
                    $category      = str_replace(" ",".",strtolower($fullContent[4]));
                    $type          = $fullContent[5];
                    $allowComments = "yes";
                    $visits        = 0;
                    $author        = 'admin';
                    $writeContent=$title.$separator.$content.$separator.$date.$separator.$postid.$separator.$category.$separator.$type.$separator.$allowComments.$separator.$visits.$separator.$author;
                    //echo $writeContent.'<hr>';
                    $fp = fopen($filename, "w");
                    fwrite($fp,$writeContent);
                    fclose($fp);
                  }
              }
              closedir($handle);
          }
      }
      echo '<p>Posts have been converted to the new format ... </p>';
  }


?>