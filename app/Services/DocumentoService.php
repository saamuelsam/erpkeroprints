<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\DocumentoEnvio;
use App\Models\DocumentoItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentoService
{
    /**
     * Cria documento com itens em uma transação.
     */
    public function criar(array $dados, array $itens): Documento
    {
        return DB::transaction(function () use ($dados, $itens) {
            $dados['numero'] = Documento::gerarNumero();
            $dados['user_id'] = Auth::id();

            $documento = Documento::create($dados);

            foreach ($itens as $item) {
                $documento->itens()->create($item);
            }

            $documento->recalcularTotais();

            return $documento->fresh('itens', 'cliente');
        });
    }

    /**
     * Atualiza documento e itens.
     */
    public function atualizar(Documento $documento, array $dados, array $itens): Documento
    {
        return DB::transaction(function () use ($documento, $dados, $itens) {
            $documento->update($dados);

            // Remove itens anteriores e recria
            $documento->itens()->delete();

            foreach ($itens as $item) {
                $documento->itens()->create($item);
            }

            $documento->recalcularTotais();

            return $documento->fresh('itens', 'cliente');
        });
    }

    /**
     * Emitir documento (muda de rascunho para emitido).
     */
    public function emitir(Documento $documento): Documento
    {
        if ($documento->status !== 'RASCUNHO') {
            throw new \Exception('Apenas rascunhos podem ser emitidos.');
        }

        $documento->update(['status' => 'EMITIDO']);
        return $documento;
    }

    /**
     * Registra envio do documento (email ou WhatsApp).
     */
    public function registrarEnvio(Documento $documento, string $tipo, string $destinatario, ?array $detalhes = null): DocumentoEnvio
    {
        $envio = DocumentoEnvio::create([
            'documento_id' => $documento->id,
            'tipo'         => $tipo,
            'destinatario' => $destinatario,
            'user_id'      => Auth::id(),
            'detalhes'     => $detalhes,
        ]);

        // Se o documento ainda está como EMITIDO, marca como ENVIADO
        if (in_array($documento->status, ['EMITIDO', 'RASCUNHO'])) {
            $documento->update(['status' => 'ENVIADO']);
        }

        return $envio;
    }
}
