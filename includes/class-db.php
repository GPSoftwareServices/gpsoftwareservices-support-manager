<?php
/**
 * Central database access for GPSoftwareServices Support Manager.
 *
 * @package GPSoftwareServicesSupportManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GPSUMA_DB {
	/** @var wpdb */
	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function table( $name ) {
		$allowed = array(
			'clienti', 'dispositivi', 'interventi', 'pacchetti', 'richieste',
			'contratti', 'contratti_allegati', 'tenants', 'aziende', 'scadenze',
			'ticket_commenti', 'ticket_allegati',
		);
		if ( ! in_array( $name, $allowed, true ) ) {
			throw new InvalidArgumentException( 'Invalid GPSoftwareServices Support Manager table name.' );
		}
		return $this->wpdb->prefix . 'gpsuma_' . $name;
	}

	public function raw_table( $name ) {
		$allowed = array( 'interventi_dispositivi', 'interventi_allegati' );
		if ( ! in_array( $name, $allowed, true ) ) {
			throw new InvalidArgumentException( 'Invalid GPSoftwareServices Support Manager relation table name.' );
		}
		return $this->wpdb->prefix . 'gpsuma_' . $name;
	}

	public function insert( $table, array $data, array $formats = null ) {
		return $this->wpdb->insert( $this->table( $table ), $data, $formats );
	}

	public function update( $table, array $data, array $where, array $formats = null, array $where_formats = null ) {
		return $this->wpdb->update( $this->table( $table ), $data, $where, $formats, $where_formats );
	}

	public function delete( $table, array $where, array $where_formats = null ) {
		return $this->wpdb->delete( $this->table( $table ), $where, $where_formats );
	}

	/**
	 * Execute a prepared single-row query against plugin-owned tables.
	 *
	 * The SQL template is internal and every dynamic value, including identifiers,
	 * is supplied through wpdb placeholders by the calling repository method.
	 */
	private function get_row( $query, array $args = array(), $output = OBJECT ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal SQL templates are fixed in repository methods; all values and identifiers are passed as placeholders.
		$prepared_query = $this->wpdb->prepare( $query, ...$args );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared by wpdb immediately above; operational custom-table data must be current.
		return $this->wpdb->get_row( $prepared_query, $output );
	}

	/** Execute a prepared multi-row query against plugin-owned tables. */
	private function get_results( $query, array $args = array(), $output = OBJECT ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal SQL templates are fixed in repository methods; all values and identifiers are passed as placeholders.
		$prepared_query = $this->wpdb->prepare( $query, ...$args );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared by wpdb immediately above; operational custom-table data must be current.
		return $this->wpdb->get_results( $prepared_query, $output );
	}

	/** Execute a prepared scalar query against plugin-owned tables. */
	private function get_var( $query, array $args = array() ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal SQL templates are fixed in repository methods; all values and identifiers are passed as placeholders.
		$prepared_query = $this->wpdb->prepare( $query, ...$args );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared by wpdb immediately above; operational custom-table data must be current.
		return $this->wpdb->get_var( $prepared_query );
	}

	/** Execute a prepared column query against plugin-owned tables. */
	private function get_col( $query, array $args = array() ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal SQL templates are fixed in repository methods; all values and identifiers are passed as placeholders.
		$prepared_query = $this->wpdb->prepare( $query, ...$args );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared by wpdb immediately above; operational custom-table data must be current.
		return $this->wpdb->get_col( $prepared_query );
	}

	public function get_contract( $id, $tenant_id, $output = OBJECT ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d AND tenant_id = %d',
			array( $this->table( 'contratti' ), $id, $tenant_id ),
			$output
		);
	}

	public function get_contracts_with_usage( $tenant_id ) {
		return $this->get_results(
			'SELECT c.*, cl.nome AS cliente_nome, COUNT(i.id) AS interventi_usati,
				COALESCE(SUM(i.durata_minuti), 0) AS minuti_usati
			FROM %i c
			INNER JOIN %i cl ON cl.id = c.cliente_id
			LEFT JOIN %i i ON i.contratto_id = c.id
			WHERE c.tenant_id = %d
			GROUP BY c.id
			ORDER BY c.data_fine ASC',
			array(
				$this->table( 'contratti' ),
				$this->table( 'clienti' ),
				$this->table( 'interventi' ),
				$tenant_id,
			)
		);
	}

	public function get_customer_choices( $tenant_id ) {
		return $this->get_results(
			'SELECT id, nome FROM %i WHERE tenant_id = %d ORDER BY nome',
			array( $this->table( 'clienti' ), $tenant_id )
		);
	}

	public function count_contract_interventions( $contract_id ) {
		return (int) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE contratto_id = %d',
			array( $this->table( 'interventi' ), $contract_id )
		);
	}

	public function get_contract_attachment_ids( $contract_id ) {
		return $this->get_col(
			'SELECT attachment_id FROM %i WHERE contratto_id = %d',
			array( $this->table( 'contratti_allegati' ), $contract_id )
		);
	}

	public function begin() {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control for related custom-table writes.
		return $this->wpdb->query( 'START TRANSACTION' );
	}

	public function commit() {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control for related custom-table writes.
		return $this->wpdb->query( 'COMMIT' );
	}

	public function rollback() {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control for related custom-table writes.
		return $this->wpdb->query( 'ROLLBACK' );
	}

	public function customer_exists( $customer_id ) {
		return (bool) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE id = %d',
			array( $this->table( 'clienti' ), $customer_id )
		);
	}

	public function package_belongs_to_customer( $package_id, $customer_id ) {
		return (bool) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE id = %d AND cliente_id = %d',
			array( $this->table( 'pacchetti' ), $package_id, $customer_id )
		);
	}

	public function device_belongs_to_customer( $device_id, $customer_id ) {
		return (bool) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE id = %d AND cliente_id = %d',
			array( $this->table( 'dispositivi' ), $device_id, $customer_id )
		);
	}

	public function get_intervention_attachment_ids( $intervention_id ) {
		return $this->get_col(
			'SELECT attachment_id FROM %i WHERE intervento_id = %d',
			array( $this->raw_table( 'interventi_allegati' ), $intervention_id )
		);
	}

	public function delete_intervention( $intervention_id ) {
		$this->wpdb->delete( $this->raw_table( 'interventi_allegati' ), array( 'intervento_id' => $intervention_id ), array( '%d' ) );
		$this->wpdb->delete( $this->raw_table( 'interventi_dispositivi' ), array( 'intervento_id' => $intervention_id ), array( '%d' ) );
		return $this->delete( 'interventi', array( 'id' => $intervention_id ), array( '%d' ) );
	}

	public function replace_intervention_devices( $intervention_id, $customer_id, array $device_ids ) {
		$table = $this->raw_table( 'interventi_dispositivi' );
		$this->wpdb->delete( $table, array( 'intervento_id' => $intervention_id ), array( '%d' ) );
		foreach ( array_unique( array_map( 'absint', $device_ids ) ) as $device_id ) {
			if ( $device_id && $this->device_belongs_to_customer( $device_id, $customer_id ) ) {
				$this->wpdb->insert( $table, array( 'intervento_id' => $intervention_id, 'dispositivo_id' => $device_id ), array( '%d', '%d' ) );
			}
		}
	}

	public function add_intervention_attachment( $intervention_id, $attachment_id, $visible ) {
		return $this->wpdb->insert(
			$this->raw_table( 'interventi_allegati' ),
			array(
				'intervento_id'    => $intervention_id,
				'attachment_id'    => $attachment_id,
				'visibile_cliente' => $visible ? 1 : 0,
			),
			array( '%d', '%d', '%d' )
		);
	}

	public function remove_intervention_attachment( $intervention_id, $attachment_id ) {
		$table  = $this->raw_table( 'interventi_allegati' );
		$exists = (bool) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE intervento_id = %d AND attachment_id = %d',
			array( $table, $intervention_id, $attachment_id )
		);
		if ( $exists ) {
			$this->wpdb->delete( $table, array( 'intervento_id' => $intervention_id, 'attachment_id' => $attachment_id ), array( '%d', '%d' ) );
		}
		return $exists;
	}

	public function get_intervention_form_data( $tenant_id ) {
		return array(
			'clienti' => $this->get_results(
				'SELECT id, nome FROM %i WHERE tenant_id = %d ORDER BY nome',
				array( $this->table( 'clienti' ), $tenant_id )
			),
			'dispositivi' => $this->get_results(
				'SELECT id, cliente_id, tipo, marca, modello FROM %i WHERE tenant_id = %d ORDER BY tipo, marca, modello',
				array( $this->table( 'dispositivi' ), $tenant_id )
			),
			'tickets' => $this->get_results(
				'SELECT id, cliente_id, oggetto, stato FROM %i WHERE tenant_id = %d AND stato <> %s ORDER BY id DESC',
				array( $this->table( 'richieste' ), $tenant_id, 'Chiuso' )
			),
			'contratti' => $this->get_results(
				'SELECT * FROM %i WHERE tenant_id = %d AND stato = %s ORDER BY data_fine',
				array( $this->table( 'contratti' ), $tenant_id, 'Attivo' )
			),
			'pacchetti' => $this->get_results(
				'SELECT p.*, COUNT(CASE WHEN i.conta_pacchetto = 1 THEN 1 END) AS utilizzati
				FROM %i p
				LEFT JOIN %i i ON i.pacchetto_id = p.id
				WHERE p.tenant_id = %d
				GROUP BY p.id
				ORDER BY p.data_attivazione DESC, p.id DESC',
				array( $this->table( 'pacchetti' ), $this->table( 'interventi' ), $tenant_id )
			),
		);
	}

	public function get_intervention( $intervention_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d',
			array( $this->table( 'interventi' ), $intervention_id )
		);
	}

	public function get_intervention_device_ids( $intervention_id ) {
		return array_map(
			'intval',
			$this->get_col(
				'SELECT dispositivo_id FROM %i WHERE intervento_id = %d',
				array( $this->raw_table( 'interventi_dispositivi' ), $intervention_id )
			)
		);
	}

	public function get_intervention_attachments( $intervention_id ) {
		return $this->get_results(
			'SELECT * FROM %i WHERE intervento_id = %d ORDER BY id DESC',
			array( $this->raw_table( 'interventi_allegati' ), $intervention_id )
		);
	}

	public function get_interventions( $customer_id = 0 ) {
		$query = 'SELECT i.*, c.nome AS cliente_nome,
			GROUP_CONCAT(CONCAT_WS(\' \', d.tipo, d.marca, d.modello) SEPARATOR \', \') AS dispositivi
			FROM %i i
			INNER JOIN %i c ON c.id = i.cliente_id
			LEFT JOIN %i r ON r.intervento_id = i.id
			LEFT JOIN %i d ON d.id = r.dispositivo_id';
		$args  = array(
			$this->table( 'interventi' ),
			$this->table( 'clienti' ),
			$this->raw_table( 'interventi_dispositivi' ),
			$this->table( 'dispositivi' ),
		);

		if ( $customer_id ) {
			$query .= ' WHERE i.cliente_id = %d';
			$args[] = $customer_id;
		}
		$query .= ' GROUP BY i.id ORDER BY i.data_intervento DESC, i.id DESC';

		return $this->get_results( $query, $args );
	}

	public function get_portal_customer( $user_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE user_id = %d LIMIT 1',
			array( $this->table( 'clienti' ), $user_id )
		);
	}

	public function get_portal_device( $device_id, $customer_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d AND cliente_id = %d',
			array( $this->table( 'dispositivi' ), $device_id, $customer_id )
		);
	}

	public function create_portal_ticket( array $data ) {
		$result = $this->insert( 'richieste', $data, array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ) );
		return $result ? (int) $this->wpdb->insert_id : 0;
	}

	public function add_ticket_attachment( $ticket_id, $attachment_id ) {
		return $this->insert(
			'ticket_allegati',
			array( 'richiesta_id' => $ticket_id, 'attachment_id' => $attachment_id, 'visibile_cliente' => 1, 'data_creazione' => current_time( 'mysql' ) ),
			array( '%d', '%d', '%d', '%s' )
		);
	}

	public function portal_ticket_belongs_to_customer( $ticket_id, $customer_id ) {
		return (bool) $this->get_var(
			'SELECT id FROM %i WHERE id = %d AND cliente_id = %d',
			array( $this->table( 'richieste' ), $ticket_id, $customer_id )
		);
	}

	public function add_portal_ticket_comment( array $data ) {
		return $this->insert( 'ticket_commenti', $data, array( '%d', '%d', '%s', '%s', '%s', '%d', '%s' ) );
	}

	public function touch_ticket( $ticket_id ) {
		return $this->update( 'richieste', array( 'data_aggiornamento' => current_time( 'mysql' ) ), array( 'id' => $ticket_id ), array( '%s' ), array( '%d' ) );
	}

	public function get_portal_data( $customer_id ) {
		$devices = $this->get_results(
			'SELECT * FROM %i WHERE cliente_id = %d ORDER BY tipo, marca, modello',
			array( $this->table( 'dispositivi' ), $customer_id )
		);
		$packages = $this->get_results(
			"SELECT p.*, COUNT(CASE WHEN i.conta_pacchetto = 1 THEN 1 END) AS utilizzati
			FROM %i p LEFT JOIN %i i ON i.pacchetto_id = p.id
			WHERE p.cliente_id = %d GROUP BY p.id
			ORDER BY (p.stato = 'Attivo') DESC, p.data_attivazione DESC",
			array( $this->table( 'pacchetti' ), $this->table( 'interventi' ), $customer_id )
		);
		$contracts = $this->get_results(
			"SELECT co.*, COUNT(i.id) AS interventi_usati, COALESCE(SUM(i.durata_minuti), 0) AS minuti_usati
			FROM %i co LEFT JOIN %i i ON i.contratto_id = co.id
			WHERE co.cliente_id = %d GROUP BY co.id
			ORDER BY (co.stato = 'Attivo') DESC, co.data_inizio DESC",
			array( $this->table( 'contratti' ), $this->table( 'interventi' ), $customer_id )
		);
		$tickets = $this->get_results(
			'SELECT * FROM %i WHERE cliente_id = %d ORDER BY data_creazione DESC, id DESC LIMIT 20',
			array( $this->table( 'richieste' ), $customer_id )
		);
		$interventions = $this->get_results(
			"SELECT i.*, GROUP_CONCAT(CONCAT_WS(' ', d.tipo, d.marca, d.modello) SEPARATOR ', ') AS dispositivi
			FROM %i i LEFT JOIN %i r ON r.intervento_id = i.id LEFT JOIN %i d ON d.id = r.dispositivo_id
			WHERE i.cliente_id = %d AND i.visibile_cliente = 1 GROUP BY i.id
			ORDER BY i.data_intervento DESC, i.id DESC",
			array( $this->table( 'interventi' ), $this->raw_table( 'interventi_dispositivi' ), $this->table( 'dispositivi' ), $customer_id )
		);
		$deadlines = $this->get_results(
			"SELECT s.*, CONCAT_WS(' ', d.tipo, d.marca, d.modello) AS dispositivo_nome
			FROM %i s LEFT JOIN %i d ON d.id = s.dispositivo_id
			WHERE s.cliente_id = %d AND s.stato = 'Attiva' AND s.visibile_cliente = 1
			ORDER BY s.data_scadenza ASC",
			array( $this->table( 'scadenze' ), $this->table( 'dispositivi' ), $customer_id )
		);

		$intervention_attachments = array();
		foreach ( $interventions as $intervention ) {
			$intervention_attachments[ $intervention->id ] = $this->get_results(
				'SELECT * FROM %i WHERE intervento_id = %d AND visibile_cliente = 1 ORDER BY id DESC',
				array( $this->raw_table( 'interventi_allegati' ), $intervention->id )
			);
		}

		$ticket_comments = array();
		$ticket_attachments = array();
		foreach ( $tickets as $ticket ) {
			$ticket_comments[ $ticket->id ] = $this->get_results(
				'SELECT * FROM %i WHERE richiesta_id = %d AND visibile_cliente = 1 ORDER BY data_creazione ASC, id ASC',
				array( $this->table( 'ticket_commenti' ), $ticket->id )
			);
			$ticket_attachments[ $ticket->id ] = $this->get_results(
				'SELECT * FROM %i WHERE richiesta_id = %d AND visibile_cliente = 1 ORDER BY id ASC',
				array( $this->table( 'ticket_allegati' ), $ticket->id )
			);
		}

		return array(
			'devices' => $devices,
			'packages' => $packages,
			'contracts' => $contracts,
			'tickets' => $tickets,
			'interventions' => $interventions,
			'intervention_attachments' => $intervention_attachments,
			'deadlines' => $deadlines,
			'ticket_comments' => $ticket_comments,
			'ticket_attachments' => $ticket_attachments,
		);
	}


	/**
	 * Return the data required by the administration dashboard.
	 *
	 * @param int    $tenant_id Tenant identifier.
	 * @param string $today     Current date in Y-m-d format.
	 * @param string $month     Current month in Y-m format.
	 * @param string $in_30     Date thirty days from now.
	 * @param string $in_14     Date fourteen days from now.
	 * @return array
	 */
	public function get_dashboard_data( $tenant_id, $today, $month, $in_30, $in_14 ) {
		return array(
			'clienti' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d', array( $this->table( 'clienti' ), $tenant_id ) ),
			'dispositivi' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d', array( $this->table( 'dispositivi' ), $tenant_id ) ),
			'ticket_aperti' => (int) $this->get_var( "SELECT COUNT(*) FROM %i WHERE tenant_id = %d AND stato NOT IN ('Risolto','Chiuso')", array( $this->table( 'richieste' ), $tenant_id ) ),
			'interventi_oggi' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d AND data_intervento = %s', array( $this->table( 'interventi' ), $tenant_id, $today ) ),
			'scadenze' => (int) $this->get_var( "SELECT COUNT(*) FROM %i WHERE tenant_id = %d AND stato = 'Attiva' AND data_scadenza <= %s", array( $this->table( 'scadenze' ), $tenant_id, $in_30 ) ),
			'contratti' => (int) $this->get_var( "SELECT COUNT(*) FROM %i WHERE tenant_id = %d AND stato = 'Attivo'", array( $this->table( 'contratti' ), $tenant_id ) ),
			'ricavi' => (float) $this->get_var( "SELECT COALESCE(SUM(((costo_manodopera + costo_materiali + costo_trasferta - sconto) * (1 + aliquota_iva / 100))), 0) FROM %i WHERE tenant_id = %d AND DATE_FORMAT(data_intervento, '%%Y-%%m') = %s", array( $this->table( 'interventi' ), $tenant_id, $month ) ),
			'agenda' => $this->get_results(
				'SELECT i.id, i.data_intervento, i.ora_intervento, i.tipo, i.stato, c.nome AS cliente_nome FROM %i i INNER JOIN %i c ON c.id = i.cliente_id WHERE i.tenant_id = %d AND i.data_intervento BETWEEN %s AND %s ORDER BY i.data_intervento, i.ora_intervento LIMIT 8',
				array( $this->table( 'interventi' ), $this->table( 'clienti' ), $tenant_id, $today, $in_14 )
			),
			'tickets' => $this->get_results(
				"SELECT r.id, r.oggetto, r.priorita, r.stato, r.data_creazione, c.nome AS cliente_nome FROM %i r INNER JOIN %i c ON c.id = r.cliente_id WHERE r.tenant_id = %d AND r.stato NOT IN ('Risolto','Chiuso') ORDER BY FIELD(r.priorita,'Urgente','Alta','Normale','Bassa'), r.data_creazione DESC LIMIT 6",
				array( $this->table( 'richieste' ), $this->table( 'clienti' ), $tenant_id )
			),
			'stati' => $this->get_results(
				"SELECT COALESCE(NULLIF(stato,''),'Operativo') AS stato, COUNT(*) AS totale FROM %i WHERE tenant_id = %d GROUP BY stato ORDER BY totale DESC",
				array( $this->table( 'dispositivi' ), $tenant_id )
			),
		);
	}

	/**
	 * Return economic-report rows and customer choices.
	 *
	 * @param int $tenant_id Tenant identifier.
	 * @param int $year      Report year.
	 * @param int $customer_id Optional customer identifier.
	 * @return array
	 */
	public function get_economic_report_data( $tenant_id, $year, $customer_id = 0 ) {
		$intervention_query = 'SELECT i.*, c.nome AS cliente_nome FROM %i i INNER JOIN %i c ON c.id = i.cliente_id WHERE YEAR(i.data_intervento) = %d AND c.tenant_id = %d';
		$intervention_args  = array( $this->table( 'interventi' ), $this->table( 'clienti' ), $year, $tenant_id );
		$contract_query     = 'SELECT co.*, c.nome AS cliente_nome FROM %i co INNER JOIN %i c ON c.id = co.cliente_id WHERE YEAR(co.data_inizio) = %d AND co.tenant_id = %d';
		$contract_args      = array( $this->table( 'contratti' ), $this->table( 'clienti' ), $year, $tenant_id );

		if ( $customer_id ) {
			$intervention_query .= ' AND i.cliente_id = %d';
			$intervention_args[] = $customer_id;
			$contract_query .= ' AND co.cliente_id = %d';
			$contract_args[] = $customer_id;
		}

		$intervention_query .= ' ORDER BY i.data_intervento DESC, i.id DESC';
		$contract_query     .= ' ORDER BY co.data_inizio DESC, co.id DESC';

		return array(
			'interventions' => $this->get_results( $intervention_query, $intervention_args ),
			'contracts'     => $this->get_results( $contract_query, $contract_args ),
			'customers'     => $this->get_customer_choices( $tenant_id ),
		);
	}

	/**
	 * Return data required to render an intervention report.
	 *
	 * @param int $intervention_id Intervention identifier.
	 * @return array
	 */
	public function get_intervention_report_data( $intervention_id ) {
		return array(
			'intervention' => $this->get_row(
				'SELECT i.*, c.nome AS cliente_nome, c.email AS cliente_email, c.telefono AS cliente_telefono, c.indirizzo AS cliente_indirizzo, t.oggetto AS ticket_oggetto FROM %i i INNER JOIN %i c ON c.id = i.cliente_id LEFT JOIN %i t ON t.id = i.ticket_id WHERE i.id = %d',
				array( $this->table( 'interventi' ), $this->table( 'clienti' ), $this->table( 'richieste' ), $intervention_id )
			),
			'devices' => $this->get_results(
				'SELECT d.* FROM %i d INNER JOIN %i r ON r.dispositivo_id = d.id WHERE r.intervento_id = %d',
				array( $this->table( 'dispositivi' ), $this->raw_table( 'interventi_dispositivi' ), $intervention_id )
			),
		);
	}

	public function get_device_with_customer( $device_id, $token = '' ) {
		$query = 'SELECT d.*, c.nome AS cliente_nome, c.user_id FROM %i d INNER JOIN %i c ON c.id = d.cliente_id WHERE d.id = %d';
		$args  = array( $this->table( 'dispositivi' ), $this->table( 'clienti' ), $device_id );
		if ( '' !== $token ) {
			$query .= ' AND d.qr_token = %s';
			$args[] = $token;
		}
		return $this->get_row( $query, $args );
	}

	public function ensure_device_qr_token( $device_id, $current_token = '' ) {
		if ( $current_token ) {
			return $current_token;
		}
		$token = wp_generate_password( 32, false, false );
		$this->update( 'dispositivi', array( 'qr_token' => $token ), array( 'id' => $device_id ), array( '%s' ), array( '%d' ) );
		return $token;
	}

	public function get_device_portal_data( $device_id, $include_private = false ) {
		$private = $include_private ? 1 : 0;
		return array(
			'interventions' => $this->get_results(
				'SELECT i.* FROM %i i INNER JOIN %i r ON r.intervento_id = i.id WHERE r.dispositivo_id = %d AND (i.visibile_cliente = 1 OR %d = 1) ORDER BY i.data_intervento DESC, i.id DESC LIMIT 20',
				array( $this->table( 'interventi' ), $this->raw_table( 'interventi_dispositivi' ), $device_id, $private )
			),
			'deadlines' => $this->get_results(
				'SELECT * FROM %i WHERE dispositivo_id = %d AND stato = %s AND (visibile_cliente = 1 OR %d = 1) ORDER BY data_scadenza',
				array( $this->table( 'scadenze' ), $device_id, 'Attiva', $private )
			),
		);
	}

	public function get_agenda_rows( $start, $end ) {
		return $this->get_results(
			'SELECT i.*, c.nome AS cliente_nome, c.indirizzo AS cliente_indirizzo FROM %i i INNER JOIN %i c ON c.id = i.cliente_id WHERE i.data_intervento BETWEEN %s AND %s ORDER BY i.data_intervento, i.ora_intervento, i.id',
			array( $this->table( 'interventi' ), $this->table( 'clienti' ), $start, $end )
		);
	}


	public function get_company( $company_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d',
			array( $this->table( 'aziende' ), $company_id )
		);
	}

	public function get_companies( $active_only = false ) {
		$query = 'SELECT * FROM %i';
		$args  = array( $this->table( 'aziende' ) );
		if ( $active_only ) {
			$query .= ' WHERE stato = %s';
			$args[] = 'Attiva';
		}
		$query .= ' ORDER BY nome';
		return $this->get_results( $query, $args );
	}

	public function company_is_active( $company_id ) {
		return (bool) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE id = %d AND stato = %s',
			array( $this->table( 'aziende' ), $company_id, 'Attiva' )
		);
	}

	public function save_company( array $data, $company_id = 0 ) {
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
		if ( $company_id ) {
			return $this->update( 'aziende', $data, array( 'id' => $company_id ), $formats, array( '%d' ) );
		}
		return $this->insert( 'aziende', $data, $formats );
	}

	public function get_deadline_form_data() {
		return array(
			'customers' => $this->get_results(
				'SELECT id, nome FROM %i ORDER BY nome',
				array( $this->table( 'clienti' ) )
			),
			'devices' => $this->get_results(
				'SELECT id, cliente_id, tipo, marca, modello FROM %i ORDER BY tipo, marca, modello',
				array( $this->table( 'dispositivi' ) )
			),
		);
	}

	public function get_deadline( $deadline_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d',
			array( $this->table( 'scadenze' ), $deadline_id )
		);
	}

	public function get_deadlines_admin() {
		return $this->get_results(
			"SELECT s.*, c.nome AS cliente_nome, CONCAT_WS(' ', d.tipo, d.marca, d.modello) AS dispositivo_nome
			 FROM %i s
			 INNER JOIN %i c ON c.id = s.cliente_id
			 LEFT JOIN %i d ON d.id = s.dispositivo_id
			 ORDER BY (s.stato = %s) DESC, s.data_scadenza ASC, s.id DESC",
			array( $this->table( 'scadenze' ), $this->table( 'clienti' ), $this->table( 'dispositivi' ), 'Attiva' )
		);
	}

	public function save_deadline( array $data, $deadline_id = 0 ) {
		$formats = array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s' );
		if ( $deadline_id ) {
			return $this->update( 'scadenze', $data, array( 'id' => $deadline_id ), $formats, array( '%d' ) );
		}
		return $this->insert( 'scadenze', $data, $formats );
	}

	public function delete_deadline( $deadline_id ) {
		return $this->delete( 'scadenze', array( 'id' => $deadline_id ), array( '%d' ) );
	}


	/** Return the complete administration summary for one customer. */
	public function get_customer_admin_summary( $customer_id ) {
		$customer = $this->get_row(
			'SELECT c.*, u.display_name AS utente_nome, u.user_email AS utente_email FROM %i c LEFT JOIN %i u ON u.ID = c.user_id WHERE c.id = %d',
			array( $this->table( 'clienti' ), $this->wpdb->users, $customer_id )
		);
		if ( ! $customer ) {
			return null;
		}
		return array(
			'cliente' => $customer,
			'tot_dispositivi' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE cliente_id = %d', array( $this->table( 'dispositivi' ), $customer_id ) ),
			'tot_interventi' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE cliente_id = %d', array( $this->table( 'interventi' ), $customer_id ) ),
			'minuti_totali' => (int) $this->get_var( 'SELECT COALESCE(SUM(durata_minuti), 0) FROM %i WHERE cliente_id = %d', array( $this->table( 'interventi' ), $customer_id ) ),
			'dispositivi' => $this->get_results( 'SELECT * FROM %i WHERE cliente_id = %d ORDER BY data_creazione DESC, id DESC LIMIT 6', array( $this->table( 'dispositivi' ), $customer_id ) ),
			'interventi' => $this->get_results(
				"SELECT i.*, GROUP_CONCAT(CONCAT_WS(' ', d.tipo, d.marca, d.modello) SEPARATOR ', ') AS dispositivi FROM %i i LEFT JOIN %i r ON r.intervento_id = i.id LEFT JOIN %i d ON d.id = r.dispositivo_id WHERE i.cliente_id = %d GROUP BY i.id ORDER BY i.data_intervento DESC, i.id DESC LIMIT 6",
				array( $this->table( 'interventi' ), $this->raw_table( 'interventi_dispositivi' ), $this->table( 'dispositivi' ), $customer_id )
			),
			'pacchetto' => $this->get_row(
				"SELECT p.*, COUNT(CASE WHEN i.conta_pacchetto = 1 THEN 1 END) AS utilizzati FROM %i p LEFT JOIN %i i ON i.pacchetto_id = p.id WHERE p.cliente_id = %d AND p.stato = %s GROUP BY p.id ORDER BY p.data_attivazione DESC, p.id DESC LIMIT 1",
				array( $this->table( 'pacchetti' ), $this->table( 'interventi' ), $customer_id, 'Attivo' )
			),
		);
	}

	/** Return device detail data without queries in the template. */
	public function get_device_admin_summary( $device_id ) {
		$device = $this->get_row(
			'SELECT d.*, c.nome AS cliente_nome, c.id AS cliente_id_reale FROM %i d INNER JOIN %i c ON c.id = d.cliente_id WHERE d.id = %d',
			array( $this->table( 'dispositivi' ), $this->table( 'clienti' ), $device_id )
		);
		if ( ! $device ) {
			return null;
		}
		$interventions = $this->get_results(
			'SELECT i.* FROM %i i INNER JOIN %i r ON r.intervento_id = i.id WHERE r.dispositivo_id = %d ORDER BY i.data_intervento DESC, i.id DESC',
			array( $this->table( 'interventi' ), $this->raw_table( 'interventi_dispositivi' ), $device_id )
		);
		$attachments = array();
		foreach ( $interventions as $intervention ) {
			$attachments[ $intervention->id ] = $this->get_results(
				'SELECT * FROM %i WHERE intervento_id = %d ORDER BY id DESC',
				array( $this->raw_table( 'interventi_allegati' ), $intervention->id )
			);
		}
		return array(
			'dispositivo' => $device,
			'interventi' => $interventions,
			'allegati' => $attachments,
			'scadenze' => $this->get_results( 'SELECT * FROM %i WHERE dispositivo_id = %d AND stato = %s ORDER BY data_scadenza ASC', array( $this->table( 'scadenze' ), $device_id, 'Attiva' ) ),
		);
	}

	/* Administrative repositories. */
	public function count_customer_devices( $customer_id ) {
		return (int) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE cliente_id = %d',
			array( $this->table( 'dispositivi' ), $customer_id )
		);
	}

	public function get_customer( $customer_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d',
			array( $this->table( 'clienti' ), $customer_id )
		);
	}

	public function find_customer_by_user( $user_id, $exclude_customer_id = 0 ) {
		return (int) $this->get_var(
			'SELECT id FROM %i WHERE user_id = %d AND id <> %d LIMIT 1',
			array( $this->table( 'clienti' ), $user_id, $exclude_customer_id )
		);
	}

	public function get_customers_admin() {
		return $this->get_results(
			'SELECT c.*, u.display_name AS utente_nome, u.user_email AS utente_email
			FROM %i c
			LEFT JOIN %i u ON u.ID = c.user_id
			ORDER BY c.id DESC',
			array( $this->table( 'clienti' ), $this->wpdb->users )
		);
	}

	public function get_device( $device_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d',
			array( $this->table( 'dispositivi' ), $device_id )
		);
	}

	public function get_devices_admin( $customer_id = 0 ) {
		if ( $customer_id ) {
			return $this->get_results(
				'SELECT d.*, c.nome AS cliente_nome
				FROM %i d INNER JOIN %i c ON c.id = d.cliente_id
				WHERE d.cliente_id = %d ORDER BY d.id DESC',
				array( $this->table( 'dispositivi' ), $this->table( 'clienti' ), $customer_id )
			);
		}
		return $this->get_results(
			'SELECT d.*, c.nome AS cliente_nome
			FROM %i d INNER JOIN %i c ON c.id = d.cliente_id
			ORDER BY d.id DESC',
			array( $this->table( 'dispositivi' ), $this->table( 'clienti' ) )
		);
	}

	public function count_package_interventions( $package_id ) {
		return (int) $this->get_var(
			'SELECT COUNT(*) FROM %i WHERE pacchetto_id = %d',
			array( $this->table( 'interventi' ), $package_id )
		);
	}

	public function get_package( $package_id ) {
		return $this->get_row(
			'SELECT * FROM %i WHERE id = %d',
			array( $this->table( 'pacchetti' ), $package_id )
		);
	}

	public function get_packages_admin( $customer_id = 0 ) {
		$query = 'SELECT p.*, c.nome AS cliente_nome,
			COUNT(CASE WHEN i.conta_pacchetto = 1 THEN 1 END) AS utilizzati
			FROM %i p
			INNER JOIN %i c ON c.id = p.cliente_id
			LEFT JOIN %i i ON i.pacchetto_id = p.id';
		$args = array( $this->table( 'pacchetti' ), $this->table( 'clienti' ), $this->table( 'interventi' ) );
		if ( $customer_id ) {
			$query .= ' WHERE p.cliente_id = %d';
			$args[] = $customer_id;
		}
		$query .= ' GROUP BY p.id ORDER BY p.data_attivazione DESC, p.id DESC';
		return $this->get_results( $query, $args );
	}

	public function get_ticket_mail_data( $ticket_id ) {
		return $this->get_row(
			'SELECT r.*, c.email, c.nome FROM %i r
			INNER JOIN %i c ON c.id = r.cliente_id WHERE r.id = %d',
			array( $this->table( 'richieste' ), $this->table( 'clienti' ), $ticket_id )
		);
	}

	public function get_ticket_state( $ticket_id ) {
		return (string) $this->get_var(
			'SELECT stato FROM %i WHERE id = %d',
			array( $this->table( 'richieste' ), $ticket_id )
		);
	}

	public function get_tickets_admin( $status = '' ) {
		$query = 'SELECT r.*, c.nome AS cliente_nome, c.email AS cliente_email,
			CONCAT_WS(%s, d.tipo, d.marca, d.modello) AS dispositivo_nome
			FROM %i r INNER JOIN %i c ON c.id = r.cliente_id
			LEFT JOIN %i d ON d.id = r.dispositivo_id';
		$args = array( ' ', $this->table( 'richieste' ), $this->table( 'clienti' ), $this->table( 'dispositivi' ) );
		if ( '' !== $status ) {
			$query .= ' WHERE r.stato = %s';
			$args[] = $status;
		}
		$query .= ' ORDER BY r.data_creazione DESC, r.id DESC';
		return $this->get_results( $query, $args );
	}

	public function get_ticket_comments_map( array $ticket_ids ) {
		$map = array();
		foreach ( array_map( 'absint', $ticket_ids ) as $ticket_id ) {
			$map[ $ticket_id ] = $this->get_results(
				'SELECT * FROM %i WHERE richiesta_id = %d ORDER BY data_creazione ASC, id ASC',
				array( $this->table( 'ticket_commenti' ), $ticket_id )
			);
		}
		return $map;
	}

	public function get_ticket_attachments_map( array $ticket_ids ) {
		$map = array();
		foreach ( array_map( 'absint', $ticket_ids ) as $ticket_id ) {
			$map[ $ticket_id ] = $this->get_results(
				'SELECT * FROM %i WHERE richiesta_id = %d ORDER BY id ASC',
				array( $this->table( 'ticket_allegati' ), $ticket_id )
			);
		}
		return $map;
	}

	public function get_intervention_start( $intervention_id ) {
		return (string) $this->get_var(
			'SELECT ora_inizio_effettiva FROM %i WHERE id = %d',
			array( $this->table( 'interventi' ), $intervention_id )
		);
	}



	public function global_search( $query, $tenant_id ) {
		$like = '%' . $this->wpdb->esc_like( $query ) . '%';
		return array(
			'customers' => $this->get_results( 'SELECT * FROM %i WHERE tenant_id = %d AND (nome LIKE %s OR email LIKE %s OR telefono LIKE %s) ORDER BY nome LIMIT 30', array( $this->table( 'clienti' ), $tenant_id, $like, $like, $like ) ),
			'devices' => $this->get_results( 'SELECT d.*, c.nome AS cliente_nome FROM %i d INNER JOIN %i c ON c.id = d.cliente_id WHERE d.tenant_id = %d AND (d.tipo LIKE %s OR d.marca LIKE %s OR d.modello LIKE %s OR d.seriale LIKE %s OR d.sistema LIKE %s OR c.nome LIKE %s) ORDER BY d.id DESC LIMIT 50', array( $this->table( 'dispositivi' ), $this->table( 'clienti' ), $tenant_id, $like, $like, $like, $like, $like, $like ) ),
			'interventions' => $this->get_results( 'SELECT i.*, c.nome AS cliente_nome FROM %i i INNER JOIN %i c ON c.id = i.cliente_id WHERE i.tenant_id = %d AND (i.tipo LIKE %s OR i.descrizione LIKE %s OR i.tecnico LIKE %s OR c.nome LIKE %s) ORDER BY i.data_intervento DESC LIMIT 30', array( $this->table( 'interventi' ), $this->table( 'clienti' ), $tenant_id, $like, $like, $like, $like ) ),
		);
	}

	public function get_api_summary( $tenant_id ) {
		return array(
			'clienti' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d', array( $this->table( 'clienti' ), $tenant_id ) ),
			'dispositivi' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d', array( $this->table( 'dispositivi' ), $tenant_id ) ),
			'interventi' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d', array( $this->table( 'interventi' ), $tenant_id ) ),
			'ticket' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d', array( $this->table( 'richieste' ), $tenant_id ) ),
			'contratti' => (int) $this->get_var( 'SELECT COUNT(*) FROM %i WHERE tenant_id = %d', array( $this->table( 'contratti' ), $tenant_id ) ),
		);
	}

	public function api_search( $query, $tenant_id ) {
		$like = '%' . $this->wpdb->esc_like( $query ) . '%';
		return array(
			'clienti' => $this->get_results( 'SELECT id, nome, email, telefono FROM %i WHERE tenant_id = %d AND (nome LIKE %s OR email LIKE %s OR telefono LIKE %s) ORDER BY nome LIMIT 10', array( $this->table( 'clienti' ), $tenant_id, $like, $like, $like ) ),
			'dispositivi' => $this->get_results( 'SELECT d.id, d.tipo, d.marca, d.modello, d.seriale, c.nome AS cliente FROM %i d INNER JOIN %i c ON c.id = d.cliente_id WHERE d.tenant_id = %d AND (d.tipo LIKE %s OR d.marca LIKE %s OR d.modello LIKE %s OR d.seriale LIKE %s) ORDER BY d.id DESC LIMIT 10', array( $this->table( 'dispositivi' ), $this->table( 'clienti' ), $tenant_id, $like, $like, $like, $like ) ),
		);
	}
}
