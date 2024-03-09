<?php

use App\Models\OAuthToken;
use App\Services\GoogleCalendarService;
use App\Services\GoogleClientService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Handle the Filament routes.
Route::get('/auth', function () {
    return view('welcome');
});

Route::get('/auth/redirect', function () {
    return Socialite::driver('google')
        ->scopes(['https://www.googleapis.com/auth/calendar'])
        ->with(['access_type' => 'offline', 'prompt' => 'consent'])
        ->redirect();
})->name('auth.redirect');

Route::get('/auth/callback', function () {
    $user = Socialite::driver('google')->user();

    $loginUser = auth()->user();

    // Prepare the token array as expected by Google_Client
    $currentTime = now();
    $tokenDetails = [
        'access_token' => $user->token,
        'refresh_token' => $user->refreshToken, // Might be null, handle accordingly
        'expires_in' => $user->expiresIn,
        'created' => now()->getTimestamp() // Capture the current timestamp as 'created'
    ];

    OAuthToken::updateOrCreate(
        ['user_id' => $loginUser->id, 'provider' => 'google'],
        [
            'token' => json_encode($tokenDetails), // Save the full token details as a JSON string
            'refresh_token' => $user->refreshToken, // Might be null
            'expires_at' => $currentTime->addSeconds($user->expiresIn),
        ]
    );

    return 'success!';
})->name('auth.callback');

Route::get('/calendars', function () {
    try {
        $client = app(GoogleClientService::class)->initializeGoogleClient(auth()->user());
        $service = new \Google_Service_Calendar($client);

        $calendarList = $service->calendarList->listCalendarList();

        dd($calendarList->getItems());
    } catch (\Exception $e) {
        dd($e->getMessage());
    }
});

Route::get('/calendars/create', function () {
    $user = auth()->user();
    $client = app(GoogleClientService::class)->initializeGoogleClient($user);

    $eventData = [
        'calendar_id' => 'primary', // 'primary' is the default calendar ID for the user
        'summary' => 'Google I/O 2025',
        'description' => 'A chance to hear more about Google\'s developer products.',
        'start' => now()->timezone('Asia/Tokyo')->addHours(1),
        'end' => now()->timezone('Asia/Tokyo')->addHours(2),
    ];
    $event = app(GoogleCalendarService::class)->createEvent($client, $user, $eventData);

    dd($event);
});

Route::get('/calendars/find', function () {
    $user = auth()->user();
    $client = app(GoogleClientService::class)->initializeGoogleClient($user);

    $event = app(GoogleCalendarService::class)->findEvent($client, $user);

    dd($event);
});

Route::get('/calendars/delete/{googleEventId}', function () {
    $user = auth()->user();
    $client = app(GoogleClientService::class)->initializeGoogleClient($user);

    app(GoogleCalendarService::class)->deleteEvent($client, $user, request('googleEventId'));

    return 'Event deleted!';
});
