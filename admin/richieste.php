<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db = new GPSUMA_DB();
$gpsuma_stati = array( 'Nuovo', 'Preso in carico', 'In lavorazione', 'In attesa cliente', 'Risolto', 'Chiuso' );

if ( ! function_exists( 'gpsuma_ticket_send_mail' ) ) {
	function gpsuma_ticket_send_mail( $ticket_id, $subject, $message ) {
		$gpsuma_db = new GPSUMA_DB();
		$row = $gpsuma_db->get_ticket_mail_data( $ticket_id );
		if ( $row && is_email( $row->email ) ) {
			wp_mail( $row->email, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ) );
		}
	}
}

if ( isset( $_POST['gpsuma_aggiorna_ticket'] ) && check_admin_referer( 'gpsuma_ticket_admin' ) ) {
	$id = GPSUMA_Request::post_int( 'ticket_id' );
	$gpsuma_stato = GPSUMA_Request::post_text( 'stato', 'Nuovo' );
	$gpsuma_commento = GPSUMA_Request::post_textarea( 'commento' );
	$gpsuma_visibile = GPSUMA_Request::post_bool( 'visibile_cliente' ) ? 1 : 0;
	if ( $id && in_array( $gpsuma_stato, $gpsuma_stati, true ) ) {
		$gpsuma_old = $gpsuma_db->get_ticket_state( $id );
		$gpsuma_db->update(
			'richieste',
			array( 'stato' => $gpsuma_stato, 'data_aggiornamento' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		if ( $gpsuma_commento ) {
			$gpsuma_user = wp_get_current_user();
			$gpsuma_db->insert(
				'ticket_commenti',
				array(
					'richiesta_id' => $id,
					'autore_user_id' => get_current_user_id(),
					'autore_nome' => $gpsuma_user->display_name ? $gpsuma_user->display_name : $gpsuma_user->user_login,
					'tipo_autore' => 'tecnico',
					'messaggio' => $gpsuma_commento,
					'visibile_cliente' => $gpsuma_visibile,
					'data_creazione' => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
			);
			if ( $gpsuma_visibile ) {
				gpsuma_ticket_send_mail( $id, 'Aggiornamento ticket #' . $id, $gpsuma_commento . "\n\nNuovo stato: " . $gpsuma_stato );
			}
		} elseif ( $gpsuma_old !== $gpsuma_stato ) {
			gpsuma_ticket_send_mail( $id, 'Stato ticket #' . $id . ' aggiornato', 'Il ticket è ora nello stato: ' . $gpsuma_stato );
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ticket aggiornato.', 'gpsoftwareservices-support-manager' ) . '</p></div>';
	}
}

$gpsuma_filtro = GPSUMA_Request::get_text( 'stato' );
if ( ! in_array( $gpsuma_filtro, array_merge( array( '' ), $gpsuma_stati ), true ) ) { $gpsuma_filtro = ''; }
$gpsuma_rows = $gpsuma_db->get_tickets_admin( $gpsuma_filtro );
$gpsuma_ticket_ids = wp_list_pluck( $gpsuma_rows, 'id' );
$gpsuma_comments_map = $gpsuma_db->get_ticket_comments_map( $gpsuma_ticket_ids );
$gpsuma_attachments_map = $gpsuma_db->get_ticket_attachments_map( $gpsuma_ticket_ids );
?>
<div class="wrap gpsuma-wrapper">
 <div class="gpsuma-page-header"><div><span class="gpsuma-eyebrow">Help desk</span><h1>🎫 Ticket assistenza</h1><p>Gestisci richieste, conversazioni e avanzamento delle attività.</p></div></div>
 <div class="gpsuma-box"><form method="get" class="gpsuma-filter-form"><input type="hidden" name="page" value="gpsuma-richieste"><label>Stato</label><select name="stato"><option value="">Tutti</option><?php foreach($gpsuma_stati as $s):?><option <?php selected($gpsuma_filtro,$s);?>><?php echo esc_html($s);?></option><?php endforeach;?></select><button class="button">Filtra</button></form></div>
 <div class="gpsuma-request-admin-list">
 <?php if(!$gpsuma_rows):?><div class="gpsuma-box"><p>Nessun ticket trovato.</p></div><?php endif;?>
 <?php foreach ( $gpsuma_rows as $gpsuma_r ) : $comments = isset( $gpsuma_comments_map[ $gpsuma_r->id ] ) ? $gpsuma_comments_map[ $gpsuma_r->id ] : array(); $gpsuma_atts = isset( $gpsuma_attachments_map[ $gpsuma_r->id ] ) ? $gpsuma_attachments_map[ $gpsuma_r->id ] : array(); ?>
 <article class="gpsuma-box gpsuma-request-admin-card">
  <div class="gpsuma-request-admin-head"><div><span class="gpsuma-status-pill gpsuma-status-<?php echo esc_attr(sanitize_title($gpsuma_r->stato));?>"><?php echo esc_html($gpsuma_r->stato);?></span><h2>#<?php echo (int)$gpsuma_r->id;?> · <?php echo esc_html($gpsuma_r->oggetto);?></h2><p><strong><?php echo esc_html($gpsuma_r->cliente_nome);?></strong> · <?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($gpsuma_r->data_creazione)));?></p></div><span class="gpsuma-priority">Priorità: <?php echo esc_html($gpsuma_r->priorita);?></span></div>
  <p><?php echo nl2br(esc_html($gpsuma_r->descrizione));?></p>
  <?php if($gpsuma_r->dispositivo_nome||$gpsuma_r->dispositivo):?><p><strong>Dispositivo:</strong> <?php echo esc_html($gpsuma_r->dispositivo_nome?:$gpsuma_r->dispositivo);?></p><?php endif;?>
  <?php if($gpsuma_atts):?><div class="gpsuma-ticket-attachments"><strong>Allegati iniziali</strong><?php foreach($gpsuma_atts as $gpsuma_a):$gpsuma_url=wp_get_attachment_url($gpsuma_a->attachment_id);?><a href="<?php echo esc_url($gpsuma_url);?>" target="_blank" rel="noopener">📎 <?php echo esc_html(get_the_title($gpsuma_a->attachment_id)?:basename((string)$gpsuma_url));?></a><?php endforeach;?></div><?php endif;?>
  <?php if($comments):?><div class="gpsuma-ticket-thread"><?php foreach($comments as $gpsuma_c):?><div class="gpsuma-ticket-message <?php echo $gpsuma_c->tipo_autore==='tecnico'?'is-tech':'is-client';?>"><strong><?php echo esc_html($gpsuma_c->autore_nome);?></strong><small><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($gpsuma_c->data_creazione)));?><?php if(!$gpsuma_c->visibile_cliente):?> · nota interna<?php endif;?></small><p><?php echo nl2br(esc_html($gpsuma_c->messaggio));?></p></div><?php endforeach;?></div><?php endif;?>
  <form method="post" class="gpsuma-request-admin-form"><?php wp_nonce_field('gpsuma_ticket_admin');?><input type="hidden" name="ticket_id" value="<?php echo (int)$gpsuma_r->id;?>"><div><label>Stato</label><select name="stato"><?php foreach($gpsuma_stati as $s):?><option value="<?php echo esc_attr($s);?>" <?php selected($gpsuma_r->stato,$s);?>><?php echo esc_html($s);?></option><?php endforeach;?></select></div><div class="gpsuma-request-note"><label>Nuovo commento</label><textarea name="commento" rows="3" placeholder="Scrivi un aggiornamento..."></textarea><label><input type="checkbox" name="visibile_cliente" value="1" checked> Visibile al cliente e invia email</label></div><button class="button button-primary" name="gpsuma_aggiorna_ticket" value="1">Salva aggiornamento</button></form>
 </article><?php endforeach;?>
 </div>
</div>
