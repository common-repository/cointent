<?php

/**
 * This class handles the tracking permissions popup
 */
class cointent_tracking_req {

	/**
	 * Class constructor.
	 */
	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'cointent_enqueue' ) );
	}

	/**
	 * Enqueue styles and scripts needed for the pointers.
	 */
	function cointent_enqueue() {
		$options = get_option( 'Cointent' );
		if ( !isset( $options['tracking_popup'] ) && !isset( $_GET['allow_tracking'] ) ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_script( 'utils' );
			add_action( 'admin_print_footer_scripts', array( $this, 'cointent_tracking_request' ) );
		}
	}

	/**
	 * Shows a popup that asks for permission to allow tracking.
	 */
	function cointent_tracking_request() {
		$id      = '#toplevel_page_cointent';
		$content = '<h3>' . __( 'Help improve CoinTent for WordPress', 'cointent' ) . '</h3>';
		$content .= '<p>' . __( 'You\'ve just installed CoinTent for WordPress. Please help us improve it by allowing us to gather anonymous usage stats so we know which configurations, and flows are working best for you and your readers.', 'cointent' ) . '</p>';
		$opt_arr = array(
			'content'  => $content,
			'position' => array(
				'edge' => 'left',
				'align' => 'right',

			)
		);
		$button2 = __( 'Allow tracking', 'cointent' );
		$button1 =  __( 'Do not allow tracking', 'cointent' );
		$nonce   = wp_create_nonce( 'cointent_activate_tracking' );

		$function2 = 'cointent_store_answer("yes","'.$nonce.'");';
		$function1 = 'cointent_store_answer("no","'.$nonce.'");';

		$this->cointent_print_scripts( $id, $opt_arr, $button1,  $button2, $function2, $function1 );
	}



	/**
	 * Prints the pointer script
	 *
	 * @param string      $selector         The CSS selector the pointer is attached to.
	 * @param array       $options          The options for the pointer.
	 * @param string      $button1          Text for button 1
	 * @param string|bool $button2          Text for button 2 (or false to not show it, defaults to false)
	 * @param string      $button2_function The JavaScript function to attach to button 2
	 * @param string      $button1_function The JavaScript function to attach to button 1
	 */
	function cointent_print_scripts( $selector, $options, $button1, $button2 = false, $button2_function = '', $button1_function = '' ) {
		?>
	<script type="text/javascript">
		//<![CDATA[
        function cointent_store_answer( input, nonce ) {

            var cointent_tracking_data = {
                action : 'cointent_tracking_data',
                allow_tracking : input,
                nonce: nonce
            };
            jQuery.post( ajaxurl, cointent_tracking_data, function( response ) {
                jQuery('#wp-pointer-0').remove();
            } );
        }

        (function ($) {
			var cointent_pointer_options = <?php echo json_encode( $options ); ?>, setup;

			cointent_pointer_options = $.extend(cointent_pointer_options, {
				buttons: function (event, t) {
					var button = jQuery('<a id="pointer-close" style="margin-left:5px" class="button-secondary">' + '<?php echo $button1; ?>' + '</a>');
					button.bind('click.pointer', function () {
						t.element.pointer('close');
					});
					return button;
				},
				close:function () {
				}
			});

			setup = function () {
				$('<?php echo $selector; ?>').pointer(cointent_pointer_options).pointer('open');
				<?php if ( $button2 ) { ?>
					jQuery('#pointer-close').after('<a id="pointer-primary" class="button-primary">' + '<?php echo $button2; ?>' + '</a>');
					jQuery('#pointer-primary').click(function () {

						<?php echo $button2_function; ?>
					});
					jQuery('#pointer-close').click(function () {
						<?php echo $button1_function; ?>
					});
					<?php } ?>
			};

			if (cointent_pointer_options.position && cointent_pointer_options.position.defer_loading)
				$(window).bind('load.wp-pointers', setup);
			else
				$(document).ready(setup);
		})(jQuery);
		//]]>
	</script>
	<?php
	}
}

$ct_tracking_req = new cointent_tracking_req;
