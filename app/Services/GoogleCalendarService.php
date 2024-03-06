<?php

namespace App\Services;

use App\Models\GoogleCalendarEvent;
use App\Models\User;
use Google_Client;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{

    private $client;

    public function __construct(Google_Client $client = null)
    {
        $this->client = $client ?: new Google_Client();
    }
    public function createEvent(User $user, array $eventData) : GoogleCalendarEvent
    {
        $this->client->setAccessToken($user->oauthToken->token);
        $service = new \Google_Service_Calendar($this->client);

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
}
