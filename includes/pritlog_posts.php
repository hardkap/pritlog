<?php
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
                        $theme_new['content']          = $lang['pageNewContent'];
                        $theme_new['readmore']         = $lang['pageNewReadmore'];
                        $theme_new['textAreaCols']     = $config['textAreaCols'];
                        $theme_new['textAreaRows']     = $config['textAreaRows'];
                        $theme_new['category']         = $lang['pageNewCategory'];
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
      global $theme_main, $SHP, $public_data, $blogPath, $priv;
      $newPostFileName=$config['postDir'].$newPostFile;
	  $msglog = "";
      unset($GLOBALS['$public_data']);
      $public_data['postTitle']     = $postTitle=@htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["title"])));
      $public_data['postContent']   = $postContent=@htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["posts"])));
      $public_data['postDate']      = $postDate=date("Y-m-d H:i:s");
      $public_data['isPage']        = $isPage=@isset($_POST["isPage"])?$_POST["isPage"]:0;
      $public_data['stick']         = $stick=@isset($_POST["isSticky"])?$_POST["isSticky"]:"no";
	  $public_data['status']        = $status=@isset($_POST["isDraft"])?$_POST["isDraft"]:1;
      $public_data['allowComments'] = $allowComments=@isset($_POST["allowComments"])?$_POST["allowComments"]:"no";
      $public_data['thisAuthor']    = $thisAuthor = @$_POST['author'];
      $visits=0;
      $public_data['postCategory'] = $postCategory=@htmlentities(sqlite_escape_string(removeAccent(strtolower($_POST["category"]))));
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

      if(trim($postTitle) == '' || trim($postContent) == '' || trim($postCategory) == '' || strstr($postCategory,'.') || strlen(trim($postContent)) <= 15 )
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
	  $msgstat = "error";
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
			  $msgstat = "success";
			  //header('Location: '.$config['blogPath'].$config['cleanIndex'].'/newEntrySuccess');
			  //header('Location: '.$config['blogPath'].$config['cleanIndex']);
			  //die();
          }
          else {
		      $msgstat = "error";
              $msglog .= $lang['errorPasswordIncorrect'].'<br>';
          }
      }
      //$_SESSION['growlmsg'] = $msglog;
	  //header('Location: '.$_SESSION['referrer']);
	  $data = array ("status" => $msgstat, "out" => $msglog, "func" => "newentry");
	  echo json_encode($data);	
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
                $thisPass   = @$authorsPass[$thisAuthor];
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
            $theme_edit['labelContent'] = $lang['pageNewContent'];
            $theme_edit['readmore']     = $lang['pageNewReadmore'];
            $theme_edit['textAreaCols']     = $config['textAreaCols'];
            $theme_edit['textAreaRows']     = $config['textAreaRows'];
            $theme_edit['content']     = $content;
            $theme_edit['labelCategory']     = $lang['pageNewCategory'];
            $category=str_replace("_"," ",$category);
            $theme_edit['category']     = $category;
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
            $theme_edit['hidden'].= '<input name="pass" type="hidden" id="pass" value="'.@$thisPass.'">';
            $theme_edit['submit'] = $lang['pageEditSubmit'];
            $theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_edit["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/editentry.tpl"));
        }
        else {
          $theme_main['content'] .= $lang['errorNotAuthorized'].' .. <br>';
       }

      }
  }

  function editEntrySubmit() {
      global $separator, $newPostFile, $newFullPostNumber, $config, $debugMode, $authors, $authorsPass;
      global $optionValue, $lang, $theme_main, $SHP, $public_data, $blogPath;
      $theme_main['content'] = "";
      if ($debugMode=="on") {$theme_main['content'] .= "Inside editEntrySubmit ..".$_POST['fileName']."<br>";}
      $theme_main['content'] .= "<h3>".$lang['pageEdit']."...</h3>";
      unset($GLOBALS['$public_data']);
      $public_data['entryName']     = $entryName= @$_POST['fileName'];
      $public_data['postTitle']     = $postTitle=@htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["title"])));
      $public_data['postContent']   = $postContent=@htmlentities(sqlite_escape_string(str_replace("\\","",$_POST["posts"])));
      $public_data['postDate']      = $postDate=date("Y-m-d H:i:s");
      $public_data['isPage']        = $isPage=@isset($_POST["isPage"])?$_POST["isPage"]:0;
      $public_data['stick']         = $stick=@isset($_POST["isSticky"])?$_POST["isSticky"]:"no";
	  $public_data['status']        = $status=@isset($_POST["isDraft"])?$_POST["isDraft"]:1;
      $public_data['allowComments'] = $allowComments=@isset($_POST["allowComments"])?$_POST["allowComments"]:"no";
      $public_data['visits']        = $visits=@isset($_POST["visits"])?$_POST["visits"]:0;
      $public_data['postCategory']  = $postCategory=@htmlentities(sqlite_escape_string(removeAccent(strtolower($_POST["category"]))));
      $public_data['thisAuthor']    = $thisAuthor = @$_POST['author'];
      $thisPass   = @$_POST['pass'];
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
      if(trim($postTitle) == '' || trim($postContent) == '' || trim($postCategory) == '' || strstr($postCategory,'.') || strlen(trim($postContent)) <= 15 )
      {
      	   $msglog = $lang['errorAllFields'].'.<br>';
      	   $msglog .= $lang['errorCatName'].'<br>';
	       $do = 0;
      }
	  $msgstat = "error";
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
              $rc = sqlite_query($config['db'], "UPDATE posts SET title='$postTitle', content='$postContent', category='$postCategory', type='$postType', stick='$stick', status = '$status', allowcomments='$allowComments', visits='$visits', author='$thisAuthor' WHERE postid='$entryName';");
			  if ($rc) {
			     $msgstat = "success";
				 @$msglog .= $lang['msgEditSuccess'];
			  }	 
			  else 	
			     @$msglog .= "Strange!!";
              if ($SHP->hooks_exist('hook-editsubmit-after')) {
                 $SHP->execute_hooks('hook-editsubmit-after');
              }
          }
          else {
              $msglog .= $lang['errorNotAuthorized'].' .. <br>';
          }
      }
	  //$_SESSION['growlmsg'] = $msglog;
	  //header('Location: '.$_SESSION['referrer']);
	  $data = array ("status" => $msgstat, "out" => $msglog, "func" => "editentry");
	  //fwrite(fopen("debug.txt","w"),json_encode($data)."\nEdit entry"."\n");
	  echo json_encode($data);	  
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
		  $_SESSION['viewurl'] = $theme_post['postLink'];
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
              //$theme_post['delete'] = "&nbsp;-&nbsp;<a href=".$config['blogPath'].$config['cleanIndex']."/deleteEntry/".$fileName.">".$lang['postFtDelete']."</a>";
			  $theme_post['delete'] = '&nbsp;-&nbsp;<a href="javascript:void(null)" onclick="'.'confirm_delete(\''.$config['blogPath'].$config['cleanIndex']."/deleteEntrySubmit/".$fileName.'\')'.'">'.$lang['postFtDelete']."</a>";
          }

          if ($postType == "page") {
			$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_post["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/page.tpl"));
		  }
		  else {	
			  $theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_post["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/post.tpl"));
			  $commentFullName=$config['commentDir'].$fileName.$config['dbFilesExtension'];
			  $i=0;
			  $theme_commentlist['header'] = $lang['pageViewComments'];
			  $theme_commentlist['comments'] = "";
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
								//$theme_comment['delete'] = '<a href="'.$config['blogPath'].$config['cleanIndex'].'/deleteComment/'.$fileName.'/'.$sequence.'">'.$lang['postFtDelete'].'</a>';
								$theme_comment['delete']    = '<a href="javascript:void(null)" onclick="'.'confirm_delete(\''.$config['blogPath'].$config['cleanIndex'].'/deleteCommentSubmit/'.$fileName.'/'.$sequence.'\')'.'">'.$lang['postFtDelete']."</a>";
								if (isset($_SESSION['isAdmin'])?$_SESSION['isAdmin']:false) {
									$theme_comment['ip'] =  '&nbsp;&nbsp;-&nbsp;&nbsp;'.$ip;
								}
							}
							$theme_commentlist['comments'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_comment["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/comment.tpl"));
							$i++;
						}
						if ($i == 0) {$theme_commentlist['comments'] .= $lang['pageViewCommentsNo']."<br>";}
					}
					$theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_commentlist["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/commentlist.tpl"));

					if ($SHP->hooks_exist('hook-commentform-replace')) {
						$SHP->execute_hooks('hook-commentform-replace');
					}
					else {
						$theme_commentform['nicEdit']  = '';
						$theme_commentform['nicEdit'] .= $nicEditUrl;
						$theme_commentform['nicEdit'] .= '<br /><br /><h3>'.$lang['pageComments'].'</h3>';
						$theme_commentform['nicEdit'] .= '<script type="text/javascript">';
						$theme_commentform['nicEdit'] .= '    bkLib.onDomLoaded(function(){';
						$theme_commentform['nicEdit'] .= "          editor1 = new nicEditor({buttonList : ['bold','italic','underline','link','unlink'], iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('comment');";
						$theme_commentform['nicEdit'] .= "          });";
						$theme_commentform['nicEdit'] .= "</script>";
		
		
						$theme_commentform['commentAction'] = $config['blogPath'].$config['cleanIndex']."/sendComment";
						$theme_commentform['legend']        = $lang['pageCommentsForm'];
						$theme_commentform['authorLabel']   = $lang['pageCommentsAuthor'];
						$theme_commentform['required']      = $lang['pageCommentsRequired'];
						$theme_commentform['emailLabel']     = $lang['pageAuthorsNewEmail'];
						$theme_commentform['optional']       = $lang['pageCommentsOptionalEmail'];
						$theme_commentform['url']           = $lang['pageCommentsUrl'];
						$theme_commentform['optionalUrl']   = $lang['pageCommentsOptionalUrl'];
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
					$theme_commentform['securityCode'].= '<input name="code" class="ptext" type="text" id="code"><p>';
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
    $public_data['commentFileName'] = $commentFileName = @isset($_POST['sendComment'])?$_POST['sendComment']:"";
	$public_data['author']          = $author          = @isset($_POST['author'])?$_POST['author']:"";
	$public_data['commentTitle']    = $commentTitle    = $lang['pageCommentsBy'].' '.$author;
	$public_data['comment']         = $comment         = @isset($_POST['comment'])?$_POST['comment']:"";
	$public_data['url']             = $url             = @isset($_POST['commentUrl'])?$_POST['commentUrl']:"";
	$public_data['email']           = $email           = @isset($_POST['commentEmail'])?$_POST['commentEmail']:"";
	$public_data['code']            = $code            = @$_POST['code'];
	$public_data['originalCode']    = $originalCode    = @$_POST['originalCode'];
	$do              = 1;
	$triedAsAdmin    = 0;
	$msgstat = "error";
	$msglog  = "";
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

			if(trim($commentTitle) == '' || trim($author) == '' || trim($comment) == '' || strlen(trim($comment)) <=10 )
    	    {
				$theme_main['content'] .= $lang['errorAllFields'].'<br>';
				$msglog .= $lang['errorAllFields'].'<br>';
				$do = 0;
    	    }

    	    if($config['commentsSecurityCode'] == 1)
    	    {
				$originalCode = @$_POST['originalCode'];
				if ($code !== $originalCode)
				{
					$theme_main['content'] .= $lang['errorSecurityCode'].'<br>';
					$msglog .= $lang['errorSecurityCode'].'<br>';
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
				$msglog .= $lang['errorCommentUser1']." ".$author." ".$lang['errorCommentUser2'];
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
			 $msglog .= $lang['errorLongComment1'].' '.$config['commentsMaxLength'].' '.$lang['errorLongComment2'].' '.strlen($comment);
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
			$msgstat = "success";
			$msglog = $_SESSION['growlmsg'] = $_SESSION['message'];
		}
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
	$data = array ("status" => $msgstat, "out" => $msglog, "func" => "addcomment");
    echo json_encode($data);	   	
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
       global $fileName, $theme_main, $SHP, $public_data, $priv, $optionValue, $optionValue2;
       $theme_main['content'] = "";
	   $msglog = "";
       if ($debugMode=="on") {echo "Inside deleteCommentSubmit ..<br>";}
       $public_data['fileName']   = $fileName   = $optionValue;
       $public_data['commentNum'] = $commentNum = $optionValue2;
       $postFile = $config['postDir'].$fileName.$config['dbFilesExtension'];
       $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." postid = '$fileName';");
       while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
           $author = $row['author'];
       }
       $public_data['thisAuthor'] = $thisAuthor = $_SESSION['username'];
       $theme_main['content'] .= "<h3>".$lang['pageCommentDel']."...</h3>";
       if ((($config['authorDeleteComment'] == 1) && ($_SESSION['logged_in'])) ||
           (($config['authorDeleteComment'] == 0) && ($thisAuthor == 'admin' || $thisAuthor == $author) && ($_SESSION['logged_in']))) {
            sqlite_query($config['db'], "delete from comments WHERE postid = '$fileName' and sequence = '$commentNum';");
			$theme_main['content'] .= $lang['msgCommentDeleted'].'...<a href="'.$_SESSION['referrer'].'">'.$lang['msgGoBack'].'</a>';
			$msglog .= $lang['msgCommentDeleted'];
            unset($GLOBALS['$public_data']);
            if ($SHP->hooks_exist('hook-deletecomment')) {
                $SHP->execute_hooks('hook-deletecomment', $public_data);
            }
       }
       else {
          $theme_main['content'] .= $lang['errorNotAuthorized'].' .. <br>';
		  $msglog .= $lang['errorNotAuthorized'].' .. <br>';
       }
	   $_SESSION['growlmsg'] = $msglog;
	   header('Location: '.$_SESSION['viewurl']);
  }

