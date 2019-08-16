<?php

namespace Reservations\Controllers;

use Reservations\Models\ReservationModel;
use Reservations\Models\ScheduleModel;
use Reservations\Domain\Reservation;
use Reservations\Domain\ScheduleDay;
use Reservations\Exceptions\DbException;
use Reservations\Exceptions\NotFoundException;
use Reservations\Exceptions\ReservationException;
use Reservations\Exceptions\ScheduleException;
use Reservations\Utils\TimeConverter;

class UserController extends AbstractController
{
    /**
     * A function that gets table ids from the DB and 
     * <br>renders a form for reservation.
     * 
     * @return string reservation form
     */
    public function getForm(): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));

        $tables = $reservationModel->getTables();

        $params = [];
        $params['tables'] = $tables;

        return $this->render('reservation.twig', $params);
    }

    /**
     * An API that returns all possible reservations times
     * <br>for a specified table id and date.
     * 
     * @param integer $tableId an id of the table
     * @param string $date YYYYMMDD formatted date string
     * 
     * @return string JSON string representing reservation possibilites
     */
    public function getTimeJS(int $tableId, string $date): string
    {
        # I couldn't get the string request parameter to work
        # so I had to use an int representation
        # TO-DO: rewrite this hack
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        $date = $year . '-' . $month . '-' . $day;

        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));
        
        // Tries to populate an array with reservation possibilities
        // initializes an empty one on caught exception
        try {
            $array = $reservationModel->getPossibleReservations($date, $tableId);
        } catch (ScheduleException $e) {
            $array = [];
        }
        // Converts time indices to time strings
        $array = TimeConverter::convertIndexArray($array);

        return json_encode($array);
    }

    /**
     * A function that tries to make a reservation. 
     * 
     * Renders a page displaying an id of the freshly inserted reservation on success,
     * <br>and returns with an error message on fail.
     * 
     * @return string the success page
     */
    public function reserve(): string
    {
        // Returns if trying to access it without submitting a form
        if (!$this->request->isPost()) {
            return $this->render('reservation.twig', []);
        }

        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));

        $params = $this->request->getParams();
        $reservation = Reservation::constructUsingParams($params);
        
        // Initializes viewParams array and populates it
        // with error message and form input values
        $viewParams = [];
        $this->setViewParamsErrorMessage($reservation, $viewParams);
        $this->setViewParamsFormValues($reservation, $viewParams);
        // Gets table ids from the DB
        $viewParams['tables'] = $reservationModel->getTables();
        
        // Returns if there is an error message
        if (isset($viewParams['errorMessage'])) {
            return $this->render('reservation.twig', $viewParams);
        }
        // Tries to make a reservation and fetch an id out of it
        // returns with error message on fail
        try {
            $id = $reservationModel->reserve($reservation);
        } catch (ReservationException $e) {
            $viewParams['errorMessage'] = 'Это время недоступно<br/>' . $e->getMessage();
            return $this->render('reservation.twig', $viewParams);
        }  catch (DbException $e) {
            $viewParams['errorMessage'] = 'Ошибка в базе данных<br/>' . $e->getMessage();
            return $this->render('reservation.twig', $viewParams);
        } catch (\Exception $e) {
            $viewParams['errorMessage'] = 'Что-то пошло не так!<br/>' . $e->getMessage();
            return $this->render('reservation.twig', $viewParams);
        }

        return $this->render('success.twig', ['id' => $id]);
    }

    /**
     * A helper method for reserve().
     * 
     * Modifies an input array, sets error message if reservation is missing a parameter.
     * 
     * @return void
     */
    private function setViewParamsErrorMessage(Reservation $reservation, &$viewParams): void
    {
        if (!$reservation->hasName()) {
            $viewParams['errorMessage'] = ($viewParams['errorMessage'] ?? '') .
                'Укажите имя.' . "<br/>";
        }

        if (!$reservation->hasPhone()) {
            $viewParams['errorMessage'] = ($viewParams['errorMessage'] ?? '') .
                'Как до вас дозвониться?' . "<br/>";
        }
        
        if (!$reservation->hasTableId()) {
            $viewParams['errorMessage'] = ($viewParams['errorMessage'] ?? '') .
                'Какой столик выберем?' . "<br/>";
        }
        
        if (!$reservation->hasDate()) {
            $viewParams['errorMessage'] = ($viewParams['errorMessage'] ?? '') .
                'Когда вы хотите забронировать?' . "<br/>";
        }
        
        if (!$reservation->hasTime()) {
            $viewParams ['errorMessage'] = ($viewParams['errorMessage'] ?? '') .
                'В какое время вам удобно?' . "<br/>";
        }
        
        if (!$reservation->hasDuration()) {
            $viewParams['errorMessage'] = ($viewParams['errorMessage'] ?? '') .
                'Насколько вы хотите забронировать столик?' . "<br/>";
        }
    }

    /**
     * A helper method for reserve().
     * 
     * Modifies an input array, sets parameters based on reservation values.
     * 
     * @return void
     */
    private function setViewParamsFormValues(Reservation $reservation, &$viewParams): void
    {
        $viewParams['name']     = $reservation->hasName()     ? $reservation->getName()     : null;
        $viewParams['phone']    = $reservation->hasPhone()    ? $reservation->getPhone()    : null;
        $viewParams['table_id'] = $reservation->hasTableId()  ? $reservation->getTableId()  : null;
        $viewParams['date']     = $reservation->hasDate()     ? date_format($reservation->getDate(), 'Y-m-d H:i:s') : null;
        $viewParams['time']     = $reservation->hasTime()     ? $reservation->getTime()     : null;
        $viewParams['duration'] = $reservation->hasDuration() ? $reservation->getDuration() : null;
    }
}
