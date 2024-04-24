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
    $product = new WC_Product_Simple();
    $product->set_name($data["event_title"]);
    $product->set_description($data["event_desc"]);
    
    $product->set_regular_price('50');
    $product->set_sold_individually(true);
    $product->set_catalog_visibility('hidden');
    $product->save();

    mc_update_data($event_id, 'event_product', $product->get_id());
}
