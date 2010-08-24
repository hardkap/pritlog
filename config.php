<?php

/***********************************************************************************/
/* The below section has all the configuration options available. Modify as needed */
/***********************************************************************************/

/* PLEASE CHANGE ATLEAST THE FIRST FOUR OPTIONS                                     */
  $config_blogTitle                  = "PRITLOG - Simple and Powerful";              // BLOG Title
  $configPass                        = "password";                                   // Admin password for adding entries
  $config_sendMailWithNewComment     = 1;                                            // Receive a mail when someone posts a comment. (0 = No, 1 = Yes) It works only if you host allows sendmail
  $config_sendMailWithNewCommentMail = "yourid@yourmail.com";                        // Email adress to send mail if allowed

/* THE BELOW ARE OPTIONAL.                                                          */
  $postdir                           = getcwd()."/posts/";                           // Name of the folder where entries will be saved.
  $commentdir                        = getcwd()."/comments/";                        // Name of the folder where comments will be saved.
  $config_menuEntriesLimit           = 7;                                            // Limits of entries to show in the menu
  $config_textAreaCols               = 50;                                           // Cols of the textarea to add and edit entries
  $config_textAreaRows               = 10;                                           // Rows of the textarea to add and edit entries
  $config_entriesPerPage             = 5;                                            // For pagination... How many entries will be displayed per page?
  $config_maxPagesDisplayed          = 5;                                            // Maximum number of pages displayed at the bottom
  $config_allowComments              = 1;                                            // Allow comments
  $config_commentsMaxLength          = 500;                                          // Comment maximum characters
  $config_commentsSecurityCode       = 1;                                            // Allow security code for comments (0 = No, 1 = Yes)
  $config_onlyNumbersOnCAPTCHA       = 0;                                            // Use only numbers on CAPTCHA
  $config_CAPTCHALength              = 8;                                            // Just to make different codes
  $config_randomString               = 'ajhd092nmbd20dbJASDK1BFGAB1';                // Just for creating random captcha. Not used otherwise.
  $config_commentsForbiddenAuthors   = array("admin","Admin");                       // These are the usernames that normal users cant use.
  $config_statsDontLog               = array("127.0.0.1","192.168.0.1");             // These IP will not be considered for logging statistics.
  $config_dbFilesExtension           = ".prt";                                       // Extension of the files used as databases
  $config_usersOnlineTimeout         = 120;                                          // How long is an user considered online? In seconds
  $config_entriesOnRSS               = 0;                                            // 0 = ALL ENTRIES, if you want a limit, change this
  $config_metaDescription            = "Pritlog";                                    // Also for search engines...
  $config_metaKeywords               = 'Pritlog, my blog, pplog';	             // Also for search engines...
  $config_menuLinks                  = array('http://google.com,google', 'http://pplog.infogami.com/,Get PPLOG', 'http://hardkap.net/pritlog,Get PRITLOG');  // Links to be displayed at the menu
  $debugMode                         = "off";                                        // Turn this on for debugging displays. But is not fully functional yet.
  $separator                         = "#~#";                                        // Separator used between fields when the entry files are created.

/********************************************************************************************/
/* END OF CONFIRATION. Dont modify anything below unless you are sure of what you are doing */
/********************************************************************************************/

?>