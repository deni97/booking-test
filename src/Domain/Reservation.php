<?php

namespace Reservations\Domain;

use Reservations\Utils\TimeConverter;
use Reservations\Core\FilteredMap;

class Reservation
{
    private $id;
    private $name;
    private $phone;
    private $table_id;
    private $date;
    private $time;
    private $duration;
    private $convertedTime;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getTableId(): int
    {
        return $this->table_id;
    }

    public function getTable_Id(): int
    {
        return $this->table_id;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getConvertedTime(): string
    {
        return TimeConverter::getTime($this->getTime());
    }

    public function hasName(): bool
    {
        return isset($this->name);
    }

    public function hasPhone(): bool
    {
        return isset($this->phone);
    }

    public function hasTableId(): bool
    {
        return isset($this->table_id);
    }

    public function hasDate(): bool
    {
        return isset($this->date);
    }

    public function hasTime(): bool
    {
        return isset($this->time);
    }

    public function hasDuration(): bool
    {
        return isset($this->duration);
    }

    public static function constructUsingParams(FilteredMap $params): Reservation
    {
        $reservation = new Reservation();

        if ($params->getString('name') === '') {
            $reservation->name = null;
        } else {
            $reservation->name = $params->getString('name');
        }

        if ($params->getString('phone') === '') {
            $reservation->phone = null;
        } else {
            $reservation->phone = $params->getString('phone');
        }

        if ($params->getString('table_id') === '') {
            $reservation->table_id = null;
        } else {
            $reservation->table_id = $params->getInt('table_id');
        }

        if ($params->getString('time') === '') {
            $reservation->time = null;
        } else {
            $time = $params->getString('time');
            $reservation->time = TimeConverter::getIndex($time);
        }

        if ($params->getString('date') === '') {
            $reservation->date = null;
        } else {
            $reservation->date = $params->getDate('date');
        }

        if ($params->getString('duration') === '') {
            $reservation->duration = null;
        } else {
            $reservation->duration = $params->getInt('duration');
        }

        return $reservation;
    }
}