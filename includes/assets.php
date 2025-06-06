<?php
/**
 * Asset Enqueueing for the Frontend Dashboard.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue scripts and styles for the Store Management Dashboard.
 *
 * This function is hooked to 'wp_enqueue_scripts'.
 * It ensures that Vue.js, Axios, and the main Vue application script are loaded
 * when the dashboard shortcode is present on the page.
 * It also localizes the main app script with necessary PHP data.
 */
function esm_enqueue_dashboard_assets() {
    global $post;

    // Check if the shortcode is present on the current page/post,
    // or if we are on a specific admin page that might host a similar app (future).
    // For now, primarily targeting the shortcode.
    $is_dashboard_page = ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'store_management_dashboard' ) );

    // Allow other ways to identify the dashboard page, e.g. a specific page slug,
    // if the shortcode check isn't sufficient or if used in theme templates.
    if ( !$is_dashboard_page ) {
        // Example: check if it's the 'storemanagement' page by slug
        if (is_page('storemanagement')) {
            $is_dashboard_page = true;
        }
    }

    if ( $is_dashboard_page && current_user_can('store_manager_frontend') ) { // Also check user capability

        // Enqueue Vue.js (from CDN as an example, or local copy)
        // It's good practice to allow overriding via theme/child theme if needed.
        if (!wp_script_is('vue-runtime', 'registered')) { // Check if Vue is already registered by another plugin/theme
            wp_register_script( 'vue-runtime', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.2.30', true );
        }
        wp_enqueue_script('vue-runtime');

        // Enqueue Axios (from CDN or local copy)
        if (!wp_script_is('axios', 'registered')) {
            wp_register_script( 'axios', 'https://unpkg.com/axios/dist/axios.min.js', array(), '0.21.4', true );
        }
        wp_enqueue_script('axios');

        // Enqueue your compiled Vue app's JavaScript
        // Assumes a build process outputs 'store-management-app.js' to 'assets/js/' in the plugin.
        $app_script_path = 'assets/js/store-management-app.js';
        wp_enqueue_script(
            'esm-vue-app',
            ESM_PLUGIN_URL . $app_script_path,
            array('vue-runtime', 'axios', 'wp-mediaelement'), // Dependencies
            filemtime( ESM_PLUGIN_DIR . $app_script_path ), // Versioning based on file modification time
            true // Load in footer
        );

        // Localize script with data like REST API URL, nonce, etc.
        wp_localize_script( 'esm-vue-app', 'wpApiSettings', array(
            'root'          => esc_url_raw( rest_url() ), // WordPress REST API root
            'nonce'         => wp_create_nonce( 'wp_rest' ), // Nonce for WP REST API authentication
            'esm_namespace' => 'esm/v1', // Your custom namespace for the plugin's API
            'text_domain'   => 'easy-store-manager', // For any JS translations needed via wp.i18n
            'wc_ajax_url'   => WC()->ajax_url(), // WooCommerce AJAX URL if needed
            // Add any other data your Vue app needs from PHP
        ) );

        // Enqueue your app's CSS (if you have a separate CSS file from your build process)
        // $app_style_path = 'assets/css/store-management-app.css';
        // if (file_exists(ESM_PLUGIN_DIR . $app_style_path)) {
        //     wp_enqueue_style(
        //         'esm-vue-app-styles',
        //         ESM_PLUGIN_URL . $app_style_path,
        //         array(),
        //         filemtime( ESM_PLUGIN_DIR . $app_style_path )
        //     );
        // }

        // Ensure WordPress media scripts are enqueued for the media library functionality
        // This is crucial for the Product Form's image upload feature.
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }
    }
}
// The add_action('wp_enqueue_scripts', 'esm_enqueue_dashboard_assets') is in the main plugin file.

?>
