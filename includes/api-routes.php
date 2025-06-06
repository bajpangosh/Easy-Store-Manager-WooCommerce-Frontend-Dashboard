<?php
/**
 * REST API Route Registration.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register all API routes for the plugin.
 * Hooked to 'rest_api_init'.
 */
function esm_register_all_api_routes() {
    $namespace = 'esm/v1'; // Define your plugin's namespace

    // --- Product Routes ---
    register_rest_route( $namespace, '/products', array(
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'esm_get_products_handler', // Defined in api-product.php
            'permission_callback' => 'esm_product_permissions_check', // Defined in api-helpers.php
            'args'                => esm_get_product_collection_params_args(), // Using a specific arg getter for products
        ),
        array(
            'methods'             => WP_REST_Server::CREATABLE, // POST
            'callback'            => 'esm_create_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => esm_get_product_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
        ),
    ) );

    register_rest_route( $namespace, '/products/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'esm_get_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => array(
                'id' => array( 'validate_callback' => fn($param) => is_numeric($param) && $param > 0, 'required' => true ),
            ),
        ),
        array(
            'methods'             => WP_REST_Server::EDITABLE, // PUT/POST
            'callback'            => 'esm_update_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => esm_get_product_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
        ),
        array(
            'methods'             => WP_REST_Server::DELETABLE, // DELETE
            'callback'            => 'esm_delete_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => array(
                'id'    => array( 'validate_callback' => fn($param) => is_numeric($param) && $param > 0, 'required' => true ),
                'force' => array( 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'wc_string_to_bool' ),
            ),
        ),
    ) );

    register_rest_route( $namespace, '/products/bulk-update', array(
        array(
            'methods'             => WP_REST_Server::CREATABLE, // POST
            'callback'            => 'esm_bulk_update_products_handler',
            'permission_callback' => 'esm_product_permissions_check',
            // Args for bulk update are typically handled within the handler due to variability
        ),
    ) );

    // --- Order Routes ---
    register_rest_route( $namespace, '/orders', array(
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'esm_get_orders_handler', // Defined in api-order.php
            'permission_callback' => 'esm_order_permissions_check', // Defined in api-helpers.php
            'args'                => esm_get_orders_collection_params(), // Defined in api-order.php (uses helper)
        ),
    ) );

    register_rest_route( $namespace, '/orders/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'esm_get_order_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array(
                'id' => array( 'validate_callback' => fn($param) => is_numeric($param) && $param > 0, 'required' => true ),
            ),
        ),
    ) );

    register_rest_route( $namespace, '/orders/(?P<id>\d+)/status', array(
        array(
            'methods'             => WP_REST_Server::EDITABLE, // PUT or POST
            'callback'            => 'esm_update_order_status_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array(
                'id'     => array( 'validate_callback' => fn($param) => is_numeric($param) && $param > 0, 'required' => true ),
                'status' => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_key' ),
            ),
        ),
    ) );

    register_rest_route( $namespace, '/orders/(?P<id>\d+)/notes', array(
        array(
            'methods'             => WP_REST_Server::CREATABLE, // POST
            'callback'            => 'esm_add_order_note_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array(
                'id'                => array( 'validate_callback' => fn($param) => is_numeric($param) && $param > 0, 'required' => true ),
                'note'              => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'wp_kses_post' ),
                'is_customer_note'  => array( 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'wc_string_to_bool' ),
            ),
        ),
    ) );

    // --- Report Routes ---
    register_rest_route( $namespace, '/reports/sales', array(
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'esm_get_sales_report_handler', // Defined in api-report.php
            'permission_callback' => 'esm_order_permissions_check', // Sales data often tied to order permissions
            'args'                => esm_get_report_period_args(), // Defined in api-helpers.php
        ),
    ) );

    register_rest_route( $namespace, '/reports/bestsellers', array(
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'esm_get_bestsellers_report_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array_merge(
                esm_get_report_period_args(),
                array(
                    'limit' => array(
                        'description'       => __('Number of bestsellers to return.', 'easy-store-manager'),
                        'type'              => 'integer',
                        'default'           => 5,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => fn($param) => is_numeric($param) && $param > 0 && $param <= 50
                    ),
                )
            ),
        ),
    ) );

    register_rest_route( $namespace, '/reports/low-stock', array(
        array(
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => 'esm_get_low_stock_report_handler',
            'permission_callback' => 'esm_product_permissions_check', // Low stock is product related
            'args'                => array(
                'threshold' => array(
                    'description'       => __('Stock quantity at or below which products are considered low stock.', 'easy-store-manager'),
                    'type'              => 'integer',
                    'default'           => intval(get_option('woocommerce_notify_low_stock_amount', 5)),
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn($param) => is_numeric($param) && $param >= 0
                ),
                'page'     => array(
                    'description'       => __('Current page of the collection for pagination.', 'easy-store-manager'),
                    'type'              => 'integer',
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn($param) => is_numeric($param) && $param > 0
                ),
                'per_page' => array(
                    'description'       => __('Maximum number of items to be returned in result set.', 'easy-store-manager'),
                    'type'              => 'integer',
                    'default'           => 10,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn($param) => is_numeric($param) && $param > 0 && $param <= 100
                ),
            ),
        ),
    ) );
}

/**
 * Get collection parameters specific to products.
 * Extends common collection params.
 * @return array
 */
function esm_get_product_collection_params_args() {
    $params = esm_get_collection_params(); // From api-helpers.php
    $params['orderby']['enum'] = array('date', 'id', 'include', 'title', 'slug', 'modified', 'rand', 'menu_order', 'price', 'popularity', 'rating');
    $params['status'] = array(
        'default'           => 'any',
        'description'       => __( 'Limit result set to products with specific statuses.', 'easy-store-manager' ),
        'type'              => 'string', // WC REST API uses string for product status, not array
        'enum'              => array_keys( get_post_statuses() ), // Consider wc_get_product_statuses()
        'sanitize_callback' => 'sanitize_key',
        'validate_callback' => 'rest_validate_request_arg',
    );
     $params['type'] = array(
        'description'       => __( 'Limit result set to products of a specific type.', 'easy-store-manager' ),
        'type'              => 'string', // Can be array in some contexts, but usually string for single type filter
        'enum'              => array_keys( wc_get_product_types() ),
        'sanitize_callback' => 'sanitize_key',
        'validate_callback' => 'rest_validate_request_arg',
    );
    // Add other product-specific filters like category, tag, featured, etc. if needed
    return $params;
}
?>
