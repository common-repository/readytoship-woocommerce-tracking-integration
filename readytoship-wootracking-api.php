<?php
/*
Plugin Name: WooCommerce Shipment Tracking API Extension for ReadyToShip
Plugin URI:  http://www.readytoship.com.au
Description: This plugin extends the WooCommerce API to work with the offical WooThemes Shipment Tracking module, allowing for tracking numbers to be added and retrieved via the WooCommerce API
Version:     0.6
Author:      ReadyToShip
Author URI:  http://www.readytoship.com.au
Copyright: © 2016 Directshop Pty Ltd.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/

if (!defined('ABSPATH'))
{
    exit; // Exit if accessed directly.
}

if (!class_exists('RTS_WooTracking_API'))
{
    class RTS_WooTracking_API
    {

        protected static $_instance = null;

        public static function instance()
        {
            if (is_null(self::$_instance))
            {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct()
        {
            // Define constants
            $this->define('RTS_WOOTRACKING_API_PLUGIN_FILE', __FILE__);
            $this->includes();
            $this->initHooks();
        }

        private function includes()
        {
            include_once('includes/woocommerce_install_check.php');
        }

        private function initHooks()
        {
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
            add_action('woocommerce_api_loaded', array($this, 'loadResources'));
            add_filter('woocommerce_api_classes', array($this, 'registerResources'));
        }

        public function loadResources()
        {
            if (RTS_WooTracking_Api_Dependency_Check::hasAllDependencies())
            {
                include_once('includes/api/v2/class-wc-api-tracking.php');
            }
        }

        public function registerResources($classes)
        {
            if (RTS_WooTracking_Api_Dependency_Check::hasAllDependencies())
            {
                $classes[] = "WC_API_Tracking";
            }
            return $classes;
        }

        /**
         * Activate the plugin
         */
        public function activate()
        {
            // Do nothing
        }

        /**
         * Deactivate the plugin
         */
        public function deactivate()
        {
            // Do nothing
        }

        private function define($name, $value)
        {
            if (!defined($name))
            {
                define($name, $value);
            }
        }
    }
}

if (class_exists('RTS_WooTracking_API'))
{
    // instantiate the plugin class
    $rts_wootracking_api = RTS_WooTracking_API::instance();
}
