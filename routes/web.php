<?php

use App\Models\OAuthToken;
use App\Services\GoogleCalendarService;
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

    OAuthToken::updateOrCreate(
        ['user_id' => $loginUser->id, 'provider' => 'google'],
        [
            'token' => $user->token,
            'refresh_token' => $user->refreshToken, // Might be null
            'expires_at' => now()->addSeconds($user->expiresIn),
        ]
    );

    return 'success!';
})->name('auth.callback');

Route::get('/calendars', function () {
    $user = auth()->user();

    $client = new \Google_Client();
    $client->setAccessToken($user->oauthToken->token);
    $service = new \Google_Service_Calendar($client);

    $calendarList = $service->calendarList->listCalendarList();

    dd($calendarList->getItems());
});

Route::get('/calendars/create', function () {
    $user = auth()->user();

    $eventData = [
        'calendar_id' => 'primary', // 'primary' is the default calendar ID for the user
        'summary' => 'Google I/O 2025',
        'description' => 'A chance to hear more about Google\'s developer products.',
        'start' => now()->timezone('Asia/Tokyo')->addHours(1),
        'end' => now()->timezone('Asia/Tokyo')->addHours(2),
    ];

    $googleCalendarService = app(GoogleCalendarService::class);
    $event = $googleCalendarService->createEvent($user, $eventData);

    dd($event);
});

Route::get('/calendars/find', function () {
    $user = auth()->user();

    $client = new \Google_Client();
    $client->setAccessToken($user->oauthToken->token);
    $service = new \Google_Service_Calendar($client);

    $startDateTime = Carbon::create(2024, 3, 3, 9, 0, 0, 'Asia/Tokyo');
    $endDateTime = Carbon::create(2024, 3, 3, 17, 0, 0, 'Asia/Tokyo');

    $calendarId = 'primary';
    $optParams = array(
        'timeMin' => $startDateTime->toRfc3339String(), // Start of your date/time range in RFC3339 format
        'timeMax' => $endDateTime->toRfc3339String(), // End of your date/time range in RFC3339 format
        'singleEvents' => true,
        'orderBy' => 'startTime',
    );
    $events = $service->events->listEvents($calendarId, $optParams);

    $matchedEventId = null;

    foreach ($events->getItems() as $event) {
        $eventStart = $event->start->dateTime;
        $eventEnd = $event->end->dateTime;

        $eventStartTokyo = Carbon::parse($eventStart)->timezone('Asia/Tokyo')->toRfc3339String();
        $eventEndTokyo = Carbon::parse($eventEnd)->timezone('Asia/Tokyo')->toRfc3339String();

        if ($eventStartTokyo == $startDateTime->toRfc3339String()
            && $eventEndTokyo == $endDateTime->toRfc3339String()
        ) {
            $matchedEventId = $event->getId();
            break; // Exit the loop once a matching event is found
        }
    }

    if ($matchedEventId) {
        $service->events->delete($calendarId, $matchedEventId);
        return response()->json(['success' => true, 'message' => 'Event deleted successfully.']);

    } else {
        // No matching event found
        return "No event found with the specified start and end times.";
    }
});
