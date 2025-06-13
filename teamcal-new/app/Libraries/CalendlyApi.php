<?php namespace App\Libraries;

use Config\CalendlyConfig;

class CalendlyApi {
    private $apiKey;
    private $baseUrl = 'https://api.calendly.com';

    public function __construct() {
        $config = new CalendlyConfig();
        $this->apiKey = $config->apiKey;
        
        if ($this->apiKey && $this->apiKey !== 'YOUR_CALENDLY_API_KEY_HERE') {
            $maskedKey = substr($this->apiKey, 0, 4) . '...' . substr($this->apiKey, -4);
            log_message('debug', 'Calendly API Key found: ' . $maskedKey);
        } else {
            log_message('error', 'Calendly API Key not configured in CalendlyConfig.php');
        }
    }

    protected function curlGet(string $url) {
        log_message('debug', 'Making request to: ' . $url);
        
        if (!$this->apiKey || $this->apiKey === 'YOUR_CALENDLY_API_KEY_HERE') {
            throw new \Exception("Calendly API Key not configured in CalendlyConfig.php");
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            log_message('error', 'Curl error: ' . $error);
            throw new \Exception("Curl error: " . $error);
        }
        
        curl_close($ch);
        
        log_message('debug', 'Response code: ' . $httpCode);
        log_message('debug', 'Response body: ' . $response);
        
        if ($httpCode >= 400) {
            throw new \Exception("Calendly API returned error code: " . $httpCode . " Response: " . $response);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse Calendly API response: " . json_last_error_msg());
        }
        
        return $data;
    }

    public function getEventDetails(string $slug, string $profile) {
        // First, get the current user's information
        $url = $this->baseUrl . '/users/me';
        $userData = $this->curlGet($url);
        
        if (empty($userData['resource'])) {
            throw new \Exception("Failed to get current user information");
        }
        
        $userUri = $userData['resource']['uri'];
        log_message('debug', 'Found user URI: ' . $userUri);
        
        // Then get the event types for this user
        $url = $this->baseUrl . '/event_types?user=' . urlencode($userUri);
        $data = $this->curlGet($url);
        
        if (empty($data['collection'])) {
            throw new \Exception("No event types found for user");
        }
        
        log_message('debug', 'Found ' . count($data['collection']) . ' event types');
        
        // Find the event type that matches the slug
        $eventType = null;
        foreach ($data['collection'] as $type) {
            log_message('debug', 'Checking event type: ' . $type['slug']);
            if ($type['slug'] === $slug) {
                $eventType = $type;
                break;
            }
        }
        
        if (!$eventType) {
            throw new \Exception("Event type not found: " . $slug);
        }
        
        log_message('debug', 'Found event type: ' . json_encode($eventType));
        
        return [
            'uuid' => $eventType['uri'],
            'scheduling_link_uuid' => $eventType['scheduling_url'],
            'timezone' => $eventType['timezone'] ?? 'UTC'
        ];
    }

    public function getAvailability(string $uuid, string $start, string $end, string $timezone, string $schedulingLinkUuid) {
        $timezoneEncoded = urlencode($timezone);
        $url = $this->baseUrl . '/availability_schedules?event_type=' . urlencode($uuid) . 
               '&start_time=' . urlencode($start) . 
               '&end_time=' . urlencode($end) . 
               '&timezone=' . $timezoneEncoded;
        return $this->curlGet($url);
    }
}