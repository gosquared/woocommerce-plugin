<?php
/*
Plugin Name: GoSquared WooCommerce Plugin
Plugin URI: http://www.gosquared.com/
Description: The official GoSquared WooCommerce plugin to load the Tracking Code for GoSquared applications on your WooCommerce-powered site
Version: 0.1.0
License: GPL3 http://www.gnu.org/licenses/gpl.html
Author: GoSquared
Author URI: http://www.gosquared.com/about/
Contributions by: Jack Kingston, Aaran Parker
 */

/*  Copyright 2011 GoSquared (email : support@gosquared.com)

    This file is part of GoSquared WooCommerce Plugin.

    GoSquared WooCommerce Plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    GoSquared WooCommerce Plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GoSquared WooCommerce Plugin.  If not, see <http://www.gnu.org/licenses/>.
*/

ini_set('display_errors', "on");
add_option('gstc_acct');
add_option('gstc_trackAdmin');
add_option('gstc_trackPreview');
add_option('gstc_trackUser');
add_action('admin_init', 'gs_init');
add_action('admin_menu', 'gs_options');
//add_action('wp_footer', 'gs_print_gstc');
add_action('woocommerce_thankyou', 'gs_order_action');
add_action('wp_login', 'gs_login_action');
add_action('wp_print_footer_scripts', 'gs_print_gstc');
add_action('wp_print_footer_scripts', 'gs_append_and_clear_events_in_session');
add_action('wp_logout', 'gs_logout_action');

function gs_init() {
    $style_url = WP_PLUGIN_URL . '/gosquared-woocommerce/gs.css';
    $style_file = WP_PLUGIN_DIR . '/gosquared-livestats/gs.css';
    /* Register our stylesheet. */
    wp_register_style('gs_style', $style_url);
}

function gs_options() {
    $page = add_options_page('GoSquared', 'GoSquared', 'manage_options', 'gs-livestats', 'gs_options_page');
    /* Using registered $page handle to hook stylesheet loading */
    add_action('admin_print_styles-' . $page, 'gs_admin_style');
}

function gs_admin_style() {
    wp_enqueue_style('gs_style');
}

function gs_success($message) {
    echo '<div class="center"><div class="message_wrapper"><div class="gs_success">' . $message . '</div></div></div>';
}

function gs_fail($message) {
    echo '<div class="center"><div class="message_wrapper"><div class="gs_fail">' . $message . '</div></div></div>';
}

function gs_warn($message) {
    echo '<div class="center"><div class="message_wrapper"><div class="gs_warn">' . $message . '</div></div></div>';
}

function woocommerce_installed() {
    return class_exists( 'woocommerce_payment_gateway' );
}

function gs_options_page() {
    global $style_file, $style_url;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>

<div id="gs-admin-settings-page">
    <br />


    <a href="http://www.gosquared.com/" title="Go to the GoSquared homepage" target="_blank"><div id="gosquaredlogo"></div></a>

    <?php
    if (isset($_POST['gs_acct'])) {
        // Handle submission
        $acct = $_POST['gs_acct'];
        $trackAdmin = isset($_POST['gs_trackAdmin']) ? $_POST['gs_trackAdmin'] : 'Yes';
        $trackPreview = isset($_POST['gs_trackPreview']) ? $_POST['gs_trackPreview'] : "Yes";
        $trackUser = isset($_POST['gs_trackUser']) ? $_POST['gs_trackUser'] : 'Username';
        $valid_acct = preg_match('/^GSN-[0-9]{6,7}-[A-Z]{1}$/', $acct);
        if ($valid_acct) {
            update_option('gstc_acct', $acct);
            update_option('gstc_trackAdmin', $trackAdmin);
            update_option('gstc_trackPreview', $trackPreview);
            update_option('gstc_trackUser', $trackUser);
            gs_success('Settings updated successfully');
        } else {
            $msg = "";
            if (!$valid_acct)
                $msg .= '<p>Site token not of valid format. Must be like GSN-000000-X</p>';
            if(!$msg) $msg = 'An error occurred';
            gs_fail($msg);
        }
    }

    if (!woocommerce_installed()) {
        $msg = "WooCommerce Plugin is not installed";
        gs_fail($msg);
    }

    $acct = get_option('gstc_acct');
    $trackAdmin = get_option('gstc_trackAdmin');
    $trackPreview = get_option('gstc_trackPreview');
    $trackUser = get_option('gstc_trackUser');
    ?>

    <div class="gs-admin-header">

        <?php
        if (!$acct)
            $default_text = 'GSN-000000-X';
        else
            $default_text = $acct;

        if (!$trackAdmin)
            $trackAdmin = 'Yes';

        if (!$trackPreview)
            $trackPreview = 'Yes';

        if (!$trackUser)
            $trackUser = 'Username';
        ?>

    </div>

    <form name="gs-options" action="" method = "post">

        <h2>Site Token - Start tracking "<?php echo get_bloginfo('name'); ?>" with GoSquared.</h2>

        <p>Your Site Token enables GoSquared to monitor your Wordpress site's traffic. <a href="https://www.gosquared.com/join/" title="Sign up to GoSquared for free to start monitoring your site in real-time" target="_blank">Sign up for free</a> to register your site with GoSquared.</p>


        <div class="input-field">
            <span class="input-label">Your GoSquared Site Token </span>
            <input class="gs-text-input" type="text" name="gs_acct" value = "<?php echo $default_text ?>"
                   onclick="if(this.value=='<?php echo $default_text ?>')this.value=''"
                   onblur="if(this.value=='')this.value='<?php $default_text ?>'"/>&nbsp;
            <a href="http://www.gosquared.com/support/wiki/faqs#faq-site-token" target="_blank">What's this?</a>
        </div>

        <h2>Advanced Settings</h2>
        <table class="gs-settings">
            <tr>
                <td class="label">Track admin pages </td>
                <td><input type="radio" name="gs_trackAdmin" value="Yes" id="trackAdmin" <?php if ($trackAdmin == 'Yes')
                    echo 'checked="checked" '; ?>/> Yes</td>
                <td><input type="radio" name="gs_trackAdmin" value="No" id="trackAdmin" <?php if ($trackAdmin == 'No')
                    echo 'checked="checked" '; ?>/> No</td>
            </tr>
            <tr>
                <td class="label">Track post preview pages</td>
                <td><input type="radio" name="gs_trackPreview" value="Yes" id="trackPreview" <?php if ($trackPreview == 'Yes')
                    echo 'checked="checked" '; ?>/> Yes</td>
                <td><input type="radio" name="gs_trackPreview" value="No" id="trackPreview" <?php if ($trackPreview == 'No')
                    echo 'checked="checked" '; ?>/> No</td>
            </tr>
            <tr>
                <td class="label">Tag individual users with </td>
                <td><input type="radio" name="gs_trackUser" value="Off" id="trackUser" <?php if ($trackUser == 'Off')
                    echo 'checked="checked" '; ?>/> Off</td>
                <td><input type="radio" name="gs_trackUser" value="UserID" id="trackUser" <?php if ($trackUser == 'UserID')
                    echo 'checked="checked" '; ?>/> User ID</td>
                <td><input type="radio" name="gs_trackUser" value="Username" id="trackUser" <?php if ($trackUser == 'Username')
                    echo 'checked="checked" '; ?> /> Username</td>
                <td class="wide"><input type="radio" name="gs_trackUser" value="DisplayName" id="trackUser" <?php if ($trackUser == 'DisplayName')
                    echo 'checked="checked" '; ?>/> Display Name</td>
            </tr>
        </table>
        <input type="submit" value="Save Settings" class="button-primary" />
    </form>
</div>

<?php
}

function gs_print_gstc() {
    $acct = get_option('gstc_acct');
    $trackAdmin = get_option('gstc_trackAdmin');
    $trackPreview = get_option('gstc_trackPreview');
    $trackUser = get_option('gstc_trackUser');

    //Check if we are not tracking admin pages and if this is an admin page then return
    if ($trackAdmin == 'No' && is_admin())
        return;

    //Check if we are not tracking preview pages and if this is a preview page then return
    if (isset($_GET['preview']) && $_GET['preview'] == 'true' && $trackPreview == 'No')
        return;

    if ($acct) {
        $gstc_userDetail = '';

        //If tracking names, get the relevant information
        if ($trackUser != 'Off') {
            //Get current user
            require_once(ABSPATH . WPINC . "/pluggable.php");
            $current_user = wp_get_current_user();
            //Check if current user is not a guest
            if (0 != $current_user->ID) {
                switch ($trackUser) {
                    case 'UserID':
                        $gstc_userDetail = $current_user->ID;
                        break;

                    case 'Username':
                        $gstc_userDetail = $current_user->user_login;
                        break;

                    case 'DisplayName':
                        $gstc_userDetail = $current_user->display_name;
                        break;

                    default:
                        $gstc_userDetail = false;
                        break;
                }
            }
        }
        $params = array();
        $params['acct'] = $acct;
        if ($gstc_userDetail) {
            $params['VisitorName'] = $gstc_userDetail;
        }
        if (woocommerce_installed() && !is_admin()) {
            global $woocommerce;
            $params['Visitor'] = array();
//            $params['Visitor']['page_type'] = getPageType();
            $params['Visitor']['cart_total'] = $woocommerce->cart->get_cart_total();
//            $params['Visitor']['cart_items'] = array();
            $cart_items = $woocommerce->cart->get_cart();
            foreach ($cart_items as $item) {
                $item_title = $item['data']->get_title();
                $item_quantity = $item['quantity'];
//                $item_price = $item['data']->get_price();
//                $item_total_value = $item_price * $item_quantity;
//                $item_fields = array("title" => $item_title,
//                                     "quantity" => $item_quantity,
//                                     "price" => $item_price,
//                                     "total_value" => $item_total_value);
//                array_push($params['Visitor']['cart_items'], $item_fields);
                $params['Visitor'][$item_title] = "x$item_quantity";
            }
            $total_discount = $woocommerce->cart->get_total_discount();
            if ($total_discount == false) $total_discount = "0";
            $params['Visitor']['total_discount'] = $total_discount;
        }
        if (array_key_exists('add-to-cart', $_GET) && $woocommerce->verify_nonce('add_to_cart', '_GET')) {
            wp_enqueue_script('gs_shop');
            $item = array("id" => $_GET['add-to-cart']);
            if (is_numeric($_GET['add-to-cart'])) {
                $item[quantity] = (isset($_POST['quantity'])) ? (int) $_POST['quantity'] : 1;
            } else if ($_GET['add-to-cart'] == 'group') {

            }
            $item_data = (array) apply_filters('woocommerce_add_cart_item_data', array(), $item['id']);
        }
        ?>
        <script>
            var GoSquared = <?php echo json_encode($params); ?>;
            (function(w){
                function gs(){
                    w._gstc_lt=+(new Date); var d=document;
                    var g = d.createElement("script"); g.type = "text/javascript"; g.async = true; g.src = "//d1l6p2sc9645hc.cloudfront.net/tracker.js";
                    var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(g, s);
                }
                w.addEventListener?w.addEventListener("load",gs,false):w.attachEvent("onload",gs);

                function appendEvent(cls,ev,f){
                    var els = document.getElementsByClassName(cls);
                    for (var e in els) {
                        if (!isNaN(+e)) {
                            var el = els[e];
                            el[ev] = f;
                        }
                    }
                }
                function addToCard(e){
                    var id = e.currentTarget.getAttribute("data-product_id");
                    GoSquared.DefaultTracker.TrackEvent('Item added to cart', {'item_id': id});
                }
                var addToCartListeners = appendEvent('add_to_cart_button', 'onclick', addToCard);
                w.addEventListener?w.addEventListener("load",addToCartListeners,false):w.attachEvent("onload",addToCartListeners);

            })(window);
        </script>
        <?php
    }
}

function gs_order_action($order_id) {
    $order = &new woocommerce_order($order_id);
    $details = array();
    $details['total'] = woocommerce_price($order->order_total);
    $details['tax'] = woocommerce_price($order->order_tax);
    $details['shipping'] = woocommerce_price($order->order_shipping);
    foreach ($order->items as $item) {
        $details[$item['name']] = $item['quantity']." @ ".woocommerce_price($item['cost']);
    }
    $details_json = json_encode($details);
    appendEvents(array("Order Received" => $details_json));
    add_action('wp_print_footer_scripts', 'events_footer');
}

function gs_login_action() {
    gs_add_event_to_session("Logged in", "");
}

function gs_logout_action() {
    gs_add_event_to_session("Logged out", "");
}

function gs_add_to_cart_action() {

}

function get_gsevents() {
    $events = array();
    if (isset($_SESSION['gsevents']) && is_array($_SESSION['gsevents'])) {
        $events = $_SESSION['gsevents'];
    }
    return $events;
}
function gs_add_event_to_session($name, $val) {
    $events = get_gsevents();
    $events[$name] = $val;
    $_SESSION['gsevents'] = $events;
}

function gs_append_and_clear_events_in_session() {
    $events = get_gsevents();
    appendEvents($events);
    $_SESSION['gsevents'] = array();
}

function appendEvents($eventArray) {
    ?>
    <script>
        window.gsevents = window.gsevents || [];
<?php foreach ($eventArray as $name => $val) { ?>
        window.gsevents.push(function () {
            GoSquared.DefaultTracker.TrackEvent('<?php echo $name; ?>', '<?php echo $val; ?>');
        });
<?php } ?>
    </script>
    <?php
    events_footer();
}

function events_footer() {
    ?>
    <script>
        (function() {
            function waitForTracker(f) {
                if (!GoSquared.DefaultTracker) {
                    setTimeout(function() {waitForTracker(f);},500);
                } else {
                    f();
                }
            }
            waitForTracker(function() {
                var gsevents = window.gsevents || [];
                for (var ev in gsevents) {
                    gsevents[ev]();
                }
            });
        })();
    </script>
    <?php
}

function getPageType() {
    if (woocommerce_installed()) {
        if (is_shop()) return "C";
        if (is_product_category()) return "C";
        if (is_product_tag()) return "C";
        if (is_product()) return "P";
        if (is_cart()) return "S";
        if (is_checkout()) return "S";
    }
    return "H";
}
?>
