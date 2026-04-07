<?php
/**
 * Admin Interface for Lipsey's Import
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_Import_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_lipseys_import_upload', array($this, 'handle_file_upload'));
        add_action('wp_ajax_lipseys_import_preview', array($this, 'handle_preview'));
        add_action('wp_ajax_lipseys_import_process', array($this, 'handle_import'));
        add_action('wp_ajax_lipseys_test_image', array($this, 'test_image_url'));
        // API import handlers
        add_action('wp_ajax_lipseys_api_fetch_catalog', array($this, 'handle_api_fetch_catalog'));
        add_action('wp_ajax_lipseys_api_fetch_catalog_details_batch', array($this, 'handle_api_fetch_catalog_details_batch'));
        add_action('wp_ajax_lipseys_api_test_details_connection', array($this, 'handle_api_test_details_connection'));
        add_action('wp_ajax_lipseys_api_import_start', array($this, 'handle_api_import'));
        add_action('wp_ajax_lipseys_api_update_pricing', array($this, 'handle_api_update_pricing'));
        add_action('wp_ajax_lipseys_attach_images', array($this, 'handle_attach_images'));
        add_action('wp_ajax_lipseys_attach_images_reset_failed', array($this, 'handle_attach_images_reset_failed'));
        add_action('wp_ajax_lipseys_remove_zanders_products', array($this, 'handle_remove_zanders_products'));
        add_action('wp_ajax_lipseys_recategorize_by_type', array($this, 'handle_recategorize_by_type'));
        add_action('wp_ajax_lipseys_sync_accessory_categories', array($this, 'handle_sync_accessory_categories'));
        add_action('wp_ajax_lipseys_sync_by_product_name', array($this, 'handle_sync_by_product_name'));
        add_action('wp_ajax_lipseys_backfill_type', array($this, 'handle_backfill_type'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Lipsey\'s Import',
            'Lipsey\'s Import',
            'manage_woocommerce',
            'lipseys-import',
            array($this, 'render_admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'woocommerce_page_lipseys-import') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_style('lipseys-import-admin', LIPSEYS_IMPORT_PLUGIN_URL . 'assets/admin.css', array(), LIPSEYS_IMPORT_VERSION);
        wp_enqueue_script('lipseys-import-admin', LIPSEYS_IMPORT_PLUGIN_URL . 'assets/admin.js', array('jquery'), LIPSEYS_IMPORT_VERSION, true);
        
        wp_localize_script('lipseys-import-admin', 'lipseysImport', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lipseys_import_nonce')
        ));
    }
    
    public function render_admin_page() {
        global $wpdb;
        // Initialize category structure
        if (isset($_POST['init_categories'])) {
            Lipseys_Category_Mapper::init_category_structure();
            echo '<div class="notice notice-success"><p>Category structure initialized!</p></div>';
        }

        // Counts for Backfill / Recategorize (shown on both tabs)
        $recat_count = (int) $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_type' AND pm.meta_value != '' WHERE p.post_type = 'product' AND p.post_status = 'publish'");
        $missing_type_count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku' AND pm_sku.meta_value != ''
            LEFT JOIN {$wpdb->postmeta} pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = '_lipseys_type'
            WHERE p.post_type = 'product' AND p.post_status = 'publish' AND (pm_type.meta_value IS NULL OR pm_type.meta_value = '')"
        );
        
        $api_url = add_query_arg('page', 'lipseys-api', admin_url('admin.php'));
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'csv';
        ?>
        <div class="wrap">
            <h1>Lipsey's Product Import</h1>
            <p><strong>Enter Lipsey's credentials and turn on order submit:</strong> <a href="<?php echo esc_url($api_url); ?>">Configure Lipsey's API</a> — that page has the email, password, Test connection, and the &quot;Submit paid orders to Lipsey's API&quot; checkbox.</p>
            
            <!-- Tabs -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=lipseys-import&tab=csv" class="nav-tab <?php echo $active_tab === 'csv' ? 'nav-tab-active' : ''; ?>">CSV Import</a>
                <a href="?page=lipseys-import&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">API Import</a>
            </h2>
            
            <div class="lipseys-import-container">
            
            <?php if ($active_tab === 'csv'): ?>
                <!-- CSV Import Tab -->
                <!-- Configuration Section -->
                <div class="postbox">
                    <h2 class="hndle">Configuration</h2>
                    <div class="inside">
                        <form method="post" action="">
                            <table class="form-table">
                                <tr>
                                    <th><label>Image Base URL</label></th>
                                    <td>
                                        <input type="text" id="image_base_url" class="regular-text" 
                                               value="<?php echo esc_attr(Lipseys_Image_Handler::get_image_base_url()); ?>" 
                                               placeholder="https://www.lipseyscloud.com/images/">
                                        <p class="description">Base URL for Lipsey's product images (default: Lipsey's cloud). Change only if your catalog uses a different host.</p>
                                        <button type="button" id="test-image-url" class="button">Test Image URL</button>
                                        <span id="image-test-result"></span>
                                    </td>
                                </tr>
                            </table>
                            <p>
                                <button type="submit" name="init_categories" class="button button-secondary">
                                    Initialize Category Structure
                                </button>
                                <span class="description">Creates default WooCommerce categories (Firearms, Accessories, etc.)</span>
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Import Section -->
                <div class="postbox">
                    <h2 class="hndle">Import Products</h2>
                    <div class="inside">
                        <form id="lipseys-import-form" enctype="multipart/form-data">
                            <table class="form-table">
                                <tr>
                                    <th><label for="csv_file">CSV File</label></th>
                                    <td>
                                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                                        <p class="description">Upload your Lipsey's catalog CSV file</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="batch_size">Batch Size</label></th>
                                    <td>
                                        <input type="number" id="batch_size" name="batch_size" value="5" min="1" max="500">
                                        <p class="description">Number of products per batch. Use 5 if you get 502; use &quot;Resume from&quot; below to continue after a timeout.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="csv_start_offset">Resume from row</label></th>
                                    <td>
                                        <input type="number" id="csv_start_offset" name="csv_start_offset" value="0" min="0" step="1">
                                        <p class="description">After a 502, set this to the last &quot;Processed&quot; number (e.g. 40) and click Start Import again to continue.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="update_existing">Update Existing Products</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="update_existing" name="update_existing" checked>
                                            Update existing products (by SKU) if they already exist
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="csv_skip_images">Skip images</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="csv_skip_images" name="csv_skip_images" value="1">
                                            Skip image download (faster import; add images later to avoid 502)
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="button" id="preview-btn" class="button button-secondary">Preview CSV</button>
                                <button type="button" id="import-btn" class="button button-primary">Start Import</button>
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Preview Section -->
                <div id="preview-section" class="postbox" style="display: none;">
                    <h2 class="hndle">CSV Preview</h2>
                    <div class="inside">
                        <div id="preview-content"></div>
                    </div>
                </div>
                
                <!-- Progress Section -->
                <div id="progress-section" class="postbox" style="display: none;">
                    <h2 class="hndle">Import Progress</h2>
                    <div class="inside">
                        <div id="progress-bar-container">
                            <div id="progress-bar" style="width: 0%; background: #2271b1; height: 30px; line-height: 30px; text-align: center; color: white;">
                                0%
                            </div>
                        </div>
                        <div id="progress-stats" style="margin-top: 15px;">
                            <p><strong>Status:</strong> <span id="progress-status">Ready</span></p>
                            <p><strong>Processed:</strong> <span id="processed-count">0</span> / <span id="total-count">0</span></p>
                            <p><strong>Created:</strong> <span id="created-count">0</span></p>
                            <p><strong>Updated:</strong> <span id="updated-count">0</span></p>
                            <p><strong>Errors:</strong> <span id="error-count">0</span></p>
                        </div>
                        <div id="error-log" style="margin-top: 15px; max-height: 200px; overflow-y: auto; display: none;">
                            <h4>Error Log:</h4>
                            <ul id="error-list"></ul>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'api'): ?>
                <!-- API Import Tab -->
                
                <!-- Configuration Section -->
                <div class="postbox">
                    <h2 class="hndle">Configuration</h2>
                    <div class="inside">
                        <form method="post" action="">
                            <table class="form-table">
                                <tr>
                                    <th><label>API Status</label></th>
                                    <td>
                                        <?php
                                        $creds = Lipseys_API_Client::get_credentials();
                                        if ($creds): ?>
                                            <span style="color: green;">✓ API credentials configured</span>
                                            <p class="description">Email: <?php echo esc_html($creds['email']); ?></p>
                                        <?php else: ?>
                                            <span style="color: red;">✗ API credentials not configured</span>
                                            <p class="description"><a href="<?php echo esc_url($api_url); ?>">Configure API credentials</a></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Proxy</label></th>
                                    <td>
                                        <?php
                                        $proxy_url = get_option('lipseys_api_proxy_url', '');
                                        if (!empty($proxy_url)): ?>
                                            <span style="color: green;">✓ Proxy configured</span>
                                            <p class="description">All API requests go through the proxy. <a href="<?php echo esc_url($api_url); ?>">Edit Proxy URL / secret</a></p>
                                        <?php else: ?>
                                            <span style="color: orange;">⚠ Proxy not set</span>
                                            <p class="description">Fetch catalog will fail on EasyWP without a proxy. <a href="<?php echo esc_url($api_url); ?>">Set Proxy URL and Proxy secret in Lipsey&apos;s API</a> (e.g. your lipseys-proxy.php URL and secret).</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Image Base URL</label></th>
                                    <td>
                                        <input type="text" id="api_image_base_url" class="regular-text" 
                                               value="<?php echo esc_attr(Lipseys_Image_Handler::get_image_base_url()); ?>" 
                                               placeholder="https://www.lipseyscloud.com/images/">
                                        <p class="description">Base URL for Lipsey's product images</p>
                                    </td>
                                </tr>
                            </table>
                            <p>
                                <button type="submit" name="init_categories" class="button button-secondary">
                                    Initialize Category Structure
                                </button>
                                <span class="description">Creates default WooCommerce categories (Firearms, Accessories, etc.)</span>
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Import from API Section -->
                <div class="postbox">
                    <h2 class="hndle">Import Products from API</h2>
                    <div class="inside">
                        <p><strong>Note:</strong> The API Catalog Feed updates every 4 hours. This will import all products directly from Lipsey's API.</p>
                        <form id="lipseys-api-import-form">
                            <table class="form-table">
                                <tr>
                                    <th><label for="api_batch_size">Batch Size</label></th>
                                    <td>
                                        <input type="number" id="api_batch_size" name="batch_size" value="25" min="1" max="500">
                                        <p class="description">Number of products per batch. Use 10–25 to avoid 502 timeout on first run; increase after it works.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="api_skip_images">Skip image download</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="api_skip_images" name="api_skip_images" value="1" checked>
                                            <strong>Skip image download during import</strong> (recommended — avoids 502). Use &quot;Attach images&quot; at the bottom after import.
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Filters</label></th>
                                    <td>
                                        <p>
                                            <label for="api_filter_manufacturer">Manufacturer:</label><br>
                                            <input type="text" id="api_filter_manufacturer" name="filter_manufacturer" class="regular-text" placeholder="e.g., Glock, Ruger">
                                        </p>
                                        <p>
                                            <label for="api_filter_type">Product Type:</label><br>
                                            <input type="text" id="api_filter_type" name="filter_type" class="regular-text" placeholder="e.g., Semi-Auto Pistols, Rifles">
                                        </p>
                                        <p>
                                            <label>
                                                <input type="checkbox" id="api_filter_in_stock" name="filter_in_stock_only" checked>
                                                In-stock products only
                                            </label>
                                            <span class="description" style="display: block; margin-top: 4px;">Uncheck to import <strong>full catalog</strong> (e.g. 7000+ products); when checked, only items with current stock are fetched (often a few hundred).</span>
                                        </p>
                                        <p class="description">Leave filters empty to import all products</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="button" id="api-fetch-catalog-btn" class="button button-secondary">Fetch catalog</button>
                                <button type="button" id="api-import-btn" class="button button-primary">Start API Import</button>
                                <button type="button" id="api-update-pricing-btn" class="button button-secondary">Update Pricing & Inventory Only</button>
                                <button type="button" id="api-test-details-connection-btn" class="button button-secondary">Test details connection</button>
                            </p>
                            <p class="description"><strong>Test details connection:</strong> After &quot;Fetch catalog&quot;, click this to run one CatalogFeed/Item request. If it fails with &quot;connection dropped&quot;, your host may be killing outbound requests — use <a href="?page=lipseys-import&tab=csv">CSV Import</a> instead.</p>
                            <p class="description"><strong>Step 1:</strong> Click &quot;Fetch catalog&quot; (may take 1–2 min). <strong>Step 2:</strong> Then click &quot;Start API Import&quot; to process in batches. <strong>Update Pricing & Inventory:</strong> Fast update of prices and stock for existing products (Pricing Quantity Feed, hourly). <em>Does not update specifications (barrel, finish, capacity, etc.) — use full &quot;Start API Import&quot; to refresh or add specs.</em></p>
                            <p class="description" style="margin-top: 8px;"><strong>If &quot;Fetching details…&quot; stalls:</strong> Use a <strong>Manufacturer</strong> filter (e.g. Glock) to reduce items, then Fetch catalog again.</p>
                            <p class="description" style="margin-top: 8px;"><strong>Grips / Triggers / Holsters:</strong> Run <strong>Initialize Category Structure</strong> once (above). If &quot;Products with TYPE stored&quot; is 0 after import, use <strong>Backfill TYPE from API</strong> and then <strong>Recategorize by TYPE</strong> (below).</p>
                            <p class="description" style="margin-top: 8px;"><strong>If API keeps 502/504:</strong> Use the <a href="?page=lipseys-import&tab=csv">CSV Import</a> tab with a Lipsey's catalog CSV — no gateway timeouts.</p>
                        </form>
                    </div>
                </div>
                
                <!-- API Progress Section -->
                <div id="api-progress-section" class="postbox" style="display: none;">
                    <h2 class="hndle">Import Progress</h2>
                    <div class="inside">
                        <div id="api-progress-bar-container">
                            <div id="api-progress-bar" style="width: 0%; background: #2271b1; height: 30px; line-height: 30px; text-align: center; color: white;">
                                0%
                            </div>
                        </div>
                        <div id="api-progress-stats" style="margin-top: 15px;">
                            <p><strong>Status:</strong> <span id="api-progress-status">Ready</span></p>
                            <p><strong>Processed:</strong> <span id="api-processed-count">0</span> / <span id="api-total-count">0</span></p>
                            <p><strong>Fetched from API:</strong> <span id="api-fetched-count">0</span></p>
                            <p><strong>Created:</strong> <span id="api-created-count">0</span></p>
                            <p><strong>Updated:</strong> <span id="api-updated-count">0</span></p>
                            <p><strong>Errors:</strong> <span id="api-error-count">0</span></p>
                        </div>
                        <div id="api-error-log" style="margin-top: 15px; max-height: 200px; overflow-y: auto; display: none;">
                            <h4>Error Log:</h4>
                            <ul id="api-error-list"></ul>
                        </div>
                    </div>
                </div>
                
                <!-- Attach images (after import with "Skip images" checked) -->
                <div class="postbox" style="margin-top: 15px;">
                    <h2 class="hndle">Attach images later</h2>
                    <div class="inside">
                        <p class="description">Use this after importing with &quot;Skip image download&quot; checked. Keep <strong>batch size 1</strong>. Each request attaches one image and returns in under 4 seconds to avoid 502. Click once and leave the tab open; if you see 502, click the button again to continue from where it stopped.</p>
                        <p><label for="attach-images-batch">Batch size (1–15) per request:</label> <input type="number" id="attach-images-batch" min="1" max="15" value="1" style="width: 4em;"> <strong>Status:</strong> <span id="attach-images-status">—</span></p>
                        <p><button type="button" id="attach-images-btn" class="button button-secondary">Attach images to products missing thumbnails</button> <button type="button" id="attach-images-reset-failed-btn" class="button">Reset failed attempts</button></p>
                    </div>
                </div>
                
                <!-- Remove Zanders products (one-time cleanup when moving to Lipsey's only) -->
                <?php
                global $wpdb;
                $zanders_count = (int) $wpdb->get_var(
                    "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_zanders_item_number' AND pm.meta_value != ''
                    WHERE p.post_type = 'product' AND p.post_status IN ('publish', 'draft', 'private')"
                );
                ?>
                <div class="postbox" style="margin-top: 15px;">
                    <h2 class="hndle">Remove Zanders products</h2>
                    <div class="inside">
                        <p class="description">Products imported by the Zanders plugin have the meta <code>_zanders_item_number</code>. Use this to move them to Trash so only Lipsey's products remain. You can empty Trash later in WooCommerce → Products.</p>
                        <p><strong>Zanders products found:</strong> <span id="zanders-product-count"><?php echo (int) $zanders_count; ?></span></p>
                        <p><button type="button" id="remove-zanders-btn" class="button button-secondary" <?php echo $zanders_count <= 0 ? ' disabled' : ''; ?>>Move Zanders products to Trash</button> <span id="remove-zanders-status"></span></p>
                    </div>
                </div>
                
            <?php endif; ?>

            <!-- Category tools: order of operations, Backfill TYPE, Recategorize, Sync (visible on both tabs) -->
            <div class="postbox" style="margin-top: 15px; border-left: 4px solid #2271b1;">
                <h2 class="hndle">Order of operations (Grips, Triggers, Holsters)</h2>
                <div class="inside">
                    <ol style="margin: 0.5em 0 1em 1.2em;">
                        <li><strong>Backfill TYPE</strong> — Run until &quot;Products missing TYPE&quot; is 0. Leave the tab open.</li>
                        <li><strong>Recategorize by TYPE</strong> — Click <strong>once</strong> and leave the tab open until it says &quot;Done.&quot; Each extra click restarts from the beginning (so it can look like the same batch repeating). If you get 502, reduce batch size and run again.</li>
                        <li><strong>Sync Triggers / Grips / Holsters</strong> — Run after Recategorize. This copies products from accessory-* categories into the main Triggers, Grips, Holsters categories so the shop category pages show products.</li>
                    </ol>
                </div>
            </div>
            <div class="postbox" style="margin-top: 15px;">
                <h2 class="hndle">Backfill TYPE from API</h2>
                <div class="inside">
                    <p class="description">If &quot;Products with TYPE stored&quot; (below) is 0, this fetches TYPE from the Lipsey's API for each product that has a SKU but no <code>_lipseys_type</code>. Run this, then <strong>Recategorize by TYPE</strong>. Batch of 5–50 per request (default 15).</p>
                    <p><strong>Products missing TYPE (have SKU):</strong> <span id="backfill-type-count"><?php echo (int) $missing_type_count; ?></span></p>
                    <p><label for="backfill-type-batch">Batch size:</label> <input type="number" id="backfill-type-batch" min="5" max="50" value="15" style="width: 4em;"> <button type="button" id="backfill-type-btn" class="button button-secondary">Backfill TYPE from API</button> <span id="backfill-type-status">—</span></p>
                </div>
            </div>
            <div class="postbox" style="margin-top: 15px;">
                <h2 class="hndle">Recategorize by Lipsey's TYPE</h2>
                <div class="inside">
                    <p class="description">Re-apply category mapping (Grips, Triggers, Holsters) using stored TYPE. <strong>Click once</strong> and leave the tab open until it says Done — clicking again restarts from 0. If you get 502, reduce batch size and click again.</p>
                    <p><strong>Products with TYPE stored:</strong> <span id="recategorize-count"><?php echo (int) $recat_count; ?></span></p>
                    <p><label for="recategorize-batch">Batch size (lower if 502):</label> <input type="number" id="recategorize-batch" min="10" max="200" value="30" style="width: 4em;"> <button type="button" id="recategorize-by-type-btn" class="button button-secondary">Recategorize products by TYPE</button> <span id="recategorize-status">—</span></p>
                </div>
            </div>
            <div class="postbox" style="margin-top: 15px;">
                <h2 class="hndle">Sync Triggers / Grips / Holsters</h2>
                <div class="inside">
                    <p class="description">Run <strong>after</strong> Recategorize. If Triggers, Grips, or Holsters category pages are still empty but products show ACCESSORY-TRIGGERS etc. on the shop, this adds those products to the main Triggers, Grips, Holsters categories so the category pages populate.</p>
                    <p><button type="button" id="sync-accessory-categories-btn" class="button button-secondary">Sync products into Triggers, Grips, Holsters</button> <span id="sync-accessory-status">—</span></p>
                    <p class="description" style="margin-top: 10px;">If you expect more holsters/triggers/grips than TYPE gives you, use <strong>Add by product name</strong> below — it adds any product whose <em>title</em> contains &quot;holster&quot;, &quot;trigger&quot;, or &quot;grip&quot; to the matching category.</p>
                    <p><button type="button" id="sync-by-product-name-btn" class="button button-secondary">Add to Triggers/Grips/Holsters by product name</button> <span id="sync-by-name-status">—</span></p>
                </div>
            </div>
            
            </div>
        </div>
        <?php
    }
    
    public function handle_file_upload() {
        check_ajax_referer('lipseys_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error(array('message' => 'No file uploaded'));
        }
        
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'File upload error: ' . $file['error']));
        }
        
        // Move to uploads directory
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . basename($file['name']);
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => 'Failed to move uploaded file'));
        }
        
        $user_id = get_current_user_id();
        set_transient('lipseys_csv_upload_' . $user_id, $file_path, 86400); // 24 hours so resume works next day
        
        wp_send_json_success(array(
            'file_path' => $file_path,
            'file_name' => basename($file['name'])
        ));
    }
    
    /**
     * Get path to uploaded CSV (from transient, or from POST if transient was lost).
     * Caller may pass $post_file_path from $_POST['file_path'] for recovery when transient expires or is lost (e.g. different server).
     */
    private function get_uploaded_csv_path( $post_file_path = null ) {
        $user_id = get_current_user_id();
        $file_path = get_transient( 'lipseys_csv_upload_' . $user_id );
        if ( ! empty( $file_path ) && is_string( $file_path ) && file_exists( $file_path ) ) {
            set_transient( 'lipseys_csv_upload_' . $user_id, $file_path, 86400 ); // refresh TTL on use
            return $file_path;
        }
        if ( ! empty( $post_file_path ) && is_string( $post_file_path ) ) {
            $path = sanitize_text_field( wp_unslash( $post_file_path ) );
            $upload_dir = wp_upload_dir();
            $base = realpath( $upload_dir['basedir'] );
            if ( $base && $path && file_exists( $path ) ) {
                $real = realpath( $path );
                if ( $real && strpos( $real, $base ) === 0 ) {
                    set_transient( 'lipseys_csv_upload_' . $user_id, $real, 86400 );
                    return $real;
                }
            }
        }
        return null;
    }
    
    public function handle_preview() {
        check_ajax_referer('lipseys_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $post_path = isset($_POST['file_path']) ? $_POST['file_path'] : null;
        $file_path = $this->get_uploaded_csv_path($post_path);
        if ($file_path === null) {
            wp_send_json_error(array('message' => 'File not found. Upload the CSV again and click Preview CSV.'));
        }
        
        $processor = new Lipseys_CSV_Processor($file_path);
        
        if (!$processor->parse()) {
            wp_send_json_error(array(
                'message' => 'Failed to parse CSV',
                'errors' => $processor->get_errors()
            ));
        }
        
        $preview = $processor->get_preview(10);
        $total_count = $processor->get_count();
        
        wp_send_json_success(array(
            'preview' => $preview,
            'total_count' => $total_count,
            'headers' => $processor->get_headers()
        ));
    }
    
    public function handle_import() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Refresh the page and try again.'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        set_time_limit(120);
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }
        
        $post_path = isset($_POST['file_path']) ? $_POST['file_path'] : null;
        $file_path = $this->get_uploaded_csv_path($post_path);
        if ($file_path === null) {
            wp_send_json_error(array('message' => 'File not found. Upload the CSV again, click Preview CSV, then Start Import.'));
        }
        
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $skip_images = ! empty($_POST['csv_skip_images']);
        
        // Update image base URL if provided
        if (isset($_POST['image_base_url'])) {
            Lipseys_Image_Handler::set_image_base_url(sanitize_text_field($_POST['image_base_url']));
        }
        
        $processor = new Lipseys_CSV_Processor($file_path);
        
        if (!$processor->parse()) {
            wp_send_json_error(array(
                'message' => 'Failed to parse CSV',
                'errors' => $processor->get_errors()
            ));
        }
        
        $rows = $processor->get_rows();
        $total = count($rows);
        $batch = array_slice($rows, $offset, $batch_size);
        
        $importer = new Lipseys_Product_Importer();
        $importer->set_skip_images($skip_images);
        
        foreach ($batch as $row) {
            $importer->import_product($row);
        }
        
        $stats = $importer->get_stats();
        $errors = $importer->get_errors();
        
        $new_offset = $offset + count($batch);
        $is_complete = $new_offset >= $total;
        
        wp_send_json_success(array(
            'offset' => $new_offset,
            'total' => $total,
            'is_complete' => $is_complete,
            'stats' => $stats,
            'errors' => array_slice($errors, -10) // Last 10 errors
        ));
    }
    
    public function test_image_url() {
        check_ajax_referer('lipseys_import_nonce', 'nonce');
        
        $image_name = isset($_POST['image_name']) ? sanitize_text_field($_POST['image_name']) : '';
        $base_url = isset($_POST['base_url']) ? sanitize_text_field($_POST['base_url']) : '';
        
        if (empty($image_name)) {
            $image_name = '1103534d.jpg'; // Default test image from CSV
        }
        
        if (!empty($base_url)) {
            Lipseys_Image_Handler::set_image_base_url($base_url);
        }
        
        $result = Lipseys_Image_Handler::test_image_download($image_name);
        
        wp_send_json_success($result);
    }
    
    /**
     * Fetch catalog only (no product processing). Use this first to avoid 502 on "Start API Import".
     * This one request may take 1–2 min; if it 502s, the host is killing long requests.
     */
    public function handle_api_fetch_catalog() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));

        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Refresh the page and try again.'));
        }

        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        set_time_limit(180);
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }

        $filter_manufacturer = isset($_POST['filter_manufacturer']) ? sanitize_text_field(wp_unslash($_POST['filter_manufacturer'])) : '';
        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field(wp_unslash($_POST['filter_type'])) : '';
        $filter_in_stock_only = isset($_POST['filter_in_stock_only']) ? (bool) $_POST['filter_in_stock_only'] : true;

        if (isset($_POST['image_base_url'])) {
            Lipseys_Image_Handler::set_image_base_url(sanitize_text_field(wp_unslash($_POST['image_base_url'])));
        }

        try {
            $api_importer = new Lipseys_API_Importer();
            $result = $api_importer->fetch_and_cache_catalog(array(
                'filter_manufacturer' => $filter_manufacturer,
                'filter_type' => $filter_type,
                'filter_in_stock_only' => $filter_in_stock_only,
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Fetch failed: ' . $e->getMessage(),
                'hint' => 'Check API credentials and proxy. If you get 502, your host may be killing long requests.',
            ));
        }

        if (! empty($result['success'])) {
            $data = array(
                'message' => 'Catalog loaded: ' . $result['total'] . ' products. Click "Start API Import" to begin.',
                'total' => $result['total'],
            );
            if (! empty($result['use_batch_details'])) {
                $data['use_batch_details'] = true;
                $data['message'] = 'Got ' . $result['total'] . ' items. Fetching details in batches…';
            }
            wp_send_json_success($data);
        }

        wp_send_json_error(array(
            'message' => implode(' ', $result['errors']),
            'errors' => $result['errors'],
        ));
    }

    /**
     * Fetch one batch of full catalog details (per-item API calls). Used when catalog was loaded via pricing feed.
     */
    public function handle_api_fetch_catalog_details_batch() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));

        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        @set_time_limit(120);
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }
        $batch_index = isset($_POST['batch_index']) ? max(0, intval($_POST['batch_index'])) : 0;
        $batch_size = isset($_POST['batch_size']) ? min(50, max(2, intval($_POST['batch_size']))) : 3;
        $chunk_offset = isset($_POST['chunk_offset']) ? max(0, intval($_POST['chunk_offset'])) : 0;
        $chunk_size = isset($_POST['chunk_size']) ? min(1000, max(100, intval($_POST['chunk_size']))) : 500;

        try {
            $api_importer = new Lipseys_API_Importer();
            $result = $api_importer->fetch_catalog_details_batch($batch_index, $batch_size, $chunk_offset, $chunk_size);
        } catch (Throwable $e) {
            wp_send_json_error(array(
                'message' => 'Server error: ' . $e->getMessage(),
                'is_complete' => false,
            ));
        }

        if (empty($result['success'])) {
            $msg = isset($result['errors'][0]) ? $result['errors'][0] : 'Details batch failed. Try "Fetch catalog" again or use a Manufacturer filter.';
            wp_send_json_error(array(
                'message' => $msg,
                'is_complete' => false,
            ));
        }

        $is_chunk_complete = ! empty($result['is_chunk_complete']);
        $message = 'Fetching details… ' . $result['catalog_count'] . ' in this chunk';
        if ($is_chunk_complete) {
            $message = 'Fetched ' . $result['catalog_count'] . ' products. Importing this chunk…';
        }
        if ($result['is_complete']) {
            $message = 'Catalog loaded: ' . $result['catalog_count'] . ' products. Click "Start API Import" to begin.';
        }

        wp_send_json_success(array(
            'batch_index' => $result['batch_index'],
            'fetched' => $result['fetched'],
            'catalog_count' => $result['catalog_count'],
            'is_chunk_complete' => $is_chunk_complete,
            'is_complete' => $result['is_complete'],
            'total_items' => $result['total_items'],
            'chunk_offset' => $result['chunk_offset'],
            'chunk_size' => $result['chunk_size'],
            'message' => $message,
        ));
    }

    /**
     * Test a single CatalogFeed/Item request (after Fetch catalog). Helps diagnose "connection dropped" on details fetch.
     */
    public function handle_api_test_details_connection() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));

        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $item_numbers = get_transient('lipseys_api_import_item_list');
        if (! is_array($item_numbers) || empty($item_numbers)) {
            wp_send_json_error(array('message' => 'No item list. Click "Fetch catalog" first (with a Manufacturer filter if you like).'));
        }

        $item_no = $item_numbers[0];
        try {
            $res = Lipseys_API_Client::catalog_feed_item($item_no);
        } catch (Throwable $e) {
            wp_send_json_error(array('message' => 'Request threw: ' . $e->getMessage()));
        }

        if (! empty($res['success']) && ! empty($res['data'])) {
            wp_send_json_success(array('message' => 'OK — server can reach Lipsey\'s API. Item: ' . $item_no));
        }
        $err = isset($res['errors'][0]) ? $res['errors'][0] : 'Unknown error';
        wp_send_json_error(array('message' => $err));
    }

    /**
     * Handle API import via AJAX (processes batches from cached catalog only).
     * Always returns JSON (no wp_die) so the UI can show the real error.
     */
    public function handle_api_import() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));

        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Refresh the page and try again.'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        set_time_limit(180);
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }

        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $skip_images = ! empty($_POST['api_skip_images']);
        $filter_manufacturer = isset($_POST['filter_manufacturer']) ? sanitize_text_field(wp_unslash($_POST['filter_manufacturer'])) : '';
        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field(wp_unslash($_POST['filter_type'])) : '';
        $filter_in_stock_only = isset($_POST['filter_in_stock_only']) ? (bool) $_POST['filter_in_stock_only'] : true;

        if (isset($_POST['image_base_url'])) {
            Lipseys_Image_Handler::set_image_base_url(sanitize_text_field(wp_unslash($_POST['image_base_url'])));
        }

        try {
            $api_importer = new Lipseys_API_Importer();
            $result = $api_importer->import_from_api(array(
                'batch_size' => $batch_size,
                'offset' => $offset,
                'update_existing' => true,
                'skip_images' => $skip_images,
                'filter_manufacturer' => $filter_manufacturer,
                'filter_type' => $filter_type,
                'filter_in_stock_only' => $filter_in_stock_only,
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => array($e->getMessage()),
                'hint' => 'Try a smaller batch (e.g. 25) or check PHP error log.',
            ));
        }

        if (!empty($result['success'])) {
            wp_send_json_success($result);
        }

        wp_send_json_error(array_merge(
            array('message' => 'Import returned an error.'),
            $result
        ));
    }
    
    /**
     * Attach images to products that have _lipseys_image_name but no thumbnail (small batches to avoid 502).
     */
    public function handle_attach_images() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        // EasyWP gateway often 502s in under 5s. Batch 1: finish in ~4s (no COUNT query, no thumbnails).
        $batch = isset($_POST['batch_size']) ? min(15, max(1, intval($_POST['batch_size']))) : 1;
        $max_time = $batch === 1 ? 6 : min(35, 20 + $batch * 2);
        @set_time_limit($max_time);
        $deadline = time() + ($batch === 1 ? 4 : 28);
        if (isset($_POST['image_base_url'])) {
            Lipseys_Image_Handler::set_image_base_url(sanitize_text_field(wp_unslash($_POST['image_base_url'])));
        }
        // Skip thumbnail generation for this request (saves 1–3s per image on EasyWP).
        $skip_thumbnails = function () { return array(); };
        add_filter('intermediate_image_sizes_advanced', $skip_thumbnails);
        global $wpdb;
        $ids = $wpdb->get_col(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_image_name' AND pm.meta_value != ''
            LEFT JOIN {$wpdb->postmeta} th ON p.ID = th.post_id AND th.meta_key = '_thumbnail_id'
            LEFT JOIN {$wpdb->postmeta} fail ON p.ID = fail.post_id AND fail.meta_key = '_lipseys_image_attach_failed'
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            AND (th.meta_value IS NULL OR th.meta_value = '' OR th.meta_value = '0')
            AND fail.post_id IS NULL
            ORDER BY p.ID ASC
            LIMIT " . intval($batch)
        );
        $attached = 0;
        foreach ($ids as $product_id) {
            if (time() >= $deadline) {
                break;
            }
            $product_id = (int) $product_id;
            $image_name = get_post_meta($product_id, '_lipseys_image_name', true);
            if (empty($image_name)) {
                continue;
            }
            $sku = get_post_meta($product_id, '_sku', true);
            $result = Lipseys_Image_Handler::attach_image_to_product($product_id, $image_name, $sku);
            if ($result) {
                $attached++;
            } else {
                update_post_meta($product_id, '_lipseys_image_attach_failed', time());
            }
        }
        remove_filter('intermediate_image_sizes_advanced', $skip_thumbnails);
        // Actual remaining count (same query for all batch sizes).
        $remaining = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_image_name' AND pm.meta_value != ''
            LEFT JOIN {$wpdb->postmeta} th ON p.ID = th.post_id AND th.meta_key = '_thumbnail_id'
            LEFT JOIN {$wpdb->postmeta} fail ON p.ID = fail.post_id AND fail.meta_key = '_lipseys_image_attach_failed'
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            AND (th.meta_value IS NULL OR th.meta_value = '' OR th.meta_value = '0')
            AND fail.post_id IS NULL"
        );
        wp_send_json_success(array(
            'attached' => $attached,
            'remaining' => $remaining,
            'is_complete' => $remaining <= 0,
        ));
    }
    
    /**
     * Reset _lipseys_image_attach_failed so "Attach images" will retry those products.
     */
    public function handle_attach_images_reset_failed() {
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        global $wpdb;
        $deleted = $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_lipseys_image_attach_failed'");
        wp_send_json_success(array('cleared' => (int) $deleted));
    }
    
    /**
     * Recategorize products that have _lipseys_type: re-apply map_type_to_categories and set_category_ids.
     * Batched (e.g. 100 per request) to avoid timeout. Use after adding TYPE mappings (Grips, Triggers, Holsters).
     */
    public function handle_recategorize_by_type() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        $batch_size = isset($_POST['batch_size']) ? min(200, max(1, (int) $_POST['batch_size'])) : 30;
        $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
        global $wpdb;
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_type' AND pm.meta_value != ''
                WHERE p.post_type = 'product' AND p.post_status = 'publish'
                ORDER BY p.ID ASC
                LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            )
        );
        $updated = 0;
        foreach ($ids as $post_id) {
            $post_id = (int) $post_id;
            $product = wc_get_product($post_id);
            if (! $product || ! is_a($product, 'WC_Product')) {
                continue;
            }
            $type = get_post_meta($post_id, '_lipseys_type', true);
            $itemgroup = get_post_meta($post_id, '_lipseys_itemgroup', true);
            $categories = Lipseys_Category_Mapper::map_type_to_categories($type, $itemgroup);
            if (! empty($categories)) {
                $product->set_category_ids($categories);
                $product->save();
                $updated++;
            }
        }
        $total_with_type = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_type' AND pm.meta_value != ''
            WHERE p.post_type = 'product' AND p.post_status = 'publish'"
        );
        $next_offset = $offset + count($ids);
        $remaining = max(0, $total_with_type - $next_offset);
        wp_send_json_success(array(
            'updated' => $updated,
            'remaining' => $remaining,
            'next_offset' => $next_offset,
            'is_complete' => $remaining <= 0,
        ));
    }

    /**
     * Backfill _lipseys_type and _lipseys_itemgroup from API for products that have SKU but no TYPE stored.
     * One API call per product; runs in batches to avoid timeout.
     */
    public function handle_backfill_type() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        global $wpdb;
        $batch_size = isset($_POST['batch_size']) ? min(50, max(5, (int) $_POST['batch_size'])) : 15;
        @set_time_limit(min(120, 45 + $batch_size * 2));

        $ids = $wpdb->get_col(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku' AND pm_sku.meta_value != ''
            LEFT JOIN {$wpdb->postmeta} pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = '_lipseys_type'
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            AND (pm_type.meta_value IS NULL OR pm_type.meta_value = '')
            ORDER BY p.ID ASC
            LIMIT " . (int) $batch_size
        );
        if (empty($ids)) {
            wp_send_json_success(array(
                'updated' => 0,
                'remaining' => 0,
                'is_complete' => true,
                'message' => 'No products missing TYPE.',
            ));
        }

        $updated = 0;
        foreach ($ids as $post_id) {
            $product = wc_get_product($post_id);
            if (! $product || ! is_a($product, 'WC_Product')) {
                continue;
            }
            $sku = $product->get_sku();
            if (empty($sku)) {
                continue;
            }
            $response = Lipseys_API_Client::catalog_feed_item($sku);
            if (empty($response['success']) || empty($response['data'])) {
                continue;
            }
            $data = is_array($response['data']) ? $response['data'] : (array) $response['data'];
            $type = isset($data['type']) ? sanitize_text_field($data['type']) : (isset($data['Type']) ? sanitize_text_field($data['Type']) : '');
            $itemgroup = isset($data['itemType']) ? sanitize_text_field($data['itemType']) : (isset($data['ItemType']) ? sanitize_text_field($data['ItemType']) : '');
            if ($type !== '') {
                update_post_meta($post_id, '_lipseys_type', $type);
                $updated++;
            }
            if ($itemgroup !== '') {
                update_post_meta($post_id, '_lipseys_itemgroup', $itemgroup);
            }
            // Model (short name for specs) and MFG MDL # (part number for top block)
            $model = isset($data['model']) && (string) $data['model'] !== '' ? sanitize_text_field($data['model']) : (isset($data['mfgModelNumber']) ? sanitize_text_field($data['mfgModelNumber']) : '');
            $mfg_no = isset($data['mfgModelNumber']) && (string) trim($data['mfgModelNumber']) !== '' ? sanitize_text_field($data['mfgModelNumber']) : $model;
            if ($model !== '') {
                update_post_meta($post_id, '_model', $model);
            }
            if ($mfg_no !== '') {
                update_post_meta($post_id, '_manufacturer_part_number', $mfg_no);
            }
            // Lipsey's web-style display title (description1 | description2 or displayName/title)
            $d1 = isset($data['description1']) ? trim((string) $data['description1']) : '';
            $d2 = isset($data['description2']) ? trim((string) $data['description2']) : '';
            $display_title = (isset($data['displayName']) && trim((string) $data['displayName']) !== '') ? trim($data['displayName']) : ((isset($data['title']) && trim((string) $data['title']) !== '') ? trim($data['title']) : '');
            if ($display_title === '' && ($d1 !== '' || $d2 !== '')) {
                $display_title = $d2 !== '' ? $d1 . ' | ' . $d2 : $d1;
            }
            if ($display_title !== '') {
                update_post_meta($post_id, '_lipseys_display_title', sanitize_text_field($display_title));
            }
        }

        $remaining = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku' AND pm_sku.meta_value != ''
            LEFT JOIN {$wpdb->postmeta} pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = '_lipseys_type'
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            AND (pm_type.meta_value IS NULL OR pm_type.meta_value = '')"
        );
        $recat_count = (int) $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lipseys_type' AND pm.meta_value != '' WHERE p.post_type = 'product' AND p.post_status = 'publish'");

        wp_send_json_success(array(
            'updated' => $updated,
            'remaining' => $remaining,
            'is_complete' => $remaining <= 0,
            'recategorize_count' => $recat_count,
        ));
    }
    
    /**
     * Sync: ensure products in Triggers, Grips, Holsters (same terms Recategorize uses).
     * Recategorize assigns to categories by name (Triggers, Grips, Holsters under Accessories) — slugs are triggers/grips/holsters, not accessory-*.
     * This checks product counts in those terms and, if an old "accessory-*" slug term exists, copies products from it into the canonical term.
     */
    public function handle_sync_accessory_categories() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        @set_time_limit(90);
        $canonical_names = array( 'Triggers', 'Grips', 'Holsters' );
        $total_updated = 0;
        $details = array();
        $accessories = get_term_by('name', 'Accessories', 'product_cat');
        $parent_id = $accessories ? $accessories->term_id : 0;
        foreach ($canonical_names as $canonical_name) {
            $canonical_term = get_term_by('name', $canonical_name, 'product_cat');
            if (! $canonical_term && $parent_id) {
                $canonical_id = Lipseys_Category_Mapper::get_or_create_category($canonical_name, 'Accessories');
                $canonical_term = $canonical_id ? get_term($canonical_id, 'product_cat') : null;
            } else {
                $canonical_id = $canonical_term ? $canonical_term->term_id : 0;
            }
            if (! $canonical_id) {
                $details[ $canonical_name ] = 0;
                continue;
            }
            $in_canonical = get_objects_in_term($canonical_id, 'product_cat');
            $count_canonical = is_array($in_canonical) ? count(array_unique($in_canonical)) : 0;
            $updated = 0;
            $source_slugs = array(
                'Triggers'  => 'accessory-triggers',
                'Grips'     => 'accessory-grips',
                'Holsters'  => 'accessory-holsters',
            );
            $source_slug = isset($source_slugs[ $canonical_name ]) ? $source_slugs[ $canonical_name ] : '';
            $source_term = $source_slug ? get_term_by('slug', $source_slug, 'product_cat') : null;
            if ($source_term && ! is_wp_error($source_term)) {
                $product_ids = get_objects_in_term($source_term->term_id, 'product_cat');
                if (is_array($product_ids)) {
                    foreach (array_unique($product_ids) as $post_id) {
                        $product = wc_get_product($post_id);
                        if (! $product || ! is_a($product, 'WC_Product')) {
                            continue;
                        }
                        $cat_ids = $product->get_category_ids();
                        if (in_array($canonical_id, $cat_ids, true)) {
                            continue;
                        }
                        $cat_ids[] = $canonical_id;
                        $product->set_category_ids($cat_ids);
                        $product->save();
                        $updated++;
                    }
                }
            }
            $total_updated += $updated;
            $details[ $canonical_name ] = $count_canonical + $updated;
        }
        $in_canonical_total = (int) array_sum(array_values($details));
        if ($total_updated > 0) {
            $message = sprintf(__('Added %d product(s) to Triggers/Grips/Holsters. Category pages should now show products.', 'lipseys-import'), $total_updated);
        } elseif ($in_canonical_total > 0) {
            $message = sprintf(
                __('Products are already in Triggers/Grips/Holsters (Triggers: %d, Grips: %d, Holsters: %d). Category pages should show them. No sync needed.', 'lipseys-import'),
                $details['Triggers'],
                $details['Grips'],
                $details['Holsters']
            );
        } else {
            $message = __('No products in Triggers/Grips/Holsters yet. Run Recategorize by TYPE first, then check again.', 'lipseys-import');
        }
        wp_send_json_success(array(
            'total_updated'    => $total_updated,
            'details'          => $details,
            'Triggers'         => (int) $details['Triggers'],
            'Grips'            => (int) $details['Grips'],
            'Holsters'         => (int) $details['Holsters'],
            'message'          => $message,
        ));
    }
    
    /**
     * Add products to Triggers, Grips, Holsters by product title keyword (holster, trigger, grip).
     * Use when TYPE-based Recategorize leaves too few in those categories.
     */
    public function handle_sync_by_product_name() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        @set_time_limit(120);
        global $wpdb;
        $holsters_id = Lipseys_Category_Mapper::get_or_create_category('Holsters', 'Accessories');
        $triggers_id = Lipseys_Category_Mapper::get_or_create_category('Triggers', 'Accessories');
        $grips_id    = Lipseys_Category_Mapper::get_or_create_category('Grips', 'Accessories');
        $added_holsters = 0;
        $added_triggers = 0;
        $added_grips    = 0;
        foreach (array(
            array('holster', $holsters_id, 'added_holsters'),
            array('trigger', $triggers_id, 'added_triggers'),
            array('grip', $grips_id, 'added_grips'),
        ) as $row) {
            list($keyword, $term_id, $counter) = $row;
            $ids = $wpdb->get_col($wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
                WHERE p.post_type = 'product' AND p.post_status = 'publish'
                AND LOWER(p.post_title) LIKE %s",
                '%' . $wpdb->esc_like(strtolower($keyword)) . '%'
            ));
            if (! is_array($ids)) {
                continue;
            }
            foreach (array_unique(array_map('intval', $ids)) as $post_id) {
                $product = wc_get_product($post_id);
                if (! $product || ! is_a($product, 'WC_Product')) {
                    continue;
                }
                $cat_ids = $product->get_category_ids();
                if (in_array($term_id, $cat_ids, true)) {
                    continue;
                }
                $cat_ids[] = $term_id;
                $product->set_category_ids($cat_ids);
                $product->save();
                $$counter++;
            }
        }
        $total = $added_holsters + $added_triggers + $added_grips;
        $message = $total > 0
            ? sprintf(__('Added by product name: Holsters %d, Triggers %d, Grips %d. Category pages updated.', 'lipseys-import'), $added_holsters, $added_triggers, $added_grips)
            : __('No additional products found by product name (holster/trigger/grip).', 'lipseys-import');
        wp_send_json_success(array(
            'message'  => $message,
            'Holsters'  => $added_holsters,
            'Triggers'  => $added_triggers,
            'Grips'     => $added_grips,
            'total'     => $total,
        ));
    }
    
    /**
     * Move products with _zanders_item_number to Trash (one-time cleanup when using Lipsey's only).
     */
    public function handle_remove_zanders_products() {
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (empty($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lipseys_import_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        global $wpdb;
        $ids = $wpdb->get_col(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_zanders_item_number' AND pm.meta_value != ''
            WHERE p.post_type = 'product' AND p.post_status IN ('publish', 'draft', 'private')"
        );
        $trashed = 0;
        foreach ($ids as $post_id) {
            $post_id = (int) $post_id;
            if (get_post_type($post_id) === 'product') {
                wp_trash_post($post_id);
                $trashed++;
            }
        }
        wp_send_json_success(array(
            'trashed' => $trashed,
            'message' => $trashed ? sprintf(__('%d Zanders product(s) moved to Trash. Empty Trash in WooCommerce → Products when ready.', 'lipseys-import'), $trashed) : __('No Zanders products found.', 'lipseys-import'),
        ));
    }
    
    /**
     * Handle pricing/inventory update via AJAX
     */
    public function handle_api_update_pricing() {
        check_ajax_referer('lipseys_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        try {
            $api_importer = new Lipseys_API_Importer();
            $result = $api_importer->update_pricing_inventory();
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Update failed: ' . $e->getMessage()));
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
