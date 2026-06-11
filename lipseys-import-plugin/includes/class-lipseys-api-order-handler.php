<?php
/**
 * Submits WooCommerce paid orders to Lipsey's API (APIOrder) when enabled.
 * Products must have Lipsey's item number in SKU or meta _lipseys_item_no.
 *
 * @package Lipseys_Import
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_API_Order_Handler {

    public function __construct() {
        add_action('woocommerce_payment_complete', array($this, 'maybe_submit_to_lipseys'), 10, 2);
    }

    /**
     * If "submit orders" is enabled, build Lipsey's items from order and call APIOrder.
     *
     * @param int      $order_id
     * @param int|null $payment_method_id Unused.
     */
    public function maybe_submit_to_lipseys($order_id, $payment_method_id = null) {
        if (get_option('lipseys_api_submit_orders', 'no') !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order || !$order->is_paid()) {
            return;
        }

        $items = $this->build_lipseys_items($order);
        if (empty($items)) {
            return; // No Lipsey's items; skip (e.g. custom/other products)
        }

        $po_number = $order->get_order_number();
        if (empty($po_number)) {
            $po_number = 'WC-' . $order_id;
        }

        $response = Lipseys_API_Client::api_order($po_number, $items, false);

        if (!empty($response['errors'])) {
            $order->add_order_note(
                'Lipsey\'s API order submit failed: ' . implode(' ', $response['errors'])
            );
            return;
        }

        if (!empty($response['success'])) {
            $order->add_order_note(
                'Order submitted to Lipsey\'s API. PO: ' . $po_number . '. Response: ' . wp_json_encode($response['data'])
            );
        }
    }

    /**
     * Build array of { ItemNo, Quantity, Note } from order line items.
     * Uses product SKU or meta _lipseys_item_no as Lipsey's item number; skips lines without one.
     *
     * @param WC_Order $order
     * @return array
     */
    private function build_lipseys_items($order) {
        $out = array();
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }
            $item_no = $this->get_lipseys_item_no($product);
            if (empty($item_no)) {
                continue;
            }
            $qty = (int) $item->get_quantity();
            if ($qty < 1) {
                continue;
            }
            $out[] = array(
                'ItemNo'   => $item_no,
                'Quantity' => $qty,
                'Note'     => '',
            );
        }
        return $out;
    }

    /**
     * Get Lipsey's item number from product: meta _lipseys_item_no, or SKU if set.
     *
     * @param WC_Product $product
     * @return string
     */
    private function get_lipseys_item_no($product) {
        $meta = $product->get_meta('_lipseys_item_no');
        if (is_string($meta) && trim($meta) !== '') {
            return trim($meta);
        }
        $sku = $product->get_sku();
        if (is_string($sku) && trim($sku) !== '') {
            return trim($sku);
        }
        return '';
    }
}
