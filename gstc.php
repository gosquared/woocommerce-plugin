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

/*  Copyright 2012 GoSquared (email : support@gosquared.com)

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

include 'gsadmin.php';

ini_set('display_errors', "on");

add_action('init', 'gs_init');
add_action('admin_footer', 'gs_print_gstc');
add_action('wp_footer', 'gs_print_gstc');
add_action('login_footer', 'gs_print_gstc');

function gs_init() {
    $style_url = WP_PLUGIN_URL . '/gosquared-woocommerce/gs.css';
    $style_file = WP_PLUGIN_DIR . '/gosquared-livestats/gs.css';
    /* Register our stylesheet. */
    wp_register_style('gs_style', $style_url);
}

function woocommerce_installed() {
    return class_exists( 'woocommerce_payment_gateway' );
}

function gs_print_gstc() {
    $acct = get_option('gstc_acct');
    $trackAdmin = get_option('gstc_trackAdmin');
    $trackPreview = get_option('gstc_trackPreview');
    $trackUser = get_option('gstc_trackUser');
    //Check if we are not tracking admin pages and if this is an admin page then return
    if ($trackAdmin == 'No' && is_admin())
        return;
echo "line 58\n";
    //Check if we are not tracking preview pages and if this is a preview page then return
    if (isset($_GET['preview']) && $_GET['preview'] == 'true' && $trackPreview == 'No')
        return;
echo "line 62\n";
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
		$params['Visitor'] = fetchCartContents();
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

                function addToCart(e){
                    var id = e.currentTarget.getAttribute("data-product_id");
                    GoSquared.DefaultTracker.TrackEvent('Item added to cart', {'item_id': id});
                }
                var addToCartListeners = appendEvent('add_to_cart_button', 'onclick', addToCart);
                w.addEventListener?w.addEventListener("load",addToCartListeners,false):w.attachEvent("onload",addToCartListeners);

            })(window);
        </script>
        <?php
    }
}

function fetchCartContents () {
	$visitor = array();
	if (woocommerce_installed() && !is_admin()) {
		global $woocommerce;
		$visitor['cart_total'] = $woocommerce->cart->get_cart_total();
		$visitor['cart_items'] = array();
		$cart_items = $woocommerce->cart->get_cart();
		foreach ($cart_items as $item) {
			$item_title = $item['data']->get_title();
			$item_quantity = $item['quantity'];
			$item_price = $item['data']->get_price();
			$item_total_value = $item_price * $item_quantity;
			$item_fields = array("title" => $item_title,
								 "quantity" => $item_quantity,
								 "price" => $item_price,
								 "total_value" => $item_total_value);
			// serialize cart array to get around tracker only storing single-layer values
		    $item_fields_json = json_encode($item_fields);
			array_push($visitor['cart_items'], $item_fields_json);
			$visitor[$item_title] = "x$item_quantity";
		}
		$total_discount = $woocommerce->cart->get_total_discount();
		if ($total_discount == false) $total_discount = "0";
		$visitor['total_discount'] = $total_discount;
	}
	return $visitor;
}

include 'GS_GetEvents.php';
?>
