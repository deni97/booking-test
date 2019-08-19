<?php

namespace Reservations\Domain;

class OddScheduleDay extends AbstractScheduleDay
{
    private $day;
    private $name;

    public function getDay(): string
    {
        return $this->day;
    }

    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(): void
    {
        $id = (int) date('N', strtotime($this->day));

        switch ($id) {
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
}
