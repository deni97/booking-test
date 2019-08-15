<?php

namespace Reservations\Models;

use Reservations\Exceptions\ScheduleException;
use Reservations\Exceptions\DbException;
use Reservations\Domain\ScheduleDay;
use Reservations\Domain\OddScheduleDay;
use DateTime;
use PDO;

class ScheduleModel extends AbstractModel
{
    const CLASSNAME = '\Reservations\Domain\ScheduleDay';
    const CLASSNAME_ODD = '\Reservations\Domain\OddScheduleDay';

    public function getByDate(string $date)
    {
        $schedule = null;

        try {
            $schedule = $this->getOddScheduleDay(new DateTime($date));
        } catch (ScheduleException $e) {
            $date = $date . ' 00:00:00';
            $day = (int) date('N', strtotime($date));
            $schedule = $this->getDay($day);
        }

        return $schedule;
    }

    public function getDay(int $day): ScheduleDay
    {
        if ($day < 1 || $day > 7) {
            throw new ScheduleException('Day of the week should be in range [1, 7].');
        }

        $query = 'SELECT * FROM schedule WHERE id = :day';

        $stmt = $this->db->prepare($query);
        $stmt->execute(['day' => $day]);

        $stmt->setFetchMode(PDO::FETCH_CLASS, self::CLASSNAME);
        $schedule = $stmt->fetch();

        if (empty($schedule)) {
            throw new ScheduleException();
        }

        return $schedule;
    }

    public function getWeek(): array
    {
        $query = 'SELECT * FROM schedule';

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $schedule = $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME);

        if (empty($schedule) || (count($schedule) !== 7)) {
            throw new ScheduleException();
        }

        return $schedule;
    }

    public function getOddScheduleDay($day): OddScheduleDay
    {
        $query = 'SELECT * FROM odd_schedule WHERE day = DATE(:day)';
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['day' => $day->format('Y-m-d H:i:s')]);

        $stmt->setFetchMode(PDO::FETCH_CLASS, self::CLASSNAME_ODD);
        $schedule = $stmt->fetch();

        if (empty($schedule)) {
            throw new ScheduleException('No odds that day!');
        }

        return $schedule;
    }

    public function getOddScheduleAfter($day): array
    {
        $query = 'SELECT * FROM odd_schedule WHERE day > :day';

        $stmt = $this->db->prepare($query);
        $stmt->execute(['day' => $day]);

        $schedule = $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME_ODD);

        if (empty($schedule)) {
            throw new ScheduleException('Nothing special is coming.');
        }

        return $schedule;
    }

    public function scheduleOddDay($day, int $time, int $duration): void
    {
        if ($time < 0 || $time > 47) {
            throw new ScheduleException('Time represented as int should belong to [0, 47] interval.');
        }

        if ($day == date("Y-m-d")) {
            throw new ScheduleException("Can\'t schedule today.");
        }

        $query = 'INSERT INTO odd_schedule (day, time, duration) VALUES (:day, :time, :duration)';

        $stmt = $this->db->prepare($query);
        
        $params = [
            'day' => $day,
            'time' => $time,
            'duration' => $duration
        ];

        if (!$stmt->execute($params)) {
            throw new DbException($stmt->errorInfo()[2]);
        }
    }

    public function updateSchedule(array $schedule): void
    {
        if (count($schedule) !== 7) {
            throw new ScheduleException('Array contains more or less that 7 elements.');
        }

        foreach ($schedule as $day) {
            if (!($day instanceof ScheduleDay)) {
                throw new ScheduleException('Schedule array should consist of ScheduleDay objects');
            }
        }

        $this->db->beginTransaction();

        $query = <<<SQL
UPDATE schedule 
SET open_at = :open_at, duration = :duration
WHERE id = :id
SQL;

        $stmt = $this->db->prepare($query);

        foreach ($schedule as $day) {
            $stmt->bindValue('id', $day->getId());
            $stmt->bindValue('open_at', $day->getOpen_At());
            $stmt->bindValue('duration', $day->getDuration());
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                throw new DbException($stmt->errorInfo()[2]);
            }
        }

        $this->db->commit();
    }
}
