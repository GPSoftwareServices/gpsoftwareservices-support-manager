<?php
if (!defined('ABSPATH')) { exit; }

class GPSUMA_Reports {
    public function __construct() {
        add_action('admin_post_gpsuma_intervento_report', array($this, 'intervento_report'));
        add_action('admin_post_gpsuma_device_label', array($this, 'device_label'));
        add_action('template_redirect', array($this, 'device_qr_page'));
    }

    private function duration($minutes) {
        $minutes=(int)$minutes;
        if($minutes<=0) return '—';
        $h=intdiv($minutes,60); $m=$minutes%60;
        return trim(($h?$h.' h ':'').($m?$m.' min':''));
    }

    private function shell_start($title, $auto_print=false) {
        wp_register_style('gpsuma-report', GPSUMA_URL . 'assets/css/report.css', array(), GPSUMA_VERSION);
        wp_register_script('gpsuma-report', GPSUMA_URL . 'assets/js/report.js', array(), GPSUMA_VERSION, true);
        wp_register_script('gpsuma-qrcode', GPSUMA_URL . 'assets/js/qrcode-local.js', array(), GPSUMA_VERSION, true);
        wp_enqueue_style('gpsuma-report');
        wp_enqueue_script('gpsuma-report');
        wp_enqueue_script('gpsuma-qrcode');
        ?><!doctype html><html lang="<?php echo esc_attr(get_bloginfo('language')); ?>"><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html($title); ?></title><?php wp_print_styles('gpsuma-report'); ?></head><body data-gpsuma-auto-print="<?php echo $auto_print ? '1' : '0'; ?>"><?php
        if(current_user_can('manage_options')) {
            $back_url = wp_get_referer() ? wp_get_referer() : admin_url('admin.php?page=gpsuma-dashboard');
            ?><div class="actions"><button type="button" id="gpsuma-print-report">🖨️ <?php echo esc_html__('Stampa / Salva PDF', 'gpsoftwareservices-support-manager'); ?></button><a href="<?php echo esc_url($back_url); ?>"><?php echo esc_html__('Chiudi', 'gpsoftwareservices-support-manager'); ?></a></div><?php
        }
    }
    private function shell_end(){ wp_print_footer_scripts(); echo '</body></html>'; }

    public function intervento_report() {
        if(!current_user_can('manage_options')) wp_die('Non autorizzato.');
        $id = GPSUMA_Request::get_int( 'id' ); check_admin_referer( 'gpsuma_report_' . $id );
        $data = ( new GPSUMA_DB() )->get_intervention_report_data( $id );
        $i = $data['intervention'];
        if ( ! $i ) { wp_die( esc_html__( 'Intervento non trovato.', 'gpsoftwareservices-support-manager' ) ); }
        $devices = $data['devices'];
        $checks=json_decode((string)$i->checklist,true); if(!is_array($checks))$checks=array();
        $labels=array('backup'=>'Backup verificato','antivirus'=>'Antivirus aggiornato','windows_update'=>'Windows Update','pulizia'=>'Pulizia hardware','smart'=>'Controllo SMART','stampante'=>'Test stampante','ups'=>'Verifica UPS');
        $this->shell_start('Rapporto intervento #'.$id);
        ?><main class="sheet"><header class="header"><div><div class="brand">GP Software Services</div><div class="subtitle">Rapporto di intervento tecnico</div></div><div><strong>#<?php echo esc_html($id); ?></strong><br><?php echo esc_html(date_i18n('d/m/Y',strtotime($i->data_intervento))); ?></div></header>
        <section class="meta"><div><small>Cliente</small><strong><?php echo esc_html($i->cliente_nome); ?></strong></div><div><small>Tecnico</small><strong><?php echo esc_html($i->tecnico?:'—'); ?></strong></div><div><small>Data e ora</small><?php echo esc_html(date_i18n('d/m/Y',strtotime($i->data_intervento)).' '.substr((string)$i->ora_intervento,0,5)); ?></div><div><small>Durata effettiva</small><?php echo esc_html($this->duration($i->durata_minuti)); ?></div><div><small>Tipo</small><?php echo esc_html($i->tipo?:'Intervento tecnico'); ?></div><div><small>Stato</small><?php echo esc_html($i->stato); ?></div><?php if($i->ticket_id): ?><div><small>Ticket collegato</small>#<?php echo esc_html($i->ticket_id.' · '.$i->ticket_oggetto); ?></div><?php endif; ?><div><small>Contatti</small><?php echo esc_html(trim($i->cliente_telefono.' '.$i->cliente_email)); ?></div></section>
        <?php if($devices): ?><section class="section"><h2>Dispositivi</h2><div class="box"><?php foreach($devices as $d) echo esc_html(trim($d->tipo.' '.$d->marca.' '.$d->modello).' · S/N '.($d->seriale?:'—'))."\n"; ?></div></section><?php endif; ?>
        <section class="section"><h2>Lavorazioni eseguite</h2><div class="box"><?php echo nl2br(esc_html($i->descrizione)); ?></div></section>
        <?php if($i->materiale): ?><section class="section"><h2>Materiale utilizzato</h2><div class="box"><?php echo nl2br(esc_html($i->materiale)); ?></div></section><?php endif; ?>
        <section class="section"><h2>Checklist</h2><div class="checklist"><?php foreach($labels as $key=>$label): ?><div class="check"><?php echo in_array($key,$checks,true)?'☑':'☐'; ?> <?php echo esc_html($label); ?></div><?php endforeach; ?></div></section>
        <section class="section"><h2>Firma cliente</h2><?php if($i->firma_cliente): ?><img class="signature" src="<?php echo esc_url( $i->firma_cliente ); ?>" alt="Firma cliente"><?php else: ?><p>Firma non acquisita.</p><?php endif; ?></section>
        <footer class="footer">Documento generato il <?php echo esc_html(date_i18n('d/m/Y H:i',current_time('timestamp'))); ?> · GP Software Services</footer></main><?php
        $this->shell_end(); exit;
    }

    private function device_url($device) {
        return add_query_arg(array('gpsuma_device_qr'=>(int)$device->id,'token'=>$device->qr_token),home_url('/'));
    }

    public function device_label() {
        if(!current_user_can('manage_options')) wp_die('Non autorizzato.');
        $id = GPSUMA_Request::get_int( 'id' ); check_admin_referer( 'gpsuma_label_' . $id );
        $db = new GPSUMA_DB();
        $d = $db->get_device_with_customer( $id );
        if ( ! $d ) { wp_die( esc_html__( 'Dispositivo non trovato.', 'gpsoftwareservices-support-manager' ) ); }
        $d->qr_token = $db->ensure_device_qr_token( $id, (string) $d->qr_token );
        $url=$this->device_url($d);
        $this->shell_start('Etichetta dispositivo #'.$id); ?><main class="sheet"><div class="label"><div class="qr" data-gpsuma-qr="<?php echo esc_attr($url); ?>" data-gpsuma-qr-size="300"></div><div><div class="brand">GP Software Services</div><h1><?php echo esc_html(trim($d->tipo.' '.$d->marca.' '.$d->modello)); ?></h1><p><strong>Cliente:</strong> <?php echo esc_html($d->cliente_nome); ?></p><p><strong>Seriale:</strong> <?php echo esc_html($d->seriale?:'—'); ?></p><p><strong>Codice asset:</strong> GPSUMA-<?php echo esc_html(str_pad((string)$d->id,6,'0',STR_PAD_LEFT)); ?></p><p class="subtitle">Scansiona per aprire la scheda tecnica.</p></div></div></main><?php $this->shell_end(); exit;
    }

    public function device_qr_page() {
        if ( ! GPSUMA_Request::get_int( 'gpsuma_device_qr' ) ) { return; }
        $id = GPSUMA_Request::get_int( 'gpsuma_device_qr' ); $token = GPSUMA_Request::get_text( 'token' );
        $db = new GPSUMA_DB();
        $d = $db->get_device_with_customer( $id, $token );
        if(!$d) wp_die('QR Code non valido o revocato.');
        $allowed=current_user_can('manage_options') || (is_user_logged_in() && (int)$d->user_id===get_current_user_id());
        if(!$allowed){ auth_redirect(); exit; }
        $portal_data = $db->get_device_portal_data( $id, current_user_can( 'manage_options' ) );
        $ints = $portal_data['interventions'];
        $dead = $portal_data['deadlines'];
        $this->shell_start('Scheda dispositivo'); ?><main class="mobile-card"><span class="badge"><?php echo esc_html($d->stato?:'Operativo'); ?></span><h1><?php echo esc_html(trim($d->tipo.' '.$d->marca.' '.$d->modello)); ?></h1><p class="subtitle"><?php echo esc_html($d->cliente_nome); ?> · GPSUMA-<?php echo esc_html(str_pad((string)$d->id,6,'0',STR_PAD_LEFT)); ?></p>
        <section class="meta"><div><small>Seriale</small><?php echo esc_html($d->seriale?:'—'); ?></div><div><small>Sistema</small><?php echo esc_html($d->sistema?:'—'); ?></div><div><small>Marca</small><?php echo esc_html($d->marca?:'—'); ?></div><div><small>Modello</small><?php echo esc_html($d->modello?:'—'); ?></div></section>
        <?php if($dead): ?><section class="section"><h2>Scadenze attive</h2><?php foreach($dead as $s): ?><div class="box" class="box gpsuma-box-gap"><strong><?php echo esc_html($s->titolo); ?></strong><br><?php echo esc_html($s->tipo.' · '.date_i18n('d/m/Y',strtotime($s->data_scadenza))); ?></div><?php endforeach; ?></section><?php endif; ?>
        <section class="section"><h2>Storico interventi</h2><?php if(!$ints): ?><p>Nessun intervento visibile.</p><?php else: ?><div class="timeline"><?php foreach($ints as $i): ?><article><span class="badge"><?php echo esc_html($i->stato); ?></span><h3><?php echo esc_html(date_i18n('d/m/Y',strtotime($i->data_intervento)).' · '.($i->tipo?:'Intervento')); ?></h3><p><?php echo nl2br(esc_html($i->descrizione)); ?></p><?php if($i->tecnico): ?><small>Tecnico: <?php echo esc_html($i->tecnico); ?></small><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></section></main><?php $this->shell_end(); exit;
    }
}
