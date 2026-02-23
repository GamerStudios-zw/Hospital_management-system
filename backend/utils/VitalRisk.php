<?php
// FILE: backend/utils/VitalRisk.php

class VitalRisk {
    public static function classify($temperature, $pulse, $bp, $spo2) {
        $temp = is_null($temperature) ? null : (float)$temperature;
        $pul = is_null($pulse) ? null : (int)$pulse;
        $oxy = is_null($spo2) ? null : (int)$spo2;
        $bpParsed = self::parseBp($bp);

        $reasonsRed = [];
        $reasonsOrange = [];

        if (!is_null($temp)) {
            if ($temp < 35.0 || $temp >= 39.5) $reasonsRed[] = 'Temperature critical';
            elseif (($temp >= 35.0 && $temp < 36.0) || ($temp > 37.5 && $temp < 39.5)) $reasonsOrange[] = 'Temperature borderline';
        }

        if (!is_null($pul)) {
            if ($pul < 40 || $pul > 130) $reasonsRed[] = 'Pulse critical';
            elseif (($pul >= 40 && $pul < 50) || ($pul > 100 && $pul <= 130)) $reasonsOrange[] = 'Pulse borderline';
        }

        if ($bpParsed) {
            $sys = $bpParsed['sys'];
            $dia = $bpParsed['dia'];
            if ($sys < 90 || $sys > 180 || $dia < 60 || $dia > 120) $reasonsRed[] = 'Blood pressure critical';
            elseif (($sys >= 90 && $sys < 100) || ($sys > 140 && $sys <= 180) || ($dia >= 60 && $dia < 65) || ($dia > 90 && $dia <= 120)) $reasonsOrange[] = 'Blood pressure borderline';
        }

        if (!is_null($oxy)) {
            if ($oxy < 90) $reasonsRed[] = 'SpO2 critical';
            elseif ($oxy >= 90 && $oxy <= 94) $reasonsOrange[] = 'SpO2 borderline';
        }

        if (!empty($reasonsRed)) {
            return [
                'risk_level' => 'red',
                'risk_label' => 'Urgent Care',
                'risk_color' => 'danger',
                'risk_reasons' => $reasonsRed
            ];
        }

        if (!empty($reasonsOrange)) {
            return [
                'risk_level' => 'orange',
                'risk_label' => 'In Between',
                'risk_color' => 'warning',
                'risk_reasons' => $reasonsOrange
            ];
        }

        return [
            'risk_level' => 'green',
            'risk_label' => 'Normal',
            'risk_color' => 'success',
            'risk_reasons' => []
        ];
    }

    private static function parseBp($bp) {
        if (!$bp) return null;
        if (!preg_match('/^(\d{2,3})\s*\/\s*(\d{2,3})$/', trim((string)$bp), $m)) {
            return null;
        }
        return ['sys' => (int)$m[1], 'dia' => (int)$m[2]];
    }
}
?>
