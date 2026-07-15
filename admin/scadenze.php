<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db          = new GPSUMA_DB();
$gpsuma_id_modifica = GPSUMA_Request::get_int( 'modifica' );
$gpsuma_id_elimina  = GPSUMA_Request::get_int( 'elimina' );
$gpsuma_messaggio   = '';
$gpsuma_tipo_messaggio = 'success';

if ( $gpsuma_id_elimina && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gpsuma_elimina_scadenza_' . $gpsuma_id_elimina ) ) {
    $gpsuma_db->delete_deadline( $gpsuma_id_elimina );
    $gpsuma_messaggio = 'Scadenza eliminata correttamente.';
}

if ( isset( $_POST['gpsuma_salva_scadenza'] ) ) {
    check_admin_referer( 'gpsuma_scadenza_save' );
    $gpsuma_cliente_id   = GPSUMA_Request::post_int( 'cliente_id' );
    $gpsuma_dispositivo_id = GPSUMA_Request::post_int( 'dispositivo_id' );
    $gpsuma_titolo       = GPSUMA_Request::post_text( 'titolo' );
    $gpsuma_tipo         = GPSUMA_Request::post_text( 'tipo' );
    $gpsuma_data         = GPSUMA_Request::post_text( 'data_scadenza' );
    if ( ! $gpsuma_db->customer_exists( $gpsuma_cliente_id ) || ! $gpsuma_titolo || ! $gpsuma_tipo || ! $gpsuma_data ) {
        $gpsuma_messaggio = 'Compila cliente, tipo, titolo e data.';
        $gpsuma_tipo_messaggio = 'error';
    } else {
        if ( $gpsuma_dispositivo_id && ! $gpsuma_db->device_belongs_to_customer( $gpsuma_dispositivo_id, $gpsuma_cliente_id ) ) { $gpsuma_dispositivo_id = 0; }
        $gpsuma_stato = GPSUMA_Request::post_text( 'stato', 'Attiva' );
        if ( ! in_array( $gpsuma_stato, array( 'Attiva', 'Completata', 'Annullata' ), true ) ) { $gpsuma_stato = 'Attiva'; }
        $gpsuma_dati = array(
            'cliente_id'       => $gpsuma_cliente_id,
            'dispositivo_id'   => $gpsuma_dispositivo_id ?: null,
            'tipo'             => $gpsuma_tipo,
            'titolo'           => $gpsuma_titolo,
            'data_scadenza'    => $gpsuma_data,
            'preavviso_giorni' => GPSUMA_Request::post_int( 'preavviso_giorni', 30 ),
            'stato'            => $gpsuma_stato,
            'visibile_cliente' => GPSUMA_Request::post_bool( 'visibile_cliente' ) ? 1 : 0,
            'note'             => GPSUMA_Request::post_textarea( 'note' ),
        );
        $gpsuma_ok = $gpsuma_db->save_deadline( $gpsuma_dati, $gpsuma_id_modifica );
        if ( false !== $gpsuma_ok ) {
            $gpsuma_messaggio = $gpsuma_id_modifica ? 'Scadenza aggiornata correttamente.' : 'Scadenza inserita correttamente.';
        } else {
            $gpsuma_messaggio = 'Errore nel salvataggio.';
            $gpsuma_tipo_messaggio = 'error';
        }
    }
}
$gpsuma_form_data   = $gpsuma_db->get_deadline_form_data();
$gpsuma_clienti     = $gpsuma_form_data['customers'];
$gpsuma_dispositivi = $gpsuma_form_data['devices'];
$gpsuma_scadenza    = $gpsuma_id_modifica ? $gpsuma_db->get_deadline( $gpsuma_id_modifica ) : null;
$gpsuma_elenco      = $gpsuma_db->get_deadlines_admin();
function gpsuma_sv( $object, $field, $default = '' ) { return $object && isset( $object->$field ) ? $object->$field : $default; }
$gpsuma_oggi = current_time( 'Y-m-d' );
?>
<div class="wrap gpsuma-wrapper"><h1>⏰ Scadenze</h1>
<?php if($gpsuma_messaggio): ?><div class="notice notice-<?php echo esc_attr($gpsuma_tipo_messaggio); ?> is-dismissible"><p><?php echo esc_html($gpsuma_messaggio); ?></p></div><?php endif; ?>
<div class="gpsuma-box"><h2><?php echo $gpsuma_scadenza?'Modifica scadenza':'Nuova scadenza'; ?></h2>
<form method="post"><?php wp_nonce_field('gpsuma_scadenza_save'); ?><div class="gpsuma-form-grid">
<p><label>Cliente *</label><select name="cliente_id" id="gpsuma-scadenza-cliente" required><option value="">Seleziona cliente</option><?php foreach($gpsuma_clienti as $gpsuma_c): ?><option value="<?php echo esc_attr($gpsuma_c->id); ?>" <?php selected((int)gpsuma_sv($gpsuma_scadenza,'cliente_id',absint($_POST['cliente_id']??0)),(int)$gpsuma_c->id); ?>><?php echo esc_html($gpsuma_c->nome); ?></option><?php endforeach; ?></select></p>
<p><label>Dispositivo</label><select name="dispositivo_id" id="gpsuma-scadenza-dispositivo"><option value="">Nessun dispositivo specifico</option><?php foreach($gpsuma_dispositivi as $gpsuma_d): ?><option data-cliente="<?php echo esc_attr($gpsuma_d->cliente_id); ?>" value="<?php echo esc_attr($gpsuma_d->id); ?>" <?php selected((int)gpsuma_sv($gpsuma_scadenza,'dispositivo_id'),(int)$gpsuma_d->id); ?>><?php echo esc_html(trim($gpsuma_d->tipo.' '.$gpsuma_d->marca.' '.$gpsuma_d->modello)); ?></option><?php endforeach; ?></select></p>
<p><label>Tipo *</label><select name="tipo" required><?php foreach(array('Antivirus','Garanzia','Licenza','Backup','UPS','Manutenzione','Dominio','Certificato SSL','Altro') as $gpsuma_t): ?><option <?php selected(gpsuma_sv($gpsuma_scadenza,'tipo'),$gpsuma_t); ?>><?php echo esc_html($gpsuma_t); ?></option><?php endforeach; ?></select></p>
<p><label>Titolo *</label><input type="text" name="titolo" required value="<?php echo esc_attr(gpsuma_sv($gpsuma_scadenza,'titolo')); ?>" placeholder="Es. Rinnovo licenza Microsoft 365"></p>
<p><label>Data scadenza *</label><input type="date" name="data_scadenza" required value="<?php echo esc_attr(gpsuma_sv($gpsuma_scadenza,'data_scadenza')); ?>"></p>
<p><label>Preavviso (giorni)</label><input type="number" min="0" name="preavviso_giorni" value="<?php echo esc_attr(gpsuma_sv($gpsuma_scadenza,'preavviso_giorni',30)); ?>"></p>
<p><label>Stato</label><select name="stato"><?php foreach(array('Attiva','Completata','Annullata') as $gpsuma_st): ?><option <?php selected(gpsuma_sv($gpsuma_scadenza,'stato','Attiva'),$gpsuma_st); ?>><?php echo esc_html($gpsuma_st); ?></option><?php endforeach; ?></select></p>
</div>
<p><label>Note</label><textarea name="note" rows="3" class="large-text"><?php echo esc_textarea(gpsuma_sv($gpsuma_scadenza,'note')); ?></textarea></p>
<p><label><input type="checkbox" name="visibile_cliente" value="1" <?php checked((int)gpsuma_sv($gpsuma_scadenza,'visibile_cliente',1),1); ?>> Visibile nell’area cliente</label></p>
<p><button class="button button-primary" name="gpsuma_salva_scadenza" value="1"><?php echo $gpsuma_scadenza?'Aggiorna scadenza':'Salva scadenza'; ?></button> <?php if($gpsuma_scadenza): ?><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-scadenze')); ?>">Annulla</a><?php endif; ?></p></form></div>
<div class="gpsuma-box"><h2>Elenco scadenze</h2><?php if(!$gpsuma_elenco): ?><div class="gpsuma-empty-state">Nessuna scadenza inserita.</div><?php else: ?><table class="widefat striped"><thead><tr><th>Data</th><th>Cliente</th><th>Tipo</th><th>Titolo</th><th>Dispositivo</th><th>Stato</th><th>Azioni</th></tr></thead><tbody><?php foreach($gpsuma_elenco as $s): $gpsuma_giorni=(int)floor((strtotime($s->data_scadenza)-strtotime($gpsuma_oggi))/DAY_IN_SECONDS); $gpsuma_classe=$s->stato==='Attiva'&&$gpsuma_giorni<0?'gpsuma-expired':($s->stato==='Attiva'&&$gpsuma_giorni<=(int)$s->preavviso_giorni?'gpsuma-due':''); ?><tr class="<?php echo esc_attr($gpsuma_classe); ?>"><td><strong><?php echo esc_html(date_i18n('d/m/Y',strtotime($s->data_scadenza))); ?></strong><br><small><?php echo $gpsuma_giorni<0?esc_html(abs($gpsuma_giorni).' giorni fa'):esc_html('tra '.$gpsuma_giorni.' giorni'); ?></small></td><td><?php echo esc_html($s->cliente_nome); ?></td><td><?php echo esc_html($s->tipo); ?></td><td><?php echo esc_html($s->titolo); ?></td><td><?php echo esc_html($s->dispositivo_nome?:'—'); ?></td><td><span class="gpsuma-status-pill"><?php echo esc_html($s->stato); ?></span></td><td><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-scadenze&modifica='.(int)$s->id)); ?>">Modifica</a> | <a onclick="return confirm('Eliminare questa scadenza?')" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gpsuma-scadenze&elimina='.(int)$s->id),'gpsuma_elimina_scadenza_'.$s->id)); ?>">Elimina</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
