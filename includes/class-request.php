<?php
/**
 * Request sanitization helpers.
 *
 * @package GPSoftwareServicesSupportManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification -- This class only normalizes input; every state-changing caller must verify its action-specific nonce before using these values.

final class GPSUMA_Request {
	public static function post_text( $key, $default = '' ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;
	}

	public static function post_textarea( $key, $default = '' ) {
		return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : $default;
	}

	public static function post_int( $key, $default = 0 ) {
		return isset( $_POST[ $key ] ) ? absint( wp_unslash( $_POST[ $key ] ) ) : $default;
	}

	public static function post_float( $key, $default = 0.0 ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}
		$value = str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		return is_numeric( $value ) ? (float) $value : $default;
	}

	public static function post_bool( $key ) {
		return isset( $_POST[ $key ] );
	}


	public static function get_text( $key, $default = '' ) {
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default;
	}

	public static function get_int( $key, $default = 0 ) {
		return isset( $_GET[ $key ] ) ? absint( wp_unslash( $_GET[ $key ] ) ) : $default;
	}
	public static function post_int_array( $key ) {
		if ( ! isset( $_POST[ $key ] ) ) { return array(); }
		return array_values( array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $key ] ) ) ) );
	}

	public static function post_text_array( $key ) {
		if ( ! isset( $_POST[ $key ] ) ) { return array(); }
		return array_map( 'sanitize_text_field', (array) wp_unslash( $_POST[ $key ] ) );
	}

	public static function post_data_image( $key, $default = '' ) {
		if ( ! isset( $_POST[ $key ] ) ) { return $default; }
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The data URI is validated by prefix and sanitized immediately below.
		$value = wp_unslash( $_POST[ $key ] );
		return is_string( $value ) && 0 === strpos( $value, 'data:image/png;base64,' ) ? sanitize_text_field( $value ) : $default;
	}

}
// phpcs:enable WordPress.Security.NonceVerification
