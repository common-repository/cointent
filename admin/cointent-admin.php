<?php


require_once COINTENT_DIR. '/admin/cointent-general-settings.php';
require_once COINTENT_DIR . '/admin/ajax.php';

add_action( "admin_menu", 'cointent_add_admin_pages' );
add_action( 'wp_ajax_save_dismiss_header', 'save_dismiss_header_callback' );

function save_dismiss_header_callback() {
	$result = get_option('Cointent');
	$result['intro_dismissed'] = 1;
	update_option( 'Cointent', $result);
}

function cointent_add_admin_pages() {
	add_menu_page( 'CoinTent', 'CoinTent', 'manage_options', 'cointent.php', 'cointent_general_settings', plugins_url('/images/admin_icon.png', COINTENT_BASE_DIR) );
	//add_options_page( 'Cointent', 'Cointent', 'manage_options',  'cointent_options_settings' );
	cointent_register_settings();

	$options = get_option( 'Cointent' );
	global $wp_version;

	if ( version_compare( $wp_version, '3.3', '>=' ) && !isset( $options['tracking_popup'] ) ) {
		require_once COINTENT_DIR . '/admin/cointent-pointers.php';
	}
}
function cointent_register_settings() {
	register_setting( 'cointent-settings-group', 'Cointent', 'cointent_validate_settings' );
	wp_register_style('cointent-wp-plugin-admin', plugins_url('style.css', COINTENT_BASE_DIR) );
	wp_register_style('cointent-font', '//fonts.googleapis.com/css?family=Lato:300,400,700,900');
	wp_register_script('cointent-js',  plugins_url('cointent-admin.js', COINTENT_BASE_DIR) );

	wp_enqueue_style('cointent-font');
	wp_enqueue_style('cointent-wp-plugin-admin');
	wp_enqueue_script('cointent-js');
}

function cointent_validate_settings($input) {
	$result = get_option('Cointent');

	$result['publisher_id'] = intval($input['publisher_id']);
	$result['preview_count'] = intval($input['preview_count']);
	$result['intro_dismissed'] = isset($input['intro_dismissed']) ? intval($input['intro_dismissed']) : 0;

	$result['publisher_token'] = !empty($input['publisher_token']) ? intval($input['publisher_token']) : '';
	$result['environment'] = isset($input['environment']) ? $input['environment'] : 'live';
	$result['cointent_tracking'] = (bool)$input['cointent_tracking'];
	$result['view_type'] = $input['view_type'];
	$result['reload_full_page'] = intval($input['reload_full_page']);
	$result['client_side_locking'] = intval($input['client_side_locking']);

	$result['meter_active'] = intval($input['meter_active']);
	$result['meter_articles'] = intval($input['meter_articles']);
	$result['meter_timeframe'] = $input['meter_timeframe'];
	$result['meter_timeframe_days'] = $input['meter_timeframe_days'];
	$result['meter_type'] = $input['meter_type'];
	/************************************************************
	 * 					AD BLOCK SETTINGS 						*
	 ************************************************************/
	$result['adblock_active'] = intval($input['adblock_active']);
	$result['adblock_type'] = $input['adblock_type'];
	$result['adblock_close_button_timer'] = $input['adblock_close_button'] == false ? 0 : intval($input['adblock_close_button_timer']);
	$result['adblock_close_button'] = intval($input['adblock_close_button']);

	$result['adblock_image_url'] = $input['adblock_image_url'];
	$result['adblock_logo'] = $input['adblock_logo'];

	$result['adblock_header_text'] = $input['adblock_header_text'];
	$result['adblock_subheader_text'] = $input['adblock_subheader_text'];
	$result['adblock_per_session'] = intval($input['adblock_per_session']);

	if (isset($input['include_categories'])) {
		$result['include_categories'] = $input['include_categories'];
	} else {
		$result['include_categories'] = array();
	}

	if (isset($input['exclude_categories'])) {
		$result['exclude_categories'] = $input['exclude_categories'];
	} else {
		$result['exclude_categories'] = array();
	}
	$pregString = '/^[a-z0-9A-Z\s<>\()!?._-]{0,140}$/i';
	/*CSS classes */

	$result['widget_wrapper_prepurchase'] = '';
	$result['widget_wrapper_postpurchase'] = '';

	/*TITLES */
	if (strpos($input['widget_title'],'"') !== false) {
		$error = 'Widget title cannot contain double quotes, please remove them or replace them with single quotes.';
	} else {
		$result['widget_title'] = $input['widget_title'];
	}

	if (strpos($input['widget_subtitle'],'"') !== false) {
		$error = 'Widget Subtitle cannot contain double quotes, please remove them or replace them with single quotes.';
	} else {
		$result['widget_subtitle'] = $input['widget_subtitle'];
	}
	if (strpos($input['widget_post_purchase_title'],'"') !== false) {
		$error = 'Widget post purchase title cannot contain double quotes, please remove them or replace them with single quotes.';
	} else {
		$result['widget_post_purchase_title'] = $input['widget_post_purchase_title'];
	}
	if (strpos($input['widget_post_purchase_subtitle'],'"') !== false) {
		$error = 'Widget Post purchase subtitle cannot contain double quotes, please remove them or replace them with single quotes.';
	} else {
		$result['widget_post_purchase_subtitle'] = $input['widget_post_purchase_subtitle'];
	}

	$prevalidate = (string)trim($input['widget_additional_css']);
	if(preg_match($pregString, $prevalidate)) {
		$result['widget_additional_css'] = $prevalidate;
	} else {
		$error = "CSS class contains characters that are not allowed please use only alphanumerics.";
	}

	if(isset($error)) {
		$result['error'] = $error;
	}

	return $result;
}
