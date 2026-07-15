<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_tenant = gpsuma_tenant_id();
$gpsuma_oggi   = current_time( 'Y-m-d' );
$gpsuma_mese   = current_time( 'Y-m' );
$gpsuma_entro30 = wp_date( 'Y-m-d', strtotime( $gpsuma_oggi . ' +30 days' ) );
$gpsuma_entro14 = wp_date( 'Y-m-d', strtotime( $gpsuma_oggi . ' +14 days' ) );
$gpsuma_db      = new GPSUMA_DB();
$gpsuma_data    = $gpsuma_db->get_dashboard_data( $gpsuma_tenant, $gpsuma_oggi, $gpsuma_mese, $gpsuma_entro30, $gpsuma_entro14 );

$gpsuma_clienti          = $gpsuma_data['clienti'];
$gpsuma_dispositivi      = $gpsuma_data['dispositivi'];
$gpsuma_ticket_aperti    = $gpsuma_data['ticket_aperti'];
$gpsuma_interventi_oggi  = $gpsuma_data['interventi_oggi'];
$gpsuma_scadenze         = $gpsuma_data['scadenze'];
$gpsuma_contratti        = $gpsuma_data['contratti'];
$gpsuma_ricavi           = $gpsuma_data['ricavi'];
$gpsuma_agenda           = $gpsuma_data['agenda'];
$gpsuma_tickets          = $gpsuma_data['tickets'];
$gpsuma_stati            = $gpsuma_data['stati'];
?>
<div class="wrap gpsuma-wrapper gpsuma-dashboard gpsuma-dashboard-v2">
  <div class="gpsuma-v2-hero">
    <div><span class="gpsuma-v2-kicker">PANORAMICA OPERATIVA</span><h1>Buon lavoro, <?php echo esc_html(wp_get_current_user()->display_name); ?></h1><p>Controlla attività, ticket, scadenze e andamento economico dell’azienda selezionata.</p></div>
    <form class="gpsuma-dashboard-search" method="get"><input type="hidden" name="page" value="gpsuma-ricerca"><span class="dashicons dashicons-search"></span><input type="search" name="q" placeholder="Cerca cliente, seriale, ticket..."><button>Cerca</button></form>
  </div>
  <div class="gpsuma-v2-metrics">
    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti')); ?>"><span class="dashicons dashicons-groups"></span><small>Clienti</small><strong><?php echo esc_html( number_format_i18n( (int) $gpsuma_clienti ) ); ?></strong><em>Anagrafiche attive</em></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi')); ?>"><span class="dashicons dashicons-desktop"></span><small>Dispositivi</small><strong><?php echo esc_html( number_format_i18n( (int) $gpsuma_dispositivi ) ); ?></strong><em>Asset registrati</em></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-richieste')); ?>"><span class="dashicons dashicons-tickets-alt"></span><small>Ticket aperti</small><strong><?php echo esc_html( number_format_i18n( (int) $gpsuma_ticket_aperti ) ); ?></strong><em>Da gestire</em></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-agenda')); ?>"><span class="dashicons dashicons-calendar-alt"></span><small>Interventi oggi</small><strong><?php echo esc_html( number_format_i18n( (int) $gpsuma_interventi_oggi ) ); ?></strong><em>In agenda</em></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-scadenze')); ?>"><span class="dashicons dashicons-warning"></span><small>Scadenze</small><strong><?php echo esc_html( number_format_i18n( (int) $gpsuma_scadenze ) ); ?></strong><em>Entro 30 giorni</em></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-economico')); ?>"><span class="dashicons dashicons-chart-line"></span><small>Valore mese</small><strong>€ <?php echo esc_html(number_format_i18n($gpsuma_ricavi,0)); ?></strong><em><?php echo esc_html(date_i18n('F Y')); ?></em></a>
  </div>
  <div class="gpsuma-v2-grid-main">
    <section class="gpsuma-v2-panel gpsuma-v2-panel-wide"><header><div><h2>Prossimi interventi</h2><p>Agenda dei prossimi 14 giorni</p></div><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-agenda')); ?>">Apri agenda →</a></header>
      <?php if(!$gpsuma_agenda): ?><div class="gpsuma-empty-state">Nessun intervento pianificato.</div><?php else: ?><div class="gpsuma-v2-agenda-list"><?php foreach($gpsuma_agenda as $gpsuma_x): ?><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&modifica='.(int)$gpsuma_x->id)); ?>"><time><b><?php echo esc_html(date_i18n('d',strtotime($gpsuma_x->data_intervento))); ?></b><span><?php echo esc_html(date_i18n('M',strtotime($gpsuma_x->data_intervento))); ?></span></time><div><strong><?php echo esc_html($gpsuma_x->cliente_nome); ?></strong><span><?php echo esc_html($gpsuma_x->tipo ?: 'Intervento'); ?></span></div><em><?php echo esc_html($gpsuma_x->ora_intervento ? substr($gpsuma_x->ora_intervento,0,5) : '—'); ?></em><i class="gpsuma-status-pill gpsuma-status-<?php echo esc_attr(sanitize_title($gpsuma_x->stato)); ?>"><?php echo esc_html($gpsuma_x->stato); ?></i></a><?php endforeach; ?></div><?php endif; ?>
    </section>
    <section class="gpsuma-v2-panel"><header><div><h2>Ticket prioritari</h2><p>Richieste ancora aperte</p></div><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-richieste')); ?>">Tutti →</a></header>
      <?php if(!$gpsuma_tickets): ?><div class="gpsuma-empty-state gpsuma-empty-success">✓ Nessun ticket aperto.</div><?php else: ?><div class="gpsuma-v2-ticket-list"><?php foreach($gpsuma_tickets as $gpsuma_x): ?><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-richieste&modifica='.(int)$gpsuma_x->id)); ?>"><div><strong><?php echo esc_html($gpsuma_x->oggetto); ?></strong><span><?php echo esc_html($gpsuma_x->cliente_nome); ?></span></div><b class="gpsuma-priority-<?php echo esc_attr(sanitize_title($gpsuma_x->priorita)); ?>"><?php echo esc_html($gpsuma_x->priorita); ?></b></a><?php endforeach; ?></div><?php endif; ?>
    </section>
  </div>
  <div class="gpsuma-v2-grid-bottom">
    <section class="gpsuma-v2-panel"><header><div><h2>Salute inventario</h2><p>Distribuzione per stato</p></div></header><div class="gpsuma-v2-health"><?php foreach($gpsuma_stati as $s): ?><div><span class="gpsuma-device-status gpsuma-device-status-<?php echo esc_attr(sanitize_title($s->stato)); ?>"><?php echo esc_html($s->stato); ?></span><strong><?php echo (int)$s->totale; ?></strong></div><?php endforeach; ?></div></section>
    <section class="gpsuma-v2-panel"><header><div><h2>Contratti attivi</h2><p>Coperture di assistenza correnti</p></div></header><div class="gpsuma-v2-contract-number"><strong><?php echo esc_html( number_format_i18n( (int) $gpsuma_contratti ) ); ?></strong><span>contratti attualmente attivi</span><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-contratti')); ?>">Gestisci contratti</a></div></section>
    <section class="gpsuma-v2-panel"><header><div><h2>Azioni rapide</h2><p>Operazioni frequenti</p></div></header><div class="gpsuma-v2-quick"><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti')); ?>">+ Cliente</a><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi')); ?>">+ Dispositivo</a><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-richieste')); ?>">+ Ticket</a><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi')); ?>">+ Intervento</a></div></section>
  </div>
</div>
