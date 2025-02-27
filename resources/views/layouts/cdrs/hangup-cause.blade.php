<span class="badge badge-outline-{{ $color }}" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top"
@if (isset($hint)) data-bs-content=" {{ $hint ?? ''}}" @endif>{{ ucwords(str_replace('_', ' ', strtolower($value))) }}</span>
