<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\VendaController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\Financeiro\EntradaController;
use App\Http\Controllers\Financeiro\SaidaController;
use App\Http\Controllers\Financeiro\FluxoCaixaController;
use App\Http\Controllers\Financeiro\ContaReceberController;
use App\Http\Controllers\Financeiro\ContaPagarController;
use App\Http\Controllers\Financeiro\DocumentoController;
use App\Http\Controllers\Api\CepController;

// ─── Raiz → Login ────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));
Route::post('/webhooks/mercado-pago', MercadoPagoWebhookController::class)
    ->name('webhooks.mercado-pago');

// ─── Autenticação (laravel/ui) ───────────────────────────────────────────────
Auth::routes(['register' => false]);

// ─── Área Protegida ──────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // ── Clientes ─────────────────────────────────────────────────────────────
    Route::resource('clientes', ClienteController::class);
    Route::patch('clientes/{cliente}/toggle-ativo', [ClienteController::class, 'toggleAtivo'])
         ->name('clientes.toggle-ativo');

    // ── Categorias ────────────────────────────────────────────────────────────
    Route::resource('categorias', CategoriaController::class)->except(['show']);

    // ── Produtos ──────────────────────────────────────────────────────────────
    Route::resource('produtos', ProdutoController::class);
    Route::post('produtos/{produto}/entrada-estoque', [ProdutoController::class, 'entradaEstoque'])
         ->name('produtos.entrada-estoque');

    // ── API interna ───────────────────────────────────────────────────────────
    Route::get('/api/produtos/buscar', [ProdutoController::class, 'buscarApi'])
         ->name('api.produtos.buscar');
    Route::get('/api/cep/{cep}', [CepController::class, 'buscar'])
         ->name('api.cep.buscar');

    // ── Ordens de Serviço ─────────────────────────────────────────────────────
    Route::get('ordens-servico/producao', [OrdemServicoController::class, 'producao'])
         ->name('ordens-servico.producao');
    Route::patch('ordens-servico/{os}/status-rapido', [OrdemServicoController::class, 'atualizarStatusRapido'])
         ->name('ordens-servico.status-rapido');
    Route::resource('ordens-servico', OrdemServicoController::class)
         ->parameters(['ordens-servico' => 'os']);

    Route::get('vendas/pdv', [VendaController::class, 'pdv'])->name('vendas.pdv');
    Route::get('vendas/cliente', [VendaController::class, 'cliente'])->name('vendas.cliente');
    Route::post('vendas', [VendaController::class, 'store'])->name('vendas.store');
    Route::post('vendas/{venda}/consultar-pagamento', [VendaController::class, 'consultarPagamento'])->name('vendas.consultar-pagamento');
    Route::post('vendas/{venda}/cancelar', [VendaController::class, 'cancelar'])->name('vendas.cancelar');
    Route::get('vendas', [VendaController::class, 'index'])->name('vendas.index');

    // ── Financeiro ────────────────────────────────────────────────────────────
    Route::prefix('financeiro')->name('financeiro.')->group(function () {
        Route::resource('entradas', EntradaController::class)->except(['show']);
        Route::resource('saidas', SaidaController::class)->except(['show']);
        Route::get('fluxo-caixa', [FluxoCaixaController::class, 'index'])->name('fluxo-caixa');

        // Contas a Receber
        Route::resource('contas-receber', ContaReceberController::class)->except(['show']);
        Route::post('contas-receber/{conta}/baixar', [ContaReceberController::class, 'baixar'])
             ->name('contas-receber.baixar');

        // Contas a Pagar
        Route::resource('contas-pagar', ContaPagarController::class)->except(['show']);
        Route::post('contas-pagar/{conta}/baixar', [ContaPagarController::class, 'baixar'])
             ->name('contas-pagar.baixar');

        // Documentos
        Route::resource('documentos', DocumentoController::class);
        Route::post('documentos/{documento}/emitir', [DocumentoController::class, 'emitir'])
             ->name('documentos.emitir');
        Route::get('documentos/{documento}/pdf', [DocumentoController::class, 'pdf'])
             ->name('documentos.pdf');
        Route::post('documentos/{documento}/enviar-email', [DocumentoController::class, 'enviarEmail'])
             ->name('documentos.enviar-email');
        Route::post('documentos/{documento}/enviar-whatsapp', [DocumentoController::class, 'enviarWhatsApp'])
             ->name('documentos.enviar-whatsapp');
    });

});
