<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Non autorizzato.', 'gpsoftwareservices-support-manager' ) ); }

$gpsuma_db = new GPSUMA_DB();
if ( isset( $_POST['gpsuma_save_company'] ) ) {
    check_admin_referer( 'gpsuma_company' );
    $id = GPSUMA_Request::post_int( 'id' );
    $gpsuma_data = array(
        'nome'              => GPSUMA_Request::post_text( 'nome' ),
        'ragione_sociale'   => GPSUMA_Request::post_text( 'ragione_sociale' ),
        'partita_iva'       => GPSUMA_Request::post_text( 'partita_iva' ),
        'codice_fiscale'    => GPSUMA_Request::post_text( 'codice_fiscale' ),
        'email'             => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
        'telefono'          => GPSUMA_Request::post_text( 'telefono' ),
        'indirizzo'         => GPSUMA_Request::post_textarea( 'indirizzo' ),
        'colore_primario'   => sanitize_hex_color( GPSUMA_Request::post_text( 'colore_primario' ) ) ?: '#2271b1',
        'stato'             => in_array( GPSUMA_Request::post_text( 'stato', 'Attiva' ), array( 'Attiva', 'Disattivata' ), true ) ? GPSUMA_Request::post_text( 'stato', 'Attiva' ) : 'Attiva',
    );
    $gpsuma_db->save_company( $gpsuma_data, $id );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Azienda salvata.', 'gpsoftwareservices-support-manager' ) . '</p></div>';
}
$gpsuma_edit_id = GPSUMA_Request::get_int( 'edit' );
$gpsuma_edit    = $gpsuma_edit_id ? $gpsuma_db->get_company( $gpsuma_edit_id ) : null;
$gpsuma_rows    = $gpsuma_db->get_companies();
?>
<div class="wrap gpsuma-wrap"><h1>🏢 <?php esc_html_e( 'Aziende / Tenant', 'gpsoftwareservices-support-manager' ); ?></h1>
<div class="gpsuma-card"><h2><?php echo esc_html( $gpsuma_edit ? 'Modifica azienda' : 'Nuova azienda' ); ?></h2>
<form method="post"><?php wp_nonce_field( 'gpsuma_company' ); ?><input type="hidden" name="id" value="<?php echo esc_attr( $gpsuma_edit->id ?? 0 ); ?>">
<div class="gpsuma-form-grid">
<p><label>Nome *</label><input required name="nome" value="<?php echo esc_attr( $gpsuma_edit->nome ?? '' ); ?>"></p>
<p><label>Ragione sociale</label><input name="ragione_sociale" value="<?php echo esc_attr( $gpsuma_edit->ragione_sociale ?? '' ); ?>"></p>
<p><label>Partita IVA</label><input name="partita_iva" value="<?php echo esc_attr( $gpsuma_edit->partita_iva ?? '' ); ?>"></p>
<p><label>Codice fiscale</label><input name="codice_fiscale" value="<?php echo esc_attr( $gpsuma_edit->codice_fiscale ?? '' ); ?>"></p>
<p><label>Email</label><input type="email" name="email" value="<?php echo esc_attr( $gpsuma_edit->email ?? '' ); ?>"></p>
<p><label>Telefono</label><input name="telefono" value="<?php echo esc_attr( $gpsuma_edit->telefono ?? '' ); ?>"></p>
<p><label>Colore principale</label><input type="color" name="colore_primario" value="<?php echo esc_attr( $gpsuma_edit->colore_primario ?? '#2271b1' ); ?>"></p>
<p><label>Stato</label><select name="stato"><option <?php selected( $gpsuma_edit->stato ?? 'Attiva', 'Attiva' ); ?>>Attiva</option><option <?php selected( $gpsuma_edit->stato ?? '', 'Disattivata' ); ?>>Disattivata</option></select></p>
</div><p><label>Indirizzo</label><textarea name="indirizzo"><?php echo esc_textarea( $gpsuma_edit->indirizzo ?? '' ); ?></textarea></p>
<button class="button button-primary" name="gpsuma_save_company" value="1">Salva azienda</button></form></div>
<div class="gpsuma-card"><h2>Aziende configurate</h2><table class="widefat striped"><thead><tr><th>Nome</th><th>P.IVA</th><th>Email</th><th>Stato</th><th></th></tr></thead><tbody>
<?php foreach ( $gpsuma_rows as $gpsuma_row ) : ?><tr><td><strong><?php echo esc_html( $gpsuma_row->nome ); ?></strong><?php if ( (int) $gpsuma_row->id === gpsuma_tenant_id() ) : ?> <span class="gpsuma-badge gpsuma-badge-green">Attiva ora</span><?php endif; ?></td><td><?php echo esc_html( $gpsuma_row->partita_iva ); ?></td><td><?php echo esc_html( $gpsuma_row->email ); ?></td><td><?php echo esc_html( $gpsuma_row->stato ); ?></td><td><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'gpsuma-aziende', 'edit' => (int) $gpsuma_row->id ), admin_url( 'admin.php' ) ) ); ?>">Modifica</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
