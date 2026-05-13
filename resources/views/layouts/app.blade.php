<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kero Prints ERP') | Kero Prints Gráfica e Papelaria</title>

    <!-- DNS Preconnect — elimina latência de resolução DNS -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- Google Fonts — swap evita FOIT -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --primary:      #FFD000;  /* Amarelo Kero */
            --primary-dark: #E6BB00;
            --magenta:      #EC008C;
            --cyan:         #00AEEF;
            --sidebar-bg:   #111827;
            --sidebar-text: #9CA3AF;
            --sidebar-hover: rgba(255,208,0,0.10);
            --sidebar-active: rgba(255,208,0,0.18);
            --topbar-height: 64px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #F1F5F9;
            color: #1E293B;
            margin: 0;
        }

        /* ── Sidebar ─────────────────────────────────────── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 16px 16px 14px;
            border-bottom: 1px solid rgba(255,208,0,0.15);
            background: linear-gradient(135deg, #1a1f2e 0%, #111827 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-brand .brand-logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-brand .brand-logo-img {
            max-width: 190px;
            height: auto;
            display: block;
            /* sem fundo branco — logo branca fica diretamente no escuro */
        }

        .sidebar-section {
            padding: 12px 12px 4px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #475569;
            text-transform: uppercase;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--sidebar-text);
            padding: 9px 14px;
            border-radius: 8px;
            margin: 1px 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: var(--primary);
        }

        .sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: var(--primary);
            font-weight: 600;
            border-left: 3px solid var(--primary);
            padding-left: 11px;
        }

        .sidebar .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 0.875rem;
        }

        /* ── Topbar ──────────────────────────────────────── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 999;
            gap: 16px;
        }

        .topbar .page-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1E293B;
            flex: 1;
        }

        /* ── Main Content ─────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 24px;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* ── Cards ───────────────────────────────────────── */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 1px 2px rgba(0,0,0,0.04);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #F1F5F9;
            font-weight: 600;
            padding: 16px 20px;
        }

        /* ── Badges e status ──────────────────────────────── */
        .badge { font-weight: 600; font-size: 0.75rem; }

        /* ── Botões ───────────────────────────────────────── */
        .btn { font-weight: 500; border-radius: 8px; font-size: 0.875rem; }
        .btn-primary { background: var(--primary); border-color: var(--primary); color: #111; font-weight: 600; }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); color: #111; }

        /* ── Tabelas ──────────────────────────────────────── */
        .table { font-size: 0.875rem; }
        .table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; }
        .table td { vertical-align: middle; }

        /* ── Alertas flash ────────────────────────────────── */
        .flash-messages { position: fixed; top: 70px; right: 20px; z-index: 9999; min-width: 300px; }

        /* ── Responsivo ───────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .topbar { left: 0; }
            .main-content { margin-left: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

@auth
<!-- ══════════════════ SIDEBAR ══════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-area">
            <img src="{{ asset('images/logo-white.png') }}" alt="Kero Prints Gráfica e Papelaria" class="brand-logo-img">
        </div>
    </div>

    <nav class="py-2">
        <div class="sidebar-section">Principal</div>
        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>

        <div class="sidebar-section mt-2">Cadastros</div>
        <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
            <i class="fa-solid fa-users"></i> Clientes
        </a>
        <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
            <i class="fa-solid fa-tags"></i> Categorias
        </a>
        <a href="{{ route('produtos.index') }}" class="nav-link {{ request()->routeIs('produtos.*') ? 'active' : '' }}">
            <i class="fa-solid fa-box"></i> Produtos
        </a>

        <div class="sidebar-section mt-2">Operações</div>
        <a href="{{ route('vendas.pdv') }}" class="nav-link {{ request()->routeIs('vendas.*') ? 'active' : '' }}">
            <i class="fa-solid fa-cash-register"></i> Vendas / PDV
        </a>
        <a href="{{ route('ordens-servico.producao') }}" class="nav-link {{ request()->routeIs('ordens-servico.producao') ? 'active' : '' }}">
            <i class="fa-solid fa-industry"></i> Produção
        </a>
        <a href="{{ route('ordens-servico.index') }}" class="nav-link {{ request()->routeIs('ordens-servico.*') && !request()->routeIs('ordens-servico.producao') ? 'active' : '' }}">
            <i class="fa-solid fa-clipboard-list"></i> Ordens de Serviço
        </a>

        <div class="sidebar-section mt-2">Financeiro</div>
        <a class="nav-link {{ request()->routeIs('financeiro.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#menuFinanceiro" role="button"
           aria-expanded="{{ request()->routeIs('financeiro.*') ? 'true' : 'false' }}">
            <i class="fa-solid fa-wallet"></i> Financeiro
            <i class="fa-solid fa-chevron-down ms-auto" style="font-size:.6rem;transition:transform .2s"></i>
        </a>
        <div class="collapse {{ request()->routeIs('financeiro.*') ? 'show' : '' }}" id="menuFinanceiro">
            <a href="{{ route('financeiro.entradas.index') }}" class="nav-link ps-4 {{ request()->routeIs('financeiro.entradas.*') ? 'active' : '' }}">
                <i class="fa-solid fa-arrow-trend-up text-success"></i> Entradas
            </a>
            <a href="{{ route('financeiro.saidas.index') }}" class="nav-link ps-4 {{ request()->routeIs('financeiro.saidas.*') ? 'active' : '' }}">
                <i class="fa-solid fa-arrow-trend-down text-danger"></i> Saídas
            </a>
            <a href="{{ route('financeiro.fluxo-caixa') }}" class="nav-link ps-4 {{ request()->routeIs('financeiro.fluxo-caixa') ? 'active' : '' }}">
                <i class="fa-solid fa-money-bill-transfer"></i> Fluxo de Caixa
            </a>
            <a href="{{ route('financeiro.contas-receber.index') }}" class="nav-link ps-4 {{ request()->routeIs('financeiro.contas-receber.*') ? 'active' : '' }}">
                <i class="fa-solid fa-hand-holding-dollar text-info"></i> Contas a Receber
            </a>
            <a href="{{ route('financeiro.contas-pagar.index') }}" class="nav-link ps-4 {{ request()->routeIs('financeiro.contas-pagar.*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-invoice-dollar text-warning"></i> Contas a Pagar
            </a>
            <a href="{{ route('financeiro.documentos.index') }}" class="nav-link ps-4 {{ request()->routeIs('financeiro.documentos.*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-pdf text-danger"></i> Documentos
            </a>
        </div>

        <div class="sidebar-section mt-2">Conta</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link w-100 text-start border-0 bg-transparent">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </button>
        </form>
    </nav>
</aside>

<!-- ══════════════════ TOPBAR ══════════════════ -->
<div class="topbar">
    <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="page-title">@yield('page-title', 'Dashboard')</div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small"><i class="fa-regular fa-user me-1"></i>{{ Auth::user()->name }}</span>
    </div>
</div>
@endauth

<!-- ══════════════════ FLASH MESSAGES ══════════════════ -->
<div class="flash-messages">
    @if(session('sucesso'))
        <div class="alert alert-success alert-dismissible shadow-sm fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('sucesso') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('erro'))
        <div class="alert alert-danger alert-dismissible shadow-sm fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('erro') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>

<!-- ══════════════════ MAIN CONTENT ══════════════════ -->
@auth
<main class="main-content">
    @yield('content')
</main>
@else
    @yield('content')
@endauth

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Auto-dismiss flash messages após 4s -->
<script>
    setTimeout(() => {
        document.querySelectorAll('.flash-messages .alert').forEach(el => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 4000);
</script>

@stack('scripts')
</body>
</html>
