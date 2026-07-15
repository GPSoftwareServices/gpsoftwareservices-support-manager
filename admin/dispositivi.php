<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db = new GPSUMA_DB();
$gpsuma_id_modifica = GPSUMA_Request::get_int( 'modifica' );
$gpsuma_id_elimina = GPSUMA_Request::get_int( 'elimina' );
$gpsuma_filtro_cliente = GPSUMA_Request::get_int( 'cliente_id' );
$gpsuma_messaggio = '';
$gpsuma_tipo_messaggio = 'success';

if ( $gpsuma_id_elimina > 0 ) {
	check_admin_referer( 'gpsuma_elimina_dispositivo_' . $gpsuma_id_elimina );
	$gpsuma_eliminato = $gpsuma_db->delete( 'dispositivi', array( 'id' => $gpsuma_id_elimina ), array( '%d' ) );
	$gpsuma_messaggio = false !== $gpsuma_eliminato ? 'Dispositivo eliminato correttamente.' : 'Errore durante l’eliminazione del dispositivo.';
	$gpsuma_tipo_messaggio = false !== $gpsuma_eliminato ? 'success' : 'error';
}

if ( isset( $_POST['gpsuma_salva_dispositivo'] ) && check_admin_referer( 'gpsuma_dispositivo_save' ) ) {
	$gpsuma_cliente_id = GPSUMA_Request::post_int( 'cliente_id' );
	if ( ! $gpsuma_cliente_id || ! $gpsuma_db->customer_exists( $gpsuma_cliente_id ) ) {
		$gpsuma_messaggio = 'Seleziona un cliente valido.';
		$gpsuma_tipo_messaggio = 'error';
	} else {
		$gpsuma_stato = GPSUMA_Request::post_text( 'stato', 'Operativo' );
		if ( ! in_array( $gpsuma_stato, array( 'Operativo', 'Da controllare', 'Guasto', 'Dismesso' ), true ) ) { $gpsuma_stato = 'Operativo'; }
		$gpsuma_dati = array(
			'cliente_id' => $gpsuma_cliente_id,
			'tipo' => GPSUMA_Request::post_text( 'tipo' ),
			'marca' => GPSUMA_Request::post_text( 'marca' ),
			'modello' => GPSUMA_Request::post_text( 'modello' ),
			'seriale' => GPSUMA_Request::post_text( 'seriale' ),
			'sistema' => GPSUMA_Request::post_text( 'sistema' ),
			'stato' => $gpsuma_stato,
			'note' => GPSUMA_Request::post_textarea( 'note' ),
		);
		$gpsuma_formati = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
		$gpsuma_salvato = $gpsuma_id_modifica
			? $gpsuma_db->update( 'dispositivi', $gpsuma_dati, array( 'id' => $gpsuma_id_modifica ), $gpsuma_formati, array( '%d' ) )
			: $gpsuma_db->insert( 'dispositivi', $gpsuma_dati, $gpsuma_formati );
		$gpsuma_messaggio = false !== $gpsuma_salvato ? ( $gpsuma_id_modifica ? 'Dispositivo aggiornato correttamente.' : 'Dispositivo inserito correttamente.' ) : 'Errore durante il salvataggio del dispositivo.';
		$gpsuma_tipo_messaggio = false !== $gpsuma_salvato ? 'success' : 'error';
	}
}
$gpsuma_clienti = $gpsuma_db->get_customers_admin();
$gpsuma_dispositivo_modifica = $gpsuma_id_modifica ? $gpsuma_db->get_device( $gpsuma_id_modifica ) : null;
$gpsuma_dispositivi = $gpsuma_db->get_devices_admin( $gpsuma_filtro_cliente );
$gpsuma_valore_cliente = $gpsuma_dispositivo_modifica ? (int) $gpsuma_dispositivo_modifica->cliente_id : 0;
?>

<div class="wrap gpsuma-wrapper">

    <h1>💻 Dispositivi</h1>

    <?php if ($gpsuma_messaggio): ?>
        <div class="notice notice-<?php echo esc_attr($gpsuma_tipo_messaggio); ?> is-dismissible">
            <p><?php echo esc_html($gpsuma_messaggio); ?></p>
        </div>
    <?php endif; ?>

    <div class="gpsuma-box">

        <h2>
            <?php echo $gpsuma_dispositivo_modifica ? 'Modifica dispositivo' : 'Nuovo dispositivo'; ?>
        </h2>

        <?php if (empty($gpsuma_clienti)): ?>

            <div class="notice notice-warning inline">
                <p>
                    Prima di inserire un dispositivo devi creare almeno un cliente.
                    <a href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti')); ?>">
                        Vai alla gestione clienti
                    </a>
                </p>
            </div>

        <?php else: ?>

            <form method="post">

                <?php wp_nonce_field('gpsuma_dispositivo_save'); ?>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            <label for="gpsuma-cliente-id">Cliente</label>
                        </th>
                        <td>
                            <select
                                id="gpsuma-cliente-id"
                                name="cliente_id"
                                required
                            >
                                <option value="">Seleziona cliente</option>

                                <?php foreach ($gpsuma_clienti as $gpsuma_cliente): ?>
                                    <option
                                        value="<?php echo esc_attr($gpsuma_cliente->id); ?>"
                                        <?php selected($gpsuma_valore_cliente, (int) $gpsuma_cliente->id); ?>
                                    >
                                        <?php echo esc_html($gpsuma_cliente->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gpsuma-tipo">Tipo dispositivo</label>
                        </th>
                        <td>
                            <input
                                id="gpsuma-tipo"
                                type="text"
                                name="tipo"
                                class="regular-text"
                                placeholder="Es. PC, Stampante, Server"
                                required
                                value="<?php
                                    echo $gpsuma_dispositivo_modifica
                                        ? esc_attr($gpsuma_dispositivo_modifica->tipo)
                                        : '';
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gpsuma-marca">Marca</label>
                        </th>
                        <td>
                            <input
                                id="gpsuma-marca"
                                type="text"
                                name="marca"
                                class="regular-text"
                                value="<?php
                                    echo $gpsuma_dispositivo_modifica
                                        ? esc_attr($gpsuma_dispositivo_modifica->marca)
                                        : '';
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gpsuma-modello">Modello</label>
                        </th>
                        <td>
                            <input
                                id="gpsuma-modello"
                                type="text"
                                name="modello"
                                class="regular-text"
                                value="<?php
                                    echo $gpsuma_dispositivo_modifica
                                        ? esc_attr($gpsuma_dispositivo_modifica->modello)
                                        : '';
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gpsuma-seriale">Numero seriale</label>
                        </th>
                        <td>
                            <input
                                id="gpsuma-seriale"
                                type="text"
                                name="seriale"
                                class="regular-text"
                                value="<?php
                                    echo $gpsuma_dispositivo_modifica
                                        ? esc_attr($gpsuma_dispositivo_modifica->seriale)
                                        : '';
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gpsuma-sistema">Sistema operativo</label>
                        </th>
                        <td>
                            <input
                                id="gpsuma-sistema"
                                type="text"
                                name="sistema"
                                class="regular-text"
                                placeholder="Es. Windows 11 Pro"
                                value="<?php
                                    echo $gpsuma_dispositivo_modifica
                                        ? esc_attr($gpsuma_dispositivo_modifica->sistema)
                                        : '';
                                ?>"
                            >
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><label for="gpsuma-stato">Stato dispositivo</label></th>
                        <td><select id="gpsuma-stato" name="stato">
                            <?php $gpsuma_stato_corrente = $gpsuma_dispositivo_modifica && !empty($gpsuma_dispositivo_modifica->stato) ? $gpsuma_dispositivo_modifica->stato : 'Operativo'; ?>
                            <?php foreach (array('Operativo','Da controllare','Guasto','Dismesso') as $gpsuma_st): ?>
                                <option value="<?php echo esc_attr($gpsuma_st); ?>" <?php selected($gpsuma_stato_corrente,$gpsuma_st); ?>><?php echo esc_html($gpsuma_st); ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gpsuma-note">Note</label>
                        </th>
                        <td>
                            <textarea
                                id="gpsuma-note"
                                name="note"
                                rows="4"
                                class="large-text"
                            ><?php
                                echo $gpsuma_dispositivo_modifica
                                    ? esc_textarea($gpsuma_dispositivo_modifica->note)
                                    : '';
                            ?></textarea>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <button
                        type="submit"
                        name="gpsuma_salva_dispositivo"
                        class="button button-primary"
                    >
                        <?php echo $gpsuma_dispositivo_modifica ? 'Aggiorna dispositivo' : 'Salva dispositivo'; ?>
                    </button>

                    <?php if ($gpsuma_dispositivo_modifica): ?>
                        <a
                            class="button"
                            href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi')); ?>"
                        >
                            Annulla modifica
                        </a>
                    <?php endif; ?>
                </p>

            </form>

        <?php endif; ?>

    </div>

    <div class="gpsuma-box">

        <div class="gpsuma-box-header">
            <h2>Elenco dispositivi</h2>

            <form method="get" class="gpsuma-filter-form">
                <input type="hidden" name="page" value="gpsuma-dispositivi">

                <label for="gpsuma-filtro-cliente">
                    Filtra per cliente
                </label>

                <select id="gpsuma-filtro-cliente" name="cliente_id">
                    <option value="0">Tutti i clienti</option>
                    <?php foreach ($gpsuma_clienti as $gpsuma_cliente): ?>
                        <option
                            value="<?php echo esc_attr($gpsuma_cliente->id); ?>"
                            <?php selected($gpsuma_filtro_cliente, (int) $gpsuma_cliente->id); ?>
                        >
                            <?php echo esc_html($gpsuma_cliente->nome); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button">
                    Filtra
                </button>

                <?php if ($gpsuma_filtro_cliente): ?>
                    <a
                        class="button"
                        href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivi')); ?>"
                    >
                        Azzera filtro
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="gpsuma-table-scroll">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Marca / Modello</th>
                        <th>Seriale</th>
                        <th>Sistema operativo</th>
                        <th>Stato</th>
                        <th>Note</th>
                        <th>Azioni</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($gpsuma_dispositivi)): ?>
                        <tr>
                            <td colspan="9">
                                Nessun dispositivo trovato.
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($gpsuma_dispositivi as $gpsuma_dispositivo): ?>
                            <tr>
                                <td><?php echo esc_html($gpsuma_dispositivo->id); ?></td>
                                <td><?php echo esc_html($gpsuma_dispositivo->cliente_nome); ?></td>
                                <td><?php echo esc_html($gpsuma_dispositivo->tipo); ?></td>
                                <td>
                                    <?php
                                    $gpsuma_marca_modello = trim(
                                        $gpsuma_dispositivo->marca . ' ' . $gpsuma_dispositivo->modello
                                    );

                                    echo $gpsuma_marca_modello
                                        ? esc_html($gpsuma_marca_modello)
                                        : '—';
                                    ?>
                                </td>
                                <td>
                                    <?php echo $gpsuma_dispositivo->seriale ? esc_html($gpsuma_dispositivo->seriale) : '—'; ?>
                                </td>
                                <td>
                                    <?php echo $gpsuma_dispositivo->sistema ? esc_html($gpsuma_dispositivo->sistema) : '—'; ?>
                                </td>
                                <td><span class="gpsuma-device-status gpsuma-device-status-<?php echo esc_attr(sanitize_title($gpsuma_dispositivo->stato ?: 'Operativo')); ?>"><?php echo esc_html($gpsuma_dispositivo->stato ?: 'Operativo'); ?></span></td>
                                <td class="gpsuma-note-cell">
                                    <?php echo $gpsuma_dispositivo->note ? esc_html($gpsuma_dispositivo->note) : '—'; ?>
                                </td>
                                <td class="gpsuma-actions">
                                    <a
                                        class="button button-primary"
                                        href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-dispositivo-dettaglio&id=' . (int) $gpsuma_dispositivo->id)); ?>"
                                    >
                                        👁 Apri scheda
                                    </a>

                                    <a
                                        class="button"
                                        href="<?php echo esc_url(
                                            admin_url(
                                                'admin.php?page=gpsuma-dispositivi&modifica=' .
                                                (int) $gpsuma_dispositivo->id
                                            )
                                        ); ?>"
                                    >
                                        ✏️ Modifica
                                    </a>

                                    <a
                                        class="button"
                                        href="<?php echo esc_url(
                                            wp_nonce_url(
                                                admin_url(
                                                    'admin.php?page=gpsuma-dispositivi&elimina=' .
                                                    (int) $gpsuma_dispositivo->id
                                                ),
                                                'gpsuma_elimina_dispositivo_' . (int) $gpsuma_dispositivo->id
                                            )
                                        ); ?>"
                                        onclick="return confirm('Eliminare questo dispositivo?');"
                                    >
                                        🗑 Elimina
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>

</div>
