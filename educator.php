<?php
/**
 * Plugin Name: Educator 2
 * Plugin URI: http://educatorplugin.com/
 * Description: Sell and teach online courses
 * Author: educatorteam
 * Author URI: http://educatorplugin.com/
 * Version: 2.0.3
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: educator
*/

/*
Copyright (C) 2016 http://educatorplugin.com/ - contact@educatorplugin.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EDR_VERSION', '2.0.3' );
define( 'EDR_DB_VERSION', '2.0' );
define( 'EDR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EDR_PT_COURSE', 'edr_course' );
define( 'EDR_PT_LESSON', 'edr_lesson' );
define( 'EDR_PT_MEMBERSHIP', 'edr_membership' );
define( 'EDR_TX_CATEGORY', 'edr_course_category' );

// Setup autoloader.
require EDR_PLUGIN_DIR . 'includes/Edr/Autoloader.php';
new Edr_Autoloader();

// Setup Educator.
Edr_Main::get_instance( __FILE__ );
