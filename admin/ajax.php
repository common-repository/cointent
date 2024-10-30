<?php
function cointent_store_tracking_response() {
	if ( !wp_verify_nonce( $_POST['nonce'], 'cointent_activate_tracking' ) )
		die();

	$options = get_option( 'Cointent' );

	$options['tracking_popup'] = 'done';

	if ( $_POST['allow_tracking'] == 'yes' ) {
		$options['cointent_tracking'] = true;
	}
	else {
		$options['cointent_tracking'] = false;
	}
	update_option( 'Cointent', $options );
}

add_action( 'wp_ajax_cointent_tracking_data', 'cointent_store_tracking_response' );