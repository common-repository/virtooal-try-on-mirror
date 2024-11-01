<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active = 'nav-tab-active';
?>

<nav class="nav-tab-wrapper wp-clearfix" aria-label="Secondary menu">
	<a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=virtooal&tab=settings" class="nav-tab <?php echo 'settings' === $tab ? $active : ''; ?>">
		<?php _e( 'Settings', 'virtooal-try-on-mirror' ); ?>
	</a>
	<a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=virtooal&tab=product_feed" class="nav-tab <?php echo 'product_feed' === $tab ? $active : ''; ?>">
		<?php _e( 'XML Product Feed', 'virtooal-try-on-mirror' ); ?>
	</a>
	<a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=virtooal&tab=api" class="nav-tab <?php echo 'api' === $tab ? $active : ''; ?>">
		<?php _e( 'Auglio API Connection', 'virtooal-try-on-mirror' ); ?>
	</a>
</nav>

