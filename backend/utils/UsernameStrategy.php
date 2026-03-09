<?php
// FILE: backend/utils/UsernameStrategy.php

class UsernameStrategy {
    public static function buildFromFullName($db, $fullName) {
        $nameToken = self::extractNameToken($fullName);
        $hospitalToken = self::resolveHospitalToken($db);
        return $nameToken . '@' . $hospitalToken;
    }

    public static function buildEmailFromFullName($db, $fullName, $tld = 'gov') {
        $nameToken = self::extractNameToken($fullName);
        $hospitalToken = self::resolveHospitalToken($db);
        $tldToken = self::sanitizeToken($tld);
        if ($tldToken === '') {
            $tldToken = 'gov';
        }
        return $nameToken . '@' . $hospitalToken . '.' . $tldToken;
    }

    public static function extractNameToken($fullName) {
        $firstPart = strtok(trim((string)$fullName), " \t\r\n");
        $token = self::sanitizeToken($firstPart);
        return $token !== '' ? $token : 'staff';
    }

    public static function resolveHospitalToken($db) {
        $hospitalToken = 'hospital';
        try {
            $settings = $db->query("SELECT hospital_name FROM system_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($settings && !empty($settings['hospital_name'])) {
                $firstPart = strtok((string)$settings['hospital_name'], " \t\r\n");
                $token = self::sanitizeToken($firstPart);
                if ($token !== '') {
                    $hospitalToken = $token;
                }
            }
        } catch (Exception $e) {
            // Keep fallback token when settings table/value is unavailable.
        }
        return $hospitalToken;
    }

    public static function sanitizeToken($value) {
        $text = strtolower(trim((string)$value));
        if ($text === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = strtolower($converted);
            }
        }

        return preg_replace('/[^a-z0-9]+/', '', $text);
    }
}
