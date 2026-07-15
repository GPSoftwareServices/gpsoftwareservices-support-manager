<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
Plugin Name: GPSoftwareServices Support Manager
Plugin URI: https://gpsoftwareservices.it/gpsoftwareservices-support-manager/
Description: Manage customers, assets, technical interventions, service contracts and a secure customer portal directly in WordPress.
Version: 5.2.2
Requires at least: 6.4
Requires PHP: 7.4
Author: GP Software Services
Author URI: https://gpsoftwareservices.it/
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: gpsoftwareservices-support-manager
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('GPSUMA_VERSION', '5.2.2');
define('GPSUMA_PATH', plugin_dir_path(__FILE__));
define('GPSUMA_URL', plugin_dir_url(__FILE__));

require_once GPSUMA_PATH . 'includes/class-db.php';
require_once GPSUMA_PATH . 'includes/class-request.php';
require_once GPSUMA_PATH . 'includes/class-installer.php';
require_once GPSUMA_PATH . 'includes/class-admin.php';
require_once GPSUMA_PATH . 'includes/class-updater.php';
require_once GPSUMA_PATH . 'includes/class-frontend.php';
require_once GPSUMA_PATH . 'modules/calendar/class-calendar.php';
require_once GPSUMA_PATH . 'modules/reports/class-reports.php';
require_once GPSUMA_PATH . 'modules/core/class-tenant.php';
require_once GPSUMA_PATH . 'modules/core/class-app-shell.php';
require_once GPSUMA_PATH . 'modules/api/class-api.php';

register_activation_hook(__FILE__, array('GPSUMA_Installer', 'install'));



function gpsuma_check_updates() {
    GPSUMA_Updater::update();
}
add_action('plugins_loaded', 'gpsuma_check_updates');

function gpsuma_start_plugin() {
    if (is_admin()) {
        GPSUMA_Tenant::init();
        new GPSUMA_Admin();
        new GPSUMA_Calendar();
    }

    new GPSUMA_Frontend();
    new GPSUMA_Reports();
    GPSUMA_App_Shell::init();
    GPSUMA_API::init();
}
add_action('plugins_loaded', 'gpsuma_start_plugin');
