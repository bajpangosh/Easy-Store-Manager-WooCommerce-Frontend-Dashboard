<?php
/**
 * REST API Order Endpoints.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Get the query params for orders collection.
 * Reuses common collection params and adds order-specific ones.
 * @return array
 */
function esm_get_orders_collection_params() {
    $params = esm_get_collection_params(); // From api-helpers.php

    $params['status'] = array(
        'description'       => __( 'Limit result set to orders with specific statuses.', 'easy-store-manager' ),
        'type'              => 'array',
        'items'             => array('type' => 'string'),
        'sanitize_callback' => 'wc_string_to_array', // Converts comma-separated or space-separated string to array
        'validate_callback' => 'rest_validate_request_arg', // Further validation can be done in handler
    );
    $params['customer'] = array( // Customer ID
        'description'       => __( 'Limit result set to orders by a specific customer ID.', 'easy-store-manager' ),
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'validate_callback' => 'rest_validate_request_arg',
    );
    $params['date_before'] = array(
        'description'       => __( 'Limit result set to orders made before a specific date (YYYY-MM-DD).', 'easy-store-manager' ),
        'type'              => 'string',
        'format'            => 'date-time',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => 'wc_rest_validate_date_arg',
    );
    $params['date_after'] = array(
        'description'       => __( 'Limit result set to orders made after a specific date (YYYY-MM-DD).', 'easy-store-manager' ),
        'type'              => 'string',
        'format'            => 'date-time',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => 'wc_rest_validate_date_arg',
    );
    $params['orderby']['enum'] = array( 'date', 'id', 'include', 'title', 'modified' ); // WooCommerce default orderby options for orders

    return $params;
}

/**
 * Format basic order data for list response.
 * @param WC_Order $order
 * @return array
 */
function esm_format_order_data_for_list( WC_Order $order ) {
    return array(
        'id'               => $order->get_id(),
        'order_number'     => $order->get_order_number(),
        'status'           => $order->get_status(), // without 'wc-' prefix
        'date_created'     => wc_rest_prepare_date_response( $order->get_date_created() ),
        'total'            => $order->get_total(),
        'currency'         => $order->get_currency(),
        'customer_id'      => $order->get_customer_id(),
        'customer_name'    => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
        'billing_email'    => $order->get_billing_email(),
    );
}

/**
 * Format detailed order data for single response.
 * @param WC_Order $order
 * @return array
 */
function esm_format_single_order_data( WC_Order $order ) {
    $line_items = array();
    foreach ( $order->get_items() as $item_id => $item ) {
        $product = $item->get_product();
        $line_items[] = array(
            'id'           => $item_id,
            'product_id'   => $item->get_product_id(),
            'variation_id' => $item->get_variation_id(),
            'name'         => $item->get_name(),
            'quantity'     => $item->get_quantity(),
            'sku'          => $product ? $product->get_sku() : null,
            'subtotal'     => $order->get_line_subtotal( $item, true, true ), // formatted
            'total'        => $order->get_line_total( $item, true, true ),   // formatted
        );
    }

    $order_notes_data = array();
    $notes = wc_get_order_notes( array('order_id' => $order->get_id(), 'orderby' => 'date_created', 'order' => 'DESC') );
    foreach ($notes as $note) {
        $order_notes_data[] = array(
            'id'               => $note->id,
            'content'          => $note->content, // raw, may contain HTML
            'date_created_gmt' => wc_rest_prepare_date_response( $note->date_created ), // WC_DateTime object
            'is_customer_note' => (bool) $note->customer_note,
            'added_by'         => $note->added_by,
        );
    }

    $data = esm_format_order_data_for_list( $order ); // Start with basic data
    $data['date_modified_gmt'] = wc_rest_prepare_date_response( $order->get_date_modified() );
    $data['billing_address']  = $order->get_address('billing'); // Array of address fields
    $data['shipping_address'] = $order->get_address('shipping'); // Array of address fields
    $data['formatted_billing_address'] = $order->get_formatted_billing_address() ?: null;
    $data['formatted_shipping_address'] = $order->get_formatted_shipping_address() ?: null;
    $data['payment_method_title'] = $order->get_payment_method_title();
    $data['shipping_method_title'] = $order->get_shipping_method();
    $data['line_items']       = $line_items;
    $data['order_notes']      = $order_notes_data;
    $data['customer_note']    => $order->get_customer_note(), // raw
    // Add more fields as needed: currency_symbol, customer_ip_address, etc.
    );
    return $data;
}


/**
 * Handler for getting orders.
 */
function esm_get_orders_handler( WP_REST_Request $request ) {
    $params = $request->get_params();
    $query_args = array(
        'paginate' => true,
        'limit'    => $params['per_page'],
        'paged'    => $params['page'],
        'orderby'  => $params['orderby'],
        'order'    => strtoupper( $params['order'] ),
    );

    if ( ! empty( $params['status'] ) ) {
        // Ensure statuses are prefixed with 'wc-' if not already
        $statuses = is_array($params['status']) ? $params['status'] : array_map('trim', explode(',', $params['status']));
        $query_args['status'] = array_map(function($status) {
            return str_starts_with($status, 'wc-') ? $status : 'wc-' . $status;
        }, $statuses);
    }
    if ( ! empty( $params['search'] ) ) {
        $query_args['s'] = wc_clean( $params['search'] ); // WC_Order_Query handles search by ID, email, name, etc.
    }
    if ( ! empty( $params['customer'] ) ) {
        $query_args['customer_id'] = absint( $params['customer'] );
    }
    if ( ! empty( $params['date_before'] ) ) $query_args['date_before'] = wc_clean( $params['date_before'] );
    if ( ! empty( $params['date_after'] ) ) $query_args['date_after'] = wc_clean( $params['date_after'] );

    $query = new WC_Order_Query( $query_args );
    $orders_query_result = $query->get_orders();

    $orders_data = array_map( 'esm_format_order_data_for_list', $orders_query_result );

    $response = new WP_REST_Response( $orders_data, 200 );
    $response->header( 'X-WP-Total', $query->get_total() );
    $response->header( 'X-WP-TotalPages', $query->get_max_num_pages() );

    return $response;
}

/**
 * Handler for getting a single order.
 */
function esm_get_order_handler( WP_REST_Request $request ) {
    $order_id = absint( $request['id'] );
    $order = wc_get_order( $order_id );

    if ( ! $order || 0 === $order->get_id() ) {
        return new WP_Error( 'esm_order_not_found', __( 'Order not found.', 'easy-store-manager' ), array( 'status' => 404 ) );
    }
    return new WP_REST_Response( esm_format_single_order_data( $order ), 200 );
}

/**
 * Handler for updating order status.
 */
function esm_update_order_status_handler( WP_REST_Request $request ) {
    $order_id = absint( $request['id'] );
    $new_status_slug = wc_clean( $request['status'] ); // e.g., "processing", "completed"

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return new WP_Error( 'esm_order_not_found_for_status_update', __( 'Order not found.', 'easy-store-manager' ), array( 'status' => 404 ) );
    }

    // Prepend 'wc-' if not already present, as update_status expects 'wc-slug'
    $new_status_wc = str_starts_with($new_status_slug, 'wc-') ? $new_status_slug : 'wc-' . $new_status_slug;

    if ( ! array_key_exists( $new_status_wc, wc_get_order_statuses() ) ) {
        return new WP_Error( 'esm_invalid_order_status', __( 'Invalid order status provided.', 'easy-store-manager' ), array( 'status' => 400, 'available_statuses' => array_keys(wc_get_order_statuses()) ) );
    }

    try {
        // The third param true makes it a manual status change and triggers emails
        $order->update_status( $new_status_slug, __( 'Order status updated by Store Manager via API.', 'easy-store-manager' ), true );
    } catch ( Exception $e ) {
        return new WP_Error( 'esm_order_status_update_failed', $e->getMessage(), array( 'status' => 500 ) );
    }

    $updated_order = wc_get_order( $order_id ); // Re-fetch to get the latest data
    return new WP_REST_Response( esm_format_single_order_data( $updated_order ), 200 );
}

/**
 * Handler for adding an order note.
 */
function esm_add_order_note_handler( WP_REST_Request $request ) {
    $order_id = absint( $request['id'] );
    $note_content = wp_kses_post( $request['note'] );
    $is_customer_note = (bool) $request['is_customer_note'];

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return new WP_Error( 'esm_order_not_found_for_note', __( 'Order not found.', 'easy-store-manager' ), array( 'status' => 404 ) );
    }

    if ( empty( $note_content ) ) {
        return new WP_Error( 'esm_order_note_empty', __( 'Order note cannot be empty.', 'easy-store-manager' ), array( 'status' => 400 ) );
    }

    // The third param `true` makes it a manually added note (not a system status change note)
    $note_id = $order->add_order_note( $note_content, $is_customer_note ? 1 : 0, true );

    if ( ! $note_id ) {
        return new WP_Error( 'esm_add_order_note_failed', __( 'Could not add order note.', 'easy-store-manager' ), array( 'status' => 500 ) );
    }

    $note_comment = get_comment( $note_id );
    if ( ! $note_comment ) {
         return new WP_Error( 'esm_get_order_note_failed', __( 'Could not retrieve created order note.', 'easy-store-manager' ), array( 'status' => 500 ) );
    }

    $current_user = wp_get_current_user();
    $added_by = $current_user->exists() ? $current_user->display_name : __('System', 'easy-store-manager');


    $formatted_note = array(
        'id'               => $note_comment->comment_ID,
        'content'          => $note_comment->comment_content,
        'date_created_gmt' => wc_rest_prepare_date_response( $note_comment->comment_date_gmt ),
        'is_customer_note' => $is_customer_note,
        'added_by'         => $added_by,
    );

    return new WP_REST_Response( $formatted_note, 201 ); // 201 Created
}

?>
