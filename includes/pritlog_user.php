<?php
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
          $theme_login['password']     = $lang['pageDeletePass'];
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
          @$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_login["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/login.tpl"));
      }
      if (isset($_SESSION["loginError"])) {
           //$theme_main['content'].= '<br>'.$_SESSION["loginError"].'<br>';
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
              $theme_profile['newPass1'] = $lang['pageBasicConfigNewpass1'];
              $theme_profile['newPass2'] = $lang['pageBasicConfigNewpass2'];
              $theme_profile['authorEmailLabel']    = $lang['pageAuthorsNewEmail'];
              $theme_profile['authorEmail']         = $authorsEmail[$_SESSION['username']];
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
      //$theme_main['content'] = "";
      //$theme_main['content'].= "<h3>".$lang['pageMyProfile']."</h3>";
      $do = 1;
	  $msglog = "";
	  $msgstat = "error";
      if ((isset($_SESSION['logged_in'])?$_SESSION['logged_in']:false) && !(isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false)) {
          $authorEmail=@$_POST['authorEmail'];
          $addAuthor  =$_SESSION['username'];
          if (!$SHP->hooks_exist('hook-myprofile-replace')) {
              if (trim($authorEmail) == "") {
                   //$theme_main['content'].= $lang['errorAllFields'].'<br>';
				   $msglog .= $lang['errorAllFields'].'<br>';
                   $do = 0;
              }
              if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($authorEmail))) {
                   //$theme_main['content'].= $lang['errorInvalidAdminEmail'].'<br>';
				   $msglog .= $lang['errorInvalidAdminEmail'].'<br>';
                   $do = 0;
              }
              if (@$_POST['newpass1'] != @$_POST['newpass2']) {
                   //$theme_main['content'].= $lang['errorNewPasswordsMatch']."<br>";
				   $msglog .= $lang['errorNewPasswordsMatch']."<br>";
                   $do = 0;
              }
              if (strtolower(trim($addAuthor)) == "admin") {
                   //$theme_main['content'].= $lang['errorForbiddenAuthor'].'<br>';
				   $msglog .= $lang['errorForbiddenAuthor'].'<br>';
                   $do = 0;
              }
              if (isset($_POST['newpass1']) && trim($_POST['newpass1']) != "" && strlen($_POST['newpass1']) < 5) {
                 // $theme_main['content'].= $lang['errorPassLength'].'<br>';
				  $msglog .= $lang['errorPassLength'].'<br>';
                  $do = 0;
              }
              if (md5($config['randomString'].@$_POST['origpass']) !== $authorsPass[$addAuthor]) {
                  //$theme_main['content'].= $lang['errorPasswordIncorrect'].'<br>';
				  $msglog .= $lang['errorPasswordIncorrect'].'<br>';
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
              //$theme_main['content'].= '<br>'.$lang['msgConfigSaved'].'.';
              //$theme_main['content'].= '<br>'.$lang['msgConfigLoginAgain'].'.';
			  $msglog  = $lang['msgConfigSaved'].'.';
			  $msglog .= '<br>'.$lang['msgConfigLoginAgain'].'.';
			  $msgstat = "success";
              if ($SHP->hooks_exist('hook-myprofile-success')) {
                  $SHP->execute_hooks('hook-myprofile-success');
              }
          }
      }
      else { 
          if ($SHP->hooks_exist('hook-myprofile-fail')) {
              $SHP->execute_hooks('hook-myprofile-fail');
          }
          $msglog .= $lang['errorInvalidRequest'].'<br>';
      }
	  $data = array ("status" => $msgstat, "out" => $msglog, "func" => "myprofile");
	  echo json_encode($data);	
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
              $theme_register['newPass1']       = $lang['pageBasicConfigNewpass1'];
              $theme_register['newPass2']       = $lang['pageBasicConfigNewpass2'];
              $theme_register['authorEmail'] = $lang['pageAuthorsNewEmail'];
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
    	       $theme_register['securityCode'].= '<input name="code" class="ptext" type="text" id="code"></p>';
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
	  $theme_general['content'] = "";
      $theme_general['header'] = $lang['titleRegisterPage'];
      $do = 1;
	  $msgstat = "error";
	  $msglog = "";
      if (!@isset($_SESSION['logged_in']) && !@$_SESSION['logged_in'] && ($config['allowRegistration'] == 1)) {
          $authorEmail=@$_POST['authorEmail'];
          $addAuthor=@strtolower($_POST['addAuthor']);
          if (!$SHP->hooks_exist('hook-register-replace')) {
              if (isset($authorsPass[$addAuthor])) {
                   //$theme_general['content'].= $lang['errorDuplicateAuthor'].'<br>';
				   $msglog .= $lang['errorDuplicateAuthor'].'<br>';
                   $do = 0;
              }
              if (trim($addAuthor) == "" || trim($authorEmail) == "") {
                   //$theme_main['content'].= $lang['errorAllFields'].'<br>';
				   $msglog .= $lang['errorAllFields'].'<br>';
                   $do = 0;
              }
              if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", trim($authorEmail))) {
                   //$theme_main['content'].= $lang['errorInvalidAdminEmail'].'<br>';
				   $msglog .= $lang['errorInvalidAdminEmail'].'<br>';
                   $do = 0;
              }
              if (@$_POST['newpass1'] != @$_POST['newpass2']) {
                   //$theme_main['content'].= $lang['errorNewPasswordsMatch']."<br>";
				   $msglog .= $lang['errorNewPasswordsMatch']."<br>";
                   $do = 0;
              }
              if (strtolower(trim($addAuthor)) == "admin") {
                   //$theme_main['content'].= $lang['errorForbiddenAuthor'].'<br>';
				   $msglog .= $lang['errorForbiddenAuthor'].'<br>';
                   $do = 0;
              }
              if (@strlen($_POST['newpass1']) < 5) {
                  //$theme_main['content'].= $lang['errorPassLength'].'<br>';
				  $msglog .= $lang['errorPassLength'].'<br>';
                  $do = 0;
              }
              if($config['commentsSecurityCode'] == 1)
    	      {
      	          $code         = isset($_POST['code'])?$_POST['code']:"";
       	          $originalCode = isset($_POST['originalCode'])?$_POST['originalCode']:"";
      	          if ($code !== $originalCode)
      	          {
					  //$theme_main['content'].= $lang['errorSecurityCode'].'<br>';
					  $msglog .= $lang['errorSecurityCode'].'<br>';
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
             $headers = 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
             //echo $authorLine.'<br>';
			if (mail($addEmail,
                 $subject,
                 $message,
                 $headers)) {
                   fwrite($fp,$authorLine);
                   $theme_general['content'].= '<br>'.$lang['titleRegisterThank'].'.';
				   $msgstat = "success";
				   $msglog .= '<br>'.$lang['msgMail9'].'.<br>';
                   if ($SHP->hooks_exist('hook-register-success')) {
                       $SHP->execute_hooks('hook-register-success');
                   }
             }
             else {
                 $theme_general['content'].= '<br>'.$lang['msgMail9'].'.<br>';
				 $msglog .= '<br>'.$lang['msgMail9'].'.<br>';
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
                $headers = 'From: Pritlog <'.$config['sendMailWithNewCommentMail'].'>' . "\r\n";
				mail($config['sendMailWithNewCommentMail'], $subject, $message, $headers);
             }
          }
          else {
              if ($SHP->hooks_exist('hook-register-fail')) {
                  $SHP->execute_hooks('hook-register-fail');
              }
              //$theme_main['content'].= $lang['errorPleaseGoBack'];
          }

       }
       else { 
			//$theme_main['content'].= $lang['errorInvalidRequest'].'<br>';  
			$msglog .= $lang['errorInvalidRequest'].'<br>';  
	   }
	   $data = array ("status" => $msgstat, "out" => $msglog, "func" => "register");
	   echo json_encode($data);
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
      //$theme_main['content'].= "<h3>".$lang['titleForgotPass']."</h3>";
      $do = 1;
	  $msgstat = "error";
	  $msglog = "";
      if (!isset($_SESSION['logged_in']) && !in_array('"'.$_POST['addAuthor'].'"', $authors)) {
          if (!$SHP->hooks_exist('hook-forgotpass-replace')) {
              $addAuthor=$_POST['addAuthor'];
              $addEmail =@$authorsEmail[$addAuthor];
              if (trim($addAuthor) == "") {
                   //$theme_main['content'].= $lang['errorAllFields'].'<br>';
				   $msglog .= $lang['errorAllFields'].'<br>';
                   $do = 0;
              }
              if (strtolower(trim($addAuthor)) == "admin") {
                   //$theme_main['content'].= $lang['errorForbiddenAuthor'].'<br>';
				   $msglog .= $lang['errorForbiddenAuthor'].'<br>';
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
                   //$theme_main['content'].= '<br>'.$lang['titleForgotPassMsg'];
				 $msglog .= '<br>'.$lang['titleForgotPassMsg'];   
				 $msgstat = "success";
             }
             else {
                 //$theme_main['content'].= '<br>'.$lang['msgMail9'].'.<br>';
				 $msglog .= '<br>'.$lang['msgMail9'].'.<br>';
             }
             if ($SHP->hooks_exist('hook-forgotpass-success')) {
                 $SHP->execute_hooks('hook-forgotpass-success');
             }
          }
          else {
              //$theme_main['content'].= $lang['errorPleaseGoBack'];
			  //$msglog .= $lang['errorPleaseGoBack'];
              if ($SHP->hooks_exist('hook-forgotpass-fail')) {
                  $SHP->execute_hooks('hook-forgotpass-fail');
              }
          }
       }
       else {
          //echo $lang['errorInvalidRequest']."<br>";
		  $msglog .= $lang['errorInvalidRequest']."<br>";
       }
	   $data = array ("status" => $msgstat, "out" => $msglog, "func" => "forgotpass");
	   echo json_encode($data);
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
  
  function genRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = '';

    for ($p = 0; $p < $length; $p++) {
        $string .= @$characters[mt_rand(0, strlen($characters))];
    }

    return $string;
  }     