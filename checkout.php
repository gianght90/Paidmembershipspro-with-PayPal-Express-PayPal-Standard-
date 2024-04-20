<?php
/**
 * Template: Checkout
 * Version: 3.0
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.0
 *
 * @author Paid Memberships Pro
 */

global $gateway, $pmpro_review, $skip_account_fields, $pmpro_paypal_token, $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_requirebilling, $pmpro_level, $tospage, $pmpro_show_discount_code, $pmpro_error_fields, $pmpro_default_country;
global $discount_code, $username, $password, $password2, $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth,$ExpirationYear;

$pmpro_levels = pmpro_getAllLevels();

/**
 * Filter to set if PMPro uses email or text as the type for email field inputs.
 *
 * @since 1.8.4.5
 *
 * @param bool $use_email_type, true to use email type, false to use text type
 */
$pmpro_email_field_type = apply_filters('pmpro_email_field_type', true);

// Set the wrapping class for the checkout div based on the default gateway;
$default_gateway = get_option( 'pmpro_gateway' );
if ( empty( $default_gateway ) ) {
	$pmpro_checkout_gateway_class = 'pmpro_checkout_gateway-none';
} else {
	$pmpro_checkout_gateway_class = 'pmpro_checkout_gateway-' . $default_gateway;
}
?>

<?php do_action('pmpro_checkout_before_form'); ?>

<div id="pmpro_level-<?php echo intval( $pmpro_level->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( $pmpro_checkout_gateway_class, 'pmpro_level-' . $pmpro_level->id ) ); ?>">
<form id="pmpro_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="<?php if(!empty($_REQUEST['review'])) echo esc_url( pmpro_url("checkout", "?pmpro_level=" . $pmpro_level->id) ); ?>" method="post">

	<input type="hidden" id="pmpro_level" name="pmpro_level" value="<?php echo esc_attr($pmpro_level->id) ?>" />
	<input type="hidden" id="checkjavascript" name="checkjavascript" value="1" />
	<?php if ($discount_code && $pmpro_review) { ?>
		<input class="<?php echo esc_attr( pmpro_get_element_class( 'input pmpro_alter_price', 'pmpro_discount_code' ) ); ?>" id="pmpro_discount_code" name="pmpro_discount_code" type="hidden" size="20" value="<?php echo esc_attr($discount_code) ?>" />
	<?php } ?>

	<?php if($pmpro_msg) { ?>
		<div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
			<?php echo wp_kses_post( apply_filters( 'pmpro_checkout_message', $pmpro_msg, $pmpro_msgt ) ); ?>
		</div>
	<?php } else { ?>
		<div id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
	<?php } ?>

	<?php if($pmpro_review) { ?>
		<p><?php echo wp_kses( __( 'Almost done. Review the membership information and pricing below then <strong>click the "Complete Payment" button</strong> to finish your order.', 'paid-memberships-pro' ), array( 'strong' => array() ) ); ?></p>
	<?php } ?>

	<?php
		$include_pricing_fields = apply_filters( 'pmpro_include_pricing_fields', true );
		if ( $include_pricing_fields ) {
		?>
		<div id="pmpro_pricing_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout', 'pmpro_pricing_fields' ) ); ?>">
			<h2>
				<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-name' ) ); ?>"><?php esc_html_e('Membership Level', 'paid-memberships-pro' );?></span>
				<?php if(count($pmpro_levels) > 1) { ?><span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-msg' ) ); ?>"><a aria-label="<?php esc_html_e( 'Select a different membership level', 'paid-memberships-pro' ); ?>" href="<?php echo esc_url( pmpro_url( "levels" ) ); ?>"><?php esc_html_e('change', 'paid-memberships-pro' );?></a></span><?php } ?>
			</h2>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-fields' ) ); ?>">
				<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_name_text' ) );?>">
					<?php
					// Tell the user which level they are signing up for.
					printf( esc_html__('You have selected the %s membership level.', 'paid-memberships-pro' ), '<strong>' . esc_html( $pmpro_level->name ) . '</strong>' );

					// If a level will be removed with this purchase, let them know that too.
					// First off, get the group for this level and check if it allows a user to have multiple levels.
					$group_id = pmpro_get_group_id_for_level( $pmpro_level->id );
					$group    = pmpro_get_level_group( $group_id );
					if ( ! empty( $group ) && empty( $group->allow_multiple_selections ) ) {
						// Get all of the user's current membership levels.
						$levels = pmpro_getMembershipLevelsForUser( $current_user->ID );

						// Loop through the levels and see if any are in the same group as the level being purchased.
						if ( ! empty( $levels ) ) {
							foreach ( $levels as $level ) {
								// If this is the level that the user is purchasing, continue.
								if ( $level->id == $pmpro_level->id ) {
									continue;
								}

								// If this level is not in the same group, continue.
								if ( pmpro_get_group_id_for_level( $level->id ) != $group_id ) {
									continue;
								}

								// If we made it this far, the user is going to lose this level after checkout.
								printf( ' ' . esc_html__( 'Your current membership level of %s will be removed when you complete your purchase.', 'paid-memberships-pro' ), '<strong>' . esc_html( $level->name ) . '</strong>' );
							}
						}
					}
					?>
				</p>

				<?php
					/**
					 * All devs to filter the level description at checkout.
					 * We also have a function in includes/filters.php that applies the the_content filters to this description.
					 * @param string $description The level description.
					 * @param object $pmpro_level The PMPro Level object.
					 */
					$level_description = apply_filters('pmpro_level_description', $pmpro_level->description, $pmpro_level);
					if ( ! empty( $level_description ) ) { ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_description_text' ) );?>">
							<?php echo wp_kses_post( $level_description ); ?>
						</div>
						<?php
					}
				?>

				<div id="pmpro_level_cost">
					<?php if($discount_code && pmpro_checkDiscountCode($discount_code)) { ?>
						<?php
							echo '<p class="' . esc_attr( pmpro_get_element_class( 'pmpro_level_discount_applied' ) ) . '">';
							echo wp_kses( sprintf( __( 'The <strong>%s</strong> code has been applied to your order.', 'paid-memberships-pro' ), $discount_code ), array( 'strong' => array() ) );
							echo '</p>';
						?>
					<?php } ?>

					<?php
						$level_cost_text = pmpro_getLevelCost( $pmpro_level );
						if ( ! empty( $level_cost_text ) ) { ?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_cost_text' ) );?>">
								<?php echo wp_kses_post( wpautop( $level_cost_text ) ); ?>
							</div>
						<?php }
					?>

					<?php
						$level_expiration_text = pmpro_getLevelExpiration( $pmpro_level );
						if ( ! empty( $level_expiration_text ) ) { ?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level_expiration_text' ) );?>">
								<?php echo wp_kses_post( wpautop( $level_expiration_text ) ); ?>
							</div>
						<?php }
					?>
				</div> <!-- end #pmpro_level_cost -->

				<?php do_action("pmpro_checkout_after_level_cost"); ?>

				<?php if($pmpro_show_discount_code) { ?>
					<?php if($discount_code && !$pmpro_review) { ?>
						<p id="other_discount_code_p" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_small', 'other_discount_code_p' ) ); ?>"><button type="button" id="other_discount_code_toggle"><?php esc_html_e('Click here to change your discount code', 'paid-memberships-pro' );?></button></p>
					<?php } elseif(!$pmpro_review) { ?>
						<p id="other_discount_code_p" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_small', 'other_discount_code_p' ) ); ?>"><?php esc_html_e('Do you have a discount code?', 'paid-memberships-pro' );?> <button type="button" id="other_discount_code_toggle"><?php esc_html_e('Click here to enter your discount code', 'paid-memberships-pro' );?></button></p>
					<?php } elseif($pmpro_review && $discount_code) { ?>
						<p><strong><?php esc_html_e('Discount Code', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $discount_code ); ?></p>
					<?php } ?>
				<?php } ?>

				<?php if($pmpro_show_discount_code) { ?>
				<div id="other_discount_code_tr" style="display: none;">
					<label for="pmpro_other_discount_code"><?php esc_html_e('Discount Code', 'paid-memberships-pro' );?></label>
					<input id="pmpro_other_discount_code" name="pmpro_other_discount_code" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'input pmpro_alter_price', 'other_discount_code' ) ); ?>" size="20" value="<?php echo esc_attr($discount_code); ?>" />
					<input aria-label="<?php esc_html_e( 'Apply discount code', 'paid-memberships-pro' ); ?>" type="button" name="other_discount_code_button" id="other_discount_code_button" value="<?php esc_attr_e('Apply', 'paid-memberships-pro' );?>" />
				</div>
				<?php } ?>
			</div> <!-- end pmpro_checkout-fields -->
		</div> <!-- end pmpro_pricing_fields -->
		<?php
		} // if ( $include_pricing_fields )
	?>

	<?php
		do_action('pmpro_checkout_after_pricing_fields');
	?>

	<?php if(!$skip_account_fields && !$pmpro_review) { ?>

	<?php 
		// Get discount code from URL parameter, so if the user logs in it will keep it applied.
		$discount_code_link = !empty( $discount_code) ? '&pmpro_discount_code=' . $discount_code : ''; 
	?>
	<div id="pmpro_user_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout', 'pmpro_user_fields' ) ); ?>">
		<hr />
		<h2>
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-name' ) ); ?>"><?php esc_html_e('Account Information', 'paid-memberships-pro' );?></span>
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-msg' ) ); ?>"><?php esc_html_e('Already have an account?', 'paid-memberships-pro' );?> <a href="<?php echo esc_url( wp_login_url( apply_filters( 'pmpro_checkout_login_redirect', pmpro_url("checkout", "?pmpro_level=" . $pmpro_level->id . $discount_code_link) ) ) ); ?>"><?php esc_html_e('Log in here', 'paid-memberships-pro' );?></a></span>
		</h2>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-fields' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_checkout-field-username', 'pmpro_checkout-field-username' ) ); ?>">
				<label for="username"><?php esc_html_e('Username', 'paid-memberships-pro' );?></label>
				<input id="username" name="username" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'input', 'username' ) ); ?>" size="30" value="<?php echo esc_attr($username); ?>" />
			</div> <!-- end pmpro_checkout-field-username -->

			<?php
				do_action('pmpro_checkout_after_username');
			?>

			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_checkout-field-password', 'pmpro_checkout-field-password' ) ); ?>">
				<label for="password"><?php esc_html_e('Password', 'paid-memberships-pro' );?></label>
				<input id="password" name="password" type="password" class="<?php echo esc_attr( pmpro_get_element_class( 'input', 'password' ) ); ?>" size="30" value="<?php echo esc_attr($password); ?>" />
			</div> <!-- end pmpro_checkout-field-password -->

			<?php
				$pmpro_checkout_confirm_password = apply_filters("pmpro_checkout_confirm_password", true);
				if($pmpro_checkout_confirm_password) { ?>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_checkout-field-password2', 'pmpro_checkout-field-password2' ) ); ?>">
						<label for="password2"><?php esc_html_e('Confirm Password', 'paid-memberships-pro' );?></label>
						<input id="password2" name="password2" type="password" class="<?php echo esc_attr( pmpro_get_element_class( 'input', 'password2' ) ); ?>" size="30" value="<?php echo esc_attr($password2); ?>" />
					</div> <!-- end pmpro_checkout-field-password2 -->
				<?php } else { ?>
					<input type="hidden" name="password2_copy" value="1" />
				<?php }
			?>

			<?php
				do_action('pmpro_checkout_after_password');
			?>

			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_checkout-field-bemail', 'pmpro_checkout-field-bemail' ) ); ?>">
				<label for="bemail"><?php esc_html_e('Email Address', 'paid-memberships-pro' );?></label>
				<input id="bemail" name="bemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'input', 'bemail' ) ); ?>" size="30" value="<?php echo esc_attr($bemail); ?>" />
			</div> <!-- end pmpro_checkout-field-bemail -->

			<?php
				$pmpro_checkout_confirm_email = apply_filters("pmpro_checkout_confirm_email", true);
				if($pmpro_checkout_confirm_email) { ?>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_checkout-field-bconfirmemail', 'pmpro_checkout-field-bconfirmemail' ) ); ?>">
						<label for="bconfirmemail"><?php esc_html_e('Confirm Email Address', 'paid-memberships-pro' );?></label>
						<input id="bconfirmemail" name="bconfirmemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'input', 'bconfirmemail' ) ); ?>" size="30" value="<?php echo esc_attr($bconfirmemail); ?>" />
					</div> <!-- end pmpro_checkout-field-bconfirmemail -->
				<?php } else { ?>
					<input type="hidden" name="bconfirmemail_copy" value="1" />
				<?php }
			?>

			<?php
				do_action('pmpro_checkout_after_email');
			?>

			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_hidden' ) ); ?>">
				<label for="fullname"><?php esc_html_e('Full Name', 'paid-memberships-pro' );?></label>
				<input id="fullname" name="fullname" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'input', 'fullname' ) ); ?>" size="30" value="" autocomplete="off"/> <strong><?php esc_html_e('LEAVE THIS BLANK', 'paid-memberships-pro' );?></strong>
			</div> <!-- end pmpro_hidden -->

		</div>  <!-- end pmpro_checkout-fields -->
	</div> <!-- end pmpro_user_fields -->
	<?php } elseif($current_user->ID && !$pmpro_review) { ?>
		<div id="pmpro_account_loggedin" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_alert', 'pmpro_account_loggedin' ) ); ?>">
			<?php
				$allowed_html = array(
					'a' => array(
						'href' => array(),
						'title' => array(),
						'target' => array(),
					),
					'strong' => array(),
				);
				echo wp_kses( sprintf( __('You are logged in as <strong>%s</strong>. If you would like to use a different account for this membership, <a href="%s">log out now</a>.', 'paid-memberships-pro' ), $current_user->user_login, wp_logout_url( esc_url_raw( $_SERVER['REQUEST_URI'] ) ) ), $allowed_html );
			?>
		</div> <!-- end pmpro_account_loggedin -->
	<?php } ?>

	<?php
		do_action('pmpro_checkout_after_user_fields');
	?>

	<?php
		do_action('pmpro_checkout_boxes');
	?>

	<?php if(pmpro_getGateway() == "paypal" && empty($pmpro_review) && true == apply_filters('pmpro_include_payment_option_for_paypal', true ) ) { ?>
	<div id="pmpro_payment_method" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout', 'pmpro_payment_method' ) ); ?>" <?php if(!$pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
		<hr />
		<h2>
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-name' ) ); ?>"><?php esc_html_e('Choose your Payment Method', 'paid-memberships-pro' ); ?></span>
		</h2>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-fields' ) ); ?>">
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'gateway_paypal' ) ); ?>">
				<input type="radio" name="gateway" value="paypal" <?php if(!$gateway || $gateway == "paypal") { ?>checked="checked"<?php } ?> />
				<a href="javascript:void(0);" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_radio' ) ); ?>"><?php esc_html_e('Check Out with a Credit Card Here', 'paid-memberships-pro' );?></a>
			</span>
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'gateway_paypalexpress' ) ); ?>">
				<input type="radio" name="gateway" value="paypalexpress" <?php if($gateway == "paypalexpress") { ?>checked="checked"<?php } ?> />
				<a href="javascript:void(0);" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_radio' ) ); ?>"><?php esc_html_e('Check Out with PayPal', 'paid-memberships-pro' );?></a>
			</span>
		</div> <!-- end pmpro_checkout-fields -->
	</div> <!-- end pmpro_payment_method -->
	<?php } ?>
	<?php
		$pmpro_accepted_credit_cards = get_option( 'pmpro_accepted_credit_cards' );
		$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);
		$pmpro_accepted_credit_cards_string = pmpro_implodeToEnglish($pmpro_accepted_credit_cards);
	?>
	<?php if($tospage && !$pmpro_review) { ?>
		<div id="pmpro_tos_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout', 'pmpro_tos_fields' ) ); ?>">
			<hr />
			<h2>
				<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-h2-name' ) ); ?>"><?php echo esc_html( $tospage->post_title );?></span>
			</h2>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-fields' ) ); ?>">
				<div id="pmpro_license" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field', 'pmpro_license' ) ); ?>">
<?php 
	/**
	 * Hook to run formatting filters before displaying the content of your "Terms of Service" page at checkout.
	 *
	 * @since 2.4.1
	 * @since 2.10.1 We escape the content BEFORE the filter, so it can be overridden.
	 *
	 * @param string $pmpro_tos_content The content of the post assigned as the Terms of Service page.
	 * @param string $tospage The post assigned as the Terms of Service page.
	 *
	 * @return string $pmpro_tos_content
	 */
	$pmpro_tos_content = apply_filters( 'pmpro_tos_content', wp_kses_post( do_shortcode( $tospage->post_content ) ), $tospage );
	echo $pmpro_tos_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
				</div> <!-- end pmpro_license -->
				<?php
					if ( isset( $_REQUEST['tos'] ) ) {
						$tos = intval( $_REQUEST['tos'] );
					} else {
						$tos = "";
					}
				?>
				<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_label-inline pmpro_clickable', 'tos' ) ); ?>" for="tos">
		            <input type="checkbox" name="tos" value="1" id="tos" <?php checked( 1, $tos ); ?> />
		            <?php echo esc_html( sprintf( __( 'I agree to the %s', 'paid-memberships-pro' ), $tospage->post_title ) );?>
		        </label>
				<?php
				/**
				 * Allow adding text or more checkboxes after the Tos checkbox
                 * This is NOT intended to support multiple Tos checkboxes
				 *
				 * @since 2.8
				 */
				 do_action( "pmpro_checkout_after_tos" );
				 ?>
			</div> <!-- end pmpro_checkout-fields -->
		</div> <!-- end pmpro_tos_fields -->
		<?php
		}
	?>

	<?php do_action("pmpro_checkout_after_tos_fields"); ?>

	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_captcha', 'pmpro_captcha' ) ); ?>">
	<?php
		$recaptcha = get_option( "pmpro_recaptcha");
		if ( $recaptcha == 2 || $recaptcha == 1 ) {
			pmpro_recaptcha_get_html();
		}
	?>
	</div> <!-- end pmpro_captcha -->

	<?php
		do_action('pmpro_checkout_after_captcha');
		do_action("pmpro_checkout_before_submit_button");

		// Add nonce.
		wp_nonce_field( 'pmpro_checkout_nonce', 'pmpro_checkout_nonce' );
	?>

	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_submit' ) ); ?>">
		<hr />
		<?php if ( $pmpro_msg ) { ?>
			<div id="pmpro_message_bottom" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( apply_filters( 'pmpro_checkout_message', $pmpro_msg, $pmpro_msgt ) ); ?></div>
		<?php } else { ?>
			<div id="pmpro_message_bottom" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
		<?php } ?>

		<?php if($pmpro_review) { ?>

			<span id="pmpro_submit_span">
				<input type="hidden" name="confirm" value="1" />
				<input type="hidden" name="token" value="<?php echo esc_attr($pmpro_paypal_token); ?>" />
				<input type="hidden" name="gateway" value="<?php echo esc_attr($gateway); ?>" />
				<input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php esc_attr_e('Complete Payment', 'paid-memberships-pro' );?>" />
			</span>

		<?php } else { ?>

			<?php
				$pmpro_checkout_default_submit_button = apply_filters('pmpro_checkout_default_submit_button', true);
				if($pmpro_checkout_default_submit_button)
				{
				?>
				<span id="pmpro_submit_span">
					<input type="hidden" name="submit-checkout" value="1" />
					<input type="submit"  id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class(  'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ) ); ?>" value="<?php if($pmpro_requirebilling) { esc_html_e('Submit and Check Out', 'paid-memberships-pro' ); } else { esc_html_e('Submit and Confirm', 'paid-memberships-pro' );}?>" />
				</span>
				<?php
				}
			?>

		<?php } ?>

		<span id="pmpro_processing_message" style="visibility: hidden;">
			<?php
				$processing_message = apply_filters("pmpro_processing_message", __("Processing...", 'paid-memberships-pro' ));
				echo wp_kses_post( $processing_message );
			?>
		</span>
	</div>
</form>

<?php do_action('pmpro_checkout_after_form'); ?>
</div> <!-- end pmpro_level-ID -->
