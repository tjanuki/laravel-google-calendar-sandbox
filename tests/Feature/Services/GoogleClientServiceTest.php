<?php

use App\Models\User;
use App\Services\GoogleClientService;
use Mockery\MockInterface;


it('initializes Google client with valid token', function () {
    $googleClientMock = $this->mock(\Google_Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('setClientId')->with(config('services.google.client_id'));
        $mock->shouldReceive('setClientSecret')->with(config('services.google.client_secret'));
        $mock->shouldReceive('setAccessToken')->with(['access_token' => 'fake_access_token', 'expires_in' => 3600]);
        $mock->shouldReceive('isAccessTokenExpired')->andReturn(false);
    });

    app()->bind(\Google_Client::class, function () use ($googleClientMock) {
        return $googleClientMock;
    });

    $googleClientService = app(GoogleClientService::class);

    $oauthToken = json_encode(['access_token' => 'fake_access_token', 'expires_in' => 3600]);
    $user = User::factory()->has(\App\Models\OAuthToken::factory([
        'token' => $oauthToken
    ]))->create();


    $client = $googleClientService->initializeGoogleClient($user);

    // prettier-ignore
    expect($client)->toBeInstanceOf(\Google_Client::class);
});

it('refreshes access token', function () {
    $googleClientMock = $this->mock(\Google_Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('fetchAccessTokenWithRefreshToken')->with('fake_refresh_token')->andReturn([
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600
        ]);
        $mock->shouldReceive('getAccessToken')->andReturn([
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600
        ]);
        $mock->shouldReceive('getRefreshToken')->andReturn('fake_refresh_token');
    });


    app()->bind(\Google_Client::class, function () use ($googleClientMock) {
        return $googleClientMock;
    });

    $googleClientService = app(GoogleClientService::class);

    $oauthToken = json_encode(['access_token' => 'fake_access_token', 'refresh_token' => 'fake_refresh_token', 'expires_in' => 3600]);
    $user = User::factory()->has(\App\Models\OAuthToken::factory([
        'token' => $oauthToken
    ]))->create();

    $googleClientService->refreshAccessToken($googleClientMock, $user->oauthToken);

    $user->refresh();

    // prettier-ignore
    expect($user->oauthToken->token)->toBe('{"access_token":"new_access_token","refresh_token":"new_refresh_token","expires_in":3600}')
        ->and($user->oauthToken->refresh_token)->toBe('new_refresh_token')
        ->and($user->oauthToken->expires_at)->toBe(now()->addSeconds(3600)->toDateTimeString());
});
