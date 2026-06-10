<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kero Prints ERP') | Kero Prints</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #ffd000;
            --primary-dark: #e6bb00;
            --ink: #18202b;
            --muted: #667085;
            --line: #e4e7ec;
            --surface: #ffffff;
            --canvas: #f5f6f8;
            --magenta: #ec008c;
            --cyan: #00aeef;
            --success: #15803d;
            --header-height: 62px;
            --nav-height: 46px;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--canvas);
            color: var(--ink);
            font-family: 'Inter', sans-serif;
            font-size: .875rem;
            margin: 0;
        }

        a { color: #087ba7; }
        a:hover { color: #075f80; }

        .app-header {
            background: #171b23;
            border-bottom: 1px solid #272d38;
            height: var(--header-height);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .app-header-inner,
        .app-nav-inner,
        .main-content {
            margin: 0 auto;
            max-width: 1680px;
            width: 100%;
        }

        .app-header-inner {
            align-items: center;
            display: flex;
            gap: 16px;
            height: 100%;
            padding: 0 20px;
        }

        .brand-link {
            align-items: center;
            display: inline-flex;
            flex: 0 0 auto;
        }

        .brand-logo {
            display: block;
            height: 34px;
            object-fit: contain;
            width: auto;
        }

        .page-context {
            border-left: 1px solid #343b47;
            color: #f8fafc;
            font-size: .9rem;
            font-weight: 700;
            line-height: 1.2;
            min-width: 0;
            padding-left: 16px;
        }

        .header-actions {
            align-items: center;
            display: flex;
            gap: 7px;
            margin-left: auto;
        }

        .quick-action {
            align-items: center;
            background: #252b35;
            border: 1px solid #373f4c;
            border-radius: 6px;
            color: #e5e7eb;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 600;
            gap: 7px;
            height: 34px;
            padding: 0 11px;
            text-decoration: none;
            white-space: nowrap;
        }

        .quick-action:hover {
            background: #313845;
            border-color: #4b5563;
            color: #fff;
        }

        .quick-action.is-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #171b23;
        }

        .quick-action.is-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #171b23;
        }

        .user-menu .dropdown-toggle {
            align-items: center;
            background: transparent;
            border: 0;
            color: #d1d5db;
            display: inline-flex;
            gap: 8px;
            padding: 5px 2px 5px 8px;
        }

        .user-avatar {
            align-items: center;
            background: #343b47;
            border-radius: 50%;
            color: var(--primary);
            display: inline-flex;
            font-size: .74rem;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .mobile-nav-toggle {
            background: #252b35;
            border: 1px solid #373f4c;
            border-radius: 6px;
            color: #fff;
            display: none;
            height: 34px;
            width: 36px;
        }

        .app-nav {
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            min-height: var(--nav-height);
            position: sticky;
            top: var(--header-height);
            z-index: 1025;
        }

        .app-nav-inner {
            align-items: stretch;
            display: flex;
            min-height: var(--nav-height);
            padding: 0 14px;
        }

        .main-nav {
            align-items: stretch;
            display: flex;
            gap: 2px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .main-nav > li { display: flex; }

        .main-nav-link {
            align-items: center;
            border-bottom: 2px solid transparent;
            color: #475467;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 600;
            gap: 7px;
            padding: 0 11px;
            text-decoration: none;
            white-space: nowrap;
        }

        .main-nav-link i { color: #98a2b3; font-size: .8rem; }

        .main-nav-link:hover,
        .main-nav-link.active {
            background: #f8fafc;
            border-bottom-color: var(--primary);
            color: var(--ink);
        }

        .main-nav-link.active i { color: #9a7800; }

        .main-nav-link.dropdown-toggle::after { margin-left: 2px; }

        .dropdown-menu {
            border: 1px solid var(--line);
            border-radius: 7px;
            box-shadow: 0 12px 28px rgba(16, 24, 40, .12);
            font-size: .82rem;
            margin-top: 0;
            padding: 6px;
        }

        .dropdown-item {
            align-items: center;
            border-radius: 5px;
            display: flex;
            gap: 9px;
            padding: 8px 10px;
        }

        .dropdown-item i {
            color: #667085;
            text-align: center;
            width: 16px;
        }

        .dropdown-item.active,
        .dropdown-item:active {
            background: #fff8d6;
            color: var(--ink);
        }

        .main-content {
            min-height: calc(100vh - var(--header-height) - var(--nav-height));
            padding: 18px 20px 32px;
        }

        .main-content > .d-flex:first-child h4,
        .main-content > h4:first-child {
            font-size: 1.12rem;
            letter-spacing: 0;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 7px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .035);
        }

        .card-header,
        .card-footer {
            background: #fff;
            border-color: #eaecf0;
            font-size: .84rem;
            font-weight: 700;
            padding: 11px 14px;
        }

        .card-body { padding: 14px; }

        .row.g-4 {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }

        .btn {
            align-items: center;
            border-radius: 6px;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 600;
            justify-content: center;
            min-height: 34px;
        }

        .btn-sm { min-height: 30px; }
        .btn-lg { font-size: .88rem; min-height: 42px; }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #171b23;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #171b23;
        }

        .btn-outline-primary {
            border-color: #d5ad00;
            color: #6b5700;
        }

        .btn-outline-primary:hover {
            background: #fff8d6;
            border-color: #d5ad00;
            color: #4b3d00;
        }

        .form-label {
            color: #344054;
            font-size: .78rem;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select,
        .input-group-text {
            border-color: #d0d5dd;
            border-radius: 6px;
            font-size: .82rem;
            min-height: 36px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #c5a100;
            box-shadow: 0 0 0 3px rgba(255, 208, 0, .18);
        }

        textarea.form-control { min-height: auto; }

        .table {
            --bs-table-hover-bg: #fafbfc;
            font-size: .8rem;
            margin-bottom: 0;
        }

        .table > :not(caption) > * > * {
            border-bottom-color: #eaecf0;
            padding: .62rem .7rem;
        }

        .table th {
            background: #f8fafc;
            color: #667085;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge {
            border-radius: 4px;
            font-size: .68rem;
            font-weight: 700;
            padding: .36em .55em;
        }

        .alert {
            border-radius: 6px;
            font-size: .8rem;
        }

        .pagination { --bs-pagination-border-radius: 5px; }

        .flash-messages {
            min-width: min(360px, calc(100vw - 24px));
            position: fixed;
            right: 16px;
            top: 118px;
            z-index: 1090;
        }

        .empty-state {
            color: var(--muted);
            padding: 36px 16px;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .quick-action span,
            .user-name { display: none; }
            .quick-action { justify-content: center; padding: 0; width: 34px; }
            .main-nav-link { padding: 0 8px; }
        }

        @media (max-width: 820px) {
            .app-header { height: 56px; }
            .app-header-inner { gap: 10px; padding: 0 12px; }
            .brand-logo { height: 29px; }
            .page-context { font-size: .82rem; padding-left: 10px; }
            .mobile-nav-toggle { display: inline-flex; align-items: center; justify-content: center; }
            .app-nav { display: none; position: sticky; top: 56px; }
            .app-nav.show { display: block; }
            .app-nav-inner { padding: 8px; }
            .main-nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
            .main-nav > li { display: block; }
            .main-nav-link {
                border: 0;
                border-radius: 5px;
                min-height: 40px;
                padding: 0 10px;
                width: 100%;
            }
            .main-nav .dropdown-menu {
                position: fixed !important;
                inset: auto 10px !important;
                transform: none !important;
                width: calc(100vw - 20px);
            }
            .main-content { padding: 14px 10px 26px; }
            .flash-messages { top: 66px; }
        }
    </style>

    @stack('styles')
</head>
<body>
@auth
<header class="app-header">
    <div class="app-header-inner">
        <button class="mobile-nav-toggle" id="mobileNavToggle" type="button" aria-label="Abrir menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="{{ route('home') }}" class="brand-link" title="Kero Prints ERP">
            <img src="{{ asset('images/logo-white.png') }}" alt="Kero Prints" class="brand-logo">
        </a>

        <div class="page-context">@yield('page-title', 'Dashboard')</div>

        <div class="header-actions">
            <a href="{{ route('vendas.pdv') }}" class="quick-action is-primary" title="Abrir PDV">
                <i class="fa-solid fa-cash-register"></i><span>Nova venda</span>
            </a>
            <a href="{{ route('ordens-servico.create') }}" class="quick-action" title="Criar ordem de serviço">
                <i class="fa-solid fa-plus"></i><span>Nova OS</span>
            </a>
            <a href="{{ route('clientes.create') }}" class="quick-action" title="Cadastrar cliente">
                <i class="fa-solid fa-user-plus"></i><span>Cliente</span>
            </a>

            <div class="dropdown user-menu">
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="user-avatar"><i class="fa-solid fa-user"></i></span>
                    <span class="user-name">{{ Auth::user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted">{{ Auth::user()->email }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fa-solid fa-right-from-bracket"></i>Sair
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<nav class="app-nav" id="appNav">
    <div class="app-nav-inner">
        <ul class="main-nav">
            <li>
                <a href="{{ route('home') }}" class="main-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>Visão geral
                </a>
            </li>
            <li>
                <a href="{{ route('vendas.pdv') }}" class="main-nav-link {{ request()->routeIs('vendas.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-cash-register"></i>Vendas
                </a>
            </li>
            <li>
                <a href="{{ route('ordens-servico.producao') }}" class="main-nav-link {{ request()->routeIs('ordens-servico.producao') ? 'active' : '' }}">
                    <i class="fa-solid fa-industry"></i>Produção
                </a>
            </li>
            <li>
                <a href="{{ route('ordens-servico.index') }}" class="main-nav-link {{ request()->routeIs('ordens-servico.*') && !request()->routeIs('ordens-servico.producao') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i>Ordens
                </a>
            </li>
            <li class="dropdown">
                <a href="#" class="main-nav-link dropdown-toggle {{ request()->routeIs('clientes.*', 'categorias.*', 'produtos.*') ? 'active' : '' }}" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-boxes-stacked"></i>Cadastros
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}"><i class="fa-solid fa-users"></i>Clientes</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('produtos.*') ? 'active' : '' }}" href="{{ route('produtos.index') }}"><i class="fa-solid fa-box"></i>Produtos</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('categorias.*') ? 'active' : '' }}" href="{{ route('categorias.index') }}"><i class="fa-solid fa-tags"></i>Categorias</a></li>
                </ul>
            </li>
            <li class="dropdown">
                <a href="#" class="main-nav-link dropdown-toggle {{ request()->routeIs('financeiro.*') ? 'active' : '' }}" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-wallet"></i>Financeiro
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('financeiro.fluxo-caixa') }}"><i class="fa-solid fa-chart-column"></i>Fluxo de caixa</a></li>
                    <li><a class="dropdown-item" href="{{ route('financeiro.entradas.index') }}"><i class="fa-solid fa-arrow-trend-up"></i>Entradas</a></li>
                    <li><a class="dropdown-item" href="{{ route('financeiro.saidas.index') }}"><i class="fa-solid fa-arrow-trend-down"></i>Saídas</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('financeiro.contas-receber.index') }}"><i class="fa-solid fa-hand-holding-dollar"></i>Contas a receber</a></li>
                    <li><a class="dropdown-item" href="{{ route('financeiro.contas-pagar.index') }}"><i class="fa-solid fa-file-invoice-dollar"></i>Contas a pagar</a></li>
                    <li><a class="dropdown-item" href="{{ route('financeiro.documentos.index') }}"><i class="fa-solid fa-file-lines"></i>Documentos</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
@endauth

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

@auth
<main class="main-content">
    @yield('content')
</main>
@else
    @yield('content')
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('mobileNavToggle')?.addEventListener('click', () => {
        document.getElementById('appNav')?.classList.toggle('show');
    });

    setTimeout(() => {
        document.querySelectorAll('.flash-messages .alert').forEach(el => {
            bootstrap.Alert.getOrCreateInstance(el).close();
        });
    }, 4000);
</script>

@stack('scripts')
</body>
</html>
