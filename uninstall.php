<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Uninstall routine for GPSoftwareServices Support Manager.
 *
 * Data is removed only after the administrator explicitly enables the
 * corresponding option in the plugin settings.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

if (!(int) get_option('gpsuma_delete_data_on_uninstall', 0)) {
    return;
}

global $wpdb;
$gpsuma_tables = array(
    'gpsuma_contratti_allegati',
    'gpsuma_ticket_allegati',
    'gpsuma_ticket_commenti',
    'gpsuma_interventi_allegati',
    'gpsuma_interventi_dispositivi',
    'gpsuma_scadenze',
    'gpsuma_richieste',
    'gpsuma_contratti',
    'gpsuma_pacchetti',
    'gpsuma_dispositivi',
    'gpsuma_interventi',
    'gpsuma_clienti',
    'gpsuma_aziende',
);
foreach ( $gpsuma_tables as $gpsuma_table ) {
    $gpsuma_table_name = $wpdb->prefix . $gpsuma_table;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Explicit uninstall operation; schema changes cannot use object caching.
    $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $gpsuma_table_name ) );
}

delete_option('gpsuma_db_version');
delete_option('gpsuma_ui_version');
delete_option('gpsuma_current_tenant_id');
delete_option('gpsuma_delete_data_on_uninstall');
remove_role('gpsuma_cliente');
