<?php

/**
	Simple Support

	The contents of this file are subject to the terms of the GNU General
	Public License Version 2.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2011 Jermaine Maree

		@package Base
		@version 0.1
**/

namespace SimpleSupport;

//! Base
class Base {

	//@{ Framework details
	const
		TEXT_Framework='Simple Support',
		TEXT_Version='0.1';
	//@}

	protected static
		//! Global variables
		$vars,
		//! Statuses
		$statuses,
		//! Options
		$option,
		//! Notices
		$notices,
		//! Pagination Reply Template Count
		$pagination_reply_template_count = 0;

	/**
		Compiles an array of HTML attributes into an attribute string
	**/
	static function attributes(array $attrs) {
		if(!empty($attrs)) {
			$result='';
			foreach($attrs as $key=>$val)
				$result.=' '.$key.'="'.$val.'"';
			return $result;
		}
	}

	/**
		Get plugin option
	**/
	protected static function get_option($key) {
		$value = isset(self::$option[$key])?self::$option[$key]:FALSE;
		return $value;
	}

	/**
		Add notice
	**/
	protected static function add_notice($message,$class='updated') {
		if(!self::$notices) { self::$notices = array(); }
		self::$notices[] = array(
			'class'		=> $class,
			'message'	=> $message
		);
	}

	/**
		Prevent class instantiation / cloning
	**/
	private function __construct() {}
	private function __clone() {}

}

//! Core
class Core extends Base {

	/**
		Boot framework
	**/
	static function boot() {
		// Prevent multiple calls
		if(self::$vars)
			return;
		// Initialize framework
		self::init();
		// Boot admin
		if(is_admin()) {
			// Admin library
			require(SS_PATH.'lib/ss-admin.php');
			\SimpleSupport\Admin::boot();
		}
	}

	/**
		Initialize
	**/
	private static function init() {
		// Global $pagenow variable
		global $pagenow;
		// Permalinks
		$permalinks = get_option('permalink_structure');
		// Hydrate framework variables
		self::$vars=array(
			// Global $pagenow variable
			'PAGENOW' => $pagenow,
			// Permalinks
			'PERMALINKS' => ($permalinks && ($permalinks != ''))?TRUE:FALSE,
			// Version
			'VERSION' => self::TEXT_Framework.' '.self::TEXT_Version,
		);
		// Set statuses
		self::$statuses = array(
			'not_resolved'	=> 'Not resolved',
			'in_progress'	=> 'In progress',
			'resolved'		=> 'Resolved',
			'not_support'	=> 'Not a support question'
		);
		// Options
		self::$option = get_option('simplesupport');
		// If no options, set defaults
		if(!self::$option || !is_array(self::$option)) {
			self::$option = array('version' => self::TEXT_Version);
			update_option('simplesupport',self::$option);
		}
		// Plugin actions
		self::plugin_actions();
	}

	/**
		Plugin actions
	**/
	private static function plugin_actions() {
		// AJAX action
		add_action('wp_ajax_update_topic_status',__CLASS__.'::update_topic_status');
		// Register scripts
		add_action('wp_enqueue_scripts',__CLASS__.'::enqueue_scripts');
		// bbPress template actions
		add_action('bbp_theme_before_topic_title',__CLASS__.'::action_before_topic_title');
		add_action('bbp_template_before_replies_loop',__CLASS__.'::action_before_replies_loop');
		add_action('bbp_template_after_pagination_loop',__CLASS__.'::action_after_pagination_loop');
	}

	/**
		Enqueue scripts
	**/
	static function enqueue_scripts() {
		wp_enqueue_script('simplesupport',SS_URL.'js/simplesupport.js',array(jquery),'0.1');

		// AJAX url + nonce variables
		wp_localize_script('simplesupport','bandit',
			array(
				'ajaxurl'	=> admin_url('admin-ajax.php'),
				'ajaxnonce' => wp_create_nonce('wpbandit-ajax-nonce')
			)
		);
	}

	/**
		Before topic title action - Displays support status icon
	**/
	static function action_before_topic_title() {
		// Get forum id
		$forum_id = bbp_get_forum_id();
		if(!$forum_id)
			$forum_id = bbp_get_topic_forum_id();
		// Support Forum Enabled ?
		if(self::get_option('forum_'.$forum_id)) {
			// Get topic ID
			$topic_id = bbp_get_topic_id();
			// Get topic status
			$status = get_post_meta($topic_id,'topic_support_status',TRUE);
			if(!$status) { $status = 'not_resolved'; }
			// Display icon
			$icon = '<img class="ss-status-icon-forum" src="'.SS_URL.'icons/'.$status.'.png">';
			echo $icon;
		} else {
			// Display icon
			$icon = '<img class="ss-status-icon-forum" src="'.SS_URL.'icons/not_support.png">';
			echo $icon;
		}
	}

	/**
		Before single topic loop - Displays support status
	**/
	static function action_before_replies_loop() {
		// Get forum ID
		$forum_id = bbp_get_forum_id();
		// Is support forum enabled ?
		if(!self::get_option('forum_'.$forum_id))
			return FALSE;
		// Get topic ID
		$topic_id = bbp_get_topic_id();
		// Get topic status
		$status = get_post_meta($topic_id,'topic_support_status',TRUE);
		if(!$status) { $status = 'not_resolved'; }
		// Build status HTML
		$output  = '<div class="ss-status ss-status-top">';
		$output .= '<img class="ss-status-icon-thread" src="'.SS_URL.'icons/'.$status.'.png">';
		$output .= '<span class="ss-status-text">'.self::$statuses[$status].'</span>';
		$output .= '</div>';
		// Print HTML
		echo $output;
	}

	/**
		After single topic action - Displays support status form
	**/
	static function action_after_pagination_loop() {
		// Increase pagination template reply count
		self::$pagination_reply_template_count++;
		
		// Show support status form ?
		if(self::show_support_status_form()) {
			// Get topic ID
			$topic_id = bbp_get_topic_id();
			// Get topic status
			$status = get_post_meta($topic_id,'topic_support_status',TRUE);
			if(!$status) { $status = 'not_resolved'; }
			// Build form
			$output  = '<div class="ss-status">';
			$output .= '<img class="ss-status-icon-thread" src="'.SS_URL.'icons/'.$status.'.png">';
			$output .= '<form class="ss-status-form" method="post">';
			// Select field
			$output .= '<select name="ss_topic_status" class="ss-status-select-field">';
			$output .= '<option value="not_resolved"'.selected($status,'not_resolved',FALSE).'>Not resolved</option>';
			$output .= '<option value="in_progress"'.selected($status,'in_progress',FALSE).'>In progress</option>';
			$output .= '<option value="resolved"'.selected($status,'resolved',FALSE).'>Resolved</option>';
			$output .= '<option value="not_support"'.selected($status,'not_support',FALSE).'>Not a support question</option>';
			$output .= '</select>';
			// Topic ID
			$output .= '<input name="ss_topic_id" type="hidden" value="'.$topic_id.'">';
			// Update button
			$output .= '<input class="ss-update-status-button" type="submit" name="submit" value="Update Status">';
			$output .= '</form>';
			$output .= '</div>';
			// Print form
			echo $output;
		}
	}

	/**
		Show support status form ?
	**/
	private static function show_support_status_form() {
		// Topic page
		if('topic'!=get_post_type())
			return FALSE;
		// Get forum ID
		$forum_id = bbp_get_forum_id();
		// Is support forum enabled ?
		if(!self::get_option('forum_'.$forum_id))
			return FALSE;
		// Is user administrator ?
		if(!current_user_can('administrator'))
			return FALSE;
		// Bottom pagination template ?
		if(self::$pagination_reply_template_count != 2)
			return FALSE;
		// Show form
		return TRUE;
	}

	/**
		Update topic status
	**/
	static function update_topic_status() {
		// Default variables
		$statuses = array('not_resolved','in_progress','resolved','not_support');
		$success = FALSE;
		// Get $_POST data
		$topic_id = esc_attr($_POST['ss_topic_id']);
		$topic_status = esc_attr($_POST['ss_topic_status']);
		// Topic status sanity check
		if(!in_array($topic_status,$statuses))
			$topic_status = 'not_resolved';
		// Update topic status
		if($topic_id && is_numeric($topic_id))
			$success = update_post_meta($topic_id,'topic_support_status',$topic_status);
		// Generate response
		$response = json_encode(
			array(
				'success'		=> $success?TRUE:FALSE,
				'topic_status'	=> $topic_status,
				'topic_text'	=> self::$statuses[$topic_status],
				'topic_icon'	=> SS_URL.'icons/'.$topic_status.'.png'
			)
		);
		// JSON header
		header('Content-type: application/json');
		echo $response;
		die();
	}

}

// Boot SimpleSupport
\SimpleSupport\Core::boot();
