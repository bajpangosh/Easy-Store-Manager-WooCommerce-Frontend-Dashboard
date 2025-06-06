<?php
/**
 * Plugin Name:       Easy Store Manager – WooCommerce Frontend Dashboard
 * Plugin URI:        https://example.com/easy-store-manager-plugin
 * Description:       Enables Store Managers to handle WooCommerce product and order management via a frontend dashboard.
 * Version:           1.0.1
 * Author:            AI Assistant
 * Author URI:        https://example.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easy-store-manager
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: (latest WooCommerce version)
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ESM_VERSION', '1.0.1' );
define( 'ESM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ESM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ESM_INCLUDES_DIR', ESM_PLUGIN_DIR . 'includes/' );

// -----------------------------------------------------------------------------
// Includes - Load necessary files
// -----------------------------------------------------------------------------
require_once ESM_INCLUDES_DIR . 'plugin-setup.php';         // Activation/deactivation, page creation
require_once ESM_INCLUDES_DIR . 'capabilities.php';        // Role definition & admin redirect
require_once ESM_INCLUDES_DIR . 'api-helpers.php';         // Shared helper functions for API (params, etc.)
require_once ESM_INCLUDES_DIR . 'api-product.php';         // Product API handlers
require_once ESM_INCLUDES_DIR . 'api-order.php';           // Order API handlers
require_once ESM_INCLUDES_DIR . 'api-report.php';          // Report API handlers
require_once ESM_INCLUDES_DIR . 'api-routes.php';          // Main API route registration (registers routes that use handlers above)
require_once ESM_INCLUDES_DIR . 'shortcode-dashboard.php'; // Shortcode definition
require_once ESM_INCLUDES_DIR . 'assets.php';              // Asset enqueueing

// -----------------------------------------------------------------------------
// Activation / Deactivation Hooks (defined in plugin-setup.php)
// -----------------------------------------------------------------------------
register_activation_hook( __FILE__, 'esm_activate_plugin' );
register_deactivation_hook( __FILE__, 'esm_deactivate_plugin' );

// -----------------------------------------------------------------------------
// Initialize Plugin Features (hooked into WordPress actions)
// -----------------------------------------------------------------------------
// Hooks for functions defined in included files:

// From capabilities.php
add_action( 'init', 'esm_register_store_manager_frontend_role' );
add_action( 'admin_init', 'esm_redirect_store_manager_frontend' );

// From plugin-setup.php (page creation, if not done only on activation)
add_action( 'init', 'esm_create_store_management_page_if_not_exists' );

// From api-routes.php
add_action( 'rest_api_init', 'esm_register_all_api_routes' );

// From shortcode-dashboard.php
// The add_shortcode call is typically made directly in the file where the function is defined.

// From assets.php
add_action( 'wp_enqueue_scripts', 'esm_enqueue_dashboard_assets' );

// Add a check for WooCommerce being active
function esm_check_woocommerce_active() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'esm_woocommerce_missing_notice' );
        return false;
    }
    return true;
}
add_action( 'plugins_loaded', 'esm_check_woocommerce_active' );

function esm_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e( 'Easy Store Manager requires WooCommerce to be installed and active. Please install or activate WooCommerce.', 'easy-store-manager' ); ?></p>
    </div>
    <?php
}

// Function to load text domain
function esm_load_textdomain() {
    load_plugin_textdomain( 'easy-store-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'esm_load_textdomain' );

?>
