<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GPSUMA_API {
    public static function init() { add_action( 'rest_api_init', array( __CLASS__, 'routes' ) ); }
    public static function routes() {
        register_rest_route( 'gpsuma/v1', '/summary', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'summary' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ) );
        register_rest_route( 'gpsuma/v1', '/search', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'search' ), 'permission_callback' => array( __CLASS__, 'can_manage' ), 'args' => array( 'q' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ) ) ) );
    }
    public static function can_manage() { return current_user_can( 'manage_options' ); }
    public static function summary() {
        $tenant_id = gpsuma_tenant_id();
        return rest_ensure_response( array_merge( array( 'tenant_id' => $tenant_id ), ( new GPSUMA_DB() )->get_api_summary( $tenant_id ) ) );
    }
    public static function search( WP_REST_Request $request ) {
        $query = sanitize_text_field( (string) $request->get_param( 'q' ) );
        return rest_ensure_response( ( new GPSUMA_DB() )->api_search( $query, gpsuma_tenant_id() ) );
    }
}
