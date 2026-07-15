<?php
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_options')) { wp_die('Non autorizzato.'); }
$gpsuma_q = GPSUMA_Request::get_text( 'q' );
$gpsuma_clienti = $gpsuma_dispositivi = $gpsuma_interventi = array();
if ( strlen( $gpsuma_q ) >= 2 ) {
    $gpsuma_results     = ( new GPSUMA_DB() )->global_search( $gpsuma_q, gpsuma_tenant_id() );
    $gpsuma_clienti     = $gpsuma_results['customers'];
    $gpsuma_dispositivi = $gpsuma_results['devices'];
    $gpsuma_interventi  = $gpsuma_results['interventions'];
}
?>
<div class="wrap gpsuma-wrapper"><h1>🔎 Ricerca globale</h1><div class="gpsuma-box"><form method="get" class="gpsuma-global-search"><input type="hidden" name="page" value="gpsuma-ricerca"><input type="search" name="q" value="<?php echo esc_attr($gpsuma_q); ?>" placeholder="Cliente, seriale, modello, dispositivo, intervento..." autofocus><button class="button button-primary">Cerca</button></form></div>
<?php if($gpsuma_q && strlen($gpsuma_q)<2): ?><div class="notice notice-warning"><p>Inserisci almeno 2 caratteri.</p></div><?php endif; ?>
<?php if(strlen($gpsuma_q)>=2): ?><div class="gpsuma-search-grid">
<section class="gpsuma-box"><h2>Clienti (<?php echo count($gpsuma_clienti); ?>)</h2><?php if(!$gpsuma_clienti): ?><p>Nessun risultato.</p><?php endif; foreach($gpsuma_clienti as $gpsuma_c): ?><a class="gpsuma-search-result" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-cliente-dettaglio&id='.(int)$gpsuma_c->id)); ?>"><strong><?php echo esc_html($gpsuma_c->nome); ?></strong><span><?php echo esc_html(trim(($gpsuma_c->email?:'').' · '.($gpsuma_c->telefono?:''),' ·')); ?></span></a><?php endforeach; ?></section>
<section class="gpsuma-box"><h2>Dispositivi (<?php echo count($gpsuma_dispositivi); ?>)</h2><?php if(!$gpsuma_dispositivi): ?><p>Nessun risultato.</p><?php endif; foreach($gpsuma_dispositivi as $gpsuma_d): ?><a class="gpsuma-search-result" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivo-dettaglio&id='.(int)$gpsuma_d->id)); ?>"><strong><?php echo esc_html(trim($gpsuma_d->tipo.' '.$gpsuma_d->marca.' '.$gpsuma_d->modello)); ?></strong><span><?php echo esc_html($gpsuma_d->cliente_nome.' · Seriale: '.($gpsuma_d->seriale?:'—')); ?></span><em class="gpsuma-device-status gpsuma-device-status-<?php echo esc_attr(sanitize_title($gpsuma_d->stato?:'Operativo')); ?>"><?php echo esc_html($gpsuma_d->stato?:'Operativo'); ?></em></a><?php endforeach; ?></section>
<section class="gpsuma-box"><h2>Interventi (<?php echo count($gpsuma_interventi); ?>)</h2><?php if(!$gpsuma_interventi): ?><p>Nessun risultato.</p><?php endif; foreach($gpsuma_interventi as $gpsuma_i): ?><a class="gpsuma-search-result" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&modifica='.(int)$gpsuma_i->id)); ?>"><strong><?php echo esc_html($gpsuma_i->tipo?:'Intervento'); ?></strong><span><?php echo esc_html($gpsuma_i->cliente_nome.' · '.date_i18n('d/m/Y',strtotime($gpsuma_i->data_intervento))); ?></span><small><?php echo esc_html(wp_trim_words($gpsuma_i->descrizione,14)); ?></small></a><?php endforeach; ?></section>
</div><?php endif; ?></div>