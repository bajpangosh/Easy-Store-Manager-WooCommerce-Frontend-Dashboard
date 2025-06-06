<?php
/**
 * Plugin Setup: Activation, Deactivation, Page Creation.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin activation hook.
 *
 * Creates user roles, pages, or other setup tasks.
 */
function esm_activate_plugin() {
    // Ensure user role is registered (it's also on init, but good to ensure on activation)
    if (function_exists('esm_register_store_manager_frontend_role')) { // Check if function exists (from capabilities.php)
        esm_register_store_manager_frontend_role();
    }

    // Create the store management page
    esm_create_store_management_page_if_not_exists(true); // Pass true to force option update on activation

    flush_rewrite_rules(); // Important after adding roles or custom post types/permalinks
}

/**
 * Plugin deactivation hook.
 *
 * Cleans up options, roles, or other data if necessary.
 */
function esm_deactivate_plugin() {
    // Example: Remove user roles or settings if the plugin is set to clean up on deactivation.
    // Be cautious with data removal. Often, it's better to leave data unless a "complete uninstall" option is provided.
    // remove_role('store_manager_frontend'); // Example, ensure this is desired behavior

    // Optionally, remove the page created by the plugin, or an option to do so.
    // $page = get_page_by_path('storemanagement');
    // if ($page) {
    //     wp_delete_post($page->ID, true); // True to bypass trash
    // }
    // delete_option('esm_page_created');

    flush_rewrite_rules();
}

/**
 * Create "Store Management" Page if it doesn't exist or option not set.
 * Hooked to 'init' from the main plugin file.
 *
 * @param bool $force_option_update Whether to force updating the 'esm_page_created' option.
 */
function esm_create_store_management_page_if_not_exists( $force_option_update = false ) {
    // Check if page already created (via option) to prevent running on every init
    if ( !get_option('esm_page_created') || $force_option_update ) {
        $page = get_page_by_path( 'storemanagement' );
        if ( ! $page ) { // Check if page by slug truly doesn't exist
            $page_args = array(
                'post_title'    => __( 'Store Management', 'easy-store-manager' ),
                'post_name'     => 'storemanagement', // slug
                'post_content'  => '[store_management_dashboard]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
            );
            $page_id = wp_insert_post( $page_args );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'esm_page_created', true );
            }
        } elseif ($force_option_update) {
            // If page exists but option was not set, set it now.
             update_option( 'esm_page_created', true );
        }
    }
}
?>
