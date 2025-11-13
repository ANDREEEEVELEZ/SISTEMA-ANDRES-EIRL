<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\Log;

class ApisNetPeService
{
    protected $client;
    protected $token;

    public function __construct()
    {
        $this->token = env('APIS_NET_PE_TOKEN'); // token de Decolecta
        $this->client = new Client([
            'base_uri' => 'https://api.decolecta.com',
            'verify' => false,
        ]);
    }

    /**
     * Consultar datos por RUC.
     */
    public function consultarRuc($numero)
    {
        try {
            $res = $this->client->request('GET', '/v1/sunat/ruc', [
                'http_errors' => false,
                'connect_timeout' => 10,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                ],
                'query' => ['numero' => $numero],
            ]);

            $status = $res->getStatusCode();
            $body = $res->getBody()->getContents();

            // Registrar respuesta para facilitar debugging cuando no se encuentran datos
            if ($status !== 200) {
                Log::warning('ApisNetPeService::consultarRuc no 200', [
                    'numero' => $numero,
                    'status' => $status,
                    'body' => $body,
                ]);
            } else {
                // También loguear respuestas vacías o inesperadas
                if (empty(trim($body))) {
                    Log::warning('ApisNetPeService::consultarRuc respuesta vacía', [
                        'numero' => $numero,
                        'status' => $status,
                        'body' => $body,
                    ]);
                }
            }

            return json_decode($body, true);
        } catch (Exception $e) {
            Log::error('ApisNetPeService::consultarRuc excepción', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Consultar datos por DNI.
     */
    public function consultarDni($numero)
    {
        try {
            $res = $this->client->request('GET', '/v1/reniec/dni', [
                'http_errors' => false,
                'connect_timeout' => 10,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                ],
                'query' => ['numero' => $numero],
            ]);

            $status = $res->getStatusCode();
            $body = $res->getBody()->getContents();

            if ($status !== 200) {
                Log::warning('ApisNetPeService::consultarDni no 200', [
                    'numero' => $numero,
                    'status' => $status,
                    'body' => $body,
                ]);
            } else {
                if (empty(trim($body))) {
                    Log::warning('ApisNetPeService::consultarDni respuesta vacía', [
                        'numero' => $numero,
                        'status' => $status,
                        'body' => $body,
                    ]);
                }
            }

            return json_decode($body, true);
        } catch (Exception $e) {
            Log::error('ApisNetPeService::consultarDni excepción', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
