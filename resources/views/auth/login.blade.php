<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión · Unidata</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body {
            background-color: var(--bg-root);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            position: relative;
        }

        /* Abstract Background Elements */
        .bg-blob-1 { position: absolute; width: 600px; height: 600px; background: rgba(139,92,246,0.15); border-radius: 50%; filter: blur(100px); top: -200px; left: -200px; pointer-events: none; }
        .bg-blob-2 { position: absolute; width: 500px; height: 500px; background: rgba(16,185,129,0.1); border-radius: 50%; filter: blur(100px); bottom: -150px; right: -150px; pointer-events: none; }
        .bg-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 30px 30px; pointer-events: none; z-index: 1; mask-image: radial-gradient(circle at center, black, transparent 80%); -webkit-mask-image: radial-gradient(circle at center, black, transparent 80%); }

        .login-card {
            background: rgba(15,23,42,0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 420px;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            box-sizing: border-box;
        }

        .login-header { text-align: center; margin-bottom: 40px; }
        .login-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 25px -5px rgba(139,92,246,0.4);
        }
        .login-title { font-size: 24px; font-weight: 800; color: #f8fafc; margin: 0 0 8px; letter-spacing: -0.03em; }
        .login-subtitle { font-size: 14px; color: #94a3b8; margin: 0; }

        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 8px; }
        
        .form-input-box {
            position: relative;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .form-input-box:focus-within {
            border-color: #8b5cf6;
            background: rgba(139,92,246,0.05);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
        }
        .form-input-icon {
            padding: 0 14px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-input-box:focus-within .form-input-icon { color: #8b5cf6; }
        .form-control {
            width: 100%; border: none; background: transparent; padding: 14px 14px 14px 0;
            font-size: 14px; color: white; display: block; outline: none;
            font-family: inherit;
        }
        .form-control::placeholder { color: #475569; }

        .form-options { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
        
        .custom-checkbox { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .custom-checkbox input { display: none; }
        .checkmark { width: 18px; height: 18px; border-radius: 6px; border: 1.5px solid #475569; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .custom-checkbox input:checked + .checkmark { background: #8b5cf6; border-color: #8b5cf6; }
        .checkmark svg { width: 12px; height: 12px; color: white; stroke-width: 3; opacity: 0; transform: scale(0.5); transition: all 0.2s cubic-bezier(.16,1,.3,1); }
        .custom-checkbox input:checked + .checkmark svg { opacity: 1; transform: scale(1); }
        .checkbox-label { font-size: 13px; color: #94a3b8; user-select: none; }

        .btn-submit {
            width: 100%; background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white; border: none; border-radius: 12px; padding: 14px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            box-shadow: 0 10px 20px -10px rgba(139,92,246,0.5);
            transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
            display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -10px rgba(139,92,246,0.6); }
        .btn-submit:active { transform: translateY(0); }

        .alert-error {
            background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2);
            color: #fca5a5; padding: 12px 16px; border-radius: 10px; font-size: 13px;
            margin-bottom: 24px; display: flex; align-items:flex-start; gap: 10px;
        }
    </style>
</head>
<body>
    <div class="bg-blob-1"></div>
    <div class="bg-blob-2"></div>
    <div class="bg-grid"></div>

    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" width="28" height="28">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1 class="login-title">Bienvenido a Unidata</h1>
            <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="login">Usuario o Correo Electrónico</label>
                <div class="form-input-box">
                    <div class="form-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <input id="login" type="text" name="login" class="form-control" value="{{ old('login') }}" required autofocus placeholder="tu_usuario o tu@empresa.com">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="form-input-box">
                    <div class="form-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <input id="password" type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>

            <div class="form-options">
                <label class="custom-checkbox">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span class="checkbox-label">Recordar sesión</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <span>Ingresar al Portal</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </form>
    </div>
</body>
</html>
