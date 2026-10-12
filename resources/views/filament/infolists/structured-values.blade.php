<dl style="display:grid;gap:0.75rem;min-width:0;">
    @forelse ((array) $values as $key => $value)
        @php
            $label = is_numeric($key) ? 'Item '.((int) $key + 1) : \Illuminate\Support\Str::headline($key);
            $nested = is_array($value) || is_object($value);
        @endphp
        <div style="border-left:2px solid #9ca3af;padding-left:0.75rem;min-width:0;">
            @if ($nested)
                <dt class="sr-only">{{ $label }}</dt>
                <dd>
                    <details>
                        <summary style="font-weight:600;cursor:pointer;padding:0.25rem 0;">{{ $label }}</summary>
                        <div style="margin-top:0.5rem;">
                            @include('filament.infolists.structured-values', ['values' => $value])
                        </div>
                    </details>
                </dd>
            @else
                <dt style="font-weight:600;">{{ $label }}</dt>
                <dd style="margin-top:0.25rem;overflow-wrap:anywhere;"><span style="white-space:pre-wrap;">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : (($value === null || $value === '') ? 'Not provided' : $value) }}</span></dd>
            @endif
        </div>
    @empty
        <div>No details recorded.</div>
    @endforelse
</dl>
