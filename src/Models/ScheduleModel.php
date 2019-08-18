<?php

namespace Reservations\Models;

use Reservations\Exceptions\ScheduleException;
use Reservations\Exceptions\DbException;
use Reservations\Domain\ScheduleDay;
use Reservations\Domain\OddScheduleDay;
use Reservations\Domain\AbstractScheduleDay;
use DateTime;
use PDO;

class ScheduleModel extends AbstractModel
{
    /**
     * A classname used for fetching ScheduleDays.
     */
    const CLASSNAME = '\Reservations\Domain\ScheduleDay';

    /**
     * A classname for fetching OddScheduleDays.
     */
    const CLASSNAME_ODD = '\Reservations\Domain\OddScheduleDay';

    /**
     * A function that tries to get an OddScheduleDay for a specified date,
     * <br>goes for a regular one on fail. 
     * 
     * @param string $date MySQL-compatible date string
     * 
     * @return AbstractScheduleDay 
     */
    public function getByDate(string $date): AbstractScheduleDay
    {
        try {
            $scheduleDay = $this->getOddScheduleDay($date);
        } catch (ScheduleException $e) {
            $day = (int) date('N', strtotime($date));
            $scheduleDay = $this->getDay($day);
        }

        return $scheduleDay;
    }

    /**
     * A function that tries to fetch schedule for a specified day of the week.
     * 
     * @param integer $day number representation of a day of the week
     * 
     * @return ScheduleDay
     */
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

    /**
     * A function that tries too fetch all ScheduleDays for a week.
     * 
     * @return array a set of ScheduleDays representing a week
     */
    public function getWeek(): array
    {
        $query = 'SELECT * FROM schedule';

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $schedule = $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME);
        // Throws an exception in case it didn't fetch a thing
        // or if an array doesn't represent a week
        if (empty($schedule) || (count($schedule) !== 7)) {
            throw new ScheduleException();
        }

        return $schedule;
    }

    /**
     * A function that tries to fetch an OddScheduleDay on the specified date.
     * 
     * @param string $date MySQL-compatible date string
     * 
     * @return OddScheduleDay
     */
    public function getOddScheduleDay(string $date): OddScheduleDay
    {
        $query = 'SELECT * FROM odd_schedule WHERE day = DATE(:day)';
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['day' => $date]);

        $stmt->setFetchMode(PDO::FETCH_CLASS, self::CLASSNAME_ODD);
        $scheduleDay = $stmt->fetch();

        if (empty($scheduleDay)) {
            throw new ScheduleException('No odds that day!');
        }

        return $scheduleDay;
    }

    /**
     * A function that tries to fetch all OddScheduleDays.
     * 
     * @return array an array populated with OddScheduleDays
     */
    public function getOddSchedule(): array
    {
        $query = 'SELECT * FROM odd_schedule ORDER BY day';

        $stmt = $this->db->prepare($query);

        $stmt->execute();

        $schedule = $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME_ODD);

        if (empty($schedule)) {
            throw new ScheduleException('Nothing special is coming.');
        }

        return $schedule;
    }

    /**
     * A function that tries to fetch all OddScheduleDays after a specified date.
     * <br>Currently it's of no use.
     * 
     * @param string $date MySQL-compatible date string
     * 
     * @return array an array populated with OddScheduleDays
     */
    public function getOddScheduleAfter(string $date): array
    {
        $query = 'SELECT * FROM odd_schedule WHERE day > :day';

        $stmt = $this->db->prepare($query);
        $stmt->execute(['day' => $date]);

        $schedule = $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME_ODD);

        if (empty($schedule)) {
            throw new ScheduleException('Nothing special is coming.');
        }

        return $schedule;
    }

    /**
     * A function that tries to schedule an odd day.
     * 
     * @param string $date MySQL-compatible date string
     * @param integer $time an int representation of time
     * @param integer $duration int representation of duration
     * 
     * @return void
     */
    public function scheduleOddDay(string $date, int $openAt, int $duration): void
    {
        // Checks if the caller tries to schedule anything but today
        if ($openAt < 0 || $openAt > 47) {
            throw new ScheduleException('Time represented as int should belong to [0, 47] interval.');
        }
        // Checks if the caller tries to schedule today
        if ($date == date("Y-m-d")) {
            throw new ScheduleException("Can\'t schedule today.");
        }


        $query = 'INSERT INTO odd_schedule (day, open_at, duration) VALUES (:day, :open_at, :duration)';

        $stmt = $this->db->prepare($query);
        
        $params = [
            'day' => $date,
            'open_at' => $openAt,
            'duration' => $duration
        ];
        var_dump($params);

        if (!$stmt->execute($params)) {
            throw new DbException($stmt->errorInfo()[2]);
        }
    }

    /**
     * A function that tries to update a week's schedule in the DB.
     * 
     * @param array $schedule a set of ScheduleDays representing a week
     * 
     * @return void
     */
    public function updateSchedule(array $schedule): void
    {
        // Checks if $schedule array does not represent a week
        if (count($schedule) !== 7) {
            throw new ScheduleException('Array should contain exactly 7 elements, representing a week.');
        }
        // Checks if $schedule array contains anything but ScheduleDays
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
        // Roll back whatever goes wrong
        foreach ($schedule as $day) {
            $params = [
                'id'       => $day->getId(),
                'open_at'  => $day->getOpen_At(),
                'duration' => $day->getDuration()
            ];
            
            if (!$stmt->execute($params)) {
                $this->db->rollBack();
                throw new DbException($stmt->errorInfo()[2]);
            }
        }

        $this->db->commit();
    }
}
