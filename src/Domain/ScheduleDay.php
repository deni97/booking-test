<?php

namespace Reservations\Domain;

use Reservations\Core\FilteredMap;

class ScheduleDay
{
    private $id;
    private $open_at;
    private $duration;
    private $name;

    public function setName(): void
    {
        switch ($this->id) {
            case 1:
                $this->name = 'Понедельник';
                break;
            case 2:
                $this->name = 'Вторник';
                break;
            case 3:
                $this->name = 'Среда';
                break;
            case 4:
                $this->name = 'Четверг';
                break;
            case 5:
                $this->name = 'Пятница';
                break;
            case 6:
                $this->name = 'Суббота';
                break;
            case 7:
                $this->name = 'Воскресенье';
                break;
            default:
                $this->name = 'UNDEFINED';
                break;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOpenAt(): int
    {
        return $this->open_at;
    }

    public function getOpen_At(): int
    {
        return $this->open_at;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setId(int $id): ScheduleDay
    {
        $this->id = $id;
        return $this;
    }

    public function setOpenAt(int $open_at): ScheduleDay
    {
        $this->open_at = $open_at;
        return $this;
    }

    public function setDuration(int $duration): ScheduleDay
    {
        $this->duration = $duration;
        return $this;
    }

    public function getCopy(): ScheduleDay
    {
        $scheduleDay = new ScheduleDay();

        $scheduleDay->setId($this->id)->setOpenAt($this->open_at)->setDuration($this->duration);

        return $scheduleDay;
    }
}