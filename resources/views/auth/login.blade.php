<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Kero Prints Gráfica e Papelaria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0d1117 0%, #161b27 50%, #0d1117 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 48px 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-icon {
            width: 68px;
            height: 68px;
            background: #FFD000;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.9rem;
            color: #111;
            box-shadow: 0 8px 30px rgba(255,208,0,0.5);
        }

        .brand h1 {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand p {
            color: #94A3B8;
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #CBD5E1;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            font-size: 0.9rem;
        }

        .input-wrap .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #94A3B8;
            cursor: pointer;
            height: 34px;
            width: 34px;
            border-radius: 8px;
        }

        .input-wrap .toggle-password:hover {
            background: rgba(255,255,255,0.08);
            color: #FFD000;
        }

        .input-wrap .toggle-password i {
            position: static;
            transform: none;
            color: inherit;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 46px 12px 42px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            transition: all 0.2s;
            outline: none;
        }

        input:focus {
            border-color: #FFD000;
            background: rgba(255,208,0,0.06);
            box-shadow: 0 0 0 3px rgba(255,208,0,0.2);
        }

        input::placeholder { color: #475569; }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .form-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .form-check label {
            margin: 0;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .error-msg {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 20px;
        }

        .error-msg p {
            color: #FCA5A5;
            font-size: 0.8rem;
            margin-bottom: 2px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #FFD000;
            border: none;
            border-radius: 10px;
            color: #111827;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 18px rgba(255,208,0,0.4);
            letter-spacing: -0.2px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            background: #E6BB00;
            box-shadow: 0 6px 24px rgba(255,208,0,0.5);
        }

        .btn-login:active { transform: translateY(0); }

        .footer-text {
            text-align: center;
            margin-top: 28px;
            color: #475569;
            font-size: 0.78rem;
        }

        /* Glow decorativo */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 30%, rgba(255,208,0,0.06) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -30%;
            right: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(236,0,140,0.04) 0%, transparent 70%);
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <img src="{{ asset('images/logo-white.png') }}" alt="Kero Prints Gráfica e Papelaria"
                 style="max-width:240px;height:auto;margin:0 auto 24px;display:block;">
        </div>

        @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                <p><i class="fa-solid fa-circle-xmark me-1"></i>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        @if (session('status'))
        <div class="error-msg" style="background:rgba(34,197,94,0.15);border-color:rgba(34,197,94,0.3)">
            <p style="color:#86EFAC">{{ session('status') }}</p>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Login</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="text" id="email" name="email" value="{{ old('email') }}"
                           placeholder="keroprints@.com" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar senha">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-check">
                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Lembrar-me</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Entrar no sistema
            </button>
        </form>

        <div class="footer-text">
            Kero Prints Gráfica e Papelaria &copy; {{ date('Y') }} — Acesso restrito
        </div>
    </div>
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword?.addEventListener('click', () => {
            const mostrando = passwordInput.type === 'text';
            passwordInput.type = mostrando ? 'password' : 'text';
            togglePassword.setAttribute('aria-label', mostrando ? 'Mostrar senha' : 'Ocultar senha');
            togglePassword.innerHTML = mostrando
                ? '<i class="fa-solid fa-eye"></i>'
                : '<i class="fa-solid fa-eye-slash"></i>';
            passwordInput.focus();
        });
    </script>
</body>
</html>
