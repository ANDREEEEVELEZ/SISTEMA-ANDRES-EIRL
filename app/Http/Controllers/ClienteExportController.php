<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ClienteExportController extends Controller
{
    public function exportarPdf(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('tipo_cliente')) {
            $query->where('tipo_cliente', $request->tipo_cliente);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo_doc')) {
            $query->where('tipo_doc', $request->tipo_doc);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_registro', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_registro', '<=', $request->fecha_hasta);
        }
        if ($request->filled('nombre_razon')) {
            $query->where('nombre_razon', 'like', '%'.$request->nombre_razon.'%');
        }
        if ($request->boolean('solo_con_ventas')) {
            $query->whereHas('ventas');
        }

        $clientes = $query->get();

        $pdf = Pdf::loadView('clientes.export-pdf', compact('clientes'));
        $filename = 'clientes_'.now()->format('Ymd_His').'.pdf';
        return $pdf->download($filename);
    }

    public function imprimirClientePdf($id)
    {
        $cliente = Cliente::with('ventas')->findOrFail($id);

        $pdf = Pdf::loadView('clientes.imprimir-cliente-pdf', compact('cliente'));
        $filename = 'cliente_'.$cliente->num_doc.'_'.now()->format('Ymd_His').'.pdf';
        return $pdf->download($filename);
    }
}
