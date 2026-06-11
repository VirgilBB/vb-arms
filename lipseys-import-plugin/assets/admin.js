jQuery(document).ready(function($) {
    let importInProgress = false;
    let currentFile = null;
    
    // Test Image URL
    $('#test-image-url').on('click', function() {
        const baseUrl = $('#image_base_url').val();
        const resultSpan = $('#image-test-result');
        
        resultSpan.html('<span style="color: #666;">Testing...</span>');
        
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            data: {
                action: 'lipseys_test_image',
                nonce: lipseysImport.nonce,
                base_url: baseUrl,
                image_name: '1103534d.jpg' // Test with actual image from CSV
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.success) {
                        resultSpan.html('<span style="color: green;">✓ Image URL works! (' + response.data.size + ' bytes)</span>');
                    } else {
                        resultSpan.html('<span style="color: red;">✗ Image not found: ' + response.data.error + '</span>');
                    }
                } else {
                    resultSpan.html('<span style="color: red;">Error: ' + response.data + '</span>');
                }
            }
        });
    });
    
    // Preview CSV
    $('#preview-btn').on('click', function() {
        const fileInput = $('#csv_file')[0];
        
        if (!fileInput.files.length) {
            alert('Please select a CSV file first');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'lipseys_import_upload');
        formData.append('nonce', lipseysImport.nonce);
        formData.append('csv_file', fileInput.files[0]);
        
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    currentFile = response.data.file_path;
                    loadPreview(response.data.file_path);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    function loadPreview(filePath) {
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            data: {
                action: 'lipseys_import_preview',
                nonce: lipseysImport.nonce,
                file_path: filePath
            },
            success: function(response) {
                if (response.success) {
                    displayPreview(response.data);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
    
    function displayPreview(data) {
        let html = '<p><strong>Total Products:</strong> ' + data.total_count + '</p>';
        html += '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr>';
        
        // Show key columns
        const keyColumns = ['ITEMNO', 'DESCRIPTION1', 'TYPE', 'CURRENTPRICE', 'QUANTITY', 'FFLREQUIRED'];
        keyColumns.forEach(col => {
            if (data.headers.includes(col)) {
                html += '<th>' + col + '</th>';
            }
        });
        html += '</tr></thead><tbody>';
        
        data.preview.forEach(row => {
            html += '<tr>';
            keyColumns.forEach(col => {
                if (data.headers.includes(col)) {
                    html += '<td>' + (row[col] || '') + '</td>';
                }
            });
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $('#preview-content').html(html);
        $('#preview-section').show();
    }
    
    // Start Import
    $('#import-btn').on('click', function() {
        if (!currentFile) {
            alert('Please preview the CSV file first');
            return;
        }
        
        if (importInProgress) {
            alert('Import already in progress');
            return;
        }
        
        if (!confirm('Start importing products? This may take a while.')) {
            return;
        }
        
        importInProgress = true;
        $('#progress-section').show();
        $('#import-btn').prop('disabled', true);
        
        const batchSize = parseInt($('#batch_size').val()) || 5;
        const startOffset = parseInt($('#csv_start_offset').val(), 10) || 0;
        const imageBaseUrl = $('#image_base_url').val();
        const skipImages = $('#csv_skip_images').is(':checked');
        
        processBatch(currentFile, startOffset, batchSize, imageBaseUrl, skipImages);
    });
    
    function processBatch(filePath, offset, batchSize, imageBaseUrl, skipImages) {
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            timeout: 120000,
            data: {
                action: 'lipseys_import_process',
                nonce: lipseysImport.nonce,
                file_path: filePath,
                batch_size: batchSize,
                offset: offset,
                image_base_url: imageBaseUrl,
                csv_skip_images: skipImages ? '1' : ''
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const progress = Math.round((data.offset / data.total) * 100);
                    
                    $('#progress-bar').css('width', progress + '%').text(progress + '%');
                    $('#processed-count').text(data.offset);
                    $('#total-count').text(data.total);
                    $('#created-count').text(data.stats.created);
                    $('#updated-count').text(data.stats.updated);
                    $('#error-count').text(data.stats.errors);
                    
                    if (data.errors && data.errors.length > 0) {
                        $('#error-log').show();
                        const errorList = $('#error-list');
                        data.errors.forEach(error => {
                            errorList.append('<li>' + error + '</li>');
                        });
                    }
                    
                    if (data.is_complete) {
                        $('#progress-status').text('Complete!');
                        $('#import-btn').prop('disabled', false);
                        importInProgress = false;
                        alert('Import complete! Created: ' + data.stats.created + ', Updated: ' + data.stats.updated);
                    } else {
                        // Continue with next batch
                        setTimeout(function() {
                            processBatch(filePath, data.offset, batchSize, imageBaseUrl, skipImages);
                        }, 100);
                    }
                } else {
                    $('#progress-status').text('Error: ' + response.data.message);
                    $('#import-btn').prop('disabled', false);
                    importInProgress = false;
                }
            },
            error: function(xhr, status, err) {
                var msg = 'AJAX Error: ' + status;
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                var text = (xhr.responseText || '').trim();
                if (text) {
                    try {
                        var j = JSON.parse(text);
                        if (j.data && j.data.message) msg += ' — ' + j.data.message;
                        else if (j.message) msg += ' — ' + j.message;
                    } catch (e) {
                        if (xhr.status === 502 || xhr.status === 504) msg += ' — Request timed out. Try a smaller batch size (e.g. 25 or 10).';
                        else if (text.indexOf('</') !== -1) msg += ' — Server returned HTML (check PHP error log).';
                    }
                } else if (xhr.status === 502 || xhr.status === 504) {
                    msg += ' — Request timed out. Try batch size 25 or 10.';
                } else if (status === 'timeout') {
                    msg += ' — Request timed out. Try a smaller batch size.';
                }
                $('#progress-status').text(msg);
                $('#import-btn').prop('disabled', false);
                importInProgress = false;
                var lastProcessed = $('#processed-count').text();
                if (lastProcessed && lastProcessed !== '0') {
                    $('#csv_start_offset').val(lastProcessed);
                }
            }
        });
    }
    
    // === API IMPORT HANDLERS ===
    
    let apiImportInProgress = false;
    let fetchCatalogInProgress = false;
    
    // Fetch catalog (Step 1) — one long request; then use "Start API Import" to process in batches
    $('#api-fetch-catalog-btn').on('click', function() {
        if (fetchCatalogInProgress || apiImportInProgress) {
            alert('Another operation is in progress');
            return;
        }
        fetchCatalogInProgress = true;
        $('#api-fetch-catalog-btn').prop('disabled', true);
        $('#api-import-btn').prop('disabled', true);
        $('#api-update-pricing-btn').prop('disabled', true);
        $('#api-progress-section').show();
        $('#api-progress-status').text('Fetching catalog… (may take 1–2 min)');
        $('#api-progress-bar').css('width', '0%').text('0%');
        $('#api-processed-count').text('0');
        $('#api-total-count').text('0');
        $('#api-fetched-count').text('0');
        $('#api-created-count').text('0');
        $('#api-updated-count').text('0');
        $('#api-error-count').text('0');
        $('#api-error-log').hide();
        $('#api-error-list').empty();

        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            timeout: 180000,
            data: {
                action: 'lipseys_api_fetch_catalog',
                nonce: lipseysImport.nonce,
                image_base_url: $('#api_image_base_url').val(),
                filter_manufacturer: $('#api_filter_manufacturer').val(),
                filter_type: $('#api_filter_type').val(),
                filter_in_stock_only: $('#api_filter_in_stock').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.use_batch_details) {
                        // Pricing feed worked; fetch 500 details at a time, then import that chunk, then next 500
                        // detailsBatchSize 1 = one API call per request; avoids "connection dropped" on EasyWP when proxy is slow
                        var totalItems = response.data.total;
                        var CHUNK_SIZE = 500;
                        var detailsBatchSize = 1;
                        var chunkOffset = 0;
                        var batchIndex = 0;
                        var importBatchSize = parseInt($('#api_batch_size').val()) || 25;
                        var imageBaseUrl = $('#api_image_base_url').val();
                        var filterManufacturer = $('#api_filter_manufacturer').val();
                        var filterType = $('#api_filter_type').val();
                        var filterInStock = $('#api_filter_in_stock').is(':checked');
                        var totalProcessed = 0;
                        var totalCreated = 0;
                        var totalUpdated = 0;

                        var batchesInChunk = Math.ceil(CHUNK_SIZE / detailsBatchSize);
                        $('#api-progress-status').text('Got ' + totalItems + ' items. Fetching details… chunk 1, batch 1/' + batchesInChunk + '…');
                        $('#api-total-count').text('0');

                        function doneFetchCatalog() {
                            $('#api-progress-status').text('Complete! Imported ' + totalProcessed + ' products (created: ' + totalCreated + ', updated: ' + totalUpdated + ').');
                            $('#api-fetch-catalog-btn').prop('disabled', false);
                            $('#api-import-btn').prop('disabled', false);
                            $('#api-update-pricing-btn').prop('disabled', false);
                            fetchCatalogInProgress = false;
                        }

                        function runImportThisChunk(then) {
                            var skipImages = $('#api_skip_images').is(':checked');
                            processAPIBatchForChunk(0, importBatchSize, imageBaseUrl, filterManufacturer, filterType, filterInStock, skipImages, function(data) {
                                totalProcessed += (data.total || 0);
                                totalCreated += (data.stats && data.stats.created) ? data.stats.created : 0;
                                totalUpdated += (data.stats && data.stats.updated) ? data.stats.updated : 0;
                                then();
                            });
                        }

                        function doNextBatch() {
                            var batchNum = batchIndex + 1;
                            var batchesInChunk = Math.ceil(CHUNK_SIZE / detailsBatchSize);
                            var chunkNum = Math.floor(chunkOffset / CHUNK_SIZE) + 1;
                            $('#api-progress-status').text('Fetching details… chunk ' + chunkNum + ', batch ' + batchNum + '/' + batchesInChunk + '…');
                            $.ajax({
                                url: lipseysImport.ajax_url,
                                type: 'POST',
                                timeout: 90000,
                                data: {
                                    action: 'lipseys_api_fetch_catalog_details_batch',
                                    nonce: lipseysImport.nonce,
                                    batch_index: batchIndex,
                                    batch_size: detailsBatchSize,
                                    chunk_offset: chunkOffset,
                                    chunk_size: CHUNK_SIZE
                                },
                                success: function(res) {
                                    if (res.success) {
                                        var d = res.data;
                                        var batchNum = batchIndex + 1;
                                        var batchesInChunk = Math.ceil(CHUNK_SIZE / detailsBatchSize);
                                        $('#api-progress-status').text('Fetching details… chunk ' + (Math.floor(chunkOffset / CHUNK_SIZE) + 1) + ', batch ' + batchNum + '/' + batchesInChunk + ' — ' + d.catalog_count + ' items');
                                        $('#api-total-count').text(totalProcessed + d.catalog_count);
                                        var pct = d.total_items ? Math.round(((chunkOffset + d.catalog_count) / d.total_items) * 100) : 0;
                                        $('#api-progress-bar').css('width', pct + '%').text(pct + '%');
                                        if (d.is_chunk_complete) {
                                            $('#api-progress-status').text('Importing chunk of ' + d.catalog_count + '…');
                                            runImportThisChunk(function() {
                                                if (chunkOffset + CHUNK_SIZE < d.total_items) {
                                                    chunkOffset += CHUNK_SIZE;
                                                    batchIndex = 0;
                                                    doNextBatch();
                                                } else {
                                                    doneFetchCatalog();
                                                }
                                            });
                                        } else {
                                            batchIndex++;
                                            doNextBatch();
                                        }
                                    } else {
                                        $('#api-progress-status').text('Error: ' + (res.data.message || 'Unknown error'));
                                        $('#api-fetch-catalog-btn').prop('disabled', false);
                                        $('#api-import-btn').prop('disabled', false);
                                        $('#api-update-pricing-btn').prop('disabled', false);
                                        fetchCatalogInProgress = false;
                                    }
                                },
                                error: function(xhr, st, err) {
                                    var msg = 'Fetch details failed: ' + st;
                                    if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                                    else if (st === 'timeout') msg += ' — Request timed out. Try Manufacturer filter (e.g. Glock) and Fetch catalog again.';
                                    else if (st === 'parsererror') msg += ' — Server returned invalid JSON; check PHP error log.';
                                    else if (st === 'error' && !xhr.status) msg += ' — Connection dropped or server died (no HTTP code). Check wp-content/debug.log; use Manufacturer filter (e.g. Glock) to reduce load.';
                                    var text = (xhr.responseText || '').trim();
                                    var snippet = text.length > 2000 ? text.substring(0, 2000) : text;
                                    if (snippet) {
                                        try {
                                            var j = JSON.parse(snippet);
                                            if (j.data && j.data.message) msg += ' — ' + j.data.message;
                                            else if (j.message) msg += ' — ' + j.message;
                                        } catch (e) {
                                            if (snippet.indexOf('</') !== -1) msg += ' — Server returned HTML; check PHP error log (wp-content/debug.log).';
                                        }
                                    }
                                    msg += ' Try "Start API Import" for already-fetched products; or use Manufacturer filter and Fetch catalog again.';
                                    $('#api-progress-status').text(msg);
                                    $('#api-fetch-catalog-btn').prop('disabled', false);
                                    $('#api-import-btn').prop('disabled', false);
                                    $('#api-update-pricing-btn').prop('disabled', false);
                                    fetchCatalogInProgress = false;
                                }
                            });
                        }
                        // Wait 3s before first details batch so EasyWP doesn't drop the connection (back-to-back requests can fail)
                        $('#api-progress-status').text('Got ' + totalItems + ' items. Starting details fetch in 3s…');
                        setTimeout(function() { doNextBatch(); }, 3000);
                    } else {
                        $('#api-progress-status').text(response.data.message || ('Catalog loaded: ' + response.data.total + ' products. Click "Start API Import" to begin.'));
                        $('#api-total-count').text(response.data.total || 0);
                        $('#api-fetch-catalog-btn').prop('disabled', false);
                        $('#api-import-btn').prop('disabled', false);
                        $('#api-update-pricing-btn').prop('disabled', false);
                        fetchCatalogInProgress = false;
                    }
                } else {
                    $('#api-progress-status').text('Error: ' + (response.data.message || 'Unknown error'));
                    if (response.data.errors && response.data.errors.length) {
                        $('#api-error-log').show();
                        const list = $('#api-error-list');
                        list.empty();
                        response.data.errors.forEach(function(err) { list.append('<li>' + err + '</li>'); });
                    }
                    $('#api-fetch-catalog-btn').prop('disabled', false);
                    $('#api-import-btn').prop('disabled', false);
                    $('#api-update-pricing-btn').prop('disabled', false);
                    fetchCatalogInProgress = false;
                }
            },
            error: function(xhr, status, error) {
                let msg = 'Fetch catalog failed: ' + status;
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                var text = (xhr.responseText || '').trim();
                if (text) {
                    try {
                        var json = JSON.parse(text);
                        if (json.data && json.data.message) msg += ' — ' + json.data.message;
                        else if (json.message) msg += ' — ' + json.message;
                    } catch (e) {
                        if (xhr.status === 502) msg += ' — 502 = request killed (proxy or host timeout). Try again; if it still fails, your host or proxy limits request length.';
                        else if (text.indexOf('</') !== -1) msg += ' — Server returned HTML; check PHP error log.';
                    }
                } else if (xhr.status === 502) {
                    msg += ' — 502 = request killed. Try again; if it still fails, proxy or host is limiting request length.';
                } else if (status === 'timeout') {
                    msg += ' — Request timed out (3 min). Your host may limit request length.';
                }
                $('#api-progress-status').text(msg);
                $('#api-fetch-catalog-btn').prop('disabled', false);
                $('#api-import-btn').prop('disabled', false);
                $('#api-update-pricing-btn').prop('disabled', false);
                fetchCatalogInProgress = false;
            }
        });
    });

    // Test details connection — one CatalogFeed/Item request to see if server can reach Lipsey's
    $('#api-test-details-connection-btn').on('click', function() {
        var $btn = $(this);
        var $status = $('#api-progress-status');
        $btn.prop('disabled', true);
        $status.text('Testing…');
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: {
                action: 'lipseys_api_test_details_connection',
                nonce: lipseysImport.nonce
            },
            success: function(res) {
                if (res.success) {
                    $status.text(res.data.message || 'OK');
                } else {
                    $status.text('Test failed: ' + (res.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, st, err) {
                var msg = 'Test failed: ' + st;
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                else if (st === 'error' && !xhr.status) msg += ' — Connection dropped (no HTTP code). Your host may be killing outbound requests. Use CSV Import.';
                $status.text(msg);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Start API Import (Step 2) — processes from cached catalog only
    $('#api-import-btn').on('click', function() {
        if (apiImportInProgress || fetchCatalogInProgress) {
            alert('Another operation is in progress');
            return;
        }
        
        if (!confirm('Start importing from cached catalog? (Click "Fetch catalog" first if you haven\'t.)')) {
            return;
        }
        
        apiImportInProgress = true;
        $('#api-progress-section').show();
        $('#api-fetch-catalog-btn').prop('disabled', true);
        $('#api-import-btn').prop('disabled', true);
        $('#api-update-pricing-btn').prop('disabled', true);
        $('#api-progress-status').text('Processing batch 1...');
        
        const batchSize = parseInt($('#api_batch_size').val()) || 50;
        const imageBaseUrl = $('#api_image_base_url').val();
        const filterManufacturer = $('#api_filter_manufacturer').val();
        const filterType = $('#api_filter_type').val();
        const filterInStock = $('#api_filter_in_stock').is(':checked');
        const skipImages = $('#api_skip_images').is(':checked');
        
        processAPIBatch(0, batchSize, imageBaseUrl, filterManufacturer, filterType, filterInStock, skipImages);
    });

    // Same as processAPIBatch but on completion calls onComplete(data) instead of re-enabling buttons (used for chunked fetch→import).
    function processAPIBatchForChunk(offset, batchSize, imageBaseUrl, filterManufacturer, filterType, filterInStock, skipImages, onComplete) {
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            timeout: 120000,
            data: {
                action: 'lipseys_api_import_start',
                nonce: lipseysImport.nonce,
                batch_size: batchSize,
                offset: offset,
                image_base_url: imageBaseUrl,
                filter_manufacturer: filterManufacturer,
                filter_type: filterType,
                filter_in_stock_only: filterInStock,
                api_skip_images: skipImages ? '1' : ''
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#api-progress-bar').css('width', Math.round((data.offset / data.total) * 100) + '%').text(Math.round((data.offset / data.total) * 100) + '%');
                    $('#api-processed-count').text(data.offset);
                    $('#api-total-count').text(data.total);
                    $('#api-created-count').text(data.stats.created);
                    $('#api-updated-count').text(data.stats.updated);
                    $('#api-progress-status').text('Importing… ' + data.offset + ' / ' + data.total);
                    if (data.is_complete) {
                        onComplete(data);
                    } else {
                        setTimeout(function() {
                            processAPIBatchForChunk(data.offset, batchSize, imageBaseUrl, filterManufacturer, filterType, filterInStock, skipImages, onComplete);
                        }, 500);
                    }
                } else {
                    $('#api-progress-status').text('Error: ' + (response.data.message || 'Unknown error'));
                    $('#api-fetch-catalog-btn').prop('disabled', false);
                    $('#api-import-btn').prop('disabled', false);
                    $('#api-update-pricing-btn').prop('disabled', false);
                    fetchCatalogInProgress = false;
                }
            },
            error: function(xhr, status, error) {
                var msg = 'Import batch failed: ' + status;
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                else if (status === 'timeout') msg += ' — Request timed out; try smaller API batch size.';
                var text = (xhr.responseText || '').trim();
                if (text && text.length < 500) {
                    try {
                        var j = JSON.parse(text);
                        if (j.data && j.data.message) msg += ' — ' + j.data.message;
                        else if (j.message) msg += ' — ' + j.message;
                    } catch (e) {
                        if (text.indexOf('</') !== -1) msg += ' — Server returned HTML; check PHP error log.';
                    }
                } else if (text && text.indexOf('</') !== -1) {
                    msg += ' — Server returned HTML; check PHP error log.';
                }
                $('#api-progress-status').text(msg);
                $('#api-fetch-catalog-btn').prop('disabled', false);
                $('#api-import-btn').prop('disabled', false);
                $('#api-update-pricing-btn').prop('disabled', false);
                fetchCatalogInProgress = false;
            }
        });
    }

    function processAPIBatch(offset, batchSize, imageBaseUrl, filterManufacturer, filterType, filterInStock, skipImages) {
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            timeout: 120000,
            data: {
                action: 'lipseys_api_import_start',
                nonce: lipseysImport.nonce,
                batch_size: batchSize,
                offset: offset,
                image_base_url: imageBaseUrl,
                filter_manufacturer: filterManufacturer,
                filter_type: filterType,
                filter_in_stock_only: filterInStock,
                api_skip_images: skipImages ? '1' : ''
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const progress = Math.round((data.offset / data.total) * 100);
                    
                    $('#api-progress-bar').css('width', progress + '%').text(progress + '%');
                    $('#api-processed-count').text(data.offset);
                    $('#api-total-count').text(data.total);
                    $('#api-fetched-count').text(data.stats.fetched || data.total);
                    $('#api-created-count').text(data.stats.created);
                    $('#api-updated-count').text(data.stats.updated);
                    $('#api-error-count').text(data.stats.errors);
                    $('#api-progress-status').text('Processing batch ' + Math.ceil(data.offset / batchSize) + '...');
                    
                    if (data.errors && data.errors.length > 0) {
                        $('#api-error-log').show();
                        const errorList = $('#api-error-list');
                        data.errors.forEach(error => {
                            errorList.append('<li>' + error + '</li>');
                        });
                    }
                    
                    if (data.is_complete) {
                        $('#api-progress-status').text('Complete!');
                        $('#api-fetch-catalog-btn').prop('disabled', false);
                        $('#api-import-btn').prop('disabled', false);
                        $('#api-update-pricing-btn').prop('disabled', false);
                        apiImportInProgress = false;
                        alert('API Import complete!\nFetched: ' + data.total + '\nCreated: ' + data.stats.created + '\nUpdated: ' + data.stats.updated);
                    } else {
                        // Continue with next batch
                        setTimeout(function() {
                            processAPIBatch(data.offset, batchSize, imageBaseUrl, filterManufacturer, filterType, filterInStock, skipImages);
                        }, 500); // Slight delay to avoid overwhelming server
                    }
                } else {
                    $('#api-progress-status').text('Error: ' + (response.data.message || 'Unknown error'));
                    $('#api-fetch-catalog-btn').prop('disabled', false);
                    $('#api-import-btn').prop('disabled', false);
                    $('#api-update-pricing-btn').prop('disabled', false);
                    apiImportInProgress = false;
                    
                    if (response.data.errors) {
                        $('#api-error-log').show();
                        const errorList = $('#api-error-list');
                        response.data.errors.forEach(error => {
                            errorList.append('<li style="color: red;">' + error + '</li>');
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                let msg = 'AJAX Error: ' + status;
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                var text = (xhr.responseText || '').trim();
                if (text) {
                    try {
                        var json = JSON.parse(text);
                        if (json.data && json.data.message) {
                            msg += ' — ' + json.data.message;
                            if (json.data.hint) msg += ' ' + json.data.hint;
                        } else if (json.message) {
                            msg += ' — ' + json.message;
                        }
                    } catch (e) {
                        if (xhr.status === 502) {
                            msg += ' — 502 = gateway timeout (request took too long). Use batch size 10 and a Manufacturer filter (e.g. Glock) for first run.';
                        } else if (text.indexOf('</') !== -1) {
                            msg += ' — Server returned HTML (PHP error or security block). Try batch size 10; check PHP error log.';
                        } else if (text.length <= 300) {
                            msg += ' — ' + text.replace(/\s+/g, ' ');
                        }
                    }
                } else if (xhr.status === 502) {
                    msg += ' — 502 = gateway timeout. Use batch size 10 and a Manufacturer filter (e.g. Glock).';
                } else if (status === 'timeout') {
                    msg += ' — Request timed out. Try batch size 10 or add a Manufacturer filter.';
                } else {
                    msg += ' — Refresh the page and try again; or try batch size 10.';
                }
                $('#api-progress-status').text(msg);
                $('#api-fetch-catalog-btn').prop('disabled', false);
                $('#api-import-btn').prop('disabled', false);
                $('#api-update-pricing-btn').prop('disabled', false);
                apiImportInProgress = false;
            }
        });
    }
    
    // Update Pricing & Inventory Only
    $('#api-update-pricing-btn').on('click', function() {
        if (apiImportInProgress) {
            alert('Import already in progress');
            return;
        }
        
        if (!confirm('Update pricing and inventory for existing products?')) {
            return;
        }
        
        apiImportInProgress = true;
        $(this).prop('disabled', true);
        $('#api-fetch-catalog-btn').prop('disabled', true);
        $('#api-import-btn').prop('disabled', true);
        $('#api-progress-section').show();
        $('#api-progress-status').text('Fetching pricing feed from API...');
        
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            data: {
                action: 'lipseys_api_update_pricing',
                nonce: lipseysImport.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#api-progress-status').text('Complete!');
                    $('#api-progress-bar').css('width', '100%').text('100%');
                    alert(response.data.message);
                } else {
                    $('#api-progress-status').text('Error: ' + (response.data.message || 'Unknown error'));
                }
                
                $('#api-fetch-catalog-btn').prop('disabled', false);
                $('#api-import-btn').prop('disabled', false);
                $('#api-update-pricing-btn').prop('disabled', false);
                apiImportInProgress = false;
            },
            error: function(xhr, status, error) {
                let msg = 'AJAX Error: ' + status;
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                if (xhr.responseText && xhr.responseText.length < 200) {
                    msg += ' — ' + xhr.responseText.replace(/\s+/g, ' ').trim();
                } else if (xhr.responseText && xhr.responseText.indexOf('</') !== -1) {
                    msg += ' — Server returned HTML (check PHP error log)';
                } else if (status === 'timeout') {
                    msg += ' — Request timed out.';
                }
                $('#api-progress-status').text(msg);
                $('#api-fetch-catalog-btn').prop('disabled', false);
                $('#api-import-btn').prop('disabled', false);
                $('#api-update-pricing-btn').prop('disabled', false);
                apiImportInProgress = false;
            }
        });
    });
    
    // Attach images: batch 1 recommended. Auto-continues until remaining is 0.
    // Bind via document so it works on API Import tab (button exists in DOM when tab=api).
    var attachImagesInProgress = false;
    jQuery(document).on('click', '#attach-images-btn', function() {
        if (attachImagesInProgress || apiImportInProgress || fetchCatalogInProgress) {
            alert('Another operation is in progress');
            return;
        }
        attachImagesInProgress = true;
        var $status = jQuery('#attach-images-status');
        jQuery('#attach-images-btn').prop('disabled', true);
        $status.text('Starting… (leave this tab open)');
        var imageBaseUrl = jQuery('#api_image_base_url').val() || '';
        var batchInput = jQuery('#attach-images-batch');
        var getBatchSize = function() {
            var n = batchInput.length ? parseInt(batchInput.val(), 10) : 1;
            return isNaN(n) || n < 1 ? 1 : Math.min(15, n);
        };
        function doAttachBatch() {
            jQuery.ajax({
                url: lipseysImport.ajax_url,
                type: 'POST',
                timeout: 90000,
                data: {
                    action: 'lipseys_attach_images',
                    nonce: lipseysImport.nonce,
                    batch_size: getBatchSize(),
                    image_base_url: imageBaseUrl
                },
                success: function(response) {
                    if (response.success) {
                        var d = response.data;
                        $status.text('Attached this batch: ' + d.attached + ' — remaining: ' + d.remaining + ' (leave tab open)');
                        if (d.is_complete || d.remaining <= 0) {
                            $status.text('Done. All products that had an image name now have thumbnails.');
                            jQuery('#attach-images-btn').prop('disabled', false);
                            attachImagesInProgress = false;
                        } else {
                            setTimeout(doAttachBatch, 400);
                        }
                    } else {
                        $status.text('Error: ' + (response.data.message || 'Unknown'));
                        jQuery('#attach-images-btn').prop('disabled', false);
                        attachImagesInProgress = false;
                    }
                },
                error: function(xhr, status) {
                    var msg = status === 'timeout' ? 'Request timed out.' : (xhr.status ? 'HTTP ' + xhr.status : status);
                    var hint = (xhr.status === 502 || xhr.status === 504 || status === 'timeout') ? ' Keep batch size 1 and click the button again to continue.' : ' Click again to continue.';
                    $status.text('Error: ' + msg + hint);
                    jQuery('#attach-images-btn').prop('disabled', false);
                    attachImagesInProgress = false;
                }
            });
        }
        doAttachBatch();
    });

    jQuery(document).on('click', '#attach-images-reset-failed-btn', function() {
        var btn = jQuery(this);
        if (btn.prop('disabled')) return;
        btn.prop('disabled', true);
        jQuery('#attach-images-status').text('Resetting…');
        jQuery.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            data: {
                action: 'lipseys_attach_images_reset_failed',
                nonce: lipseysImport.nonce
            },
            success: function(response) {
                if (response.success) {
                    jQuery('#attach-images-status').text('Cleared ' + (response.data.cleared || 0) + ' failed flags. You can run Attach images again.');
                } else {
                    jQuery('#attach-images-status').text('Error: ' + (response.data.message || 'Unknown'));
                }
                btn.prop('disabled', false);
            },
            error: function() {
                jQuery('#attach-images-status').text('Request failed.');
                btn.prop('disabled', false);
            }
        });
    });

    // Backfill TYPE from API (products with SKU but no _lipseys_type)
    var backfillTypeInProgress = false;
    $('#backfill-type-btn').on('click', function() {
        if (backfillTypeInProgress || apiImportInProgress || fetchCatalogInProgress || attachImagesInProgress) {
            alert('Another operation is in progress');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        backfillTypeInProgress = true;
        var batchSize = parseInt($('#backfill-type-batch').val(), 10) || 15;
        batchSize = Math.min(50, Math.max(5, batchSize));
        $('#backfill-type-status').text('Starting…');
        function doBackfill() {
            $.ajax({
                url: lipseysImport.ajax_url,
                type: 'POST',
                timeout: Math.min(120000, 20000 + batchSize * 2500),
                data: {
                    action: 'lipseys_backfill_type',
                    nonce: lipseysImport.nonce,
                    batch_size: batchSize
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var d = response.data;
                        $('#backfill-type-status').text('Backfilled ' + d.updated + ' — remaining: ' + d.remaining + ' (leave tab open)');
                        $('#backfill-type-count').text(d.remaining);
                        if (d.recategorize_count !== undefined) {
                            $('#recategorize-count').text(d.recategorize_count);
                        }
                        if (d.is_complete || d.remaining <= 0) {
                            $('#backfill-type-status').text('Done. ' + (d.message || 'No products missing TYPE.') + ' Run Recategorize by TYPE below.');
                            $('#backfill-type-count').text('0');
                            $btn.prop('disabled', false);
                            backfillTypeInProgress = false;
                        } else {
                            setTimeout(doBackfill, 600);
                        }
                    } else {
                        $('#backfill-type-status').text('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown'));
                        $btn.prop('disabled', false);
                        backfillTypeInProgress = false;
                    }
                },
                error: function(xhr, status) {
                    $('#backfill-type-status').text('Error: ' + (status === 'timeout' ? 'Timeout' : (xhr.status || status)) + '. Click again to continue.');
                    $btn.prop('disabled', false);
                    backfillTypeInProgress = false;
                }
            });
        }
        doBackfill();
    });

    // Recategorize by Lipsey's TYPE (Grips, Triggers, Holsters, etc.)
    var recategorizeInProgress = false;
    $('#recategorize-by-type-btn').on('click', function() {
        if (recategorizeInProgress || apiImportInProgress || fetchCatalogInProgress || attachImagesInProgress || backfillTypeInProgress) {
            alert('Another operation is in progress');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        recategorizeInProgress = true;
        var batchSize = parseInt($('#recategorize-batch').val(), 10) || 30;
        batchSize = Math.min(200, Math.max(10, batchSize));
        var totalUpdated = 0;
        $('#recategorize-status').text('Starting…');
        function doBatch(offset) {
            $.ajax({
                url: lipseysImport.ajax_url,
                type: 'POST',
                timeout: Math.min(90000, 15000 + batchSize * 1500),
                data: {
                    action: 'lipseys_recategorize_by_type',
                    nonce: lipseysImport.nonce,
                    batch_size: batchSize,
                    offset: offset || 0
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var d = response.data;
                        totalUpdated += d.updated;
                        if (d.is_complete || d.remaining <= 0) {
                            $('#recategorize-status').text('Done. ' + totalUpdated + ' products recategorized. Run Sync below if category pages are still empty.');
                            $btn.prop('disabled', false);
                            recategorizeInProgress = false;
                        } else {
                            $('#recategorize-status').text(d.updated + ' this batch (' + totalUpdated + ' total) — ' + d.remaining + ' left (leave tab open)');
                            setTimeout(function() { doBatch(d.next_offset); }, 400);
                        }
                    } else {
                        $('#recategorize-status').text('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown'));
                        $btn.prop('disabled', false);
                        recategorizeInProgress = false;
                    }
                },
                error: function(xhr, status) {
                    var msg = (status === 'timeout' ? 'Timeout' : (xhr.status === 502 ? '502' : (xhr.status || status)));
                    $('#recategorize-status').text('Error: ' + msg + '. Reduce batch size and click again.');
                    $btn.prop('disabled', false);
                    recategorizeInProgress = false;
                }
            });
        }
        doBatch(0);
    });

    // Sync Triggers/Grips/Holsters from accessory-* slugs
    $('#sync-accessory-categories-btn').on('click', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);
        $('#sync-accessory-status').text('Syncing…');
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            timeout: 120000,
            data: {
                action: 'lipseys_sync_accessory_categories',
                nonce: lipseysImport.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var msg = response.data.message || '';
                    if (response.data.details) {
                        var d = response.data.details;
                        if (d.Triggers !== undefined || d.Grips !== undefined || d.Holsters !== undefined) {
                            msg += ' (Triggers: ' + (d.Triggers || 0) + ', Grips: ' + (d.Grips || 0) + ', Holsters: ' + (d.Holsters || 0) + ')';
                        }
                    }
                    $('#sync-accessory-status').text(msg);
                } else {
                    $('#sync-accessory-status').text('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown'));
                }
                $btn.prop('disabled', false);
            },
            error: function(xhr, status) {
                $('#sync-accessory-status').text('Error: ' + (status === 'timeout' ? 'Timeout' : (xhr.status || status)));
                $btn.prop('disabled', false);
            }
        });
    });

    // Add to Triggers/Grips/Holsters by product name (title contains holster/trigger/grip)
    $('#sync-by-product-name-btn').on('click', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);
        $('#sync-by-name-status').text('Running…');
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            timeout: 120000,
            data: {
                action: 'lipseys_sync_by_product_name',
                nonce: lipseysImport.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#sync-by-name-status').text(response.data.message || '');
                } else {
                    $('#sync-by-name-status').text('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown'));
                }
                $btn.prop('disabled', false);
            },
            error: function(xhr, status) {
                $('#sync-by-name-status').text('Error: ' + (status === 'timeout' ? 'Timeout' : (xhr.status || status)));
                $btn.prop('disabled', false);
            }
        });
    });

    // Remove Zanders products (move to Trash)
    $('#remove-zanders-btn').on('click', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        if (!confirm('Move all products with _zanders_item_number to Trash? You can empty Trash later in WooCommerce → Products.')) return;
        $btn.prop('disabled', true);
        $('#remove-zanders-status').text('…');
        $.ajax({
            url: lipseysImport.ajax_url,
            type: 'POST',
            data: {
                action: 'lipseys_remove_zanders_products',
                nonce: lipseysImport.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#remove-zanders-status').text(response.data.message || 'Done.');
                    $('#zanders-product-count').text('0');
                } else {
                    $('#remove-zanders-status').text('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown'));
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                $('#remove-zanders-status').text('Error: ' + (xhr.status || 'Request failed'));
                $btn.prop('disabled', false);
            }
        });
    });
});
