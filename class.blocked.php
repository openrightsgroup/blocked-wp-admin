<?php

class BlockedWP {
    const API_HOST = 'api.blocked.org.uk';
    const OPTION_REGISTERED = 'blocked_wp_registered';
    const OPTION_SECRET = 'blocked_wp_secret';
    const OPTION_LAST_VIEW = 'blocked_wp_last_view';
    const OPTION_LAST_BLOCK = 'blocked_wp_last_block';

    const DEFAULT_TIMESTAMP = '0000-00-00 00:00:00';

    private static $initiated = false;

    public static function init() {
        if (! self::$initiated) {
            self::init_hooks();
            self::$initiated = true;
        }
    }

    private static function init_hooks() {
        add_action( 'admin_init', array( 'BlockedWP', 'admin_init' ) );
        add_action( 'admin_menu', array( 'BlockedWP', 'admin_menu' ), 5 ); # Priority 5, so it's called before Jetpack's admin_menu.
        add_action( 'admin_enqueue_scripts', array('BlockedWP', 'load_resources'));

        if (BlockedWP::is_registered()) {
            add_action('admin_notices', array( 'BlockedWP', 'show_alert' ) );
            add_action('blockedwp_cron', array('BlockedWP', 'invoke_cron') );

            if ( ! wp_next_scheduled( 'blockedwp_cron' ) ) {
                wp_schedule_event( time(), 'hourly', 'blockedwp_cron' );
            }
        }
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

    public static function is_registered() {
        return get_option(BlockedWP::OPTION_REGISTERED, false);
    }

    public static function datestamp() {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }

    public static function update_lastview() {
        error_log("update");
        update_option(BlockedWP::OPTION_LAST_VIEW, BlockedWP::datestamp());
    }

    public static function get_last_view() {
        return get_option(BlockedWP::OPTION_LAST_VIEW, BlockedWP::DEFAULT_TIMESTAMP);
    }

    public static function get_last_block() {
        return get_option(BlockedWP::OPTION_LAST_BLOCK, BlockedWP::DEFAULT_TIMESTAMP);
    }

    public static function display_page() {

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
        <p>This plugin registers your site with <a href="https://www.blocked.org.uk">Blocked!</a>, a site monitoring platform provided by <a href="https://www.openrightsgroup">Open Rights Group</a>.</p>
        <p>The Blocked! platform checks your site through the parental control filters on major UK ISPs, and reports back to you on this status screen.</p>



        <form method="POST">
            <?php if (! BlockedWP::is_registered()) : ?>
                <input class="button button-primary" type="submit" value="Register <?php echo get_option("home") ?> with www.blocked.org.uk" />
                <input type="hidden" name="action" value="register" />
            <?php else: ?>
                <input class="button button-primary" type="submit" value="Unregister <?php echo get_option("home") ?>" />
                <input type="hidden" name="action" value="unregister" />
            <?php endif ?>
            <!-- <input class="button button-primary" type="submit" name="submitsite" value="Submit Site" /> -->
        </form>
<?php
        if (BlockedWP::is_registered()) {
            BlockedWP::format_results(BlockedWP::get_results());
        }
?>
        <p><a class="button button-primary" href="https://www.blocked.org.uk/site/<?php echo get_option('home')?>">View full results</a></p>
        <p><a href="https://www.blocked.org.uk">About Blocked!</a> | <a href="">Privacy Policy</a></p>
<?php
        BlockedWP::update_lastview();
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

            BlockedWP::submit_site();
        }
    }

    public static function submit_site() {
        $args = array(
            'headers' => array(
                'Authorization' => "Basic " . base64_encode(get_option('admin_email') . ':' . get_option(BlockedWP::OPTION_SECRET))
            ),
            'body' => array(
                "url" => get_option('home'),
                "source" => "wp-plugin",
                "email" => get_option('admin_email')
            )
        );

        $response = wp_remote_post("https://" . BlockedWP::API_HOST . '/1.2/submit/url', $args);

        error_log("submit site: " . $response['response']['code']);

    }

    public static function get_results() {
        $args = array(
            "url" => get_option('home'),
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

        $lastblock = BlockedWP::get_last_block();
        foreach ($jsondata->results as $result) {
            if ($result->status == 'blocked') {
                if ($result->status_timestamp > $lastblock) {
                    $lastblock = $result->status_timestamp;
                }
            }
        }
        if ($lastblock) {
            update_option(BlockedWP::OPTION_LAST_BLOCK, $lastblock);
        }
        return $jsondata->results;

    }

    function format_results($results) {
?>
    <table class="table table-compressed" style="margin-top: 2em; margin-left: 4em; margin-right: 4em">
        <tr>
            <th style="width: 50%">Network</th>
            <th style="width: 25%">Checked at</th>
            <th style="width: 25%">Result</th>
        </tr>
<?php
    foreach($results as $result):
?>
        <tr class="<?php echo $result->status; ?>">
            <td><?php echo $result->network_name; ?></td>
            <td><?php echo $result->status_timestamp; ?></td>
            <td><?php echo $result->status; ?></td>
        </tr>
<?php
    endforeach
?>
    </table>
<?php
    }

    public static function load_resources() {
        wp_register_style( 'blockedwp.css', plugin_dir_url( __FILE__ ) . 'blockedwp.css', array(), BLOCKED_WP_VERSION );
        wp_enqueue_style( 'blockedwp.css');
    }

    public static function invoke_cron() {
        error_log("ran_cron");
        if (!BlockedWP::is_registered()) {
            return;
        }

        $results = BlockedWP::get_results();

    }

    public static function show_alert() {
        if (BlockedWP::get_last_block() > BlockedWP::get_last_view()) {
?>
        <div class="notice notice-warning">
            <h3>BlockedWP</h3>
            A new block has been detected.  <a href="<?php echo admin_url('options-general.php?page=blocked-wp-admin'); ?>">More details</a>
        </div>
<?php
        }

    }
}
