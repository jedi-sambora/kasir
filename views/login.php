<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem POS Kafe - Login untuk mengakses aplikasi kasir">
    <title>Login — Café POS</title>
    <link rel="stylesheet" href="/kasir/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/kasir/assets/css/app.css">
    <style>
    .password-wrapper {
        position: relative;
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
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-logo">
                    <div class="logo-icon">
                        <i class="fas fa-mug-hot"></i>
                    </div>
                    <h1>Café POS</h1>
                    <p>Masuk ke sistem kasir</p>
                </div>

                <div class="login-error" id="login-error"></div>

                <form class="login-form" id="login-form" onsubmit="return handleLogin(event)">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-input" id="username" name="username" 
                               placeholder="Masukkan username" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-input" id="password" name="password" 
                                   placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="toggle-password" id="toggle-password" tabindex="-1">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="login-btn" id="login-btn">
                        <i class="fas fa-sign-in-alt"></i>&nbsp;&nbsp;Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="/kasir/assets/js/jquery.min.js"></script>
    <script>
        function handleLogin(e) {
            e.preventDefault();
            var btn = $('#login-btn');
            var errDiv = $('#login-error');
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>&nbsp;&nbsp;Memproses...');
            errDiv.removeClass('show').hide();

            $.ajax({
                url: '/kasir/api/auth.php',
                method: 'POST',
                data: {
                    action: 'login',
                    username: $('#username').val(),
                    password: $('#password').val()
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        window.location.href = '/kasir/index.php?page=main_menu';
                    } else {
                        errDiv.text(res.message).addClass('show').show();
                        btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i>&nbsp;&nbsp;Masuk');
                    }
                },
                error: function() {
                    errDiv.text('Terjadi kesalahan koneksi.').addClass('show').show();
                    btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i>&nbsp;&nbsp;Masuk');
                }
            });
        }

        // Toggle show/hide password
        $('#toggle-password').on('click', function() {
            var $input = $('#password');
            var $icon = $('#eye-icon');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    </script>
</body>
</html>
