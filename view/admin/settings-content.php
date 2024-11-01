<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 clas="wp-heading-inline"><?php echo $title; ?></h1>
<?php
if ( ! $api_logged_in ) {
	echo '
    <div class="notice is-dismissible notice-warning">
        <p>' .
	sprintf(
		/* Translators: %2$s: auglio.com */
		__(
			'Signup for a free Agulio account at 
            <a href="%1$s" target="_blank">%2$s</a>, and 
            copy and paste Public and Private API keys from the
            <a href="%3$s" target="_blank">profile page</a> 
            into the API Connection form.',
			'virtooal-try-on-mirror'
		),
		esc_url( 'https://auglio.com/en/pricing' ),
		'auglio.com',
		esc_url( 'https://dashboard.auglio.com/user/profile' )
	) . '
        </p>
    </div>';
}
if ( isset( $_GET['response'] ) ) {
	if ( esc_attr( $_GET['response'] ) === 'success' ) {
		echo '
        <div class="notice is-dismissible notice-success">
            <p>' . __( 'Saved successfully!', 'virtooal-try-on-mirror' ) . '</p>
        </div>';
	} else {
		echo '
        <div class="notice is-dismissible notice-error">
            <p>' . $resp . '</p>
        </div>';
	}
}
include dirname( __FILE__ ) . '/settings-nav.php';

if ( 'settings' === $tab ) :
	?>
	<h2><?php _e( 'General Settings', 'virtooal-try-on-mirror' ); ?></h2>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="virtooal_settings_form" >
		<input type="hidden" name="action" value="virtooal_settings_response">
		<input type="hidden" name="virtooal_settings_nonce" value="<?php echo wp_create_nonce( 'virtooal_settings_form_nonce' ); ?>"/>
		<table class="form-table" aria-label="General Settings">
			<tbody>
				<tr>
					<th scope="row">
						<?php _e( 'Virtual Mirror', 'virtooal-try-on-mirror' ); ?>
					</th>
					<td>
						<fieldset>
							<label for="virtooal-only_wc_pages">
								<input type="checkbox" <?php checked( $data['only_wc_pages'] == 1 ); ?> name="virtooal_settings[only_wc_pages]" id="virtooal-only_wc_pages" value="1">
								<?php _e( 'Show Virtual Mirror only on WooCommerce pages', 'virtooal-try-on-mirror' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php _e( '"Try On" button visibility', 'virtooal-try-on-mirror' ); ?>
					</th>
					<td>
						<fieldset>
							<label for="virtooal-tryon_show_catalog_page">
								<input type="checkbox" <?php checked( $data['tryon_show_catalog_page'] == 1 ); ?> name="virtooal_settings[tryon_show_catalog_page]" id="virtooal-tryon_show_catalog_page" value="1">
								<?php _e( 'Show "Try On" buttons on catalog page', 'virtooal-try-on-mirror' ); ?>
							</label>
						</fieldset>
						<fieldset>
							<label for="virtooal-tryon_show_product_page">
								<input type="checkbox" <?php checked( $data['tryon_show_product_page'] == 1 ); ?> name="virtooal_settings[tryon_show_product_page]" id="virtooal-tryon_show_product_page" value="1">
								<?php _e( 'Show "Try On" button on product page', 'virtooal-try-on-mirror' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="virtooal-tryon_position_product_page">
							<?php _e( '"Try On" button position on product page' ); ?>
						</label>
					</th>
					<td>
						<select name="virtooal_settings[tryon_position_product_page]" id="virtooal-tryon_position_product_page">
							<?php foreach ( $product_page_positions as $position_id => $position_name ) : ?>
							<option value="<?php echo $position_id; ?>" <?php selected( $data['tryon_position_product_page'] == $position_id ); ?>>
								<?php echo $position_name; ?>
							</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="virtooal-tryon_position_catalog_page">
						<?php _e( '"Try On" button position on catalog page' ); ?>
					</label>
					</th>
					<td>
						<select name="virtooal_settings[tryon_position_catalog_page]" id="virtooal-tryon_position_catalog_page">
							<?php foreach ( $catalog_page_positions as $position_id => $position_name ) : ?>
							<option <?php selected( $data['tryon_position_catalog_page'] == $position_id ); ?> value="<?php echo $position_id; ?>">
								<?php echo $position_name; ?>
							</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="virtooal-tryon_text">
							<?php _e( 'Try On button text', 'virtooal-try-on-mirror' ); ?>
						</label>
					</th>
					<td>
						<input type="text" name="virtooal_settings[tryon_text]" id="virtooal-tryon_text" class="regular-text" value="<?php echo $data['tryon_text']; ?>">
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>

<?php elseif ( 'product_feed' === $tab ) : ?>
	<h2><?php _e( 'Product Feed Settings', 'virtooal-try-on-mirror' ); ?></h2>
	<p class="description">
	<?php
	if ( $membership > 0 && 2 !== $membership ) {
		echo sprintf(
			/* translators: %s: url to Auglio Client Dashboard */
			__( 'Please update your Auglio plugin to the Premium version to enable automatic product synchronization. You can download the Premium version from the <a href="%s" target="_blank">Auglio Client Dashboard</a>', 'virtooal-try-on-mirror' ),
			esc_url( 'https://dashboard.auglio.com/platforms' )
		);
	} else {
		echo sprintf(
			/* translators:
			%1$s: url to Auglio Client Dashboard
			%2$s: url to Auglio plugin's settings page */
			__( '<a href="%1$s" target="_blank">Upgrade to Premium</a> to enable automatic product synchronization via <a href="%2$s" target="_blank">XML Feed</a>', 'virtooal-try-on-mirror' ),
			esc_url( 'https://dashboard.auglio.com/payment/plans' ),
			esc_url( site_url() . '/wp-admin/admin.php?page=virtooal&tab=product_feed' )
		);
	}
	?>
	</p>

<?php elseif ( 'api' === $tab ) : ?>
	<h2><?php _e( 'Auglio API Connection', 'virtooal-try-on-mirror' ); ?></h2>
	<?php if ( $data['refresh_token'] ) : ?>

	<p>
		<?php
		echo sprintf(
			/* translators: %s Auglio public API key */
			__( 'You are connected to Auglio API with API key: <b>%s</b>', 'virtooal-try-on-mirror' ),
			$data['public_api_key']
		);
		?>
	</p>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="virtooal_api_logout_form" >
		<input type="hidden" name="action" value="virtooal_api_logout_response">
		<input type="hidden" name="virtooal_api_logout_nonce" value="<?php echo wp_create_nonce( 'virtooal_api_logout_form_nonce' ); ?>"/>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Disconnect', 'virtooal-try-on-mirror' ); ?>">
		</p>
	</form>

	<?php else : ?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="virtooal_api_login_form" >
		<input type="hidden" name="action" value="virtooal_api_login_response">
		<input type="hidden" name="virtooal_api_login_nonce" value="<?php echo wp_create_nonce( 'virtooal_api_login_form_nonce' ); ?>"/>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="virtooal-public_api_key">
							<?php _e( 'API Key', 'virtooal-try-on-mirror' ); ?>
							<span class="woocommerce-help-tip"></span>
						</label>
					</th>
					<td>
						<input required name="virtooal[public_api_key]" id="virtooal-public_api_key" class="regular-text" type="text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="virtooal-private_api_key">
							<?php _e( 'Private API Key', 'virtooal-try-on-mirror' ); ?>
							<span class="woocommerce-help-tip"></span>
						</label>
					</th>
					<td>
						<input required name="virtooal[private_api_key]" id="virtooal-private_api_key" class="regular-text" type="password">
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Connect', 'virtooal-try-on-mirror' ); ?>"></p>
	</form>
	<?php endif; ?>
<?php endif; ?>

</div>
