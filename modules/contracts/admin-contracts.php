<?php
/**
 * Contracts administration screen.
 *
 * @package GestioneAssistenzaTecnica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You are not allowed to access this page.', 'gpsoftwareservices-support-manager' ) );
}

$gpsuma_db        = new GPSUMA_DB();
$gpsuma_tenant_id = gpsuma_tenant_id();

if ( isset( $_POST['gpsuma_save_contract'] ) ) {
	check_admin_referer( 'gpsuma_contract' );

	$gpsuma_contract_id = GPSUMA_Request::post_int( 'id' );
	$gpsuma_unlimited   = GPSUMA_Request::post_bool( 'interventi_illimitati' );
	$gpsuma_data        = array(
		'tenant_id'               => $gpsuma_tenant_id,
		'cliente_id'              => GPSUMA_Request::post_int( 'cliente_id' ),
		'nome'                    => GPSUMA_Request::post_text( 'nome' ),
		'tipo'                    => GPSUMA_Request::post_text( 'tipo', 'Personalizzato' ),
		'data_inizio'             => GPSUMA_Request::post_text( 'data_inizio' ),
		'data_fine'               => GPSUMA_Request::post_text( 'data_fine' ) ?: null,
		'stato'                   => GPSUMA_Request::post_text( 'stato', 'Attivo' ),
		'ore_incluse'             => GPSUMA_Request::post_float( 'ore_incluse' ),
		'interventi_inclusi'      => $gpsuma_unlimited ? 0 : GPSUMA_Request::post_int( 'interventi_inclusi' ),
		'interventi_illimitati'   => $gpsuma_unlimited ? 1 : 0,
		'valore_contratto'        => GPSUMA_Request::post_float( 'valore_contratto' ),
		'rinnovo_automatico'      => GPSUMA_Request::post_bool( 'rinnovo_automatico' ) ? 1 : 0,
		'servizi_inclusi'         => GPSUMA_Request::post_textarea( 'servizi_inclusi' ),
		'esclusioni'              => GPSUMA_Request::post_textarea( 'esclusioni' ),
		'note'                    => GPSUMA_Request::post_textarea( 'note' ),
	);

	if ( $gpsuma_contract_id ) {
		$gpsuma_db->update( 'contratti', $gpsuma_data, array( 'id' => $gpsuma_contract_id, 'tenant_id' => $gpsuma_tenant_id ) );
	} else {
		$gpsuma_db->insert( 'contratti', $gpsuma_data );
	}

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Contract saved.', 'gpsoftwareservices-support-manager' ) . '</p></div>';
}

$gpsuma_delete_id = GPSUMA_Request::get_int( 'delete' );
if ( $gpsuma_delete_id ) {
	check_admin_referer( 'gpsuma_delete_contract_' . $gpsuma_delete_id );
	$gpsuma_contract = $gpsuma_db->get_contract( $gpsuma_delete_id, $gpsuma_tenant_id );

	if ( $gpsuma_contract ) {
		$gpsuma_linked_interventions = $gpsuma_db->count_contract_interventions( $gpsuma_delete_id );
		$gpsuma_attachment_ids       = $gpsuma_db->get_contract_attachment_ids( $gpsuma_delete_id );

		$gpsuma_db->begin();
		$gpsuma_db->update( 'interventi', array( 'contratto_id' => null ), array( 'contratto_id' => $gpsuma_delete_id ), array( '%d' ), array( '%d' ) );
		$gpsuma_db->delete( 'contratti_allegati', array( 'contratto_id' => $gpsuma_delete_id ), array( '%d' ) );
		$gpsuma_deleted = $gpsuma_db->delete( 'contratti', array( 'id' => $gpsuma_delete_id, 'tenant_id' => $gpsuma_tenant_id ), array( '%d', '%d' ) );

		if ( false !== $gpsuma_deleted && $gpsuma_deleted > 0 ) {
			$gpsuma_db->commit();
			foreach ( $gpsuma_attachment_ids as $gpsuma_attachment_id ) {
				wp_delete_attachment( absint( $gpsuma_attachment_id ), true );
			}
			$gpsuma_message = __( 'Contract deleted successfully.', 'gpsoftwareservices-support-manager' );
			if ( $gpsuma_linked_interventions > 0 ) {
				$gpsuma_message .= ' ' . sprintf(
					/* translators: %d: number of detached interventions. */
					_n( '%d intervention was detached.', '%d interventions were detached.', $gpsuma_linked_interventions, 'gpsoftwareservices-support-manager' ),
					$gpsuma_linked_interventions
				);
			}
			echo '<div class="notice notice-success"><p>' . esc_html( $gpsuma_message ) . '</p></div>';
		} else {
			$gpsuma_db->rollback();
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The contract could not be deleted.', 'gpsoftwareservices-support-manager' ) . '</p></div>';
		}
	} else {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Contract not found or access denied.', 'gpsoftwareservices-support-manager' ) . '</p></div>';
	}
}

$gpsuma_renew_id = GPSUMA_Request::get_int( 'renew' );
if ( $gpsuma_renew_id ) {
	check_admin_referer( 'gpsuma_renew_' . $gpsuma_renew_id );
	$gpsuma_old = $gpsuma_db->get_contract( $gpsuma_renew_id, $gpsuma_tenant_id, ARRAY_A );
	if ( $gpsuma_old ) {
		unset( $gpsuma_old['id'], $gpsuma_old['data_creazione'] );
		$gpsuma_start                = ! empty( $gpsuma_old['data_fine'] ) ? strtotime( $gpsuma_old['data_fine'] . ' +1 day' ) : current_time( 'timestamp' );
		$gpsuma_old['data_inizio']   = wp_date( 'Y-m-d', $gpsuma_start );
		$gpsuma_old['data_fine']     = wp_date( 'Y-m-d', strtotime( '+1 year -1 day', $gpsuma_start ) );
		$gpsuma_old['stato']         = 'Attivo';
		$gpsuma_db->insert( 'contratti', $gpsuma_old );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Renewal created.', 'gpsoftwareservices-support-manager' ) . '</p></div>';
	}
}

$gpsuma_edit_id = GPSUMA_Request::get_int( 'edit' );
$gpsuma_edit    = $gpsuma_edit_id ? $gpsuma_db->get_contract( $gpsuma_edit_id, $gpsuma_tenant_id ) : null;
$gpsuma_clients = $gpsuma_db->get_customer_choices( $gpsuma_tenant_id );
$gpsuma_rows    = $gpsuma_db->get_contracts_with_usage( $gpsuma_tenant_id );
?>
<div class="wrap gpsuma-wrap">
	<h1><?php echo esc_html__( 'Assistance contracts', 'gpsoftwareservices-support-manager' ); ?></h1>
	<div class="gpsuma-card">
		<h2><?php echo esc_html( $gpsuma_edit ? __( 'Edit contract', 'gpsoftwareservices-support-manager' ) : __( 'New contract', 'gpsoftwareservices-support-manager' ) ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'gpsuma_contract' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $gpsuma_edit->id ?? 0 ); ?>">
			<div class="gpsuma-form-grid">
				<p><label><?php echo esc_html__( 'Customer', 'gpsoftwareservices-support-manager' ); ?> *</label>
				<select required name="cliente_id"><option value=""><?php echo esc_html__( 'Select', 'gpsoftwareservices-support-manager' ); ?></option>
				<?php foreach ( $gpsuma_clients as $gpsuma_client ) : ?>
					<option value="<?php echo esc_attr( (int) $gpsuma_client->id ); ?>" <?php selected( $gpsuma_edit->cliente_id ?? 0, $gpsuma_client->id ); ?>><?php echo esc_html( $gpsuma_client->nome ); ?></option>
				<?php endforeach; ?></select></p>
				<p><label><?php echo esc_html__( 'Contract name', 'gpsoftwareservices-support-manager' ); ?> *</label><input required name="nome" value="<?php echo esc_attr( $gpsuma_edit->nome ?? '' ); ?>"></p>
				<p><label><?php echo esc_html__( 'Type', 'gpsoftwareservices-support-manager' ); ?></label><select name="tipo">
				<?php foreach ( array( 'Bronze', 'Silver', 'Gold', 'Premium', 'Personalizzato' ) as $type ) : ?>
					<option <?php selected( $gpsuma_edit->tipo ?? 'Personalizzato', $type ); ?>><?php echo esc_html( $type ); ?></option>
				<?php endforeach; ?></select></p>
				<p><label><?php echo esc_html__( 'Status', 'gpsoftwareservices-support-manager' ); ?></label><select name="stato">
				<?php foreach ( array( 'Bozza', 'Attivo', 'Sospeso', 'Scaduto', 'Chiuso' ) as $status ) : ?>
					<option <?php selected( $gpsuma_edit->stato ?? 'Attivo', $status ); ?>><?php echo esc_html( $status ); ?></option>
				<?php endforeach; ?></select></p>
				<p><label><?php echo esc_html__( 'Start date', 'gpsoftwareservices-support-manager' ); ?> *</label><input required type="date" name="data_inizio" value="<?php echo esc_attr( $gpsuma_edit->data_inizio ?? wp_date( 'Y-m-d' ) ); ?>"></p>
				<p><label><?php echo esc_html__( 'End date', 'gpsoftwareservices-support-manager' ); ?></label><input type="date" name="data_fine" value="<?php echo esc_attr( $gpsuma_edit->data_fine ?? '' ); ?>"></p>
				<p><label><?php echo esc_html__( 'Included hours', 'gpsoftwareservices-support-manager' ); ?></label><input type="number" step="0.25" min="0" name="ore_incluse" value="<?php echo esc_attr( $gpsuma_edit->ore_incluse ?? 0 ); ?>"></p>
				<p><label><?php echo esc_html__( 'Included interventions', 'gpsoftwareservices-support-manager' ); ?></label><input type="number" min="0" name="interventi_inclusi" value="<?php echo esc_attr( $gpsuma_edit->interventi_inclusi ?? 0 ); ?>"></p>
				<p><label><input type="checkbox" name="interventi_illimitati" <?php checked( $gpsuma_edit->interventi_illimitati ?? 0, 1 ); ?>> <?php echo esc_html__( 'Unlimited interventions', 'gpsoftwareservices-support-manager' ); ?></label></p>
				<p><label><?php echo esc_html__( 'Contract value', 'gpsoftwareservices-support-manager' ); ?> €</label><input type="number" step="0.01" min="0" name="valore_contratto" value="<?php echo esc_attr( $gpsuma_edit->valore_contratto ?? 0 ); ?>"></p>
				<p><label><input type="checkbox" name="rinnovo_automatico" <?php checked( $gpsuma_edit->rinnovo_automatico ?? 0, 1 ); ?>> <?php echo esc_html__( 'Automatic renewal', 'gpsoftwareservices-support-manager' ); ?></label></p>
			</div>
			<div class="gpsuma-form-grid">
				<p><label><?php echo esc_html__( 'Included services', 'gpsoftwareservices-support-manager' ); ?></label><textarea name="servizi_inclusi" rows="5"><?php echo esc_textarea( $gpsuma_edit->servizi_inclusi ?? '' ); ?></textarea></p>
				<p><label><?php echo esc_html__( 'Exclusions', 'gpsoftwareservices-support-manager' ); ?></label><textarea name="esclusioni" rows="5"><?php echo esc_textarea( $gpsuma_edit->esclusioni ?? '' ); ?></textarea></p>
			</div>
			<p><label><?php echo esc_html__( 'Notes', 'gpsoftwareservices-support-manager' ); ?></label><textarea name="note"><?php echo esc_textarea( $gpsuma_edit->note ?? '' ); ?></textarea></p>
			<button class="button button-primary" name="gpsuma_save_contract"><?php echo esc_html__( 'Save contract', 'gpsoftwareservices-support-manager' ); ?></button>
		</form>
	</div>
	<div class="gpsuma-card"><h2><?php echo esc_html__( 'Contracts', 'gpsoftwareservices-support-manager' ); ?></h2>
	<table class="widefat striped"><thead><tr><th><?php echo esc_html__( 'Customer / Contract', 'gpsoftwareservices-support-manager' ); ?></th><th><?php echo esc_html__( 'Period', 'gpsoftwareservices-support-manager' ); ?></th><th><?php echo esc_html__( 'Usage', 'gpsoftwareservices-support-manager' ); ?></th><th><?php echo esc_html__( 'Value', 'gpsoftwareservices-support-manager' ); ?></th><th><?php echo esc_html__( 'Status', 'gpsoftwareservices-support-manager' ); ?></th><th></th></tr></thead><tbody>
	<?php foreach ( $gpsuma_rows as $gpsuma_row ) :
		$gpsuma_hours     = round( $gpsuma_row->minuti_usati / 60, 2 );
		$gpsuma_remaining = max( 0, $gpsuma_row->ore_incluse - $gpsuma_hours );
		$gpsuma_unlimited = ! empty( $gpsuma_row->interventi_illimitati );
		$gpsuma_days      = $gpsuma_row->data_fine ? (int) floor( ( strtotime( $gpsuma_row->data_fine ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) : null;
	?>
	<tr><td><strong><?php echo esc_html( $gpsuma_row->nome ); ?></strong><br><?php echo esc_html( $gpsuma_row->cliente_nome . ' · ' . $gpsuma_row->tipo ); ?></td>
	<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $gpsuma_row->data_inizio ) ) ); ?><?php if ( $gpsuma_row->data_fine ) : ?><br>→ <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $gpsuma_row->data_fine ) ) ); ?><?php if ( null !== $gpsuma_days && $gpsuma_days <= 30 ) : ?> <span class="gpsuma-badge gpsuma-badge-orange"><?php echo esc_html( $gpsuma_days < 0 ? __( 'Expired', 'gpsoftwareservices-support-manager' ) : $gpsuma_days . ' ' . __( 'days', 'gpsoftwareservices-support-manager' ) ); ?></span><?php endif; ?><?php endif; ?></td>
	<td><?php if ( $gpsuma_row->ore_incluse > 0 ) : ?><?php echo esc_html__( 'Hours:', 'gpsoftwareservices-support-manager' ); ?> <?php echo esc_html( $gpsuma_hours . ' / ' . $gpsuma_row->ore_incluse ); ?> (<?php echo esc_html( $gpsuma_remaining ); ?> <?php echo esc_html__( 'remaining', 'gpsoftwareservices-support-manager' ); ?>)<br><?php endif; ?>
	<?php if ( $gpsuma_unlimited ) : ?><?php echo esc_html__( 'Interventions:', 'gpsoftwareservices-support-manager' ); ?> <strong>∞ <?php echo esc_html__( 'Unlimited', 'gpsoftwareservices-support-manager' ); ?></strong> (<?php echo esc_html( $gpsuma_row->interventi_usati ); ?>)
	<?php elseif ( $gpsuma_row->interventi_inclusi > 0 ) : ?><?php echo esc_html__( 'Interventions:', 'gpsoftwareservices-support-manager' ); ?> <?php echo esc_html( $gpsuma_row->interventi_usati . ' / ' . $gpsuma_row->interventi_inclusi ); ?><?php endif; ?></td>
	<td>€ <?php echo esc_html( number_format_i18n( (float) $gpsuma_row->valore_contratto, 2 ) ); ?></td><td><?php echo esc_html( $gpsuma_row->stato ); ?></td>
	<td><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'gpsuma-contratti', 'edit' => $gpsuma_row->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html__( 'Edit', 'gpsoftwareservices-support-manager' ); ?></a>
	<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'gpsuma-contratti', 'renew' => $gpsuma_row->id ), admin_url( 'admin.php' ) ), 'gpsuma_renew_' . $gpsuma_row->id ) ); ?>"><?php echo esc_html__( 'Renew', 'gpsoftwareservices-support-manager' ); ?></a>
	<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'gpsuma-contratti', 'delete' => $gpsuma_row->id ), admin_url( 'admin.php' ) ), 'gpsuma_delete_contract_' . $gpsuma_row->id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Permanently delete this contract? Linked interventions will remain in history and will be detached.', 'gpsoftwareservices-support-manager' ) ); ?>');"><?php echo esc_html__( 'Delete', 'gpsoftwareservices-support-manager' ); ?></a></td></tr>
	<?php endforeach; ?>
	</tbody></table></div>
</div>
