<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db = new GPSUMA_DB();
$gpsuma_id_modifica = GPSUMA_Request::get_int( 'modifica' );
$gpsuma_id_elimina  = GPSUMA_Request::get_int( 'elimina' );
$gpsuma_messaggio = '';
$gpsuma_tipo_messaggio = 'success';

if ( $gpsuma_id_elimina > 0 ) {
	check_admin_referer( 'gpsuma_elimina_cliente_' . $gpsuma_id_elimina );
	if ( $gpsuma_db->count_customer_devices( $gpsuma_id_elimina ) > 0 ) {
		$gpsuma_messaggio = 'Non puoi eliminare il cliente perché ha dispositivi associati.';
		$gpsuma_tipo_messaggio = 'error';
	} else {
		$gpsuma_eliminato = $gpsuma_db->delete( 'clienti', array( 'id' => $gpsuma_id_elimina ), array( '%d' ) );
		$gpsuma_messaggio = false !== $gpsuma_eliminato ? 'Cliente eliminato correttamente.' : 'Errore durante l’eliminazione del cliente.';
		$gpsuma_tipo_messaggio = false !== $gpsuma_eliminato ? 'success' : 'error';
	}
}

if ( isset( $_POST['gpsuma_salva_cliente'] ) && check_admin_referer( 'gpsuma_cliente_save' ) ) {
	$gpsuma_user_id = GPSUMA_Request::post_int( 'user_id' );
	$gpsuma_nome = GPSUMA_Request::post_text( 'nome' );
	if ( '' === $gpsuma_nome ) {
		$gpsuma_messaggio = 'Il nome del cliente è obbligatorio.';
		$gpsuma_tipo_messaggio = 'error';
	} elseif ( $gpsuma_user_id && $gpsuma_db->find_customer_by_user( $gpsuma_user_id, $gpsuma_id_modifica ) ) {
		$gpsuma_messaggio = 'Questo utente WordPress è già associato a un altro cliente.';
		$gpsuma_tipo_messaggio = 'error';
	} else {
		$gpsuma_dati = array(
			'user_id' => $gpsuma_user_id ? $gpsuma_user_id : null,
			'nome' => $gpsuma_nome,
			'telefono' => GPSUMA_Request::post_text( 'telefono' ),
			'email' => sanitize_email( GPSUMA_Request::post_text( 'email' ) ),
			'indirizzo' => GPSUMA_Request::post_textarea( 'indirizzo' ),
			'note' => GPSUMA_Request::post_textarea( 'note' ),
		);
		$gpsuma_formati = array( '%d', '%s', '%s', '%s', '%s', '%s' );
		$gpsuma_salvato = $gpsuma_id_modifica
			? $gpsuma_db->update( 'clienti', $gpsuma_dati, array( 'id' => $gpsuma_id_modifica ), $gpsuma_formati, array( '%d' ) )
			: $gpsuma_db->insert( 'clienti', $gpsuma_dati, $gpsuma_formati );
		$gpsuma_messaggio = false !== $gpsuma_salvato ? ( $gpsuma_id_modifica ? 'Cliente aggiornato correttamente.' : 'Cliente inserito correttamente.' ) : 'Errore durante il salvataggio del cliente.';
		$gpsuma_tipo_messaggio = false !== $gpsuma_salvato ? 'success' : 'error';
		if ( false !== $gpsuma_salvato && $gpsuma_user_id ) {
			$gpsuma_utente = get_user_by( 'id', $gpsuma_user_id );
			if ( $gpsuma_utente && ! in_array( 'administrator', (array) $gpsuma_utente->roles, true ) ) { $gpsuma_utente->set_role( 'gpsuma_cliente' ); }
		}
	}
}

$gpsuma_cliente_modifica = $gpsuma_id_modifica ? $gpsuma_db->get_customer( $gpsuma_id_modifica ) : null;
$gpsuma_utenti = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
$gpsuma_clienti = $gpsuma_db->get_customers_admin();
?>

<div class="wrap gpsuma-wrapper">
    <h1>👥 Clienti</h1>

    <?php if ($gpsuma_messaggio): ?>
        <div class="notice notice-<?php echo esc_attr($gpsuma_tipo_messaggio); ?> is-dismissible">
            <p><?php echo esc_html($gpsuma_messaggio); ?></p>
        </div>
    <?php endif; ?>

    <div class="gpsuma-box">
        <h2><?php echo $gpsuma_cliente_modifica ? 'Modifica cliente' : 'Nuovo cliente'; ?></h2>

        <form method="post">
            <?php wp_nonce_field('gpsuma_cliente_save'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="gpsuma-nome">Nome</label></th>
                    <td>
                        <input id="gpsuma-nome" type="text" name="nome" class="regular-text" required
                            value="<?php echo $gpsuma_cliente_modifica ? esc_attr($gpsuma_cliente_modifica->nome) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gpsuma-telefono">Telefono</label></th>
                    <td>
                        <input id="gpsuma-telefono" type="text" name="telefono" class="regular-text"
                            value="<?php echo $gpsuma_cliente_modifica ? esc_attr($gpsuma_cliente_modifica->telefono) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gpsuma-email">Email</label></th>
                    <td>
                        <input id="gpsuma-email" type="email" name="email" class="regular-text"
                            value="<?php echo $gpsuma_cliente_modifica ? esc_attr($gpsuma_cliente_modifica->email) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gpsuma-user-id">Accesso area cliente</label></th>
                    <td>
                        <select id="gpsuma-user-id" name="user_id">
                            <option value="0">Nessun utente associato</option>
                            <?php foreach ($gpsuma_utenti as $gpsuma_utente): ?>
                                <option
                                    value="<?php echo esc_attr($gpsuma_utente->ID); ?>"
                                    <?php selected(
                                        $gpsuma_cliente_modifica ? (int) $gpsuma_cliente_modifica->user_id : 0,
                                        (int) $gpsuma_utente->ID
                                    ); ?>
                                >
                                    <?php echo esc_html($gpsuma_utente->display_name . ' — ' . $gpsuma_utente->user_email); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            Crea prima l’utente da <strong>Utenti → Aggiungi nuovo</strong>, poi associalo qui.
                            L’utente vedrà soltanto i dati di questo cliente.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gpsuma-indirizzo">Indirizzo</label></th>
                    <td>
                        <textarea id="gpsuma-indirizzo" name="indirizzo" rows="3" class="large-text"><?php
                            echo $gpsuma_cliente_modifica ? esc_textarea($gpsuma_cliente_modifica->indirizzo) : '';
                        ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gpsuma-note">Note interne</label></th>
                    <td>
                        <textarea id="gpsuma-note" name="note" rows="3" class="large-text"><?php
                            echo $gpsuma_cliente_modifica ? esc_textarea($gpsuma_cliente_modifica->note) : '';
                        ?></textarea>
                        <p class="description">Le note interne non vengono mostrate nell’area cliente.</p>
                    </td>
                </tr>
            </table>

            <button type="submit" name="gpsuma_salva_cliente" class="button button-primary">
                <?php echo $gpsuma_cliente_modifica ? 'Aggiorna cliente' : 'Salva cliente'; ?>
            </button>

            <?php if ($gpsuma_cliente_modifica): ?>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti')); ?>">Annulla modifica</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="gpsuma-box">
        <h2>Elenco clienti</h2>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Telefono</th>
                    <th>Email</th>
                    <th>Accesso area cliente</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gpsuma_clienti)): ?>
                    <tr><td colspan="6">Nessun cliente inserito.</td></tr>
                <?php else: ?>
                    <?php foreach ($gpsuma_clienti as $gpsuma_cliente): ?>
                        <tr>
                            <td><?php echo esc_html($gpsuma_cliente->id); ?></td>
                            <td><?php echo esc_html($gpsuma_cliente->nome); ?></td>
                            <td><?php echo esc_html($gpsuma_cliente->telefono); ?></td>
                            <td><?php echo esc_html($gpsuma_cliente->email); ?></td>
                            <td>
                                <?php if ($gpsuma_cliente->user_id): ?>
                                    ✅ <?php echo esc_html($gpsuma_cliente->utente_nome . ' — ' . $gpsuma_cliente->utente_email); ?>
                                <?php else: ?>
                                    <span style="color:#777">Non configurato</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-cliente-dettaglio&cliente_id=' . $gpsuma_cliente->id)); ?>">Apri scheda</a>
                                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gpsuma-clienti&modifica=' . $gpsuma_cliente->id)); ?>">✏️ Modifica</a>
                                <a class="button" href="<?php echo esc_url(wp_nonce_url(
                                    admin_url('admin.php?page=gpsuma-clienti&elimina=' . $gpsuma_cliente->id),
                                    'gpsuma_elimina_cliente_' . $gpsuma_cliente->id
                                )); ?>" onclick="return confirm('Eliminare questo cliente?');">🗑 Elimina</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
