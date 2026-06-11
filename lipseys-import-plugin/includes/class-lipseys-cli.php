<?php
/**
 * WP-CLI commands for Lipsey's Import (e.g. attach images from command line).
 * Loaded only when WP_CLI is defined (see main plugin file).
 */

if (!class_exists('WP_CLI')) {
    return;
}

class Lipseys_CLI {

    /**
     * Attach images to products that have _lipseys_image_name but no thumbnail.
     *
     * ## OPTIONS
     *
     * [--batch=<n>]
     * : Number of products to process per run. Default 10.
     *
     * [--batches=<n>]
     * : Max number of batches to run (then stop). Default 0 = run until remaining is 0.
     *
     * [--base-url=<url>]
     * : Override image base URL (default: https://www.lipseyscloud.com/images/).
     *
     * ## EXAMPLES
     *
     *     wp lipseys attach-images
     *     wp lipseys attach-images --batch=20
     *     wp lipseys attach-images --batch=20 --batches=50
     *
     * @param array $args       Positional args.
     * @param array $assoc_args Optional args.
     */
    public function attach_images($args, $assoc_args) {
        $batch_size = isset($assoc_args['batch']) ? max(1, min(500, (int) $assoc_args['batch'])) : 10;
        $max_batches = isset($assoc_args['batches']) ? max(0, (int) $assoc_args['batches']) : 0;
        if (isset($assoc_args['base-url'])) {
            Lipseys_Image_Handler::set_image_base_url($assoc_args['base-url']);
        }

        global $wpdb;
        $total_attached = 0;
        $batches_run = 0;

        while (true) {
            $ids = $wpdb->get_col(
                "SELECT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_image_name' AND pm.meta_value != ''
                LEFT JOIN {$wpdb->postmeta} th ON p.ID = th.post_id AND th.meta_key = '_thumbnail_id'
                WHERE p.post_type = 'product' AND p.post_status = 'publish'
                AND (th.meta_value IS NULL OR th.meta_value = '' OR th.meta_value = '0')
                ORDER BY p.ID ASC
                LIMIT " . (int) $batch_size
            );

            if (empty($ids)) {
                break;
            }

            $attached = 0;
            foreach ($ids as $product_id) {
                $product_id = (int) $product_id;
                $image_name = get_post_meta($product_id, '_lipseys_image_name', true);
                if (empty($image_name)) {
                    continue;
                }
                $sku = get_post_meta($product_id, '_sku', true);
                $result = Lipseys_Image_Handler::attach_image_to_product($product_id, $image_name, $sku);
                if ($result) {
                    $attached++;
                }
            }

            $total_attached += $attached;
            $batches_run++;

            $remaining = $wpdb->get_var(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_image_name' AND pm.meta_value != ''
                LEFT JOIN {$wpdb->postmeta} th ON p.ID = th.post_id AND th.meta_key = '_thumbnail_id'
                WHERE p.post_type = 'product' AND p.post_status = 'publish'
                AND (th.meta_value IS NULL OR th.meta_value = '' OR th.meta_value = '0')"
            );
            $remaining = (int) $remaining;

            WP_CLI::log(sprintf('Batch %d: attached %d, remaining %d', $batches_run, $attached, $remaining));

            if ($remaining <= 0) {
                WP_CLI::success(sprintf('Done. Total attached: %d.', $total_attached));
                return;
            }

            if ($max_batches > 0 && $batches_run >= $max_batches) {
                WP_CLI::success(sprintf('Stopped after %d batches. Attached %d; %d remaining. Run again to continue.', $batches_run, $total_attached, $remaining));
                return;
            }
        }

        WP_CLI::success(sprintf('Done. Total attached: %d.', $total_attached));
    }
}

WP_CLI::add_command('lipseys', 'Lipseys_CLI');
