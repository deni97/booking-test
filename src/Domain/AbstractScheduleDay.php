<?php

namespace Reservations\Domain;

abstract class AbstractScheduleDay
{
    protected $open_at;
    protected $duration;
    
    public function getOpen_At(): int
    {
        return $this->open_at;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
