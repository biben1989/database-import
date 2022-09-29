<?php
/*
Plugin Name: Plugin Import Database
Plugin URI: https://nixwood.com/
Author: Nixwood
Version: 1.1
Author URI: https://nixwood.com/
*/

define( 'DATABESE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


register_activation_hook( __FILE__, array( 'DataBaseImport', 'activation' ));
register_deactivation_hook( __FILE__, array( 'DataBaseImport', 'deactivation' ));
require_once( DATABESE__PLUGIN_DIR . 'class.database-import.php' );

new DataBaseImport();