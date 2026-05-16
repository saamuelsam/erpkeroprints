@foreach($categorias as $menuCategoria)
    <a href="{{ route('produtos.index', ['categoria_id' => $menuCategoria->id]) }}"
       class="nav-link {{ request()->routeIs('produtos.index') && (string) request('categoria_id') === (string) $menuCategoria->id ? 'active' : '' }}"
       style="padding-left: {{ 34 + ($nivel * 14) }}px">
        <i class="{{ $menuCategoria->children->isEmpty() ? 'fa-regular fa-folder' : 'fa-solid fa-folder-tree' }}"></i>
        {{ $menuCategoria->nome }}
    </a>
    @if($menuCategoria->children->isNotEmpty())
        @include('layouts._category_menu', ['categorias' => $menuCategoria->children, 'nivel' => $nivel + 1])
    @endif
@endforeach
