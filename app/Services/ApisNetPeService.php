<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;

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

            return json_decode($res->getBody()->getContents(), true);
        } catch (Exception $e) {
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

            return json_decode($res->getBody()->getContents(), true);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
