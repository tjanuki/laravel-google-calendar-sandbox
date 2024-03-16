<?php


use App\Models\User;
use App\Services\GoogleCalendarService;
use Mockery\MockInterface;

it('find a Google Calendar event', function () {
    $googleServiceCalendarEventMock = $this->mock(\Google_Service_Calendar_Event::class, function (MockInterface $mock) {
        $mock->shouldReceive('getId')->andReturn('fake_event_id');
        $mock->shouldReceive('getSummary')->andReturn('fake_summary');
        $mock->shouldReceive('getStart')->andReturn((object)['dateTime' => '2021-01-01T00:00:00+00:00']);
        $mock->shouldReceive('getEnd')->andReturn((object)['dateTime' => '2021-01-01T01:00:00+00:00']);
    });
    app()->bind(\Google_Service_Calendar_Event::class, function () use ($googleServiceCalendarEventMock) {
        return $googleServiceCalendarEventMock;
    });

    $googleClientMock = $this->mock(\Google_Client::class, function (MockInterface $mock) use ($googleServiceCalendarEventMock){
        $mock->shouldReceive('getLogger')->andReturn($this->createMock(\Psr\Log\LoggerInterface::class));
        $mock->shouldReceive('shouldDefer')->andReturn(false);
        $mock->shouldReceive('execute')->andReturn($googleServiceCalendarEventMock);
    });
    app()->bind(\Google_Client::class, function () use ($googleClientMock) {
        return $googleClientMock;
    });

    $googleServiceMock = $this->mock(\Google_Service_Calendar::class, function (MockInterface $mock) {
        $mock->shouldReceive('events->get')->andReturn((object)[
            'id' => 'fake_event_id',
            'summary' => 'fake_summary',
            'start' => (object)['dateTime' => '2021-01-01T00:00:00+00:00'],
            'end' => (object)['dateTime' => '2021-01-01T01:00:00+00:00'],
        ]);

    });
    app()->bind(\Google_Service_Calendar::class, function () use ($googleServiceMock) {
        return $googleServiceMock;
    });

    $googleCalendarService = app(GoogleCalendarService::class);

    $user = User::factory()->has(\App\Models\GoogleCalendarEvent::factory())->create();

    $event = $googleCalendarService->findEvent($user);

    // prettier-ignore
    expect($event)->toBeInstanceOf(\Google_Service_Calendar_Event::class)
        ->and($event->getId())->toBe('fake_event_id')
        ->and($event->getSummary())->toBe('fake_summary')
        ->and($event->getStart()->dateTime)->toBe('2021-01-01T00:00:00+00:00')
        ->and($event->getEnd()->dateTime)->toBe('2021-01-01T01:00:00+00:00');

});

it('creates a Google CalendarEvent', function () {
    $googleServiceCalendarEventMock = $this->mock(\Google_Service_Calendar_Event::class, function (MockInterface $mock) {
        $mock->shouldReceive('getId')->andReturn('fake_event_id');
        $mock->shouldReceive('getSummary')->andReturn('fake_summary');

        $startMock = Mockery::mock(stdClass::class);
        $startMock->shouldReceive('getDateTime')->andReturn('2021-01-01T00:00:00+00:00');

        $endMock = Mockery::mock(stdClass::class);
        $endMock->shouldReceive('getDateTime')->andReturn('2021-01-01T01:00:00+00:00');

        $mock->shouldReceive('getStart')->andReturn($startMock);
        $mock->shouldReceive('getEnd')->andReturn($endMock);
    });
    app()->bind(\Google_Service_Calendar_Event::class, function () use ($googleServiceCalendarEventMock) {
        return $googleServiceCalendarEventMock;
    });

    $googleClientMock = $this->mock(\Google_Client::class, function (MockInterface $mock) use ($googleServiceCalendarEventMock){
        $mock->shouldReceive('getLogger')->andReturn($this->createMock(\Psr\Log\LoggerInterface::class));
        $mock->shouldReceive('shouldDefer')->andReturn(false);
        $mock->shouldReceive('execute')->andReturn($googleServiceCalendarEventMock);
    });
    app()->bind(\Google_Client::class, function () use ($googleClientMock) {
        return $googleClientMock;
    });

    $googleServiceMock = $this->mock(\Google_Service_Calendar::class, function (MockInterface $mock) {
        $mock->shouldReceive('events->insert')->andReturn((object)[
            'id' => 'fake_event_id',
            'calendar_id' => 'primary',
            'summary' => 'fake_summary',
            'start' => (object)['dateTime' => '2021-01-01T00:00:00+00:00'],
            'end' => (object)['dateTime' => '2021-01-01T01:00:00+00:00'],
        ]);

    });
    app()->bind(\Google_Service_Calendar::class, function () use ($googleServiceMock) {
        return $googleServiceMock;
    });

    $googleCalendarService = app(GoogleCalendarService::class);

    $user = User::factory()->create();

    $event = $googleCalendarService->createEvent($user, [
        'calendar_id' => 'primary',
        'summary' => 'fake_summary',
        'description' => 'fake_description',
        'start' => now(),
        'end' => now()->addHour(),
    ]);

    // prettier-ignore
    expect($event)->toBeInstanceOf(\App\Models\GoogleCalendarEvent::class)
        ->and($event->google_event_id)->toBe('fake_event_id')
        ->and($event->summary)->toBe('fake_summary')
        ->and($event->start)->toBe('2021-01-01T00:00:00+00:00')
        ->and($event->end)->toBe('2021-01-01T01:00:00+00:00');

});

it('deletes an event', function () {
    $googleServiceCalendarEventMock = $this->mock(\Google_Service_Calendar_Event::class, function (MockInterface $mock) {
        $mock->shouldReceive('getId')->andReturn('fake_event_id');
    });
    app()->bind(\Google_Service_Calendar_Event::class, function () use ($googleServiceCalendarEventMock) {
        return $googleServiceCalendarEventMock;
    });

    $googleClientMock = $this->mock(\Google_Client::class, function (MockInterface $mock) use ($googleServiceCalendarEventMock){
        $mock->shouldReceive('getLogger')->andReturn($this->createMock(\Psr\Log\LoggerInterface::class));
        $mock->shouldReceive('shouldDefer')->andReturn(false);
        $mock->shouldReceive('execute')->andReturn($googleServiceCalendarEventMock);
    });
    app()->bind(\Google_Client::class, function () use ($googleClientMock) {
        return $googleClientMock;
    });

    $googleServiceMock = $this->mock(\Google_Service_Calendar::class, function (MockInterface $mock) {
        $mock->shouldReceive('events->delete')->andReturn((object)[
            'id' => 'fake_event_id',
        ]);

    });
    app()->bind(\Google_Service_Calendar::class, function () use ($googleServiceMock) {
        return $googleServiceMock;
    });

    $googleCalendarService = app(GoogleCalendarService::class);

    $user = User::factory()->has(\App\Models\GoogleCalendarEvent::factory())->create();

    $googleCalendarService->deleteEvent($user, $user->googleCalendarEvents()->first()->google_event_id);

    // prettier-ignore
    expect($user->googleCalendarEvents()->where('google_event_id', 'fake_event_id')->exists())->toBeFalse();
});
