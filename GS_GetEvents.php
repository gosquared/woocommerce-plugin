<?php

include_once('GS_EventCollector.php');

$action_methods = array();

function add_gs_action_method($method_name) {
	global $action_methods;
	array_push($action_methods, $method_name);
}

function gs_add_event_to_session($name, $val) {
	//var_dump($name, $val);
	GS_EventCollector::getInstance()->addEvent($name, $val);
}

add_action('admin_footer', 'gs_append_event_collector');
add_action('wp_footer', 'gs_append_event_collector');
add_action('login_footer', 'gs_append_event_collector');
function gs_append_event_collector() {
    $eventCollector = GS_EventCollector::getInstance();
    echo $eventCollector->getJS();
}

add_action('init', 'get_events');
function get_events() {

	global $action_methods;
    foreach ($action_methods as $f) {
		call_user_func($f);
	}

    // for testing:
    $event_collector = GS_EventCollector::getInstance();
    var_dump($event_collector->eventList);

}

add_gs_action_method('gs_get_update_cart');
function gs_get_update_cart() {
    global $woocommerce;

    if ( isset($_GET['remove_item']) && $_GET['remove_item'] && $woocommerce->verify_nonce('cart', '_GET')) {
        gs_add_event_to_session("Item removed", "");
    } else if (isset($_POST['update_cart']) && $_POST['update_cart']  && $woocommerce->verify_nonce('cart')) {
        gs_add_event_to_session("Cart updated", "");
    }
}

add_gs_action_method('gs_get_add_to_cart_action');
function gs_get_add_to_cart_action() {
//TODO catch add-to-cart by POST
}

add_action('wp_login', 'gs_login_action');
function gs_login_action() {
    gs_add_event_to_session("Logged in", "");
}

add_action('wp_logout', 'gs_logout_action');
function gs_logout_action() {
    gs_add_event_to_session("Logged out", "");
}

add_action('woocommerce_thankyou', 'gs_order_action');
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
    gs_add_event_to_session("Order Received", $details_json);
}
