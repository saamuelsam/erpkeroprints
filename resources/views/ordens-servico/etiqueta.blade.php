<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiqueta {{ $os->numero_os }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --label-width: 100mm;
            --label-min-height: 60mm;
        }

        body {
            background: #f1f5f9;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 24px;
        }

        .toolbar {
            margin: 0 auto 16px;
            max-width: var(--label-width);
        }

        .label-sheet {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 120px);
        }

        .label-box {
            background: #fff;
            border: 1px solid #111827;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
            min-height: var(--label-min-height);
            padding: 8mm;
            width: var(--label-width);
        }

        .label-title {
            border-bottom: 1px solid #111827;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            margin-bottom: 8px;
            padding-bottom: 4px;
            text-transform: uppercase;
        }

        .label-client {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 8px;
            overflow-wrap: anywhere;
        }

        .label-meta {
            color: #374151;
            display: flex;
            font-size: 11px;
            gap: 8px;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .items-title {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .items {
            font-size: 14px;
            line-height: 1.25;
            margin: 0;
            padding-left: 18px;
        }

        .items li {
            margin-bottom: 3px;
            overflow-wrap: anywhere;
        }

        .service-note {
            border-top: 1px dashed #9ca3af;
            font-size: 12px;
            margin-top: 8px;
            padding-top: 6px;
            overflow-wrap: anywhere;
        }

        @media print {
            @page {
                margin: 0;
                size: 100mm 60mm;
            }

            body {
                background: #fff;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .label-sheet {
                align-items: flex-start;
                display: block;
                min-height: auto;
            }

            .label-box {
                border-radius: 0;
                box-shadow: none;
                min-height: 60mm;
                width: 100mm;
            }
        }
    </style>
</head>
<body>
@php
    $clienteEtiqueta = $os->cliente_exibicao;
    $itensEtiqueta = $os->itens;
@endphp

<div class="toolbar no-print d-flex gap-2 justify-content-between align-items-center">
    <a href="{{ route('ordens-servico.show', $os) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar para OS
    </a>
    <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i>Imprimir
    </button>
</div>

<main class="label-sheet">
    <section class="label-box">
        <div class="label-title">Etiqueta do pedido</div>
        <div class="label-client">{{ $clienteEtiqueta }}</div>

        <div class="label-meta">
            <span>{{ $os->numero_os }}</span>
            <span>{{ $os->data_prevista_entrega?->format('d/m/Y') ?? $os->data_abertura->format('d/m/Y') }}</span>
        </div>

        <div class="items-title">Produtos / Servicos</div>
        @if($itensEtiqueta->isNotEmpty())
            <ul class="items">
                @foreach($itensEtiqueta as $item)
                    <li>
                        {{ number_format($item->quantidade, 2, ',', '.') }}x {{ $item->descricao_item }}
                    </li>
                @endforeach
            </ul>
        @else
            <div class="service-note">
                {{ $os->descricao_servico ?: 'Pedido sem itens detalhados.' }}
            </div>
        @endif

        @if($os->descricao_servico && $itensEtiqueta->isNotEmpty())
            <div class="service-note">{{ $os->descricao_servico }}</div>
        @endif
    </section>
</main>

<script>
    window.addEventListener('load', () => {
        setTimeout(() => window.print(), 350);
    });
</script>
</body>
</html>
