@extends('layouts.app')

@section('title', 'Homologación de Clientes')
@section('breadcrumb', 'Clientes / Homologación')

@section('content')

{{-- Page Header --}}
<div class="page-header shadow-premium" style="margin-bottom: 20px; padding: 18px 24px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div class="page-header-content" style="display:flex; gap:16px; align-items:center;">
        <div class="page-header-icon shadow-premium" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34d399;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
        </div>
        <div>
            <h1 class="page-title" style="margin:0;">Homologación de Clientes</h1>
            <p class="page-subtitle" style="margin:4px 0 0; color:var(--text-secondary);">Busca directo en las sucursales · encuentra clientes que no están en Lista de Clientes</p>
        </div>
    </div>
    <a href="{{ route('clientes.index') }}" class="btn btn--ghost btn--sm" style="border:1px solid var(--border); padding:9px 16px;">Lista de Clientes</a>
</div>

<div class="glass-card shadow-premium" style="display:flex; flex-direction:column; overflow:hidden; border:1px solid var(--border);">

    <form method="GET" action="{{ route('clientes.homologacion') }}" style="margin:0;">
        <div style="padding:16px 20px; background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); display:flex; flex-direction:column; gap:14px;">
            <div>
                <h2 class="card-title" style="margin:0; font-size:16px; font-weight:800; color:white;">Buscar en sucursales</h2>
                <p class="card-subtitle" style="margin:4px 0 0; font-size:12px; color:var(--text-secondary);">
                    RFC (con o sin espacios: "CVA 050825H75" = "CVA050825H75") o parte de la razón social.
                    Consulta {{ $branches->pluck('name')->map(fn($n) => ucfirst(strtolower($n)))->implode(', ') }}.
                </p>
            </div>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div class="search-input-wrap" style="flex:1; min-width:240px; display:flex; align-items:center; background:rgba(0,0,0,0.25); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
                    <span style="padding:0 12px; color:var(--text-muted); display:flex; align-items:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input type="text" name="q" value="{{ $search }}" placeholder="RFC o Razón Social…" autocomplete="off" autofocus
                           style="width:100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:12.5px; font-weight:600;">
                </div>
                <button type="submit" class="btn btn--primary btn--sm shadow-premium" style="background:var(--grad-premium); border:none; padding:8px 18px; font-size:12px;">Buscar</button>
                @if($search)
                    <a href="{{ route('clientes.homologacion') }}" class="btn btn--ghost btn--sm" style="border:1px solid var(--border); padding:8px 16px; font-size:12px;">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    @if($aviso)
        <div style="margin:14px 20px 0; background:rgba(251,191,36,0.08); border:1px solid rgba(251,191,36,0.25); border-radius:10px; padding:10px 14px; color:#fbbf24; font-size:12.5px;">{{ $aviso }}</div>
    @endif

    @foreach($erroresSucursal as $code => $msg)
        <div style="margin:10px 20px 0; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); border-radius:10px; padding:10px 14px; color:#f87171; font-size:12.5px;">
            No se pudo consultar <strong>{{ $code }}</strong>: {{ \Illuminate\Support\Str::limit($msg, 180) }}
        </div>
    @endforeach

    @if($search === '')
        <div style="padding:40px 20px; text-align:center; color:var(--text-muted); font-size:13px;">Escribe un RFC o razón social para ver en qué sucursales existe el cliente.</div>
    @elseif(empty($clientes) && !$aviso)
        <div style="padding:40px 20px; text-align:center; color:var(--text-muted); font-size:13px;">Sin resultados para "{{ $search }}" en ninguna sucursal.</div>
    @elseif(!empty($clientes))
        <div style="padding:10px 20px; font-size:12px; color:var(--text-secondary);">{{ count($clientes) }} {{ count($clientes) === 1 ? 'cliente encontrado' : 'clientes encontrados' }}</div>
        <div class="custom-scroll" style="overflow:auto; max-height:calc(100vh - 330px); width:100%; background:#0b0f1a;">
            <table class="data-table" style="border-collapse:separate; border-spacing:0; width:100%;">
                <thead>
                    <tr>
                        <th style="position:sticky; top:0; background:#1a1f2e; min-width:140px;">RFC</th>
                        <th style="position:sticky; top:0; background:#1a1f2e; min-width:220px;">Razón social</th>
                        @foreach($branches as $b)
                            <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:130px;">{{ ucfirst(strtolower($b->name)) }}</th>
                        @endforeach
                        <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:110px;">Maestro</th>
                        <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:110px;">Alta más antigua</th>
                        <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:140px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $c)
                        @php
                            $faltan = $branches->filter(fn($b) => !isset($c['sucursales'][$b->code]) && !isset($erroresSucursal[$b->code]))->count();
                            $inactivo = collect($c['sucursales'])->contains(fn($s) => $s['status'] !== 'A');
                        @endphp
                        <tr>
                            <td style="font-family:monospace; font-weight:700; white-space:nowrap;">{{ $c['rfc'] }}</td>
                            <td>{{ $c['razon_social'] }}</td>
                            @foreach($branches as $b)
                                @php($s = $c['sucursales'][$b->code] ?? null)
                                <td style="text-align:center; font-size:11.5px; line-height:1.5;">
                                    @if(isset($erroresSucursal[$b->code]))
                                        <span style="color:#f87171;" title="No se pudo consultar">⚠️</span>
                                    @elseif($s)
                                        <div style="color:#34d399; font-weight:800;">✅ #{{ $s['cliente'] }}</div>
                                        <div style="color:var(--text-muted);">IdGlobal {{ $s['id_global'] }}</div>
                                        <div style="color:{{ $s['status'] === 'A' ? 'var(--text-muted)' : '#fbbf24' }};">
                                            {{ $s['status'] === 'A' ? 'Activo' : ($s['status'] === '' ? 'Sin estatus' : "Status Magic ({$s['status']})") }} · {{ $s['fecha_alta'] ?: 's/f' }}
                                        </div>
                                        @if($s['duplicado'])
                                            <div style="color:#fbbf24;" title="El mismo RFC aparece en más de un cliente de esta sucursal">RFC repetido</div>
                                        @endif
                                    @else
                                        <span style="color:#f87171; font-weight:800;">❌</span>
                                    @endif
                                </td>
                            @endforeach
                            <td style="text-align:center; font-size:11.5px;">
                                @if($c['maestro'])
                                    <div style="color:#34d399; font-weight:800;">✅</div>
                                    <div style="color:var(--text-muted);">IdGlobal {{ $c['maestro']['id_global'] }}</div>
                                @else
                                    <span style="color:#f87171; font-weight:800;">❌</span>
                                @endif
                            </td>
                            <td style="text-align:center; font-size:11.5px;">{{ $c['fecha_alta_min'] ?? '—' }}</td>
                            <td style="text-align:center; font-size:11.5px; line-height:1.5;">
                                @if($c['maestro'] && $faltan === 0)
                                    <span style="color:#34d399; font-weight:700;">Homologado</span>
                                @else
                                    <span style="color:#fbbf24; font-weight:700;">
                                        {{ $c['maestro'] ? '' : 'Falta en maestro' }}{{ !$c['maestro'] && $faltan ? ' · ' : '' }}{{ $faltan ? "Falta en {$faltan} " . ($faltan === 1 ? 'sucursal' : 'sucursales') : '' }}
                                    </span>
                                @endif
                                @if($inactivo)
                                    <div style="color:#fbbf24;">No activo en alguna sucursal</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
