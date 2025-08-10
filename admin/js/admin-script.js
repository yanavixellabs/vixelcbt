/**
 * VixelCBT Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        VixelCBTAdmin.init();
    });
    
    // Main admin object
    window.VixelCBTAdmin = {
        
        init: function() {
            this.initDashboard();
            this.initTables();
            this.initForms();
            this.initModals();
            this.initBulkActions();
            this.initFileUploads();
            this.initDatePickers();
            this.initTooltips();
        },
        
        // Dashboard functionality
        initDashboard: function() {
            // Animate stat cards on load
            $('.vixelcbt-stat-card').each(function(index) {
                $(this).delay(index * 100).animate({
                    opacity: 1,
                    transform: 'translateY(0)'
                }, 500);
            });
            
            // Quick action hover effects
            $('.quick-action-btn').hover(
                function() {
                    $(this).find('.dashicons').addClass('animated');
                },
                function() {
                    $(this).find('.dashicons').removeClass('animated');
                }
            );
        },
        
        // Table functionality
        initTables: function() {
            // Make tables responsive
            $('.widefat').wrap('<div class="table-responsive"></div>');
            
            // Add sorting functionality
            $('.widefat th').on('click', function() {
                if ($(this).hasClass('sortable')) {
                    const table = $(this).closest('table');
                    const index = $(this).index();
                    const rows = table.find('tbody tr').get();
                    
                    rows.sort(function(a, b) {
                        const aVal = $(a).children('td').eq(index).text();
                        const bVal = $(b).children('td').eq(index).text();
                        
                        if ($(this).hasClass('sort-desc')) {
                            return aVal.localeCompare(bVal);
                        } else {
                            return bVal.localeCompare(aVal);
                        }
                    }.bind(this));
                    
                    $.each(rows, function(index, row) {
                        table.children('tbody').append(row);
                    });
                    
                    $(this).toggleClass('sort-desc');
                }
            });
            
            // Row selection
            $('.widefat tbody tr').on('click', function(e) {
                if (!$(e.target).is('input, button, a')) {
                    $(this).toggleClass('selected');
                    $(this).find('input[type="checkbox"]').prop('checked', $(this).hasClass('selected'));
                }
            });
            
            // Select all functionality
            $('.widefat thead input[type="checkbox"]').on('change', function() {
                const isChecked = $(this).is(':checked');
                $(this).closest('table').find('tbody input[type="checkbox"]').prop('checked', isChecked);
                $(this).closest('table').find('tbody tr').toggleClass('selected', isChecked);
            });
        },
        
        // Form functionality
        initForms: function() {
            // Form validation
            $('form').on('submit', function(e) {
                const form = $(this);
                let isValid = true;
                
                // Check required fields
                form.find('[required]').each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('error');
                        isValid = false;
                    } else {
                        $(this).removeClass('error');
                    }
                });
                
                // Email validation
                form.find('input[type="email"]').each(function() {
                    const email = $(this).val();
                    if (email && !VixelCBTAdmin.isValidEmail(email)) {
                        $(this).addClass('error');
                        isValid = false;
                    } else {
                        $(this).removeClass('error');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    VixelCBTAdmin.showNotice('Please fill in all required fields correctly.', 'error');
                    return false;
                }
            });
            
            // Auto-save functionality for long forms
            $('form.auto-save').each(function() {
                const form = $(this);
                let saveTimer;
                
                form.find('input, textarea, select').on('change input', function() {
                    clearTimeout(saveTimer);
                    saveTimer = setTimeout(function() {
                        VixelCBTAdmin.autoSaveForm(form);
                    }, 2000);
                });
            });
            
            // Dynamic form fields
            $('.add-field-btn').on('click', function() {
                const template = $(this).data('template');
                const container = $(this).data('container');
                const newField = $(template).clone();
                
                // Update field names and IDs
                const index = $(container + ' .dynamic-field').length;
                newField.find('input, select, textarea').each(function() {
                    const name = $(this).attr('name');
                    const id = $(this).attr('id');
                    
                    if (name) {
                        $(this).attr('name', name.replace('[0]', '[' + index + ']'));
                    }
                    if (id) {
                        $(this).attr('id', id.replace('_0', '_' + index));
                    }
                });
                
                $(container).append(newField);
            });
            
            // Remove dynamic fields
            $(document).on('click', '.remove-field-btn', function() {
                $(this).closest('.dynamic-field').remove();
            });
        },
        
        // Modal functionality
        initModals: function() {
            // Open modal
            $('[data-modal]').on('click', function(e) {
                e.preventDefault();
                const modalId = $(this).data('modal');
                VixelCBTAdmin.openModal(modalId);
            });
            
            // Close modal
            $('.modal-close, .modal-overlay').on('click', function() {
                VixelCBTAdmin.closeModal();
            });
            
            // Prevent modal content click from closing modal
            $('.modal-content').on('click', function(e) {
                e.stopPropagation();
            });
            
            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) {
                    VixelCBTAdmin.closeModal();
                }
            });
        },
        
        // Bulk actions
        initBulkActions: function() {
            $('.bulk-action-btn').on('click', function() {
                const action = $(this).data('action');
                const selectedItems = $('.widefat tbody input[type="checkbox"]:checked');
                
                if (selectedItems.length === 0) {
                    VixelCBTAdmin.showNotice('Please select items to perform bulk action.', 'warning');
                    return;
                }
                
                const itemIds = [];
                selectedItems.each(function() {
                    itemIds.push($(this).val());
                });
                
                if (confirm(`Are you sure you want to ${action} ${itemIds.length} items?`)) {
                    VixelCBTAdmin.performBulkAction(action, itemIds);
                }
            });
        },
        
        // File uploads
        initFileUploads: function() {
            // Drag and drop file upload
            $('.file-upload-area').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            }).on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            }).on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                VixelCBTAdmin.handleFileUpload(files, $(this));
            });
            
            // File input change
            $('.file-input').on('change', function() {
                const files = this.files;
                const uploadArea = $(this).closest('.file-upload-area');
                VixelCBTAdmin.handleFileUpload(files, uploadArea);
            });
        },
        
        // Date pickers
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.date-picker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
                
                $('.datetime-picker').datetimepicker({
                    dateFormat: 'yy-mm-dd',
                    timeFormat: 'HH:mm:ss',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },
        
        // Tooltips
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const tooltip = $('<div class="tooltip">' + $(this).data('tooltip') + '</div>');
                $('body').append(tooltip);
                
                $(this).hover(
                    function(e) {
                        tooltip.css({
                            top: e.pageY - tooltip.outerHeight() - 10,
                            left: e.pageX - tooltip.outerWidth() / 2
                        }).fadeIn(200);
                    },
                    function() {
                        tooltip.fadeOut(200);
                    }
                ).mousemove(function(e) {
                    tooltip.css({
                        top: e.pageY - tooltip.outerHeight() - 10,
                        left: e.pageX - tooltip.outerWidth() / 2
                    });
                });
            });
        },
        
        // Utility functions
        openModal: function(modalId) {
            $('#' + modalId).addClass('active');
            $('body').addClass('modal-open');
        },
        
        closeModal: function() {
            $('.modal').removeClass('active');
            $('body').removeClass('modal-open');
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            const notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual dismiss
            notice.find('.notice-dismiss').on('click', function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },
        
        autoSaveForm: function(form) {
            const formData = form.serialize();
            
            $.ajax({
                url: vixelcbt_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=vixelcbt_auto_save&nonce=' + vixelcbt_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        VixelCBTAdmin.showSaveIndicator(form);
                    }
                }
            });
        },
        
        showSaveIndicator: function(form) {
            const indicator = $('<div class="save-indicator">Saved</div>');
            form.append(indicator);
            
            setTimeout(function() {
                indicator.fadeOut(function() {
                    $(this).remove();
                });
            }, 2000);
        },
        
        performBulkAction: function(action, itemIds) {
            $.ajax({
                url: vixelcbt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vixelcbt_bulk_action',
                    bulk_action: action,
                    item_ids: itemIds,
                    nonce: vixelcbt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VixelCBTAdmin.showNotice(response.data.message, 'success');
                        location.reload(); // Refresh page to show changes
                    } else {
                        VixelCBTAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    VixelCBTAdmin.showNotice('An error occurred while performing bulk action.', 'error');
                }
            });
        },
        
        handleFileUpload: function(files, uploadArea) {
            if (files.length === 0) return;
            
            const formData = new FormData();
            
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            
            formData.append('action', 'vixelcbt_file_upload');
            formData.append('nonce', vixelcbt_ajax.nonce);
            
            // Show upload progress
            const progressBar = $('<div class="upload-progress"><div class="progress-bar"></div></div>');
            uploadArea.append(progressBar);
            
            $.ajax({
                url: vixelcbt_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            progressBar.find('.progress-bar').css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    progressBar.remove();
                    
                    if (response.success) {
                        VixelCBTAdmin.showNotice('Files uploaded successfully.', 'success');
                        // Handle successful upload
                    } else {
                        VixelCBTAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    progressBar.remove();
                    VixelCBTAdmin.showNotice('Upload failed. Please try again.', 'error');
                }
            });
        },
        
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = function() {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Add CSS animations
    const style = $(`
        <style>
            .animated {
                animation: bounce 0.5s ease-in-out;
            }
            
            @keyframes bounce {
                0%, 20%, 60%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-10px);
                }
                80% {
                    transform: translateY(-5px);
                }
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .widefat tr.selected {
                background-color: #e3f2fd;
            }
            
            .widefat th.sortable {
                cursor: pointer;
                user-select: none;
            }
            
            .widefat th.sortable:hover {
                background-color: #f0f0f0;
            }
            
            .widefat th.sort-desc:after {
                content: ' ↓';
            }
            
            .widefat th.sortable:not(.sort-desc):after {
                content: ' ↑';
            }
            
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 100000;
            }
            
            .modal.active {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .modal-content {
                background: white;
                padding: 20px;
                border-radius: 8px;
                max-width: 90%;
                max-height: 90%;
                overflow-y: auto;
            }
            
            .modal-close {
                float: right;
                font-size: 24px;
                cursor: pointer;
                line-height: 1;
            }
            
            .file-upload-area {
                border: 2px dashed #ccc;
                border-radius: 8px;
                padding: 40px;
                text-align: center;
                transition: all 0.3s ease;
            }
            
            .file-upload-area.dragover {
                border-color: #0073aa;
                background-color: #f0f8ff;
            }
            
            .upload-progress {
                width: 100%;
                height: 20px;
                background-color: #f0f0f0;
                border-radius: 10px;
                overflow: hidden;
                margin-top: 10px;
            }
            
            .progress-bar {
                height: 100%;
                background-color: #0073aa;
                transition: width 0.3s ease;
            }
            
            .save-indicator {
                position: fixed;
                top: 50px;
                right: 20px;
                background: #46b450;
                color: white;
                padding: 10px 20px;
                border-radius: 4px;
                z-index: 10000;
            }
            
            .tooltip {
                position: absolute;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 10000;
                display: none;
                max-width: 200px;
            }
            
            .tooltip:before {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: #333 transparent transparent transparent;
            }
            
            .error {
                border-color: #dc3232 !important;
                box-shadow: 0 0 2px rgba(220, 50, 50, 0.8);
            }
            
            .dynamic-field {
                position: relative;
                margin-bottom: 15px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .remove-field-btn {
                position: absolute;
                top: 5px;
                right: 5px;
                background: #dc3232;
                color: white;
                border: none;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                cursor: pointer;
                font-size: 12px;
            }
            
            body.modal-open {
                overflow: hidden;
            }
            
            body.vixelcbt-loading {
                cursor: wait;
            }
            
            body.vixelcbt-loading * {
                pointer-events: none;
            }
        </style>
    `);
    
    $('head').append(style);
    
})(jQuery);