<?php

namespace App\Http\Controllers;

use App\Services\PortalMetricsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomologationController extends Controller
{
    private const SQL_QUERY = <<<'SQL'
SELECT
    D.Clave_Articulo AS Codigo_Deasa,
    D.Descripcion AS Descripcion_Deasa,
    IF(A.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Aiesa,
    IF(DI.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Dimegsa,
    IF(Q.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Queretaro,
    IF(S.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Segsa,
    IF(T.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Tapatia,
    IF(V.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Vallarta
FROM deasa.articulo D
LEFT JOIN aiesa.articulo A     ON A.Clave_Articulo = D.Clave_Articulo AND A.Habilitado = 1
LEFT JOIN dimegsa.articulo DI  ON DI.Clave_Articulo = D.Clave_Articulo AND DI.Habilitado = 1
LEFT JOIN queretaro.articulo Q ON Q.Clave_Articulo = D.Clave_Articulo AND Q.Habilitado = 1
LEFT JOIN segsa.articulo S     ON S.Clave_Articulo = D.Clave_Articulo AND S.Habilitado = 1
LEFT JOIN tapatia.articulo T   ON T.Clave_Articulo = D.Clave_Articulo AND T.Habilitado = 1
LEFT JOIN vallarta.articulo V  ON V.Clave_Articulo = D.Clave_Articulo AND V.Habilitado = 1
WHERE D.Habilitado = 1
SQL;

    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $articles = null;
        $error = null;
        $portal = app(PortalMetricsService::class);

        try {
            $articles = $this->buildQuery($search)
                ->orderBy('D.Clave_Articulo')
                ->simplePaginate(30)
                ->withQueryString();
        } catch (QueryException $exception) {
            $articles = new Paginator([], 30);
            $error = 'No fue posible ejecutar la consulta de homologacion. Verifica que existan las bases de datos deasa, aiesa, dimegsa, queretaro, segsa, tapatia y vallarta, y que el usuario de MySQL tenga acceso.';
        }

        return view('welcome', [
            'articles' => $articles,
            'error' => $error,
            'metrics' => $portal->summary(),
            'modules' => $portal->modules(),
            'flow' => $portal->flow(),
            'risks' => $portal->risks(),
            'search' => $search,
            'sqlQuery' => self::SQL_QUERY,
        ]);
    }

    private function buildQuery(string $search): Builder
    {
        $query = DB::table('deasa.articulo as D')
            ->selectRaw("
                D.Clave_Articulo AS Codigo_Deasa,
                D.Descripcion AS Descripcion_Deasa,
                IF(A.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Aiesa,
                IF(DI.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Dimegsa,
                IF(Q.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Queretaro,
                IF(S.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Segsa,
                IF(T.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Tapatia,
                IF(V.Clave_Articulo IS NOT NULL, 'ACTIVO', 'INACTIVO/FALTA') AS En_Vallarta
            ")
            ->leftJoin('aiesa.articulo as A', function ($join): void {
                $join->on('A.Clave_Articulo', '=', 'D.Clave_Articulo')
                    ->where('A.Habilitado', '=', 1);
            })
            ->leftJoin('dimegsa.articulo as DI', function ($join): void {
                $join->on('DI.Clave_Articulo', '=', 'D.Clave_Articulo')
                    ->where('DI.Habilitado', '=', 1);
            })
            ->leftJoin('queretaro.articulo as Q', function ($join): void {
                $join->on('Q.Clave_Articulo', '=', 'D.Clave_Articulo')
                    ->where('Q.Habilitado', '=', 1);
            })
            ->leftJoin('segsa.articulo as S', function ($join): void {
                $join->on('S.Clave_Articulo', '=', 'D.Clave_Articulo')
                    ->where('S.Habilitado', '=', 1);
            })
            ->leftJoin('tapatia.articulo as T', function ($join): void {
                $join->on('T.Clave_Articulo', '=', 'D.Clave_Articulo')
                    ->where('T.Habilitado', '=', 1);
            })
            ->leftJoin('vallarta.articulo as V', function ($join): void {
                $join->on('V.Clave_Articulo', '=', 'D.Clave_Articulo')
                    ->where('V.Habilitado', '=', 1);
            })
            ->where('D.Habilitado', 1);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search): void {
            $builder
                ->where('D.Clave_Articulo', 'like', "%{$search}%")
                ->orWhere('D.Descripcion', 'like', "%{$search}%");
        });
    }
}
