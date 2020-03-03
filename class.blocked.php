<?php

class BlockedWP {
    const API_HOST = 'api.blocked.org.uk';
    const OPTION_REGISTERED = 'blocked_wp_registered';
    const OPTION_SECRET = 'blocked_wp_secret';

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

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (@$_POST['submitsite']) {
                BlockedWP::submit_site();
            } else {
                if ($_POST['action'] == 'register') {
                    BlockedWP::register_user();
                    update_option(BlockedWP::OPTION_REGISTERED, true);
                } elseif ($_POST['action'] == 'unregister') {
                    delete_option(BlockedWP::OPTION_REGISTERED);
                }
            }
        }

?>
    <h1>Blocked-WP Admin</h1>

        <form method="POST">
            <?php if (! get_option(BlockedWP::OPTION_REGISTERED, false)): ?>
                <input class="button button-primary" type="submit" value="Register <?php echo get_option("siteurl") ?> with www.blocked.org.uk" />
                <input type="hidden" name="action" value="register" />
            <?php else: ?>
                <input class="button button-primary" type="submit" value="Unregister <?php echo get_option("siteurl") ?>" />
                <input type="hidden" name="action" value="unregister" />
            <?php endif ?>
            <input class="button button-primary" type="submit" name="submitsite" value="Submit Site" />
        </form>
        <?php echo get_option(BlockedWP::OPTION_REGISTERED, false); ?> <br/>
        <?php echo get_option("admin_email", false); ?> <br/>
        <?php echo get_option("siteurl", false); ?> <br/>
        <?php echo get_option(BlockedWP::OPTION_SECRET, false); ?> <br/>
<?php
        BlockedWP::format_results( BlockedWP::get_results() );
    }

    public static function register_user() {
        $response = wp_remote_post(
            "https://" . BlockedWP::API_HOST . '/1.2/register/user',
            array(
                'body' => array(
                    'email' => get_option('admin_email'),
                    'password' => wp_generate_password()
                )
            )
        );
        if ($response['response']['code'] != 201) {
            print "<h3>Error creating remote account: " . $response['response']['code'] . "</h3>";
        } else {
            $jsonresponse = json_decode($response['body']);
            update_option(BlockedWP::OPTION_SECRET, $jsonresponse->secret);
        }
    }

    public static function submit_site() {
        $args = array(
            'headers' => array(
                'Authorization' => "Basic " . base64_encode(get_option('admin_email') . ':' . get_option(BlockedWP::OPTION_SECRET))
            ),
            'body' => array(
                "url" => get_option('siteurl'),
                "source" => "wp-plugin",
                "email" => get_option('admin_email')
            )
        );
        print_r($args);
        $response = wp_remote_post("https://" . BlockedWP::API_HOST . '/1.2/submit/url', $args);

        error_log("submit site: " . $response['response']['code']);

    }

    public static function get_results() {
        $args = array(
            "url" => get_option('siteurl'),
        );
        $options = array(
            "headers" => array(
                "Authorization" => "Basic " . base64_encode(get_option('admin_email') . ':' . get_option(BlockedWP::OPTION_SECRET))
            )
        );

        $response = wp_remote_get(
            "https://" . BlockedWP::API_HOST . "/1.2/status/url?" . http_build_query($args),
            $options
        );

        $jsondata = json_decode($response['body']);
        return $jsondata->results;

    }

    function format_results($results) {
?>
    <table class="table table-compressed" style="margin-top: 2em; margin-left: 4em; margin-right: 4em">
        <tr>
            <th style="width: 50%">Network</th>
            <th style="width: 25%; text-align: right">Checked at</th>
            <th style="width: 25%">Result</th>
        </tr>
<?php
    foreach($results as $result):
?>
        <tr>
            <td><?php echo $result->network_name; ?></td>
            <td style="text-align: right"><?php echo $result->status_timestamp; ?></td>
            <td style="text-align: right"><?php echo $result->status; ?></td>
        </tr>
<?php
    endforeach
?>
    </table>
<?php
    }
}