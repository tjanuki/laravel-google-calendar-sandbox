<?php

use App\Models\User;
use App\Services\GoogleClientService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->googleClientMock = $this->createMock(\Google_Client::class);
    $this->googleClientMock->method('setAccessToken')->with($this->callback(function ($token) {
        return $token['access_token'] === 'fake_access_token';
    }));
    $this->googleClientMock->method('isAccessTokenExpired')->willReturn(false);

    $this->googleClientService = new GoogleClientService($this->googleClientMock);
});

it('initializes Google client with valid token', function () {
    $oauthToken = json_encode(['access_token' => 'fake_access_token', 'expires_in' => 3600]);
    $user = User::factory()->has(\App\Models\OAuthToken::factory([
        'token' => $oauthToken
    ]))->create();


    $client = $this->googleClientService->initializeGoogleClient($user);

    // prettier-ignore
    expect($client)->toBeInstanceOf(\Google_Client::class);
});
