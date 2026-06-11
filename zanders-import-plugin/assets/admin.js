jQuery(document).ready(function($) {
    let importInProgress = false;
    let currentFile = null;
    let currentLiveFile = null;
    let fileFormat = 'csv';
    
    // Check for existing files from automated sync on page load
    $.ajax({
        url: zandersImport.ajax_url,
        type: 'POST',
        data: {
            action: 'zanders_check_existing_files',
            nonce: zandersImport.nonce
        },
        success: function(response) {
            if (response.success && response.data.main_file) {
                currentFile = response.data.main_file;
                fileFormat = response.data.file_format || 'csv';
                if (response.data.live_file) {
                    currentLiveFile = response.data.live_file;
                }
                
                // Show notification that files were found
                if (response.data.message) {
                    let html = '<span style="color: green;">✓ ' + response.data.message + '</span>';
                    if (response.data.found_files && response.data.found_files.length > 0) {
                        html += '<br><small style="color: #666;">Files: ' + response.data.found_files.join(', ') + '</small>';
                    }
                    $('#download-result').html(html);
                }
            } else {
                // Show helpful message if files not found
                let html = '<span style="color: orange;">⚠ No files found from automated sync.</span>';
                if (response.data && response.data.contents && response.data.contents.length > 0) {
                    html += '<br><small style="color: #666;">Found in directory: ' + response.data.contents.join(', ') + '</small>';
                } else if (response.data && response.data.directory) {
                    html += '<br><small style="color: #666;">Checking: ' + response.data.directory + '</small>';
                }
                html += '<br><small>Files will be uploaded automatically by the sync script. You can also use "Manual File Upload" below.</small>';
                $('#download-result').html(html);
            }
        },
        error: function() {
            // Silent fail on page load - don't show error
        }
    });
    
    // Test FTP Connection
    $('#test-ftp-btn').on('click', function() {
        const host = $('#ftp_host').val();
        const username = $('#ftp_username').val();
        const password = $('#ftp_password').val();
        const folder = $('#ftp_folder').val();
        const port = $('#ftp_port').val() || 21;
        const useSsl = $('#ftp_use_ssl').is(':checked');
        const resultSpan = $('#ftp-test-result');
        
        if (!username || !password) {
            alert('Please enter FTP username and password');
            return;
        }
        
        resultSpan.html('<span style="color: #666;">Testing connection...</span>');
        
        $.ajax({
            url: zandersImport.ajax_url,
            type: 'POST',
            data: {
                action: 'zanders_test_ftp',
                nonce: zandersImport.nonce,
                host: host,
                username: username,
                password: password,
                folder: folder,
                port: port,
                use_ssl: useSsl
            },
            success: function(response) {
                if (response.success) {
                    let html = '<span style="color: green;">✓ ' + response.message + '</span>';
                    if (response.found_expected && response.found_expected.length > 0) {
                        html += '<br><small style="color: green;">Found expected files: ' + response.found_expected.join(', ') + '</small>';
                    }
                    if (response.files && response.files.length > 0) {
                        html += '<br><small>Sample files: ' + response.files.slice(0, 3).join(', ') + '</small>';
                    }
                    if (response.current_dir) {
                        html += '<br><small>Current directory: ' + response.current_dir + '</small>';
                    }
                    resultSpan.html(html);
                } else {
                    let html = '<span style="color: red;">✗ ' + response.message + '</span>';
                    if (response.diagnostics) {
                        html += '<br><details style="margin-top: 10px;"><summary style="cursor: pointer; color: #666;">View Diagnostics</summary><pre style="background: #f5f5f5; padding: 10px; margin-top: 5px; font-size: 11px;">';
                        html += JSON.stringify(response.diagnostics, null, 2);
                        html += '</pre></details>';
                    }
                    if (response.suggestion) {
                        html += '<br><small style="color: orange;">Suggestion: ' + response.suggestion + '</small>';
                    }
                    resultSpan.html(html);
                }
            },
            error: function(xhr, status, error) {
                resultSpan.html('<span style="color: red;">✗ Connection failed: ' + error + '</span>');
            }
        });
    });
    
    // Manual File Upload
    $('#upload-files-btn').on('click', function() {
        const form = $('#manual-upload-form')[0];
        const formData = new FormData(form);
        const resultSpan = $('#upload-result');
        const btn = $(this);
        const originalText = btn.text();
        
        if (!formData.get('manual_csv_file') || formData.get('manual_csv_file').size === 0) {
            alert('Please select a CSV file to upload');
            return;
        }
        
        formData.append('action', 'zanders_upload_files');
        formData.append('nonce', zandersImport.nonce);
        
        btn.prop('disabled', true).text('Uploading...');
        resultSpan.html('<span style="color: #666;">Uploading...</span>');
        
        $.ajax({
            url: zandersImport.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    currentFile = response.data.main_file;
                    fileFormat = 'csv';
                    
                    // Set live file if uploaded
                    if (response.data.files.length > 1) {
                        currentLiveFile = currentFile.replace('ZandersInv.csv', 'LiveInv.csv');
                    }
                    
                    resultSpan.html('<span style="color: green; font-weight: bold;">✓ ' + response.data.message + '</span>');
                    $('#upload-success').show();
                    alert('Files uploaded successfully! You can now use "Preview Inventory" and "Start Import" buttons below.');
                } else {
                    resultSpan.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                    $('#upload-success').hide();
                }
                btn.prop('disabled', false).text('📤 Upload Files');
            },
            error: function() {
                resultSpan.html('<span style="color: red;">✗ Upload failed</span>');
                btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Download Files from FTP
    $('#download-files-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        
        btn.prop('disabled', true).text('Downloading...');
        
        $.ajax({
            url: zandersImport.ajax_url,
            type: 'POST',
            data: {
                action: 'zanders_download_files',
                nonce: zandersImport.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentFile = response.data.main_file;
                    fileFormat = $('#file_format').val();
                    
                    // Set live file if available
                    if (response.data.files.includes('LiveInv.csv')) {
                        currentLiveFile = currentFile.replace('ZandersInv.csv', 'LiveInv.csv');
                    } else if (response.data.files.includes('Qtypricingout.xml')) {
                        currentLiveFile = currentFile.replace('ZandersInv.xml', 'Qtypricingout.xml');
                    }
                    
                    alert('Files downloaded successfully!\n\n' + response.data.message);
                    loadPreview();
                } else {
                    alert('Error: ' + response.data.message);
                    if (response.data.errors && response.data.errors.length > 0) {
                        console.error('FTP Errors:', response.data.errors);
                    }
                }
                btn.prop('disabled', false).text(originalText);
            },
            error: function() {
                alert('AJAX Error: Failed to download files');
                btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Preview Inventory
    $('#preview-btn').on('click', function() {
        // If no file set, try to find existing files first
        if (!currentFile) {
            $.ajax({
                url: zandersImport.ajax_url,
                type: 'POST',
                data: {
                    action: 'zanders_check_existing_files',
                    nonce: zandersImport.nonce
                },
                success: function(response) {
                    if (response.success && response.data.main_file) {
                        currentFile = response.data.main_file;
                        fileFormat = response.data.file_format || 'csv';
                        if (response.data.live_file) {
                            currentLiveFile = response.data.live_file;
                        }
                        loadPreview();
                    } else {
                        alert('No files found. Please download files from FTP or use Manual File Upload first.');
                    }
                },
                error: function() {
                    alert('No files found. Please download files from FTP or use Manual File Upload first.');
                }
            });
            return;
        }
        loadPreview();
    });
    
    function loadPreview() {
        if (!currentFile) {
            alert('No file available. Please download files from FTP or use Manual File Upload first.');
            return;
        }
        
        // Get filter values
        const filters = {
            filter_min_quantity: $('#filter_min_quantity').val() || 0,
            filter_product_type: $('#filter_product_type').val() || '',
            filter_min_price: $('#filter_min_price').val() || 0,
            filter_max_price: $('#filter_max_price').val() || 0,
            filter_manufacturer: $('#filter_manufacturer').val() || ''
        };
        
        $.ajax({
            url: zandersImport.ajax_url,
            type: 'POST',
            data: {
                action: 'zanders_import_preview',
                nonce: zandersImport.nonce,
                file_path: currentFile,
                file_format: fileFormat,
                live_file: currentLiveFile || '',
                ...filters
            },
            success: function(response) {
                console.log('Preview Response:', response);
                console.log('Response Data:', response.data);
                
                if (response.success) {
                    // Log debug info if present
                    if (response.data.debug_info) {
                        console.log('DEBUG INFO FOUND:', response.data.debug_info);
                    } else {
                        console.log('NO DEBUG INFO in response');
                    }
                    displayPreview(response.data);
                } else {
                    let errorMsg = 'Error: ' + response.data.message;
                    if (response.data.debug_info) {
                        errorMsg += '\n\nDebug Info:\n' + JSON.stringify(response.data.debug_info, null, 2);
                    }
                    alert(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('Preview error:', xhr, status, error);
                console.error('XHR Response:', xhr.responseText);
                alert('Error loading preview: ' + error);
            }
        });
    }
    
    function displayPreview(data) {
        console.log('Displaying preview with data:', data);
        
        let html = '<p><strong>Total Products Available:</strong> ' + data.total_count;
        if (data.filters_applied) {
            html += ' <span style="color: orange;">(filters applied)</span>';
        }
        html += '</p>';
        
        // Show unique products count if available (helps understand if there are duplicates)
        if (data.unique_products !== null && data.unique_products !== undefined) {
            if (data.unique_products === data.total_count) {
                html += '<p style="color: #28a745; font-size: 0.9em;">✓ All ' + data.total_count + ' products are unique (no duplicates)</p>';
            } else {
                html += '<p style="color: #856404; font-size: 0.9em;">ℹ️ <strong>' + data.unique_products + ' unique products</strong> (out of ' + data.total_count + ' rows). ';
                html += 'Duplicates will update the same product during import.</p>';
            }
        }
        
        // Show debug info if 0 products found
        console.log('Checking if debug_info exists:', data.debug_info ? 'YES' : 'NO');
        if (data.total_count === 0) {
            html += '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 4px;">';
            html += '<strong style="color: #856404;">⚠️ No products found</strong><br>';
            
            if (data.debug_info) {
                // Show CSV parsing stats
                if (data.debug_info.csv_stats) {
                    html += '<p style="margin: 10px 0 5px 0;"><strong>CSV File Stats:</strong></p>';
                    html += '<ul style="margin: 5px 0; padding-left: 20px;">';
                    html += '<li>Total rows in CSV: ' + data.debug_info.csv_stats.total_rows_read + '</li>';
                    html += '<li>Rows with Avail="Y": ' + data.debug_info.csv_stats.rows_with_avail_y + '</li>';
                    html += '<li>Products after availability filter: ' + data.debug_info.csv_stats.rows_after_avail_filter + '</li>';
                    html += '</ul>';
                }
                
                html += '<p style="margin: 10px 0 5px 0;"><strong>Before filters:</strong> ' + data.debug_info.total_before_filter + ' products</p>';
                
                if (data.debug_info.filters_applied && Object.keys(data.debug_info.filters_applied).length > 0) {
                    html += '<p style="margin: 5px 0;"><strong>Filters applied:</strong></p>';
                    html += '<ul style="margin: 5px 0; padding-left: 20px;">';
                    if (data.debug_info.filters_applied.manufacturers) {
                        html += '<li>Manufacturer: "' + data.debug_info.filters_applied.manufacturers[0] + '"</li>';
                    }
                    if (data.debug_info.filters_applied.min_quantity) {
                        html += '<li>Min Quantity: ' + data.debug_info.filters_applied.min_quantity + '</li>';
                    }
                    if (data.debug_info.filters_applied.product_type) {
                        html += '<li>Product Type: ' + data.debug_info.filters_applied.product_type + '</li>';
                    }
                    if (data.debug_info.filters_applied.limit) {
                        html += '<li>Limit: ' + data.debug_info.filters_applied.limit + '</li>';
                    }
                    html += '</ul>';
                }
                
                if (data.debug_info.sample_mfg_values && data.debug_info.sample_mfg_values.length > 0) {
                    html += '<p style="margin: 10px 0 5px 0;"><strong>Sample Manufacturer values in CSV:</strong></p>';
                    html += '<ul style="margin: 5px 0; padding-left: 20px;">';
                    data.debug_info.sample_mfg_values.slice(0, 15).forEach(function(mfg) {
                        html += '<li>' + mfg + '</li>';
                    });
                    html += '</ul>';
                } else if (data.debug_info.total_before_filter === 0) {
                    html += '<p style="margin: 10px 0 5px 0; color: #856404;"><strong>⚠️ No products found in CSV file at all.</strong></p>';
                    html += '<p style="margin: 5px 0;">This could mean:</p>';
                    html += '<ul style="margin: 5px 0; padding-left: 20px;">';
                    html += '<li>The CSV file is empty</li>';
                    html += '<li>No products have Avail = "Y" (for ZandersInv.csv)</li>';
                    html += '<li>No products have Qty > 0 (for LiveInv.csv)</li>';
                    html += '</ul>';
                }
                
                if (data.debug_info.sample_items && data.debug_info.sample_items.length > 0) {
                    html += '<p style="margin: 10px 0 5px 0;"><strong>Sample items from CSV:</strong></p>';
                    html += '<table style="width: 100%; border-collapse: collapse; margin: 5px 0;">';
                    html += '<thead><tr style="background: #f0f0f0;"><th style="padding: 5px; border: 1px solid #ddd;">Item#</th><th style="padding: 5px; border: 1px solid #ddd;">MFG</th><th style="padding: 5px; border: 1px solid #ddd;">Desc1</th><th style="padding: 5px; border: 1px solid #ddd;">Category</th><th style="padding: 5px; border: 1px solid #ddd;">Avail</th></tr></thead>';
                    html += '<tbody>';
                    data.debug_info.sample_items.forEach(function(item) {
                        html += '<tr>';
                        html += '<td style="padding: 5px; border: 1px solid #ddd;">' + (item['Item#'] || '') + '</td>';
                        html += '<td style="padding: 5px; border: 1px solid #ddd;">' + (item['MFG'] || '') + '</td>';
                        html += '<td style="padding: 5px; border: 1px solid #ddd;">' + (item['Desc1'] || '') + '</td>';
                        html += '<td style="padding: 5px; border: 1px solid #ddd;">' + (item['Category'] || '') + '</td>';
                        html += '<td style="padding: 5px; border: 1px solid #ddd;">' + (item['Avail'] || '') + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }
                
                if (data.debug_info.filters_applied && data.debug_info.filters_applied.manufacturers && data.debug_info.filters_applied.manufacturers.length > 0) {
                    html += '<p style="margin: 10px 0 5px 0; color: #856404;"><strong>💡 Tip:</strong> You searched for "' + data.debug_info.filters_applied.manufacturers[0] + '". ';
                    html += 'Check the manufacturer values above to see the exact format in your CSV file.</p>';
                }
            } else {
                html += '<p style="margin: 10px 0 5px 0;">No debug information available. Try removing filters to see if products are found.</p>';
            }
            
            html += '</div>';
        }
        
        html += '<p class="description">This is the number of products that will be imported based on your filters.</p>';
        html += '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr>';
        
        // Show key columns
        const keyColumns = ['Item#', 'Desc1', 'MFG', 'Category', 'Price1', 'Qty1', 'MSRP'];
        const headers = data.headers.length > 0 ? data.headers : Object.keys(data.preview[0] || {});
        
        keyColumns.forEach(col => {
            if (headers.includes(col)) {
                html += '<th>' + col + '</th>';
            }
        });
        html += '</tr></thead><tbody>';
        
        data.preview.forEach(row => {
            html += '<tr>';
            keyColumns.forEach(col => {
                if (headers.includes(col)) {
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
            alert('Please download files from FTP first');
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
        
        const batchSize = parseInt($('#batch_size').val()) || 50;
        const downloadImages = $('#download_images').is(':checked') ? 1 : 0;
        
        // Get filter values
        const filters = {
            filter_min_quantity: $('#filter_min_quantity').val() || 0,
            filter_product_type: $('#filter_product_type').val() || '',
            filter_min_price: $('#filter_min_price').val() || 0,
            filter_max_price: $('#filter_max_price').val() || 0,
            filter_manufacturer: $('#filter_manufacturer').val() || '',
            filter_limit: $('#filter_limit').val() || 0
        };
        
        processBatch(currentFile, 0, batchSize, downloadImages, filters);
    });
    
    function processBatch(filePath, offset, batchSize, downloadImages, filters) {
        $.ajax({
            url: zandersImport.ajax_url,
            type: 'POST',
            data: {
                action: 'zanders_import_process',
                nonce: zandersImport.nonce,
                file_path: filePath,
                file_format: fileFormat,
                live_file: currentLiveFile || '',
                batch_size: batchSize,
                offset: offset,
                download_images: downloadImages,
                ...filters
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
                            processBatch(filePath, data.offset, batchSize, downloadImages, filters);
                        }, 100);
                    }
                } else {
                    $('#progress-status').text('Error: ' + response.data.message);
                    $('#import-btn').prop('disabled', false);
                    importInProgress = false;
                }
            },
            error: function() {
                $('#progress-status').text('AJAX Error');
                $('#import-btn').prop('disabled', false);
                importInProgress = false;
            }
        });
    }
});
