<?php

namespace Reservations\Domain;

class OddScheduleDay extends AbstractScheduleDay
{
    private $day;

    public function getDay(): date
    {
        return $this->day;
    }
}
