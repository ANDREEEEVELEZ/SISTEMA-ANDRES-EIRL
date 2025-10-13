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
        $this->token = env('APIS_NET_PE_TOKEN'); // guarda tu token en .env
        $this->client = new Client([
            'base_uri' => 'https://api.apis.net.pe',
            'verify' => false,
        ]);
    }

    /**
     * Consultar datos por RUC.
     */
    public function consultarRuc($numero)
    {
        try {
            $res = $this->client->request('GET', '/v2/sunat/ruc', [
                'http_errors' => false,
                'connect_timeout' => 5,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Referer' => 'https://apis.net.pe/api-consulta-ruc',
                    'User-Agent' => 'laravel/guzzle',
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
            $res = $this->client->request('GET', '/v2/reniec/dni', [
                'http_errors' => false,
                'connect_timeout' => 5,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Referer' => 'https://apis.net.pe/api-consulta-dni',
                    'User-Agent' => 'laravel/guzzle',
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
