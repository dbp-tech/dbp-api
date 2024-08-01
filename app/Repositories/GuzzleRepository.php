<?php

namespace App\Repositories;


use Illuminate\Support\Facades\Cache;

class GuzzleRepository {
    public function getTiktokToken($tokenOnly = false) {
        $tiktokCache = Cache::get('tiktok_token');
        if (!$tiktokCache AND !$tokenOnly) {
            $refreshToken = "ROW_cXceSQAAAACnoSiSa0danC3PmWPmiXT6SHGEHJ8p6nxwo_UANhQ486FE4QroYWRys_DNWMYql2U";
            $refreshUrl = 'https://auth.tiktok-shops.com/api/v2/token/refresh?app_key=' . env('TIKTOK_APP_KEY') . '&app_secret=' . env('TIKTOK_APP_SECRET') . '&refresh_token=' . $refreshToken . '&grant_type=refresh_token';
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $refreshUrl);
            $content = json_decode($response->getBody()->getContents(), true);

            Cache::put('tiktok_token', json_encode($content['data']));
            $tiktokToken = $content['data']['access_token'];
        } else {
            $tiktokCache = json_decode($tiktokCache, true);
            if ($tiktokCache['access_token_expire_in'] < strtotime(now())) {
                $tiktokToken = $this->getTiktokToken(true);
            } else {
                $tiktokToken = $tiktokCache['access_token'];
            }
        }
        return $tiktokToken;
    }

    public function signatureAlgorithm($timestamp, $requestPath, $data = []) {
        $appSecret = env('TIKTOK_APP_SECRET');
        $params = [
            "app_key" => env('TIKTOK_APP_KEY')
        ];
        foreach ($data as $key => $item) {
            $params[$key] = $item;
        }
        $params["timestamp"] = $timestamp;
        $buildQuery = '';
        foreach ($params as $key => $item) {
            $buildQuery = $buildQuery . $key . (is_array($item) ? implode(",", $item) : $item);
        }
        $stringToSign = $requestPath . $buildQuery;

        $wrappedString = $appSecret . $stringToSign . $appSecret;
        $sign = hash_hmac('sha256', $wrappedString, $appSecret);
        return $sign;
    }

    public function getTokpedToken() {
        $tokpedTokenCache = Cache::get('tokped_token');
        if (!$tokpedTokenCache) {
            $tokpedTokenCache = $this->createTokenTokped();
            Cache::put('tokped_token', $tokpedTokenCache);
        }
        return "Bearer " . $tokpedTokenCache;
    }

    public function getData($query, $endpoint) {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $this->getTokpedToken(),
            ];
            $client = new \GuzzleHttp\Client([
                'headers' => $headers
            ]);

            if (count($query) > 0) {
                $response = $client->request('GET', $endpoint, ['query' => $query]);
            } else {
                $response = $client->request('GET', $endpoint);
            }
            $content = $response->getBody()->getContents();
            return resultFunction("", true, json_decode($content, true)['data']);
        } catch (\Exception $e) {
            if ($e->getCode() == 401) {
                $tokpedTokenCache = $this->createTokenTokped();
                Cache::put('tokped_token', $tokpedTokenCache);
                return $this->getData($query, $endpoint);
            }
            return resultFunction("Err code GR-DD catch: " . $e->getMessage());
        }
    }

    public function getDataPost($body, $endpoint) {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $this->getTokpedToken(),
            ];
            $client = new \GuzzleHttp\Client([
                'headers' => $headers
            ]);

            $response = $client->post($endpoint, [
                'body' => json_encode($body)
            ]);
            $content = $response->getBody()->getContents();
            return resultFunction("", true, json_decode($content, true));
        } catch (\Exception $e) {
            if ($e->getCode() == 401) {
                $tokpedTokenCache = $this->createTokenTokped();
                Cache::put('tokped_token', $tokpedTokenCache);
                return $this->getDataPost($body, $endpoint);
            }
            return resultFunction("Err code GR-DD catch: " . $e->getMessage());
        }
    }

    public function createTokenTokped() {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Basic " . env('TOKPED_TOKEN'),
        ];
        $client = new \GuzzleHttp\Client([
            'headers' => $headers
        ]);

        $response = $client->post("https://accounts.tokopedia.com/token?grant_type=client_credentials");
        $content = json_decode($response->getBody()->getContents(), true);
        return $content['access_token'];
    }
}