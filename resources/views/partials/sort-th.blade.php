{{--
    Sortable table header partial.
    Usage: @include('partials.sort-th', ['col' => 'name', 'label' => 'Name', 'sort' => $sort, 'dir' => $dir])
--}}
@php
    $isActive  = $sort === $col;
    $nextDir   = ($isActive && $dir === 'asc') ? 'desc' : 'asc';
    $icon      = $isActive ? ($dir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    $params    = array_merge(request()->except(['sort','dir','page']), ['sort' => $col, 'dir' => $nextDir]);
@endphp
<th>
    <a href="{{ request()->fullUrlWithQuery(['sort' => $col, 'dir' => $nextDir]) }}"
       class="text-decoration-none text-dark d-flex align-items-center gap-1">
        {{ $label }}
        <i class="bi {{ $icon }} {{ $isActive ? 'text-primary' : 'text-muted' }}" style="font-size:.8rem;"></i>
    </a>
</th>
