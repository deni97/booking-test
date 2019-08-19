<?php

namespace Reservations\Domain;

use Reservations\Utils\TimeConverter;

abstract class AbstractScheduleDay
{
    protected $open_at;
    protected $duration;

    protected $convertedTime;
    
    public function getOpen_At(): int
    {
        return $this->open_at;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getConvertedTime(): array
    {
        return $this->convertedTime;
    }

    public function setConvertedTime(): void
    {
        $from = TimeConverter::getTime($this->open_at);
        $until = TimeConverter::getTime($this->open_at + $this->duration);

        $this->convertedTime = ['from' => $from, 'until' => $until];
    }
}
