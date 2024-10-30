<?php

/**
 * Saving and display of CoinTent settings on the admin page
 */

function cointent_general_settings() {
	$options = get_option('Cointent');

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	?>
	<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		jQuery(document).ready(function () {
			jQuery('#ct_adblock_close_on').on('change', function (e) {
				if(e.delegateTarget.checked) {
					jQuery('.closeButtonOptions').show();
				}
			});
			jQuery('#ct_adblock_close_off').on('change', function (e) {
				if(e.delegateTarget.checked) {
					jQuery('.closeButtonOptions').hide();
				}
			});
		});

	</script>
	<div id="cointent-admin" class="metabox-holder has-right-sidebar">
		<?php if (!empty($_POST['_Submit'])) : ?>
			<div id="message" class="updated fade"><p><strong><?php _e('Options saved.', 'cointent') ?></strong></p></div>
		<?php endif; ?>

		<?php if( !empty($options['error'])) : ?>
			<div class="error_box">ERROR, a field was not saved : <?php echo $options['error']?></div>
		<?php endif; ?>

		<div class="intro">
			<?php if( empty($options['intro_dismissed']) || $options['intro_dismissed'] !== 1) : ?>
				<div class="intro_box">
					<div class="ct_close"><img src="<?php echo plugins_url('/images/close.png', COINTENT_BASE_DIR)?>"/></div>
					<div class="header">
						<span>Welcome to </span><img src="<?php echo plugins_url('/images/logo_and_name_265x51.png', COINTENT_BASE_DIR)?>"/>
					</div>
					<p>Geting started is easy! For more informtation, please read our <a target="_blank" href="//cointent.com/docs/wordpress">documentation</a>. If you have any questions, send us an email at  <a href="mailto:support@cointent.com">support@cointent.com</a></p>
					<div>

						<div class="cointent_step_box">
							<div><img src="<?php echo plugins_url('/images/step_1.png', COINTENT_BASE_DIR)?>"></div>
							<h3><?php _e('Step 1', 'cointent')?></h3>
							<h4><?php _e('General Settings', 'cointent')?></h4>
							<p> Signup for your <a  target="_blank" href="https://cointent.com/p/account">publisher account</a>, then fill in the publisher information in "General Settings" section below.</p>
						</div>
						<div class="cointent_step_box">
							<div><img src="<?php echo plugins_url('/images/step_2.png', COINTENT_BASE_DIR)?>"></div>
							<h3><?php _e('Step 2', 'cointent')?></h3>
							<h4><?php _e('Paywall', 'cointent')?></h4>
							<p>Choose which posts will be behind a paywall by selecting <a target="_blank" href="https://en.support.wordpress.com/posts/categories/">categories</a> in the "Paywall" section below or see <a target="_blank" href="https://cointent.com/docs/wordpress/advanced">advanced options</a> to use shortcodes</p>
						</div>
						<div class="cointent_step_box">
							<div><img src="<?php echo plugins_url('/images/step_3.png', COINTENT_BASE_DIR)?>"></div>
							<h3><?php _e('Step 3', 'cointent')?></h3>
							<h4><?php _e('Pricing and Subscriptions', 'cointent')?></h4>
							<p>You are ready to start selling content! You can update prices or setup subscriptions on <a target="_blank" href="//cointent.com/p/account">cointent.com</a>.</p>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<div class="">

			<form action="options.php" method="post" id="cointent-conf">
				<?php settings_fields( 'cointent-settings-group' ); ?>
				<?php do_settings_sections( 'cointent-settings-group' ); ?>
				<br>

				<div class="ct-section">

					<div class="body">
						<div class="postbox">
							<div class="ct_postbox_header">
								<h4><?php _e('Step 1') ?></h4>
								<h3><?php _e('General Settings') ?></h3>

								<div class="submit-button">
									<?php submit_button(); ?>
								</div>

								<div class="toggle_tray"><div class="triangle"></div></div>
							</div>
							<div class="ct_postbox_body">
								<table class="cointent_main_table">
									<tr>
										<td>
											<h4>Publisher Id</h4>
										</td>
										<td>
											<input type="text" id="ct_publisher_id" name="Cointent[publisher_id]" value="<?php echo $options['publisher_id'];?>"/>
										</td>
										<td>
											<p>Need an ID? <a class="button button-secondary" target="_blank" href="https://cointent.com/p/signup">Signup</a></p>
										</td>
									</tr>
									<tr>
										<td>
											<h4>Publisher Token</h4>

										</td>
										<td>
											<input type="text" id="ct_publisher_token" name="Cointent[publisher_token]" value="<?php echo $options['publisher_token'];?>"/>
										</td>
										<td>
											<p>Need a publisher token? <a class="button button-secondary" target="_blank" href="https://cointent.com/p/signup">Signup</a></p>
										</td>
									</tr>
								</table>

							</div>
						</div>
					</div>
					<div class="ct-section">

						<div class="body">
							<div class="postbox">
								<div class="ct_postbox_header">
									<h4><?php _e('Step 2') ?></h4>
									<h3><?php _e('Paywall') ?> </h3>
									<div class="submit-button">
										<?php submit_button(); ?>
									</div>
									<p>This section helps you determine which content will be behind a paywall. You can use either categories or Shortcode (or both)</p>
									<div class="toggle_tray"><div class="triangle"></div></div>
								</div>
								<div class="ct_postbox_body">
									<table class="cointent_main_table">

										<tr>
											<td  class="top">
												<h4><?php _e('Categories (Recommended)') ?> </h4>
												<p>You can select entire categories of posts to put behind a paywall.</p>
											</td>
											<td  class="top">
												<h2 scope="row" valign="top"><?php _e('Include Categories');?></h2>
												<p>Posts in these categories will have a paywall.</p>
												<div>
													<?php
													$activeCat = $options['include_categories'];
													if(!$activeCat) {
														$activeCat = array();
													}

													$categories = get_categories( array( 'hide_empty' => 0 ) );
													echo "<ul>";
													$allChecked = array_key_exists( 'all_cointent', $activeCat ) ? "checked='checked'" : '';
													echo "<li><input name='Cointent[include_categories][all_cointent]' type='checkbox' value='yes' $allChecked />All Categories</li>\n";
													foreach ( $categories as $cat ) {
														$checked = array_key_exists( $cat->term_id, $activeCat ) ? "checked='checked'" : '';
														echo "<li><input name='Cointent[include_categories][{$cat->term_id}]' type='checkbox' value='yes' $checked /> {$cat->name}</li>\n";
													}
													echo "</ul>";
													?>
												</div>
											</td>
											<td class="top">
												<h2 scope="row" valign="top"><?php _e('Exclude Categories');?></h2>
												<p>Posts in the categories selected below will not have a paywall (even if it is also selected under "Include Categories"). </p>
												<div>
													<?php
													$activeCat = $options['exclude_categories'];
													if(!$activeCat) {
														$activeCat = array();
													}
													$categories = get_categories( array( 'hide_empty' => 0 ) );
													echo "<ul>";
													foreach ( $categories as $cat ) {
														$checked = array_key_exists( $cat->term_id, $activeCat ) ? "checked='checked'" : '';
														echo "<li><input name='Cointent[exclude_categories][{$cat->term_id}]' type='checkbox' value='yes' $checked /> {$cat->name}</li>\n";
													}
													echo "</ul>";
													?>
												</div>
											</td>
										</tr>
										<tr>
											<td  class="top">
												<h4>Shortcode (Advanced)</h4>
												<p>Our shortcode is : [cointent_lockedcontent][/cointent_lockedcontent]. Options added to the shortcode override defaults set in the CoinTent admin section.</p>
											</td>
											<td colspan="2" >
												<p>For information on how to set-up your shortcode, see the <a target="_blank" href="https://cointent.com/docs/wordpress/advanced" >Advanced Options documentation</a></p>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="ct-section">
						<div class="body">
							<div class="postbox">
								<div class="ct_postbox_header">
									<h4><?php _e('Step 3') ?></h4>
									<h3><?php _e('Pricing and Subscriptions') ?></h3>
									<div class="submit-button">
										<?php submit_button(); ?>
									</div>
									<div class="toggle_tray"><div class="triangle"></div></div>
								</div>
								<div class="ct_postbox_body">
									<table class="cointent_main_table">
										<tr>
											<td>
												<h4>Setup</h4>
											</td>
											<td colspan="2">
												Content you choose to be behind a paywall will automatically be available for micropayments. If you wish to setup subscriptions in addition, please go to the <a target="_blank" href="https://cointent.com/p/account#subscriptionPlans">publisher dashboard</a>.  For more information, please view our <a target="_blank" href="https://cointent.com/docs/subscriptions">subscription documentation</a>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="ct-section">
						<div class="body">
							<div class="postbox">
								<div class="ct_postbox_header">
									<h4></h4>
									<h3><?php _e('Preview Settings') ?></h3>
									<div class="submit-button">
										<?php submit_button(); ?>
									</div>
									<p>This section details the three methods to creating a preview: word count, more tag, and shortcode. The preview is the content displayed before the user purchases and unlocks the post.  You can use this section to choose how much content is displayed before the paywall cut off.</p>

									<div class="toggle_tray"><div class="triangle"></div></div>
								</div>
								<div class="ct_postbox_body">
									<table class="cointent_main_table">
										<tr>
											<td>
												<h4><?php _e('Word Count, (default)', 'cointent') ?> </h4>
												<p>
													<?php _e('This number of words will be shown as a preview to your post. The default is 55, this can be overridden using either a more tag or a shortcode.', 'cointent')?>
												</p>
											</td>
											<td>
												<ul>
													<li>
														<input type="text" id="ct_preview_count" name="Cointent[preview_count]" value="<?php echo $options['preview_count'];?>"/>
													</li>
												</ul>
											</td>
											<td><h2><?php _e('Example')?></h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit</p></td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('More Tag (priority 2)', 'cointent')?></h4>
												<p>The more tag will override the Word Count to make a longer or shorter length.</p>
											</td>
											<td >
												<h2><?php _e('<!--more-->', 'cointent')?></h2>
												<p><?php _e('We use the built in Wordpress more tag to define the preview of locked posts. The more tag will trump your word count setting, but if a shortcode is present the plugin will use that to determine the preview.', 'cointent')?></p>
											</td>
											<td >
												<h2><?php _e('Example', 'cointent')?></h2>
												<p><?php _e('All content put before the more tag will be the preview. <!--more--> Content below the more tag will be behind a paywall.', 'cointent')?></p>
											</td>

										</tr>
										<tr>
											<td>
												<h4><?php _e('Shortcode (priority 1, Advanced)') ?> </h4>
												<p>You can override the Word Count or More Tag by using our shortcode within the post..</p>
											</td>
											<td>
												<p>Put the shortcode around the text and images you want to hide, you can leave content unhidden above and below it. Shortcodes have the highest priority and will trump, the more tag or the word count methods.</p>
											</td>
											<td>
												<h2>Example</h2>
												<p>[cointent_lockedcontent] ALL content put between the shortcode tags will be behind a paywall. [/cointent_lockedcontent] Content outside will not be locked.</p>
											</td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('Preview Only Image/Text (Advanced)', 'cointent') ?> </h4>
												<p>Advanced option: Preview image/text that disappears after purchase.  To setup image/text that disappears after purchase, look at our <a target="_blank" href="https://cointent.com/docs/wordpress/advanced">advanced preview options documentation</a></p>
											</td>
											<td>
												<p>Pre-purchase</p>
												<img style="max-width:350px;" src="<?php echo plugins_url('/images/preview_cointent_extras_pre.png', COINTENT_BASE_DIR)?>"/>
											</td>
											<td>
												<p>Post-purchase</p>
												<img style="max-width:350px;" src="<?php echo plugins_url('/images/preview_cointent_extras_post.png', COINTENT_BASE_DIR)?>"/>
											</td>

										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="ct-section">

						<div class="body">
							<div class="postbox">
								<div class="ct_postbox_header">
									<h4></h4>
									<h3><?php _e('Widget and Popup Customization') ?> </h3>
									<div class="submit-button">
										<?php submit_button(); ?>
									</div>
									<p><?php _e('This section allows you to customize the messaging that goes along with the purchase options on your site (via the CoinTent widget), and customize information on the pop-up.', 'cointent')?></p>
									<div class="toggle_tray"><div class="triangle"></div></div>
								</div>
								<div class="ct_postbox_body">
									<table class="cointent_main_table">
										<tr>
											<td>
												<h4><?php _e('View Type', 'cointent')?></h4>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_condensed" name="Cointent[view_type]" value="condensed" <?php if ($options['view_type'] ==  "condensed" ) { echo "checked"; }?>/><label for="ct_condensed">&nbsp;<?php _e( 'Condensed' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_full" name="Cointent[view_type]" value="full" <?php if ($options['view_type'] ==  "full" ) { echo "checked"; }?>/><label for="ct_full">&nbsp;<?php _e( 'Full' ); ?></label><br>
													</li>

												</ul>
											</td>
											<td>
												<p><?php sprintf( _e( 'Checkout the different view options at our webpage <a class="button button-secondary" href="%s">View Options</a>', 'cointent' ), esc_url( 'https://cointent.com/docs/wordpress/views' ) )?></p>

											</td>
										</tr>

										<tr>
											<td>
												<h4>Message Above Purchase Buttons</h4>
												<p>This is default text shown next to your purchase buttons and in the post-purchase space. (Subtitles also support HTML)</p>
											</td>
											<td  class="top">
												<h2>Before Purchase</h2>

												<label for="ct_widget_title">&nbsp;<?php _e( 'Title, Before Purchase' ); ?></label>
												<textarea type="text" id="ct_widget_title" name="Cointent[widget_title]" cols="40" rows="6"><?php echo $options['widget_title'];?></textarea>
												<br/>
												<label for="ct_widget_subtitle">&nbsp;<?php _e( 'Subtitle, Before Purchase' ); ?></label>
												<textarea type="text" id="ct_widget_subtitle" name="Cointent[widget_subtitle]" cols="40" rows="6"><?php echo $options['widget_subtitle'];?></textarea></td>

											</td>
											<td class="top">
												<h2>After Purchase</h2>
												<label for="ct_widget_post_purchase_title">&nbsp;<?php _e( 'Title, After Purchase' ); ?></label>
												<textarea type="text" id="ct_widget_post_purchase_title" name="Cointent[widget_post_purchase_title]" cols="40" rows="6"><?php echo $options['widget_post_purchase_title'];?></textarea>
												<br/>
												<label for="ct_widget_post_purchase_subtitle">&nbsp;<?php _e( 'Subtitle, After Purchase' ); ?></label>
												<textarea type="text" id="ct_widget_post_purchase_subtitle" name="Cointent[widget_post_purchase_subtitle]" cols="40" rows="6"> <?php echo $options['widget_post_purchase_subtitle'];?></textarea>

											</td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('Header Logo & Color')?></h4>
											</td>

											<td colspan="2">
												<p>You can add your logo image and background color to the CoinTent popup. Below is an example of the header. Please visit the <a target="_blank" href="https://cointent.com/p/account">account page</a> to setup.</p>
												<img src="<?php echo plugins_url('/images/banner.png', COINTENT_BASE_DIR)?>"/>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="ct-section">

						<div class="body">
							<div class="postbox">
								<div class="ct_postbox_header">
									<h4></h4>
									<h3><?php _e('Metered Paywall') ?> </h3>
									<div class="submit-button">
										<?php submit_button(); ?>
									</div>
									<p><?php _e('This section allows you to activate a metered paywall', 'cointent')?></p>
									<div class="toggle_tray"><div class="triangle"></div></div>
								</div>
								<div class="ct_postbox_body">
									<table class="cointent_main_table">
										<tr>
											<td>
												<h4><?php _e('Meter', 'cointent')?></h4>
											</td>
											<td>
												<input type="radio" id="ct_all" name="Cointent[meter_active]" value="1" <?php if ($options['meter_active'] ==  1 ) { echo "checked"; }?>/><label for="ct_meter_on">&nbsp;<?php _e( 'On' ); ?></label><br>
												<input type="radio" id="ct_all" name="Cointent[meter_active]" value="0" <?php if ($options['meter_active'] ==  0 ) { echo "checked"; }?>/><label for="ct_meter_off">&nbsp;<?php _e( 'Off' ); ?></label><br>
											</td>
											<td>
												<p>Global on switch for a metered paywall. Use the below settings to customize the paywall.</p>
											</td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('Meter Type', 'cointent')?></h4>
											</td>
											<td>
												<ul>

													<li>
														<input type="radio" id="ct_all" name="Cointent[meter_type]" value="all" <?php if ($options['meter_type'] ==  "all" ) { echo "checked"; }?>/><label for="ct_all">&nbsp;<?php _e( 'All' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_page" name="Cointent[meter_type]" value="page" <?php if ($options['meter_type'] ==  "page" ) { echo "checked"; }?>/><label for="ct_page">&nbsp;<?php _e( 'Page' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_article" name="Cointent[meter_type]" value="article" <?php if ($options['meter_type'] ==  "article" ) { echo "checked"; }?>/><label for="ct_article">&nbsp;<?php _e( 'Article' ); ?></label><br>
													</li>
												</ul>
											</td>
											<td>
												<p>This setting tells us what to count against the meter.</p>
												<p>All: any page hit, a new page or a new article counts as a meter view. </p>
												<p>Page: Each page with a distinct url will count as a page view. If there are multiple articles on the page it will only count once.</p>
												<p>Article: Each view of an artilce will count as a page view. If there are multiple articles on the page it will count each as a view.</p>
											</td>
										</tr>

										<tr>
											<td>
												<h4>Meter Time Period</h4>
												<p>How long does the meter last</p>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_timeframe_all" name="Cointent[meter_timeframe]" value="all" <?php if ($options['meter_timeframe'] ==  "all" ) { echo "checked"; }?>/><label for="ct_timeframe_all">&nbsp;<?php _e( 'All Time' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_month" name="Cointent[meter_timeframe]" value="month" <?php if ($options['meter_timeframe'] ==  "month" ) { echo "checked"; }?>/><label for="ct_month">&nbsp;<?php _e( 'Month' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_day" name="Cointent[meter_timeframe]" value="days" <?php if ($options['meter_timeframe'] ==  "days" ) { echo "checked"; }?>/><label for="ct_day_check">&nbsp;<?php _e( 'Days' ); ?></label><br>
														<input type="number" id="ct_day_amount" name="Cointent[meter_timeframe_days]" value="<?php if ($options['meter_timeframe'] ==  "days" ) { echo $options['meter_timeframe_days']; } ?>"/><label for="ct_day_amount">&nbsp;<?php _e( '# of Days' ); ?></label><br>
													</li>
												</ul>
											</td>
											<td>
												<p>This setting tells us when to reset the meter</p>
												<p>All: Never reset the meter, users will only get a set number of views, before they are required to purchase.</p>
												<p>Month: The meter resets at the beginning of every month.</p>
												<p>X Days: Check the last X days of viewership that, is how many article views they have. This never fully resets, it is a rolling time frame in which to count views.</p>
											</td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('Article Limit')?></h4>
											</td>
											<td>
												<input type="number" id="ct_article_count" name="Cointent[meter_articles]" value="<?php echo $options['meter_articles']?>"/><label for="ct_article_count">&nbsp;<?php _e( '# of Articles' ); ?></label><br>
											</td>
											<td>
												This is the number of articles the user can view before hitting a hard paywall.
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="ct-section">

						<div class="body">
							<div class="postbox">
								<div class="ct_postbox_header">
									<h4></h4>
									<h3><?php _e('Ad Block Paywall') ?> </h3>
									<div class="submit-button">
										<?php submit_button(); ?>
									</div>
									<p><?php _e('This section allows you to activate an adblock paywall', 'cointent')?></p>
									<div class="toggle_tray"><div class="triangle"></div></div>
								</div>
								<div class="ct_postbox_body">
									<table class="cointent_main_table">
										<tr>
											<td>
												<h4><?php _e('Ad Block', 'cointent')?></h4>
											</td>
											<td>
												<input type="radio" id="ct_adblock_active_on" name="Cointent[adblock_active]" value="1" <?php if ($options['adblock_active'] ==  1 ) { echo "checked"; }?>/><label for="ct_adblock_active_on">&nbsp;<?php _e( 'On' ); ?></label><br>
												<input type="radio" id="ct_adblock_active_off" name="Cointent[adblock_active]" value="0" <?php if ($options['adblock_active'] ==  0 ) { echo "checked"; }?>/><label for="ct_adblock_active_off">&nbsp;<?php _e( 'Off' ); ?></label><br>
											</td>
											<td>
												<p>Global on switch for a ad block messaging. Use the below settings to customize the dialog.</p>
											</td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('Template', 'cointent')?></h4>
											</td>
											<td>
												<ul>

													<li>
														<input type="radio" id="ct_adblock_view" name="Cointent[adblock_type]" value="viewOnly" <?php if ($options['adblock_type'] ==  "viewOnly" ) { echo "checked"; }?>/><label for="ct_adblock_view">&nbsp;<?php _e( 'Whitelist Only' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_adblock_buy" name="Cointent[adblock_type]" value="buyOnly" <?php if ($options['adblock_type'] ==  "buyOnly" ) { echo "checked"; }?>/><label for="ct_adblock_buy">&nbsp;<?php _e( 'Buy Only' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_adblock_nonwhitelist" name="Cointent[adblock_type]" value="both" <?php if ($options['adblock_type'] ==  "both" ) { echo "checked"; }?>/><label for="ct_adblock_nonwhitelist">&nbsp;<?php _e( 'Both Options' ); ?></label><br>
													</li>
												</ul>
											</td>
											<td>
												<p>This setting tells us what the template you want to use.</p>
											</td>
										</tr>

										<tr>
											<td>
												<h4>Close Button</h4>
												<p></p>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_adblock_close_off" name="Cointent[adblock_close_button]" value="0" <?php if ($options['adblock_close_button'] ==  0 ) { echo "checked"; }?>/><label for="ct_adblock_close_off">&nbsp;<?php _e( 'Off' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_adblock_close_on" name="Cointent[adblock_close_button]" value="1" <?php if ($options['adblock_close_button'] ==  1 ) { echo "checked"; }?>/><label for="ct_adblock_close_on">&nbsp;<?php _e( 'On' ); ?></label><br>
													</li>
												</ul>
												<div style="display:<?php if ($options['adblock_close_button'] ==  1 ) { echo "block"; } else { echo "none"; }?>;" class="closeButtonOptions">
													<hr>
													<input type="number" id="ct_adblock_close_timer" name="Cointent[adblock_close_button_timer]" value="<?php echo $options['adblock_close_button_timer']; ?>"/><label for="adblock_close_button_timer">&nbsp;<?php _e( '# of Seconds Until Close Button Active' ); ?></label><br>

												</div>
											</td>
											<td>
												<p>Options for the close button in the upper right hand corner.</p>
											</td>
										</tr>

										<tr>
											<td>
												<h4>Frequency of showing the popup</h4>
												<p></p>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_adblock_per_session_off" name="Cointent[adblock_per_session]" value="0" <?php if ($options['adblock_per_session'] ==  0 ) { echo "checked"; }?>/><label for="ct_adblock_per_session_off">&nbsp;<?php _e( 'Show the dialog on every page view' ); ?></label><br>
														<input type="radio" id="ct_adblock_per_session_on" name="Cointent[adblock_per_session]" value="1" <?php if ($options['adblock_per_session'] ==  1 ) { echo "checked"; }?>/><label for="ct_adblock_per_session_on">&nbsp;<?php _e( 'Show the dialog only once per session' ); ?></label><br>
													</li>
												</ul>
											</td>
											<td>
												<p>Options for the hwo frequently to show the dialog to your ad block users.</p>
											</td>
										</tr>

										<tr>
											<td>
												<h4><?php _e('Images')?></h4>
											</td>
											<td>
												<label for="ct_adblock_logo">&nbsp;<?php _e( 'Logo' ); ?></label>
												<input type="text" id="ct_adblock_logo" name="Cointent[adblock_logo]"
													   placeholder="(default image)" value="<?php echo $options['adblock_logo'];?>"  style="width:100%"/>

												<br/>
												<label for="ct_adblock_image_url">&nbsp;<?php _e( 'Image' ); ?></label>
												<input type="text" id="ct_adblock_image_url" name="Cointent[adblock_image_url]"
													   placeholder="(default image)"  value="<?php echo $options['adblock_image_url'];?>" style="width:100%"/>
											</td>
											<td>
												<div>
													<p>Preview of logo</p>
													<img style="max-width:200px; max-height:150px;" src="<?php if(!empty($options['adblock_logo'])) {
														echo $options['adblock_logo'];
													} else {
														echo '//connect.cointent.com/images/timestimes_logo.png';
													}?>"/>
												</div>
												<div>
													<p>Preview of image</p>
													<img style="max-width:200px; max-height:150px;" src="<?php if(!empty($options['adblock_image_url'])) {
														echo $options['adblock_image_url'];
													} else {
														echo '//connect.cointent.com/images/journalist-writing.jpg';
													}?>" />
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('Text')?></h4>
											</td>
											<td>
												<label for="ct_adblock_header">&nbsp;<?php _e( 'Header Title' ); ?></label>
												<textarea type="text" id="ct_adblock_header" name="Cointent[adblock_header_text]" cols="40" rows="6"><?php echo $options['adblock_header_text'];?></textarea>
												<br/>
												<label for="ct_adblock_subheader">&nbsp;<?php _e( 'Subheader Title' ); ?></label>
												<textarea type="text" id="ct_adblock_subheader" name="Cointent[adblock_subheader_text]" cols="40" rows="6"><?php echo $options['adblock_subheader_text'];?></textarea></td>


											</td>
											<td>
												This is the number of articles the user can view before hitting a hard paywall.
											</td>
										</tr>

									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="ct-section">

						<div class="body">
							<div class="postbox">
								<div class="ct_postbox_header">
									<h4></h4>
									<h3><?php _e('Advanced Settings', 'cointent') ?></h3>
									<div class="submit-button">
										<?php submit_button(); ?>
									</div>
									<p>This section includes settings that will allow a technical user to make more customization to CoinTent, and to configure the plugin for a development environment.</p>
									<div class="toggle_tray"><div class="triangle"></div></div>
								</div>
								<div class="ct_postbox_body">
									<table class="cointent_main_table">
										<tr>
											<td>
												<h4>Full page reload</h4>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_reload_partial_page" name="Cointent[reload_full_page]" value="0" <?php if ($options['reload_full_page'] ==  0 ) { echo "checked"; }?>/><label for="ct_reload_partial_page">&nbsp;<?php _e( 'Partial' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_reload_inplace" name="Cointent[reload_full_page]" value="1" <?php if ($options['reload_full_page'] ==  1 ) { echo "checked"; }?>/><label for="ct_reload_inplace">&nbsp;<?php _e( 'In place page reload' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_reload_full_page" name="Cointent[reload_full_page]" value="2" <?php if ($options['reload_full_page'] ==  2 ) { echo "checked"; }?>/><label for="ct_reload_full_page">&nbsp;<?php _e( 'Full page refresh' ); ?></label><br>
													</li>
												</ul>
											</td>
											<td>
												<p>If you are just locking text we suggest partial page reload. If you have interactive content, you may need to do a full page reload, or in place page reload to make sure the correct javascript files are included.</p>
											</td>
										</tr>

										<tr>
											<td>
												<h4>Client Side Locking</h4>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_client_side_locking_off" name="Cointent[client_side_locking]" value="0" <?php if ($options['client_side_locking'] ==  0 ) { echo "checked"; }?>/><label for="ct_client_side_locking_off">&nbsp;<?php _e( 'Off' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_client_side_locking_on" name="Cointent[client_side_locking]" value="1" <?php if ($options['client_side_locking'] ==  1 ) { echo "checked"; }?>/><label for="ct_client_side_locking_on">&nbsp;<?php _e( 'On' ); ?></label><br>
													</li>
												</ul>
											</td>
											<td>
												<p>Client side locking vs Server side locking</p>
											</td>
										</tr>
										<tr>
											<td>
												<h4><?php _e('Tracking') ?> </h4>
												<p>Please be sure to opt into tracking to get the full value from our offering.</p>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_active" name="Cointent[cointent_tracking]" value="1" <?php if (isset($options['cointent_tracking']) && $options['cointent_tracking'] == true ) { echo "checked"; }?>/><label for="ct_tracking_active">&nbsp;<?php _e( 'Active' ); ?></label><br>
													</li>
													<li>
														<input type="radio" id="ct_inactive" name="Cointent[cointent_tracking]" value="0" <?php if (!isset($options['cointent_tracking']) || $options['cointent_tracking']  == false ) { echo "checked"; }?>/><label for="ct_tracking_inactive">&nbsp;<?php _e( 'Inactive' ); ?></label>
													</li>
													<li>
														<input type="hidden" id="ct_tracking_popup" name="Cointent[tracking_popup]" value="<?php echo $options['tracking_popup'] ?>"/>
													</li>
												</ul>
											</td>
											<td>
												<p>
													By opting in, we can assist you by providing full transparency on your content views, clicks, and purchases.
													Tracking these actions allows you to find out how many users are seeing and trying to buy your content, and can help you optimize your flows and content offerings to maximize your results.
													Security and privacy are of the utmost importance to us, and we use <a target="_blank" href="https://www.keen.io">keen.io</a> and <a target="_blank" href="https://mixpanel.com">mixpanel.com</a> to process tracking calls and keep them secure.
												</p>
											</td>
										</tr>
										<tr>
											<td>
												<h4>Environment</h4>
												<p>Changing the environment allows testing of the product flow without the use of real credit cards, and without affecting your stats. Please use this on any dev or staging environments you have setup.</p>
											</td>
											<td>
												<ul>
													<li>
														<input type="radio" id="ct_production" name="Cointent[environment]" value="production" <?php if ($options['environment'] ==  "production" ) { echo "checked"; }?>/>
														<label for="ct_production">&nbsp;<?php _e( 'Live' ); ?></label>
													</li>
													<li>
														<input type="radio" id="ct_sandbox" name="Cointent[environment]" value="sandbox" <?php if ($options['environment'] ==  "sandbox" ) { echo "checked"; }?>/>
														<label for="ct_sandbox">&nbsp;<?php _e( 'Sandbox - for testing only' ); ?></label>
													</li>

												</ul>
											</td>
											<td>
												<dl>
													<dt class="bold">Live </dt><dd> This is the setting you use to on your site when you are ready for real customers to pay real money!</dd>
													<dt class="bold">Sandbox </dt><dd>This is a testing environment, no real payments will be processed, only use this if you have a dev or staging blog that isn't open to the public. Please see the <a target="_blank" href="https://cointent.com/docs/environments"> documentation</a> for valid testing credit card numbers. </dd>
												</dl>
											</td>
										</tr>
										<tr>
											<td>
												<h4>Addition CSS Class</h4>
											</td>
											<td>
												<input type="text" id="ct_widget_additional_css" name="Cointent[widget_additional_css]"  value="<?php echo $options['widget_additional_css'];?>"/>
											</td>
											<td>
												<p>In case you want to apply your own css to our widget.</p>
											</td>
										</tr>

									</table>
								</div>
							</div>
						</div>
					</div>
			</form>
		</div>
	</div>
	<?php
}