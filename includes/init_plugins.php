<?php

//include Simple Hooks Plugin Class
include "SHP.class.php";

//create instance of class
$SHP = new SHP();

//set hook to which plugin developers can assign functions

$SHP->developer_set_hook('hook-test');
$SHP->developer_set_hook('hook-with_args');


// All pages
$SHP->developer_set_hook('hook-head-before');         // Before any HTML, Just for PHP processing
$SHP->developer_set_hook('hook-head');                // In the <head> tags, before main container or after page title
$SHP->developer_set_hook('hook-main');                // Main Page

// Posts
$SHP->developer_set_hook('hook-post');                // The top of every post displayed

// Creating a post
$SHP->developer_set_hook('hook-new');                 // The top of the content area

// Storing the new post in the DB
$SHP->developer_set_hook('hook-newsubmit-before');
$SHP->developer_set_hook('hook-newsubmit-validate');
$SHP->developer_set_hook('hook-newsubmit-after');

// Editing a post
$SHP->developer_set_hook('hook-edit');   // The top of the content area

// Storing the modified post in the DB
$SHP->developer_set_hook('hook-editsubmit-before');
$SHP->developer_set_hook('hook-editsubmit-validate');
$SHP->developer_set_hook('hook-editsubmit-after');

$SHP->developer_set_hook('hook-pluginFunction1');
$SHP->developer_set_hook('hook-pluginFunction2');
$SHP->developer_set_hook('hook-pluginFunction3');
$SHP->developer_set_hook('hook-pluginFunction4');
$SHP->developer_set_hook('hook-pluginFunction5');

$SHP->developer_set_hook('hook-logout');
$SHP->developer_set_hook('hook-login');
$SHP->developer_set_hook('hook-login-replace');
$SHP->developer_set_hook('hook-login-valid');
$SHP->developer_set_hook('hook-login-invalid');
$SHP->developer_set_hook('hook-myprofile');
$SHP->developer_set_hook('hook-myprofile-replace');
$SHP->developer_set_hook('hook-myprofile-validate');
$SHP->developer_set_hook('hook-myprofile-success');
$SHP->developer_set_hook('hook-myprofile-success');
$SHP->developer_set_hook('hook-register');
$SHP->developer_set_hook('hook-register-replace');
$SHP->developer_set_hook('hook-register-validate');
$SHP->developer_set_hook('hook-register-success');
$SHP->developer_set_hook('hook-register-fail');
$SHP->developer_set_hook('hook-forgotpass');
$SHP->developer_set_hook('hook-forgotpass-replace');
$SHP->developer_set_hook('hook-forgotpass-validate');
$SHP->developer_set_hook('hook-forgotpass-success');
$SHP->developer_set_hook('hook-forgotpass-fail');
$SHP->developer_set_hook('hook-activation-success');
$SHP->developer_set_hook('hook-activation-fail');
$SHP->developer_set_hook('hook-searchposts-replace');
$SHP->developer_set_hook('hook-commentform');
$SHP->developer_set_hook('hook-commentform-replace');
$SHP->developer_set_hook('hook-comment-validate');
$SHP->developer_set_hook('hook-comment-success');
$SHP->developer_set_hook('hook-comment-replace');
$SHP->developer_set_hook('hook-comment-fail');
$SHP->developer_set_hook('hook-deletecomment');
$SHP->developer_set_hook('hook-delete-entry');

//set multiple hooks to which plugin developers can assign functions
//$SHP->developer_set_hooks(array('test1','test2', 'with_args'));

//load plugins from folder, if no argument is supplied, a './plugins/' constant will be used
//trailing slash at the end is REQUIRED!
//this method will load all *.plugin.php files from given directory, INCLUDING subdirectories
$SHP->load_plugins(getcwd()."/plugins/");

//now, this is a workaround because plugins, when included, can't access $SHP variable, so we
//as developers have to basically redefine functions which can be called from plugin files
function add_hook($pluginid, $where, $function) {
	global $SHP;
	$SHP->add_hook($pluginid, $where, $function);
}

//same as above
function register_plugin($plugin_id, $data) {
	global $SHP;
	$SHP->register_plugin($plugin_id, $data);
}

?>