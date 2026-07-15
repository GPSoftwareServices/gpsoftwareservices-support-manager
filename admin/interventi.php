<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You are not allowed to access this page.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db = new GPSUMA_DB();
$gpsuma_tenant_id = gpsuma_tenant_id();
$gpsuma_cliente_preselezionato = GPSUMA_Request::get_int( 'cliente_id' );
$gpsuma_dispositivo_preselezionato = GPSUMA_Request::get_int( 'dispositivo_id' );
$gpsuma_id_modifica = GPSUMA_Request::get_int( 'modifica' );
$gpsuma_id_elimina = GPSUMA_Request::get_int( 'elimina' );
$gpsuma_filtro_cliente = GPSUMA_Request::get_int( 'cliente' );
$gpsuma_messaggio = '';
$gpsuma_tipo_messaggio = 'success';

if ( $gpsuma_id_elimina ) {
	check_admin_referer( 'gpsuma_elimina_intervento_' . $gpsuma_id_elimina );
	foreach ( $gpsuma_db->get_intervention_attachment_ids( $gpsuma_id_elimina ) as $gpsuma_attachment_id ) {
		wp_delete_attachment( absint( $gpsuma_attachment_id ), true );
	}
	$gpsuma_db->delete_intervention( $gpsuma_id_elimina );
	$gpsuma_messaggio = __( 'Intervention deleted successfully.', 'gpsoftwareservices-support-manager' );
}

if ( isset( $_POST['gpsuma_salva_intervento'] ) ) {
	check_admin_referer( 'gpsuma_intervento_save' );
	$gpsuma_cliente_id = GPSUMA_Request::post_int( 'cliente_id' );
	$gpsuma_data = GPSUMA_Request::post_text( 'data_intervento' );
	$gpsuma_descrizione = GPSUMA_Request::post_textarea( 'descrizione' );

	if ( ! $gpsuma_db->customer_exists( $gpsuma_cliente_id ) || ! $gpsuma_data || ! $gpsuma_descrizione ) {
		$gpsuma_messaggio = __( 'Complete customer, date and description.', 'gpsoftwareservices-support-manager' );
		$gpsuma_tipo_messaggio = 'error';
	} else {
		$gpsuma_pacchetto_id = GPSUMA_Request::post_int( 'pacchetto_id' );
		$gpsuma_contratto_id = GPSUMA_Request::post_int( 'contratto_id' );
		if ( $gpsuma_pacchetto_id && ! $gpsuma_db->package_belongs_to_customer( $gpsuma_pacchetto_id, $gpsuma_cliente_id ) ) {
			$gpsuma_pacchetto_id = 0;
		}
		$gpsuma_existing = $gpsuma_id_modifica ? $gpsuma_db->get_intervention( $gpsuma_id_modifica ) : null;
		$gpsuma_dati = array(
			'tenant_id' => $gpsuma_tenant_id,
			'cliente_id' => $gpsuma_cliente_id,
			'pacchetto_id' => $gpsuma_pacchetto_id ?: null,
			'contratto_id' => $gpsuma_contratto_id ?: null,
			'conta_pacchetto' => ( $gpsuma_pacchetto_id && GPSUMA_Request::post_bool( 'conta_pacchetto' ) ) ? 1 : 0,
			'data_intervento' => $gpsuma_data,
			'ora_intervento' => GPSUMA_Request::post_text( 'ora_intervento' ) ?: null,
			'ora_fine' => GPSUMA_Request::post_text( 'ora_fine' ) ?: null,
			'tipo' => GPSUMA_Request::post_text( 'tipo' ),
			'descrizione' => $gpsuma_descrizione,
			'durata_minuti' => GPSUMA_Request::post_int( 'durata_minuti' ),
			'durata_prevista_minuti' => GPSUMA_Request::post_int( 'durata_prevista_minuti' ),
			'tecnico' => GPSUMA_Request::post_text( 'tecnico' ),
			'ticket_id' => GPSUMA_Request::post_int( 'ticket_id' ) ?: null,
			'indirizzo_intervento' => GPSUMA_Request::post_textarea( 'indirizzo_intervento' ),
			'checklist' => wp_json_encode( GPSUMA_Request::post_text_array( 'checklist' ) ),
			'firma_cliente' => GPSUMA_Request::post_data_image( 'firma_cliente', $gpsuma_existing ? (string) $gpsuma_existing->firma_cliente : '' ),
			'costo_manodopera' => GPSUMA_Request::post_float( 'costo_manodopera' ),
			'costo_materiali' => GPSUMA_Request::post_float( 'costo_materiali' ),
			'costo_trasferta' => GPSUMA_Request::post_float( 'costo_trasferta' ),
			'sconto' => GPSUMA_Request::post_float( 'sconto' ),
			'aliquota_iva' => GPSUMA_Request::post_float( 'aliquota_iva', 22.0 ),
			'stato_pagamento' => GPSUMA_Request::post_text( 'stato_pagamento', 'Da pagare' ),
			'metodo_pagamento' => GPSUMA_Request::post_text( 'metodo_pagamento' ),
			'materiale' => GPSUMA_Request::post_textarea( 'materiale' ),
			'note_interne' => GPSUMA_Request::post_textarea( 'note_interne' ),
			'stato' => GPSUMA_Request::post_text( 'stato', 'Completato' ),
			'visibile_cliente' => GPSUMA_Request::post_bool( 'visibile_cliente' ) ? 1 : 0,
		);

		if ( $gpsuma_id_modifica ) {
			$gpsuma_ok = $gpsuma_db->update( 'interventi', $gpsuma_dati, array( 'id' => $gpsuma_id_modifica ), null, array( '%d' ) );
			$gpsuma_intervento_id = $gpsuma_id_modifica;
		} else {
			$gpsuma_ok = $gpsuma_db->insert( 'interventi', $gpsuma_dati );
			global $wpdb;
			$gpsuma_intervento_id = (int) $wpdb->insert_id;
		}

		if ( false !== $gpsuma_ok ) {
			$gpsuma_db->replace_intervention_devices( $gpsuma_intervento_id, $gpsuma_cliente_id, GPSUMA_Request::post_int_array( 'dispositivi' ) );

			if ( ! empty( $_FILES['gpsuma_allegati']['name'][0] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File uploads are validated by media_handle_upload().
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$gpsuma_files = $_FILES['gpsuma_allegati']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passed to WordPress media upload validation.
				foreach ( $gpsuma_files['name'] as $gpsuma_key => $gpsuma_name ) {
					if ( ! $gpsuma_name ) { continue; }
					$_FILES['gpsuma_singolo_allegato'] = array(
						'name' => sanitize_file_name( $gpsuma_files['name'][ $gpsuma_key ] ),
						'type' => sanitize_mime_type( $gpsuma_files['type'][ $gpsuma_key ] ),
						'tmp_name' => $gpsuma_files['tmp_name'][ $gpsuma_key ], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Temporary upload path generated by PHP.
						'error' => absint( $gpsuma_files['error'][ $gpsuma_key ] ),
						'size' => absint( $gpsuma_files['size'][ $gpsuma_key ] ),
					);
					$gpsuma_attachment_id = media_handle_upload( 'gpsuma_singolo_allegato', 0 );
					if ( ! is_wp_error( $gpsuma_attachment_id ) ) {
						$gpsuma_db->add_intervention_attachment( $gpsuma_intervento_id, $gpsuma_attachment_id, GPSUMA_Request::post_bool( 'allegati_visibili_cliente' ) );
					}
				}
				unset( $_FILES['gpsuma_singolo_allegato'] );
			}

			foreach ( GPSUMA_Request::post_int_array( 'elimina_allegati' ) as $gpsuma_attachment_id ) {
				if ( $gpsuma_db->remove_intervention_attachment( $gpsuma_intervento_id, $gpsuma_attachment_id ) ) {
					wp_delete_attachment( $gpsuma_attachment_id, true );
				}
			}
			$gpsuma_messaggio = $gpsuma_id_modifica ? __( 'Intervention updated successfully.', 'gpsoftwareservices-support-manager' ) : __( 'Intervention added successfully.', 'gpsoftwareservices-support-manager' );
			if ( ! $gpsuma_id_modifica ) { $_POST = array(); }
		} else {
			$gpsuma_messaggio = __( 'Database error while saving the intervention.', 'gpsoftwareservices-support-manager' );
			$gpsuma_tipo_messaggio = 'error';
		}
	}
}

$gpsuma_form_data = $gpsuma_db->get_intervention_form_data( $gpsuma_tenant_id );
$gpsuma_clienti = $gpsuma_form_data['clienti'];
$gpsuma_dispositivi = $gpsuma_form_data['dispositivi'];
$gpsuma_tickets = $gpsuma_form_data['tickets'];
$gpsuma_contratti = $gpsuma_form_data['contratti'];
$gpsuma_pacchetti = $gpsuma_form_data['pacchetti'];
$gpsuma_intervento = null;
$gpsuma_selezionati = $gpsuma_dispositivo_preselezionato ? array( $gpsuma_dispositivo_preselezionato ) : array();
$gpsuma_allegati = array();
if ( $gpsuma_id_modifica ) {
	$gpsuma_intervento = $gpsuma_db->get_intervention( $gpsuma_id_modifica );
	$gpsuma_selezionati = $gpsuma_db->get_intervention_device_ids( $gpsuma_id_modifica );
	$gpsuma_allegati = $gpsuma_db->get_intervention_attachments( $gpsuma_id_modifica );
}
$gpsuma_elenco = $gpsuma_db->get_interventions( $gpsuma_filtro_cliente );
if ( ! function_exists( 'gpsuma_val' ) ) {
	function gpsuma_val( $obj, $field, $default = '' ) { return $obj && isset( $obj->$field ) ? $obj->$field : $default; }
}
?>
<div class="wrap gpsuma-wrapper"><h1>🔧 Interventi</h1>
<?php if($gpsuma_messaggio): ?><div class="notice notice-<?php echo esc_attr($gpsuma_tipo_messaggio); ?> is-dismissible"><p><?php echo esc_html($gpsuma_messaggio); ?></p></div><?php endif; ?>
<div class="gpsuma-box"><h2><?php echo $gpsuma_intervento?'Modifica intervento':'Nuovo intervento'; ?></h2>
<form method="post" enctype="multipart/form-data"><?php wp_nonce_field('gpsuma_intervento_save'); ?>
<div class="gpsuma-form-grid">
<p><label>Cliente *</label><select name="cliente_id" id="gpsuma-intervento-cliente" required><option value="">Seleziona cliente</option><?php foreach($gpsuma_clienti as $gpsuma_c): $gpsuma_cv=$gpsuma_intervento?(int)$gpsuma_intervento->cliente_id:(isset($_POST['cliente_id'])?absint($_POST['cliente_id']):$gpsuma_cliente_preselezionato); ?><option value="<?php echo esc_attr($gpsuma_c->id); ?>" <?php selected($gpsuma_cv,(int)$gpsuma_c->id); ?>><?php echo esc_html($gpsuma_c->nome); ?></option><?php endforeach; ?></select></p>
<p><label>Data *</label><input type="date" name="data_intervento" required value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'data_intervento',wp_date('Y-m-d'))); ?>"></p>
<p><label>Ora inizio</label><input type="time" name="ora_intervento" value="<?php echo esc_attr(substr((string)gpsuma_val($gpsuma_intervento,'ora_intervento'),0,5)); ?>"></p>
<p><label>Ora fine prevista</label><input type="time" name="ora_fine" value="<?php echo esc_attr(substr((string)gpsuma_val($gpsuma_intervento,'ora_fine'),0,5)); ?>"></p>
<p><label>Tipo</label><select name="tipo"><?php foreach(array('Installazione','Configurazione','Riparazione','Aggiornamento','Manutenzione','Formazione','Backup','Rete','Altro') as $gpsuma_t): ?><option <?php selected(gpsuma_val($gpsuma_intervento,'tipo'),$gpsuma_t); ?>><?php echo esc_html($gpsuma_t); ?></option><?php endforeach; ?></select></p>
<p><label>Durata effettiva (minuti)</label><input type="number" min="0" name="durata_minuti" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'durata_minuti')); ?>"></p>
<p><label>Durata prevista (minuti)</label><input type="number" min="0" name="durata_prevista_minuti" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'durata_prevista_minuti',60)); ?>"></p>
<p><label>Tecnico</label><input type="text" name="tecnico" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'tecnico',wp_get_current_user()->display_name)); ?>"></p>
<p><label>Stato</label><select name="stato"><?php foreach(array('Pianificato','In corso','Completato','Annullato') as $gpsuma_st): ?><option <?php selected(gpsuma_val($gpsuma_intervento,'stato','Completato'),$gpsuma_st); ?>><?php echo esc_html($gpsuma_st); ?></option><?php endforeach; ?></select></p>
<p><label>Pacchetto assistenza</label><select name="pacchetto_id" id="gpsuma-intervento-pacchetto"><option value="">Nessun pacchetto</option><?php foreach($gpsuma_pacchetti as $gpsuma_p): $gpsuma_illimitato=!empty($gpsuma_p->interventi_illimitati); $gpsuma_res=$gpsuma_illimitato?null:max(0,(int)$gpsuma_p->interventi_inclusi-(int)$gpsuma_p->utilizzati); ?><option data-cliente="<?php echo esc_attr($gpsuma_p->cliente_id); ?>" value="<?php echo esc_attr($gpsuma_p->id); ?>" <?php selected((int)gpsuma_val($gpsuma_intervento,'pacchetto_id'),(int)$gpsuma_p->id); ?>><?php echo esc_html($gpsuma_p->nome.' — '.($gpsuma_illimitato?'∞ illimitati':$gpsuma_res.' residui').' ('.$gpsuma_p->stato.')'); ?></option><?php endforeach; ?></select></p><p><label>Contratto di assistenza</label><select name="contratto_id" id="gpsuma-intervento-contratto"><option value="">Nessun contratto</option><?php foreach($gpsuma_contratti as $gpsuma_ct): ?><option data-cliente="<?php echo esc_attr($gpsuma_ct->cliente_id); ?>" value="<?php echo esc_attr($gpsuma_ct->id); ?>" <?php selected((int)gpsuma_val($gpsuma_intervento,'contratto_id'),(int)$gpsuma_ct->id); ?>><?php echo esc_html($gpsuma_ct->nome.' ('.$gpsuma_ct->stato.')'); ?></option><?php endforeach; ?></select></p>
</div>
<div class="gpsuma-form-grid"><p><label>Ticket collegato</label><select name="ticket_id" id="gpsuma-ticket-id"><option value="">Nessun ticket</option><?php foreach($gpsuma_tickets as $gpsuma_tk): ?><option data-cliente="<?php echo esc_attr($gpsuma_tk->cliente_id); ?>" value="<?php echo esc_attr($gpsuma_tk->id); ?>" <?php selected((int)gpsuma_val($gpsuma_intervento,'ticket_id'),(int)$gpsuma_tk->id); ?>>#<?php echo esc_html($gpsuma_tk->id.' — '.$gpsuma_tk->oggetto.' ('.$gpsuma_tk->stato.')'); ?></option><?php endforeach; ?></select></p><p><label>Indirizzo intervento</label><textarea name="indirizzo_intervento" rows="2"><?php echo esc_textarea(gpsuma_val($gpsuma_intervento,'indirizzo_intervento')); ?></textarea></p></div>
<p><label>Dispositivi coinvolti</label><span class="description">Vengono mostrati solo quelli del cliente selezionato.</span></p>
<div class="gpsuma-device-checks"><?php foreach($gpsuma_dispositivi as $gpsuma_d): $gpsuma_nome=trim($gpsuma_d->tipo.' '.$gpsuma_d->marca.' '.$gpsuma_d->modello); ?><label data-cliente="<?php echo esc_attr($gpsuma_d->cliente_id); ?>"><input type="checkbox" name="dispositivi[]" value="<?php echo esc_attr($gpsuma_d->id); ?>" <?php checked(in_array((int)$gpsuma_d->id,$gpsuma_selezionati,true)); ?>> <?php echo esc_html($gpsuma_nome?:'Dispositivo #'.$gpsuma_d->id); ?></label><?php endforeach; ?></div>
<p><label>Descrizione *</label><textarea name="descrizione" rows="5" class="large-text" required><?php echo esc_textarea(gpsuma_val($gpsuma_intervento,'descrizione')); ?></textarea></p>

<div class="gpsuma-work-box gpsuma-economic-box"><h3>💶 Riepilogo economico</h3>
<div class="gpsuma-form-grid">
<p><label>Manodopera (€)</label><input class="gpsuma-cost" type="number" step="0.01" min="0" name="costo_manodopera" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'costo_manodopera','0.00')); ?>"></p>
<p><label>Materiali (€)</label><input class="gpsuma-cost" type="number" step="0.01" min="0" name="costo_materiali" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'costo_materiali','0.00')); ?>"></p>
<p><label>Trasferta (€)</label><input class="gpsuma-cost" type="number" step="0.01" min="0" name="costo_trasferta" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'costo_trasferta','0.00')); ?>"></p>
<p><label>Sconto (€)</label><input class="gpsuma-cost" type="number" step="0.01" min="0" name="sconto" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'sconto','0.00')); ?>"></p>
<p><label>IVA (%)</label><input id="gpsuma-iva" type="number" step="0.01" min="0" name="aliquota_iva" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'aliquota_iva','22')); ?>"></p>
<p><label>Stato pagamento</label><select name="stato_pagamento"><?php foreach(array('Da pagare','Parzialmente pagato','Pagato','Incluso nel pacchetto','Non fatturabile') as $gpsuma_sp): ?><option <?php selected(gpsuma_val($gpsuma_intervento,'stato_pagamento','Da pagare'),$gpsuma_sp); ?>><?php echo esc_html($gpsuma_sp); ?></option><?php endforeach; ?></select></p>
<p><label>Metodo pagamento</label><select name="metodo_pagamento"><option value="">—</option><?php foreach(array('Contanti','Bonifico','Carta/POS','Assegno','Altro') as $gpsuma_mp): ?><option <?php selected(gpsuma_val($gpsuma_intervento,'metodo_pagamento'),$gpsuma_mp); ?>><?php echo esc_html($gpsuma_mp); ?></option><?php endforeach; ?></select></p>
<p><label>Totale stimato</label><strong id="gpsuma-total-preview" class="gpsuma-total-preview">€ 0,00</strong></p>
</div></div>

<p><label>Materiale utilizzato</label><textarea name="materiale" rows="3" class="large-text"><?php echo esc_textarea(gpsuma_val($gpsuma_intervento,'materiale')); ?></textarea></p>
<div class="gpsuma-attachments-box"><p><label><strong>Allegati</strong></label><input type="file" name="gpsuma_allegati[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.txt"><span class="description"> Puoi selezionare più file.</span></p>
<p><label><input type="checkbox" name="allegati_visibili_cliente" value="1" checked> I nuovi allegati sono visibili al cliente</label></p>
<?php if($gpsuma_allegati): ?><div class="gpsuma-admin-attachment-list"><?php foreach($gpsuma_allegati as $gpsuma_a): $gpsuma_url=wp_get_attachment_url($gpsuma_a->attachment_id); $gpsuma_titolo=get_the_title($gpsuma_a->attachment_id); ?><label><span>📎 <a href="<?php echo esc_url($gpsuma_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($gpsuma_titolo?:basename((string)$gpsuma_url)); ?></a></span><span><input type="checkbox" name="elimina_allegati[]" value="<?php echo esc_attr($gpsuma_a->attachment_id); ?>"> elimina</span></label><?php endforeach; ?></div><?php endif; ?></div>
<div class="gpsuma-work-box"><h3>✅ Checklist intervento</h3><?php $gpsuma_ck=json_decode((string)gpsuma_val($gpsuma_intervento,'checklist','[]'),true)?:array(); foreach(array('Backup verificato','Antivirus aggiornato','Windows Update','Pulizia hardware','Controllo SMART disco','Test stampante','Verifica UPS') as $gpsuma_voce): ?><label><input type="checkbox" name="checklist[]" value="<?php echo esc_attr($gpsuma_voce); ?>" <?php checked(in_array($gpsuma_voce,$gpsuma_ck,true)); ?>> <?php echo esc_html($gpsuma_voce); ?></label><?php endforeach; ?></div>
<div class="gpsuma-signature-box"><h3>✍️ Firma cliente</h3><canvas id="gpsuma-signature" width="700" height="180"></canvas><input type="hidden" name="firma_cliente" id="gpsuma-firma-input" value="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'firma_cliente')); ?>"><p><button type="button" class="button" id="gpsuma-firma-pulisci">Pulisci firma</button></p><?php if(gpsuma_val($gpsuma_intervento,'firma_cliente')): ?><img class="gpsuma-signature-preview" src="<?php echo esc_attr(gpsuma_val($gpsuma_intervento,'firma_cliente')); ?>" alt="Firma cliente"><?php endif; ?></div>
<p><label>Note interne <span class="description">Non visibili al cliente</span></label><textarea name="note_interne" rows="3" class="large-text"><?php echo esc_textarea(gpsuma_val($gpsuma_intervento,'note_interne')); ?></textarea></p>
<p><label><input type="checkbox" name="conta_pacchetto" value="1" <?php checked((int)gpsuma_val($gpsuma_intervento,'conta_pacchetto',0),1); ?>> Scala un intervento dal pacchetto selezionato</label></p>
<p><label><input type="checkbox" name="visibile_cliente" value="1" <?php checked((int)gpsuma_val($gpsuma_intervento,'visibile_cliente',1),1); ?>> Visibile nell’area cliente</label></p>
<p><button class="button button-primary" name="gpsuma_salva_intervento" type="submit"><?php echo $gpsuma_intervento?'Aggiorna intervento':'Salva intervento'; ?></button> <?php if($gpsuma_intervento): ?><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi')); ?>">Annulla</a><?php endif; ?></p>
</form></div>
<div class="gpsuma-box"><div class="gpsuma-box-header"><h2>Registro interventi</h2><form class="gpsuma-filter-form"><input type="hidden" name="page" value="gpsuma-interventi"><select name="cliente"><option value="0">Tutti i clienti</option><?php foreach($gpsuma_clienti as $gpsuma_c): ?><option value="<?php echo esc_attr($gpsuma_c->id); ?>" <?php selected($gpsuma_filtro_cliente,(int)$gpsuma_c->id); ?>><?php echo esc_html($gpsuma_c->nome); ?></option><?php endforeach; ?></select><button class="button">Filtra</button></form></div>
<div class="gpsuma-table-scroll"><table class="widefat striped"><thead><tr><th>N°</th><th>Data</th><th>Cliente</th><th>Tipo / Descrizione</th><th>Dispositivi</th><th>Durata</th><th>Totale</th><th>Pagamento</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>
<?php if(!$gpsuma_elenco): ?><tr><td colspan="10">Nessun intervento registrato.</td></tr><?php endif; foreach($gpsuma_elenco as $gpsuma_i): ?><tr><td><?php echo esc_html($gpsuma_i->id); ?></td><td><?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_i->data_intervento))); ?></td><td><?php echo esc_html($gpsuma_i->cliente_nome); ?></td><td><strong><?php echo esc_html($gpsuma_i->tipo); ?></strong><br><?php echo esc_html(wp_trim_words($gpsuma_i->descrizione,18)); ?></td><td><?php echo esc_html($gpsuma_i->dispositivi?:'—'); ?></td><td><?php echo $gpsuma_i->durata_minuti?esc_html($gpsuma_i->durata_minuti.' min'):'—'; ?></td><?php $gpsuma_sub=max(0,(float)$gpsuma_i->costo_manodopera+(float)$gpsuma_i->costo_materiali+(float)$gpsuma_i->costo_trasferta-(float)$gpsuma_i->sconto); $gpsuma_tot=$gpsuma_sub+($gpsuma_sub*(float)$gpsuma_i->aliquota_iva/100); ?><td><strong><?php echo esc_html(number_format_i18n($gpsuma_tot,2).' €'); ?></strong></td><td><?php echo esc_html($gpsuma_i->stato_pagamento?:'Da pagare'); ?></td><td><?php echo esc_html($gpsuma_i->stato); ?><?php if(!$gpsuma_i->visibile_cliente): ?><br><small>Non visibile</small><?php endif; ?></td><td class="gpsuma-actions"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&modifica='.$gpsuma_i->id)); ?>">✏️ Modifica</a> <a class="button" onclick="return confirm('Eliminare questo intervento?')" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gpsuma-interventi&elimina='.$gpsuma_i->id),'gpsuma_elimina_intervento_'.$gpsuma_i->id)); ?>">🗑 Elimina</a></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
