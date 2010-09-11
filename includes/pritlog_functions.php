<?php
	function closeUnclosedTags($unclosedString){ 
		$unclosedString = html_entity_decode($unclosedString);
		//$unclosedString = strip_tags($unclosedString, '<b><strong><h2><h3><h4><h5><pre><p><a><img><div><br><br/>');
		//echo substr($unclosedString,0,10)." ";
		preg_match_all("/<([^\/]\w*)>/", $closedString = $unclosedString, $tags); 
		for ($i=count($tags[1])-1;$i>=0;$i--){ 
			$tag = $tags[1][$i]; 
			//if ($tag !== "br") echo substr($unclosedString,0,10)."  ".$tag." ".substr_count($closedString, "</$tag>")." ".substr_count($closedString, "<$tag>")."<br/>";
			if (($tag !== "br") && (substr_count($closedString, "</$tag>") < substr_count($closedString, "<$tag>"))) $closedString .= "</$tag>"; 
		} 
		return $closedString; 
	} 


	function myTruncate($text, $limit, $break=". ", $pad="...") {
		if((strlen($text) <= $limit) || ($limit == 0)) return closeUnclosedTags($text);
		if(false !== ($breakpoint = strpos($text, $break, $limit))) {
			$texta = html_entity_decode($text);
			if (( substr_count($texta, "<div", 0, $breakpoint) == substr_count($texta, "</div", 0, $breakpoint) ) && !strpos(substr($texta,0,$breakpoint),"<div class")) {
				//echo $limit." ".strlen($text)."  ".substr($text,0,50)."<br/>";
				if ( ($breakpoint < strlen($text) - 1) ) { $text = substr($text, 0, $breakpoint) . $pad; }
			}	
			else { 
				//myTruncate($text, $limit+1, $break, $pad);
			}	
		}
		return closeUnclosedTags($text);
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
          $author=(trim($row['author'])=="")?'admin':$row['author'];
          $line=$title.$separator.$content.$separator.$date.$separator.$fileName.$separator.$category.$separator.$postType.$separator.$allowComments.$separator.$visits.$separator.$author;
          array_push($file_array_sorted, $line);
      }
      return $file_array_sorted;
  }


  function sidebarStats() {
      global $config, $separator, $lang;
      $ip=$_SERVER['REMOTE_ADDR'];
      $currentTime=time();
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
      $stats .= $users  . ' '.$lang['sidebarStatsMembersOnline'].'<br>';
      $stats .= $guests . ' '.$lang['sidebarStatsGuestsOnline'].'<br>';
      $stats .= $lang['sidebarStatsHits'].': '.$statcount.'<br>';
      return $stats;
  }


  function listPosts() {
      global $separator, $entries, $config, $requestCategory, $priv;
      global $userFileName, $optionValue3, $lang, $theme_main, $SHP, $theme_post;
	  unset($_SESSION['viewurl']);
      $config_Teaser=0;
      $filterEntries=array();
	  $totalEntries = 0;
	  $theme_main['content'] = '';
	  $theme_general['header'] = '';
      if (trim($requestCategory) == "") {
		  $result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE ".$priv." (type = 'post' or (type = 'page' AND stick = 'yes'));");
		  while ($row = @sqlite_fetch_array($result, SQLITE_ASSOC)) {
              $totalEntries = $row['view'];
              if ($row['view'] == 0) {
                 $theme_general['content'] = '<br><br>'.$lang['msgNoPosts'].' <a href="'.$config['blogPath'].$config['cleanIndex'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
				 $theme_main['content'] = @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_general["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/general.tpl"));;
              }
          }
      }
      else {
			$result = sqlite_query($config['db'], "select count(postid) AS view from posts WHERE ".$priv." type = 'post' AND (category = '$requestCategory' or category like '$requestCategory,%'or category like '%,$requestCategory' or category like '%,$requestCategory,%');");
		    while ($row = @sqlite_fetch_array($result, SQLITE_ASSOC)) {
              $totalEntries = $row['view'];
          }
      }

      if ($totalEntries == 0) {
          $theme_general['content'] = '<br><br>'.$lang['msgNoPosts'].' <a href="'.$config['blogPath'].$config['cleanIndex'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
		  $theme_main['content'] = @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_general["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/general.tpl"));;
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
		   $continue_reading='<br><br><a href="'.$config['blogPath'].$config['cleanIndex'].'/posts/'.$fileName."/".$titleModified.'">'.$lang['pageViewFullPost'].' &raquo;</a>';
           if (strstr($entry[1],"*readmore*")) { $readmore=$continue_reading; }
           else { $readmore=""; }
           $content =explode("*readmore*",$entry[1]);

           $theme_post['loc_top']           = "";
           $theme_post['loc_title_after']   = "";
           $theme_post['loc_content_after'] = "";
           $theme_post['loc_footer']        = "";
           $theme_post['loc_bottom']        = "";
           $theme_post['postLink'] = $config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified;
           $theme_post['title']    = $title;
		   if ($readmore == "")
				$theme_post['content']   = html_entity_decode(myTruncate($content[0],$config['excerptLength'],". ",$continue_reading));
		   else		
				$theme_post['content']  = html_entity_decode(myTruncate($content[0],0,". ",$continue_reading).$readmore);
		   
           $categoryText=str_replace("_"," ",$category);
           $theme_post['authorLabel']    = $lang['pageAuthorsNew1'];
           $theme_post['author']         = $author;
           $theme_post['dateLabel']     = $lang['postFtPosted'];
           $theme_post['date']          = $date1;
           $theme_post['postMonth']     = $postMonth;
           $theme_post['postDay']       = $postDay;
           $theme_post['postYear']      = $postYear;
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
               $theme_post['delete']    = '&nbsp;-&nbsp;<a href="javascript:void(null)" onclick="'.'confirm_delete(\''.$config['blogPath'].$config['cleanIndex']."/deleteEntrySubmit/".$fileName.'\')'.'">'.$lang['postFtDelete']."</a></center><br/>";
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
			$theme_main['pagenav'] = $lang['msgPages'].': ';
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
				$theme_main['pagenav'] .=  '<a href='.$config['blogPath'].$config['cleanIndex'].$categoryText.'/page/'.$i.'>['.$i.']</a> ...';
			}
			elseif($startPage > 1 && $displayed == 0)
			{
				$theme_main['pagenav'] .= '... <a href='.$config['blogPath'].$config['cleanIndex'].$categoryText.'/page/'.$i.'>['.$i.']</a> ';
	 			$displayed = 1;
			}
			else
			{
				$theme_main['pagenav'] .= '<a href='.$config['blogPath'].$config['cleanIndex'].$categoryText.'/page/'.$i.'>['.$i.']</a> ';
			}
		}
		else
		{
			$theme_main['pagenav'] .= '['.$i.'] ';
		}
	    }
	}
  }


  function sidebarListEntries() {
      global $separator, $entries, $config, $theme_main, $priv;
      $i=0;
      $limit = $config['menuEntriesLimit'];
  	  $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." type = 'post' ORDER BY postid desc LIMIT $limit;");
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
      $theme_main['content'] = $theme_general['content'] = "";
      $latestCommentsFile=$config['commentDir']."latest".$config['dbFilesExtension'];
      $userFileName=$config['commentDir']."users".$config['dbFilesExtension'].".dat";
      $theme_general['header'] = $lang['pageAllComments'];
      $result = sqlite_query($config['db'], "select count(commentid) AS view from comments WHERE status = 'approved' and postid in (select postid from posts where ".$priv." 1)");
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $commentCount  = $row['view'];
          if ($commentCount > 0) {
              $theme_general['content'].= '<table><tr><th>'.$lang['pageAllCommentsTitle'].'</th><th>'.$lang['pageAllCommentsDate'].'</th><th>'.$lang['pageAllCommentsBy'].'</th></tr>';
          }
          else {
              $theme_general['content'].= $lang['pageAllCommentsNo'].'!<br>';
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
              $theme_general['content'].= "<tr><td><a style='font-style:normal' href=".$config['blogPath'].$config['cleanIndex']."/posts/".$postid."/".$titleModified."#Comments>".$title."</a></td>";
              $theme_general['content'].= "<td>".$date."</td><td>".$author."</td></tr>";
          }
          $theme_general['content'].= "</table>";
		  $theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_general["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/general.tpl"));
      }
  }
  

  function viewArchive() {
      global $separator, $entries, $config, $lang, $theme_main, $priv;
      $theme_main['content'] = $theme_general['content'] = "";
      $i=0;
	  $theme_general['header'] = $lang['pageArchive'];
      $archiveArray   = array();
      $archiveArrayUnique = array();
      $archiveArrayFormat = array();
      $result = sqlite_query($config['db'], "select * from posts where ".$priv." 1 ORDER BY date;");
      if (sqlite_num_rows($result) == 0) {
          $theme_general['content'].= '<br><br>'.$lang['msgNoPosts'].' <a href="'.$config['blogPath'].$config['cleanIndex'].'/newEntry">'.$lang['msgNoPostsMakeOne'].'</a>?<br>';
      }
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $date          = $row['date'];
          $monthYear     = date("Y-m",strtotime($date));
          array_push($archiveArray,$monthYear);
          $archiveArrayFormat[$monthYear] = date("M Y",strtotime($date));
      }
      $archiveArrayUnique=array_unique($archiveArray);
      foreach ($archiveArrayUnique as $archiveMonthYear) {
          $theme_general['content'].= "<a href=\"".$config['blogPath'].$config['cleanIndex']."/month/".str_replace(" ","-",$archiveMonthYear)."\">".$archiveArrayFormat[$archiveMonthYear]."</a><br>";
      }
	  $theme_main['content'] .= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_general["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/general.tpl"));
  }


  function viewArchiveMonth() {
      global $separator, $entries, $config, $optionValue, $lang, $theme_main, $priv;
      $i=0;
      $theme_main['content'] = $theme_general['content'] = "";
      $theme_general['header'] = $lang['pageArchiveFor']." ".date("M Y",strtotime($optionValue));
      $requestMonth = $optionValue;
      $result = sqlite_query($config['db'], "select * from posts WHERE ".$priv." date LIKE '%$requestMonth%';");
	  $theme_general['content'].= "<table>";
      while ($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
          $title         = $row['title'];
          $titleModified = titleModify($title);
          $postDate      = $row['date'];
          $fileName      = $row['postid'];
          $theme_general['content'].= "<tr><td>".$postDate.":&nbsp;</td><td><a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified.">".$title."</a></td></tr>";
      }
	  $theme_general['content'].= "</table>";
      $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_general["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/general.tpl"));;
  }


  function searchPosts() {
      global $separator, $config, $entries, $lang, $theme_main, $SHP, $public_data, $priv;
      $theme_main['content'] = $theme_general['content'] = "";
      $searchkey   = isset($_POST['searchkey'])?$_POST['searchkey']:$_GET['searchkey'];
      $theme_general['header'] = $lang['pageSearch'];
      $i=0;
      $searchResults = array();
      unset($GLOBALS['$public_data']);
      if (trim($searchkey) == "") {
          $theme_general['content'] .= $lang['errorSearchNothing'].'<br>';
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
                  $theme_general['content'].= "<a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified.">".$title."</a><br/>";
                  $i++;
                  array_push($searchResults, $fileName);
              }
              $searchexpr="";
              foreach ($searcharray as $singlekey) {
                  if ($searchexpr!="") $searchexpr.=" or ";
                  $searchexpr.="(lower(title) like lower('%$singlekey%') or lower(content) like lower('%$singlekey%'))";
              }
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
                      $theme_general['content'].= "<a href=".$config['blogPath'].$config['cleanIndex']."/posts/".$fileName."/".$titleModified.">".$title."</a><br/>";
                      $i++;
                  }
              }
          }
          if ($i == 0) {$theme_general['content'].= $lang['errorSearchEmptyResult'];}
		  $theme_main['content'].= @preg_replace("/\{([^\{]{1,100}?)\}/e","$"."theme_general["."$1"."]",file_get_contents(getcwd()."/themes/".$config['theme']."/blocks/general.tpl"));;
      }
  }


  