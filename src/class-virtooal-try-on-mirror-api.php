<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
* Virtooal_Try_On_Mirror_Api class for Virtooal Api handling.
*/
class Virtooal_Try_On_Mirror_Api {


	private $api_base_url = 'https://api2.virtooal.com/';

	public function __construct() {}

	public function login( $public_api_key, $private_api_key ) {
		$args = array(
			'headers' => array(
				'Content-type: application/x-www-form-urlencoded',
			),
			'body'    => array(
				'public_api_key'  => $public_api_key,
				'private_api_key' => $private_api_key,
			),
		);
		return $this->http_post( 'auth/login', $args );
	}

	public function logout() {
		$args = $this->get_headers();
		return $this->http_get( 'auth/logout', $args );
	}

	public function get_settings() {
		$args     = $this->get_headers();
		$response = $this->http_get( 'settings', $args );

		if ( 200 === $response['http_code'] ) {
			$settings                        = $response['body']['settings'];
			$virtooal_settings               = get_option( 'virtooal_settings' );
			$virtooal_settings['tryon_text'] = $settings['tryon_text'];
			$virtooal_settings['automirror'] = $settings['automirror'];
			update_option( 'virtooal_settings', $virtooal_settings );
		} else {

			if ( isset( $response['body']['message'] ) ) {
				$error_message = $response['body']['message'];
			} else {
				$error_message = 'Virtooal API - Unknown Error';
			}
			return array(
				'success' => false,
				'message' => $error_message,
			);
		}
	}

	public function post_settings( $data ) {
		$args         = $this->get_headers();
		$args['body'] = $data;
		return $this->http_post( 'settings', $args );
	}

	public function get_product( $id ) {
		$args = $this->get_headers();
		return $this->http_get( 'products/' . $id, $args );
	}

	public function post_product( $id, $data ) {
		$args         = $this->get_headers();
		$args['body'] = $data;
		return $this->http_post( 'products/' . $id, $args );
	}

	public function get_user() {
		$args = $this->get_headers();
		return $this->http_get( 'user', $args );
	}

	public function post_user( $data ) {
		$args         = $this->get_headers();
		$args['body'] = $data;
		return $this->http_post( 'user', $args );
	}

	private function get_access_token() {
		$virtooal_api = get_option( 'virtooal_api' );
		$access_token = $virtooal_api['access_token'];

		list( $header, $payload, $signature ) = explode( '.', $access_token );

		$payload = json_decode( base64_decode( $payload ) );

		if ( $payload->exp <= time() ) {
			return $this->refresh_token( $virtooal_api );
		} else {
			return $access_token;
		}
	}

	public function refresh_token( $virtooal_api ) {
		$refresh_token = $virtooal_api['refresh_token'];

		list( $refresh_header, $refresh_payload, $refresh_signature ) = explode( '.', $refresh_token );
		$refresh_payload = json_decode( base64_decode( $refresh_payload ) );
		if ( $refresh_payload->exp <= time() ) {
			return false;
		}
		$args     = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $refresh_token,
			),
		);
		$response = $this->http_get( 'auth/refresh_token', $args );

		if ( 200 !== $response['http_code'] ) {
			return false;
		}
		$virtooal_api['refresh_token'] = $response['body']['refresh_token'];
		$virtooal_api['access_token']  = $response['body']['access_token'];

		update_option( 'virtooal_api', $virtooal_api );
		return $virtooal_api['access_token'];
	}

	private function get_headers() {
		$access_token = $this->get_access_token();
		return array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
		);
	}

	private function http_get( $path, $args ) {
		$response = wp_remote_get( $this->api_base_url . $path, $args );
		return $this->process_response( $response );
	}

	private function http_post( $path, $args ) {
		$response = wp_remote_post( $this->api_base_url . $path, $args );
		return $this->process_response( $response );
	}

	private function process_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return array(
				'http_code' => 0,
				'body'      => $response->get_error_message(),
			);
		} else {
			return array(
				'http_code' => wp_remote_retrieve_response_code( $response ),
				'body'      => json_decode( wp_remote_retrieve_body( $response ), true ),
			);
		}
	}
}
