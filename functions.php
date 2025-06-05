<?php
/**
 * Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 */

/**
 * Register Store Manager Frontend Role
 */
function register_store_manager_frontend_role() {
    add_role(
        'store_manager_frontend',
        __( 'Store Manager Frontend', 'text-domain' ),
        array(
            'read_product'           => true,
            'edit_product'           => true,
            'delete_product'         => true,
            'edit_products'          => true,
            'delete_products'        => true,
            'assign_product_terms'   => true,
            'upload_files'           => true,
            'edit_shop_orders'       => true,
            'read_shop_orders'       => true,
            'view_admin_dashboard'   => true, // Granted initially
        )
    );
}
add_action( 'init', 'register_store_manager_frontend_role' );

/**
 * Create "Store Management" Page
 */
function create_store_management_page() {
    if ( null === get_page_by_path( 'storemanagement' ) ) {
        $page_args = array(
            'post_title'    => __( 'Store Management', 'text-domain' ),
            'post_name'     => 'storemanagement',
            'post_content'  => '[store_management_dashboard]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        );
        wp_insert_post( $page_args );
    }
}
add_action( 'init', 'create_store_management_page' );

/**
 * Redirect Store Manager Frontend from /wp-admin/ to /storemanagement/
 */
function redirect_store_manager_frontend() {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) && current_user_can( 'store_manager_frontend' ) ) {
        $screen = get_current_screen();
        if ( $screen && 'profile' === $screen->id ) {
            return;
        }
        if ( isset( $_GET['page'] ) && 'storemanagement' === $_GET['page'] ) {
            return;
        }
        wp_redirect( home_url( '/storemanagement/' ) );
        exit;
    }
}
add_action( 'admin_init', 'redirect_store_manager_frontend' );

// --- WordPress REST API Endpoints ---
add_action( 'rest_api_init', 'register_esm_api_routes' );

/**
 * Register API routes.
 */
function register_esm_api_routes() {
    $namespace = 'esm/v1';

    // --- Product Routes ---
    // ... (Product routes remain unchanged) ...
    register_rest_route( $namespace, '/products', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_products_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => esm_get_collection_params(),
        ),
    ) );
    register_rest_route( $namespace, '/products/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => array('id' => array('validate_callback' => fn($p) => is_numeric($p))),
        ),
    ) );
    register_rest_route( $namespace, '/products', array(
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'esm_create_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => esm_get_product_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
        ),
    ) );
    register_rest_route( $namespace, '/products/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'esm_update_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => esm_get_product_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
        ),
    ) );
    register_rest_route( $namespace, '/products/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'esm_delete_product_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => array(
                'id' => array('validate_callback' => fn($p) => is_numeric($p)),
                'force' => array('type' => 'boolean', 'default' => false),
            ),
        ),
    ) );
    register_rest_route( $namespace, '/products/bulk-update', array(
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'esm_bulk_update_products_handler',
            'permission_callback' => 'esm_product_permissions_check',
        ),
    ) );

    // --- Order Routes ---
    // ... (Order routes remain unchanged) ...
    register_rest_route( $namespace, '/orders', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_orders_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => esm_get_orders_collection_params(),
        ),
    ) );
    register_rest_route( $namespace, '/orders/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_order_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array('id' => array('validate_callback' => fn($p) => is_numeric($p))),
        ),
    ) );
    register_rest_route( $namespace, '/orders/(?P<id>\d+)/status', array(
        array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'esm_update_order_status_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array(
                'id'     => array('validate_callback' => fn($p) => is_numeric($p), 'required' => true),
                'status' => array('type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'),
            ),
        ),
    ) );
    register_rest_route( $namespace, '/orders/(?P<id>\d+)/notes', array(
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'esm_add_order_note_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array(
                'id'                => array('validate_callback' => fn($p) => is_numeric($p), 'required' => true),
                'note'              => array('type' => 'string', 'required' => true, 'sanitize_callback' => 'wp_kses_post'),
                'is_customer_note'  => array('type' => 'boolean', 'default' => false),
            ),
        ),
    ) );

    // --- Report Routes ---
    register_rest_route( $namespace, '/reports/sales', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_sales_report_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => esm_get_report_period_args(),
        ),
    ) );
    register_rest_route( $namespace, '/reports/bestsellers', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_bestsellers_report_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array_merge( esm_get_report_period_args(), array(
                'limit' => array('type' => 'integer', 'default' => 5, 'sanitize_callback' => 'absint', 'validate_callback' => fn($p) => is_numeric($p) && $p > 0 && $p <= 50),
            ) ),
        ),
    ) );
    register_rest_route( $namespace, '/reports/low-stock', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_low_stock_report_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => array(
                'threshold' => array('type' => 'integer', 'default' => get_option('woocommerce_notify_low_stock_amount', 5), 'sanitize_callback' => 'absint', 'validate_callback' => fn($p) => is_numeric($p) && $p >= 0),
                 'page'     => array('type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint'),
                 'per_page' => array('type' => 'integer', 'default' => 10, 'sanitize_callback' => 'absint'),
            ),
        ),
    ) );

}

// --- Permission Callbacks ---
// ... (esm_product_permissions_check, esm_order_permissions_check remain unchanged) ...
function esm_product_permissions_check( $request ) {
    if ( current_user_can( 'edit_products' ) ) {
        return true;
    }
    return new WP_Error( 'rest_forbidden_products', esc_html__( 'You do not have permission to manage products.', 'text-domain' ), array( 'status' => rest_authorization_required_code() ) );
}
function esm_order_permissions_check( $request ) {
    if ( current_user_can( 'edit_shop_orders' ) ) {
        return true;
    }
    return new WP_Error( 'rest_forbidden_orders', esc_html__( 'You do not have permission to manage orders.', 'text-domain' ), array( 'status' => rest_authorization_required_code() ) );
}

// --- Product Management ---
// ... (All product management functions remain unchanged) ...
function esm_get_collection_params() {
	$params = array();
	$params['page'] = array('description' => __( 'Current page of the collection.', 'text-domain' ), 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint', 'validate_callback' => 'rest_validate_request_arg', 'minimum' => 1);
	$params['per_page'] = array('description' => __( 'Maximum number of items to be returned in result set.', 'text-domain' ), 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 100, 'sanitize_callback' => 'absint', 'validate_callback' => 'rest_validate_request_arg');
	$params['search'] = array('description' => __( 'Limit results to those matching a string.', 'text-domain' ), 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'validate_callback' => 'rest_validate_request_arg');
	$params['order'] = array('description' => __( 'Order sort attribute ascending or descending.', 'text-domain' ), 'type' => 'string', 'default' => 'desc', 'enum' => array( 'asc', 'desc' ), 'validate_callback' => 'rest_validate_request_arg');
	$params['orderby'] = array('description' => __( 'Sort collection by object attribute.', 'text-domain' ), 'type' => 'string', 'default' => 'date', 'enum' => array('date', 'id', 'include', 'title', 'slug', 'modified', 'rand', 'menu_order', 'price'), 'validate_callback' => 'rest_validate_request_arg');
    return $params;
}
function esm_get_product_data( $product ) {
    if ( ! $product instanceof WC_Product ) return array();
    // ... (implementation unchanged)
    $data = array(
        'id' => $product->get_id(), 'name' => $product->get_name(), 'slug' => $product->get_slug(),
        'permalink' => $product->get_permalink(),
        'date_created' => wc_rest_prepare_date_response( $product->get_date_created(), false ),
        'date_created_gmt' => wc_rest_prepare_date_response( $product->get_date_created() ),
        'date_modified' => wc_rest_prepare_date_response( $product->get_date_modified(), false ),
        'date_modified_gmt' => wc_rest_prepare_date_response( $product->get_date_modified() ),
        'type' => $product->get_type(), 'status' => $product->get_status(), 'featured' => $product->is_featured(),
        'catalog_visibility' => $product->get_catalog_visibility(),
        'description' => wp_filter_post_kses( $product->get_description() ),
        'short_description' => wp_filter_post_kses( $product->get_short_description() ),
        'sku' => $product->get_sku(), 'price' => $product->get_price(),
        'regular_price' => $product->get_regular_price(), 'sale_price' => $product->get_sale_price(),
        'date_on_sale_from' => wc_rest_prepare_date_response( $product->get_date_on_sale_from(), false ),
        'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_from() ),
        'date_on_sale_to' => wc_rest_prepare_date_response( $product->get_date_on_sale_to(), false ),
        'date_on_sale_to_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_to() ),
        'price_html' => $product->get_price_html(), 'on_sale' => $product->is_on_sale(),
        'purchasable' => $product->is_purchasable(), 'total_sales' => $product->get_total_sales(),
        'virtual' => $product->is_virtual(), 'downloadable' => $product->is_downloadable(),
        'manage_stock' => $product->managing_stock(), 'stock_quantity' => $product->get_stock_quantity(),
        'stock_status' => $product->get_stock_status(), 'backorders' => $product->get_backorders(),
        'backorders_allowed' => $product->backorders_allowed(), 'backordered' => $product->is_on_backorder(),
        'weight' => $product->get_weight(),
        'dimensions' => array('length' => $product->get_length(), 'width' => $product->get_width(), 'height' => $product->get_height()),
        'shipping_required' => $product->needs_shipping(), 'shipping_taxable' => $product->is_shipping_taxable(),
        'shipping_class' => $product->get_shipping_class(), 'shipping_class_id' => $product->get_shipping_class_id(),
        'reviews_allowed' => $product->get_reviews_allowed(), 'average_rating' => $product->get_average_rating(),
        'rating_count' => $product->get_rating_count(), 'parent_id' => $product->get_parent_id(),
        'purchase_note' => wp_filter_post_kses( $product->get_purchase_note() ),
        'categories' => wc_get_object_terms( $product->get_id(), 'product_cat', 'name' ),
        'tags' => wc_get_object_terms( $product->get_id(), 'product_tag', 'name' ),
        'image_id' => $product->get_image_id(),
        'featured_image_url' => get_the_post_thumbnail_url( $product->get_id(), 'medium' ),
    );
    return $data;
}
function esm_get_products_handler( $request ) { /* ... */ }
function esm_get_product_handler( $request ) { /* ... */ }
function esm_get_product_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) { /* ... */ }
function esm_create_product_handler( $request ) { /* ... */ }
function esm_update_product_handler( $request ) { /* ... */ }
function esm_delete_product_handler( $request ) { /* ... */ }
function esm_bulk_update_products_handler( $request ) { /* ... */ }
// Note: To keep the response shorter, the full implementations of existing product functions are represented by /* ... */
// They were not changed in this step.

// --- Order Management ---
// ... (All order management functions remain unchanged) ...
function esm_get_orders_collection_params() { /* ... */ }
function esm_format_order_data_for_list( $order ) { /* ... */ }
function esm_format_single_order_data( $order ) { /* ... */ }
function esm_get_orders_handler( $request ) { /* ... */ }
function esm_get_order_handler( $request ) { /* ... */ }
function esm_update_order_status_handler( $request ) { /* ... */ }
function esm_add_order_note_handler( $request ) { /* ... */ }


// --- Report Management ---

/**
 * Helper function to parse date period parameters.
 * Returns an array with 'date_start' and 'date_end' WC_DateTime objects.
 */
function esm_parse_date_period_params( WP_REST_Request $request ) {
    $period = $request->get_param('period');
    $date_start_str = $request->get_param('date_start');
    $date_end_str = $request->get_param('date_end');

    $now = new WC_DateTime();
    $date_end = $now->clone(); // Default end date is today

    switch ($period) {
        case '7days':
            $date_start = $now->clone()->modify('-6 days'); // Includes today
            break;
        case '30days':
            $date_start = $now->clone()->modify('-29 days'); // Includes today
            break;
        case 'current_month':
            $date_start = $now->clone()->modify('first day of this month');
            break;
        case 'last_month':
            $date_start = $now->clone()->modify('first day of last month');
            $date_end = $now->clone()->modify('last day of last month');
            break;
        case 'custom':
            if (empty($date_start_str) || empty($date_end_str)) {
                return new WP_Error('esm_reports_custom_dates_required', __('Custom period requires date_start and date_end.', 'text-domain'), array('status' => 400));
            }
            try {
                $date_start = new WC_DateTime($date_start_str);
                $date_end = new WC_DateTime($date_end_str);
                if ($date_start > $date_end) {
                    return new WP_Error('esm_reports_invalid_date_range', __('Start date cannot be after end date.', 'text-domain'), array('status' => 400));
                }
            } catch (Exception $e) {
                return new WP_Error('esm_reports_invalid_date_format', __('Invalid date format for custom period.', 'text-domain'), array('status' => 400));
            }
            break;
        default: // Default to '7days' if invalid period
             $date_start = $now->clone()->modify('-6 days');
    }

    // Set time to beginning of day for start_date and end of day for end_date
    $date_start->setTime(0,0,0);
    $date_end->setTime(23,59,59);

    return array('date_start' => $date_start, 'date_end' => $date_end);
}

/**
 * Get common period arguments for report endpoints.
 */
function esm_get_report_period_args() {
    return array(
        'period' => array(
            'description' => __('Predefined period for the report.', 'text-domain'),
            'type' => 'string',
            'enum' => array('7days', '30days', 'current_month', 'last_month', 'custom'),
            'default' => '7days',
            'sanitize_callback' => 'sanitize_key',
        ),
        'date_start' => array(
            'description' => __('Start date for custom period (YYYY-MM-DD).', 'text-domain'),
            'type' => 'string',
            'format' => 'date',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'wc_rest_validate_date_arg',
        ),
        'date_end' => array(
            'description' => __('End date for custom period (YYYY-MM-DD).', 'text-domain'),
            'type' => 'string',
            'format' => 'date',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'wc_rest_validate_date_arg',
        ),
    );
}

/**
 * Handler for sales report endpoint.
 */
function esm_get_sales_report_handler( WP_REST_Request $request ) {
    $date_params = esm_parse_date_period_params($request);
    if (is_wp_error($date_params)) {
        return $date_params;
    }
    $date_start_obj = $date_params['date_start'];
    $date_end_obj = $date_params['date_end'];

    $orders = wc_get_orders(array(
        'limit'        => -1, // Get all orders in the range
        'status'       => array('wc-processing', 'wc-completed', 'wc-on-hold'), // Consider these as sales
        'date_created' => $date_start_obj->getTimestamp() . '...' . $date_end_obj->getTimestamp(),
    ));

    $total_sales = 0;
    $order_count = count($orders);
    $daily_sales = array();

    // Initialize daily sales for the period to 0
    $current_date = $date_start_obj->clone();
    while($current_date <= $date_end_obj) {
        $daily_sales[$current_date->format('Y-m-d')] = 0;
        $current_date->modify('+1 day');
    }

    foreach ($orders as $order) {
        if ($order instanceof WC_Order) {
            $total_sales += $order->get_total();
            $order_date_str = $order->get_date_created()->format('Y-m-d');
            if (isset($daily_sales[$order_date_str])) {
                 $daily_sales[$order_date_str] += $order->get_total();
            }
        }
    }

    $formatted_daily_sales = array();
    foreach ($daily_sales as $date => $total) {
        $formatted_daily_sales[] = array('date' => $date, 'total' => round($total, 2));
    }


    return new WP_REST_Response(array(
        'total_sales' => round($total_sales, 2),
        'order_count' => $order_count,
        'period'      => array(
            'start' => $date_start_obj->format('Y-m-d'),
            'end'   => $date_end_obj->format('Y-m-d'),
        ),
        'daily_sales_data' => $formatted_daily_sales,
    ), 200);
}


/**
 * Handler for bestsellers report endpoint.
 */
function esm_get_bestsellers_report_handler( WP_REST_Request $request ) {
    $date_params = esm_parse_date_period_params($request);
    if (is_wp_error($date_params)) {
        return $date_params;
    }
    $limit = $request->get_param('limit');

    $orders = wc_get_orders(array(
        'limit'        => -1,
        'status'       => array('wc-processing', 'wc-completed'), // Only count sales from processed/completed orders
        'date_created' => $date_params['date_start']->getTimestamp() . '...' . $date_params['date_end']->getTimestamp(),
    ));

    $product_sales = array();
    foreach ($orders as $order) {
        if ($order instanceof WC_Order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                // Use variation ID if it's a variation, otherwise product ID
                $id_to_track = $item->get_variation_id() > 0 ? $item->get_variation_id() : $product_id;
                if (!isset($product_sales[$id_to_track])) {
                    $product_sales[$id_to_track] = array(
                        'product_id' => $product_id, // Store parent product ID
                        'variation_id' => $item->get_variation_id(),
                        'name' => $item->get_name(), // Get name from item directly
                        'quantity_sold' => 0,
                    );
                }
                $product_sales[$id_to_track]['quantity_sold'] += $item->get_quantity();
            }
        }
    }

    uasort($product_sales, function($a, $b) {
        return $b['quantity_sold'] <=> $a['quantity_sold'];
    });

    $bestsellers = array_slice($product_sales, 0, $limit, true);

    // Re-fetch product details for current name and permalink if needed, or rely on item name.
    // For simplicity, we are using the name from the line item.

    return new WP_REST_Response(array_values($bestsellers), 200);
}


/**
 * Handler for low stock report endpoint.
 */
function esm_get_low_stock_report_handler( WP_REST_Request $request ) {
    $threshold = $request->get_param('threshold');
    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');

    $args = array(
        'status'       => 'publish',
        'type'         => array('simple', 'variation'), // Check simple products and variations
        'manage_stock' => true,
        'stock_status' => 'instock', // Only products that are in stock but low
        'limit'        => $per_page,
        'page'         => $page,
        'paginate'     => true,
        'meta_query'   => array( // Using meta_query for stock quantity
            array(
                'key'     => '_stock',
                'value'   => $threshold,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ),
             array( // Ensure stock is not null or empty string, which can happen
                'key'     => '_stock',
                'value'   => '',
                'compare' => '!=',
            ),
        ),
        'orderby' => array( // Order by stock quantity ascending
            'meta_value_num' => 'ASC',
            'ID'          => 'ASC',
        ),
        'meta_key' => '_stock', // Required for orderby meta_value_num
    );

    $query = new WC_Product_Query($args);
    $products_query_result = $query->get_products();

    $low_stock_products = array();
    foreach ($products_query_result->products as $product_obj) {
        if ($product_obj instanceof WC_Product) {
            $stock_quantity = $product_obj->get_stock_quantity();
            // Double check threshold here as meta_query with type NUMERIC can sometimes be tricky with empty/null values
            if ($stock_quantity !== null && $stock_quantity <= $threshold) {
                 $low_stock_products[] = array(
                    'product_id'     => $product_obj->get_id(),
                    'name'           => $product_obj->get_formatted_name(), // Gets name with variation attributes
                    'sku'            => $product_obj->get_sku(),
                    'stock_quantity' => $stock_quantity,
                    'permalink'      => $product_obj->get_permalink(),
                );
            }
        }
    }

    $response = new WP_REST_Response( $low_stock_products, 200 );
    $response->header( 'X-WP-Total', $products_query_result->total );
    $response->header( 'X-WP-TotalPages', $products_query_result->max_num_pages );

    return $response;
}

?>
