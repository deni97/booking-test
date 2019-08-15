<?php

namespace Reservations\Domain;

class OddScheduleDay
{
    private $day;
    private $open_at;
    private $duration;

    public function getDay(): date
    {
        return $this->day;
    }

    public function getOpen_At(): int
    {
        return $this->open_at;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
