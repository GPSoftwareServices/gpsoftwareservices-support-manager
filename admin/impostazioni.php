<?php
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_options')) { wp_die(esc_html__('Non autorizzato.', 'gpsoftwareservices-support-manager')); }

if (isset($_POST['gpsuma_save_settings'])) {
    check_admin_referer('gpsuma_save_settings');
    update_option('gpsuma_delete_data_on_uninstall', isset($_POST['delete_data_on_uninstall']) ? 1 : 0, false);
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Impostazioni salvate.', 'gpsoftwareservices-support-manager') . '</p></div>';
}
$gpsuma_delete_data = (int) get_option('gpsuma_delete_data_on_uninstall', 0);
?>
<div class="wrap gpsuma-wrap">
    <h1><?php echo esc_html__('Impostazioni GPSoftwareServices Support Manager', 'gpsoftwareservices-support-manager'); ?></h1>
    <div class="gpsuma-box">
        <form method="post">
            <?php wp_nonce_field('gpsuma_save_settings'); ?>
            <h2><?php echo esc_html__('Disinstallazione', 'gpsoftwareservices-support-manager'); ?></h2>
            <label>
                <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($gpsuma_delete_data, 1); ?>>
                <?php echo esc_html__('Elimina definitivamente tutti i dati del plugin quando viene disinstallato.', 'gpsoftwareservices-support-manager'); ?>
            </label>
            <p class="description"><?php echo esc_html__('L’opzione è disattivata per impostazione predefinita. La semplice disattivazione del plugin non elimina mai i dati.', 'gpsoftwareservices-support-manager'); ?></p>
            <p><button type="submit" name="gpsuma_save_settings" class="button button-primary"><?php echo esc_html__('Salva impostazioni', 'gpsoftwareservices-support-manager'); ?></button></p>
        </form>
    </div>
</div>
