<?php
// FILE: backend/utils/VitalRisk.php

class VitalRisk {
    private const SCALE = [
        'green' => ['rank' => 0, 'label' => 'Normal', 'hex' => '#22c55e', 'color' => 'success', 'action' => 'Routine monitoring'],
        'yellow' => ['rank' => 1, 'label' => 'Mild Concern', 'hex' => '#facc15', 'color' => 'warning', 'action' => 'Monitor closely'],
        'orange' => ['rank' => 2, 'label' => 'High Risk', 'hex' => '#f97316', 'color' => 'orange', 'action' => 'Urgent review required'],
        'red' => ['rank' => 3, 'label' => 'Critical', 'hex' => '#ef4444', 'color' => 'danger', 'action' => 'Immediate Doctor Review']
    ];

    public static function classify($temperature, $pulse, $bp, $spo2) {
        $temp = is_null($temperature) ? null : (float)$temperature;
        $pul = is_null($pulse) ? null : (int)$pulse;
        $oxy = is_null($spo2) ? null : (int)$spo2;
        $bpParsed = self::parseBp($bp);

        $levels = [];
        $reasons = [];

        if (!is_null($temp)) {
            $tempAssessment = self::assessTemperature($temp);
            $levels[] = $tempAssessment['level'];
            if (!empty($tempAssessment['reason'])) $reasons[] = $tempAssessment['reason'];
        }

        if (!is_null($pul)) {
            $pulseAssessment = self::assessPulse($pul);
            $levels[] = $pulseAssessment['level'];
            if (!empty($pulseAssessment['reason'])) $reasons[] = $pulseAssessment['reason'];
        }

        if ($bpParsed) {
            $bpAssessment = self::assessBp($bpParsed['sys'], $bpParsed['dia']);
            $levels[] = $bpAssessment['level'];
            if (!empty($bpAssessment['reason'])) $reasons[] = $bpAssessment['reason'];
        }

        if (!is_null($oxy)) {
            $spo2Assessment = self::assessSpo2($oxy);
            $levels[] = $spo2Assessment['level'];
            if (!empty($spo2Assessment['reason'])) $reasons[] = $spo2Assessment['reason'];
        }

        $overallLevel = self::pickHighestLevel($levels);
        $meta = self::SCALE[$overallLevel];

        return [
            'risk_level' => $overallLevel,
            'risk_label' => $meta['label'],
            'risk_color' => $meta['color'],
            'risk_hex' => $meta['hex'],
            'risk_priority' => $meta['rank'],
            'risk_action' => $meta['action'],
            'risk_reasons' => $reasons
        ];
    }

    private static function assessTemperature($temp) {
        if ($temp >= 39.5) return ['level' => 'red', 'reason' => 'Critical fever (>= 39.5°C)'];
        if ($temp >= 38.5 && $temp <= 39.4) return ['level' => 'orange', 'reason' => 'High fever (38.5-39.4°C)'];
        if ($temp >= 37.5 && $temp <= 38.4) return ['level' => 'yellow', 'reason' => 'Mild fever (37.5-38.4°C)'];
        if ($temp < 35.0) return ['level' => 'red', 'reason' => 'Severe hypothermia (< 35.0°C)'];
        if ($temp >= 35.0 && $temp <= 36.0) return ['level' => 'yellow', 'reason' => 'Mild hypothermia (35.0-36.0°C)'];
        return ['level' => 'green', 'reason' => 'Temperature normal'];
    }

    private static function assessPulse($pulse) {
        if ($pulse > 130) return ['level' => 'red', 'reason' => 'Critical tachycardia (> 130 bpm)'];
        if ($pulse >= 121 && $pulse <= 130) return ['level' => 'orange', 'reason' => 'High tachycardia (121-130 bpm)'];
        if ($pulse >= 101 && $pulse <= 120) return ['level' => 'yellow', 'reason' => 'Mild tachycardia (101-120 bpm)'];
        if ($pulse < 50) return ['level' => 'red', 'reason' => 'Critical bradycardia (< 50 bpm)'];
        if ($pulse >= 50 && $pulse <= 59) return ['level' => 'yellow', 'reason' => 'Mild bradycardia (50-59 bpm)'];
        return ['level' => 'green', 'reason' => 'Pulse normal'];
    }

    private static function assessBp($sys, $dia) {
        if ($sys >= 180 || $dia >= 120) return ['level' => 'red', 'reason' => 'Hypertensive crisis'];
        if ($sys < 90 || $dia < 60) return ['level' => 'red', 'reason' => 'Hypotension'];
        if (($sys >= 140 && $sys <= 179) || ($dia >= 90 && $dia <= 119)) return ['level' => 'orange', 'reason' => 'Hypertension stage 2'];
        if (($sys >= 130 && $sys <= 139) || ($dia >= 80 && $dia <= 89)) return ['level' => 'yellow', 'reason' => 'Hypertension stage 1'];
        if (($sys >= 120 && $sys <= 129) && $dia < 80) return ['level' => 'yellow', 'reason' => 'Elevated blood pressure'];
        return ['level' => 'green', 'reason' => 'Blood pressure normal'];
    }

    private static function assessSpo2($spo2) {
        if ($spo2 < 85) return ['level' => 'red', 'reason' => 'Critical hypoxia (SpO2 < 85%)'];
        if ($spo2 >= 85 && $spo2 <= 89) return ['level' => 'orange', 'reason' => 'Severe hypoxia (SpO2 85-89%)'];
        if ($spo2 >= 90 && $spo2 <= 94) return ['level' => 'yellow', 'reason' => 'Mild hypoxia (SpO2 90-94%)'];
        return ['level' => 'green', 'reason' => 'SpO2 normal'];
    }

    private static function pickHighestLevel($levels) {
        if (in_array('red', $levels, true)) return 'red';
        if (in_array('orange', $levels, true)) return 'orange';
        if (in_array('yellow', $levels, true)) return 'yellow';
        return 'green';
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
