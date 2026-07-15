<?php
if (!defined('ABSPATH')) { exit; }

$gpsuma_dispositivo_id = GPSUMA_Request::get_int( 'id' );
if ( ! $gpsuma_dispositivo_id ) {
    wp_die( esc_html__( 'Dispositivo non valido.', 'gpsoftwareservices-support-manager' ) );
}

$gpsuma_db = new GPSUMA_DB();
$gpsuma_summary = $gpsuma_db->get_device_admin_summary( $gpsuma_dispositivo_id );
if ( ! $gpsuma_summary ) {
    wp_die( esc_html__( 'Dispositivo non trovato.', 'gpsoftwareservices-support-manager' ) );
}
$gpsuma_dispositivo = $gpsuma_summary['dispositivo'];
$gpsuma_interventi = $gpsuma_summary['interventi'];
$gpsuma_allegati_per_intervento = $gpsuma_summary['allegati'];
$gpsuma_scadenze = $gpsuma_summary['scadenze'];
$gpsuma_tot_interventi = count($gpsuma_interventi);
$gpsuma_tot_minuti = 0;
foreach ($gpsuma_interventi as $gpsuma_i) { $gpsuma_tot_minuti += (int) $gpsuma_i->durata_minuti; }
$gpsuma_ultimo = $gpsuma_interventi ? $gpsuma_interventi[0] : null;
$gpsuma_nome = trim($gpsuma_dispositivo->tipo . ' ' . $gpsuma_dispositivo->marca . ' ' . $gpsuma_dispositivo->modello);
if (!$gpsuma_nome) { $gpsuma_nome = 'Dispositivo #' . $gpsuma_dispositivo->id; }

function gpsuma_durata($minuti) {
    $minuti = (int) $minuti;
    if ($minuti <= 0) return '—';
    $ore = intdiv($minuti, 60);
    $resto = $minuti % 60;
    if ($ore && $resto) return $ore . ' h ' . $resto . ' min';
    if ($ore) return $ore . ' h';
    return $resto . ' min';
}
?>
<div class="wrap gpsuma-wrapper gpsuma-device-360">
    <div class="gpsuma-customer-topbar">
        <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi&cliente_id='.(int)$gpsuma_dispositivo->cliente_id)); ?>">← Torna ai dispositivi</a>
        <div class="gpsuma-customer-actions">
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi&modifica='.(int)$gpsuma_dispositivo->id)); ?>">✏️ Modifica</a>
            <a class="button" target="_blank" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=gpsuma_device_label&id='.(int)$gpsuma_dispositivo->id),'gpsuma_label_'.(int)$gpsuma_dispositivo->id)); ?>">🏷️ QR / Etichetta</a>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&cliente_id='.(int)$gpsuma_dispositivo->cliente_id.'&dispositivo_id='.(int)$gpsuma_dispositivo->id)); ?>">+ Nuovo intervento</a>
        </div>
    </div>

    <section class="gpsuma-customer-hero gpsuma-device-hero">
        <div class="gpsuma-customer-avatar">💻</div>
        <div class="gpsuma-customer-identity">
            <span class="gpsuma-eyebrow">Scheda dispositivo</span> <span class="gpsuma-device-status gpsuma-device-status-<?php echo esc_attr(sanitize_title($gpsuma_dispositivo->stato ?: 'Operativo')); ?>"><?php echo esc_html($gpsuma_dispositivo->stato ?: 'Operativo'); ?></span>
            <h1><?php echo esc_html($gpsuma_nome); ?></h1>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-cliente-dettaglio&id='.(int)$gpsuma_dispositivo->cliente_id)); ?>"><?php echo esc_html($gpsuma_dispositivo->cliente_nome); ?></a></p>
        </div>
        <div class="gpsuma-customer-contact-grid">
            <div><small>Tipo</small><strong><?php echo esc_html($gpsuma_dispositivo->tipo ?: '—'); ?></strong></div>
            <div><small>Seriale</small><strong><?php echo esc_html($gpsuma_dispositivo->seriale ?: '—'); ?></strong></div>
            <div><small>Sistema</small><strong><?php echo esc_html($gpsuma_dispositivo->sistema ?: '—'); ?></strong></div>
            <div><small>Inserito il</small><strong><?php echo esc_html(date_i18n('d/m/Y', strtotime($gpsuma_dispositivo->data_creazione))); ?></strong></div>
        </div>
    </section>

    <div class="gpsuma-customer-stats">
        <div><span>🔧</span><strong><?php echo esc_html($gpsuma_tot_interventi); ?></strong><small>Interventi</small></div>
        <div><span>⏱️</span><strong><?php echo esc_html(gpsuma_durata($gpsuma_tot_minuti)); ?></strong><small>Tempo totale</small></div>
        <div><span>📅</span><strong><?php echo $gpsuma_ultimo ? esc_html(date_i18n('d/m/Y', strtotime($gpsuma_ultimo->data_intervento))) : '—'; ?></strong><small>Ultimo intervento</small></div>
        <div><span>🏷️</span><strong><?php echo esc_html($gpsuma_dispositivo->marca ?: '—'); ?></strong><small>Marca</small></div>
    </div>

    <div class="gpsuma-customer-layout">
        <div class="gpsuma-customer-main">
            <section class="gpsuma-panel">
                <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">🧾</span><h2>Storico interventi</h2></div></div>
                <?php if (!$gpsuma_interventi): ?>
                    <div class="gpsuma-empty-state">Nessun intervento collegato a questo dispositivo.</div>
                <?php else: ?>
                    <div class="gpsuma-device-timeline">
                        <?php foreach ($gpsuma_interventi as $gpsuma_i): ?>
                            <article>
                                <div class="gpsuma-device-timeline-date"><strong><?php echo esc_html(date_i18n('d/m/Y', strtotime($gpsuma_i->data_intervento))); ?></strong><small><?php echo esc_html(substr((string)$gpsuma_i->ora_intervento,0,5)); ?></small></div>
                                <div class="gpsuma-device-timeline-dot"></div>
                                <div class="gpsuma-device-timeline-content">
                                    <div><span class="gpsuma-status-pill"><?php echo esc_html($gpsuma_i->stato); ?></span><small><?php echo esc_html(gpsuma_durata($gpsuma_i->durata_minuti)); ?></small></div>
                                    <h3><?php echo esc_html($gpsuma_i->tipo ?: 'Intervento'); ?></h3>
                                    <p><?php echo nl2br(esc_html($gpsuma_i->descrizione)); ?></p>
                                    <?php if ($gpsuma_i->materiale): ?><p><strong>Materiale:</strong> <?php echo nl2br(esc_html($gpsuma_i->materiale)); ?></p><?php endif; ?>
                                    <?php if ($gpsuma_i->tecnico): ?><small>Tecnico: <?php echo esc_html($gpsuma_i->tecnico); ?></small><?php endif; ?>
                                    <?php if(!empty($gpsuma_allegati_per_intervento[$gpsuma_i->id])): ?><div class="gpsuma-timeline-attachments"><strong>Allegati</strong><?php foreach($gpsuma_allegati_per_intervento[$gpsuma_i->id] as $gpsuma_a): $gpsuma_url=wp_get_attachment_url($gpsuma_a->attachment_id); ?><a href="<?php echo esc_url($gpsuma_url); ?>" target="_blank" rel="noopener">📎 <?php echo esc_html(get_the_title($gpsuma_a->attachment_id)?:basename((string)$gpsuma_url)); ?></a><?php endforeach; ?></div><?php endif; ?>
                                    <p><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-interventi&modifica='.(int)$gpsuma_i->id)); ?>">Apri intervento →</a> · <a target="_blank" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=gpsuma_intervento_report&id='.(int)$gpsuma_i->id),'gpsuma_report_'.(int)$gpsuma_i->id)); ?>">📄 Rapporto PDF</a></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="gpsuma-customer-side">
            <section class="gpsuma-panel">
                <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">ℹ️</span><h2>Dettagli tecnici</h2></div></div>
                <dl class="gpsuma-device-details">
                    <div><dt>Tipo</dt><dd><?php echo esc_html($gpsuma_dispositivo->tipo ?: '—'); ?></dd></div>
                    <div><dt>Marca</dt><dd><?php echo esc_html($gpsuma_dispositivo->marca ?: '—'); ?></dd></div>
                    <div><dt>Modello</dt><dd><?php echo esc_html($gpsuma_dispositivo->modello ?: '—'); ?></dd></div>
                    <div><dt>Numero seriale</dt><dd><?php echo esc_html($gpsuma_dispositivo->seriale ?: '—'); ?></dd></div>
                    <div><dt>Sistema operativo</dt><dd><?php echo esc_html($gpsuma_dispositivo->sistema ?: '—'); ?></dd></div>
                    <div><dt>Stato</dt><dd><span class="gpsuma-device-status gpsuma-device-status-<?php echo esc_attr(sanitize_title($gpsuma_dispositivo->stato ?: 'Operativo')); ?>"><?php echo esc_html($gpsuma_dispositivo->stato ?: 'Operativo'); ?></span></dd></div>
                </dl>
            </section>
            <section class="gpsuma-panel">
                <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">⏰</span><h2>Scadenze</h2></div></div>
                <?php if(!$gpsuma_scadenze): ?><p>Nessuna scadenza attiva.</p><?php else: ?><div class="gpsuma-device-deadlines"><?php foreach($gpsuma_scadenze as $gpsuma_sc): $gpsuma_days=(int)floor((strtotime($gpsuma_sc->data_scadenza)-strtotime(current_time('Y-m-d')))/DAY_IN_SECONDS); ?><a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-scadenze&modifica='.(int)$gpsuma_sc->id)); ?>"><strong><?php echo esc_html($gpsuma_sc->titolo); ?></strong><span><?php echo esc_html($gpsuma_sc->tipo.' · '.date_i18n('d/m/Y',strtotime($gpsuma_sc->data_scadenza))); ?></span><small><?php echo $gpsuma_days<0?esc_html('Scaduta da '.abs($gpsuma_days).' giorni'):esc_html('Tra '.$gpsuma_days.' giorni'); ?></small></a><?php endforeach; ?></div><?php endif; ?>
            </section>
            <section class="gpsuma-panel">
                <div class="gpsuma-section-title"><div><span class="gpsuma-section-icon">📝</span><h2>Note</h2></div></div>
                <p class="gpsuma-preline"><?php echo $gpsuma_dispositivo->note ? nl2br(esc_html($gpsuma_dispositivo->note)) : 'Nessuna nota presente.'; ?></p>
            </section>
        </aside>
    </div>
</div>
