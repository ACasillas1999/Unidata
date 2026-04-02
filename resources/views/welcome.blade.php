<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Portal de Homologacion</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|literata:500,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.16),_transparent_25%),radial-gradient(circle_at_bottom_right,_rgba(14,116,144,0.14),_transparent_30%),linear-gradient(180deg,_#fffaf0_0%,_#fffdf8_45%,_#eef6f7_100%)] text-slate-900">
        <div class="absolute inset-x-0 top-0 -z-10 h-72 bg-[linear-gradient(90deg,_rgba(120,53,15,0.06),_rgba(217,119,6,0.14),_rgba(14,116,144,0.12))] blur-3xl"></div>

        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-[2rem] border border-amber-200/70 bg-white/85 shadow-[0_25px_90px_rgba(71,45,6,0.10)] backdrop-blur">
                <div class="grid gap-8 border-b border-amber-100 px-6 py-8 lg:grid-cols-[1.3fr_0.95fr] lg:px-10">
                    <div class="space-y-5">
                        <span class="inline-flex rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-amber-900">
                            Portal Central
                        </span>

                        <div class="space-y-3">
                            <h1 class="max-w-4xl font-['Literata'] text-4xl leading-tight font-bold text-slate-950 sm:text-5xl">
                                Homologacion y sincronizacion controlada del catalogo de articulos.
                            </h1>

                            <p class="max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">
                                El proyecto concentra articulos de todas las sucursales, construye un catalogo maestro
                                homologado y replica cambios de forma unidireccional solo despues de validacion,
                                aprobacion y seguimiento por tienda.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($metrics as $metric)
                                <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $metric['label'] }}</p>
                                    <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($metric['value']) }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $metric['hint'] }}</p>
                                </article>
                            @endforeach
                        </div>

                        <div class="flex flex-wrap gap-3 text-sm">
                            <a
                                href="{{ asset('Propuesta%20Homologacion.pdf') }}"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex items-center rounded-full bg-slate-950 px-5 py-3 font-semibold text-white transition hover:bg-slate-800"
                            >
                                Abrir propuesta PDF
                            </a>

                            <div class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-800">
                                Flujo unidireccional controlado
                            </div>

                            <div class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-3 font-medium text-sky-800">
                                Base homologada + base local + sucursales
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5">
                        <article class="rounded-[1.75rem] border border-slate-200 bg-slate-950 p-5 text-sm text-slate-200 shadow-inner shadow-black/20">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h2 class="font-semibold text-white">Arquitectura operativa</h2>
                                <span class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-[0.24em] text-slate-400">
                                    3 capas
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-amber-200">Sucursales</p>
                                    <p class="mt-2 leading-6 text-slate-300">Bases operativas de cada tienda con sus propios articulos y reglas.</p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-amber-200">Base Homologada</p>
                                    <p class="mt-2 leading-6 text-slate-300">Catalogo maestro unico desde el que se aprueban y publican cambios.</p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-amber-200">Base Local del Programa</p>
                                    <p class="mt-2 leading-6 text-slate-300">Conexiones, lotes, auditoria y reglas por sucursal.</p>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-amber-200 bg-amber-50 p-5">
                            <h2 class="font-semibold text-amber-950">Principio de control</h2>
                            <p class="mt-2 text-sm leading-7 text-amber-900/80">
                                Los cambios a articulos deben nacer en el sistema central, pasar por estandarizacion y aprobacion,
                                y solo despues replicarse a las tiendas. No se sincroniza directamente desde las sucursales.
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="grid gap-8 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_65px_rgba(15,23,42,0.08)]">
                    <div class="border-b border-slate-200 px-6 py-5 lg:px-8">
                        <h2 class="font-['Literata'] text-2xl font-bold text-slate-950">Modulos clave del portal</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            La estructura local ya contempla catalogo, homologacion, importacion, sincronizacion, auditoria y administracion.
                        </p>
                    </div>

                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2 lg:px-8">
                        @foreach ($modules as $module)
                            <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                <div class="@if ($module['accent'] === 'amber') border-amber-200 bg-amber-100 text-amber-900 @elseif ($module['accent'] === 'emerald') border-emerald-200 bg-emerald-100 text-emerald-900 @elseif ($module['accent'] === 'sky') border-sky-200 bg-sky-100 text-sky-900 @elseif ($module['accent'] === 'rose') border-rose-200 bg-rose-100 text-rose-900 @elseif ($module['accent'] === 'violet') border-violet-200 bg-violet-100 text-violet-900 @else border-slate-300 bg-slate-200 text-slate-800 @endif mb-4 inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">
                                    {{ $module['accent'] }}
                                </div>

                                <h3 class="text-lg font-semibold text-slate-950">{{ $module['title'] }}</h3>
                                <p class="mt-2 text-sm leading-7 text-slate-600">{{ $module['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </article>

                <div class="grid gap-8">
                    <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_65px_rgba(15,23,42,0.08)]">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h2 class="font-['Literata'] text-2xl font-bold text-slate-950">Flujo recomendado</h2>
                        </div>

                        <ol class="space-y-4 px-6 py-6">
                            @foreach ($flow as $step)
                                <li class="flex gap-4">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                                        {{ $loop->iteration }}
                                    </span>
                                    <p class="pt-1 text-sm leading-7 text-slate-600">{{ $step }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </article>

                    <article class="overflow-hidden rounded-[2rem] border border-rose-200 bg-rose-50 shadow-[0_20px_65px_rgba(190,24,93,0.08)]">
                        <div class="border-b border-rose-200 px-6 py-5">
                            <h2 class="font-['Literata'] text-2xl font-bold text-rose-950">Riesgos a controlar</h2>
                        </div>

                        <ul class="space-y-3 px-6 py-6">
                            @foreach ($risks as $risk)
                                <li class="rounded-2xl border border-rose-200 bg-white/70 px-4 py-4 text-sm leading-7 text-rose-900">
                                    {{ $risk }}
                                </li>
                            @endforeach
                        </ul>
                    </article>
                </div>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_65px_rgba(15,23,42,0.08)]">
                <div class="grid gap-8 border-b border-slate-200 px-6 py-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
                    <div class="space-y-4">
                        <div>
                            <h2 class="font-['Literata'] text-2xl font-bold text-slate-950">Modulo actual de homologacion</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Esta consulta sigue siendo el punto de partida para detectar presencia o ausencia del articulo entre las bases actuales.
                            </p>
                        </div>

                        <form method="GET" action="{{ route('home') }}" class="grid gap-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5 lg:grid-cols-[1fr_auto_auto]">
                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-700">Buscar articulo</span>
                                <input
                                    type="text"
                                    name="q"
                                    value="{{ $search }}"
                                    placeholder="Codigo o descripcion"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none ring-0 transition placeholder:text-slate-400 focus:border-amber-500"
                                >
                            </label>

                            <button
                                type="submit"
                                class="rounded-2xl bg-amber-500 px-5 py-3 text-sm font-semibold text-amber-950 transition hover:bg-amber-400"
                            >
                                Ejecutar consulta
                            </button>

                            <a
                                href="{{ route('home') }}"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100"
                            >
                                Limpiar
                            </a>
                        </form>
                    </div>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-950 p-5 text-sm text-slate-200 shadow-inner shadow-black/20">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="font-semibold text-white">Consulta SQL base</h2>
                            <span class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-[0.24em] text-slate-400">
                                MySQL
                            </span>
                        </div>

                        <pre class="max-h-[26rem] overflow-auto rounded-2xl bg-black/30 p-4 text-xs leading-6 text-amber-100">{{ $sqlQuery }}</pre>
                    </div>
                </div>

                @if ($error)
                    <section class="border-b border-rose-200 bg-rose-50 px-6 py-5 text-rose-800 lg:px-8">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.24em] text-rose-600">Error de conexion</h2>
                        <p class="mt-2 max-w-4xl text-sm leading-7">{{ $error }}</p>
                    </section>
                @endif

                <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-end lg:justify-between lg:px-8">
                    <div>
                        <h3 class="font-semibold text-slate-950">Resultado de homologacion</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            @if ($search !== '')
                                Filtrando por: <span class="font-semibold text-slate-800">{{ $search }}</span>
                            @else
                                Mostrando articulos habilitados en Deasa.
                            @endif
                        </p>
                    </div>

                    <div class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-600">
                        {{ count($articles->items()) }} registros en esta pagina
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-950 text-xs uppercase tracking-[0.18em] text-slate-300">
                            <tr>
                                <th class="px-4 py-4 font-semibold">Codigo Deasa</th>
                                <th class="px-4 py-4 font-semibold">Descripcion Deasa</th>
                                <th class="px-4 py-4 font-semibold">Aiesa</th>
                                <th class="px-4 py-4 font-semibold">Dimegsa</th>
                                <th class="px-4 py-4 font-semibold">Queretaro</th>
                                <th class="px-4 py-4 font-semibold">Segsa</th>
                                <th class="px-4 py-4 font-semibold">Tapatia</th>
                                <th class="px-4 py-4 font-semibold">Vallarta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($articles as $article)
                                <tr class="align-top transition hover:bg-amber-50/50">
                                    <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-900">{{ $article->Codigo_Deasa }}</td>
                                    <td class="min-w-80 px-4 py-4 text-slate-600">{{ $article->Descripcion_Deasa }}</td>
                                    <td class="px-4 py-4">@include('partials.status-pill', ['value' => $article->En_Aiesa])</td>
                                    <td class="px-4 py-4">@include('partials.status-pill', ['value' => $article->En_Dimegsa])</td>
                                    <td class="px-4 py-4">@include('partials.status-pill', ['value' => $article->En_Queretaro])</td>
                                    <td class="px-4 py-4">@include('partials.status-pill', ['value' => $article->En_Segsa])</td>
                                    <td class="px-4 py-4">@include('partials.status-pill', ['value' => $article->En_Tapatia])</td>
                                    <td class="px-4 py-4">@include('partials.status-pill', ['value' => $article->En_Vallarta])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
                                        No hay registros para mostrar con la configuracion actual.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between gap-4 border-t border-slate-200 bg-slate-50 px-6 py-4 lg:px-8">
                    <p class="text-sm text-slate-500">
                        La homologacion visual es solo una parte del flujo. El objetivo final es aprobar cambios y sincronizarlos por lote con trazabilidad.
                    </p>

                    @if ($articles->hasPages())
                        <div>
                            {{ $articles->links() }}
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </body>
</html>
