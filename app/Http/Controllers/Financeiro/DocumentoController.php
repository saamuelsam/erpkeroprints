<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentoRequest;
use App\Mail\DocumentoMail;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\FinanceiroEntrada;
use App\Services\DocumentoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DocumentoController extends Controller
{
    public function __construct(protected DocumentoService $documentoService)
    {
    }

    public function index(Request $request)
    {
        $query = Documento::with('cliente');

        if ($busca = $request->input('busca')) {
            $query->busca($busca);
        }

        if ($tipo = $request->input('tipo')) {
            $query->tipo($tipo);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $documentos = $query->latest('data_emissao')->paginate(20)->withQueryString();

        return view('financeiro.documentos.index', [
            'documentos' => $documentos,
            'tipos'      => Documento::TIPOS,
        ]);
    }

    public function create()
    {
        $clientes = Cliente::ativos()->orderBy('nome')->get(['id', 'nome', 'email', 'telefone']);

        return view('financeiro.documentos.form', [
            'documento'       => new Documento(['data_emissao' => now()->toDateString()]),
            'clientes'        => $clientes,
            'tipos'           => Documento::TIPOS,
            'formasPagamento' => FinanceiroEntrada::FORMAS_PAGAMENTO,
        ]);
    }

    public function store(DocumentoRequest $request)
    {
        try {
            $dados = $request->except('itens');
            $itens = $request->input('itens', []);

            $this->documentoService->criar($dados, $itens);

            return redirect()->route('financeiro.documentos.index')
                ->with('sucesso', 'Documento criado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function show(Documento $documento)
    {
        $documento->load('itens', 'cliente', 'envios.responsavel', 'responsavel');

        return view('financeiro.documentos.show', compact('documento'));
    }

    public function edit(Documento $documento)
    {
        if (!in_array($documento->status, ['RASCUNHO', 'EMITIDO'])) {
            return back()->with('erro', 'Apenas rascunhos ou emitidos podem ser editados.');
        }

        $documento->load('itens');
        $clientes = Cliente::ativos()->orderBy('nome')->get(['id', 'nome', 'email', 'telefone']);

        return view('financeiro.documentos.form', [
            'documento'       => $documento,
            'clientes'        => $clientes,
            'tipos'           => Documento::TIPOS,
            'formasPagamento' => FinanceiroEntrada::FORMAS_PAGAMENTO,
        ]);
    }

    public function update(DocumentoRequest $request, Documento $documento)
    {
        if (!in_array($documento->status, ['RASCUNHO', 'EMITIDO'])) {
            return back()->with('erro', 'Apenas rascunhos ou emitidos podem ser editados.');
        }

        try {
            $dados = $request->except('itens');
            $itens = $request->input('itens', []);

            $this->documentoService->atualizar($documento, $dados, $itens);

            return redirect()->route('financeiro.documentos.show', $documento)
                ->with('sucesso', 'Documento atualizado!');
        } catch (\Exception $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function destroy(Documento $documento)
    {
        if ($documento->status === 'PAGO') {
            return back()->with('erro', 'Documentos pagos não podem ser excluídos.');
        }

        $documento->update(['status' => 'CANCELADO']);

        return redirect()->route('financeiro.documentos.index')
            ->with('sucesso', 'Documento cancelado!');
    }

    /**
     * Emitir o documento (RASCUNHO → EMITIDO).
     */
    public function emitir(Documento $documento)
    {
        try {
            $this->documentoService->emitir($documento);
            return back()->with('sucesso', 'Documento emitido!');
        } catch (\Exception $e) {
            return back()->with('erro', $e->getMessage());
        }
    }

    /**
     * Gerar e fazer download do PDF.
     */
    public function pdf(Documento $documento)
    {
        $documento->load('itens', 'cliente');

        $pdf = Pdf::loadView('financeiro.documentos.pdf', compact('documento'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("{$documento->numero}.pdf");
    }

    /**
     * Enviar documento por e-mail.
     */
    public function enviarEmail(Documento $documento)
    {
        $documento->load('itens', 'cliente');

        $email = $documento->cliente->email;

        if (!$email) {
            return back()->with('erro', 'O cliente não possui e-mail cadastrado.');
        }

        $pdf = Pdf::loadView('financeiro.documentos.pdf', compact('documento'))
            ->setPaper('a4', 'portrait')
            ->output();

        Mail::to($email)->send(new DocumentoMail($documento, $pdf));

        $this->documentoService->registrarEnvio($documento, 'EMAIL', $email);

        return back()->with('sucesso', "Documento enviado para {$email}!");
    }

    /**
     * Gerar link de WhatsApp com mensagem e link do PDF.
     * (O PDF é gerado no servidor, o link de envio abre o WhatsApp)
     */
    public function enviarWhatsApp(Documento $documento)
    {
        $documento->load('cliente');

        $telefone = preg_replace('/\D/', '', $documento->cliente->telefone ?? '');

        if (strlen($telefone) < 10) {
            return back()->with('erro', 'O cliente não possui telefone válido cadastrado.');
        }

        // Formata o número para WhatsApp (Brasil: 55 + DDD + número)
        if (strlen($telefone) <= 11) {
            $telefone = '55' . $telefone;
        }

        $tipo = $documento->tipo_label;
        $msg  = urlencode(
            "Olá! Segue o {$tipo} #{$documento->numero} no valor de R$ " .
            number_format($documento->valor_total, 2, ',', '.') .
            ". Acesse o documento pelo sistema MasterPrint ERP. Obrigado!"
        );

        $this->documentoService->registrarEnvio($documento, 'WHATSAPP', $telefone);

        $whatsappUrl = "https://wa.me/{$telefone}?text={$msg}";

        return redirect()->away($whatsappUrl);
    }
}
