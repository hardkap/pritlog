<?php

/*#######################################################################
# 	PRITLOG		                                                #
#	                                                                #
#	Version: 0.8                                                    #
#######################################################################*/


  $separator    = "#~#";                                        // Separator used between fields when the entry files are created.

  $config       = array();
  /*
  if (file_exists(getcwd().'/config_admin.php')) { require("config_admin.php"); }
  else {die("Old config file not found"); }
  */

  $postdb = getcwd()."/data/postdb.sqlite";
  if (function_exists('sqlite_open')) {
      if ($db = sqlite_open($postdb, 0666, $sqliteerror)) {
          @sqlite_query($db, 'DROP TABLE posts');
          @sqlite_query($db, 'DROP TABLE comments');
          @sqlite_query($db, 'DROP TABLE stats');
          @sqlite_query($db, 'DROP TABLE active_guests');
          @sqlite_query($db, 'DROP TABLE active_users');
          sqlite_query($db, 'CREATE TABLE posts (title CHAR(100), content CHAR(4500), date DATETIME, postid PRIMARY KEY, category CHAR(20), type CHAR(5), stick CHAR(5), allowcomments CHAR(4), visits INTEGER, author CHAR(25));');
          sqlite_query($db, 'CREATE TABLE comments (commentid INTEGER PRIMARY KEY, postid CHAR(6), sequence INTEGER, author CHAR(25), title CHAR(100), content CHAR(4500), date DATETIME, ip CHAR(16), url CHAR(50), email CHAR(50));');
          sqlite_query($db, 'CREATE TABLE stats (statid INTEGER PRIMARY KEY, stattype CHAR(10), statcount INTEGER);');
          sqlite_query($db, 'CREATE TABLE active_guests (id INTEGER PRIMARY KEY, ip CHAR(16), logtime DATETIME);');
          sqlite_query($db, 'CREATE TABLE active_users (id INTEGER PRIMARY KEY, ip CHAR(16), logtime DATETIME);');
          $config['postDir']=getcwd().'/posts/';
          $config['commentsDir']=getcwd().'/comments/';
          $config['statFile']=getcwd()."/comments/online.prt.dat";
          $entries = getPosts();
          //unset($db);
      }
      else {die("Update failed. Please make sure the <strong>data</strong> folder has write permissions."); }
  }
  else { die("Your server does not seem to have Sqlite enabled. This version of Pritlog will not work without Sqlite."); }

  $serverName='http://'.$_SERVER['SERVER_NAME'];
  $serverPort=($_SERVER['SERVER_PORT']=='80')?'':':'.$_SERVER['SERVER_PORT'];
  $scriptName=$_SERVER["SCRIPT_NAME"];
  $blogPath=dirname($serverName.$serverPort.$scriptName);

  $config['blogPath'] = $blogPath;

  $action = (isset($_GET['action']))?$_GET['action']:"startProcess";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">
<head>
<title>
Pritlog Update to 0.8
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

<h1>Updating Pritlog to 0.8</h1>
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
         echo '<p>You are about to update Pritlog to the latest test version 0.8.</p>';
         echo '<p><strong>Before you start:</strong><br>';
         echo '<li>Extract latest Pritlog files to your blog path - overwriting any old ones</li>';
         echo '<li>Move the files <strong>config_admin.php</strong> and <strong>authors.php</strong> to the "data" folder (overwriting the existing files in this folder)</li>';
         echo '</p>';
         echo '<p><strong>All this script does is: </strong><br>';
         echo '<li>Load the posts and comments to Sqlite database</li>';
         echo '<li>Create additional tables in the Sqlite database</li>';
         echo '<li>List additional language variables that need to be added</li>';
         echo '</p>';
         echo '<p>It is recommended that you take a backup of your posts and comments directories before the update process</p>';
         echo '<form action="'.$_SERVER['SCRIPT_NAME'].'?action=performProcess" method="post">';
         echo '<p><strong>Ready to start the update process?</strong></p>';
         echo '<p><input type="submit" value="Start update"></p>';
         echo '</form>';
  }

  function performProcess() {
         global $blogPath;
         echo '<p>Starting the process ... </p>';
         readConfig();
         writeConfig();
         loadPosts();
         loadComments();
         loadStats();
         echo '<p><strong>You may now delete the following files/folders from your blog path: </strong>';
         echo '<li>posts</li>';
         echo '<li>comments</li>';
         echo '<li>update.php</li>';
         echo '<p><strong>CONGRATULATIONS !! Your Pritlog installation has been updated to the 0.8</strong></p>';
         echo '<p><a href="'.$blogPath.'">Home page</a></p>';
         translationInfo();
  }

  function translationInfo() {
      global $config, $lang;
      //echo $config['blogLanguage'].'<br>';
      if ($config['blogLanguage'] !== "english-us" ) {
          require("lang/english-us.php");
          $langArray = array();
          $langArrayValue = array();
          $langArrayValue = $lang;
          foreach ($lang as $key => $value) {
              array_push($langArray,$key);
          }
          unset($lang);
          require("lang/".$config['blogLanguage'].".php");
          echo '<br><strong>TRANSLATION REQUIRED: </strong><br>';
          echo '<p>Since you are not using the default language (english-us), the following additional language variables must be added to your language file. Please refer the english-us.php language file... </p>';
          echo '<table>';
          echo '<tr><th align="left"><strong>Language variable</strong></th><th align="left"><strong>English Text</strong></th></tr>';
          foreach ($langArray as $value) {
              if (isset($lang[$value])) continue;
              else echo "<tr><td>\$lang[".$value."]</td><td>=&nbsp;&nbsp;'".$langArrayValue[$value]."'</td></tr>";
          }
          echo "</table>";
      }
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
              sort($file_array_unsorted);
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

  function loadPosts() {
      global $config, $entries, $separator, $db;
      foreach ($entries as $value) {
          $posts         =explode($separator,$value);
          $title         =trim(sqlite_escape_string($posts[0]));
          $content       =sqlite_escape_string($posts[1]);
          $date          =date("Y-m-d H:i:s",strtotime(trim($posts[2])));
          $postid        =trim($posts[3]);
          $category      =trim(sqlite_escape_string($posts[4]));
          $type          =trim($posts[5]);
          $allowComments =$posts[6];
          $visits        =$posts[7];
          $author        =trim(sqlite_escape_string($posts[8]));
          $stick         ="no";
          //echo $visits.'<br>';
          //echo $title.' '.$date.' '.trim($posts[2]).'<br>';
          //echo $postid.'  '.$content.'<br>';
          sqlite_query($db, "INSERT INTO posts (postid, title, content, date, category, type, stick, allowcomments, visits, author) VALUES('$postid', '$title', '$content', '$date', '$category', '$type', '$stick', '$allowComments','$visits', '$author');");
      }
      echo '<p>Posts have been loaded to the Sqlite database ... </p>';
  }

  function loadComments() {
      global $config, $db, $separator;
      if (file_exists($config['commentsDir'])) {
          if ($handle = opendir($config['commentsDir'])) {
              $file_array_unsorted = array();
              $file_array_sorted   = array();
              while (false !== ($file = readdir($handle))) {
                  array_push($file_array_unsorted,$file);
              }
              sort($file_array_unsorted);
              foreach ($file_array_unsorted as $value) {
                  $filename=$config['commentsDir'].$value;
                  if ((file_exists($filename)) && ($filename !== $config['commentsDir'].".") && ($filename !== $config['commentsDir']."..") && strstr($filename,'00') && strstr($filename,'.prt')) {
                     $fp = fopen($filename, "rb");
                     $allcomments=explode("\n",fread($fp, filesize($filename)));
                     fclose($fp);
                     foreach ($allcomments as $value) {
                        if (trim($value) != "") {
                             $comment = explode($separator,$value);
                             $title   = sqlite_escape_string($comment[0]);
                             $author  = sqlite_escape_string($comment[1]);
                             $content = sqlite_escape_string($comment[2]);
                             $date    = date("Y-m-d H:i:s",strtotime(trim($comment[3])));
                             $postid  = $comment[4];
                             $sequence= $comment[5];
                             $ip      = '127.0.0.1';
                             $url     = '';
                             $email   = '';
                             sqlite_query($db, "INSERT INTO comments (postid, sequence, title, author, content, date, ip, url, email) VALUES('$postid', '$sequence', '$title', '$author', '$content', '$date', '$ip', '$url', '$email');");
                             //echo $title.' '.$date.' '.trim($comment[3]).'<br>';
                        }
                     }
                  }
              }
              closedir($handle);
          }
      }
      echo '<p>Comments have been loaded to the Sqlite database ... </p>';
  }

  function loadStats() {
      global $config, $db, $separator;
      $filename=$config['statFile'];
      if (file_exists($filename)) {
          $fp = fopen($filename, "rb");
          $allstats=explode("\n",fread($fp, filesize($filename)));
          fclose($fp);
          $total = count($allstats);
          $stattype  = "total";
          $statcount = $total;
          //echo $statcount.'<br>';
          sqlite_query($db, "INSERT INTO stats (stattype, statcount) VALUES('$stattype', '$statcount');");
      }
      echo '<p>Statistics have been updated to the Sqlite database ... </p>';
  }

  function readConfig() {
    /* Read config information from file. */
    global $config;

    $contents = file( getcwd()."/data/".'config_admin.php' ) or die("Config file not found in the 'data' subfolder");
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
        if ( !isset( $config[ 'allowRegistration' ] ) )          { $config[ 'allowRegistration' ]          = 0; }
        if ( !isset( $config[ 'sendRegistMail' ] ) )             { $config[ 'sendRegistMail' ]             = 1; }
        if ( !isset( $config[ 'timeoutDuration' ] ) )            { $config[ 'timeoutDuration' ]            = 900; }
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
        }
        echo '<p>Config file has been converted to the latest format ... </p>';
  }


?>