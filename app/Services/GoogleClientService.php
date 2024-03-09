<?php

namespace App\Services;

use App\Models\User;

class GoogleClientService
{

    /**
     * @throws \Exception
     */
    public function initializeGoogleClient(User $user): \Google_Client
    {
        $oauthToken = $user->oauthToken;

        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $tokenArray = json_decode($oauthToken->token, true);
        $client->setAccessToken($tokenArray);

        if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
            $this->refreshAccessToken($client, $oauthToken);
        } elseif ($client->isAccessTokenExpired()) {
            throw new \Exception('Access token expired and refresh token not available.');
        }

        return $client;
    }

    public function refreshAccessToken(\Google_Client $client, $oauthToken): void
    {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $newAccessToken = $client->getAccessToken();

        // Consider wrapping these operations in a database transaction
        $oauthToken->token = json_encode($newAccessToken);
        $oauthToken->refresh_token = $newAccessToken['refresh_token'] ?? $oauthToken->refresh_token;
        $oauthToken->expires_at = now()->addSeconds($newAccessToken['expires_in']);
        $oauthToken->save();

        logger(__METHOD__ . ': Updated access token: ' . var_export($newAccessToken, true));
    }
}
