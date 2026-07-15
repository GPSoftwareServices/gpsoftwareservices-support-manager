<?php
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_options')) { wp_die('Non autorizzato.'); }

$gpsuma_cliente_id = GPSUMA_Request::get_int( 'cliente_id' );
if ( ! $gpsuma_cliente_id ) { wp_die( esc_html__( 'Cliente non specificato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db = new GPSUMA_DB();
$gpsuma_summary = $gpsuma_db->get_customer_admin_summary( $gpsuma_cliente_id );
if ( ! $gpsuma_summary ) { wp_die( esc_html__( 'Cliente non trovato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_cliente         = $gpsuma_summary['cliente'];
$gpsuma_tot_dispositivi = $gpsuma_summary['tot_dispositivi'];
$gpsuma_tot_interventi  = $gpsuma_summary['tot_interventi'];
$gpsuma_minuti_totali   = $gpsuma_summary['minuti_totali'];
$gpsuma_media_minuti    = $gpsuma_tot_interventi ? (int) round( $gpsuma_minuti_totali / $gpsuma_tot_interventi ) : 0;
$gpsuma_dispositivi     = $gpsuma_summary['dispositivi'];
$gpsuma_interventi      = $gpsuma_summary['interventi'];
$gpsuma_pacchetto       = $gpsuma_summary['pacchetto'];

$gpsuma_timeline = array();
foreach ($gpsuma_interventi as $gpsuma_item) {
    $gpsuma_timeline[] = array('date'=>$gpsuma_item->data_intervento, 'type'=>'intervento', 'title'=>$gpsuma_item->tipo ?: 'Intervento', 'text'=>wp_trim_words($gpsuma_item->descrizione, 15), 'url'=>admin_url('admin.php?page=gpsuma-interventi&modifica='.$gpsuma_item->id));
}
foreach ($gpsuma_dispositivi as $gpsuma_item) {
    $gpsuma_nome = trim($gpsuma_item->tipo.' '.$gpsuma_item->marca.' '.$gpsuma_item->modello);
    $gpsuma_timeline[] = array('date'=>substr($gpsuma_item->data_creazione,0,10), 'type'=>'dispositivo', 'title'=>'Nuovo dispositivo', 'text'=>$gpsuma_nome ?: 'Dispositivo #'.$gpsuma_item->id, 'url'=>admin_url('admin.php?page=gpsuma-dispositivo-dettaglio&id='.$gpsuma_item->id));
}
if ($gpsuma_pacchetto) {
    $gpsuma_timeline[] = array('date'=>$gpsuma_pacchetto->data_attivazione, 'type'=>'pacchetto', 'title'=>'Pacchetto attivato', 'text'=>$gpsuma_pacchetto->nome, 'url'=>admin_url('admin.php?page=gpsuma-pacchetti&modifica='.$gpsuma_pacchetto->id));
}
$gpsuma_timeline[] = array('date'=>substr($gpsuma_cliente->data_creazione,0,10), 'type'=>'cliente', 'title'=>'Cliente creato', 'text'=>$gpsuma_cliente->nome, 'url'=>admin_url('admin.php?page=gpsuma-clienti&modifica='.$gpsuma_cliente->id));
usort($gpsuma_timeline, function($a,$b){ return strcmp($b['date'],$a['date']); });
$gpsuma_timeline = array_slice($gpsuma_timeline,0,10);

function gpsuma_080_minutes($minutes) {
    $minutes=(int)$minutes;
    if ($minutes < 60) return $minutes.' min';
    $h=floor($minutes/60); $m=$minutes%60;
    return $h.' h'.($m ? ' '.$m.' min' : '');
}
?>
<div class="wrap gpsuma-wrapper gpsuma-customer-360">
    <div class="gpsuma-c360-topbar">
        <div>
            <a class="gpsuma-back-link" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti')); ?>">← Torna ai clienti</a>
            <h1><?php echo esc_html($gpsuma_cliente->nome); ?></h1>
            <div class="gpsuma-c360-contact">
                <?php if($gpsuma_cliente->telefono): ?><span>☎ <?php echo esc_html($gpsuma_cliente->telefono); ?></span><?php endif; ?>
                <?php if($gpsuma_cliente->email): ?><span>✉ <?php echo esc_html($gpsuma_cliente->email); ?></span><?php endif; ?>
                <?php if($gpsuma_cliente->indirizzo): ?><span>📍 <?php echo esc_html($gpsuma_cliente->indirizzo); ?></span><?php endif; ?>
            </div>
        </div>
        <div class="gpsuma-c360-actions">
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti&modifica='.$gpsuma_cliente_id)); ?>">Modifica anagrafica</a>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&cliente='.$gpsuma_cliente_id)); ?>">+ Nuovo intervento</a>
        </div>
    </div>

    <div class="gpsuma-c360-stats">
        <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi&cliente_id='.$gpsuma_cliente_id)); ?>"><span>💻</span><strong><?php echo esc_html($gpsuma_tot_dispositivi); ?></strong><small>Dispositivi</small></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&cliente='.$gpsuma_cliente_id)); ?>"><span>🔧</span><strong><?php echo esc_html($gpsuma_tot_interventi); ?></strong><small>Interventi</small></a>
        <div><span>⏱</span><strong><?php echo esc_html(gpsuma_080_minutes($gpsuma_minuti_totali)); ?></strong><small>Tempo totale</small></div>
        <div><span>📊</span><strong><?php echo esc_html(gpsuma_080_minutes($gpsuma_media_minuti)); ?></strong><small>Durata media</small></div>
    </div>

    <div class="gpsuma-c360-grid">
        <section class="gpsuma-box gpsuma-c360-package">
            <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">📦</span><h2>Pacchetto assistenza</h2></div><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-pacchetti&cliente='.$gpsuma_cliente_id)); ?>">Vedi tutti</a></div>
            <?php if($gpsuma_pacchetto):
                $gpsuma_usati=(int)$gpsuma_pacchetto->utilizzati; $gpsuma_illimitato=!empty($gpsuma_pacchetto->interventi_illimitati); $gpsuma_inclusi=(int)$gpsuma_pacchetto->interventi_inclusi; $gpsuma_residui=$gpsuma_illimitato?null:max(0,$gpsuma_inclusi-$gpsuma_usati); $gpsuma_perc=(!$gpsuma_illimitato&&$gpsuma_inclusi) ? min(100,round(($gpsuma_usati/$gpsuma_inclusi)*100)) : 0; ?>
                <h3><?php echo esc_html($gpsuma_pacchetto->nome); ?></h3>
                <div class="gpsuma-c360-package-count"><strong><?php echo $gpsuma_illimitato ? '∞' : esc_html($gpsuma_residui); ?></strong><span><?php echo $gpsuma_illimitato ? 'interventi illimitati' : 'interventi residui su '.esc_html($gpsuma_inclusi); ?></span></div>
                <div class="gpsuma-progress gpsuma-progress-large"><span style="width:<?php echo esc_attr($gpsuma_perc); ?>%"></span></div>
                <div class="gpsuma-c360-meta"><span>Utilizzati: <strong><?php echo esc_html($gpsuma_usati); ?></strong></span><span>Scadenza: <strong><?php echo $gpsuma_pacchetto->data_scadenza ? esc_html(date_i18n('d/m/Y',strtotime($gpsuma_pacchetto->data_scadenza))) : 'Nessuna'; ?></strong></span></div>
            <?php else: ?>
                <div class="gpsuma-empty-state">Nessun pacchetto attivo.<br><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-pacchetti&cliente='.$gpsuma_cliente_id)); ?>">Crea pacchetto</a></div>
            <?php endif; ?>
        </section>

        <section class="gpsuma-box">
            <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">👤</span><h2>Accesso cliente</h2></div></div>
            <?php if($gpsuma_cliente->user_id): ?>
                <p class="gpsuma-access-ok">✓ Area riservata configurata</p>
                <strong><?php echo esc_html($gpsuma_cliente->utente_nome); ?></strong><br><span class="gpsuma-muted"><?php echo esc_html($gpsuma_cliente->utente_email); ?></span>
            <?php else: ?>
                <p class="gpsuma-access-warning">Accesso non ancora configurato.</p>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti&modifica='.$gpsuma_cliente_id)); ?>">Associa utente</a>
            <?php endif; ?>
            <?php if($gpsuma_cliente->note): ?><div class="gpsuma-internal-note"><strong>Note interne</strong><p><?php echo nl2br(esc_html($gpsuma_cliente->note)); ?></p></div><?php endif; ?>
        </section>
    </div>

    <div class="gpsuma-c360-grid gpsuma-c360-main-grid">
        <section class="gpsuma-box">
            <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">💻</span><h2>Dispositivi recenti</h2></div><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi&cliente_id='.$gpsuma_cliente_id)); ?>">Vedi tutti</a></div>
            <div class="gpsuma-c360-device-list">
                <?php if(!$gpsuma_dispositivi): ?><div class="gpsuma-empty-state">Nessun dispositivo associato.</div><?php endif; ?>
                <?php foreach($gpsuma_dispositivi as $gpsuma_d): $gpsuma_nome=trim($gpsuma_d->tipo.' '.$gpsuma_d->marca.' '.$gpsuma_d->modello); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivo-dettaglio&id='.$gpsuma_d->id)); ?>"><span class="gpsuma-device-avatar">💻</span><span><strong><?php echo esc_html($gpsuma_nome ?: 'Dispositivo #'.$gpsuma_d->id); ?></strong><small><?php echo $gpsuma_d->seriale ? 'Seriale: '.esc_html($gpsuma_d->seriale) : esc_html($gpsuma_d->sistema ?: 'Nessun dettaglio'); ?></small></span><b>›</b></a>
                <?php endforeach; ?>
            </div>
            <a class="button gpsuma-full-button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi&cliente_id='.$gpsuma_cliente_id)); ?>">+ Aggiungi dispositivo</a>
        </section>

        <section class="gpsuma-box">
            <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">🔧</span><h2>Ultimi interventi</h2></div><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&cliente='.$gpsuma_cliente_id)); ?>">Cronologia</a></div>
            <div class="gpsuma-c360-intervention-list">
                <?php if(!$gpsuma_interventi): ?><div class="gpsuma-empty-state">Nessun intervento registrato.</div><?php endif; ?>
                <?php foreach($gpsuma_interventi as $gpsuma_i): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&modifica='.$gpsuma_i->id)); ?>"><time><?php echo esc_html(date_i18n('d M',strtotime($gpsuma_i->data_intervento))); ?></time><span><strong><?php echo esc_html($gpsuma_i->tipo ?: 'Intervento'); ?></strong><small><?php echo esc_html(wp_trim_words($gpsuma_i->descrizione,11)); ?></small></span><em class="gpsuma-status-pill gpsuma-status-<?php echo esc_attr(sanitize_title($gpsuma_i->stato)); ?>"><?php echo esc_html($gpsuma_i->stato); ?></em></a>
                <?php endforeach; ?>
            </div>
            <a class="button button-primary gpsuma-full-button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&cliente='.$gpsuma_cliente_id)); ?>">+ Registra intervento</a>
        </section>
    </div>

    <section class="gpsuma-box">
        <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">🕘</span><h2>Timeline cliente</h2></div></div>
        <div class="gpsuma-timeline">
            <?php foreach($gpsuma_timeline as $gpsuma_e): ?>
                <a href="<?php echo esc_url($gpsuma_e['url']); ?>" class="gpsuma-timeline-item gpsuma-timeline-<?php echo esc_attr($gpsuma_e['type']); ?>">
                    <span class="gpsuma-timeline-dot"></span><time><?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_e['date']))); ?></time><div><strong><?php echo esc_html($gpsuma_e['title']); ?></strong><p><?php echo esc_html($gpsuma_e['text']); ?></p></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>
