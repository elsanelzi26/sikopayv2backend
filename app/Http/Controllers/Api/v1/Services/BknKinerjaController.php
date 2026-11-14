<?php

namespace App\Http\Controllers\Api\v1\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BknKinerjaController extends Controller
{
    //
    public function getSource(Request $request, $path)
    {
        $url = "https://kinerja.bkn.go.id/" . $path;

        $client = new Client();
        $token = $request->bearerToken();
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        try {
            $response = $client->request('GET', $url, [
                'headers' => $headers,
                'query' => $request->query()
            ]);

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());

        } catch (Exception $e) {
            //throw $e;
            return response()->json([
                'error' => 'Request Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
