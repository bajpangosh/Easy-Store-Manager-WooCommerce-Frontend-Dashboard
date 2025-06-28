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
            'view_admin_dashboard'   => true,
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
 * Register the shortcode for store management dashboard
 */
function store_management_dashboard_shortcode( $atts ) {
    // Check if user has permission
    if ( ! current_user_can( 'edit_products' ) && ! current_user_can( 'edit_shop_orders' ) ) {
        return '<div class="store-management-error"><p>' . __( 'You do not have permission to access this dashboard.', 'text-domain' ) . '</p></div>';
    }

    // Enqueue scripts and styles
    wp_enqueue_script( 'vue', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.3.4', true );
    wp_enqueue_script( 'store-management-app', get_template_directory_uri() . '/store-management-app.js', array( 'vue' ), '1.0.0', true );
    wp_enqueue_style( 'tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.3.0' );
    
    // Enqueue WordPress media library
    wp_enqueue_media();
    
    // Localize script with API data
    wp_localize_script( 'store-management-app', 'esmData', array(
        'apiUrl'    => home_url( '/wp-json/esm/v1/' ),
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'currentUser' => wp_get_current_user()->display_name,
        'userEmail' => wp_get_current_user()->user_email,
    ) );

    // Load the HTML template
    ob_start();
    include get_template_directory() . '/store-management-app.html';
    return ob_get_clean();
}
add_shortcode( 'store_management_dashboard', 'store_management_dashboard_shortcode' );

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

/**
 * Rate limiting functionality
 */
function esm_check_rate_limit( $user_id = null ) {
    $user_id = $user_id ?: get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'esm_rate_limit_' . $user_id . '_' . md5( $ip );
    
    $requests = get_transient( $key );
    $limit = defined( 'ESM_RATE_LIMIT_REQUESTS' ) ? ESM_RATE_LIMIT_REQUESTS : 100;
    $window = defined( 'ESM_RATE_LIMIT_WINDOW' ) ? ESM_RATE_LIMIT_WINDOW : 60;
    
    if ( false === $requests ) {
        set_transient( $key, 1, $window );
        return true;
    }
    
    if ( $requests >= $limit ) {
        return false;
    }
    
    set_transient( $key, $requests + 1, $window );
    return true;
}

/**
 * Add security headers
 */
function esm_add_security_headers() {
    if ( is_page( 'storemanagement' ) ) {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: DENY' );
        header( 'X-XSS-Protection: 1; mode=block' );
    }
}
add_action( 'send_headers', 'esm_add_security_headers' );

// --- WordPress REST API Endpoints ---
add_action( 'rest_api_init', 'register_esm_api_routes' );

/**
 * Register API routes.
 */
function register_esm_api_routes() {
    $namespace = 'esm/v1';

    // Product Routes
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
            'args'                => array('id' => array('validate_callback' => function($p) { return is_numeric($p); })),
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
                'id' => array('validate_callback' => function($p) { return is_numeric($p); }),
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

    // Order Routes
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
            'args'                => array('id' => array('validate_callback' => function($p) { return is_numeric($p); })),
        ),
    ) );
    
    register_rest_route( $namespace, '/orders/(?P<id>\d+)/status', array(
        array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'esm_update_order_status_handler',
            'permission_callback' => 'esm_order_permissions_check',
            'args'                => array(
                'id'     => array('validate_callback' => function($p) { return is_numeric($p); }, 'required' => true),
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
                'id'                => array('validate_callback' => function($p) { return is_numeric($p); }, 'required' => true),
                'note'              => array('type' => 'string', 'required' => true, 'sanitize_callback' => 'wp_kses_post'),
                'is_customer_note'  => array('type' => 'boolean', 'default' => false),
            ),
        ),
    ) );

    // Report Routes
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
                'limit' => array('type' => 'integer', 'default' => 5, 'sanitize_callback' => 'absint', 'validate_callback' => function($p) { return is_numeric($p) && $p > 0 && $p <= 50; }),
            ) ),
        ),
    ) );
    
    register_rest_route( $namespace, '/reports/low-stock', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'esm_get_low_stock_report_handler',
            'permission_callback' => 'esm_product_permissions_check',
            'args'                => array(
                'threshold' => array('type' => 'integer', 'default' => get_option('woocommerce_notify_low_stock_amount', 5), 'sanitize_callback' => 'absint', 'validate_callback' => function($p) { return is_numeric($p) && $p >= 0; }),
                'page'     => array('type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint'),
                'per_page' => array('type' => 'integer', 'default' => 10, 'sanitize_callback' => 'absint'),
            ),
        ),
    ) );
}

// Permission Callbacks
function esm_product_permissions_check( $request ) {
    if ( ! esm_check_rate_limit() ) {
        return new WP_Error( 'rest_rate_limited', __( 'Rate limit exceeded. Please try again later.', 'text-domain' ), array( 'status' => 429 ) );
    }
    
    if ( current_user_can( 'edit_products' ) ) {
        return true;
    }
    return new WP_Error( 'rest_forbidden_products', __( 'You do not have permission to manage products.', 'text-domain' ), array( 'status' => rest_authorization_required_code() ) );
}

function esm_order_permissions_check( $request ) {
    if ( ! esm_check_rate_limit() ) {
        return new WP_Error( 'rest_rate_limited', __( 'Rate limit exceeded. Please try again later.', 'text-domain' ), array( 'status' => 429 ) );
    }
    
    if ( current_user_can( 'edit_shop_orders' ) ) {
        return true;
    }
    return new WP_Error( 'rest_forbidden_orders', __( 'You do not have permission to manage orders.', 'text-domain' ), array( 'status' => rest_authorization_required_code() ) );
}

// Product Management Functions
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
    
    $data = array(
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'slug' => $product->get_slug(),
        'permalink' => $product->get_permalink(),
        'date_created' => wc_rest_prepare_date_response( $product->get_date_created(), false ),
        'date_created_gmt' => wc_rest_prepare_date_response( $product->get_date_created() ),
        'date_modified' => wc_rest_prepare_date_response( $product->get_date_modified(), false ),
        'date_modified_gmt' => wc_rest_prepare_date_response( $product->get_date_modified() ),
        'type' => $product->get_type(),
        'status' => $product->get_status(),
        'featured' => $product->is_featured(),
        'catalog_visibility' => $product->get_catalog_visibility(),
        'description' => wp_filter_post_kses( $product->get_description() ),
        'short_description' => wp_filter_post_kses( $product->get_short_description() ),
        'sku' => $product->get_sku(),
        'price' => $product->get_price(),
        'regular_price' => $product->get_regular_price(),
        'sale_price' => $product->get_sale_price(),
        'date_on_sale_from' => wc_rest_prepare_date_response( $product->get_date_on_sale_from(), false ),
        'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_from() ),
        'date_on_sale_to' => wc_rest_prepare_date_response( $product->get_date_on_sale_to(), false ),
        'date_on_sale_to_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_to() ),
        'price_html' => $product->get_price_html(),
        'on_sale' => $product->is_on_sale(),
        'purchasable' => $product->is_purchasable(),
        'total_sales' => $product->get_total_sales(),
        'virtual' => $product->is_virtual(),
        'downloadable' => $product->is_downloadable(),
        'manage_stock' => $product->managing_stock(),
        'stock_quantity' => $product->get_stock_quantity(),
        'stock_status' => $product->get_stock_status(),
        'backorders' => $product->get_backorders(),
        'backorders_allowed' => $product->backorders_allowed(),
        'backordered' => $product->is_on_backorder(),
        'weight' => $product->get_weight(),
        'dimensions' => array(
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height()
        ),
        'shipping_required' => $product->needs_shipping(),
        'shipping_taxable' => $product->is_shipping_taxable(),
        'shipping_class' => $product->get_shipping_class(),
        'shipping_class_id' => $product->get_shipping_class_id(),
        'reviews_allowed' => $product->get_reviews_allowed(),
        'average_rating' => $product->get_average_rating(),
        'rating_count' => $product->get_rating_count(),
        'parent_id' => $product->get_parent_id(),
        'purchase_note' => wp_filter_post_kses( $product->get_purchase_note() ),
        'categories' => wc_get_object_terms( $product->get_id(), 'product_cat', 'name' ),
        'tags' => wc_get_object_terms( $product->get_id(), 'product_tag', 'name' ),
        'image_id' => $product->get_image_id(),
        'featured_image_url' => get_the_post_thumbnail_url( $product->get_id(), 'medium' ),
    );
    return $data;
}

function esm_get_products_handler( $request ) {
    try {
        $args = array(
            'status' => 'any',
            'limit' => $request->get_param('per_page'),
            'page' => $request->get_param('page'),
            'paginate' => true,
        );

        if ( $request->get_param('search') ) {
            $args['s'] = sanitize_text_field( $request->get_param('search') );
        }

        if ( $request->get_param('orderby') ) {
            $args['orderby'] = sanitize_text_field( $request->get_param('orderby') );
        }

        if ( $request->get_param('order') ) {
            $args['order'] = sanitize_text_field( $request->get_param('order') );
        }

        $query = new WC_Product_Query( $args );
        $products_query_result = $query->get_products();

        $products = array();
        foreach ( $products_query_result->products as $product ) {
            $products[] = esm_get_product_data( $product );
        }

        $response = new WP_REST_Response( $products, 200 );
        $response->header( 'X-WP-Total', $products_query_result->total );
        $response->header( 'X-WP-TotalPages', $products_query_result->max_num_pages );

        return $response;
    } catch ( Exception $e ) {
        error_log( 'ESM Product List Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_products_error', __( 'Failed to retrieve products.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_get_product_handler( $request ) {
    try {
        $product_id = (int) $request['id'];
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return new WP_Error( 'esm_product_not_found', __( 'Product not found.', 'text-domain' ), array( 'status' => 404 ) );
        }

        return new WP_REST_Response( esm_get_product_data( $product ), 200 );
    } catch ( Exception $e ) {
        error_log( 'ESM Product Get Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_product_error', __( 'Failed to retrieve product.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_get_product_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
    $args = array(
        'name' => array(
            'description' => __( 'Product name.', 'text-domain' ),
            'type' => 'string',
            'required' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'description' => array(
            'description' => __( 'Product description.', 'text-domain' ),
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ),
        'short_description' => array(
            'description' => __( 'Product short description.', 'text-domain' ),
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ),
        'sku' => array(
            'description' => __( 'Product SKU.', 'text-domain' ),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'regular_price' => array(
            'description' => __( 'Product regular price.', 'text-domain' ),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'sale_price' => array(
            'description' => __( 'Product sale price.', 'text-domain' ),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'status' => array(
            'description' => __( 'Product status.', 'text-domain' ),
            'type' => 'string',
            'enum' => array( 'draft', 'pending', 'private', 'publish' ),
            'default' => 'publish',
        ),
        'stock_quantity' => array(
            'description' => __( 'Stock quantity.', 'text-domain' ),
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ),
        'manage_stock' => array(
            'description' => __( 'Stock management at product level.', 'text-domain' ),
            'type' => 'boolean',
            'default' => false,
        ),
        'stock_status' => array(
            'description' => __( 'Stock status.', 'text-domain' ),
            'type' => 'string',
            'enum' => array( 'instock', 'outofstock', 'onbackorder' ),
            'default' => 'instock',
        ),
        'categories' => array(
            'description' => __( 'Product categories.', 'text-domain' ),
            'type' => 'array',
            'items' => array( 'type' => 'string' ),
        ),
        'tags' => array(
            'description' => __( 'Product tags.', 'text-domain' ),
            'type' => 'array',
            'items' => array( 'type' => 'string' ),
        ),
        'image_id' => array(
            'description' => __( 'Featured image ID.', 'text-domain' ),
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ),
    );

    return $args;
}

function esm_create_product_handler( $request ) {
    try {
        $product = new WC_Product_Simple();
        
        // Set basic product data
        $product->set_name( sanitize_text_field( $request->get_param('name') ) );
        $product->set_description( wp_kses_post( $request->get_param('description') ) );
        $product->set_short_description( wp_kses_post( $request->get_param('short_description') ) );
        $product->set_sku( sanitize_text_field( $request->get_param('sku') ) );
        $product->set_regular_price( sanitize_text_field( $request->get_param('regular_price') ) );
        $product->set_sale_price( sanitize_text_field( $request->get_param('sale_price') ) );
        $product->set_status( sanitize_text_field( $request->get_param('status') ?: 'publish' ) );
        
        // Stock management
        if ( $request->get_param('manage_stock') ) {
            $product->set_manage_stock( true );
            $product->set_stock_quantity( absint( $request->get_param('stock_quantity') ) );
        }
        $product->set_stock_status( sanitize_text_field( $request->get_param('stock_status') ?: 'instock' ) );
        
        // Save product
        $product_id = $product->save();
        
        // Set featured image
        if ( $request->get_param('image_id') ) {
            set_post_thumbnail( $product_id, absint( $request->get_param('image_id') ) );
        }
        
        // Set categories
        if ( $request->get_param('categories') ) {
            $categories = array_map( 'sanitize_text_field', $request->get_param('categories') );
            wp_set_object_terms( $product_id, $categories, 'product_cat' );
        }
        
        // Set tags
        if ( $request->get_param('tags') ) {
            $tags = array_map( 'sanitize_text_field', $request->get_param('tags') );
            wp_set_object_terms( $product_id, $tags, 'product_tag' );
        }
        
        $response = new WP_REST_Response( esm_get_product_data( wc_get_product( $product_id ) ), 201 );
        $response->header( 'Location', rest_url( sprintf( '/esm/v1/products/%d', $product_id ) ) );
        
        return $response;
    } catch ( Exception $e ) {
        error_log( 'ESM Product Create Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_product_create_error', __( 'Failed to create product.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_update_product_handler( $request ) {
    try {
        $product_id = (int) $request['id'];
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return new WP_Error( 'esm_product_not_found', __( 'Product not found.', 'text-domain' ), array( 'status' => 404 ) );
        }

        // Update product data
        if ( $request->get_param('name') ) {
            $product->set_name( sanitize_text_field( $request->get_param('name') ) );
        }
        if ( $request->get_param('description') ) {
            $product->set_description( wp_kses_post( $request->get_param('description') ) );
        }
        if ( $request->get_param('short_description') ) {
            $product->set_short_description( wp_kses_post( $request->get_param('short_description') ) );
        }
        if ( $request->get_param('sku') ) {
            $product->set_sku( sanitize_text_field( $request->get_param('sku') ) );
        }
        if ( $request->get_param('regular_price') ) {
            $product->set_regular_price( sanitize_text_field( $request->get_param('regular_price') ) );
        }
        if ( $request->get_param('sale_price') ) {
            $product->set_sale_price( sanitize_text_field( $request->get_param('sale_price') ) );
        }
        if ( $request->get_param('status') ) {
            $product->set_status( sanitize_text_field( $request->get_param('status') ) );
        }
        
        // Stock management
        if ( $request->has_param('manage_stock') ) {
            $product->set_manage_stock( $request->get_param('manage_stock') );
            if ( $request->get_param('manage_stock') && $request->has_param('stock_quantity') ) {
                $product->set_stock_quantity( absint( $request->get_param('stock_quantity') ) );
            }
        }
        if ( $request->get_param('stock_status') ) {
            $product->set_stock_status( sanitize_text_field( $request->get_param('stock_status') ) );
        }
        
        $product->save();
        
        // Update featured image
        if ( $request->has_param('image_id') ) {
            if ( $request->get_param('image_id') ) {
                set_post_thumbnail( $product_id, absint( $request->get_param('image_id') ) );
            } else {
                delete_post_thumbnail( $product_id );
            }
        }
        
        // Update categories
        if ( $request->has_param('categories') ) {
            $categories = array_map( 'sanitize_text_field', $request->get_param('categories') );
            wp_set_object_terms( $product_id, $categories, 'product_cat' );
        }
        
        // Update tags
        if ( $request->has_param('tags') ) {
            $tags = array_map( 'sanitize_text_field', $request->get_param('tags') );
            wp_set_object_terms( $product_id, $tags, 'product_tag' );
        }
        
        return new WP_REST_Response( esm_get_product_data( wc_get_product( $product_id ) ), 200 );
    } catch ( Exception $e ) {
        error_log( 'ESM Product Update Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_product_update_error', __( 'Failed to update product.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_delete_product_handler( $request ) {
    try {
        $product_id = (int) $request['id'];
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return new WP_Error( 'esm_product_not_found', __( 'Product not found.', 'text-domain' ), array( 'status' => 404 ) );
        }

        $force = $request->get_param('force');
        $previous = esm_get_product_data( $product );

        if ( $force ) {
            $result = $product->delete( true );
        } else {
            $product->set_status( 'trash' );
            $product->save();
            $result = true;
        }

        if ( ! $result ) {
            return new WP_Error( 'esm_product_delete_error', __( 'Failed to delete product.', 'text-domain' ), array( 'status' => 500 ) );
        }

        return new WP_REST_Response( array(
            'deleted' => true,
            'previous' => $previous,
        ), 200 );
    } catch ( Exception $e ) {
        error_log( 'ESM Product Delete Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_product_delete_error', __( 'Failed to delete product.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_bulk_update_products_handler( $request ) {
    try {
        $updates = $request->get_json_params();
        $results = array();

        if ( ! is_array( $updates ) ) {
            return new WP_Error( 'esm_invalid_data', __( 'Invalid bulk update data.', 'text-domain' ), array( 'status' => 400 ) );
        }

        foreach ( $updates as $update ) {
            if ( ! isset( $update['id'] ) ) {
                $results[] = array( 'error' => __( 'Product ID is required.', 'text-domain' ) );
                continue;
            }

            $product_id = (int) $update['id'];
            $product = wc_get_product( $product_id );

            if ( ! $product ) {
                $results[] = array( 'id' => $product_id, 'error' => __( 'Product not found.', 'text-domain' ) );
                continue;
            }

            // Apply updates
            foreach ( $update as $key => $value ) {
                switch ( $key ) {
                    case 'status':
                        $product->set_status( sanitize_text_field( $value ) );
                        break;
                    case 'regular_price':
                        $product->set_regular_price( sanitize_text_field( $value ) );
                        break;
                    case 'sale_price':
                        $product->set_sale_price( sanitize_text_field( $value ) );
                        break;
                    case 'stock_quantity':
                        $product->set_stock_quantity( absint( $value ) );
                        break;
                    case 'stock_status':
                        $product->set_stock_status( sanitize_text_field( $value ) );
                        break;
                }
            }

            $product->save();
            $results[] = array( 'id' => $product_id, 'success' => true );
        }

        return new WP_REST_Response( $results, 200 );
    } catch ( Exception $e ) {
        error_log( 'ESM Bulk Update Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_bulk_update_error', __( 'Failed to perform bulk update.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

// Order Management Functions
function esm_get_orders_collection_params() {
    $params = array(
        'page' => array(
            'description' => __( 'Current page of the collection.', 'text-domain' ),
            'type' => 'integer',
            'default' => 1,
            'sanitize_callback' => 'absint',
            'minimum' => 1,
        ),
        'per_page' => array(
            'description' => __( 'Maximum number of items to be returned in result set.', 'text-domain' ),
            'type' => 'integer',
            'default' => 10,
            'minimum' => 1,
            'maximum' => 100,
            'sanitize_callback' => 'absint',
        ),
        'search' => array(
            'description' => __( 'Limit results to those matching a string.', 'text-domain' ),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'status' => array(
            'description' => __( 'Limit result set to orders with specific statuses.', 'text-domain' ),
            'type' => 'array',
            'items' => array( 'type' => 'string' ),
            'sanitize_callback' => function( $statuses ) {
                return array_map( 'sanitize_text_field', $statuses );
            },
        ),
        'orderby' => array(
            'description' => __( 'Sort collection by object attribute.', 'text-domain' ),
            'type' => 'string',
            'default' => 'date',
            'enum' => array( 'date', 'id', 'total' ),
        ),
        'order' => array(
            'description' => __( 'Order sort attribute ascending or descending.', 'text-domain' ),
            'type' => 'string',
            'default' => 'desc',
            'enum' => array( 'asc', 'desc' ),
        ),
        'date_before' => array(
            'description' => __( 'Limit response to orders created before a given date.', 'text-domain' ),
            'type' => 'string',
            'format' => 'date-time',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'date_after' => array(
            'description' => __( 'Limit response to orders created after a given date.', 'text-domain' ),
            'type' => 'string',
            'format' => 'date-time',
            'sanitize_callback' => 'sanitize_text_field',
        ),
    );
    return $params;
}

function esm_format_order_data_for_list( $order ) {
    if ( ! $order instanceof WC_Order ) return array();
    
    return array(
        'id' => $order->get_id(),
        'order_number' => $order->get_order_number(),
        'status' => $order->get_status(),
        'currency' => $order->get_currency(),
        'date_created' => wc_rest_prepare_date_response( $order->get_date_created(), false ),
        'date_created_gmt' => wc_rest_prepare_date_response( $order->get_date_created() ),
        'total' => $order->get_total(),
        'customer_id' => $order->get_customer_id(),
        'billing' => array(
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
        ),
        'payment_method' => $order->get_payment_method(),
        'payment_method_title' => $order->get_payment_method_title(),
    );
}

function esm_format_single_order_data( $order ) {
    if ( ! $order instanceof WC_Order ) return array();
    
    $line_items = array();
    foreach ( $order->get_items() as $item_id => $item ) {
        $product = $item->get_product();
        $line_items[] = array(
            'id' => $item_id,
            'name' => $item->get_name(),
            'product_id' => $item->get_product_id(),
            'variation_id' => $item->get_variation_id(),
            'quantity' => $item->get_quantity(),
            'subtotal' => $item->get_subtotal(),
            'total' => $item->get_total(),
            'sku' => $product ? $product->get_sku() : '',
        );
    }
    
    $notes = array();
    $order_notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
    foreach ( $order_notes as $note ) {
        $notes[] = array(
            'id' => $note->comment_ID,
            'date_created' => $note->comment_date,
            'note' => $note->comment_content,
            'customer_note' => $note->comment_meta['is_customer_note'][0] ?? false,
            'added_by' => $note->comment_author,
        );
    }
    
    return array(
        'id' => $order->get_id(),
        'order_number' => $order->get_order_number(),
        'status' => $order->get_status(),
        'currency' => $order->get_currency(),
        'date_created' => wc_rest_prepare_date_response( $order->get_date_created(), false ),
        'date_created_gmt' => wc_rest_prepare_date_response( $order->get_date_created() ),
        'date_modified' => wc_rest_prepare_date_response( $order->get_date_modified(), false ),
        'date_modified_gmt' => wc_rest_prepare_date_response( $order->get_date_modified() ),
        'discount_total' => $order->get_discount_total(),
        'discount_tax' => $order->get_discount_tax(),
        'shipping_total' => $order->get_shipping_total(),
        'shipping_tax' => $order->get_shipping_tax(),
        'cart_tax' => $order->get_cart_tax(),
        'total' => $order->get_total(),
        'total_tax' => $order->get_total_tax(),
        'customer_id' => $order->get_customer_id(),
        'customer_ip_address' => $order->get_customer_ip_address(),
        'customer_user_agent' => $order->get_customer_user_agent(),
        'customer_note' => $order->get_customer_note(),
        'billing' => array(
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'company' => $order->get_billing_company(),
            'address_1' => $order->get_billing_address_1(),
            'address_2' => $order->get_billing_address_2(),
            'city' => $order->get_billing_city(),
            'state' => $order->get_billing_state(),
            'postcode' => $order->get_billing_postcode(),
            'country' => $order->get_billing_country(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
        ),
        'shipping' => array(
            'first_name' => $order->get_shipping_first_name(),
            'last_name' => $order->get_shipping_last_name(),
            'company' => $order->get_shipping_company(),
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'postcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
        ),
        'payment_method' => $order->get_payment_method(),
        'payment_method_title' => $order->get_payment_method_title(),
        'transaction_id' => $order->get_transaction_id(),
        'line_items' => $line_items,
        'notes' => $notes,
    );
}

function esm_get_orders_handler( $request ) {
    try {
        $args = array(
            'limit' => $request->get_param('per_page'),
            'page' => $request->get_param('page'),
            'paginate' => true,
        );

        if ( $request->get_param('search') ) {
            $search = sanitize_text_field( $request->get_param('search') );
            // Search by order ID or customer email
            if ( is_numeric( $search ) ) {
                $args['include'] = array( $search );
            } else {
                $args['billing_email'] = $search;
            }
        }

        if ( $request->get_param('status') ) {
            $statuses = array_map( function( $status ) {
                return 'wc-' . sanitize_text_field( $status );
            }, $request->get_param('status') );
            $args['status'] = $statuses;
        }

        if ( $request->get_param('orderby') ) {
            $args['orderby'] = sanitize_text_field( $request->get_param('orderby') );
        }

        if ( $request->get_param('order') ) {
            $args['order'] = sanitize_text_field( $request->get_param('order') );
        }

        if ( $request->get_param('date_before') ) {
            $args['date_before'] = sanitize_text_field( $request->get_param('date_before') );
        }

        if ( $request->get_param('date_after') ) {
            $args['date_after'] = sanitize_text_field( $request->get_param('date_after') );
        }

        $orders_query_result = wc_get_orders( $args );

        $orders = array();
        foreach ( $orders_query_result->orders as $order ) {
            $orders[] = esm_format_order_data_for_list( $order );
        }

        $response = new WP_REST_Response( $orders, 200 );
        $response->header( 'X-WP-Total', $orders_query_result->total );
        $response->header( 'X-WP-TotalPages', $orders_query_result->max_num_pages );

        return $response;
    } catch ( Exception $e ) {
        error_log( 'ESM Orders List Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_orders_error', __( 'Failed to retrieve orders.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_get_order_handler( $request ) {
    try {
        $order_id = (int) $request['id'];
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error( 'esm_order_not_found', __( 'Order not found.', 'text-domain' ), array( 'status' => 404 ) );
        }

        return new WP_REST_Response( esm_format_single_order_data( $order ), 200 );
    } catch ( Exception $e ) {
        error_log( 'ESM Order Get Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_order_error', __( 'Failed to retrieve order.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_update_order_status_handler( $request ) {
    try {
        $order_id = (int) $request['id'];
        $new_status = sanitize_text_field( $request->get_param('status') );

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error( 'esm_order_not_found', __( 'Order not found.', 'text-domain' ), array( 'status' => 404 ) );
        }

        // Validate status
        $valid_statuses = array_keys( wc_get_order_statuses() );
        $status_with_prefix = 'wc-' . $new_status;
        
        if ( ! in_array( $status_with_prefix, $valid_statuses ) ) {
            return new WP_Error( 'esm_invalid_status', __( 'Invalid order status provided.', 'text-domain' ), array( 'status' => 400 ) );
        }

        $old_status = $order->get_status();
        $order->update_status( $new_status, __( 'Status changed via Store Manager Dashboard.', 'text-domain' ) );

        return new WP_REST_Response( esm_format_single_order_data( $order ), 200 );
    } catch ( Exception $e ) {
        error_log( 'ESM Order Status Update Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_order_status_error', __( 'Failed to update order status.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_add_order_note_handler( $request ) {
    try {
        $order_id = (int) $request['id'];
        $note_content = wp_kses_post( $request->get_param('note') );
        $is_customer_note = (bool) $request->get_param('is_customer_note');

        if ( empty( $note_content ) ) {
            return new WP_Error( 'esm_empty_note', __( 'Order note cannot be empty.', 'text-domain' ), array( 'status' => 400 ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error( 'esm_order_not_found', __( 'Order not found.', 'text-domain' ), array( 'status' => 404 ) );
        }

        $note_id = $order->add_order_note( $note_content, $is_customer_note );

        if ( ! $note_id ) {
            return new WP_Error( 'esm_note_creation_failed', __( 'Failed to add order note.', 'text-domain' ), array( 'status' => 500 ) );
        }

        $note = get_comment( $note_id );
        $note_data = array(
            'id' => $note->comment_ID,
            'date_created' => $note->comment_date,
            'note' => $note->comment_content,
            'customer_note' => $is_customer_note,
            'added_by' => $note->comment_author,
        );

        return new WP_REST_Response( $note_data, 201 );
    } catch ( Exception $e ) {
        error_log( 'ESM Add Order Note Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_order_note_error', __( 'Failed to add order note.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

// Report Management Functions
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
        default:
             $date_start = $now->clone()->modify('-6 days');
    }

    $date_start->setTime(0,0,0);
    $date_end->setTime(23,59,59);

    return array('date_start' => $date_start, 'date_end' => $date_end);
}

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
        ),
        'date_end' => array(
            'description' => __('End date for custom period (YYYY-MM-DD).', 'text-domain'),
            'type' => 'string',
            'format' => 'date',
            'sanitize_callback' => 'sanitize_text_field',
        ),
    );
}

function esm_get_sales_report_handler( WP_REST_Request $request ) {
    try {
        $date_params = esm_parse_date_period_params($request);
        if (is_wp_error($date_params)) {
            return $date_params;
        }
        $date_start_obj = $date_params['date_start'];
        $date_end_obj = $date_params['date_end'];

        $orders = wc_get_orders(array(
            'limit'        => -1,
            'status'       => array('wc-processing', 'wc-completed', 'wc-on-hold'),
            'date_created' => $date_start_obj->getTimestamp() . '...' . $date_end_obj->getTimestamp(),
        ));

        $total_sales = 0;
        $order_count = count($orders);
        $daily_sales = array();

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
    } catch ( Exception $e ) {
        error_log( 'ESM Sales Report Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_sales_report_error', __( 'Failed to generate sales report.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_get_bestsellers_report_handler( WP_REST_Request $request ) {
    try {
        $date_params = esm_parse_date_period_params($request);
        if (is_wp_error($date_params)) {
            return $date_params;
        }
        $limit = $request->get_param('limit');

        $orders = wc_get_orders(array(
            'limit'        => -1,
            'status'       => array('wc-processing', 'wc-completed'),
            'date_created' => $date_params['date_start']->getTimestamp() . '...' . $date_params['date_end']->getTimestamp(),
        ));

        $product_sales = array();
        foreach ($orders as $order) {
            if ($order instanceof WC_Order) {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $id_to_track = $item->get_variation_id() > 0 ? $item->get_variation_id() : $product_id;
                    if (!isset($product_sales[$id_to_track])) {
                        $product_sales[$id_to_track] = array(
                            'product_id' => $product_id,
                            'variation_id' => $item->get_variation_id(),
                            'name' => $item->get_name(),
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

        return new WP_REST_Response(array_values($bestsellers), 200);
    } catch ( Exception $e ) {
        error_log( 'ESM Bestsellers Report Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_bestsellers_report_error', __( 'Failed to generate bestsellers report.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

function esm_get_low_stock_report_handler( WP_REST_Request $request ) {
    try {
        $threshold = $request->get_param('threshold');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');

        $args = array(
            'status'       => 'publish',
            'type'         => array('simple', 'variation'),
            'manage_stock' => true,
            'stock_status' => 'instock',
            'limit'        => $per_page,
            'page'         => $page,
            'paginate'     => true,
            'meta_query'   => array(
                array(
                    'key'     => '_stock',
                    'value'   => $threshold,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ),
                 array(
                    'key'     => '_stock',
                    'value'   => '',
                    'compare' => '!=',
                ),
            ),
            'orderby' => array(
                'meta_value_num' => 'ASC',
                'ID'          => 'ASC',
            ),
            'meta_key' => '_stock',
        );

        $query = new WC_Product_Query($args);
        $products_query_result = $query->get_products();

        $low_stock_products = array();
        foreach ($products_query_result->products as $product_obj) {
            if ($product_obj instanceof WC_Product) {
                $stock_quantity = $product_obj->get_stock_quantity();
                if ($stock_quantity !== null && $stock_quantity <= $threshold) {
                     $low_stock_products[] = array(
                        'product_id'     => $product_obj->get_id(),
                        'name'           => $product_obj->get_formatted_name(),
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
    } catch ( Exception $e ) {
        error_log( 'ESM Low Stock Report Error: ' . $e->getMessage() );
        return new WP_Error( 'esm_low_stock_report_error', __( 'Failed to generate low stock report.', 'text-domain' ), array( 'status' => 500 ) );
    }
}

?>