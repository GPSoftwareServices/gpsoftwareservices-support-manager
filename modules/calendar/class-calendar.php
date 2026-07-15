<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GPSUMA_Calendar {
	public function __construct() {
		add_action( 'admin_post_gpsuma_timer_intervento', array( $this, 'timer' ) );
		add_action( 'admin_post_gpsuma_sposta_intervento', array( $this, 'sposta' ) );
	}

	public function timer() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }
		$id = GPSUMA_Request::get_int( 'id' );
		$azione = sanitize_key( GPSUMA_Request::get_text( 'azione' ) );
		check_admin_referer( 'gpsuma_timer_' . $id );
		$db = new GPSUMA_DB();
		$now = current_time( 'mysql' );

		if ( 'start' === $azione ) {
			$db->update(
				'interventi',
				array( 'ora_inizio_effettiva' => $now, 'ora_fine_effettiva' => null, 'stato' => 'In corso' ),
				array( 'id' => $id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		} elseif ( 'stop' === $azione ) {
			$start = $db->get_intervention_start( $id );
			$minutes = $start ? max( 1, (int) round( ( current_time( 'timestamp' ) - strtotime( $start ) ) / 60 ) ) : 0;
			$db->update(
				'interventi',
				array( 'ora_fine_effettiva' => $now, 'durata_minuti' => $minutes, 'stato' => 'Completato' ),
				array( 'id' => $id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=gpsuma-agenda' ) );
		exit;
	}

	public function sposta() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }
		$id = GPSUMA_Request::post_int( 'id' );
		check_admin_referer( 'gpsuma_sposta_' . $id );
		$date = GPSUMA_Request::post_text( 'data' );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$db = new GPSUMA_DB();
			$db->update( 'interventi', array( 'data_intervento' => $date ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=gpsuma-agenda' ) );
		exit;
	}
}
