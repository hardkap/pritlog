<?php

//set plugin id as file name of plugin
$plugin_id = basename(__FILE__);

//some plugin data
$data['name']   = "nicEdit Plugin";
$data['desc']   = "Plugin to add nicEdit WYSIWYG editor to Pritlog";
$data['author'] = "Prit";
$data['url']    = "http://hardkap.net";

//register plugin to SHP
register_plugin($plugin_id, $data);

/*
$nicEditType = "";
$nicEditUrl  = "";
*/
function nicedit_function1() {
  global $nicEditUrl, $blogPath, $nicEditType;
  $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  if (file_exists(getcwd().'/plugins/nicFile/nicEditorIcons.gif')) {
       $nicEditType = "nicFile";
       $nicEditUrl  = '<script src="'.$blogPath.'/plugins/nicFile/nicEdit.js" type="text/javascript"></script>';
  }
  elseif (file_exists(getcwd().'/plugins/nicUpload.php')) {
       $nicEditType = "nicUpload";
       //$nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
       $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  }
  else {
       $nicEditType = "default";
       //$nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
       $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  }
}

//plugin function
function nicedit_function2() {
    global $nicEditType, $blogPath, $nicEditUrl;
    global $theme_new;
    switch ($nicEditType) {
      case "nicFile":
          $theme_new['loc_top'] .= $nicEditUrl;
          $_SESSION['auth'] = "allow";
          $nicPanel="          editor1 = new nicEditor({fullPanel : true,  iconsPath : '".$blogPath."/plugins/nicFile/nicEditorIcons.gif'}).panelInstance('posts');";
          break;
      case "nicUpload":
          $theme_new['loc_top'] .= $nicEditUrl;
          $_SESSION['auth'] = "allow";
          $nicPanel="          editor1 = new nicEditor({fullPanel : true,  iconsPath : '".$blogPath."/images/nicEditorIcons.gif', uploadURI : '".$blogPath."/plugins/nicUpload.php?".SID."'}).panelInstance('posts');";
          break;
      case "default":
          $theme_new['loc_top'] .= $nicEditUrl;
          $nicPanel="          editor1 = new nicEditor({fullPanel : true,  iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('posts');";
          break;
    }
    $theme_new['loc_top'] .= '<script type="text/javascript">';
    $theme_new['loc_top'] .= '    bkLib.onDomLoaded(function(){';
    $theme_new['loc_top'] .= $nicPanel;
    //$theme_header['loc_top'] .= "          new nicEditor({buttonList : ['bold','italic','underline','link','unlink'], iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('comment');";
    $theme_new['loc_top'] .= "          });";
    $theme_new['loc_top'] .= "</script>";
}

function nicedit_function3() {
  global $nicEditUrl, $blogPath, $nicEditType;
  $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  if (file_exists(getcwd().'/plugins/nicFile/nicEditorIcons.gif')) {
       $nicEditType = "nicFile";
       $nicEditUrl  = '<script src="'.$blogPath.'/plugins/nicFile/nicEdit.js" type="text/javascript"></script>';
  }
  elseif (file_exists(getcwd().'/plugins/nicUpload.php')) {
       $nicEditType = "nicUpload";
       //$nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
       $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  }
  else {
       $nicEditType = "default";
       //$nicEditUrl  = '<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>';
       $nicEditUrl  = '<script src="'.$blogPath.'/javascripts/nicEdit.js" type="text/javascript"></script>';
  }
}

//plugin function
function nicedit_function4() {
    global $nicEditType, $blogPath, $nicEditUrl;
    global $theme_edit;
    switch ($nicEditType) {
      case "nicFile":
          $theme_edit['loc_top'] .= $nicEditUrl;
          $_SESSION['auth'] = "allow";
          $nicPanel="          editor1 = new nicEditor({fullPanel : true, iconsPath : '".$blogPath."/plugins/nicFile/nicEditorIcons.gif'}).panelInstance('posts');";
          break;
      case "nicUpload":
          $theme_edit['loc_top'] .= $nicEditUrl;
          $_SESSION['auth'] = "allow";
          $nicPanel="          editor1 = new nicEditor({fullPanel : true,  iconsPath : '".$blogPath."/images/nicEditorIcons.gif', uploadURI : '".$blogPath."/plugins/nicUpload.php?".SID."'}).panelInstance('posts');";
          break;
      case "default":
          $theme_edit['loc_top'] .= $nicEditUrl;
          $nicPanel="          editor1 = new nicEditor({fullPanel : true,  iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('posts');";
          break;
    }
    $theme_edit['loc_top'] .= '<script type="text/javascript">';
    $theme_edit['loc_top'] .= '    bkLib.onDomLoaded(function(){';
    $theme_edit['loc_top'] .= $nicPanel;
    //$theme_header['loc_top'] .= "          new nicEditor({buttonList : ['bold','italic','underline','link','unlink'], iconsPath : '".$blogPath."/images/nicEditorIcons.gif'}).panelInstance('comment');";
    $theme_edit['loc_top'] .= "          });";
    $theme_edit['loc_top'] .= "</script>";
}

//add hook, where to execute a function

add_hook($plugin_id, 'hook-new','nicedit_function1');
add_hook($plugin_id, 'hook-new','nicedit_function2');
add_hook($plugin_id, 'hook-edit','nicedit_function3');
add_hook($plugin_id, 'hook-edit','nicedit_function4');


?>