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

    $ticket_info = get_post_registration_options($post);
    $price = 0;
    $total_quantity = 0;
    if ($ticket_info) {
        if ($ticket_info['prices']) {
            foreach ( $ticket_info['prices'] as $name => $value ) {
                if ($name !== "complimentary" || $value['price'] > 0) {
                    $price = $value['price'];
                    break;
                }
            }
        }
        if ($ticket_info['total']) {
            $total_quantity = $ticket_info['total'];
        }
    }

    //error_log($action . " " . $event_id . " data: " . print_r($data, true));
    //var_dump($ticket_info);

    $event = null;
    $product = null;
    if ('edit' === $action) {
        $event = mc_get_event($event_id);
        if (!is_object( $event)) {
            $event = mc_get_nearest_event( $event_id, true );
        }
        if (!is_null($event)) {
            $product = wc_get_product($event->event_product);
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
        $product->set_regular_price($price);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($total_quantity);
        if ($attachment_id) {
            $product->set_image_id($attachment_id);
        }
        $product->save();
    
        //if ($action === "add") {
        mc_update_data($event_id, 'event_product', $product->get_id());
        //}
    }
}

function get_post_registration_options($post) {
    if ( isset( $post['mt_label'] ) ) {
		//$reg_data        = get_post_meta( $post_id, '_mt_registration_options', true );
		$event_begin     = ( isset( $post['event_begin'] ) ) ? $post['event_begin'] : '';
		$event_begin     = ( is_array( $event_begin ) ) ? $event_begin[0] : $event_begin;
		$labels          = ( isset( $post['mt_label'] ) ) ? $post['mt_label'] : array();
		$times           = ( isset( $post['mt_label_time'] ) ) ? $post['mt_label_time'] : array();
		$prices          = ( isset( $post['mt_price'] ) ) ? $post['mt_price'] : array();
		$sold            = ( isset( $post['mt_sold'] ) ) ? $post['mt_sold'] : array();
		$close           = ( isset( $post['mt_close'] ) ) ? $post['mt_close'] : mt_close_times( $labels, $times );
		$hide            = ( isset( $post['mt_hide_registration_form'] ) ) ? 'true' : 'false';
		$availability    = ( isset( $post['mt_tickets'] ) ) ? $post['mt_tickets'] : 'inherit';
		$total_tickets   = ( isset( $post['mt_tickets_total'] ) ) ? $post['mt_tickets_total'] : 'inherit';
		$pricing_array   = mt_setup_pricing( $labels, $prices, $availability, $close, $sold, $times );
		$reg_expires     = ( isset( $post['reg_expires'] ) ) ? (int) $post['reg_expires'] : 0;
		$multiple        = ( isset( $post['mt_multiple'] ) ) ? 'true' : 'false';
		$mt_sales_type   = ( isset( $post['mt_sales_type'] ) ) ? $post['mt_sales_type'] : 'tickets';
		$counting_method = ( isset( $post['mt_counting_method'] ) ) ? $post['mt_counting_method'] : 'discrete';
		$counting_method = ( isset( $post['mt_general'] ) && 'general' === $post['mt_general'] ) ? 'general' : $counting_method;
		$sell            = ( isset( $post['mt-trigger'] ) ) ? 'true' : 'false';
		$notes           = ( isset( $post['mt_event_notes'] ) ) ? $post['mt_event_notes'] : '';
		$clear           = ( isset( $post['mt-delete-data'] ) ) ? true : false;
		$registration_options = array(
			'reg_expires'     => $reg_expires,
			'sales_type'      => $mt_sales_type,
			'counting_method' => $counting_method,
			'prices'          => $pricing_array,
			'total'           => $total_tickets,
			'multiple'        => $multiple,
		);
        return $registration_options;
	}
    return array();
}

add_shortcode( 'naiop_upcoming', 'naiop_upcoming_events' );
/**
 * Upcoming Events My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string Calendar.
 */
function naiop_upcoming_events( $atts ) {
	$args = shortcode_atts(
		array(
			'before'         => 'default',
			'after'          => '90',
			'type'           => 'default',
			'category'       => 'default',
			'template'       => 'default',
			'fallback'       => '',
			'order'          => 'asc',
			'skip'           => '0',
			'show_recurring' => 'yes',
			'author'         => 'default',
			'host'           => 'default',
			'ltype'          => '',
			'lvalue'         => '',
			'from'           => false,
			'to'             => false,
			'site'           => false,
			'language'       => '',
		),
		$atts,
		'my_calendar_upcoming'
	);

	global $user_ID;
	if ( 'current' === $args['author'] ) {
		/**
		 * Filter the author parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_author
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'upcoming' to indicate the `my_calendar_upcoming` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'upcoming' );
	}
	if ( 'current' === $args['host'] ) {
		/**
		 * Filter the host parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_host
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'upcoming' to indicate the `my_calendar_upcoming` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
		$args['host'] = apply_filters( 'mc_display_host', $user_ID, 'upcoming' );
	}

	return my_calendar_upcoming_events( $args );
}

add_filter('mc_upcoming_events_template', 'naiop_upcoming_template');
function naiop_upcoming_template($object) {
    $out = '<div class="wp-block-obb-icon-block organic-block obb-icon-box obb-orientation-vertical obb-vertical-align-center">';
        $out .= '<h3>{daterange}</h3>';
        $out .= '<p><strong>{timerange}</strong></p>';
        $out .= '{linking_title}';
    $out .= '</div>';
    return $out;
}

add_filter('naiop_upcoming_event_template', 'naiop_upcoming_event_template');
function naiop_upcoming_event_template($template) {
    return 'event/naiop_upcoming';
}

add_filter('mc_upcoming_events_header', 'naiop_upcoming_header');
function naiop_upcoming_header($header) {
    return '<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-2 wp-block-columns-is-layout-flex">';
}

add_filter('mc_upcoming_events_footer', 'naiop_upcoming_footer');
function naiop_upcoming_footer($footer) {
    return '</div>';
}

add_filter('naiop_custom_sidebar_panels', 'naiop_custom_sidebar_panels');
function naiop_custom_sidebar_panels($footer) {
    return true;
}

