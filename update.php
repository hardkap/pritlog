<?php

/*#######################################################################
# 	PRITLOG		                                                        #
#	                                                                    #
#	Version: 0.811                                                      #
#######################################################################*/


  $separator    = "#~#";                                        // Separator used between fields when the entry files are created.

  $config       = array();
  $user 		= "user";
  /*
  if (file_exists(getcwd().'/config_admin.php')) { require("config_admin.php"); }
  else {die("Old config file not found"); }
  */

  $postdb = getcwd()."/data/user/postdb.sqlite";
  if (function_exists('sqlite_open')) {
      if ($config['db'] = sqlite_open($postdb, 0666, $sqliteerror)) {
			//echo "There is an error with the sqlite database .. <br>";	
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
Pritlog Update to 0.811
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

<h1>Updating Pritlog to 0.811</h1>
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
         echo '<p>You are about to update Pritlog to the latest test version 0.811.</p>';
         echo '<p><strong>Before you start:</strong><br>';
         echo '<li>Extract latest Pritlog files to your blog path - overwriting any old ones</li>';
         echo '<li>Take a backup of the files <strong>data/user/config.php</strong>, <strong>data/users/authors.php</strong> and <strong>data/users/postdb.sqlite</strong>.</li>';
         echo '</p>';
         echo '<p><strong>All this script does is: </strong><br>';
         echo '<li>Alter the structure of the Posts table to include the new field (status)</li>';
		 echo '<li>Include the privacy setting in config.php</li>';
         echo '<li>List additional language variables that need to be added</li>';
         echo '</p>';
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
         echo '<p><strong>CONGRATULATIONS !! Your Pritlog installation has been updated to the 0.811</strong></p>';
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

  function loadPosts() {
      global $config;
	  sqlite_query($config['db'], 'CREATE TABLE posts1 (title CHAR(100), content CHAR(4500), date DATETIME, postid PRIMARY KEY, category CHAR(20), type CHAR(5), stick CHAR(5), allowcomments CHAR(4), visits INTEGER, author CHAR(25), status INTEGER);');
	  sqlite_query($config['db'], 'INSERT INTO posts1 SELECT *,1 FROM posts;');
	  sqlite_query($config['db'], 'DROP TABLE posts;');
	  sqlite_query($config['db'], 'CREATE TABLE posts (title CHAR(100), content CHAR(4500), date DATETIME, postid PRIMARY KEY, category CHAR(20), type CHAR(5), stick CHAR(5), allowcomments CHAR(4), visits INTEGER, author CHAR(25), status INTEGER);');
	  sqlite_query($config['db'], 'INSERT INTO posts SELECT * FROM posts1;');
	  sqlite_query($config['db'], 'DROP TABLE posts1');
	  echo '<p>Posts table has been altered to include the new status field ... </p>';
  }

  function readConfig() {
    /* Read config information from file. */
    global $config, $user;	
	$configFile = getcwd()."/data/".$user."/config.php";
    $contents = file( $configFile ) or die("Config file not found in the 'data' subfolder");
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

  function writeConfig() {
        global $config, $lang, $user;
        $configFile = getcwd()."/data/".$user."/config.php";
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
                          $config['timeoutDuration'].'|'.
                          $config['theme'].'|'.
                          $config['commentModerate'].'|'.
                          $config['limitLogins'].'|'.
                          $config['privacy'].'|'.
                          $config['cleanUrl'];
            $configContent=$configContent.' */ ?>';
            $fp=fopen($configFile,"w");
            fwrite($fp,$configContent);
            fclose($fp);
        }
        echo '<p>Config file has been converted to the latest format ... </p>';
  }


?>