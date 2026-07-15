<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class GPSUMA_Tenant {
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'handle_switch' ) );
        add_action( 'admin_notices', array( __CLASS__, 'switcher' ) );
    }
    public static function current_id() {
        $user_id = get_current_user_id();
        $id = $user_id ? (int) get_user_meta( $user_id, 'gpsuma_current_tenant_id', true ) : 0;
        if ( ! $id ) { $id = (int) get_option( 'gpsuma_current_tenant_id', 1 ); }
        return max( 1, $id );
    }
    public static function handle_switch() {
        $id = GPSUMA_Request::get_int( 'gpsuma_switch_tenant' );
        if ( ! current_user_can( 'manage_options' ) || ! $id ) { return; }
        check_admin_referer( 'gpsuma_switch_tenant' );
        $db = new GPSUMA_DB();
        if ( $db->company_is_active( $id ) ) {
            update_user_meta( get_current_user_id(), 'gpsuma_current_tenant_id', $id );
        }
        wp_safe_redirect( remove_query_arg( array( 'gpsuma_switch_tenant', '_wpnonce' ) ) );
        exit;
    }
    public static function current() {
        return ( new GPSUMA_DB() )->get_company( self::current_id() );
    }
    public static function switcher() {
        $page = sanitize_key( GPSUMA_Request::get_text( 'page' ) );
        if ( 0 !== strpos( $page, 'gpsuma-' ) ) { return; }
        $rows = ( new GPSUMA_DB() )->get_companies( true );
        if ( count( $rows ) < 2 ) { return; }
        echo '<div class="notice notice-info gpsuma-tenant-switch"><p><strong>' . esc_html__( 'Azienda:', 'gpsoftwareservices-support-manager' ) . '</strong> <select aria-label="' . esc_attr__( 'Azienda attiva', 'gpsoftwareservices-support-manager' ) . '" onchange="if(this.value)location.href=this.value">';
        foreach ( $rows as $row ) {
            $url = wp_nonce_url( add_query_arg( 'gpsuma_switch_tenant', (int) $row->id ), 'gpsuma_switch_tenant' );
            echo '<option value="' . esc_url( $url ) . '" ' . selected( self::current_id(), (int) $row->id, false ) . '>' . esc_html( $row->nome ) . '</option>';
        }
        echo '</select> <span class="description">' . esc_html__( 'La selezione è personale per ciascun amministratore.', 'gpsoftwareservices-support-manager' ) . '</span></p></div>';
    }
}
function gpsuma_tenant_id() { return class_exists( 'GPSUMA_Tenant' ) ? GPSUMA_Tenant::current_id() : 1; }
