<?php
/*
Plugin Name: Simple Support
Plugin URI: http://jermainemaree.com
Description: Enable support forums for bbPress.
Version: 0.1
Author: Jermaine Maree
Author URI: http://jermainemaree.com
*/

//! Define constants
define('SS_URL',plugin_dir_url(__FILE__));
define('SS_PATH',plugin_dir_path(__FILE__));

//! Add action to load plugin
add_action('setup_theme','load_ss');

//! Load Simple Support
function load_ss() {
	require(SS_PATH.'lib/ss-base.php');
}
