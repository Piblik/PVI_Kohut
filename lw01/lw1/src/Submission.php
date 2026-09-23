<?php

namespace App;

class Submission
{
    public string $customer;
    public int $hours;
    public string $equipment;
    public string $date;
    public string $description;

    public function __construct(
        string $customer,
        int $hours,
        string $equipment,
        string $date,
        string $description
    ) {
        $this->customer = $customer;
        $this->hours = $hours;
        $this->equipment = $equipment;
        $this->date = $date;
        $this->description = $description;
    }
}