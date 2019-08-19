<?php

namespace Reservations\Models;

use Reservations\Core\FilteredMap;
use Reservations\Domain\Reservation;
use Reservations\Domain\AbstractScheduleDay;
use Reservations\Models\ScheduleModel;
use Reservations\Exceptions\DbException;
use Reservations\Exceptions\NotFoundException;
use Reservations\Exceptions\ScheduleException;
use Reservations\Exceptions\ReservationException;
use DateTime;
use PDO;

class ReservationModel extends AbstractModel
{
    /**
     * Reservation data type used for fetching from the DB.
     */
    const CLASSNAME = '\Reservations\Domain\Reservation';

    /**
     * PDO that is used for communication with the archive DB.
     */
    private $archiveDb;

    public function __construct(PDO $db, PDO $archiveDb)
    {
        $this->archiveDb = $archiveDb;
        parent::__construct($db);
    }

    /**
     * Fetches a single reservation from the DB.
     * 
     * @param integer $id reservation id
     * 
     * @return Reservation a Reservation object
     */
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

    /**
     * Fetches all reservations from the DB.
     * 
     * @return array an array of Reservation objects
     */
    public function getAll(): array
    {
        $query = 'SELECT * FROM reservations';

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS, self::CLASSNAME);
    }

    /**
     * Fetches all reservations on the specified day from the DB.
     * 
     * @param date $date
     * 
     * @return array an array of Reservation objects
     */
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

    /**
     * Fetches all reservations on the specified day and table from the DB.
     * 
     * @param string $date MySQL-compatible date string
     * @param integer $table_id
     * 
     * @return array an array of Reservation objects
     */
    public function getByDateAndTable(string $date, int $table_id): array
    {
        $query = 'SELECT time, duration FROM reservations WHERE date = :date AND table_id = :table_id ORDER BY time';

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'date' => $date,
            'table_id' => $table_id
        ]);

        $reservations = $stmt->fetchAll();

        return $reservations;
    }

    /**
     * Fetches all table ids from the DB.
     * 
     * @return array a set of integers representing table ids
     */
    public function getTables(): array
    {
        $query = 'SELECT id FROM tables';

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        return $tables;
    }

    /**
     * Calculates all possible times for a reservation on the specified day and table.
     * 
     * @param string $date MySQL-compatible date string
     * @param int $table_id 
     * 
     * @return array a set of possible reservation times
     */
    public function getPossibleReservations(string $date, int $table_id): array
    {
        $scheduleModel = new ScheduleModel($this->db);
        $scheduleDay = $scheduleModel->getByDate($date);
        $duration = $scheduleDay->getDuration();
        $openAt = $scheduleDay->getOpen_At();

        // Not working that day
        if ($duration === 0) {
            throw new ScheduleException();
        }

        $possibilities = [];
        for ($i = 0; $i < $duration; ++$i) {
            $possibilities[] = $i + $openAt;
        }

        $reservations = $this->getByDateAndTable($date, $table_id);
        // No reservations for that date and table - anything is possible
        if (empty($reservations)) {
            return $possibilities;
        }
        // Cuts out reserved time out of possibilities
        foreach ($reservations as $reservation) {
            $index = array_search($reservation['time'], $possibilities);
            array_splice($possibilities, $index, $reservation['duration']);
        }

        return $possibilities;
    }

    /**
     * Tries to archive a reservation.
     * 
     * This method uses transaction on the current DB to ensure archiving. 
     * <br>Also it uses a single $stmt variable for both current DB and archive DB.
     * 
     * @param integer $id an id of the reservation
     * 
     * @return void
     */
    public function archive(int $id): void
    {
        // Gets reservation by id
        $reservation = $this->get($id);

        $this->db->beginTransaction();

        $query = 'DELETE FROM reservations WHERE id = :id';

        $stmt = $this->db->prepare($query);
        // Deny transaction if delete fails
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
            'id'       => $reservation->getId(),
            'name'     => $reservation->getName(),
            'phone'    => $reservation->getPhone(),
            'table_id' => $reservation->getTableId(),
            'date'     => $reservation->getDate(),
            'time'     => $reservation->getTime(),
            'duration' => $reservation->getDuration()
        ];

        // Rolls back changes to the current DB if archiving fails
        if (!$stmt->execute($params)) {
            $this->db->rollBack();
            throw new DbException($stmt->errorInfo()[2]);
        }

        $this->db->commit();
    }

    /**
     * Tries to make a reservation.
     * 
     * @param Reservation $reservation 
     * 
     * @return integer last inserted reservation id
     */
    public function reserve(Reservation $reservation): int
    {
        if (!$this->isPossible($reservation)) {
            throw new ReservationException();
        }

        $query = <<<SQL
INSERT INTO reservations
(name, phone, table_id, date, time, duration)
VALUES 
(:name, :phone, :table_id, :date, :time, :duration)
SQL;

        $stmt = $this->db->prepare($query);

        $params = [
            'name'     => $reservation->getName(),
            'phone'    => $reservation->getPhone(),
            'table_id' => $reservation->getTableId(),
            'date'     => date_format($reservation->getDate(), 'Y-m-d H:i:s'),
            'time'     => $reservation->getTime(),
            'duration' => $reservation->getDuration()
        ];

        if (!$stmt->execute($params)) {
            throw new DbException($stmt->errorInfo()[2]);
        }

        return $this->db->lastInsertId();
    }

    /**
     * A helper method for reserve() that checks the possibility of 
     * <br>the specified reservation.
     * 
     * @param Reservation $reservation checked reservation
     * 
     * @return bool the possibility of this reservation
     */
    private function isPossible(Reservation $reservation): bool
    {
        $date     = $reservation->getDate();
        $time     = $reservation->getTime();
        $table    = $reservation->getTable_Id();
        $duration = $reservation->getDuration();
        // Deny post-factum reservations
        if ($date->getTimestamp() < time()) {
            return false;
        }
        // Represents reservation time as a set of integers
        $reservationInterval = [];
        for ($i = $time; $i < $time + $duration; ++$i) {
            $reservationInterval[] = $i;
        }

        $date = date_format($date, 'Y-m-d');
        $possibilities = $this->getPossibleReservations($date, $table);
        // If reservation time is contained inside the possibilities set 
        // then the reservation is possible
        if (array_diff($reservationInterval, $possibilities)) {
            return false;
        }

        return true;
    }
}
