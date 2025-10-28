<?php

namespace App\Http\Controllers;

use App\Models\Arqueo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportesController extends Controller
{
    /**
     * Genera y descarga/visualiza el PDF de un arqueo existente
     */
    public function arqueoPdf($id)
    {
        $arqueo = Arqueo::with(['caja', 'user'])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.arqueo', compact('arqueo'));

        $filename = sprintf('arqueo_caja_%d_%s.pdf', $arqueo->caja_id, $arqueo->created_at->format('Ymd_His'));

        return $pdf->stream($filename);
    }
}
