<?php

namespace App\Controllers;
use App\Libraries\CalendlyApi;

class Availability extends BaseController {
    public function getSlots() {
        $rawInput = $this->request->getBody();
        log_message('debug', 'Raw input: ' . $rawInput);
        
        $data = json_decode($rawInput, true);
        if (!$data || !isset($data['url'])) {
            return $this->response->setJSON(['error' => 'Invalid request. URL is required.']);
        }

        $url = $data['url'];
        log_message('debug', 'URL: ' . $url);

        // Parse Calendly URL
        if (preg_match('/^https:\/\/calendly\.com\/([^\/]+)\/([^\/\?]+)/', $url, $matches)) {
            $profile = $matches[1];  // username
            $slug = $matches[2];     // event type slug
        } else {
            return $this->response->setJSON(['error' => 'Invalid Calendly URL format']);
        }

        log_message('debug', 'Profile: ' . $profile);
        log_message('debug', 'Slug: ' . $slug);

        try {
            $calendlyApi = new \App\Libraries\CalendlyApi();
            $eventDetails = $calendlyApi->getEventDetails($slug, $profile);
            
            if (!$eventDetails) {
                return $this->response->setJSON(['error' => 'Event not found']);
            }

            $start = date('Y-m-d\TH:i:s\Z', strtotime('+1 day'));
            $end = date('Y-m-d\TH:i:s\Z', strtotime('+30 days'));
            $timezone = $eventDetails['timezone'] ?? 'UTC';

            $availability = $calendlyApi->getAvailability(
                $eventDetails['uuid'],
                $start,
                $end,
                $timezone,
                $eventDetails['scheduling_link_uuid']
            );

            return $this->response->setJSON($availability);
        } catch (\Exception $e) {
            log_message('error', 'Calendly API error: ' . $e->getMessage());
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }
}