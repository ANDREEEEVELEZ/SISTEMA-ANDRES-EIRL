<?php

namespace App\Http\Controllers;

use App\Services\ApisNetPeService;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    protected $apisNet;

    public function __construct(ApisNetPeService $apisNet)
    {
        $this->apisNet = $apisNet;
    }

    public function buscarPorDocumento(Request $request)
    {
        $numero = $request->input('numero');
        $tipo = $request->input('tipo'); // 'ruc' o 'dni'

        if ($tipo === 'ruc') {
            $data = $this->apisNet->consultarRuc($numero);
        } else {
            $data = $this->apisNet->consultarDni($numero);
        }

        return response()->json($data);
    }
}
