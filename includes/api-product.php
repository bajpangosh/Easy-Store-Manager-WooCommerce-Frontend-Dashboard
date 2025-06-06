<?php
/**
 * REST API Product Endpoints.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Get the product schema, conforming to JSON Schema.
 * Used for validating create/update arguments.
 *
 * @param string $method HTTP method (WP_REST_Server::CREATABLE, WP_REST_Server::EDITABLE).
 * @return array
 */
function esm_get_product_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
    $params = array(
        'name' => array(
            'description'       => __( 'Product name.', 'easy-store-manager' ),
            'type'              => 'string',
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'sanitize_text_field',
            'required'          => ( $method === WP_REST_Server::CREATABLE ),
        ),
        'type' => array(
            'description'       => __( 'Product type.', 'easy-store-manager' ),
            'type'              => 'string',
            'default'           => 'simple',
            'enum'              => array_keys( wc_get_product_types() ),
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'sanitize_key',
        ),
        'status' => array(
            'description'       => __( 'Product status (post status).', 'easy-store-manager' ),
            'type'              => 'string',
            'default'           => 'publish',
            'enum'              => array_keys( get_post_statuses() ), // Consider wc_get_product_statuses() for product-specific ones
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'sanitize_key',
        ),
        'description' => array(
            'description'       => __( 'Product description.', 'easy-store-manager' ),
            'type'              => 'string',
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wp_kses_post',
        ),
        'short_description' => array(
            'description'       => __( 'Product short description.', 'easy-store-manager' ),
            'type'              => 'string',
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wp_kses_post',
        ),
        'sku' => array(
            'description'       => __( 'Unique identifier.', 'easy-store-manager' ),
            'type'              => 'string',
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wc_clean', // Use wc_clean for SKUs
        ),
        'regular_price' => array(
            'description'       => __( 'Product regular price.', 'easy-store-manager' ),
            'type'              => 'string', // Accepts string to handle various formats, WooCommerce will format it
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wc_clean',
        ),
        'sale_price' => array(
            'description'       => __( 'Product sale price.', 'easy-store-manager' ),
            'type'              => 'string',
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wc_clean',
        ),
        'manage_stock' => array(
            'description'       => __( 'Enable stock management at product level.', 'easy-store-manager' ),
            'type'              => 'boolean',
            'default'           => false,
            'context'           => array( 'view', 'edit' ),
        ),
        'stock_quantity' => array(
            'description'       => __( 'Stock quantity.', 'easy-store-manager' ),
            'type'              => 'integer',
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wc_stock_amount',
        ),
        'stock_status' => array(
            'description'       => __( 'Controls whether or not the product is listed as "in stock" or "out of stock".', 'easy-store-manager' ),
            'type'              => 'string',
            'default'           => 'instock',
            'enum'              => array( 'instock', 'outofstock', 'onbackorder' ),
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'sanitize_key',
        ),
        'image_id' => array(
            'description'       => __('Featured image ID.', 'easy-store-manager'),
            'type'              => 'integer',
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'absint',
        ),
        'categories' => array(
            'description'       => __( 'List of category IDs.', 'easy-store-manager' ),
            'type'              => 'array',
            'items'             => array( 'type' => 'integer' ),
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wp_parse_id_list',
        ),
        'tags' => array(
            'description'       => __( 'List of tag IDs.', 'easy-store-manager' ),
            'type'              => 'array',
            'items'             => array( 'type' => 'integer' ),
            'context'           => array( 'view', 'edit' ),
            'sanitize_callback' => 'wp_parse_id_list',
        ),
        // Add other fields like weight, dimensions, attributes, etc. as needed
    );
    return $params;
}


/**
 * Get product data for REST API response.
 *
 * @param WC_Product $product Product instance.
 * @return array
 */
function esm_get_product_data( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return array();
    }

    $data = array(
        'id'                    => $product->get_id(),
        'name'                  => $product->get_name(),
        'slug'                  => $product->get_slug(),
        'permalink'             => $product->get_permalink(),
        'date_created'          => wc_rest_prepare_date_response( $product->get_date_created(), false ),
        'date_created_gmt'      => wc_rest_prepare_date_response( $product->get_date_created() ),
        'date_modified'         => wc_rest_prepare_date_response( $product->get_date_modified(), false ),
        'date_modified_gmt'     => wc_rest_prepare_date_response( $product->get_date_modified() ),
        'type'                  => $product->get_type(),
        'status'                => $product->get_status(),
        'featured'              => $product->is_featured(),
        'catalog_visibility'    => $product->get_catalog_visibility(),
        'description'           => $product->get_description(), // raw
        'short_description'     => $product->get_short_description(), // raw
        'sku'                   => $product->get_sku(),
        'price'                 => $product->get_price(), // current price
        'regular_price'         => $product->get_regular_price(),
        'sale_price'            => $product->get_sale_price(),
        'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_from() ),
        'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $product->get_date_on_sale_to() ),
        'price_html'            => $product->get_price_html(),
        'on_sale'               => $product->is_on_sale(),
        'purchasable'           => $product->is_purchasable(),
        'total_sales'           => $product->get_total_sales(),
        'virtual'               => $product->is_virtual(),
        'downloadable'          => $product->is_downloadable(),
        'manage_stock'          => $product->managing_stock(),
        'stock_quantity'        => $product->get_stock_quantity(),
        'stock_status'          => $product->get_stock_status(),
        'backorders'            => $product->get_backorders(),
        'backorders_allowed'    => $product->backorders_allowed(),
        'weight'                => $product->get_weight(),
        'dimensions'            => array(
            'length' => $product->get_length(),
            'width'  => $product->get_width(),
            'height' => $product->get_height(),
        ),
        'image_id'              => $product->get_image_id(),
        'featured_image_url'    => get_the_post_thumbnail_url( $product->get_id(), 'medium_large' ), // Use a reasonable size
        'categories'            => wc_get_object_terms( $product->get_id(), 'product_cat', 'name' ), // Array of names
        'category_ids'          => $product->get_category_ids(), // Array of IDs
        'tags'                  => wc_get_object_terms( $product->get_id(), 'product_tag', 'name' ),   // Array of names
        'tag_ids'               => $product->get_tag_ids(),       // Array of IDs
    );
    return $data;
}

/**
 * Handler for getting products (listing & search).
 */
function esm_get_products_handler( WP_REST_Request $request ) {
    $params = $request->get_params(); // These are already validated/sanitized by 'args' in route registration

    $query_args = array(
        'status'   => $params['status'] ?? 'any',
        'limit'    => $params['per_page'],
        'page'     => $params['page'],
        'orderby'  => $params['orderby'],
        'order'    => strtoupper( $params['order'] ),
        's'        => $params['search'],
        'paginate' => true, // Required for wc_get_products to return total pages
    );

    if ( ! empty( $params['type'] ) ) {
        $query_args['type'] = $params['type'];
    }
    // Add more specific filters if needed, e.g., category, tag, featured

    $products_query = wc_get_products( $query_args );
    $products_data = array();

    foreach ( $products_query->products as $product_obj ) {
        if ( $product_obj instanceof WC_Product ) {
            $products_data[] = esm_get_product_data( $product_obj );
        }
    }

    $response = new WP_REST_Response( $products_data, 200 );
    $response->header( 'X-WP-Total', $products_query->total );
    $response->header( 'X-WP-TotalPages', $products_query->max_num_pages );

    return $response;
}

/**
 * Handler for getting a single product.
 */
function esm_get_product_handler( WP_REST_Request $request ) {
    $product_id = absint( $request['id'] );
    $product = wc_get_product( $product_id );

    if ( ! $product || 0 === $product->get_id() || $product->get_status() === 'trash' ) {
        return new WP_Error( 'esm_product_not_found', __( 'Product not found.', 'easy-store-manager' ), array( 'status' => 404 ) );
    }

    return new WP_REST_Response( esm_get_product_data( $product ), 200 );
}


/**
 * Helper function to set product properties from request parameters.
 *
 * @param WC_Product $product  The product object.
 * @param array      $params   The request parameters.
 * @return WC_Product The modified product object.
 */
function esm_set_product_properties( WC_Product $product, array $params ) {
    if ( isset( $params['name'] ) ) $product->set_name( sanitize_text_field( $params['name'] ) );
    if ( isset( $params['status'] ) ) $product->set_status( sanitize_key( $params['status'] ) );
    if ( isset( $params['description'] ) ) $product->set_description( wp_kses_post( $params['description'] ) );
    if ( isset( $params['short_description'] ) ) $product->set_short_description( wp_kses_post( $params['short_description'] ) );
    if ( isset( $params['sku'] ) ) $product->set_sku( wc_clean( $params['sku'] ) );
    if ( isset( $params['regular_price'] ) ) $product->set_regular_price( wc_format_decimal( $params['regular_price'] ) );
    if ( isset( $params['sale_price'] ) ) $product->set_sale_price( wc_format_decimal( $params['sale_price'] ) );

    if ( isset( $params['manage_stock'] ) ) $product->set_manage_stock( wc_string_to_bool( $params['manage_stock'] ) );
    if ( $product->get_manage_stock() && isset( $params['stock_quantity'] ) ) { // Only set quantity if manage_stock is true
        $product->set_stock_quantity( wc_stock_amount( $params['stock_quantity'] ) );
    }
    if ( isset( $params['stock_status'] ) ) $product->set_stock_status( sanitize_key( $params['stock_status'] ) );

    if ( isset( $params['categories'] ) ) $product->set_category_ids( wp_parse_id_list( $params['categories'] ) );
    if ( isset( $params['tags'] ) ) $product->set_tag_ids( wp_parse_id_list( $params['tags'] ) );
    if ( isset( $params['image_id'] ) ) $product->set_image_id( absint( $params['image_id'] ) );

    // Handle product type change (more complex, usually not done on simple updates)
    // if ( isset( $params['type'] ) && $params['type'] !== $product->get_type() ) { /* ... logic to change type ... */ }

    return $product;
}


/**
 * Handler for creating a product.
 */
function esm_create_product_handler( WP_REST_Request $request ) {
    $params = $request->get_params();

    if ( empty( $params['name'] ) ) {
        return new WP_Error( 'esm_product_missing_name', __( 'Product name is required.', 'easy-store-manager' ), array( 'status' => 400 ) );
    }

    $product_type = ! empty( $params['type'] ) ? sanitize_key( $params['type'] ) : 'simple';

    // Get the product class name. Default to WC_Product_Simple if class does not exist.
    $classname = WC_Product_Factory::get_product_classname( 0, $product_type );
    if ( ! class_exists( $classname ) ) {
        $classname = 'WC_Product_Simple';
    }
    $product = new $classname();

    if ( ! $product instanceof WC_Product ) {
        return new WP_Error( 'esm_product_invalid_type', __( 'Invalid product type specified.', 'easy-store-manager' ), array( 'status' => 400 ) );
    }

    $product = esm_set_product_properties( $product, $params );

    try {
        $product_id = $product->save();
    } catch ( WC_Data_Exception $e ) {
        return new WP_Error( 'esm_create_product_error', $e->getMessage(), array( 'status' => $e->getCode() ?: 500 ) );
    }

    if ( ! $product_id ) {
        return new WP_Error( 'esm_create_product_error_unknown', __( 'Could not create product (unknown error).', 'easy-store-manager' ), array( 'status' => 500 ) );
    }

    $product = wc_get_product( $product_id ); // Re-fetch to get all data formatted
    if ( ! $product ) {
         return new WP_Error( 'esm_create_product_error_after_save', __( 'Could not retrieve product after creation.', 'easy-store-manager' ), array( 'status' => 500 ) );
    }

    $response_data = esm_get_product_data( $product );
    $response = new WP_REST_Response( $response_data, 201 ); // 201 Created
    $response->header( 'Location', rest_url( sprintf( '%s/products/%d', 'esm/v1', $product_id ) ) );
    return $response;
}


/**
 * Handler for updating a product.
 */
function esm_update_product_handler( WP_REST_Request $request ) {
    $product_id = absint( $request['id'] );
    $params = $request->get_params();

    $product = wc_get_product( $product_id );

    if ( ! $product || 0 === $product->get_id() ) {
        return new WP_Error( 'esm_product_not_found_for_update', __( 'Product not found for update.', 'easy-store-manager' ), array( 'status' => 404 ) );
    }

    $product = esm_set_product_properties( $product, $params );

    try {
        $result = $product->save();
    } catch ( WC_Data_Exception $e ) {
        return new WP_Error( 'esm_update_product_error', $e->getMessage(), array( 'status' => $e->getCode() ?: 500 ) );
    }

    if ( ! $result ) {
        return new WP_Error( 'esm_update_product_error_unknown', __( 'Could not update product (unknown error).', 'easy-store-manager' ), array( 'status' => 500 ) );
    }

    $product = wc_get_product( $product_id ); // Re-fetch
    if ( ! $product ) {
         return new WP_Error( 'esm_update_product_error_after_save', __( 'Could not retrieve product after update.', 'easy-store-manager' ), array( 'status' => 500 ) );
    }

    return new WP_REST_Response( esm_get_product_data( $product ), 200 );
}

/**
 * Handler for deleting a product.
 */
function esm_delete_product_handler( WP_REST_Request $request ) {
    $product_id = absint( $request['id'] );
    $force_delete = isset( $request['force'] ) ? (bool) $request['force'] : false;

    $product = wc_get_product( $product_id );

    if ( ! $product || 0 === $product->get_id() ) { // Product might be already deleted or never existed
        return new WP_Error( 'esm_product_not_found_for_delete', __( 'Product not found for deletion.', 'easy-store-manager' ), array( 'status' => 404 ) );
    }

    $previous_data = esm_get_product_data( $product ); // Get data before deleting

    $result = $product->delete( $force_delete );

    if ( ! $result ) {
        return new WP_Error( 'esm_delete_product_error', __( 'Could not delete product.', 'easy-store-manager' ), array( 'status' => 500 ) );
    }

    $response_data = array(
        'deleted'  => true,
        'previous' => $previous_data,
    );
    return new WP_REST_Response( $response_data, 200 );
}


/**
 * Handler for bulk updating products.
 */
function esm_bulk_update_products_handler( WP_REST_Request $request ) {
    $params = $request->get_json_params();

    if ( empty( $params ) || ! is_array( $params ) ) {
        return new WP_Error( 'esm_bulk_update_invalid_payload', __( 'Invalid payload. Expecting an array of update objects.', 'easy-store-manager' ), array( 'status' => 400 ) );
    }

    $results = array();
    $has_errors = false;

    foreach ( $params as $update_item ) {
        if ( empty( $update_item['id'] ) ) {
            $results[] = array( 'id' => null, 'status' => 'error', 'message' => __( 'Missing product ID in an item.', 'easy-store-manager' ) );
            $has_errors = true;
            continue;
        }

        $product_id = absint( $update_item['id'] );
        $product = wc_get_product( $product_id );

        if ( ! $product || 0 === $product->get_id() ) {
            $results[] = array( 'id' => $product_id, 'status' => 'error', 'message' => __( 'Product not found.', 'easy-store-manager' ) );
            $has_errors = true;
            continue;
        }

        // Whitelist fields that can be updated in bulk for security and simplicity
        $allowed_bulk_fields = array('regular_price', 'sale_price', 'stock_status', 'status', 'manage_stock', 'stock_quantity');
        $product_update_data = array_intersect_key( $update_item, array_flip($allowed_bulk_fields) );

        if (empty($product_update_data)) {
            $results[] = array('id' => $product_id, 'status' => 'skipped', 'message' => __( 'No valid update actions provided for this item.', 'easy-store-manager' ));
            continue;
        }

        $product = esm_set_product_properties( $product, $product_update_data );

        try {
            $save_result = $product->save();
            if ( ! $save_result ) { throw new Exception(__( 'Unknown error saving product.', 'easy-store-manager' )); }
            $results[] = array( 'id' => $product_id, 'status' => 'success', 'message' => __( 'Product updated.', 'easy-store-manager' ) );
        } catch ( Exception $e ) {
            $results[] = array( 'id' => $product_id, 'status' => 'error', 'message' => $e->getMessage() );
            $has_errors = true;
        }
    }

    $status_code = $has_errors ? 207 : 200; // 207 Multi-Status if there are errors
    return new WP_REST_Response( $results, $status_code );
}

?>
