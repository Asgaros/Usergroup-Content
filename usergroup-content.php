<?php
/*
  Plugin Name: Usergroup Content
  Plugin URI: https://github.com/Asgaros/usergroup-content
  Description: A lightweight and simple usergroup content plugin for WordPress.
  Version: Dev
  Author: Thomas Belser, Manuel Grob
  Author URI: http://xyz
  License: GPL3
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: usergroup-content
  Domain Path: /languages

  Usergroup Content is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  any later version.

  Usergroup Content is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Usergroup Content. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
*/

if (!defined('ABSPATH')) exit;

function uc_textdomain() {
    load_plugin_textdomain('usergroup-content', FALSE, basename(dirname(__FILE__)).'/languages/');
}
add_action('plugins_loaded', 'uc_textdomain');

require('includes/usergroups.php');
require('includes/medialibrary.php');

$directory = plugin_dir_url(__FILE__);
$uc_usergroups = new uc_usergroups($directory);
$uc_medialibrary = new uc_medialibrary();

function uc_rewrite_rule() {
   add_rewrite_rule('wp-content/uploads/(.*)$', 'wp-content/plugins/usergroup-content/external/private-image.php?img=$1');
}

add_action( 'init', 'uc_rewrite_rule' );

?>
