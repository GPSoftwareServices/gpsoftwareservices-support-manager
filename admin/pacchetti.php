<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db = new GPSUMA_DB();
$gpsuma_id_modifica = GPSUMA_Request::get_int( 'modifica' );
$gpsuma_id_elimina = GPSUMA_Request::get_int( 'elimina' );
$gpsuma_filtro_cliente = GPSUMA_Request::get_int( 'cliente' );
$gpsuma_messaggio = '';
$gpsuma_tipo_messaggio = 'success';

if ( $gpsuma_id_elimina ) {
	check_admin_referer( 'gpsuma_elimina_pacchetto_' . $gpsuma_id_elimina );
	if ( $gpsuma_db->count_package_interventions( $gpsuma_id_elimina ) > 0 ) {
		$gpsuma_messaggio = 'Il pacchetto non può essere eliminato perché è collegato a uno o più interventi. Puoi impostarlo come Terminato.';
		$gpsuma_tipo_messaggio = 'error';
	} else {
		$gpsuma_deleted = $gpsuma_db->delete( 'pacchetti', array( 'id' => $gpsuma_id_elimina ), array( '%d' ) );
		$gpsuma_messaggio = false !== $gpsuma_deleted ? 'Pacchetto eliminato correttamente.' : 'Errore durante l’eliminazione del pacchetto.';
		$gpsuma_tipo_messaggio = false !== $gpsuma_deleted ? 'success' : 'error';
	}
}

if ( isset( $_POST['gpsuma_salva_pacchetto'] ) && check_admin_referer( 'gpsuma_pacchetto_save' ) ) {
	$gpsuma_cliente_id = GPSUMA_Request::post_int( 'cliente_id' );
	$gpsuma_nome = GPSUMA_Request::post_text( 'nome' );
	$gpsuma_illimitati = GPSUMA_Request::post_bool( 'interventi_illimitati' ) ? 1 : 0;
	$gpsuma_inclusi = $gpsuma_illimitati ? 0 : GPSUMA_Request::post_int( 'interventi_inclusi' );
	$gpsuma_attivazione = GPSUMA_Request::post_text( 'data_attivazione' );
	$gpsuma_scadenza = GPSUMA_Request::post_text( 'data_scadenza' );
	$gpsuma_stato = GPSUMA_Request::post_text( 'stato', 'Attivo' );
	if ( ! in_array( $gpsuma_stato, array( 'Attivo', 'Sospeso', 'Terminato' ), true ) ) { $gpsuma_stato = 'Attivo'; }
	if ( ! $gpsuma_db->customer_exists( $gpsuma_cliente_id ) || ! $gpsuma_nome || ( ! $gpsuma_illimitati && ! $gpsuma_inclusi ) || ! $gpsuma_attivazione ) {
		$gpsuma_messaggio = 'Compila cliente, nome, numero di interventi oppure seleziona interventi illimitati, e data di attivazione.';
		$gpsuma_tipo_messaggio = 'error';
	} elseif ( $gpsuma_scadenza && $gpsuma_scadenza < $gpsuma_attivazione ) {
		$gpsuma_messaggio = 'La data di scadenza non può precedere la data di attivazione.';
		$gpsuma_tipo_messaggio = 'error';
	} else {
		$gpsuma_dati = array(
			'cliente_id' => $gpsuma_cliente_id, 'nome' => $gpsuma_nome, 'interventi_inclusi' => $gpsuma_inclusi,
			'interventi_illimitati' => $gpsuma_illimitati, 'data_attivazione' => $gpsuma_attivazione,
			'data_scadenza' => $gpsuma_scadenza ? $gpsuma_scadenza : null, 'stato' => $gpsuma_stato,
			'note' => GPSUMA_Request::post_textarea( 'note' ),
		);
		$gpsuma_formati = array( '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s' );
		$gpsuma_ok = $gpsuma_id_modifica
			? $gpsuma_db->update( 'pacchetti', $gpsuma_dati, array( 'id' => $gpsuma_id_modifica ), $gpsuma_formati, array( '%d' ) )
			: $gpsuma_db->insert( 'pacchetti', $gpsuma_dati, $gpsuma_formati );
		$gpsuma_messaggio = false !== $gpsuma_ok ? ( $gpsuma_id_modifica ? 'Pacchetto aggiornato correttamente.' : 'Pacchetto creato correttamente.' ) : 'Errore durante il salvataggio.';
		$gpsuma_tipo_messaggio = false !== $gpsuma_ok ? 'success' : 'error';
	}
}

$gpsuma_clienti = $gpsuma_db->get_customers_admin();
$gpsuma_pacchetto = $gpsuma_id_modifica ? $gpsuma_db->get_package( $gpsuma_id_modifica ) : null;
$gpsuma_elenco = $gpsuma_db->get_packages_admin( $gpsuma_filtro_cliente );
if ( ! function_exists( 'gpsuma_pac_val' ) ) {
	function gpsuma_pac_val( $object, $field, $default = '' ) {
		return $object && isset( $object->$field ) ? $object->$field : $default;
	}
}
?>
<div class="wrap gpsuma-wrapper"><h1>📦 Pacchetti di assistenza</h1>
<?php if($gpsuma_messaggio): ?><div class="notice notice-<?php echo esc_attr($gpsuma_tipo_messaggio); ?> is-dismissible"><p><?php echo esc_html($gpsuma_messaggio); ?></p></div><?php endif; ?>
<div class="gpsuma-box"><h2><?php echo $gpsuma_pacchetto?'Modifica pacchetto':'Nuovo pacchetto'; ?></h2>
<form method="post"><?php wp_nonce_field('gpsuma_pacchetto_save'); ?><div class="gpsuma-form-grid">
<p><label>Cliente *</label><select name="cliente_id" required><option value="">Seleziona cliente</option><?php $gpsuma_cv=$gpsuma_pacchetto?(int)$gpsuma_pacchetto->cliente_id:(isset($_POST['cliente_id'])?absint($_POST['cliente_id']):0); foreach($gpsuma_clienti as $gpsuma_c): ?><option value="<?php echo esc_attr($gpsuma_c->id); ?>" <?php selected($gpsuma_cv,(int)$gpsuma_c->id); ?>><?php echo esc_html($gpsuma_c->nome); ?></option><?php endforeach; ?></select></p>
<p><label>Nome pacchetto *</label><input type="text" name="nome" required placeholder="Es. Pacchetto 10 interventi" value="<?php echo esc_attr(gpsuma_pac_val($gpsuma_pacchetto,'nome')); ?>"></p>
<p><label>Numero interventi</label><input type="number" min="1" name="interventi_inclusi" id="gpsuma-interventi-inclusi" value="<?php echo esc_attr(gpsuma_pac_val($gpsuma_pacchetto,'interventi_inclusi',10)); ?>" <?php disabled((int)gpsuma_pac_val($gpsuma_pacchetto,'interventi_illimitati',0),1); ?>></p><p><label><input type="checkbox" name="interventi_illimitati" id="gpsuma-interventi-illimitati" value="1" <?php checked((int)gpsuma_pac_val($gpsuma_pacchetto,'interventi_illimitati',0),1); ?>> Interventi illimitati</label><br><small>Usa questa opzione per i pacchetti annuali senza limite di interventi.</small></p>
<p><label>Data attivazione *</label><input type="date" name="data_attivazione" required value="<?php echo esc_attr(gpsuma_pac_val($gpsuma_pacchetto,'data_attivazione',current_time('Y-m-d'))); ?>"></p>
<p><label>Data scadenza</label><input type="date" name="data_scadenza" value="<?php echo esc_attr(gpsuma_pac_val($gpsuma_pacchetto,'data_scadenza')); ?>"></p>
<p><label>Stato</label><select name="stato"><?php foreach(array('Attivo','Sospeso','Terminato') as $gpsuma_st): ?><option <?php selected(gpsuma_pac_val($gpsuma_pacchetto,'stato','Attivo'),$gpsuma_st); ?>><?php echo esc_html($gpsuma_st); ?></option><?php endforeach; ?></select></p>
</div><p><label>Note</label><textarea class="large-text" rows="3" name="note"><?php echo esc_textarea(gpsuma_pac_val($gpsuma_pacchetto,'note')); ?></textarea></p>
<p><button class="button button-primary" name="gpsuma_salva_pacchetto"> <?php echo $gpsuma_pacchetto?'Aggiorna pacchetto':'Salva pacchetto'; ?></button><?php if($gpsuma_pacchetto): ?> <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-pacchetti')); ?>">Annulla</a><?php endif; ?></p></form></div>
<div class="gpsuma-box"><div class="gpsuma-box-header"><h2>Elenco pacchetti</h2><form class="gpsuma-filter-form"><input type="hidden" name="page" value="gpsuma-pacchetti"><select name="cliente"><option value="0">Tutti i clienti</option><?php foreach($gpsuma_clienti as $gpsuma_c): ?><option value="<?php echo esc_attr($gpsuma_c->id); ?>" <?php selected($gpsuma_filtro_cliente,(int)$gpsuma_c->id); ?>><?php echo esc_html($gpsuma_c->nome); ?></option><?php endforeach; ?></select><button class="button">Filtra</button></form></div>
<div class="gpsuma-table-scroll"><table class="widefat striped"><thead><tr><th>Cliente</th><th>Pacchetto</th><th>Periodo</th><th>Utilizzo</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>
<?php if(!$gpsuma_elenco): ?><tr><td colspan="6">Nessun pacchetto presente.</td></tr><?php endif; foreach($gpsuma_elenco as $gpsuma_p): $gpsuma_usati=(int)$gpsuma_p->utilizzati; $gpsuma_illimitato=!empty($gpsuma_p->interventi_illimitati); $gpsuma_tot=(int)$gpsuma_p->interventi_inclusi; $gpsuma_residui=$gpsuma_illimitato?null:max(0,$gpsuma_tot-$gpsuma_usati); $gpsuma_perc=(!$gpsuma_illimitato&&$gpsuma_tot)?min(100,round($gpsuma_usati/$gpsuma_tot*100)):0; ?><tr><td><?php echo esc_html($gpsuma_p->cliente_nome); ?></td><td><strong><?php echo esc_html($gpsuma_p->nome); ?></strong></td><td><?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_p->data_attivazione))); ?><?php if($gpsuma_p->data_scadenza): ?><br><small>Scade: <?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_p->data_scadenza))); ?></small><?php endif; ?></td><td><?php if($gpsuma_illimitato): ?><strong>∞ Illimitati</strong><br><small><?php echo esc_html($gpsuma_usati); ?> interventi registrati</small><?php else: ?><strong><?php echo esc_html($gpsuma_usati.' / '.$gpsuma_tot); ?></strong> · <?php echo esc_html($gpsuma_residui); ?> residui<div class="gpsuma-progress"><span style="width:<?php echo esc_attr($gpsuma_perc); ?>%"></span></div><?php endif; ?></td><td><?php echo esc_html($gpsuma_p->stato); ?><?php if(!$gpsuma_illimitato && $gpsuma_residui===0): ?><br><small>Esaurito</small><?php endif; ?></td><td class="gpsuma-actions"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-pacchetti&modifica='.$gpsuma_p->id)); ?>">✏️ Modifica</a> <a class="button" onclick="return confirm('Eliminare questo pacchetto?')" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gpsuma-pacchetti&elimina='.$gpsuma_p->id),'gpsuma_elimina_pacchetto_'.$gpsuma_p->id)); ?>">🗑 Elimina</a></td></tr><?php endforeach; ?></tbody></table></div></div></div>
