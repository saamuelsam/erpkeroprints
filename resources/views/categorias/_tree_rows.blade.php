@foreach($categorias as $categoria)
<tr>
    <td class="fw-semibold">
        <span style="padding-left: {{ $nivel * 22 }}px">
            @if($nivel > 0)
                <i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-2"></i>
            @endif
            {{ $categoria->nome }}
        </span>
    </td>
    <td class="text-muted">{{ $categoria->descricao ?: '—' }}</td>
    <td class="text-center"><span class="badge bg-primary-subtle text-primary rounded-pill">{{ $categoria->produtos_count }}</span></td>
    <td class="text-center">
        <span class="badge {{ $categoria->ativo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
            {{ $categoria->ativo ? 'Ativa' : 'Inativa' }}
        </span>
    </td>
    <td class="text-end">
        <div class="d-flex gap-1 justify-content-end">
            <a href="{{ route('categorias.create', ['parent_id' => $categoria->id]) }}" class="btn btn-sm btn-outline-primary" title="Criar dentro"><i class="fa-solid fa-plus"></i></a>
            <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="fa-solid fa-pen"></i></a>
            @if($categoria->produtos_count == 0 && $categoria->children->isEmpty())
            <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" onsubmit="return confirm('Confirmar exclusao de {{ $categoria->nome }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="fa-solid fa-trash"></i></button>
            </form>
            @endif
        </div>
    </td>
</tr>
@if($categoria->children->isNotEmpty())
    @include('categorias._tree_rows', ['categorias' => $categoria->children, 'nivel' => $nivel + 1])
@endif
@endforeach
