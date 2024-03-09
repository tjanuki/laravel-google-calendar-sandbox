<?php

namespace App\Services;

use App\Models\GoogleCalendarEvent;
use App\Models\User;
use Google_Client;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{

    private $client;

    public function createEvent(Google_Client $client, User $user, array $eventData): GoogleCalendarEvent
    {

        $service = new \Google_Service_Calendar($client);

        $startDateTime = $eventData['start'];
        $endDateTime = $eventData['end'];

        $event = new Google_Service_Calendar_Event(array(
            'summary' => $eventData['summary'],
            'description' => $eventData['description'],
            'start' => array(
                'dateTime' => $startDateTime->toRfc3339String(),
                'timeZone' => $startDateTime->getTimezone()->getName(),
            ),
            'end' => array(
                'dateTime' => $endDateTime->toRfc3339String(),
                'timeZone' => $endDateTime->getTimezone()->getName(),
            ),
            'reminders' => array(
                'useDefault' => FALSE,
                'overrides' => array(
                    array('method' => 'popup', 'minutes' => 10),
                ),
            ),
        ));

        $event = $service->events->insert($eventData['calendar_id'], $event);

        return $user->googleCalendarEvents()->create([
            'google_calendar_id' => $eventData['calendar_id'],
            'google_event_id' => $event->getId(),
            'summary' => $event->getSummary(),
            'start' => $event->getStart()->getDateTime(),
            'end' => $event->getEnd()->getDateTime()
        ]);
    }

    public function findEvent(Google_Client $client, User $user): Google_Service_Calendar_Event
    {
        $service = new \Google_Service_Calendar($client);
        $googleCalendarEvent = $user->googleCalendarEvents()->first();

        return $service->events->get(
            $googleCalendarEvent->google_calendar_id,
            $googleCalendarEvent->google_event_id
        );
    }

    public function deleteEvent(Google_Client $client, User $user, string $googleEventId): void
    {
        $service = new \Google_Service_Calendar($client);
        $googleCalendarEvent = $user->googleCalendarEvents()->where('google_event_id', $googleEventId)->first();

        $service->events->delete(
            $googleCalendarEvent->google_calendar_id,
            $googleCalendarEvent->google_event_id
        );

        $googleCalendarEvent->delete();
    }
}
