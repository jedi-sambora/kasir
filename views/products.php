<?php
/**
 * Products Management Page (Admin only)
 * Kendo Grid with CRUD operations + Image Upload
 */
requireLogin();
requireAdmin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manajemen Produk - Kelola katalog menu kafe">
    <title>Produk — Café POS</title>
    <link rel="stylesheet" href="/kasir/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.common-material.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.material.min.css">
    <link rel="stylesheet" href="/kasir/assets/css/app.css">
    <style>
    .image-upload-area {
        border: 2px dashed var(--border);
        border-radius: var(--radius-md);
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all var(--transition-fast);
        background: var(--bg-hover);
        position: relative;
    }
    .image-upload-area:hover {
        border-color: var(--primary);
        background: var(--primary-50);
    }
    .image-upload-area.drag-over {
        border-color: var(--primary);
        background: var(--primary-50);
        box-shadow: 0 0 0 4px rgba(139, 94, 60, 0.1);
    }
    .image-upload-area.has-image {
        border-style: solid;
        border-color: var(--success);
        padding: 8px;
    }
    .image-upload-area .upload-icon {
        font-size: 2rem;
        color: var(--text-muted);
        margin-bottom: 8px;
    }
    .image-upload-area .upload-text {
        font-size: 0.82rem;
        color: var(--text-muted);
    }
    .image-upload-area .preview-img {
        max-width: 100%;
        max-height: 150px;
        border-radius: var(--radius-sm);
        object-fit: cover;
    }
    .image-upload-area .remove-img {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--danger);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        z-index: 2;
    }
    .grid-product-img {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-sm);
        object-fit: cover;
        background: var(--bg-hover);
    }
    .grid-product-img-placeholder {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-sm);
        background: var(--bg-hover);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        font-size: 1.2rem;
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="page-content">
        <div class="page-title-bar">
            <h1><i class="fas fa-mug-hot" style="color:var(--primary);"></i>&nbsp; Manajemen Produk</h1>
            <div style="display:flex;gap:12px;">
                <button class="btn btn-outline" onclick="openCategoryManager()">
                    <i class="fas fa-tags"></i> Kelola Kategori
                </button>
                <button class="btn btn-primary" onclick="addProduct()">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>
        </div>

        <div id="product-grid"></div>
    </div>

    <!-- Hidden file input (outside Kendo Window to avoid DOM issues) -->
    <input type="file" id="pf-image-file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">

    <!-- Product Form Window -->
    <div id="product-form-window" style="display:none;">
        <div class="payment-content">
            <form id="product-form">
                <input type="hidden" id="pf-id">
                <input type="hidden" id="pf-image-url">
                
                <!-- Image Upload -->
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Gambar Produk</label>
                    <div class="image-upload-area" id="image-upload-area">
                        <div id="image-placeholder">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <div class="upload-text">Klik atau seret gambar ke sini<br><small>JPG, PNG, WebP — Maks 2MB</small></div>
                        </div>
                        <div id="image-preview" style="display:none;">
                            <img class="preview-img" id="preview-img" src="" alt="Preview">
                            <button type="button" class="remove-img" id="btn-remove-img"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Kategori</label>
                    <select id="pf-category" class="form-input" style="padding:12px 16px;width:100%;" required></select>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Nama Produk</label>
                    <input type="text" id="pf-name" class="form-input" style="width:100%;" placeholder="Nama produk" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Harga (Rp)</label>
                    <input type="number" id="pf-price" class="form-input" style="width:100%;" placeholder="0" required min="0">
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Status</label>
                    <select id="pf-status" class="form-input" style="padding:12px 16px;width:100%;">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="button" class="btn btn-outline" style="flex:1;justify-content:center;" onclick="closeProductForm()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;" id="pf-submit">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Manager Window -->
    <div id="category-window" style="display:none;">
        <div class="payment-content">
            <div style="display:flex;gap:8px;margin-bottom:16px;">
                <input type="text" id="new-category" class="form-input" style="flex:1;" placeholder="Nama kategori baru">
                <button class="btn btn-primary" onclick="addCategory()"><i class="fas fa-plus"></i></button>
            </div>
            <div id="category-list"></div>
            <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeCategoryManager()">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>

    <script src="/kasir/assets/js/jquery.min.js"></script>
    <script src="/kasir/assets/kendo/js/kendo.all.min.js"></script>
    <script>
    var productFormWindow = null;
    var categoryWindow = null;
    var allCategories = [];
    var pendingImageFile = null;

    function formatRupiah(num) {
        return 'Rp ' + parseInt(num).toLocaleString('id-ID');
    }

    function showToast(message, type) {
        var toast = $('<div class="toast-notification ' + (type || 'info') + '">' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() { toast.remove(); }, 3000);
    }

    // ============================================
    // Image Upload Handling
    // ============================================
    function processFile(file) {
        // Validate size
        var maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            showToast('Ukuran file maksimal 2MB', 'error');
            return;
        }
        // Validate type
        var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (allowedTypes.indexOf(file.type) === -1) {
            showToast('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP', 'error');
            return;
        }

        pendingImageFile = file;

        // Show preview
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#preview-img').attr('src', e.target.result);
            $('#image-placeholder').hide();
            $('#image-preview').show();
            $('#image-upload-area').addClass('has-image');
        };
        reader.readAsDataURL(file);
    }

    function clearImagePreview() {
        pendingImageFile = null;
        $('#pf-image-file').val('');
        $('#pf-image-url').val('');
        $('#preview-img').attr('src', '');
        $('#image-preview').hide();
        $('#image-placeholder').show();
        $('#image-upload-area').removeClass('has-image');
    }

    // Bind events using delegation (Kendo Window moves DOM, so direct binding breaks)
    $(document).ready(function() {
        var $fileInput = $('#pf-image-file');

        // Click upload area to browse
        $(document).on('click', '#image-upload-area', function(e) {
            if ($(e.target).closest('.remove-img').length) return;
            $fileInput[0].click();
        });

        // File input change
        $fileInput.on('change', function() {
            if (this.files && this.files[0]) {
                processFile(this.files[0]);
            }
        });

        // Remove button
        $(document).on('click', '#btn-remove-img', function(e) {
            e.stopPropagation();
            e.preventDefault();
            clearImagePreview();
        });

        // Drag and drop - use delegation
        $(document).on('dragover dragenter', '#image-upload-area', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave dragend', '#image-upload-area', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });

        $(document).on('drop', '#image-upload-area', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');

            var files = e.originalEvent.dataTransfer.files;
            if (files && files.length > 0) {
                processFile(files[0]);
            }
        });
    });

    function uploadImage(callback) {
        if (!pendingImageFile) {
            callback($('#pf-image-url').val() || null);
            return;
        }

        var formData = new FormData();
        formData.append('image', pendingImageFile);

        $.ajax({
            url: '/kasir/api/upload.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    callback(res.image_url);
                } else {
                    showToast(res.message, 'error');
                    callback(null);
                }
            },
            error: function() {
                showToast('Gagal mengupload gambar', 'error');
                callback(null);
            }
        });
    }

    // ============================================
    // Grid
    // ============================================
    function loadGrid() {
        $.getJSON('/kasir/api/products.php?action=all', function(res) {
            if (res.success) {
                allCategories = res.categories;
                initGrid(res.data);
            }
        });
    }

    function initGrid(data) {
        var $grid = $('#product-grid');
        if ($grid.data('kendoGrid')) {
            $grid.data('kendoGrid').destroy();
            $grid.empty();
        }

        $grid.kendoGrid({
            dataSource: {
                data: data,
                pageSize: 15,
                schema: {
                    model: {
                        id: "id",
                        fields: {
                            id: { type: "number" },
                            nama_produk: { type: "string" },
                            category_name: { type: "string" },
                            harga: { type: "number" },
                            image_url: { type: "string" },
                            status: { type: "number" }
                        }
                    }
                }
            },
            sortable: true,
            filterable: true,
            pageable: { pageSize: 15, buttonCount: 5 },
            columns: [
                { field: "id", title: "ID", width: 70 },
                { title: "Gambar", width: 80,
                    template: function(d) {
                        if (d.image_url) {
                            return '<img src="' + d.image_url + '" class="grid-product-img" onerror="this.outerHTML=\'<div class=grid-product-img-placeholder><i class=fas fa-image></i></div>\'">';
                        }
                        return '<div class="grid-product-img-placeholder"><i class="fas fa-image"></i></div>';
                    }
                },
                { field: "category_name", title: "Kategori", width: 150 },
                { field: "nama_produk", title: "Nama Produk" },
                { field: "harga", title: "Harga", width: 150, template: "#= 'Rp ' + parseInt(harga).toLocaleString('id-ID') #" },
                { field: "status", title: "Status", width: 120,
                    template: function(d) {
                        return d.status == 1 ? '<span class="badge badge-active">Aktif</span>' : '<span class="badge badge-inactive">Nonaktif</span>';
                    }
                },
                { title: "Aksi", width: 180,
                    template: function(d) {
                        return '<button class="grid-action-btn" onclick="editProduct(' + d.id + ')"><i class="fas fa-edit"></i> Edit</button>' +
                               '<button class="grid-action-btn btn-delete" onclick="deleteProduct(' + d.id + ', \'' + d.nama_produk.replace(/'/g, "\\'") + '\')"><i class="fas fa-trash"></i> Hapus</button>';
                    }
                }
            ]
        });
    }

    // ============================================
    // Product CRUD
    // ============================================
    function addProduct() {
        $('#pf-id').val('');
        $('#pf-name').val('');
        $('#pf-price').val('');
        $('#pf-status').val('1');
        clearImagePreview();
        populateCategorySelect();
        openProductForm('Tambah Produk');
    }

    function editProduct(id) {
        $.getJSON('/kasir/api/products.php?action=all', function(res) {
            var product = res.data.find(function(p) { return p.id == id; });
            if (!product) return;
            populateCategorySelect(product.category_id);
            $('#pf-id').val(product.id);
            $('#pf-name').val(product.nama_produk);
            $('#pf-price').val(product.harga);
            $('#pf-image-url').val(product.image_url || '');
            $('#pf-image-file').val('');
            $('#pf-status').val(product.status);
            pendingImageFile = null;

            // Show existing image or placeholder
            if (product.image_url) {
                $('#preview-img').attr('src', product.image_url);
                $('#image-placeholder').hide();
                $('#image-preview').show();
                $('#image-upload-area').addClass('has-image');
            } else {
                $('#preview-img').attr('src', '');
                $('#image-preview').hide();
                $('#image-placeholder').show();
                $('#image-upload-area').removeClass('has-image');
            }
            openProductForm('Edit Produk');
        });
    }

    function deleteProduct(id, name) {
        if (!confirm('Hapus produk "' + name + '"?')) return;
        $.ajax({
            url: '/kasir/api/products.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'delete', id: id }),
            dataType: 'json',
            success: function(res) {
                showToast(res.message, res.success ? 'success' : 'error');
                loadGrid();
            }
        });
    }

    function populateCategorySelect(selectedId) {
        var html = '<option value="">Pilih Kategori</option>';
        allCategories.forEach(function(c) {
            html += '<option value="' + c.id + '"' + (c.id == selectedId ? ' selected' : '') + '>' + c.nama_kategori + '</option>';
        });
        $('#pf-category').html(html);
    }

    function openProductForm(title) {
        if (!productFormWindow) {
            productFormWindow = $('#product-form-window').kendoWindow({
                title: title,
                width: '460px',
                modal: true,
                visible: false,
                actions: ['Close'],
                animation: { open: { effects: "fadeIn" }, close: { effects: "fadeOut" } }
            }).data('kendoWindow');
        }
        productFormWindow.title(title);
        productFormWindow.center().open();
    }

    function closeProductForm() {
        if (productFormWindow) productFormWindow.close();
    }

    // Form submit - upload image first, then save product
    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('#pf-submit');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        uploadImage(function(imageUrl) {
            var id = $('#pf-id').val();
            var data = {
                action: id ? 'update' : 'create',
                category_id: $('#pf-category').val(),
                nama_produk: $('#pf-name').val(),
                harga: $('#pf-price').val(),
                image_url: imageUrl || '',
                status: $('#pf-status').val()
            };
            if (id) data.id = id;

            $.ajax({
                url: '/kasir/api/products.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                dataType: 'json',
                success: function(res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
                    showToast(res.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        closeProductForm();
                        loadGrid();
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
                    showToast('Gagal menyimpan produk', 'error');
                }
            });
        });
    });

    // ============================================
    // Category Manager
    // ============================================
    function openCategoryManager() {
        loadCategories();
        if (!categoryWindow) {
            categoryWindow = $('#category-window').kendoWindow({
                title: 'Kelola Kategori',
                width: '400px',
                modal: true,
                visible: false,
                actions: ['Close'],
                animation: { open: { effects: "fadeIn" }, close: { effects: "fadeOut" } }
            }).data('kendoWindow');
        }
        categoryWindow.center().open();
    }

    function closeCategoryManager() {
        if (categoryWindow) {
            categoryWindow.close();
        }
    }

    function loadCategories() {
        $.getJSON('/kasir/api/products.php?action=all', function(res) {
            allCategories = res.categories;
            var html = '';
            allCategories.forEach(function(c) {
                html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-light);">' +
                        '<span style="font-weight:500;">' + c.nama_kategori + '</span>' +
                        '<button class="grid-action-btn btn-delete" onclick="deleteCategory(' + c.id + ')"><i class="fas fa-trash"></i></button>' +
                        '</div>';
            });
            $('#category-list').html(html || '<p style="color:var(--text-muted);">Belum ada kategori</p>');
        });
    }

    function addCategory() {
        var name = $('#new-category').val().trim();
        if (!name) return;
        $.ajax({
            url: '/kasir/api/products.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'create_category', nama_kategori: name }),
            dataType: 'json',
            success: function(res) {
                showToast(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    $('#new-category').val('');
                    loadCategories();
                }
            }
        });
    }

    function deleteCategory(id) {
        if (!confirm('Hapus kategori ini?')) return;
        $.ajax({
            url: '/kasir/api/products.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'delete_category', id: id }),
            dataType: 'json',
            success: function(res) {
                showToast(res.message, res.success ? 'success' : 'error');
                loadCategories();
            }
        });
    }

    $(document).ready(function() {
        loadGrid();
    });
    </script>
</body>
</html>
