<?php
/**
 * Template: Single Event, Upcoming events view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">
	<?php mc_template_image( $data, 'calendar' ); ?>
	<?php mc_template( $data->tags, $data->template, 'list' ); ?>
</div>
