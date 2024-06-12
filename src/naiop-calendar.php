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

function handle_report_request() {
	if (isset($_GET['naiop_event_report'])) {
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"report.csv\";");
		header("Content-Transfer-Encoding: binary");

		echo "hello " . $_GET['naiop_event_report'];
		exit;
	}
}
handle_report_request();

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

