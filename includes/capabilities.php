<?php
/**
 * User Role Definitions and Admin Area Restrictions.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Store Manager Frontend Role.
 * Hooked to 'init'.
 */
function esm_register_store_manager_frontend_role() {
    add_role(
        'store_manager_frontend',
        __( 'Store Manager Frontend', 'easy-store-manager' ),
        array(
            'read'                   => true, // Basic WordPress access
            'read_product'           => true,
            'edit_product'           => true,
            'delete_product'         => true,
            'edit_products'          => true,
            'delete_products'        => true,
            'assign_product_terms'   => true,
            'upload_files'           => true,
            'edit_shop_orders'       => true,
            'read_shop_orders'       => true,
            'view_admin_dashboard'   => true, // Initially granted, restricted by redirect_store_manager_frontend
        )
    );
}

/**
 * Redirect Store Manager Frontend from /wp-admin/ to /storemanagement/
 * Hooked to 'admin_init'.
 */
function esm_redirect_store_manager_frontend() {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) && current_user_can( 'store_manager_frontend' ) ) {

        // Allow access to own profile page
        if ( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE === true ) { // IS_PROFILE_PAGE is defined by WordPress on profile.php
             return;
        }

        // Check if trying to access a specifically allowed WooCommerce page (example)
        // $screen = get_current_screen();
        // if ( $screen && in_array( $screen->id, array( 'woocommerce_page_wc-reports', 'edit-shop_order' ) ) ) {
        //     return; // Allow access if needed in future, not part of current scope
        // }

        // For any other admin page, redirect to the frontend dashboard
        wp_redirect( home_url( '/storemanagement/' ) ); // Ensure 'storemanagement' is the correct slug
        exit;
    }
}

/**
 * Conditionally remove 'view_admin_dashboard' capability more granularly if needed.
 * This is a more advanced and optional filter if the redirection is not sufficient.
 * Not actively used by default, but kept for potential future refinement.
 */
function esm_conditionally_remove_admin_access_cap( $allcaps, $caps, $args, $user ) {
    // Check if 'view_admin_dashboard' is one of the capabilities being checked
    if ( !in_array('view_admin_dashboard', $caps) ) {
        return $allcaps;
    }

    // If the user is a store_manager_frontend and is trying to access the admin area (not AJAX)
    if ( isset( $allcaps['store_manager_frontend'] ) && is_admin() && ! defined( 'DOING_AJAX' ) ) {
        // Allow access to profile page
        if ( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE === true ) {
            return $allcaps;
        }

        // WordPress uses $pagenow global to determine the current admin page.
        global $pagenow;
        // If they are trying to access wp-admin dashboard (index.php) directly, remove the capability.
        // The redirect_store_manager_frontend function should already have redirected them,
        // but this is a fallback or can be used for more granular control.
        if ( 'index.php' === $pagenow && empty($_GET) ) {
            unset( $allcaps['view_admin_dashboard'] );
        }
    }
    return $allcaps;
}
// add_filter( 'user_has_cap', 'esm_conditionally_remove_admin_access_cap', 10, 4 );

?>
