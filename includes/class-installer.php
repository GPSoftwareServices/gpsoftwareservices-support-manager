<?php
if (!defined('ABSPATH')) { exit; }

class GPSUMA_Installer {
    /**
     * Migrate data created by versions that used the short legacy `gat` prefix.
     *
     * @param wpdb $wpdb WordPress database instance.
     * @return void
     */
    private static function migrate_legacy_data( $wpdb ) {
        $tables = array(
            'clienti', 'dispositivi', 'interventi', 'interventi_dispositivi',
            'pacchetti', 'richieste', 'interventi_allegati', 'scadenze',
            'ticket_commenti', 'ticket_allegati', 'aziende', 'contratti',
            'contratti_allegati',
        );
        foreach ( $tables as $suffix ) {
            $legacy = $wpdb->prefix . 'gat_' . $suffix;
            $current = $wpdb->prefix . 'gpsuma_' . $suffix;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time schema migration check.
            $legacy_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy ) );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time schema migration check.
            $current_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $current ) );
            if ( $legacy_exists && ! $current_exists ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Required one-time migration of plugin-owned tables.
                $wpdb->query( $wpdb->prepare( 'RENAME TABLE %i TO %i', $legacy, $current ) );
            }
        }

        $legacy_options = array(
            'gat_current_tenant_id'        => 'gpsuma_current_tenant_id',
            'gat_delete_data_on_uninstall' => 'gpsuma_delete_data_on_uninstall',
            'gat_ui_version'               => 'gpsuma_ui_version',
        );
        foreach ( $legacy_options as $legacy_key => $current_key ) {
            $legacy_value = get_option( $legacy_key, null );
            if ( null !== $legacy_value && false === get_option( $current_key, false ) ) {
                add_option( $current_key, $legacy_value );
            }
        }

        $legacy_users = get_users( array( 'role' => 'gat_cliente', 'fields' => array( 'ID' ) ) );
        foreach ( $legacy_users as $legacy_user ) {
            $user = new WP_User( $legacy_user->ID );
            $user->set_role( 'gpsuma_cliente' );
        }

        $portal_pages = get_posts(
            array(
                'post_type'      => 'any',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                's'              => '[gat_area_cliente]',
                'fields'         => 'ids',
            )
        );
        foreach ( $portal_pages as $portal_page_id ) {
            $content = (string) get_post_field( 'post_content', $portal_page_id );
            if ( false !== strpos( $content, '[gat_area_cliente]' ) ) {
                wp_update_post(
                    array(
                        'ID'           => $portal_page_id,
                        'post_content' => str_replace( '[gat_area_cliente]', '[gpsuma_area_cliente]', $content ),
                    )
                );
            }
        }
    }

    public static function install() {
        global $wpdb;
        self::migrate_legacy_data( $wpdb );
        $charset = $wpdb->get_charset_collate();
        $clienti = $wpdb->prefix . 'gpsuma_clienti';
        $dispositivi = $wpdb->prefix . 'gpsuma_dispositivi';
        $interventi = $wpdb->prefix . 'gpsuma_interventi';
        $relazioni = $wpdb->prefix . 'gpsuma_interventi_dispositivi';
        $pacchetti = $wpdb->prefix . 'gpsuma_pacchetti';
        $richieste = $wpdb->prefix . 'gpsuma_richieste';
        $allegati = $wpdb->prefix . 'gpsuma_interventi_allegati';
        $scadenze = $wpdb->prefix . 'gpsuma_scadenze';
        $ticket_commenti = $wpdb->prefix . 'gpsuma_ticket_commenti';
        $ticket_allegati = $wpdb->prefix . 'gpsuma_ticket_allegati';
        $aziende = $wpdb->prefix . 'gpsuma_aziende';
        $contratti = $wpdb->prefix . 'gpsuma_contratti';
        $contratti_allegati = $wpdb->prefix . 'gpsuma_contratti_allegati';

        $sql_clienti = "CREATE TABLE {$clienti} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) NOT NULL DEFAULT 1,
            user_id bigint(20) unsigned NULL,
            nome varchar(200) NOT NULL,
            telefono varchar(50),
            email varchar(150),
            indirizzo text,
            note text,
            data_creazione datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY tenant_id (tenant_id), UNIQUE KEY user_id (user_id)
        ) {$charset};";

        $sql_dispositivi = "CREATE TABLE {$dispositivi} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) NOT NULL DEFAULT 1,
            cliente_id bigint(20) NOT NULL,
            tipo varchar(100), marca varchar(100), modello varchar(150), seriale varchar(150),
            sistema varchar(100), qr_token varchar(64) NULL, stato varchar(30) NOT NULL DEFAULT 'Operativo', note text, data_creazione datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY tenant_id (tenant_id), KEY cliente_id (cliente_id), UNIQUE KEY qr_token (qr_token)
        ) {$charset};";

        $sql_interventi = "CREATE TABLE {$interventi} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) NOT NULL DEFAULT 1,
            cliente_id bigint(20) NOT NULL,
            pacchetto_id bigint(20) NULL,
            conta_pacchetto tinyint(1) NOT NULL DEFAULT 0,
            data_intervento date NOT NULL,
            ora_intervento time NULL,
            ora_fine time NULL,
            tipo varchar(100),
            descrizione text NOT NULL,
            durata_minuti int unsigned NULL,
            durata_prevista_minuti int unsigned NULL,
            tecnico varchar(150),
            ticket_id bigint(20) NULL,
            indirizzo_intervento text NULL,
            ora_inizio_effettiva datetime NULL,
            ora_fine_effettiva datetime NULL,
            checklist longtext NULL,
            firma_cliente longtext NULL,
            costo_manodopera decimal(10,2) NOT NULL DEFAULT 0,
            costo_materiali decimal(10,2) NOT NULL DEFAULT 0,
            costo_trasferta decimal(10,2) NOT NULL DEFAULT 0,
            sconto decimal(10,2) NOT NULL DEFAULT 0,
            aliquota_iva decimal(5,2) NOT NULL DEFAULT 22,
            stato_pagamento varchar(30) NOT NULL DEFAULT 'Da pagare',
            metodo_pagamento varchar(50) NULL,
            materiale text,
            note_interne text,
            stato varchar(50) DEFAULT 'Completato',
            visibile_cliente tinyint(1) NOT NULL DEFAULT 1,
            data_creazione datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY tenant_id (tenant_id), KEY cliente_id (cliente_id), KEY data_intervento (data_intervento), KEY ticket_id (ticket_id)
        ) {$charset};";

        $sql_pacchetti = "CREATE TABLE {$pacchetti} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) NOT NULL DEFAULT 1,
            cliente_id bigint(20) NOT NULL,
            nome varchar(200) NOT NULL,
            interventi_inclusi int(10) unsigned NOT NULL DEFAULT 10,
            interventi_illimitati tinyint(1) NOT NULL DEFAULT 0,
            data_attivazione date NOT NULL,
            data_scadenza date NULL,
            stato varchar(30) NOT NULL DEFAULT 'Attivo',
            note text NULL,
            data_creazione datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY tenant_id (tenant_id), KEY cliente_id (cliente_id), KEY stato (stato)
        ) {$charset};";

        $sql_richieste = "CREATE TABLE {$richieste} (
            id bigint(20) NOT NULL AUTO_INCREMENT, tenant_id bigint(20) NOT NULL DEFAULT 1, cliente_id bigint(20) NOT NULL,
            oggetto varchar(200) NOT NULL, descrizione text NOT NULL, dispositivo varchar(200) NULL, dispositivo_id bigint(20) NULL,
            priorita varchar(30) NOT NULL DEFAULT 'Normale', stato varchar(40) NOT NULL DEFAULT 'Aperta',
            nota_amministratore text NULL, data_creazione datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            data_aggiornamento datetime NULL, PRIMARY KEY(id), KEY tenant_id (tenant_id), KEY cliente_id (cliente_id), KEY dispositivo_id (dispositivo_id), KEY stato (stato)
        ) {$charset};";


        $sql_allegati = "CREATE TABLE {$allegati} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            intervento_id bigint(20) NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            visibile_cliente tinyint(1) NOT NULL DEFAULT 1,
            data_creazione datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY intervento_id (intervento_id), KEY attachment_id (attachment_id)
        ) {$charset};";

        $sql_scadenze = "CREATE TABLE {$scadenze} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) NOT NULL DEFAULT 1,
            cliente_id bigint(20) NOT NULL,
            dispositivo_id bigint(20) NULL,
            tipo varchar(100) NOT NULL,
            titolo varchar(200) NOT NULL,
            data_scadenza date NOT NULL,
            preavviso_giorni int unsigned NOT NULL DEFAULT 30,
            stato varchar(30) NOT NULL DEFAULT 'Attiva',
            visibile_cliente tinyint(1) NOT NULL DEFAULT 1,
            note text NULL,
            data_creazione datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY tenant_id (tenant_id), KEY cliente_id (cliente_id), KEY dispositivo_id (dispositivo_id), KEY data_scadenza (data_scadenza), KEY stato (stato)
        ) {$charset};";


        $sql_ticket_commenti = "CREATE TABLE {$ticket_commenti} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            richiesta_id bigint(20) NOT NULL,
            autore_user_id bigint(20) unsigned NULL,
            autore_nome varchar(150) NOT NULL,
            tipo_autore varchar(20) NOT NULL DEFAULT 'cliente',
            messaggio text NOT NULL,
            visibile_cliente tinyint(1) NOT NULL DEFAULT 1,
            data_creazione datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY richiesta_id (richiesta_id), KEY data_creazione (data_creazione)
        ) {$charset};";

        $sql_ticket_allegati = "CREATE TABLE {$ticket_allegati} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            richiesta_id bigint(20) NOT NULL,
            commento_id bigint(20) NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            visibile_cliente tinyint(1) NOT NULL DEFAULT 1,
            data_creazione datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY richiesta_id (richiesta_id), KEY commento_id (commento_id), KEY attachment_id (attachment_id)
        ) {$charset};";

        
        $sql_aziende = "CREATE TABLE {$aziende} (
            id bigint(20) NOT NULL AUTO_INCREMENT, nome varchar(200) NOT NULL, ragione_sociale varchar(200), partita_iva varchar(30), codice_fiscale varchar(30), email varchar(150), telefono varchar(50), indirizzo text, logo_id bigint(20) unsigned NULL, colore_primario varchar(20) DEFAULT '#2271b1', stato varchar(30) NOT NULL DEFAULT 'Attiva', data_creazione datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), KEY stato (stato)
        ) {$charset};";
        $sql_contratti = "CREATE TABLE {$contratti} (
            id bigint(20) NOT NULL AUTO_INCREMENT, tenant_id bigint(20) NOT NULL DEFAULT 1, cliente_id bigint(20) NOT NULL, nome varchar(200) NOT NULL, tipo varchar(50) DEFAULT 'Personalizzato', data_inizio date NOT NULL, data_fine date NULL, stato varchar(30) DEFAULT 'Attivo', ore_incluse decimal(8,2) NOT NULL DEFAULT 0, interventi_inclusi int unsigned NOT NULL DEFAULT 0, interventi_illimitati tinyint(1) NOT NULL DEFAULT 0, valore_contratto decimal(10,2) NOT NULL DEFAULT 0, rinnovo_automatico tinyint(1) NOT NULL DEFAULT 0, servizi_inclusi text NULL, esclusioni text NULL, note text NULL, data_creazione datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), KEY tenant_id (tenant_id), KEY cliente_id (cliente_id), KEY data_fine (data_fine), KEY stato (stato)
        ) {$charset};";
        $sql_contratti_allegati = "CREATE TABLE {$contratti_allegati} (id bigint(20) NOT NULL AUTO_INCREMENT, contratto_id bigint(20) NOT NULL, attachment_id bigint(20) unsigned NOT NULL, data_creazione datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), KEY contratto_id (contratto_id)) {$charset};";

        $sql_relazioni = "CREATE TABLE {$relazioni} (
            intervento_id bigint(20) NOT NULL,
            dispositivo_id bigint(20) NOT NULL,
            PRIMARY KEY(intervento_id, dispositivo_id),
            KEY dispositivo_id (dispositivo_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_clienti); dbDelta($sql_dispositivi); dbDelta($sql_pacchetti); dbDelta($sql_interventi); dbDelta($sql_relazioni); dbDelta($sql_richieste); dbDelta($sql_allegati); dbDelta($sql_scadenze); dbDelta($sql_ticket_commenti); dbDelta($sql_ticket_allegati); dbDelta($sql_aziende); dbDelta($sql_contratti); dbDelta($sql_contratti_allegati);
        add_role('gpsuma_cliente', 'Cliente assistenza', array('read' => true));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time schema installation check; caching would be incorrect here.
        $tenant_count = (int) $wpdb->get_var(
            $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $aziende ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared immediately above with a table identifier placeholder.
        );
        if ( 0 === $tenant_count ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required one-time insertion of the default plugin tenant during installation.
            $wpdb->insert(
                $aziende,
                array(
                    'nome'           => get_bloginfo( 'name' ),
                    'ragione_sociale' => get_bloginfo( 'name' ),
                    'email'          => get_option( 'admin_email' ),
                ),
                array( '%s', '%s', '%s' )
            );
        }
        if ( false === get_option( 'gpsuma_current_tenant_id', false ) ) {
            add_option( 'gpsuma_current_tenant_id', 1 );
        }
        update_option( 'gpsuma_db_version', '5.2.1' );
        update_option('gpsuma_ui_version', '2');
    }
}
