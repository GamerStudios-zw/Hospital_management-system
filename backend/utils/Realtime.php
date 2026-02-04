<?php
// FILE: backend/utils/Realtime.php

class Realtime {
    public static function emit($event, $payload = []) {
        $url = getenv('HMS_RT_URL');
        if (!$url) $url = 'http://127.0.0.1:8090/emit';

        $body = json_encode([
            'event' => $event,
            'payload' => $payload
        ]);

        // Prefer curl if available
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_exec($ch);
            curl_close($ch);
            return;
        }

        // Fallback to stream context
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
                'timeout' => 2
            ]
        ]);
        @file_get_contents($url, false, $context);
    }
}
