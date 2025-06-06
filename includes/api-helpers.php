<?php
/**
 * Helper functions for REST API Endpoints.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Get common query parameters for collections (products, orders).
 *
 * @return array
 */
function esm_get_collection_params() {
	$params = array();
	$params['page'] = array(
		'description'        => __( 'Current page of the collection.', 'easy-store-manager' ),
		'type'               => 'integer',
		'default'            => 1,
		'sanitize_callback'  => 'absint',
		'validate_callback'  => 'rest_validate_request_arg',
		'minimum'            => 1,
	);
	$params['per_page'] = array(
		'description'        => __( 'Maximum number of items to be returned in result set.', 'easy-store-manager' ),
		'type'               => 'integer',
		'default'            => 10,
		'minimum'            => 1,
		'maximum'            => 100,
		'sanitize_callback'  => 'absint',
		'validate_callback'  => 'rest_validate_request_arg',
	);
	$params['search'] = array(
		'description'        => __( 'Limit results to those matching a string.', 'easy-store-manager' ),
		'type'               => 'string',
		'sanitize_callback'  => 'sanitize_text_field',
		'validate_callback'  => 'rest_validate_request_arg',
	);
	$params['order'] = array(
		'description'        => __( 'Order sort attribute ascending or descending.', 'easy-store-manager' ),
		'type'               => 'string',
		'default'            => 'desc',
		'enum'               => array( 'asc', 'desc' ),
		'validate_callback'  => 'rest_validate_request_arg',
	);
	// Note: 'orderby' is context-specific (products vs orders) and defined in their respective param functions.
    return $params;
}


/**
 * Get common period arguments for report endpoints.
 * @return array
 */
function esm_get_report_period_args() {
    return array(
        'period' => array(
            'description' => __('Predefined period for the report.', 'easy-store-manager'),
            'type' => 'string',
            'enum' => array('7days', '30days', 'current_month', 'last_month', 'custom'),
            'default' => '7days',
            'sanitize_callback' => 'sanitize_key',
        ),
        'date_start' => array(
            'description' => __('Start date for custom period (YYYY-MM-DD).', 'easy-store-manager'),
            'type' => 'string',
            'format' => 'date', // Informational, validation is via wc_rest_validate_date_arg
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'wc_rest_validate_date_arg', // Use WooCommerce's date validation
        ),
        'date_end' => array(
            'description' => __('End date for custom period (YYYY-MM-DD).', 'easy-store-manager'),
            'type' => 'string',
            'format' => 'date',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'wc_rest_validate_date_arg',
        ),
    );
}

/**
 * Helper function to parse date period parameters from a REST request.
 * Returns an array with 'date_start' and 'date_end' WC_DateTime objects, or a WP_Error.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return array|WP_Error Array of WC_DateTime objects or WP_Error on failure.
 */
function esm_parse_date_period_params( WP_REST_Request $request ) {
    $period = $request->get_param('period');
    $date_start_str = $request->get_param('date_start');
    $date_end_str = $request->get_param('date_end');

    $now = new WC_DateTime();
    $date_end = $now->clone();

    switch ($period) {
        case '7days':
            $date_start = $now->clone()->modify('-6 days');
            break;
        case '30days':
            $date_start = $now->clone()->modify('-29 days');
            break;
        case 'current_month':
            $date_start = $now->clone()->modify('first day of this month');
            $date_end = $now->clone()->modify('last day of this month'); // Ensure end of current month
            break;
        case 'last_month':
            $date_start = $now->clone()->modify('first day of last month');
            $date_end = $now->clone()->modify('last day of last month');
            break;
        case 'custom':
            if (empty($date_start_str) || empty($date_end_str)) {
                return new WP_Error('esm_reports_custom_dates_required', __('Custom period requires date_start and date_end.', 'easy-store-manager'), array('status' => 400));
            }
            try {
                // Ensure the dates are treated as being in the site's timezone.
                $date_start = new WC_DateTime($date_start_str, new DateTimeZone(wc_timezone_string()));
                $date_end = new WC_DateTime($date_end_str, new DateTimeZone(wc_timezone_string()));

                if ($date_start > $date_end) {
                    return new WP_Error('esm_reports_invalid_date_range', __('Start date cannot be after end date.', 'easy-store-manager'), array('status' => 400));
                }
            } catch (Exception $e) {
                return new WP_Error('esm_reports_invalid_date_format', __('Invalid date format for custom period. Please use YYYY-MM-DD.', 'easy-store-manager'), array('status' => 400));
            }
            break;
        default:
             // Fallback to a default if an unexpected period is provided, e.g., '7days'
            $date_start = $now->clone()->modify('-6 days');
    }

    // Set time to beginning of day for start_date and end of day for end_date for full day inclusion
    $date_start->setTime(0,0,0);
    $date_end->setTime(23,59,59);

    return array('date_start' => $date_start, 'date_end' => $date_end);
}

/**
 * Permission check for product-related endpoints.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return true|WP_Error True if the user has permission, WP_Error object otherwise.
 */
function esm_product_permissions_check( $request ) {
    if ( current_user_can( 'edit_products' ) ) {
        return true;
    }
    return new WP_Error(
        'rest_forbidden_products',
        esc_html__( 'You do not have permission to manage products.', 'easy-store-manager' ),
        array( 'status' => rest_authorization_required_code() )
    );
}

/**
 * Permission check for order-related endpoints.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return true|WP_Error True if the user has permission, WP_Error object otherwise.
 */
function esm_order_permissions_check( $request ) {
    if ( current_user_can( 'edit_shop_orders' ) ) {
        return true;
    }
    return new WP_Error(
        'rest_forbidden_orders',
        esc_html__( 'You do not have permission to manage orders.', 'easy-store-manager' ),
        array( 'status' => rest_authorization_required_code() )
    );
}

?>
