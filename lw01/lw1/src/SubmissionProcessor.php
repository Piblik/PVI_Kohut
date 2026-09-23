<?php

namespace App;

class SubmissionProcessor
{
    private const K = 110;

    private array $rates = [
        'tractor' => 900,
        'harvester' => 1800,
        'loader' => 1200
    ];

    public function validate(Submission $submission): array
    {
        $errors = [];

        if (trim($submission->customer) === '') {
            $errors[] = 'Вкажіть замовника.';
        }

        if ($submission->hours < 1 || $submission->hours > 100) {
            $errors[] = 'Кількість годин повинна бути від 1 до 100.';
        }

        if (!array_key_exists($submission->equipment, $this->rates)) {
            $errors[] = 'Оберіть правильний тип техніки.';
        }

        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        if ($submission->date < $tomorrow) {
            $errors[] = 'Дата повинна бути не раніше завтрашньої.';
        }

        $descriptionLength = strlen($submission->description);

        if ($descriptionLength < 10 || $descriptionLength > 600) {
            $errors[] = 'Опис робіт повинен містити від 10 до 600 байт.';
        }

        return $errors;
    }

    public function calculateCost(Submission $submission): float
    {
        $cost = $submission->hours * $this->rates[$submission->equipment];

        if ($submission->hours >= 20) {
            $cost *= 0.93;
        }

        if ($submission->equipment === 'harvester') {
            $cost += 2500;
        }

        $cost *= self::K / 100;

        return $cost;
    }
}