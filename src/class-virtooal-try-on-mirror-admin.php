<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'interface-virtooal-try-on-mirror.php' );
require_once( 'class-virtooal-try-on-mirror.php' );
require_once( 'class-virtooal-try-on-mirror-api.php' );
/*
* Virtooal_Try_On_Mirror_Admin class for setting virtooal settings.
*/
class Virtooal_Try_On_Mirror_Admin extends Virtooal_Try_On_Mirror implements Virtooal_Try_On_Mirror_Interface {

	//Set up base actions
	public function init() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		add_action( 'admin_post_virtooal_api_login_response', array( $this, 'api_login_response' ) );
		add_action( 'admin_post_virtooal_api_logout_response', array( $this, 'api_logout_response' ) );
		add_action( 'admin_post_virtooal_settings_response', array( $this, 'settings_response' ) );

	}

	//Add plugin to woocommerce admin menu.
	public function admin_menu() {
		add_submenu_page( 'woocommerce', 'Virtooal', 'Virtooal', 'manage_options', 'virtooal', array( $this, 'admin_pages' ) );
	}

	#region META BOX
	/**
	 * Adds the meta box container.
	 */
	public function add_meta_box( $post_type ) {

		if ( ! $this->api ) {
			return;
		}
		// Limit meta box to certain post types.
		$post_types = array( 'product' );

		if ( in_array( $post_type, $post_types ) ) {
			add_meta_box(
				'virtooal_product_meta_box_name',
				__( 'Virtooal', 'textdomain' ),
				array( $this, 'render_meta_box_content' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {

		if ( ! $this->api ) {
			return;
		}
		global $woocommerce;
		$product_id = get_the_ID();
		$product    = wc_get_product( $product_id );
		$query_data = array(
			'url'        => get_permalink( $product_id ),
			'title'      => $product->get_name(),
			'product_id' => $product_id,
			'iframe'     => 1,
			'platform'   => 6,
		);
		$image      = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' );
		if ( $image ) {
			$query_data['img'] = $image[0];
		}
		$virtooal_api   = new Virtooal_Try_On_Mirror_Api();
		$response       = $virtooal_api->get_product( $product_id );
		$in_virtooal_db = false;
		$published      = null;
		if ( 200 === $response['http_code'] ) {
			$in_virtooal_db = true;
			$published      = $response['body']['product']['published'];
		}

		$this->render(
			'admin/product-meta-box.php',
			array(
				'in_virtooal_db' => $in_virtooal_db,
				'published'      => $published,
				'membership'     => $this->get_user_membership(),
				'query_data'     => http_build_query( $query_data ),
			)
		);
	}
	#endregion META BOX

	//Output form with settings.
	public function admin_pages() {
		$tab       = ( ! empty( $_GET['tab'] ) ) ? esc_attr( $_GET['tab'] ) : 'settings';
		$title     = __( 'Virtooal Settings', 'virtooal-try-on-mirror' );
		$site_url  = home_url();
		$admin_url = admin_url();

		$this->render(
			'admin/settings-content.php',
			array(
				'api_logged_in' => ! ! $this->api['access_token'],
				'tab'           => $tab,
				'title'         => $title,
				'site_url'      => $site_url,
				'admin_url'     => $admin_url,
				'membership'    => $this->api['access_token'] ? $this->get_user_membership() : 0,
				'product_page_positions' => $this->get_product_page_positions(),
				'catalog_page_positions' => $this->get_catalog_page_positions(),
				'data'          => get_option( 'virtooal_' . $tab ),
			)
		);
	}
	#region AUTH
	public function api_login_response() {
		$this->verify_nonce( 'api_login' );
		$error_message   = null;
		$public_api_key  = sanitize_text_field( $_POST['virtooal']['public_api_key'] );
		$private_api_key = sanitize_text_field( $_POST['virtooal']['private_api_key'] );
		$virtooal_api    = new Virtooal_Try_On_Mirror_Api();
		$response        = $virtooal_api->login( $public_api_key, $private_api_key );

		if ( 200 === $response['http_code'] ) {
			update_option(
				'virtooal_api',
				array(
					'public_api_key' => $public_api_key,
					'access_token'   => $response['body']['access_token'],
					'refresh_token'  => $response['body']['refresh_token'],
					'user_id'        => $response['body']['user_id'],
				)
			);
			$response = $virtooal_api->get_settings();
			if ( ! $response['success'] ) {
				$error_message = $response['message'];
			}

			global $wp_version;
			$installation_id = get_option( 'virtooal_installation_id' );
			$virtooal_api->post_user(
				array(
					'plugin_version'    => VIRTOOAL_TRY_ON_MIRROR_VERSION,
					'domain'            => get_site_url(),
					'wordpress_version' => $wp_version,
					'updated_at'        => gmdate( 'Y-m-d H:i:s' ),
					'partner_id'        => $response['body']['user_id'],
					'premium'           => 0,
					'id'                => $installation_id,
				)
			);
		} else {
			if ( isset( $response['body']['message'] ) ) {
				$error_message = $response['body']['message'];
			} elseif ( isset( $response['body']['private_api_key'] ) ) {
				$error_message = $response['body']['private_api_key'];
			}
		}
		$this->custom_redirect( 'api', $error_message );
	}

	public function api_logout_response() {
		$this->verify_nonce( 'api_logout' );
		$error_message = null;
		$virtooal_api  = new Virtooal_Try_On_Mirror_Api();
		$response      = $virtooal_api->logout();

		delete_option( 'virtooal_api' );
		if ( 200 === $response['http_code'] ) {
			delete_option( 'virtooal_api' );
		} else {
			if ( isset( $response['body']['message'] ) ) {
				$error_message = $response['body']['message'];
			}
		}
		$this->custom_redirect( 'api', $error_message );
	}
	#endregion AUTH

	#region SETTINGS
	public function settings_response() {
		$this->verify_nonce( 'settings' );
		$virtooal_settings                                = get_option( 'virtooal_settings' );
		$virtooal_settings['tryon_text']                  = sanitize_text_field( $_POST['virtooal_settings']['tryon_text'] );
		$virtooal_settings['only_wc_pages']               = isset( $_POST['virtooal_settings']['only_wc_pages'] ) ? 1 : 0;
		$virtooal_settings['tryon_position_product_page'] = $_POST['virtooal_settings']['tryon_position_product_page'];
		$virtooal_settings['tryon_show_catalog_page']     = isset( $_POST['virtooal_settings']['tryon_show_catalog_page'] ) ? 1 : 0;
		$virtooal_settings['tryon_show_product_page']     = isset( $_POST['virtooal_settings']['tryon_show_product_page'] ) ? 1 : 0;

		update_option( 'virtooal_settings', $virtooal_settings );
		$virtooal_api = new Virtooal_Try_On_Mirror_Api();

		$data = array(
			'tryon_text' => $_POST['virtooal_settings']['tryon_text'],
		);
		$response = $virtooal_api->post_settings( $data );
		$error_message = null;
		if ( isset( $response['body']['message'] ) && 200 !== $response['http_code'] ) {
			$error_message = $response['body']['message'];
		}
		$this->custom_redirect( 'settings', $error_message );
	}
	#endregion SETTINGS

	private function custom_redirect( $tab, $message = null ) {
		wp_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'response' => $message ? $message : 'success',
					),
					admin_url( 'admin.php?page=virtooal&tab=' . $tab )
				)
			)
		);
	}

	private function verify_nonce( $name ) {
		if ( ! isset( $_POST[ 'virtooal_' . $name . '_nonce' ] ) ||
			! wp_verify_nonce(
				$_POST[ 'virtooal_' . $name . '_nonce' ],
				'virtooal_' . $name . '_form_nonce'
			) ) {
			die( 'nonce' );
		}
	}

	private function get_user_membership() {
		$virtooal_api = new Virtooal_Try_On_Mirror_Api();
		$response     = $virtooal_api->get_user();
		$membership   = 0;
		if (  200 === $response['http_code'] ) {
			$membership = (int) $response['body']['membership'];
		}
		return $membership;
	}

	private function get_catalog_page_positions() {
		return array(
			'before_shop_loop_item'       => 'Before Shop Loop Item',
			'before_shop_loop_item_title' => 'Before Shop Loop Item Title',
			'shop_loop_item_title'        => 'Shop Loop Item Title',
			'after_shop_loop_item_title'  => 'After Shop Loop Item Title',
			'after_shop_loop_item'        => 'After Shop Loop Item',
		);
	}

	private function get_product_page_positions() {
		return array(
			'before_add_to_cart_form'   => 'Before Add To Cart Form',
			'before_variations_form'    => 'Before Variations Form',
			'before_add_to_cart_button' => 'Before Add To Cart Button',
			'before_single_variation'   => 'Before Single Variation',
			'after_single_variation'    => 'After Single Variation',
			'after_add_to_cart_button'  => 'After Add To Cart Button',
			'after_variations_form'     => 'After Variations Form',
			'after_add_to_cart_form'    => 'After Add To Cart Form',
			'product_meta_start'        => 'Product Meta Start',
			'product_meta_end'          => 'Product Meta End',
			'product_thumbnails'        => 'Product Thumbnails',
		);
	}
}
