<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) && IMAGIFY_HIDDEN_ACCOUNT ) {
	if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) {
		$options = Imagify_Options::get_instance();
		?>
		<input type="hidden" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="<?php echo $options->get_option_name(); ?>[api_key]">
		<?php
	}
	return;
}

if ( imagify_valid_key() ) {
	$user             = imagify_get_cached_user();
	$unconsumed_quota = $user ? $user->get_percent_unconsumed_quota : false;
	$hidden_class     = '';

	if ( ! $user ) {
		// Lazyload user.
		Imagify_Assets::get_instance()->localize_script( 'options', 'imagifyUser', array(
			'action'   => 'imagify_get_user_data',
			'_wpnonce' => wp_create_nonce( 'imagify_get_user_data' ),
		) );
	}
} else {
	$hidden_class = ' hidden';
}
?>
<div class="imagify-settings-section">

	<?php if ( imagify_valid_key() ) {
		?>
		<div class="imagify-col-content imagify-block-secondary imagify-mt2">
			<?php
			/**
			 * Remaining quota.
			 */
			if ( ! $user || ( $unconsumed_quota <= 20 && $unconsumed_quota > 0 ) ) {
				if ( ! $user ) {
					echo '<div class="imagify-user-is-almost-over-quota hidden">';
				}
				?>
				<p><strong><?php esc_html_e( 'Oops, It\'s almost over!', 'imagify' ); ?></strong></p>
				<p><?php esc_html_e( 'You have almost used all your credit. Don\'t forget to upgrade your subscription to continue optimizing your images.', 'imagify' ); ?></p>
				<p><a class="button imagify-button-ghost" href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php esc_html_e( 'View My Subscription', 'imagify' ); ?></a></p>
				<?php
				if ( ! $user ) {
					echo '</div>';
				}
			}

			if ( ! $user || 0 === $unconsumed_quota ) {
				if ( ! $user ) {
					echo '<div class="imagify-user-is-over-quota hidden">';
				}
				?>
				<p><strong><?php esc_html_e( 'Oops, It\'s Over!', 'imagify' ); ?></strong></p>
				<p>
					<?php
					printf(
						/* translators: 1 is a "bold" tag openning, 2 is a data quota, 3 is a date, 4 is the "bold" tag closing. */
						esc_html__( 'You have consumed all your credit for this month. You will have %1$s%2$s back on %3$s%4$s.', 'imagify' ),
						'<strong>',
						'<span class="imagify-user-quota-formatted">' . ( $user ? esc_html( $user->quota_formatted ) : '' ) . '</span>',
						'<span class="imagify-user-next-date-update-formatted">' . ( $user ? esc_html( $user->next_date_update_formatted ) : '' ) . '</span>',
						'</strong>'
					);
					?>
				</p>
				<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade My Subscription', 'imagify' ); ?></a></p>
				<?php
				if ( ! $user ) {
					echo '</div>';
				}
			}

			/**
			 * Best plan.
			 */
			?>
			<div class="best-plan<?php echo $hidden_class; ?>">
				<h3><?php esc_html_e( 'You’re new to Imagify', 'imagify' ); ?></h3>

				<p><?php esc_html_e( 'Let us help you by analyzing your existing images and determine the best plan for you.', 'imagify' ); ?></p>

				<button id="imagify-get-pricing-modal" data-nonce="<?php echo wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ); ?>" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-button imagify-button-light imagify-full-width">
					<i class="dashicons dashicons-dashboard" aria-hidden="true"></i>
					<span class="button-text"><?php esc_html_e( 'What plan do I need?', 'imagify' ); ?></span>
				</button>
			</div>
		</div><!-- .imagify-col-content -->
		<?php
	}
	?>

	<?php if ( imagify_valid_key() ) { ?>
		<h2 class="imagify-options-title">
			<?php esc_html_e( 'Account Type', 'imagify' ); ?>
			<strong class="imagify-user-plan-label"><?php echo $user ? esc_html( $user->plan_label ) : ''; ?></strong>
		</h2>
	<?php } else { ?>
		<h2 class="imagify-options-title"><?php esc_html_e( 'Your Account', 'imagify' ); ?></h2>
		<p class="imagify-options-subtitle"><?php esc_html_e( 'Options page isn’t available until you enter your API Key', 'imagify' ); ?></p>
	<?php } ?>

	<?php
	if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) {
		/**
		 * API key field.
		 */
		$options = Imagify_Options::get_instance();

		if ( ! $options->get( 'api_key' ) ) { ?>
			<p class="imagify-api-key-invite"><?php esc_html_e( 'Don\'t have an API Key yet?', 'imagify' );?></p>
			<?php //<p class="imagify-api-key-invite-title"><?php esc_html_e( 'Create one, it\'s FREE.', 'imagify' ); </p> ?>
			
			<p><a id="imagify-signup" class="button imagify-button-secondary" href="<?php echo esc_url( imagify_get_external_url( 'register' ) ); ?>" target="_blank"><?php esc_html_e( 'Create a Free API Key', 'imagify' ); ?></a></p>
		<?php }	?>

		<div class="imagify-api-line">
			<label for="api_key"><?php echo $options->get('api_key') ? esc_html__( 'API Key', 'imagify' ) : esc_html__( 'Enter Your API Key Below', 'imagify' ); ?></label>
			<input type="text" size="35" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="<?php echo $options->get_option_name(); ?>[api_key]" id="api_key">
			<?php
			if ( imagify_valid_key() ) {
				?>

				<span id="imagify-check-api-container" class="imagify-valid">
					<span class="imagify-icon">✓</span> <?php esc_html_e( 'Your API key is valid.', 'imagify' ); ?>
				</span>

				<?php
			} elseif ( ! imagify_valid_key() && $options->get( 'api_key' ) ) {
				?>

				<span id="imagify-check-api-container">
					<span class="dashicons dashicons-no"></span> <?php esc_html_e( 'Your API key isn\'t valid!', 'imagify' ); ?>
				</span>

				<?php
			}
			?>
			<input id="check_api_key" type="hidden" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="check_api_key">
		</div><!-- .imagify-api-line -->
	<?php } ?>
</div>
<?php
