@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if(trim($slot) === 'solidtime')
<img src="{{ asset('images/hellospace-logo.png') }}" srcset="{{ asset('images/hellospace-logo.svg') }}" class="logo" alt="hellospace Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
