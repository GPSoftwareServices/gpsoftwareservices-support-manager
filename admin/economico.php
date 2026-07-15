<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_tenant  = function_exists( 'gpsuma_tenant_id' ) ? gpsuma_tenant_id() : 1;
$gpsuma_anno    = isset( $_GET['anno'] ) ? max( 2000, absint( wp_unslash( $_GET['anno'] ) ) ) : (int) wp_date( 'Y' );
$gpsuma_cliente = isset( $_GET['cliente'] ) ? absint( wp_unslash( $_GET['cliente'] ) ) : 0;
$gpsuma_db       = new GPSUMA_DB();
$gpsuma_data     = $gpsuma_db->get_economic_report_data( $gpsuma_tenant, $gpsuma_anno, $gpsuma_cliente );
$gpsuma_rows     = $gpsuma_data['interventions'];
$gpsuma_contracts = $gpsuma_data['contracts'];
$gpsuma_clienti   = $gpsuma_data['customers'];

$gpsuma_totale_interventi = 0;
$gpsuma_incassato = 0;
$gpsuma_da_pagare = 0;
foreach ($gpsuma_rows as $gpsuma_r) {
    $gpsuma_sub = max(0, (float) $gpsuma_r->costo_manodopera + (float) $gpsuma_r->costo_materiali + (float) $gpsuma_r->costo_trasferta - (float) $gpsuma_r->sconto);
    $gpsuma_tot = $gpsuma_sub + $gpsuma_sub * (float) $gpsuma_r->aliquota_iva / 100;
    $gpsuma_totale_interventi += $gpsuma_tot;
    if ($gpsuma_r->stato_pagamento === 'Pagato') {
        $gpsuma_incassato += $gpsuma_tot;
    } elseif (!in_array($gpsuma_r->stato_pagamento, array('Incluso nel pacchetto', 'Non fatturabile'), true)) {
        $gpsuma_da_pagare += $gpsuma_tot;
    }
}

$gpsuma_totale_contratti = 0;
foreach ($gpsuma_contracts as $gpsuma_contract) {
    if ($gpsuma_contract->stato !== 'Bozza') {
        $gpsuma_totale_contratti += (float) $gpsuma_contract->valore_contratto;
    }
}
$gpsuma_totale_complessivo = $gpsuma_totale_interventi + $gpsuma_totale_contratti;

if (isset($_GET['gpsuma_export']) && check_admin_referer('gpsuma_export_economico')) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=riepilogo-economico-' . $gpsuma_anno . '.csv');
    $gpsuma_o = fopen('php://output', 'w');
    fputcsv($gpsuma_o, array('Tipo', 'Data', 'Cliente', 'Descrizione', 'Imponibile/Valore', 'IVA', 'Totale', 'Stato'), ';');
    foreach ($gpsuma_contracts as $gpsuma_contract) {
        if ($gpsuma_contract->stato === 'Bozza') { continue; }
        $gpsuma_value = (float) $gpsuma_contract->valore_contratto;
        fputcsv($gpsuma_o, array('Contratto', date_i18n('d/m/Y', strtotime($gpsuma_contract->data_inizio)), $gpsuma_contract->cliente_nome, $gpsuma_contract->nome, number_format($gpsuma_value, 2, ',', ''), '', number_format($gpsuma_value, 2, ',', ''), $gpsuma_contract->stato), ';');
    }
    foreach ($gpsuma_rows as $gpsuma_r) {
        $gpsuma_sub = max(0, (float) $gpsuma_r->costo_manodopera + (float) $gpsuma_r->costo_materiali + (float) $gpsuma_r->costo_trasferta - (float) $gpsuma_r->sconto);
        $gpsuma_tot = $gpsuma_sub + $gpsuma_sub * (float) $gpsuma_r->aliquota_iva / 100;
        fputcsv($gpsuma_o, array('Intervento', date_i18n('d/m/Y', strtotime($gpsuma_r->data_intervento)), $gpsuma_r->cliente_nome, $gpsuma_r->descrizione, number_format($gpsuma_sub, 2, ',', ''), number_format($gpsuma_tot - $gpsuma_sub, 2, ',', ''), number_format($gpsuma_tot, 2, ',', ''), $gpsuma_r->stato_pagamento), ';');
    }
    // The php://output stream is closed automatically when the request terminates.
    exit;
}
?>
<div class="wrap gpsuma-wrapper"><h1>💶 Riepilogo economico</h1>
<form class="gpsuma-box gpsuma-filter-form" method="get"><input type="hidden" name="page" value="gpsuma-economico"><label>Anno <input type="number" name="anno" value="<?php echo esc_attr($gpsuma_anno); ?>"></label><label>Cliente <select name="cliente"><option value="0">Tutti</option><?php foreach($gpsuma_clienti as $gpsuma_c): ?><option value="<?php echo esc_attr($gpsuma_c->id); ?>" <?php selected($gpsuma_cliente,(int)$gpsuma_c->id); ?>><?php echo esc_html($gpsuma_c->nome); ?></option><?php endforeach; ?></select></label><button class="button">Filtra</button><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gpsuma-economico&anno='.$gpsuma_anno.'&cliente='.$gpsuma_cliente.'&gpsuma_export=1'),'gpsuma_export_economico')); ?>">Esporta CSV</a></form>
<div class="gpsuma-stats-grid">
    <div class="gpsuma-stat-card"><span>Totale complessivo</span><strong><?php echo esc_html(number_format_i18n($gpsuma_totale_complessivo,2).' €'); ?></strong></div>
    <div class="gpsuma-stat-card"><span>Valore contratti</span><strong><?php echo esc_html(number_format_i18n($gpsuma_totale_contratti,2).' €'); ?></strong></div>
    <div class="gpsuma-stat-card"><span>Valore interventi</span><strong><?php echo esc_html(number_format_i18n($gpsuma_totale_interventi,2).' €'); ?></strong></div>
    <div class="gpsuma-stat-card"><span>Incassato interventi</span><strong><?php echo esc_html(number_format_i18n($gpsuma_incassato,2).' €'); ?></strong></div>
    <div class="gpsuma-stat-card"><span>Da incassare interventi</span><strong><?php echo esc_html(number_format_i18n($gpsuma_da_pagare,2).' €'); ?></strong></div>
    <div class="gpsuma-stat-card"><span>Contratti / Interventi</span><strong><?php echo esc_html(count($gpsuma_contracts).' / '.count($gpsuma_rows)); ?></strong></div>
</div>
<div class="gpsuma-box"><h2>Contratti</h2><p class="description">Il valore del contratto viene conteggiato nell’anno della sua data di inizio. I contratti in stato “Bozza” sono esclusi dai totali.</p><div class="gpsuma-table-scroll"><table class="widefat striped"><thead><tr><th>Data inizio</th><th>Cliente</th><th>Contratto</th><th>Periodo</th><th>Valore</th><th>Stato</th></tr></thead><tbody><?php if(!$gpsuma_contracts): ?><tr><td colspan="6">Nessun contratto per i filtri selezionati.</td></tr><?php endif; foreach($gpsuma_contracts as $gpsuma_contract): ?><tr><td><?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_contract->data_inizio))); ?></td><td><?php echo esc_html($gpsuma_contract->cliente_nome); ?></td><td><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-contratti&edit='.$gpsuma_contract->id)); ?>"><?php echo esc_html($gpsuma_contract->nome); ?></a></td><td><?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_contract->data_inizio))); ?><?php if($gpsuma_contract->data_fine): ?> → <?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_contract->data_fine))); ?><?php endif; ?></td><td><strong><?php echo esc_html(number_format_i18n((float)$gpsuma_contract->valore_contratto,2).' €'); ?></strong><?php if($gpsuma_contract->stato==='Bozza'): ?><br><small>Non conteggiato</small><?php endif; ?></td><td><?php echo esc_html($gpsuma_contract->stato); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="gpsuma-box"><h2>Interventi</h2><div class="gpsuma-table-scroll"><table class="widefat striped"><thead><tr><th>Data</th><th>Cliente</th><th>Intervento</th><th>Imponibile</th><th>IVA</th><th>Totale</th><th>Pagamento</th></tr></thead><tbody><?php if(!$gpsuma_rows): ?><tr><td colspan="7">Nessun intervento per i filtri selezionati.</td></tr><?php endif; foreach($gpsuma_rows as $gpsuma_r): $gpsuma_sub=max(0,(float)$gpsuma_r->costo_manodopera+(float)$gpsuma_r->costo_materiali+(float)$gpsuma_r->costo_trasferta-(float)$gpsuma_r->sconto);$gpsuma_tot=$gpsuma_sub+$gpsuma_sub*(float)$gpsuma_r->aliquota_iva/100; ?><tr><td><?php echo esc_html(date_i18n('d/m/Y',strtotime($gpsuma_r->data_intervento))); ?></td><td><?php echo esc_html($gpsuma_r->cliente_nome); ?></td><td><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&modifica='.$gpsuma_r->id)); ?>">#<?php echo esc_html($gpsuma_r->id); ?> — <?php echo esc_html(wp_trim_words($gpsuma_r->descrizione,12)); ?></a></td><td><?php echo esc_html(number_format_i18n($gpsuma_sub,2).' €'); ?></td><td><?php echo esc_html(number_format_i18n($gpsuma_tot-$gpsuma_sub,2).' €'); ?></td><td><strong><?php echo esc_html(number_format_i18n($gpsuma_tot,2).' €'); ?></strong></td><td><?php echo esc_html($gpsuma_r->stato_pagamento?:'Da pagare'); ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
