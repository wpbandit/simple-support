<?php

/**
	Admin Library

	The contents of this file are subject to the terms of the GNU General
	Public License Version 2.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2011 Jermaine Maree

		@package Admin
		@version 0.1
**/

namespace SimpleSupport;

//! Admin
class Admin extends Base {

	/**
		Admin methods and actions
	**/
	static function boot() {
		// Upgrade, if necessary
		self::upgrade();
		// Admin init
		add_action('admin_init',__CLASS__.'::init');
	}

	/**
		Upgrade
	**/
	private static function upgrade() {
		$current_version = self::get_option('version');
		// Do we need to upgrade?
		if(version_compare($current_version,self::TEXT_Version) < 0) {
			require(SS_PATH.'lib/ss-upgrade.php');
			\SimpleSupport\Upgrade::init();
		}
	}

	/**
		Admin init
	**/
	static function init() {
		add_action('bbp_forum_metabox',__CLASS__.'::forum_metabox_field');
		add_action('bbp_forum_attributes_metabox_save',__CLASS__.'::forum_metabox_save');
	}

	/**
		Forum Metabox Field
	**/
	static function forum_metabox_field($id) {
		// Status
		$status = self::get_option('forum_'.$id)?'1':'0';
		// Build field
		$output  = '<hr>';
		$output .= '<p>';
		$output .= '<strong class="label">Support:</strong>';
		$output .= '<label class="screen-reader-text" for="ss_forum_support_select">Status:</label>';
		$output .= '<select name="ss_forum_support" id="ss_forum_support_select">';
		$output .= '<option value="0"'.selected($status,0,FALSE).'>Disabled</option>';
		$output .= '<option value="1"'.selected($status,1,FALSE).'>Enabled</option>';
		$output .= '</select>';
		$output .= '</p>';
		// Print field
		echo $output;
	}

	/**
		Form Metabox Save
	**/
	static function forum_metabox_save($id) {
		// Default status
		$status = '0';
		// Get status
		if(isset($_POST['ss_forum_support']))
			$status = esc_attr($_POST['ss_forum_support'])?'1':'0';
		// Update status
		self::$option['forum_'.$id] = $status;
		update_option('simplesupport',self::$option);
	}

}
