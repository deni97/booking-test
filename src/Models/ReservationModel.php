<?php

namespace Reservations\Models;

use Reservations\Core\FilteredMap;
use Reservations\Domain\Reservation;
use Reservations\Models\ScheduleModel;
use Reservations\Exceptions\DbException;
use Reservations\Exceptions\NotFoundException;
use Reservations\Exceptions\ScheduleException;
use Reservations\Exceptions\ReservationException;
use DateTime;
use PDO;

class ReservationModel extends AbstractModel
{
    const CLASSNAME = '\Reservations\Domain\Reservation';

    private $archiveDb;

    public function __construct(PDO $db, PDO $archiveDb)
    {
        $this->archiveDb = $archiveDb;
        parent::__construct($db);
    }

    public function get(int $id): Reservation
    {
        $query = 'SELECT * FROM reservations WHERE id = :id ORDER BY date';

        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::CLASSNAME);
        $reservation = $stmt->fetch();

        if (empty($reservation)) {
            throw new NotFoundException();
        }

        return $reservation;
    }

    public function getAll(): array
    {
        $query = 'SELECT * FROM reservations';
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME);
    }

    public function getByDate(date $date): array
    {
        $query = 'SELECT * FROM reservations WHERE date = :date';

        $stmt = $this->db->prepare($query);
        $stmt->execute(['date' => $date]);

        $reservations = $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME);

        if (empty($reservations)) {
            throw new NotFoundException();
        }

        return $reservations;
    }

    public function getTables(): array
    {
        $query = 'SELECT id FROM tables';
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        return $tables;
    }

    public function getPossibleReservations($scheduleDay, string $date, int $table_id): array
    {
        $duration = $scheduleDay->getDuration();
        
        if ($duration === 0) {
            throw new ScheduleException("Not working that day.");
        }

        $query = 'SELECT time, duration FROM reservations WHERE date = :date AND table_id = :table_id ORDER BY time';

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'date' => $date,
            'table_id' => $table_id
        ]);

        $reservations = $stmt->fetchAll();

        $possibilities = [];
        for ($i = 0; $i < $duration; ++$i) {
            $possibilities[] = $i + $scheduleDay->getOpen_At();
        }

        if (empty($reservations)) {
            return $possibilities;
        }

        foreach ($reservations as $reservation) {
            $duration = $reservation['duration'];
            $time = $reservation['time'];
            $index = array_search($time, $possibilities);

            array_splice($possibilities, $index, $duration);
        }

        return $possibilities;
    }

    public function archive(int $id): void
    {
        $reservation = $this->get($id);

        $this->db->beginTransaction();

        $query = 'DELETE FROM reservations WHERE id = :id';

        $stmt = $this->db->prepare($query);

        if (!$stmt->execute(['id' => $id])) {
            $this->db->rollBack();
            throw new DbException($stmt->errorInfo()[2]);
        }

        $query = <<<SQL
INSERT INTO reservations
(id, name, phone, table_id, date, time, duration)
VALUES
(:id, :name, :phone, :table_id, :date, :time, :duration)
SQL;

        $stmt = $this->archiveDb->prepare($query);

        $params = [
            'id' => $reservation->getId(),
            'name' => $reservation->getName(),
            'phone' => $reservation->getPhone(),
            'table_id' => $reservation->getTableId(),
            'date' => $reservation->getDate(),
            'time' => $reservation->getTime(),
            'duration' => $reservation->getDuration()
        ];

        if (!$stmt->execute($params)) {
            $this->db->rollBack();
            throw new DbException($stmt->errorInfo()[2]);
        }

        $this->db->commit();
    }
    
    public function reserve(Reservation $reservation): int
    {
        $date = $reservation->getDate();

        if ($date->getTimestamp() < time()) {
            throw new ReservationException('Постфактум за стол не садим!');
        }

        $time = $reservation->getTime();
        $duration = $reservation->getDuration();
        $table = $reservation->getTable_Id();

        $scheduleModel = new ScheduleModel($this->db);

        $date = $reservation->getDate();
        $time = $reservation->getTime();
        $duration = $reservation->getDuration();
        $table = $reservation->getTable_Id();

        try {
            $schedule = $scheduleModel->getOddScheduleDay($date);
            $date = date_format($date, 'Y-m-d');
        } catch (ScheduleException $e) {
            $date = date_format($date, 'Y-m-d');
            $day = (int) date('N', strtotime($date));
            
            $schedule = $scheduleModel->getDay($day);
        }
        
        if (($time + $duration) > $schedule->getDuration() + $schedule->getOpen_At() + 1) {
            throw new ReservationException('Так долго не работаем!');
        }

        if (!$this->checkIfPossible(
            $time,
            $duration,
            $this->getPossibleReservations($schedule, $date, $table)
        )) {
            throw new ReservationException();
        }
        $this->db->beginTransaction();

        $query = <<<SQL
INSERT INTO reservations
(name, phone, table_id, date, time, duration)
VALUES 
(:name, :phone, :table_id, :date, :time, :duration)
SQL;

        $stmt = $this->db->prepare($query);

        $params = [
            'name' => $reservation->getName(),
            'phone' => $reservation->getPhone(),
            'table_id' => $reservation->getTableId(),
            'date' => date_format($reservation->getDate(), 'Y-m-d H:i:s'),
            'time' => $reservation->getTime(),
            'duration' => $reservation->getDuration()
        ];

        if (!$stmt->execute($params)) {
            $this->db->rollBack();
            throw new DbException($stmt->errorInfo()[2]);
        }

        $id = $this->db->lastInsertId();

        $this->db->commit();

        return $id;
    }

    private function checkIfPossible(int $time, int $duration, array $possibilities): bool
    {
        $checkedInterval = [];

        for ($i = $time; $i < $time + $duration; ++$i) {
            $checkedInterval[] = $i;
        }

        foreach ($checkedInterval as $value) {
            if (array_search($value, $possibilities) === false) {
                return false;
            }
        }

        return true;
    }
}
