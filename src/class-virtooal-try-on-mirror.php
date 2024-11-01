<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'interface-virtooal-try-on-mirror.php' );

/*
* Virtooal_Try_On_Mirror class
*/
class Virtooal_Try_On_Mirror implements Virtooal_Try_On_Mirror_Interface {

	private $is_div_open = false;

	private $plugin_version = VIRTOOAL_TRY_ON_MIRROR_VERSION;

	protected $api;
	protected $settings;

	public function __construct() {
		$this->api      = get_option( 'virtooal_api' );
		$this->settings = get_option( 'virtooal_settings' );
		if ( ! isset( $this->settings['automirror'] ) ) {
			$this->settings['automirror'] = 0;
		}
	}

	//Set up base actions
	public function init() {
		if ( ! $this->settings['automirror'] ) {
			//small mirror
			add_action( 'woocommerce_after_single_product_summary', array( $this, 'show_small_mirror' ), 5 );
			// try button
			if ( 1 === $this->settings['tryon_show_product_page'] ) {
				$action = 'woocommerce_' . $this->settings['tryon_position_product_page'];
				add_action( $action, array( $this, 'show_try_button_single' ), 20 );
			}
			if ( 1 === $this->settings['tryon_show_catalog_page'] ) {
				$action = 'woocommerce_' . $this->settings['tryon_position_catalog_page'];
				add_action( $action, array( $this, 'show_try_button_loop' ), 20 );
			}
		}
		// load the settings
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts_styles' ) );
	}

	public function load_scripts_styles() {
		if ( ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
			|| ( ! $this->settings['only_wc_pages'] && is_front_page() ) )
			&& $this->api ) {
				wp_enqueue_script(
					'virtooal-automirror',
					'//m.virtooal.com/' . $this->api['public_api_key'],
					array(),
					$this->plugin_version,
					true
				);

			if ( is_product() ) {
				wp_enqueue_script(
					'virtooal-widget',
					'//widget.virtooal.com/' . $this->api['public_api_key'] . '/' . $this->api['public_api_key'] . '/en/' . get_the_ID(),
					array(),
					$this->plugin_version,
					true
				);
			}
		}
	}

	public function show_small_mirror() {
		global $product;
		$product_id = $product->get_id();
		if ( $this->api && $product_id ) {
			$this->render( 'front/small-mirror.php' );
		}
	}

	public function show_try_button_single() {
		$this->show_try_button( 'try-button-single' );
	}

	public function show_try_button_loop() {
		$this->show_try_button( 'try-button-loop' );
	}

	private function show_try_button( $view ) {
		global $product;
		$this->render(
			'front/' . $view . '.php',
			array(
				'product_id' => $product->get_id(),
				'tryon_text' => $this->settings['tryon_text'] ? $this->settings['tryon_text'] : 'TRY ON',
			)
		);
	}

	//render template
	public function render( $template_name, array $parameters = array(), $render_output = true ) {
		foreach ( $parameters as $name => $value ) {
			${$name} = $value;
		}
		ob_start();
		include VIRTOOAL_BASE_PATH . '/view/' . $template_name;
		$output = ob_get_contents();
		ob_end_clean();

		if ( $render_output ) {
			echo $output;
		} else {
			return $output;
		}
	}
}
