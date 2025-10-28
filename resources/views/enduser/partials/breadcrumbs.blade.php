@if (!empty($items) && is_array($items))
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      @foreach ($items as $idx => $item)
        @php $last = $idx === array_key_last($items); @endphp
        @if(!$last)
          <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
        @else
          <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
        @endif
      @endforeach
    </ol>
  </nav>
@endif

