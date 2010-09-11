<?php
  function adminPage() {
      global $debugMode, $optionValue, $config, $lang, $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_admin['header'] = $lang['titleAdminPage'];

      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          $theme_admin['tabs'] = adminPageTabs();  
		  $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_admin["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/admin.tpl"));
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br>';
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
		  }		
		  $theme_adminbasic['msgtext']  = $msgtext;
		  $theme_adminbasic['msgclass'] = $msgclass;
		  $theme_adminbasic['action'] = $config['blogPath'].$config['cleanIndex']."/adminPageBasic";
          $theme_adminbasic['legend'] = $lang['pageBasicConfig'];
          $theme_adminbasic['titleLabel'] = $lang['pageBasicConfigTitle'];
          $theme_adminbasic['title'] = $config['blogTitle'];
          $theme_adminbasic['pass1']         = $lang['pageBasicConfigNewpass1'];
          $theme_adminbasic['pass2'] = $lang['pageBasicConfigNewpass2'];
          $theme_adminbasic['emailLabel'] = $lang['pageBasicConfigAdminEmail'];
          $theme_adminbasic['email'] = $config['sendMailWithNewCommentMail'];
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
				  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  }
			  else {
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
		  $theme_adminadvanced['excerptLabel']  = $lang['excerptLabel'];
          $theme_adminadvanced['excerptLength'] = $config['excerptLength'];

          $theme_adminadvanced['language'] = '<p><label for="blogLanguage">'.$lang['pageAdvancedConfigLanguage'].'</label><br>';
          $theme_adminadvanced['language'].= '<select name="blogLanguage" id="blogLanguage">';
          $languageDir=getcwd()."/lang";
          if (file_exists($languageDir)) {
              if ($handle = opendir($languageDir)) {
                  $file_array_unsorted = array();
                  while (false !== ($file = readdir($handle))) {
                      if (substr($file,strlen($file)-4) == ".php") {
                          $language=substr($file,0,strlen($file)-4);
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
		  $privacyArray = array(0, 1, 2);
		  if (is_array($privacyArray)) {
              foreach ($privacyArray as $value) {
                  $privacy=$value;
				  ($config['privacy'] == $privacy)?$selected="selected":$selected="";
				  switch ($privacy) {
					case 0: 
						$privacyText = $lang['pageAdvancedConfigPrivacy0'];
						break;
					case 1:
						$privacyText = $lang['pageAdvancedConfigPrivacy1'];
						break;
					case 2:
						$privacyText = $lang['pageAdvancedConfigPrivacy2'];
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
		  if (isset($_POST['excerptLength']) && trim($_POST['excerptLength']) != "") {
               $config['excerptLength'] = $_POST['excerptLength'];
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
				  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  }
			  else {
				  $_SESSION['growlmsg'] = $submit_result;
			  }
			  header('Location: '.$config['blogPath'].$config['cleanIndex'].'/adminPageAuthors');
			  die();
		  }	
		  else if (isset($_POST['authoredit'])) {	
		      $submit_result = adminAuthorsEdit();
			  if ($submit_result === true) {
				  $_SESSION['growlmsg'] = $lang['msgConfigSaved'];
			  }
			  else {
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
          $theme_authoradd['pass1'] = $lang['pageBasicConfigNewpass1'];
          $theme_authoradd['pass2'] = $lang['pageBasicConfigNewpass2'];
          $theme_authoradd['email'] = $lang['pageAuthorsNewEmail'];
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
                  $theme_authoredit['pass2Label'] = $lang['pageBasicConfigNewpass2'];
                  $theme_authoredit['pass2']      = 'newpass'.$i.'2';
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
		$allplugins = "";
        if ($handle = @opendir($plugin_folder)) {
		while (false !== ($file = readdir($handle))) {
			if (is_file($plugin_folder . $file)) {
				if ((strpos($plugin_folder . $file,'.plugin.php') != false) && 
				    (strpos($plugin_folder . $file,'.svn-base') == false)) {
					$plugin_array = $SHP->get_plugin_data($file);
					$pluginid1 = explode(".",$plugin_array['file']);
					$pluginid  = $pluginid1[0];
					$plugin_checked = false;
					if (@$_POST[$pluginid] == 1) {$status = 1; }
					else { $status = 0; }
					$active = false;
                                        if (!isset($_POST['notfirst']) && $plugin_array['active']) {
                                            $active = true;
                                        }
                                        if (isset($_POST['notfirst'])) {
                                            sqlite_query($config['db'], "UPDATE plugins SET status = '$status' WHERE id = '$pluginid';");
                                        }
                                        if ((@$_POST[$pluginid] == 1) || $active) {
                                            $checking='checked="checked"';
                                        }
                                        else {
                                            $checking='';
                                        }
					$allplugins .= '<tr><td><input type="checkbox" name="'.$pluginid.'" value="1" '.$checking.'></td><td><a href="'.$plugin_array['url'].'">'.$plugin_array['name'].'</a></td><td>'.$plugin_array['author'].'</td><td>'.$plugin_array['desc'].'</td></tr>';
				}
			}
			else if ((is_dir($plugin_folder . $file)) && ($file != '.') && ($file != '..')) {
				$allplugins .= list_plugins($plugin_folder . $file . '/');
			}
		}
		closedir($handle);
        }
		return $allplugins;
        plugin_cleanup();
  }

  function adminPagePlugins() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      global $theme_main, $theme_adminplugins, $SHP;
      //$theme_adminplugins['allplugins'] = "";
      $theme_adminplugins['header']  = $lang['pagePlugins'];
      $theme_adminplugins['tabs']    = adminPageTabs();
	  if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          if (isset($_POST['notfirst'])) {
			  $msgtext = $lang['msgConfigSaved'];
			  //$msgclass= "success";
			  $theme_adminplugins['script'] = '<script type="text/javascript">$.jGrowl("'.$lang['msgConfigSaved'].'");</script>';
		  }	
		  $theme_adminplugins['action'] = $config['blogPath'].$config['cleanIndex'].'/adminPluginsSubmit';
		  $plugin_folder = './plugins/';
          $theme_adminplugins['plugins'] = list_plugins($plugin_folder);
          $theme_adminplugins['submit']  = $lang['pageAdvancedConfigSubmit'];
		  $theme_main['content'] = @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_adminplugins["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/adminplugins.tpl"));
       }
       else {
          $theme_main['content'].= $lang['errorPasswordIncorrect'].' .. <br/>';
       }
  }
  
  
  function list_comments_moderate() {
      global $SHP, $theme_main, $config, $optionValue, $lang;
      $message = "";
	  $allcomments = "";
      $result = sqlite_query($config['db'], "select count(commentid) AS view from comments WHERE status = 'pending'");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentCount  = $row['view'];
          if ($commentCount > 0) {
              //$theme_main['content'].= '<table><tr><th>Select</th><th>Comment Title</th><th>Posted By</th></tr>';
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
                  $allcomments.= '<tr><td><input type="checkbox" name="'.$commentid.'" value="1"></td><td><a style="font-style:normal" href="'.$config['blogPath'].$config['cleanIndex'].'/posts/'.$postid.'/'.$titleModified.'#'.$sequence.'">'.$title.'</a></td><td>'.$author.'</td></tr>';
              }
             //$theme_main['content'].= "</table>";
          }
          else {
              //$theme_main['content'].= $lang['pageModerateEmpty'].'!<br>';
          }
      }
	  return $allcomments;

  }

 
  function adminPageModerate() {
      global $debugMode, $optionValue, $config, $lang, $authors, $authorsEmail;
      global $theme_main, $SHP;
      $theme_main['content'] = "";
      $theme_admincomments['header'] = $lang['pageModerate'];
	  $theme_admincomments['tabs']   = adminPageTabs();
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
	  	  $msgclass = "hide";
		  $msgtext	= "";
          if (isset($_REQUEST['notfirst']) || isset($_SESSION['growler'])) {
			  $msgtext = $lang['pageModerateMessage'];
			  //$msgclass= "success";
			  $theme_admincomments['script'] = '<script type="text/javascript">$.jGrowl("'.$lang['pageModerateMessage'].'");</script>';
			  unset($_SESSION['growler']);
		  }	
	      $theme_admincomments['action'] = $config['blogPath'].$config['cleanIndex'].'/adminModerateSubmit';
		  $comments = list_comments_moderate();
		  $theme_admincomments['comments']   = (trim($comments) == "")?$lang['pageModerateEmpty']:$comments;
          $theme_admincomments['approve']    = $lang['pageModerateApprove'];
		  //$_SESSION['growler'] = '<script type="text/javascript">$.jGrowl("'.$lang['pageModerateMessage'].'");</script>';
		  $theme_admincomments['deletelink'] = $config['blogPath'].$config['cleanIndex'].'/adminModerateSubmit/delete';
		  $theme_admincomments['delete']     = $lang['pageModerateDelete'];
          $theme_main['content'] = @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_admincomments["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/admincomments.tpl"));
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
 }

 function getPrivacy() {
	global $priv, $config, $morepriv;
	
	$author = @$_SESSION['username'];
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
    $configFile = getcwd()."/data/".$user."/config.php";
    if (!file_exists($configFile)) {
        $configFile = getcwd()."/data/".$user1."/config.php";
        $user = $user1;
        if (!file_exists($configFile)) {
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
				  'excerptLength',
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
		if ( !isset( $config[ 'excerptLength' ] ) )              { $config[ 'excerptLength' ]              = 250; }
        $config['menuLinksOrig']=$config['menuLinks'];
        $config['menuLinksArray']=explode(';',$config['menuLinks']);

  }

  function writeConfig($message=true) {
        global $config, $lang, $user;
        $configFile=getcwd()."/data/".$user.'/config.php';
        /*$configContent='<?php /* ';*/
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
						  $config['excerptLength'].'|'.
                          $config['cleanUrl'];
			$configContent='<?php /* '.$configContent.' */ ?>';
            $fp=fopen($configFile,"w");
			$fwrite = fwrite($fp,$configContent);
            if ($fwrite === false) {echo 'Error updating config<br/>';}
            fclose($fp);
            if ($message) {echo '<br>'.$lang['msgConfigSaved'].'<br>';}
        }
  }

