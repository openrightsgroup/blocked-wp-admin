<?php

class BlockedWP {
    const API_HOST = 'api.blocked.org.uk';

    private static $initiated = false;

    public static function init() {
        if (! self::$initiated) {
            self::init_hooks();
        }
    }

    private static function init_hooks() {
        add_action( 'admin_init', array( 'BlockedWP', 'admin_init' ) );
        add_action( 'admin_menu', array( 'BlockedWP', 'admin_menu' ), 5 ); # Priority 5, so it's called before Jetpack's admin_menu.
        //add_action( 'admin_notices', array( 'BlockedWP', 'display_notice' ) );
    }

    public static function admin_init() {
        error_log("admin_init");
        if ( get_option('Activated BlockedWP')) {
            delete_option( 'Activated BlockedWP');
        }

    }

    public static function admin_menu() {
        $hook = add_options_page( "BlockedWP Admin", "BlockedWP Admin",
            'manage_options', 'blocked-wp-admin',
            array( 'BlockedWP', 'display_page' ) );
    }


    public static function display_page() {
        error_log("display_page");
?>
    <h1>Blocked-WP Admin</h1>

        <form action="register" method="POST">
            <input class="button button-primary" type="submit" value="Register <site> with www.blocked.org.uk" />
        </form>
<?php
    }
}