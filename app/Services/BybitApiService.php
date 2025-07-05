<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BybitApiService
{
    private $client;
    private $apiKey;
    private $apiSecret;
    private $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('trading.bybit.api_key');
        $this->apiSecret = config('trading.bybit.api_secret');
        $this->baseUrl = config('trading.bybit.base_url', 'https://api.bybit.com');
    }

    public function placeOrder($symbol, $side, $orderType, $qty, $price = null)
    {
        $endpoint = '/v5/order/create';
        $timestamp = round(microtime(true) * 1000);

        $params = [
            'category' => 'linear',
            'symbol' => $symbol,
            'side' => $side,
            'orderType' => $orderType,
            'qty' => $qty,
            'timeInForce' => 'GTC'
        ];

        if ($price) {
            $params['price'] = $price;
        }

        $signature = $this->generateSignature($params, $timestamp);

        try {
            $response = $this->client->post($this->baseUrl . $endpoint, [
                'headers' => [
                    'X-BAPI-API-KEY' => $this->apiKey,
                    'X-BAPI-SIGN' => $signature,
                    'X-BAPI-TIMESTAMP' => $timestamp,
                    'X-BAPI-RECV-WINDOW' => '5000',
                    'Content-Type' => 'application/json'
                ],
                'json' => $params
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Bybit API Error: ' . $e->getMessage());
            return null;
        }
    }

    private function generateSignature($params, $timestamp)
    {
        $paramString = $timestamp . $this->apiKey . '5000' . http_build_query($params);
        return hash_hmac('sha256', $paramString, $this->apiSecret);
    }

    public function getOrderStatus($orderId)
    {
        $endpoint = '/v5/order/realtime';
        $timestamp = round(microtime(true) * 1000);

        $params = [
            'category' => 'linear',
            'orderId' => $orderId
        ];

        $signature = $this->generateSignature($params, $timestamp);

        try {
            $response = $this->client->get($this->baseUrl . $endpoint, [
                'headers' => [
                    'X-BAPI-API-KEY' => $this->apiKey,
                    'X-BAPI-SIGN' => $signature,
                    'X-BAPI-TIMESTAMP' => $timestamp,
                    'X-BAPI-RECV-WINDOW' => '5000'
                ],
                'query' => $params
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Bybit API Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getCurrentPrice($symbol)
    {
        $endpoint = '/v5/market/tickers';

        try {
            $response = $this->client->get($this->baseUrl . $endpoint, [
                'query' => [
                    'category' => 'linear',
                    'symbol' => $symbol
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['result']['list'][0]['lastPrice'] ?? null;
        } catch (\Exception $e) {
            Log::error('Bybit API Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getWalletBalance()
    {
        $endpoint = '/v5/account/wallet-balance';
        $timestamp = round(microtime(true) * 1000);

        $params = [
            'accountType' => 'UNIFIED'
        ];

        $signature = $this->generateSignature($params, $timestamp);

        try {
            $response = $this->client->get($this->baseUrl . $endpoint, [
                'headers' => [
                    'X-BAPI-API-KEY' => $this->apiKey,
                    'X-BAPI-SIGN' => $signature,
                    'X-BAPI-TIMESTAMP' => $timestamp,
                    'X-BAPI-RECV-WINDOW' => '5000'
                ],
                'query' => $params
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Bybit API Error: ' . $e->getMessage());
            return null;
        }
    }
}
