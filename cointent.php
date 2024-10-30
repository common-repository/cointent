<?php
/**
 * Plugin Name: CoinTent
 * Plugin URI: http://cointent.com
 * Description: CoinTent let’s you sell subscriptions and individual pieces of content for small amounts ($0.05-$1.00). You choose what content to sell and how to sell it. We handle the rest.
 * Version: 1.6.0
 * Author: CoinTent, Inc.
 * License: GPL2
 */

define("COINTENT_DIR", plugin_dir_path( __FILE__ ));
define("COINTENT_BASE_DIR",  __FILE__ );

define("COINTENT_PRODUCTION", 'connect.cointent.com');
define("COINTENT_SANDBOX", 'connect.sandbox.cointent.com');
define("COINTENT_API_BASE_URL", 'api.cointent.com');
define("SANDBOX_COINTENT_API_BASE_URL", 'api.sandbox.cointent.com');

// Flag to show/hide the failure message for all shortcodes
define("COINTENT_SHOW_FAILURE_MESSAGE", false);


if (!class_exists('cointent_class')) {
	class cointent_class {
		private static $instance;

		private $debug = false;

		public static function get_instance() {
			if ( !isset( self::$instance ) )
				self::$instance = new cointent_class();
			return self::$instance;
		}

		const WORDS_PER_MINUTE = 200;
		public $scripts = array();
		private function __construct() {
			// Setup admin for cointent
			if (is_admin()) {
				require_once COINTENT_DIR . '/admin/cointent-admin.php';
			} else {
				// Register filter (run w/ default priority 10)
				add_filter('the_content', array(&$this, 'cointent_determine_shortcode_status'), 10);

				// Register shortcodes (run with priority 11, via add_filter( 'the_content', 'do_shortcode', 11 ); // From shortcodes.php
				add_shortcode('cointent_lockedcontent', array(&$this, "cointent_add_widget"));
				add_shortcode('cointent_extras', array(&$this, "cointent_extra_content_handler"));

				// Handles loading the css for the widget
				add_action('wp_enqueue_scripts', array(&$this, 'cointent_register_plugin_styles'));

				// Get saved options from the DB
				$options = get_option("Cointent");

				// Tracking has to be configurable based on WP rules, check to see if the client has
				// allowed tracking to determine whether those functions are registered
				//if (isset($options['cointent_tracking']) && $options['cointent_tracking']) {
				//	cointent_home_stats();
				add_action('wp', array(&$this, "cointent_home_stats"), 5);
				add_filter('the_content', array(&$this, 'cointent_post_stats'), 5);
				//}
			}
		}

		/**
		 * Activates the Cointent plugin and does any necessary upgrades and check compatibility
		 */
		public static function cointent_activate() {
			// DO upgrades/migrations if necessary
			$default_options = array(
				'publisher_id' => 0,
				'publisher_token' => '',
				'preview_count' => 55,
				'environment' => 'production',
				'cointent_tracking' => false,
				'intro_dismissed' => false,
				'include_categories'=> array(),
				'exclude_categories'=> array(),
				'widget_wrapper_prepurchase' => '',
				'widget_wrapper_postpurchase' => '',
				'widget_title' => 'This content is available for purchase',
				'widget_subtitle' => '',
				'tracking_popup' => false,
				'widget_post_purchase_title' => 'Thank you!',
				'widget_post_purchase_subtitle' => '',
				'view_type' => 'condensed',
				'reload_full_page' => 2,
				'client_side_locking' => 1,
				'meter_type' => 'article',
				'meter_active' => 0,
				'meter_timeframe' => 'month',
				'meter_articles' => 10,
				'widget_additional_css' => '',
				'adblock_active' => 0,
				'adblock_type' => 'basic',
				'adblock_close_button' => 0,
				'adblock_close_button_timer' => 0,
				'adblock_per_session'=>0
			);

			$options = get_option( 'Cointent', $default_options );
			$options = array_merge($default_options,$options );
			update_option('Cointent', $options);

		}

		public static function cointent_uninstall() {
			// Remove info from DB on deactivate?
			delete_option( 'Cointent' );
		}
		public static function cointent_deactivate() {
			// Don't do anything, keep information
		}

		/**
		 * Register the relevant styles and scripts
		 */
		function cointent_register_plugin_styles() {
			$options = get_option('Cointent');
			$environment =  $options['environment'];
			$base_url = COINTENT_PRODUCTION;
			if ($environment == 'sandbox') {
				$base_url = COINTENT_SANDBOX;
			}

			wp_register_script('main-cointent-js', '//'.$base_url.'/cointent.0.2.js');
			wp_register_script('tracking-cointent-js', '//'.$base_url.'/cointent-tracker.0.2.js');
			$tracking_active = $options['cointent_tracking'];

//			if (!$tracking_active) {
			wp_localize_script('main-cointent-js','cointent_tracking_data', array('tracking_inactive'=>true));
//			}
			// CSS
			wp_register_style('cointent-wp-plugin', '//'.$base_url.'/style.css' );
			wp_enqueue_style('cointent-wp-plugin');

		}
		/**
		 * Handle hiding some text based on whether the cointent_contentlocker is active
		 * This is mostly TP specific but if we wanted to add in extra info we could wrap it here
		 * @param  array	 $atts     	Any passed information from the shortcode, nothing anticipated
		 * @param  string 	$content 	Any content from within the cointent_extras shortcode
		 * @return string 		 		Filtered content
		 */
		function cointent_extra_content_handler($atts, $content=null) {
			// Emergency shut off
			// Hides any text marked within cointent_extras
			if (COINTENT_SHOW_FAILURE_MESSAGE) {
				return '';
			}

			// If user has access either by purchase or because the article is not gated by cointent
			// Do not show the extra text
			$options =  get_option('Cointent');
			$post_id = get_the_ID();

			if ((!isset($_REQUEST['email']) && !isset($_REQUEST['uid'])) || !isset($_REQUEST['token']) || !isset($_REQUEST['time'])) {
				// Not enough info to do check
				$has_cointent_access = false;
			} else {
				$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
				$uid = isset($_REQUEST['uid']) ?  $_REQUEST['uid'] : '';
				$has_cointent_access = $this->cointent_has_access($email, $uid, $_REQUEST['token'], $_REQUEST['time']);
			}

			if ($has_cointent_access) {

				// If client side locking is on we always want to show all the content but we will want to hide the extras after unlocking

				if ($options['client_side_locking']){
					$content = '<span class="cointent_preview" id="cointent_preview_'.$post_id.'">'.$content.'</span>';
				} else {
					return "";
				}
			}
			if ($options['client_side_locking']){
				$content = '<span class="cointent_preview" id="cointent_preview_'.$post_id.'">'.$content.'</span>';
			}
			// Return the content wrapped in the cointent_extras
			return do_shortcode(($content));
		}


		/**
		 * Handle showing the widget at the bottom of the content
		 * @param  array 	$atts     	Any passed information from the shortcode, nothing anticipated
		 * @param  string 	$content 	Any content from within the cointent_extras shortcode
		 * @return string 			 	Filtered content
		 */
		function cointent_add_widget($atts, $content=null) {

			// Emergency shut off
			// Displays a failure message instead of the widget
			if (COINTENT_SHOW_FAILURE_MESSAGE) {
				$message = '<div id="plugin_error_message"> <p> CoinTent is currently under maintenance, but will be back up shortly.
				  </br>If you have any questions or concerns please email support@cointent.com </p> </div>';

				return do_shortcode($message);
			}

			// Content we want to display by expanding
			$hidden_content = '';
			$options =  get_option('Cointent');
			$post_id = get_the_ID();
			// If a user is running noscript or turned off javascript display a message that they can't do it
			$no_script = $this->cointent_get_no_script_notice();

			// If you have been passed authentication information check to see if the user has
			// purchased the content, if you don't just check to see if the content is gated by cointent or not
			if( (!isset($_REQUEST['email']) && !isset($_REQUEST['uid'])) || !isset($_REQUEST['token']) || !isset($_REQUEST['time'])) {
				$is_gated = $this->cointent_is_content_gated($content);
				// If not gated, don't change the content and return
				if (!$is_gated) {
					return $content;
				}
				$has_cointent_access = !$is_gated;
			} else {
				$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
				$uid = isset($_REQUEST['uid']) ?  $_REQUEST['uid'] : '';
				$has_cointent_access = $this->cointent_has_access($email, $uid, $_REQUEST['token'], $_REQUEST['time']);
			}

			// If the user has access or the script has already been loaded, don't load the script again
			if ( $has_cointent_access  && $content) {
				// if there is content in the short code, hide it behind gating.
				$hidden_content = $content;
			} else if ($options['meter_active']) {
				$hidden_content = '<div hidden class="cointent_metergated" id="cointent_metergated_'.$post_id.'">'.$content.'</div>';
			} else if ($options['client_side_locking'] && strpos($content,'id="cointent_gated_'.$post_id.'"') === false) {
				$hidden_content = '<div hidden class="cointent_gated" id="cointent_gated_'.$post_id.'">'.$content.'</div>';
			}

			$widget_script = '';
			if (strpos($hidden_content,'class="cointent-widget"')  !== false) {
				// Added class to wrap widget in, (initial case to apply publisher's css to widget and clear a float)
				$hidden_content = str_replace("cointent-widget", '', $hidden_content);
			}
			$widget_script = $this->cointent_create_widget($has_cointent_access, $atts);

			$content = $hidden_content . $widget_script .$no_script;
			return do_shortcode(($content));
		}

		/**
		 * Returns the no script notice
		 * @return string A message to show if the user has decided not to
		 */
		function cointent_get_no_script_notice() {
			return '<noscript>
				<style type="text/css">
					.cointent-widget {display:none;}
					.noscript_view { border: 1px solid #ccc; padding: 10px; background-color: rgb(255, 248, 208);}
				</style>
				<div class="noscript_view">
				This article is paid content! To access, you can pay '.get_bloginfo('name').' with CoinTent. To use CoinTent, you must enable javascript.</div>
			</noscript>';
		}

		/**
		 * Handling building out the widget based on whether the content is gated/ungated locked/unlocked
		 * @return string Widget HTML
		 */
		function cointent_create_widget($has_cointent_access, $atts) {
			// Current post Id - maps to cointent article id
			global $post;
			$time = 0;
			if ($post->post_content)	{
				//defined exerpt
				$content = $post->post_content;
			}

			$widget_script = '';

			// Setup environment to point the plugin to
			$options =  get_option('Cointent');
			$post_id = get_the_ID();
			// Pull information from admin panel
			// TODO: handle it not existing - required information
			$publisher_id = $options['publisher_id'];
			$environment =  $options['environment'];

			$base_url = COINTENT_PRODUCTION;
			if ($environment == 'sandbox') {
				$base_url = COINTENT_SANDBOX;
			}

			// Get all of the information from the shortcode
			//   article title 					-> Title of the article, if doesn't exist use post title
			//   article_labels					-> Default labels to be added to the CT system
			//   title  						-> Title to display on the widget BEFORE purchase
			//   subtitle 						-> Message below the title to display on the widget BEFORE purchase
			//   post_purchase_title  			-> Title to display on the widget AFTER purchase
			//   post_purchase_subtitle 		-> Message below the title to display on the widget AFTER purchase
			//   view_type 						->
			// 										full - full widget all options
			//  									condensed - just button and login status
			//   image_url 						-> Image to display on the widget

			extract( shortcode_atts( array(
				'media_type' => 'text',
				'article_title' => '',
				'article_labels' => '',
				'article_price' => null,
				'branding' => 'messaging',
				'buy_verb'=>'',
				'subscription_verb'=>'',
				'subtitle' => '',
				'show_subscription' => '',
				'title' => '',
				'post_purchase_title' => '',
				'post_purchase_subtitle' => '',
				'view_type' => '',
				'video_id' => '',
				'image_url' => '',
				'video_src' => '',
				'video_type' => '',
				'video_width' => '640',
				'video_height' => '360',
				'widget_additional_css'=>'',
				'video_poster' => 'https://kconnect.dev.cointent.com/images/default_poster.png',
				'reload_full_page' => 2,
				'client_side_locking' => 1,
				'meter_type' => '',
				'meter_timeframe' => '',
				'meter_articles' => '',
			), $atts, 'cointent_lockedcontent' ));

			// If we don't have an article title use the one from the post
			if (!$article_title) {
				$article_title = get_the_title($post_id);
			}

			$wrapperClass = $has_cointent_access ? $options['widget_wrapper_postpurchase'] : $options['widget_wrapper_prepurchase'];
			$additionalCss = $widget_additional_css ? $widget_additional_css : $options['widget_additional_css'];
			$title = $title ? $title : $options['widget_title'];
			$subtitle = $subtitle ? $subtitle : $options['widget_subtitle'];
			$reload_full_page = $options['reload_full_page'];
			// 0 - server side locking , 1 - client side locking
			$client_side_locking = $options['client_side_locking'];
			$post_purchase_title = $post_purchase_title ? $post_purchase_title : $options['widget_post_purchase_title'];
			$post_purchase_subtitle = $post_purchase_subtitle ? $post_purchase_subtitle : $options['widget_post_purchase_subtitle'];

			if ($media_type == '' || $media_type == 'text') {
				//	$post_id = get_the_ID(); Set above
				$cssClazz = 'widget';
			} else {
				$cssClazz = 'video';
				$post_id = $video_id;
			}

			$view_type = $view_type ? $view_type : $options['view_type'];
			// METERED PAYWALL PARAMS
			$meter_type = $options['meter_active'] == 1 ? $options['meter_type'] : 'off' ;
			$meter_timeframe = $meter_timeframe ? $meter_timeframe : $options['meter_timeframe'];
			$meter_articles= $meter_articles ? $meter_articles: $options['meter_articles'];

			// Data fields to aid in the creation of the widget
			$dataFields = 'data-publisher-id="'.$publisher_id.'" data-article-id="'.$post_id.'"'.
				'data-article-title="'.$article_title.'"'.
				'data-article-labels="'.$article_labels.'"'.
				'data-article-price="'.$article_price.'"'.
				'data-title="'.$title.'"'.
				'data-branding="'.$branding.'"'.
				'data-buy-verb="'.$buy_verb.'"'.
				'data-subscription-verb="'.$buy_verb.'"'.
				'data-show-subscription="'.$show_subscription.'"'.
				'data-url="'.get_permalink($post_id).'"'.
				'data-subtitle="'.$subtitle.'"'.
				'data-post-purchase-subtitle="'.$post_purchase_subtitle.'"'.
				'data-post-purchase-title="'.$post_purchase_title.'"'.
				'data-view-type="'.$view_type.'"'.
				'data-reload-full-page="'.$reload_full_page.'"'.
				'data-client-side-locking="'.$client_side_locking.'"'.
				'data-meter-type="'.$meter_type.'"'.
				'data-meter-timeframe="'.$meter_timeframe.'"'.
				'data-meter-articles="'.$meter_articles.'"'.
				'data-additional-css="'.$additionalCss.'"'.
				'data-src="'.$image_url.'"';

			if ($media_type == 'video') {
				$dataFields .= 'data-video-src="'.$video_src.'"'.
					'data-video-type="'.$video_type.'"'.
					'data-video-width="'.$video_width.'"'.
					'data-video-height="'.$video_height.'"'.
					'data-video-poster="'.$video_poster.'"'
				;
			}
			// If the user has access or the script has already been loaded, don't load the script again
			$widget_script .= '<script class="cointent-'.$cssClazz.'" '.$dataFields.'></script>';
			$this->addScripts($has_cointent_access, $post_id, $publisher_id);
			$widget_script .= $this->addBacklink($publisher_id, $post_id);
			// Close the wrapper
			if ($wrapperClass) {
				$widget_script ='<div class="'.$wrapperClass.'">'. $widget_script . '</div>';
			}

			return $widget_script;
		}

		function cointent_dequeue_tracking () {
			wp_dequeue_script( 'tracking-cointent-js' );
			wp_deregister_script( 'tracking-cointent-js' );
		}

		/**
		 * Builds a backlink and spinner for preloading
		 * @param $publisher_id
		 * @param $article_id
		 * @return string
		 */
		function addBacklink($publisher_id, $article_id) {
			$backlink = '';
			$backlink .= '<div style="position:relative; text-align:center;" class="cointent_messaging premessaging_'.$publisher_id.'_'.$article_id.'"><div class="spinner"></div><a class="cointent_logo on preload" target="_blank" href="https://cointent.com"><img class="powered_by_logo_widget_login" src="//connect.cointent.com/images/base64/powered_by_logo_widget_login.png" alt="Powered by CoinTent "></a></div>';
			return $backlink;
		}

		/**
		 * Manages which scripts should be included with the page and adds tracking information to it
		 *
		 * @param $has_cointent_access
		 * @param $post_id
		 * @param $publisher_id
		 */
		function addScripts($has_cointent_access, $post_id, $publisher_id) {
			$options = get_option('Cointent');

			if (!$has_cointent_access && !isset($_GET['loadScript'])) {
				wp_enqueue_script('main-cointent-js');

				$tracking_active = $options['cointent_tracking'];
				if ($tracking_active) {
					$data = array('publisherId' => $publisher_id, 'gated' => true);

					if (!is_front_page()) {
						$data['articleId'] = $post_id;
					}
					wp_localize_script('main-cointent-js', 'cointent_tracking_data', $data);
					add_action('wp_print_scripts', array(&$this, "cointent_dequeue_tracking"));
					$this->cointent_dequeue_tracking();
				}
			} else if (isset($_REQUEST['fullReload'])) {
				wp_enqueue_script('main-cointent-js');

				$tracking_active = $options['cointent_tracking'];
				if ($tracking_active) {
					$data = array('publisherId' => $publisher_id, 'gated' => true);

					if (!is_front_page()) {
						$data['articleId'] = $post_id;
					}
					$data['fullReload'] = true;
					wp_localize_script('main-cointent-js', 'cointent_tracking_data', $data);
					add_action('wp_print_scripts', array(&$this, "cointent_dequeue_tracking"));
					$this->cointent_dequeue_tracking();
				}
			}
		}

		/**
		 * Based on the passed in params, determines whether the user has access to the article
		 * @param string $email
		 * @param string $uid
		 * @param string $token
		 * @param int $timestamp
		 * @return bool
		 */
		function cointent_has_access($email = '', $uid = '', $token = '', $timestamp = 0) {
			global $post;

			// If we don't gate they have access
			if (!$this->cointent_is_content_gated()) {
				return true;
			}


			// Retrieve publisher id
			$options = get_option('Cointent');

			$environment =  $options['environment'];
			$publisher_id =  $options['publisher_id'];

			$base_url = COINTENT_API_BASE_URL;
			if ($environment == 'sandbox') {
				$base_url = SANDBOX_COINTENT_API_BASE_URL;
			}
			// Setup call to get gating information
			$url  = 'https://'.$base_url."/gating/publisher/".$publisher_id."/article/".$post->ID;

			$params = array(
				"email" => $email,
				"uid" => $uid,
				"token" => $token,
				"time" => $timestamp
			);

			$post_result = $this->cointent_call_api('GET', $url, $params);

			if ($post_result) {
				$result = json_decode($post_result);
				return $result->gating->access;
			}
			return false;
		}

		/**
		 * Make an api curl call to another server
		 * @param $method
		 * @param $url
		 * @param bool $data
		 * @return mixed
		 */
		function cointent_call_api($method, $url, $data = false)
		{
			$curl = curl_init();

			switch ($method)
			{
				case "POST":
					curl_setopt($curl, CURLOPT_POST, 1);

					if ($data)
						curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
					break;
				case "PUT":
					curl_setopt($curl, CURLOPT_PUT, 1);
					break;
				default:
					if ($data) {
						$url = sprintf("%s?%s", $url, http_build_query($data));
					}
			}

			// Optional Authentication:
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

			return curl_exec($curl);
		}

		/**
		 * Determine whether the content has been processed already for this article or page
		 *
		 * @param $content
		 * @return bool True if the shortcode is present or has already been processed
		 */
		function is_shortcode_present ($content) {

			$sc = has_shortcode($content, 'cointent_lockedcontent');
			$scString = strpos($content, '[cointent_lockedcontent') !== false;
			$scProcessed = strpos($content,'class="cointent-widget"')  !== false;
			return $sc || $scString || $scProcessed;
		}

		/**
		 * Determine whether post is gated based on include and exclude category settings
		 * Excluded categories are evalutaed first and override included categories
		 * Ex. "Cat E" is set to be excluded and "Cat I" is set to be included
		 *     a post with both "Cat E" and "Cat I" categories will NOT be gated
		 *
		 * @return boolean True if the content is gate, False if it is not
		 */
		function cointent_is_content_gated ($content=null) {
			global $post;
			$mypost = $post;
			$is_gated = false;
			$options = get_option('Cointent');
			// Category of posts that will be gated
			$activeCategories = $options['include_categories'];
			// Category of posts that will not be gated (priority)
			$inactiveCategories = $options['exclude_categories'];

			// If they aren't set make sure we at least have an array to go through
			$activeCategories = $activeCategories ? $activeCategories : array();
			$inactiveCategories = $inactiveCategories ? $inactiveCategories : array();

			// If they have added the shortcode by hand, it is GATED
			if ($this->is_shortcode_present($post->post_content)) {
				return true;
			}

			if ($content !== null) {
				return true;
			}
			//for these post types, we want to check the parent
			if ($mypost->post_type == "attachment" || $mypost->post_type == "revision") {
				$mypost = get_post($mypost->post_parent);
			}

			// Only gate posts via categories,]
			if ($mypost->post_type == "post") {
				$post_categories = wp_get_post_categories($mypost->ID);
				// Search through all the EXCLUDE categories
				// if the post matches any, return "not gated"
				foreach($post_categories as $cat) {
					if (array_key_exists($cat, $inactiveCategories)) {
						return false;
					}
				}
				// Search through all the include categories
				// if the post matches any, return "gated"
				foreach($post_categories as $cat) {
					if (array_key_exists($cat, $activeCategories) || array_key_exists('all_cointent',$activeCategories )) {
						$is_gated = true;
					}
				}
			}
			return $is_gated;
		}


		/**
		 * Filters the content for our shortcode, locks if necessary
		 * @param  string $content [description]
		 * @return void         [description]
		 */
		function cointent_determine_shortcode_status($content)
		{
			global $post;

			$has_access = false;
			$isGated = true;
			$options = get_option('Cointent');

			if ($options['adblock_active']) {
				$close_button = $options['adblock_close_button'] === 1 ? "true" : "false";
				$content = $content.'<div class="cointent-gumshoe" data-debugger="true" data-publisher-id="'. $options['publisher_id'] .'"
                 data-article-id="a1"
                 data-logo="'. $options['adblock_logo'] .'"
                  data-logo="'. $options['adblock_logo'] .'"
                 data-logo-size="large"
                 data-render-option="'.$options['adblock_type'].'"
                 data-timer="'. $options['adblock_close_button_timer'] .'"
                 data-close-button="'.$close_button.'"
                 data-article-title="Thank you for supporting us"
                 data-url= "'. $options['adblock_image_url'] .'"
                 data-per-session= "'. $options['adblock_per_session'] .'"
                 data-header-text="'. $options['adblock_header_text'] .'"
                 data-subheader-text="'. $options['adblock_subheader_text'] .'"
            ></div>';
			}

			if( (!isset($_REQUEST['email']) && !isset($_REQUEST['uid'])) || !isset($_REQUEST['token']) || !isset($_REQUEST['time'])) {
				// Not enough info to do check
				$isGated = $this->cointent_is_content_gated();
				if (!$isGated) {
					return $content;
				}
			} else {
				$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
				$uid = isset($_REQUEST['uid']) ?  $_REQUEST['uid'] : '';
				$isGated = $this->cointent_is_content_gated();
				$has_access = $this->cointent_has_access($email, $uid, $_REQUEST['token'], $_REQUEST['time']);
			}



			$view_type = $options['view_type'];
			$subtitle = $options['widget_subtitle'];
			$title = $options['widget_title'];
			$widget_post_purchase_subtitle = $options['widget_post_purchase_subtitle'];
			$widget_post_purchase_title = $options['widget_post_purchase_title'];


			/********* START TP ONLY SECTION ************/
			/*
			* //if they don't have access through cointent or its not gated by us,
			* //leave as is, either another plugin locked it or allowed it
			* if (!$has_access || !$isGated) { // USE THIS LINE IF SOMETHING ELSE IS GATING CONTENT ( LIKE PMP )
			*	return do_shortcode($content);
			* }
			*/
			/*********** End TP ONLY SECTION *************/




			/********* THIS SECTION WILL NOT WORK WITH TECHPINION, IT DOES THE LOCKING TP depends on another plugin to do locking *********/
			if ($has_access && $isGated) {
				$this->debuggerPrint(__FUNCTION__." | Adding shortcode \n");
				//$pos = strpos( $content, 'cointent_lockedcontent') ;
				if (!$this->is_shortcode_present($content)) {
					$content .= '[cointent_lockedcontent view_type="'.$view_type.'" title="'.$title.'" subtitle="'.$subtitle.'"'
						.' post_purchase_title="'.$widget_post_purchase_title.'"'
						.' post_purchase_subtitle="'.$widget_post_purchase_subtitle.'"]'
						.'[/cointent_lockedcontent]';
				}
			}
			else if ($isGated) {

				if ($post->post_content)	{
					//defined exerpt
					$content = $this->cointent_define_preview($content);
				}
			}



			return $content;
		}
		function debuggerPrint ($string, $object = null ) {
			if ($this->debug === true ) {
				if (!empty($object)) {
					error_log("DEBUGGER: ".$string." | ". print_r($object, true)."\n");
				} else {
					error_log("DEBUGGER: ".$string."\n");
				}
			}
		}


		function cointent_define_preview($content) {
			global $post;

			$options = get_option('Cointent');
			$shown_content = '';
			$view_type = $options['view_type'];
			$subtitle = $options['widget_subtitle'];
			$title = $options['widget_title'];
			$widget_post_purchase_subtitle = $options['widget_post_purchase_subtitle'];
			$widget_post_purchase_title = $options['widget_post_purchase_title'];
			$post_id = get_the_ID();

			if (!$this->is_shortcode_present($content)){
				// Make short preview - pulled form wp_trim_excerpt
				// IF THE MORE TAG EXISTS use that as breaking
				$this->debuggerPrint(__FUNCTION__." Shortcode not present in content ");
				$morestring = '<!--more-->';
				if (strpos($content,$morestring)  === false) {
					$morestring = '<span id="more-'.$post_id.'"></span>';
				}
				$explode_content = explode( $morestring, $content );
				if (isset($explode_content[0]) && isset($explode_content[1])) {
					$shown_content = $explode_content[0];
					$hidden_content = $explode_content[1];
					$this->debuggerPrint(__FUNCTION__." More string present hidden content ");
				}
				// ELSE use default word count
				else {
					$this->debuggerPrint(__FUNCTION__." Using word count: ");

					$wordAndPosition = str_word_count($content, 2);
					$length = $options['preview_count'];
					if (count($wordAndPosition) - 1 < $options['preview_count']) {
						$length = count($wordAndPosition) - 1;
					}
					$arraySlice = array_slice($wordAndPosition, $length, 1, true);
					$lastWord = reset($arraySlice);
					$indexToSplit = key($arraySlice) + strlen($lastWord);
					$shown_content = '';
					$hidden_content = $content;

					if ($length >= 0 ) {
						$shown_content = substr($content, 0, $indexToSplit+1 );
						$hidden_content = substr($content, $indexToSplit+1 );
					}

				}

				$content = wpautop($shown_content);
				$this->debuggerPrint(__FUNCTION__." | Adding shortcode \n");
				$content .= '[cointent_lockedcontent view_type="'.$view_type.'" title="'.$title.'" subtitle="'.$subtitle.'"'
					.' post_purchase_title="'.$widget_post_purchase_title.'"'
					.' post_purchase_subtitle="'.$widget_post_purchase_subtitle.'"]'
					.$hidden_content
					.'[/cointent_lockedcontent]';


			} else {
				$this->debuggerPrint(__FUNCTION__." Shortcode  present in content ");
			}
			return $content;
		}


		/**
		 *	Add stat tracking to head but don't pass article/pub id bc you are just tracking home
		 */
		function cointent_home_stats( ) {
			$this->debuggerPrint(__FUNCTION__." | Running  \n");
			if (is_front_page()) {
				$options = get_option('Cointent');
				$environment =  $options['environment'];
				$publisher_id = $options['publisher_id'];

				$base_url = COINTENT_PRODUCTION;
				if ($environment == 'sandbox') {
					$base_url = COINTENT_SANDBOX;
				}

				$isEnqueued = wp_script_is( 'main-cointent-js', 'enqueued' );
				if (!$isEnqueued) {
					wp_register_script( 'tracking-cointent-js', '//'.$base_url.'/cointent-tracker.0.2.js', array(), false, true);
					wp_enqueue_script('tracking-cointent-js');
				}

				wp_localize_script('tracking-cointent-js','cointent_tracking_data', array('publisherId'=>$publisher_id));
			}
		}
		/**
		 *	Add stat tracking to head & pass article/pub id bc you are tracking an article view
		 */
		function cointent_post_stats($content) {
			$this->debuggerPrint(__FUNCTION__." | Running  \n");
			// Home has it's own tracking, don't do this
			if (is_front_page()) {
				return $content;
			}

			if ($GLOBALS['post']->post_type == "post") {
				$options = get_option('Cointent');
				$environment =  $options['environment'];

				$base_url = COINTENT_PRODUCTION;
				if ($environment == 'sandbox') {
					$base_url = COINTENT_SANDBOX;
				}

				$this->debuggerPrint(__FUNCTION__." | Adding cointent.0.2.js to all posts \n");
				$isEnqueued = wp_script_is( 'main-cointent-js', 'enqueued' );
				// Switching to include cointent.0.2.js all the time
				if (!$isEnqueued && !isset($_GET['loadScript'])) {
					wp_register_script( 'main-cointent-js', '//'.$base_url.'/cointent.0.2.js', array(), false, true);
					wp_enqueue_script('main-cointent-js');
				}

				$isGated = $this->cointent_is_content_gated();
				if (isset($_GET['loadScript'])) {
					wp_register_script( 'tracking-cointent-js', '//'.$base_url.'/cointent-tracker.0.2.js', array(), false, true);
					wp_enqueue_script('tracking-cointent-js');

					wp_localize_script('tracking-cointent-js','cointent_tracking_data', array('articleId' => $GLOBALS['post']->ID, 'publisherId' => $options['publisher_id'], 'gated' => $isGated));
					wp_dequeue_script( 'main-cointent-js' );
					wp_deregister_script( 'main-cointent-js' );
				} else {
					$isGated = $this->cointent_is_content_gated();
					$data = array('articleId' => $GLOBALS['post']->ID, 'publisherId' => $options['publisher_id'], 'gated' => $isGated);
					wp_localize_script('main-cointent-js', 'cointent_tracking_data', $data);
				}


			}
			return $content;
		}

	}
}
function cointent_init () {
	cointent_class::get_instance();
}

if (class_exists('cointent_class'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('cointent_class', 'cointent_activate'));
	register_deactivation_hook(__FILE__, array('cointent_class', 'cointent_deactivate'));
	register_uninstall_hook(__FILE__, array('cointent_class', 'cointent_uninstall'));
	// instantiate the plugin class
	add_action('plugins_loaded', 'cointent_init');
}
?>