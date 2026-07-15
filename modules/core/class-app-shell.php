<?php
if (!defined('ABSPATH')) { exit; }

class GPSUMA_App_Shell {
    public static function init() {
        add_filter('admin_body_class', array(__CLASS__, 'body_class'));
        add_action('in_admin_header', array(__CLASS__, 'topbar'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
    }

    public static function is_gpsuma_page() {
        return is_admin() && 0 === strpos( sanitize_key( GPSUMA_Request::get_text( 'page' ) ), 'gpsuma-' );
    }

    public static function body_class($classes) {
        if (self::is_gpsuma_page()) { $classes .= ' gpsuma-admin-v2'; }
        return $classes;
    }

    public static function assets() {
        if (!self::is_gpsuma_page()) { return; }
        wp_enqueue_style('gpsuma-style', GPSUMA_URL . 'assets/css/admin.css', array(), GPSUMA_VERSION);
        $tenant = class_exists('GPSUMA_Tenant') ? GPSUMA_Tenant::current() : null;
        $primary = $tenant && !empty($tenant->colore_primario) ? sanitize_hex_color($tenant->colore_primario) : '#2563eb';
        if (!$primary) { $primary = '#2563eb'; }
        wp_add_inline_style('gpsuma-style', ':root{--gpsuma-primary:' . $primary . ';--gpsuma-primary-soft:' . $primary . '18}');
        wp_enqueue_script('gpsuma-v2-admin', GPSUMA_URL . 'assets/js/admin-v2.js', array(), GPSUMA_VERSION, true);
        wp_localize_script('gpsuma-v2-admin', 'gpsumaV2', array(
            'restUrl' => esc_url_raw(rest_url('gpsuma/v1/')),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }

    public static function topbar() {
        if (!self::is_gpsuma_page()) { return; }
        $tenant = class_exists('GPSUMA_Tenant') ? GPSUMA_Tenant::current() : null;
        $page = sanitize_key( GPSUMA_Request::get_text( 'page', 'gpsuma-dashboard' ) );
        $links = array(
            'gpsuma-dashboard' => array('Dashboard', 'dashicons-chart-area'),
            'gpsuma-clienti' => array('Clienti', 'dashicons-groups'),
            'gpsuma-dispositivi' => array('Dispositivi', 'dashicons-desktop'),
            'gpsuma-richieste' => array('Ticket', 'dashicons-tickets-alt'),
            'gpsuma-agenda' => array('Agenda', 'dashicons-calendar-alt'),
            'gpsuma-interventi' => array('Interventi', 'dashicons-admin-tools'),
        );
        echo '<div class="gpsuma-v2-topbar">';
        echo '<div class="gpsuma-v2-brand"><span class="gpsuma-v2-logo">G</span><div><strong>' . esc_html($tenant->nome ?? 'GPSoftwareServices Support Manager') . '</strong><small>Support Management</small></div></div>';
        echo '<nav class="gpsuma-v2-nav">';
        foreach ($links as $slug => $item) {
            $active = $page === $slug ? ' is-active' : '';
            echo '<a class="' . esc_attr(trim($active)) . '" href="' . esc_url(admin_url('admin.php?page=' . $slug)) . '"><span class="dashicons ' . esc_attr($item[1]) . '"></span>' . esc_html($item[0]) . '</a>';
        }
        echo '</nav>';
        echo '<div class="gpsuma-v2-actions"><a class="gpsuma-v2-search" href="' . esc_url(admin_url('admin.php?page=gpsuma-ricerca')) . '"><span class="dashicons dashicons-search"></span></a><a class="gpsuma-v2-new" href="' . esc_url(admin_url('admin.php?page=gpsuma-interventi')) . '">+ Nuovo intervento</a></div>';
        echo '</div>';
    }
}
