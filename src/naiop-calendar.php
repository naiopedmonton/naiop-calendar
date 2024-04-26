<?php

defined( 'ABSPATH' ) || exit;

/**
 * Customizations for NAIOP
 * 
 * Author:              Scott Dohei
 */


add_filter('naiop_calendar_name', 'naiop_calendar_name');
function naiop_calendar_name($object) {
    return "NAIOP Calendar";
}

add_action('mc_save_event', 'naiop_save_event', 10, 4);
/**
 * Create a WooCommerce product for the Event
 *
 * @param string $action Type of action performed.
 * @param array  $data Data passed to filter.
 * @param int    $event_id Event ID being affected.
 * @param int    $result Result of calendar save query.
 */
function naiop_save_event($action, $data, $event_id, $result) {
    $post_post = isset( $_POST['post'] ) ? $_POST['post'] : array();
	$post_data = ( wp_doing_ajax() && ! empty( $_POST ) ) ? $post_post : $_POST;
	if ( $post_data !== $_POST && is_string( $post_data ) ) {
		parse_str( $post_data, $post );
	} else {
		$post = $post_data;
	}
	$post = map_deep( $post_data, 'wp_kses_post' );
    
    $attachment_id = false;
    if ( isset( $post['event_image_id'] ) ) {
        $attachment_id = (int) $post['event_image_id'];
    } elseif ( isset( $data['event_image_id'] ) ) {
        $attachment_id = (int) $data['event_image_id'];
    }

    //error_log($action . " data: " . print_r($data, true));

    $event = null;
    $product = null;
    if ($action === "edit" && $event_id) {
        $event = mc_get_event($event_id);
        if (is_object($event) && $event->event_product) {
            error_log("event product id = " . $event->event_product);
            $product = wc_get_product($event->event_product);
        } else {
            $product = new WC_Product_Simple();
        }
    } else {
        $product = new WC_Product_Simple();
    }

    if ($product) {
        $product->set_name($data["event_title"]);
        $product->set_description($data["event_desc"]);
        $product->set_short_description($data["event_short"]);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');
        // TODO price
        $product->set_regular_price('59');
        if ($attachment_id) {
            $product->set_image_id($attachment_id);
        }
        $product->save();
    
        if ($action === "add" && $event_id && $product->get_id()) {
            mc_update_data($event_id, 'event_product', $product->get_id());
        }
    }
}
