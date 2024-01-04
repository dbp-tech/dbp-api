<?php

namespace App\Repositories;


use Illuminate\Support\Facades\Cache;

class GuzzleRepository {
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
            return resultFunction("", true, json_decode($content, true));
        } catch (\Exception $e) {
            if ($e->getCode() == 401) {
                $tokpedTokenCache = $this->createTokenTokped();
                Cache::put('tokped_token', $tokpedTokenCache);
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