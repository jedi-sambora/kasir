<?php
/**
 * User Management Page (Admin only)
 * Kendo Grid for user CRUD
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
    <meta name="description" content="Manajemen User - Kelola akun staf">
    <title>User Management — Café POS</title>
    <link rel="stylesheet" href="/kasir/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.common-material.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.material.min.css">
    <link rel="stylesheet" href="/kasir/assets/css/app.css">
    <style>
    #user-form .form-group .form-input {
        width: 100%;
        box-sizing: border-box;
    }
    #user-form .form-group select.form-input {
        width: 100%;
        box-sizing: border-box;
    }
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    .password-wrapper .form-input {
        padding-right: 48px;
    }
    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        font-size: 1rem;
        padding: 4px;
        transition: color var(--transition-fast);
    }
    .toggle-password:hover {
        color: var(--primary);
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="page-content">
        <div class="page-title-bar">
            <h1><i class="fas fa-users-cog" style="color:#9B6FB0;"></i>&nbsp; User Management</h1>
            <button class="btn btn-primary" onclick="addUser()">
                <i class="fas fa-user-plus"></i> Tambah User
            </button>
        </div>

        <div id="user-grid"></div>
    </div>

    <!-- User Form Window -->
    <div id="user-form-window" style="display:none;">
        <div class="payment-content">
            <form id="user-form">
                <input type="hidden" id="uf-id">
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Nama Lengkap</label>
                    <input type="text" id="uf-nama" class="form-input" placeholder="Nama lengkap" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Username</label>
                    <input type="text" id="uf-username" class="form-input" placeholder="Username" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Password <span id="pw-hint" style="font-weight:400;color:var(--text-muted);"></span></label>
                    <div class="password-wrapper">
                        <input type="password" id="uf-password" class="form-input" placeholder="Password">
                        <button type="button" class="toggle-password" id="toggle-uf-password" tabindex="-1">
                            <i class="fas fa-eye" id="uf-eye-icon"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Role</label>
                    <select id="uf-role" class="form-input">
                        <option value="kasir">Kasir</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="button" class="btn btn-outline" style="flex:1;justify-content:center;" onclick="closeUserForm()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="/kasir/assets/js/jquery.min.js"></script>
    <script src="/kasir/assets/kendo/js/kendo.all.min.js"></script>
    <script>
    var userFormWindow = null;

    function showToast(message, type) {
        var toast = $('<div class="toast-notification ' + (type || 'info') + '">' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() { toast.remove(); }, 3000);
    }

    function loadGrid() {
        $.getJSON('/kasir/api/users.php', function(res) {
            if (res.success) initGrid(res.data);
        });
    }

    function initGrid(data) {
        var $grid = $('#user-grid');
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
                            username: { type: "string" },
                            nama_lengkap: { type: "string" },
                            role: { type: "string" },
                            created_at: { type: "string" }
                        }
                    }
                }
            },
            sortable: true,
            filterable: true,
            pageable: { pageSize: 15, buttonCount: 5 },
            columns: [
                { field: "id", title: "ID", width: 70 },
                { field: "nama_lengkap", title: "Nama Lengkap" },
                { field: "username", title: "Username", width: 160 },
                { field: "role", title: "Role", width: 130,
                    template: function(d) {
                        return d.role === 'admin' ? '<span class="badge badge-admin">Admin</span>' : '<span class="badge badge-kasir">Kasir</span>';
                    }
                },
                { field: "created_at", title: "Dibuat", width: 180,
                    template: function(d) {
                        return d.created_at ? new Date(d.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'}) : '-';
                    }
                },
                { title: "Aksi", width: 180,
                    template: function(d) {
                        return '<button class="grid-action-btn" onclick="editUser(' + d.id + ')"><i class="fas fa-edit"></i> Edit</button>' +
                               '<button class="grid-action-btn btn-delete" onclick="deleteUser(' + d.id + ', \'' + d.nama_lengkap.replace(/'/g, "\\'") + '\')"><i class="fas fa-trash"></i> Hapus</button>';
                    }
                }
            ]
        });
    }

    function resetPasswordToggle() {
        $('#uf-password').attr('type', 'password');
        $('#uf-eye-icon').removeClass('fa-eye-slash').addClass('fa-eye');
    }

    function addUser() {
        $('#uf-id').val('');
        $('#uf-nama').val('');
        $('#uf-username').val('');
        $('#uf-password').val('').prop('required', true);
        $('#uf-role').val('kasir');
        $('#pw-hint').text('');
        resetPasswordToggle();
        openUserForm('Tambah User');
    }

    function editUser(id) {
        $.getJSON('/kasir/api/users.php', function(res) {
            var u = res.data.find(function(x) { return x.id == id; });
            if (!u) return;
            $('#uf-id').val(u.id);
            $('#uf-nama').val(u.nama_lengkap);
            $('#uf-username').val(u.username);
            $('#uf-password').val('').prop('required', false);
            $('#uf-role').val(u.role);
            $('#pw-hint').text('(kosongkan jika tidak diubah)');
            resetPasswordToggle();
            openUserForm('Edit User');
        });
    }

    function deleteUser(id, name) {
        if (!confirm('Hapus user "' + name + '"?')) return;
        $.ajax({
            url: '/kasir/api/users.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'delete', id: id }),
            dataType: 'json',
            success: function(res) {
                showToast(res.message, res.success ? 'success' : 'error');
                if (res.success) loadGrid();
            }
        });
    }

    function openUserForm(title) {
        if (!userFormWindow) {
            userFormWindow = $('#user-form-window').kendoWindow({
                title: title,
                width: '440px',
                modal: true,
                visible: false,
                actions: ['Close'],
                animation: { open: { effects: "fadeIn" }, close: { effects: "fadeOut" } }
            }).data('kendoWindow');
        }
        userFormWindow.title(title);
        userFormWindow.center().open();
    }

    function closeUserForm() {
        if (userFormWindow) userFormWindow.close();
    }

    $('#user-form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#uf-id').val();
        var data = {
            action: id ? 'update' : 'create',
            nama_lengkap: $('#uf-nama').val(),
            username: $('#uf-username').val(),
            password: $('#uf-password').val(),
            role: $('#uf-role').val()
        };
        if (id) data.id = id;

        $.ajax({
            url: '/kasir/api/users.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            success: function(res) {
                showToast(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    closeUserForm();
                    loadGrid();
                }
            }
        });
    });

    $(document).ready(function() {
        loadGrid();

        // Toggle show/hide password
        $(document).on('click', '#toggle-uf-password', function() {
            var $input = $('#uf-password');
            var $icon = $('#uf-eye-icon');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
    </script>
</body>
</html>
