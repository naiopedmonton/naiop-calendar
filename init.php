<?php
/**
 * NAIOP Calendar
 *
 * @wordpress-plugin
 * Plugin Name: NAIOP Calendar
 * Version:     1.7.0
 * Plugin URI:	https://github.com/naiopedmonton/naiop-calendar
 * GitHub Plugin URI: https://github.com/naiopedmonton/naiop-calendar
 * Description: Events for NAIOP
 * Author:      Scott Dohei
 * Text Domain: naiop-calendar
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Domain Path: lang
 */

/*
	Copyright 2009-2024  Joe Dolson (email : joe@joedolson.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/src/my-calendar.php';
require __DIR__ . '/src/naiop-calendar.php';

register_activation_hook( __FILE__, 'mc_plugin_activated' );
register_deactivation_hook( __FILE__, 'mc_plugin_deactivated' );
