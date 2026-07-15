<?php

if (!defined('ABSPATH')) {
    exit;
}


class GPSUMA_Admin {



    public function __construct(){


        add_action(

            'admin_menu',

            array(
                $this,
                'menu'
            )

        );


        add_action(

            'admin_enqueue_scripts',

            array(
                $this,
                'assets'
            )

        );


    }





    /*
    |--------------------------------------------------------------------------
    | Menu amministrazione
    |--------------------------------------------------------------------------
    */


    public function menu(){



        add_menu_page(

            'GPSoftwareServices Support Manager',

            'GPSoftwareServices Support Manager',

            'manage_options',

            'gpsuma-dashboard',

            array(
                $this,
                'dashboard'
            ),

            'dashicons-sos',

            76

        );





        add_submenu_page(

            'gpsuma-dashboard',

            'Clienti',

            '👥 Clienti',

            'manage_options',

            'gpsuma-clienti',

            array(
                $this,
                'clienti'
            )

        );






        add_submenu_page(

            'gpsuma-dashboard',

            'Dispositivi',

            '💻 Dispositivi',

            'manage_options',

            'gpsuma-dispositivi',

            array(
                $this,
                'dispositivi'
            )

        );


        add_submenu_page(
            'gpsuma-dashboard',
            'Interventi',
            '🔧 Interventi',
            'manage_options',
            'gpsuma-interventi',
            array($this, 'interventi')
        );

        add_submenu_page(
            'gpsuma-dashboard',
            'Agenda interventi',
            '📅 Agenda',
            'manage_options',
            'gpsuma-agenda',
            array($this, 'agenda')
        );

        add_submenu_page(
            'gpsuma-dashboard',
            'Ticket',
            '🎫 Ticket',
            'manage_options',
            'gpsuma-richieste',
            array($this, 'richieste')
        );

        add_submenu_page(
            'gpsuma-dashboard',
            'Pacchetti',
            '📦 Pacchetti',
            'manage_options',
            'gpsuma-pacchetti',
            array($this, 'pacchetti')
        );


        add_submenu_page('gpsuma-dashboard','Contratti','📄 Contratti','manage_options','gpsuma-contratti',array($this,'contratti'));
        add_submenu_page('gpsuma-dashboard','Aziende','🏢 Aziende','manage_options','gpsuma-aziende',array($this,'aziende'));

        add_submenu_page(
            'gpsuma-dashboard',
            'Riepilogo economico',
            '💶 Economico',
            'manage_options',
            'gpsuma-economico',
            array($this, 'economico')
        );

        add_submenu_page(
            'gpsuma-dashboard',
            'Ricerca globale',
            '🔎 Ricerca globale',
            'manage_options',
            'gpsuma-ricerca',
            array($this, 'ricerca')
        );

        add_submenu_page(
            'gpsuma-dashboard',
            'Impostazioni',
            '⚙️ Impostazioni',
            'manage_options',
            'gpsuma-impostazioni',
            array($this, 'impostazioni')
        );

        add_submenu_page(
            'gpsuma-dashboard',
            'Scadenze',
            '⏰ Scadenze',
            'manage_options',
            'gpsuma-scadenze',
            array($this, 'scadenze')
        );

        add_submenu_page(
            null,
            'Scheda cliente',
            'Scheda cliente',
            'manage_options',
            'gpsuma-cliente-dettaglio',
            array($this, 'cliente_dettaglio')
        );

        add_submenu_page(
            null,
            'Scheda dispositivo',
            'Scheda dispositivo',
            'manage_options',
            'gpsuma-dispositivo-dettaglio',
            array($this, 'dispositivo_dettaglio')
        );





    }





    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */


    public function dashboard(){


        require GPSUMA_PATH .
        'admin/dashboard.php';


    }






    /*
    |--------------------------------------------------------------------------
    | Clienti
    |--------------------------------------------------------------------------
    */


    public function clienti(){


        require GPSUMA_PATH .
        'admin/clienti.php';


    }






    /*
    |--------------------------------------------------------------------------
    | Dispositivi
    |--------------------------------------------------------------------------
    */


    public function dispositivi(){


        require GPSUMA_PATH .
        'admin/dispositivi.php';


    }








    public function interventi(){
        require GPSUMA_PATH . 'admin/interventi.php';
    }

    public function agenda(){
        require GPSUMA_PATH . 'modules/calendar/admin-agenda.php';
    }

    public function pacchetti(){
        require GPSUMA_PATH . 'admin/pacchetti.php';
    }

    public function richieste(){
        require GPSUMA_PATH . 'admin/richieste.php';
    }

    public function scadenze(){
        require GPSUMA_PATH . 'admin/scadenze.php';
    }

    public function ricerca(){
        require GPSUMA_PATH . 'admin/ricerca.php';
    }

    public function contratti(){ require GPSUMA_PATH . 'modules/contracts/admin-contracts.php'; }
    public function aziende(){ require GPSUMA_PATH . 'modules/core/admin-tenants.php'; }

    public function economico(){
        require GPSUMA_PATH . 'admin/economico.php';
    }

    public function impostazioni(){
        require GPSUMA_PATH . 'admin/impostazioni.php';
    }


    public function cliente_dettaglio(){
        require GPSUMA_PATH . 'admin/cliente-dettaglio.php';
    }

    public function dispositivo_dettaglio(){
        require GPSUMA_PATH . 'admin/dispositivo-dettaglio.php';
    }

    /*
    |--------------------------------------------------------------------------
    | CSS
    |--------------------------------------------------------------------------
    */


    public function assets(){


        wp_enqueue_style(

            'gpsuma-style',

            GPSUMA_URL .
            'assets/css/admin.css',

            array(),

            GPSUMA_VERSION

        );


    }



}