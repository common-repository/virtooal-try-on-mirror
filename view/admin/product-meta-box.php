<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$status = 'n/a';
if ( $in_virtooal_db ) {
	if ( $published ) {
		$status = __( 'Published', 'virtooal-try-on-mirror' );
	} else {
		$status = __( 'Unpublished', 'virtooal-try-on-mirror' );
	}
}
?>			
<p>
	<span class="dashicons dashicons-post-status"></span>
	<?php _e( 'Virtooal Status', 'virtooal-try-on-mirror' ); ?>: <strong><?php echo $status; ?></strong>
</p>
<p>
	<a href="//setup.virtooal.com/en/auth/index?<?php echo $query_data; ?>" class="button" target="_blank">
		<?php
		if ( $in_virtooal_db ) {
			_e( 'Edit in Virtual Mirror', 'virtooal-try-on-mirror' );
		} else {
			_e( 'Add to Virtual Mirror', 'virtooal-try-on-mirror' );
		}
		?>
	</a>
</p>

<p class="description">
	<?php
	if ( $membership > 0 && 2 !== $membership ) {
		echo sprintf(
			/* translators: %s: url to Virtooal Client Dashboard */
			__( 'Please update your Virtooal plugin to the Premium version to enable automatic product synchronization. You can download the Premium version from the <a href="%s" target="_blank">Virtooal Client Dashboard</a>', 'virtooal-try-on-mirror' ),
			esc_url( 'https://setup.virtooal.com/en/platforms/woocommerce' )
		);
	} else {
		echo sprintf(
			/* translators:
			%1$s: url to Virtooal Client Dashboard
			%2$s: url to Virtooal plugin's settings page */
			__( '<a href="%1$s" target="_blank">Upgrade to Premium</a> to enable automatic product synchronization via <a href="%2$s" target="_blank">XML Feed</a>', 'virtooal-try-on-mirror' ),
			esc_url( 'https://setup.virtooal.com/en/billing/index' ),
			esc_url( site_url() . '/wp-admin/admin.php?page=virtooal&tab=product_feed' )
		);
	}
	?>
</p>
