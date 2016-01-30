<?php
/*
  Plugin Name: Usergroup Content
  Plugin URI: https://github.com/Asgaros/usergroup-content
  Description: A lightweight and simple usergroup content plugin for WordPress.
  Version: Dev
  Author: xxx yyy
  Author URI: http://xyz
  License: GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: usergroup-content
  Domain Path: /languages

  Usergroup Content is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 2 of the License, or
  any later version.

  Usergroup Content is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Usergroup Content. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if (!defined('ABSPATH')) exit;

function usergroup_content_textdomain() {
    load_plugin_textdomain('usergroup-content', FALSE, basename(dirname(__FILE__)).'/languages/');
}
add_action('plugins_loaded', 'usergroup_content_textdomain');

?>
