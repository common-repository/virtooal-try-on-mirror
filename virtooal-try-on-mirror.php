<?php
/**
 * Plugin Name: Auglio Try-on Mirror
 * Plugin URI: http://wordpress.org/plugins/virtooal-try-on-mirror/
 * Description: This plugin allows to quickly install Auglio Try-on Mirror on any WooCommerce website.
 * Version: 1.3.0
 * WC requires at least: 3.0.0
 * WC tested up to: 7.8.0
 * Author: Auglio
 * Author URI: https://auglio.com
 * Text Domain: virtooal-try-on-mirror
 * Copyright: © 2019-2023 Auglio.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'VIRTOOAL_TRY_ON_MIRROR_VERSION' ) ) {
	define( 'VIRTOOAL_TRY_ON_MIRROR_VERSION', '1.3.0' );
}
if ( ! defined( 'VIRTOOAL_BASE_PATH' ) ) {
	define( 'VIRTOOAL_BASE_PATH', plugin_dir_path( __FILE__ ) );
}
function virtooal_try_on_mirror_activation() {

	update_option( 'virtooal_try_on_mirror_version', VIRTOOAL_TRY_ON_MIRROR_VERSION );

	$installation_id = get_option( 'virtooal_installation_id' );
	if ( ! $installation_id ) {
		$installation_id = wp_generate_uuid4();
		update_option( 'virtooal_installation_id', $installation_id );
	}

	$default_virtooal_settings = array(
		'automirror'                  => 0,
		'only_wc_pages'               => 0,
		'tryon_text'                  => '',
		'tryon_show_catalog_page'     => 1,
		'tryon_position_catalog_page' => 'after_shop_loop_item',
		'tryon_show_product_page'     => 1,
		'tryon_position_product_page' => 'after_add_to_cart_button',
	);
	$virtooal_settings         = get_option( 'virtooal_settings', array() );
	update_option( 'virtooal_settings', array_merge( $default_virtooal_settings, $virtooal_settings ) );

	$default_virtooal_api = array(
		'public_api_key' => '',
		'access_token'   => '',
		'refresh_token'  => '',
		'user_id'        => '',
	);
	$virtooal_api         = get_option( 'virtooal_api', array() );
	update_option( 'virtooal_api', array_merge( $default_virtooal_api, $virtooal_api ) );

	global $wp_version;
	$virtooal_api = get_option( 'virtooal_api', array() );
	require_once( dirname( __FILE__ ) . '/src/class-virtooal-try-on-mirror-api.php' );
	$api = new Virtooal_Try_On_Mirror_Api();
	$api->post_user(
		array(
			'plugin_version'    => VIRTOOAL_TRY_ON_MIRROR_VERSION,
			'domain'            => get_site_url(),
			'wordpress_version' => $wp_version,
			'updated_at'        => gmdate( 'Y-m-d H:i:s' ),
			'partner_id'        => $virtooal_api['user_id'],
			'premium'           => 0,
			'id'                => $installation_id,
		)
	);
}

register_activation_hook( __FILE__, 'virtooal_try_on_mirror_activation' );

function virtooal_check_version() {
	if ( VIRTOOAL_TRY_ON_MIRROR_VERSION !== get_option( 'virtooal_try_on_mirror_version' ) ) {
		virtooal_try_on_mirror_activation();
	}
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	// Check if there is admin user
	if ( is_admin() ) {
		require_once( dirname( __FILE__ ) . '/src/class-virtooal-try-on-mirror-admin.php' );
		$virtooal_try_on_mirror = new Virtooal_Try_On_Mirror_Admin();
	} else {
		require_once( dirname( __FILE__ ) . '/src/class-virtooal-try-on-mirror.php' );
		$virtooal_try_on_mirror = new Virtooal_Try_On_Mirror();
	}

	$virtooal_try_on_mirror->init();
} else {
	//wp_die('Sorry, but this plugin requires the Parent Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
}

add_action( 'admin_init', 'virtooal_has_woocommerce' );
function virtooal_has_woocommerce() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		add_action( 'admin_notices', 'virtooal_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

add_action( 'init', 'virtooal_update_settings' );
function virtooal_update_settings() {
	if ( isset( $_GET['virtooal_settings_update'] ) ) {
		require_once( dirname( __FILE__ ) . '/src/class-virtooal-try-on-mirror-api.php' );
		$virtooal_api = new Virtooal_Try_On_Mirror_Api();
		$virtooal_api->get_settings();
	}
}


function virtooal_notice() {
	echo '<div class="error"><p>' . __(
		'Sorry, but Virtooal Try-on Mirror requires WooCommerce version 3.0.0 or above to be installed and active.',
		'virtooal-try-on-mirror'
	) . '</p></div>';
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'virtooal_add_action_links' );
function virtooal_add_action_links( $links ) {
	$mylinks = array(
		'<a href="' . admin_url( 'admin.php?page=virtooal' ) . '">Settings</a>',
	);

	return array_merge( $mylinks, $links );
}

// add plugin upgrade notification
add_action( 'in_plugin_update_message-virtooal-try-on-mirror/virtooal-try-on-mirror.php', 'virtooal_show_upgrade_notification', 10, 2 );
function virtooal_show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata ) {
	// check "upgrade_notice"
	if ( isset( $new_plugin_metadata->upgrade_notice ) && strlen( trim( $new_plugin_metadata->upgrade_notice ) ) > 0 ) {
		echo '<div style="background-color: rgba(255, 185, 0, .5); padding: 10px; margin-top: 10px"><strong>'
		. __( 'Important Upgrade Notice', 'virtooal-try-on-mirror' ) . ':</strong>';
		echo esc_html( $new_plugin_metadata->upgrade_notice ), '</div>';
	}
}
