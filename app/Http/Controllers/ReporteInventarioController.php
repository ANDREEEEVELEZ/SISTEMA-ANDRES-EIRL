<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteInventarioController extends Controller
{
    /**
     * Generar reporte de productos en stock
     */
    public function reporteStock(Request $request)
    {
        // Validar filtros opcionales
        $filtros = $request->validate([
            'categoria_id' => 'nullable|exists:categorias,id',
            'estado_stock' => 'nullable|in:todos,agotados,bajo,normal',
        ]);

        // Obtener productos
        $query = Producto::with('categoria')
            ->orderBy('nombre_producto');

        // Aplicar filtro de categoría
        if (isset($filtros['categoria_id'])) {
            $query->where('categoria_id', $filtros['categoria_id']);
        }

        // Aplicar filtro de estado de stock
        if (isset($filtros['estado_stock']) && $filtros['estado_stock'] !== 'todos') {
            switch ($filtros['estado_stock']) {
                case 'agotados':
                    $query->agotados();
                    break;
                case 'bajo':
                    $query->stockBajo();
                    break;
                case 'normal':
                    $query->whereRaw('stock_total > stock_minimo');
                    break;
            }
        }

        $productos = $query->get();

        // Calcular totales
        $totalProductos = $productos->count();
        $productosAgotados = $productos->where('stock_total', '<=', 0)->count();
        $productosStockBajo = $productos->filter(fn($p) => $p->stock_total > 0 && $p->stock_total <= $p->stock_minimo)->count();
        $stockTotal = $productos->sum('stock_total');

        // Datos para el PDF
        $data = [
            'titulo' => 'Reporte de Inventario - Productos en Stock',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s'),
            'generado_por' => Auth::user()->name,
            'productos' => $productos,
            'resumen' => [
                'total_productos' => $totalProductos,
                'productos_agotados' => $productosAgotados,
                'productos_stock_bajo' => $productosStockBajo,
                'stock_total' => $stockTotal,
            ],
            'filtros' => $filtros,
        ];

        // Generar PDF
        $pdf = Pdf::loadView('reportes.inventario-stock', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('reporte-inventario-stock-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Generar reporte de movimientos de inventario
     */
    public function reporteMovimientos(Request $request)
    {
        // Validar fechas
        $filtros = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo' => 'nullable|in:todos,entrada,salida,ajuste',
            'producto_id' => 'nullable|exists:productos,id',
        ]);

        // Establecer fechas por defecto (mes actual)
        $fechaInicio = isset($filtros['fecha_inicio']) 
            ? Carbon::parse($filtros['fecha_inicio']) 
            : Carbon::now()->startOfMonth();
        
        $fechaFin = isset($filtros['fecha_fin']) 
            ? Carbon::parse($filtros['fecha_fin']) 
            : Carbon::now()->endOfMonth();

        // Obtener movimientos
        $query = MovimientoInventario::with(['producto', 'user'])
            ->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_movimiento', 'desc')
            ->orderBy('created_at', 'desc');

        // Aplicar filtro de tipo
        if (isset($filtros['tipo']) && $filtros['tipo'] !== 'todos') {
            $query->where('tipo', $filtros['tipo']);
        }

        // Aplicar filtro de producto
        if (isset($filtros['producto_id'])) {
            $query->where('producto_id', $filtros['producto_id']);
        }

        // Si el usuario no es super_admin, filtrar por sus movimientos
        if (!Auth::user()->hasRole('super_admin')) {
            $query->where('user_id', Auth::id());
        }

        $movimientos = $query->get();

        // Calcular estadísticas
        $totalMovimientos = $movimientos->count();
        $entradas = $movimientos->where('tipo', 'entrada');
        $salidas = $movimientos->where('tipo', 'salida');
        $ajustes = $movimientos->where('tipo', 'ajuste');

        $totalEntradas = $entradas->sum('cantidad_movimiento');
        $totalSalidas = $salidas->sum('cantidad_movimiento');
        $cantidadAjustes = $ajustes->count();

        // Datos para el PDF
        $data = [
            'titulo' => 'Reporte de Movimientos de Inventario',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s'),
            'generado_por' => Auth::user()->name,
            'periodo' => [
                'inicio' => $fechaInicio->format('d/m/Y'),
                'fin' => $fechaFin->format('d/m/Y'),
            ],
            'movimientos' => $movimientos,
            'resumen' => [
                'total_movimientos' => $totalMovimientos,
                'total_entradas' => $totalEntradas,
                'cantidad_entradas' => $entradas->count(),
                'total_salidas' => $totalSalidas,
                'cantidad_salidas' => $salidas->count(),
                'cantidad_ajustes' => $cantidadAjustes,
            ],
            'filtros' => $filtros,
        ];

        // Generar PDF
        $pdf = Pdf::loadView('reportes.inventario-movimientos', $data);
        $pdf->setPaper('A4', 'landscape'); // Horizontal para más espacio

        return $pdf->download('reporte-movimientos-inventario-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Generar reporte combinado (stock + movimientos)
     */
    public function reporteCompleto(Request $request)
    {
        // Validar filtros
        $filtros = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        // Establecer fechas por defecto (mes actual)
        $fechaInicio = isset($filtros['fecha_inicio']) 
            ? Carbon::parse($filtros['fecha_inicio']) 
            : Carbon::now()->startOfMonth();
        
        $fechaFin = isset($filtros['fecha_fin']) 
            ? Carbon::parse($filtros['fecha_fin']) 
            : Carbon::now()->endOfMonth();

        // Obtener productos
        $productos = Producto::with('categoria')
            ->orderBy('nombre_producto')
            ->get();

        // Obtener movimientos del periodo
        $query = MovimientoInventario::with(['producto', 'user'])
            ->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_movimiento', 'desc');

        if (!Auth::user()->hasRole('super_admin')) {
            $query->where('user_id', Auth::id());
        }

        $movimientos = $query->get();

        // Calcular estadísticas de stock
        $totalProductos = $productos->count();
        $productosAgotados = $productos->where('stock_total', '<=', 0)->count();
        $productosStockBajo = $productos->filter(fn($p) => $p->stock_total > 0 && $p->stock_total <= $p->stock_minimo)->count();
        $stockTotal = $productos->sum('stock_total');

        // Calcular estadísticas de movimientos
        $totalMovimientos = $movimientos->count();
        $entradas = $movimientos->where('tipo', 'entrada');
        $salidas = $movimientos->where('tipo', 'salida');
        $ajustes = $movimientos->where('tipo', 'ajuste');

        // Datos para el PDF
        $data = [
            'titulo' => 'Reporte Completo de Inventario',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s'),
            'generado_por' => Auth::user()->name,
            'periodo' => [
                'inicio' => $fechaInicio->format('d/m/Y'),
                'fin' => $fechaFin->format('d/m/Y'),
            ],
            'productos' => $productos,
            'movimientos' => $movimientos->take(50), // Limitamos a 50 movimientos más recientes
            'resumen_stock' => [
                'total_productos' => $totalProductos,
                'productos_agotados' => $productosAgotados,
                'productos_stock_bajo' => $productosStockBajo,
                'stock_total' => $stockTotal,
            ],
            'resumen_movimientos' => [
                'total_movimientos' => $totalMovimientos,
                'total_entradas' => $entradas->sum('cantidad_movimiento'),
                'cantidad_entradas' => $entradas->count(),
                'total_salidas' => $salidas->sum('cantidad_movimiento'),
                'cantidad_salidas' => $salidas->count(),
                'cantidad_ajustes' => $ajustes->count(),
            ],
        ];

        // Generar PDF
        $pdf = Pdf::loadView('reportes.inventario-completo', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('reporte-completo-inventario-' . now()->format('Y-m-d') . '.pdf');
    }
}
