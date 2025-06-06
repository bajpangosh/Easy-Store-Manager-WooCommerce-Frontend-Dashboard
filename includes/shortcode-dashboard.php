<?php
/**
 * Shortcode for displaying the Vue.js dashboard.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Renders the Store Management Dashboard shortcode.
 *
 * This function first checks if the current user has the 'store_manager_frontend' role.
 * If not, it displays an access denied message.
 * Otherwise, it outputs the HTML div that the Vue.js application will mount to.
 *
 * @return string HTML output for the dashboard placeholder or access denied message.
 */
function esm_store_management_dashboard_shortcode() {
    // Check if the current user has the specific role.
    // While 'store_manager_frontend' is a role, current_user_can() can check for roles directly.
    // Alternatively, a dedicated capability could be created and assigned to the role.
    if ( ! current_user_can( 'store_manager_frontend' ) ) {
        return '<p class="esm-access-denied">' . esc_html__( 'You do not have permission to access this dashboard.', 'easy-store-manager' ) . '</p>';
    }

    // The HTML element ID must match the ID used in your Vue app's mount point.
    // The loading indicator is part of the Vue app template now, but we can keep a basic one here too.
    $dashboard_html = '<div id="store-management-app" class="bg-gray-100 min-h-screen esm-dashboard-container">';
    $dashboard_html .= '  <div class="flex items-center justify-center min-h-screen">'; // Loading indicator styling
    $dashboard_html .= '    <div class="p-6 bg-white rounded-lg shadow-lg text-center">';
    $dashboard_html .= '      <svg class="mx-auto h-12 w-12 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">';
    $dashboard_html .= '        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>';
    $dashboard_html .= '        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>';
    $dashboard_html .= '      </svg>';
    $dashboard_html .= '      <p class="mt-4 text-lg font-medium text-gray-700">' . esc_html__( 'Loading Store Management Dashboard...', 'easy-store-manager' ) . '</p>';
    $dashboard_html .= '      <p class="text-sm text-gray-500">' . esc_html__( 'Please wait a moment.', 'easy-store-manager' ) . '</p>';
    $dashboard_html .= '    </div>';
    $dashboard_html .= '  </div>';
    $dashboard_html .= '</div>';

    return $dashboard_html;
}
add_shortcode( 'store_management_dashboard', 'esm_store_management_dashboard_shortcode' );

?>
