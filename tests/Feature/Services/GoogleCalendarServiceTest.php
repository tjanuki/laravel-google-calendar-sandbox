<?php


use App\Models\User;

it('find a Google Calendar event', function () {
    $googleClientMock = $this->createMock(\Google_Client::class);
    $googleServiceMock = $this->createMock(\Google_Service_Calendar::class);
    $googleEventMock = $this->createMock(\Google_Service_Calendar_Event::class);
    $googleEventMock->method('getId')->willReturn('fake_event_id');
    $googleEventMock->method('getSummary')->willReturn('fake_summary');
    $googleEventMock->method('getStart')->willReturn((object)['dateTime' => '2021-01-01T00:00:00+00:00']);
    $googleEventMock->method('getEnd')->willReturn((object)['dateTime' => '2021-01-01T01:00:00+00:00']);
    $eventsMock = $this->createMock(\Google_Service_Calendar_Resource_Events::class);
    $eventsMock->expects($this->once())
        ->method('get')
        ->willReturn($googleEventMock);
    $googleServiceMock->events = $eventsMock;

    // Inject the mocked Google_Service_Calendar instance instead of the Google_Client instance
    $googleCalendarService = new \App\Services\GoogleCalendarService();
    $user = User::factory()->has(\App\Models\GoogleCalendarEvent::factory())->create();
    $event = $googleCalendarService->findEvent($googleServiceMock, $user);

    // prettier-ignore
    expect($event)->toBeInstanceOf(\Google_Service_Calendar_Event::class)
        ->and($event->getId())->toBe('fake_event_id')
        ->and($event->getSummary())->toBe('fake_summary')
        ->and($event->getStart()->dateTime)->toBe('2021-01-01T00:00:00+00:00')
        ->and($event->getEnd()->dateTime)->toBe('2021-01-01T01:00:00+00:00');

});

it('finds a Google Calendar event', function () {
    $googleClientMock = $this->createMock(\Google_Client::class);
    $googleServiceCalendarMock = $this->createMock(\Google_Service_Calendar::class);
    $googleEventMock = $this->createMock(\Google_Service_Calendar_Event::class);
    $eventsResourceMock = $this->createMock(\Google_Service_Calendar_Resource_Events::class);

    // Setup the event mock
    $googleEventMock->method('getId')->willReturn('fake_event_id');
    $googleEventMock->method('getSummary')->willReturn('fake_summary');
    $googleEventMock->method('getStart')->willReturn((object)['dateTime' => '2021-01-01T00:00:00+00:00']);
    $googleEventMock->method('getEnd')->willReturn((object)['dateTime' => '2021-01-01T01:00:00+00:00']);

    // Configure the events resource mock to return the event mock on get()
    $eventsResourceMock->method('get')->willReturn($googleEventMock);
    $googleServiceCalendarMock->events = $eventsResourceMock;

    // Ensure the Google_Client mock returns the Google_Service_Calendar mock upon instantiation
//    $googleClientMock->method('getService')->willReturn($googleServiceCalendarMock);

    $loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
    $googleClientMock->method('getLogger')->willReturn($loggerMock);

    // Adjust the GoogleCalendarService to accept a Google_Client instance
    $googleCalendarService = new \App\Services\GoogleCalendarService();
    $user = User::factory()->has(\App\Models\GoogleCalendarEvent::factory())->create();

    // Use the Google_Client mock when calling findEvent
    $event = $googleCalendarService->findEvent($googleClientMock, $user);

    // Assertions
    expect($event)->toBeInstanceOf(\Google_Service_Calendar_Event::class)
        ->and($event->getId())->toBe('fake_event_id')
        ->and($event->getSummary())->toBe('fake_summary')
        ->and($event->getStart()->dateTime)->toBe('2021-01-01T00:00:00+00:00')
        ->and($event->getEnd()->dateTime)->toBe('2021-01-01T01:00:00+00:00');
});
