<?php
/**
 * REST API Report Endpoints.
 *
 * @package EasyStoreManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handler for sales report endpoint.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
 */
function esm_get_sales_report_handler( WP_REST_Request $request ) {
    $date_params = esm_parse_date_period_params($request); // Defined in api-helpers.php
    if (is_wp_error($date_params)) {
        return $date_params;
    }
    $date_start_obj = $date_params['date_start'];
    $date_end_obj = $date_params['date_end'];

    $orders_args = array(
        'limit'        => -1, // Get all orders in the range
        'status'       => array('wc-processing', 'wc-completed', 'wc-on-hold'), // Consider these as sales contributors
        'date_created' => $date_start_obj->getTimestamp() . '...' . $date_end_obj->getTimestamp(),
        'return'       => 'ids', // We only need IDs for counting and summing totals initially
    );
    $orders_ids = wc_get_orders($orders_args);

    $total_sales = 0;
    $order_count = count($orders_ids);
    $daily_sales = array();

    // Initialize daily sales for the period to 0 to ensure all days are represented
    $current_loop_date = $date_start_obj->clone();
    while($current_loop_date <= $date_end_obj) {
        $daily_sales[$current_loop_date->format('Y-m-d')] = 0.0;
        $current_loop_date->modify('+1 day');
    }

    if ($order_count > 0) {
        // Efficiently get totals if many orders (though wc_get_orders with 'ids' is already efficient)
        // For very high volume, direct DB query might be considered, but stick to WC functions for now.
        foreach ($orders_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order instanceof WC_Order) {
                $total_sales += $order->get_total();
                $order_date_str = $order->get_date_created()->format('Y-m-d'); // Use WC_DateTime object
                if (isset($daily_sales[$order_date_str])) {
                     $daily_sales[$order_date_str] += $order->get_total();
                }
            }
        }
    }

    $formatted_daily_sales = array();
    foreach ($daily_sales as $date => $total) {
        $formatted_daily_sales[] = array('date' => $date, 'total' => round($total, 2));
    }

    return new WP_REST_Response(array(
        'total_sales'      => round($total_sales, wc_get_price_decimals()),
        'order_count'      => $order_count,
        'period'           => array(
            'start' => $date_start_obj->format('Y-m-d'),
            'end'   => $date_end_obj->format('Y-m-d'),
        ),
        'daily_sales_data' => $formatted_daily_sales,
    ), 200);
}


/**
 * Handler for bestsellers report endpoint.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
 */
function esm_get_bestsellers_report_handler( WP_REST_Request $request ) {
    $date_params = esm_parse_date_period_params($request); // Defined in api-helpers.php
    if (is_wp_error($date_params)) {
        return $date_params;
    }
    $limit = $request->get_param('limit');

    // Get orders within the date range that are considered "paid" or "processing"
    $orders_args = array(
        'limit'        => -1,
        'status'       => array('wc-processing', 'wc-completed'),
        'date_created' => $date_params['date_start']->getTimestamp() . '...' . $date_params['date_end']->getTimestamp(),
    );
    $orders = wc_get_orders($orders_args);

    $product_sales = array();
    foreach ($orders as $order) {
        if ($order instanceof WC_Order) {
            foreach ($order->get_items() as $item_id => $item) { // $item is WC_Order_Item_Product
                if ( ! $item instanceof WC_Order_Item_Product ) {
                    continue;
                }
                $product_id = $item->get_product_id();
                // Use variation ID if it's a variation, otherwise product ID
                $id_to_track = $item->get_variation_id() > 0 ? $item->get_variation_id() : $product_id;

                if (!isset($product_sales[$id_to_track])) {
                    $product_sales[$id_to_track] = array(
                        'product_id'    => $product_id,
                        'variation_id'  => $item->get_variation_id(),
                        'name'          => $item->get_name(),
                        'quantity_sold' => 0,
                    );
                }
                $product_sales[$id_to_track]['quantity_sold'] += $item->get_quantity();
            }
        }
    }

    // Sort products by quantity sold in descending order
    uasort($product_sales, function($a, $b) {
        return $b['quantity_sold'] <=> $a['quantity_sold'];
    });

    $bestsellers = array_slice($product_sales, 0, $limit, true);

    // Values only, as keys are product/variation IDs which might not be needed if included in value
    return new WP_REST_Response(array_values($bestsellers), 200);
}


/**
 * Handler for low stock report endpoint.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
 */
function esm_get_low_stock_report_handler( WP_REST_Request $request ) {
    $threshold = $request->get_param('threshold'); // Already sanitized and validated by args
    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');

    $query_args = array(
        'status'       => 'publish', // Only published products
        'type'         => array('simple', 'variation'),
        'manage_stock' => true,     // Products that manage stock
        'stock_status' => 'instock',// Only products that are currently in stock but low
        'limit'        => $per_page,
        'page'         => $page,
        'paginate'     => true,     // Important for WC_Product_Query to return total/pages
        'meta_query'   => array(
            'relation' => 'AND',
            array(
                'key'     => '_stock',
                'value'   => $threshold,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ),
            array(
                'key'     => '_stock',
                'value'   => '',
                'compare' => '!=', // Ensure stock is not empty (which NUMERIC might treat as 0)
            ),
             array(
                'key'     => '_stock', // Ensure stock is not NULL
                'compare' => 'EXISTS',
            ),
        ),
        'orderby' => array(
            'meta_value_num' => 'ASC', // Order by stock quantity ascending
            'ID'             => 'ASC',
        ),
        'meta_key' => '_stock', // Necessary for ordering by _stock meta_value_num
    );

    $query = new WC_Product_Query($query_args);
    $products_query_result_obj = $query->get_products(); // This is the object with ->products, ->total, ->max_num_pages

    $low_stock_products = array();
    if (!empty($products_query_result_obj->products)) {
        foreach ($products_query_result_obj->products as $product_obj) {
            if ($product_obj instanceof WC_Product) {
                $stock_quantity = $product_obj->get_stock_quantity();
                // Double-check threshold because meta_query can be tricky with types sometimes
                if ($stock_quantity !== null && $stock_quantity <= $threshold) {
                     $low_stock_products[] = array(
                        'product_id'     => $product_obj->get_id(),
                        'name'           => $product_obj->get_formatted_name(),
                        'sku'            => $product_obj->get_sku(),
                        'stock_quantity' => $stock_quantity,
                        'permalink'      => $product_obj->get_permalink(),
                        'edit_link'      => get_edit_post_link($product_obj->get_id(), 'raw'), // Admin edit link
                    );
                }
            }
        }
    }

    $response = new WP_REST_Response( $low_stock_products, 200 );
    $response->header( 'X-WP-Total', $products_query_result_obj->total );
    $response->header( 'X-WP-TotalPages', $products_query_result_obj->max_num_pages );

    return $response;
}
?>
