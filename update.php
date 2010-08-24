<?php

/*#######################################################################
 	PRITLOG

	Version: 0.81
#######################################################################*/
  
  // Example loading an extension based on OS
  if (!extension_loaded('sqlite')) {
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		@dl('php_sqlite.dll');
	} else {
		@dl('sqlite.so');
	}
  }   

  $separator    = "#~#";                                        // Separator used between fields when the entry files are created.

  $config       = array();
  /*
  if (file_exists(getcwd().'/config_admin.php')) { require("config_admin.php"); }
  else {die("Old config file not found"); }
  */
  $old_postdb = getcwd()."/data/postdb.sqlite";
  $new_postdb = getcwd()."/data/user/postdb.sqlite";
  if (!file_exists($new_postdb)) {
	  mkdir(getcwd()."/data/user");
	  if (!copy($old_postdb,$new_postdb)) {
		  die("Failed copying postdb.sqlite from $old_postdb to $new_postdb. Please do this manually and rerun this script");
	  } 
  }
  $old_config = getcwd()."/data/config_admin.php";
  $new_config = getcwd()."/data/user/config.php";
  if (!file_exists($new_config)) {
	  if (!copy($old_config,$new_config)) {
		  die("Failed copying config file from $old_config to $new_config. Please do this manually and rerun this script");
	  } 
  }
  $old_authors = getcwd()."/data/authors.php";
  $new_authors = getcwd()."/data/user/authors.php";
  if (!file_exists($new_authors)) {
	  if (!copy($old_authors,$new_authors)) {
		  die("Failed copying config file from $old_authors to $new_authors. Please do this manually and rerun this script");
	  } 
  }
  $postdb = $new_postdb;
  if (function_exists('sqlite_open')) {
      if ($config['db'] = sqlite_open($postdb, 0666, $sqliteerror)) {
          @sqlite_query($config['db'], 'DROP TABLE plugins');
          @sqlite_query($config['db'], 'DROP TABLE ipban');
          @sqlite_query($config['db'], 'DROP TABLE logs');
          sqlite_query($config['db'], 'CREATE TABLE plugins (id PRIMARY KEY, name CHAR(50), author CHAR(50), url CHAR(80), description CHAR(300), status INTEGER);');
          sqlite_query($config['db'], 'CREATE TABLE ipban (id INTEGER PRIMARY KEY, ip CHAR(16));');
          sqlite_query($config['db'], 'CREATE TABLE logs (id INTEGER PRIMARY KEY, ip CHAR(16), action CHAR(30), date DATE);');
          sqlite_query($config['db'], "INSERT INTO plugins (id, name, author, url, description, status) VALUES('nicedit', 'nicEdit Plugin', 'Prit', 'http://hardkap.net/forums', 'Plugin to add nicEdit WYSIWYG editor to Pritlog', 1);");
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
Pritlog Update to 0.81
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

<h1>Updating Pritlog to 0.81</h1>
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
         echo '<p>You are about to update Pritlog to the latest test version 0.81.</p>';
         echo '<p><strong>Before you start:</strong><br>';
		 echo '<li>Delete all files except the following:';
		 echo '<ul><li>data/postdb.sqlite</li><li>data/config_admin.php</li><li>data/authors.php</li></ul></li>';
         echo '<li>Extract latest Pritlog files to your blog path - overwriting any old ones</li>';
         echo '</p>';
         echo '<p><strong>All this script does is: </strong><br>';
         echo '<li>Adds, modifies the new or updated tables</li>';
         echo '<li>Load the tables with appropriate date</li>';
         echo '<li>List additional language variables that need to be added</li>';
         echo '</p>';
         echo '<p>It is recommended that you take a backup of your <strong>data</strong> folder before the update process</p>';
         echo '<form action="'.$_SERVER['SCRIPT_NAME'].'?action=performProcess" method="post">';
         echo '<p><strong>Ready to start the update process?</strong></p>';
         echo '<p><input type="submit" value="Start update"></p>';
         echo '</form>';
  }

  function performProcess() {
         global $blogPath;
         echo '<p>Starting the process ... </p>';
         modifyComments();
         readConfig();
         writeConfig();
         echo '<p><strong>You may now delete the following files/folders from your blog path: </strong><br/>(Keeping a backup of these files for sometime is a great idea too)';
		 echo '<li>data/authors.php</li>';
		 echo '<li>data/config_admin.php</li>';
		 echo '<li>data/postdb.sqlite</li>';
         echo '<p><strong>CONGRATULATIONS !! Your Pritlog installation has been updated to the 0.81</strong></p>';
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


  function modifyComments() {
      global $config, $entries, $separator, $db;
      $result = sqlite_query($config['db'], "select * from comments");
      @sqlite_query($config['db'], 'DROP TABLE comments');
      sqlite_query($config['db'], 'CREATE TABLE comments (commentid INTEGER PRIMARY KEY, postid CHAR(6), sequence INTEGER, author CHAR(25), title CHAR(100), content CHAR(4500), date DATETIME, ip CHAR(16), url CHAR(50), email CHAR(50), status CHAR(10));');
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentid     = $row['commentid'];
          $postid        = $row['postid'];
          $sequence      = $row['sequence'];
          $author        = sqlite_escape_string($row['author']);
          $title         = sqlite_escape_string($row['title']);
          $content       = sqlite_escape_string($row['content']);
          $date          = $row['date'];
          $ip            = $row['ip'];
          $url           = sqlite_escape_string($row['url']);
          $email         = sqlite_escape_string($row['email']);
          $status        = 'approved';
          sqlite_query($config['db'], "INSERT INTO comments (postid, sequence, title, author, content, date, ip, url, email, status) VALUES('$postid', '$sequence', '$title', '$author', '$content', '$date', '$ip', '$url', '$email', '$status');");
      }
      echo '<p>Comment table has been modified ... </p>';
  }


  function readConfig() {
    /* Read config information from file. */
    global $config,$new_config;	
    $contents = file( $new_config ) or die("Config file not found in the 'data' subfolder");
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
        if ( !isset( $config[ 'blogPath' ] ) )                   { $config[ 'blogPath' ]                   = 'http://localhost'; }
        if ( !isset( $config[ 'showCategoryCloud' ] ) )          { $config[ 'showCategoryCloud' ]          = 1; }
        if ( !isset( $config[ 'allowRegistration' ] ) )          { $config[ 'allowRegistration' ]          = 0; }
        if ( !isset( $config[ 'sendRegistMail' ] ) )             { $config[ 'sendRegistMail' ]             = 1; }
        if ( !isset( $config[ 'commentModerate' ] ) )            { $config[ 'commentModerate' ]            = 0; }
        if ( !isset( $config[ 'timeoutDuration' ] ) )            { $config[ 'timeoutDuration' ]            = 900; }
		if ( !isset( $config[ 'limitLogins' ] ) )                { $config[ 'limitLogins' ]                = 10; }
        if ( !isset( $config[ 'cleanUrl' ] ) )                   { $config[ 'cleanUrl' ]                   = 0; }
		$config['menuLinksOrig']=$config['menuLinks'];
        $config['menuLinksArray']=explode(';',$config['menuLinks']);

  }

  function writeConfig() {
        global $config, $lang, $new_config;
        $configFile=$new_config;
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
						  $config['cleanUrl'];
            $configContent=$configContent.' */ ?>';
            $fp=fopen($configFile,"w");
            fwrite($fp,$configContent);
            fclose($fp);
        }
        echo '<p>Config file has been converted to the latest format ... </p>';
  }


?>