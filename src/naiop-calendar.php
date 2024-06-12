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

add_filter('mc_location_events_link', 'naiop_location_link');
function naiop_location_link($object) {
    return "";
}

/* add report column */
add_filter( 'naiop_event_headers', 'naiop_event_headers', 10, 1 );
function naiop_event_headers($headers) {
	return $headers . '<th scope="col">' . "Report" . '</th>';
}

/* add link to generate report */
add_filter( 'naiop_event_report_column', 'naiop_event_report_column', 10, 1 );
function naiop_event_report_column($event) {
	$report_url = admin_url( "admin.php?naiop_event_report=$event->event_id" );
	return "<a href='$report_url'><div class='dashicons dashicons-download'></div>Generate!</a>";
}

function get_orders_ids_by_product_id( $product_id, $order_status = array( 'draft', 'wc-completed', 'wc-processing' ) ) {
    global $wpdb;
    return $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type IN ('shop_order', 'shop_order_placehold')
        AND posts.post_status IN ( '" . implode( "','", $order_status ) . "' )
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$product_id'
    ");
}

function handle_report_request() {
	if (isset($_GET['naiop_event_report'])) {
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"report.csv\";");
		header("Content-Transfer-Encoding: binary");

		$event_id = $_GET['naiop_event_report'];
		$post_id = mc_get_event_post( $event_id );
		$registration = get_post_meta( $post_id, '_mt_registration_options', true );
		$product_ids = array();
		foreach( $registration['prices'] as $price_type => $price_config ) {
			if ( isset($price_config['product_id']) ) {
				array_push($product_ids, $price_config['product_id']);
			}
		}

		$val = "Order ID,First Name,Last Name,Email,Dietary Restrictions\n";
		$registered_count = 0;
		$processed_orders = array();
		foreach( $product_ids as $pid ) {
			$orders = get_orders_ids_by_product_id($pid);
			if ( count($orders) > 0 ) {
				foreach( $orders as $order_id ) {
					if ( in_array($order_id, $processed_orders) ) {
						continue;
					}
					$order = wc_get_order( $order_id );

					$fname_data = array();
					$fname_meta = $order->get_meta( 'naiop_event_fname', false );
					foreach ($fname_meta as $metadata) {
						if ( is_array($metadata->get_data()['value']) ) {
							$fname_data = $metadata->get_data()['value'];
						}
					}
					$lname_data = array();
					$lname_meta = $order->get_meta( 'naiop_event_lname', false );
					foreach ($lname_meta as $metadata) {
						if ( is_array($metadata->get_data()['value']) ) {
							$lname_data = $metadata->get_data()['value'];
						}
					}
					$email_data = array();
					$email_meta = $order->get_meta( 'naiop_event_email', false );
					foreach ($email_meta as $metadata) {
						if ( is_array($metadata->get_data()['value']) ) {
							$email_data = $metadata->get_data()['value'];
						}
					}
					$diet_data = array();
					$diet_meta = $order->get_meta( 'naiop_event_diet', false );
					foreach ($diet_meta as $metadata) {
						if ( is_array($metadata->get_data()['value']) ) {
							$diet_data = $metadata->get_data()['value'];
						}
					}

					$count = count($fname_data);
					$registered_count += $count;
					for ($x = 0; $x < $count; $x++) {
						$val .= $order_id . ',' . $fname_data[$x] . ',' . $lname_data[$x] . ',' . $email_data[$x] . ',' . $diet_data[$x] . "\n";
					}
					
					array_push($processed_orders, $order_id);
				}
			}
		}
		if ($registered_count === 0) {
			$val = "NO TICKETS SOLD\n";
		}
		echo $val;
	}
}

add_action( 'init', 'naiop_register_actions' );
function naiop_register_actions() {
	if (isset($_GET['naiop_event_report'])) {
		handle_report_request();
		exit;
	}
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
			'before'         => '0',
			'after'          => '3',
			'type'           => 'event',
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

