<?php

namespace App\Services;

use App\Models\GoogleCalendarEvent;
use App\Models\User;
use Google_Client;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{


    public function __construct(private readonly Google_Client $client)
    {
    }

    public function findEvent(User $user): Google_Service_Calendar_Event
    {
        $service = new \Google_Service_Calendar($this->client);
        $googleCalendarEvent = $user->googleCalendarEvents()->first();

        return $service->events->get(
            $googleCalendarEvent->google_calendar_id,
            $googleCalendarEvent->google_event_id
        );
    }

    public function createEvent(User $user, array $eventData): GoogleCalendarEvent
    {
        $startDateTime = $eventData['start'];
        $endDateTime = $eventData['end'];

        $event = new Google_Service_Calendar_Event([
            'summary' => $eventData['summary'],
            'description' => $eventData['description'],
            'start' => [
                'dateTime' => $startDateTime->toRfc3339String(),
                'timeZone' => $startDateTime->getTimezone()->getName(),
            ],
            'end' => [
                'dateTime' => $endDateTime->toRfc3339String(),
                'timeZone' => $endDateTime->getTimezone()->getName(),
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => 10],
                ],
            ],
        ]);

        $service = new \Google_Service_Calendar($this->client);
        $event = $service->events->insert($eventData['calendar_id'], $event);

        return $user->googleCalendarEvents()->create([
            'google_calendar_id' => $eventData['calendar_id'],
            'google_event_id' => $event->getId(),
            'summary' => $event->getSummary(),
            'start' => $event->getStart()->getDateTime(),
            'end' => $event->getEnd()->getDateTime()
        ]);
    }

    public function deleteEvent(User $user, string $googleEventId): void
    {
        $googleCalendarEvent = $user->googleCalendarEvents()->where('google_event_id', $googleEventId)->first();

        $service = new \Google_Service_Calendar($this->client);
        $service->events->delete(
            $googleCalendarEvent->google_calendar_id,
            $googleCalendarEvent->google_event_id
        );

        $googleCalendarEvent->delete();
    }
}
