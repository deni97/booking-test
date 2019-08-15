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
    public function getForm(): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));

        $tables = $reservationModel->getTables();

        $params = [];
        $params['tables'] = $tables;

        return $this->render('reservation.twig', $params);
    }

    public function getTimeJS(int $tableId, string $date): string
    {
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        $date = $year . '-' . $month . '-' . $day;

        $scheduleModel = new ScheduleModel($this->db);

        $scheduleDay = $scheduleModel->getByDate($date);

        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));
        
        try {
            $array = $reservationModel->getPossibleReservationsForTable($scheduleDay, $date, $tableId);
        } catch (ScheduleException $e) {
            $array = [];
        }
        
        $array = TimeConverter::convertIndexArray($array);

        return json_encode($array);
    }

    public function reserve(): string
    {
        if (!$this->request->isPost()) {
            return $this->render('reservation.twig', []);
        }

        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));

        $params = $this->request->getParams();
        $reservation = Reservation::constructUsingParams($params);
        $viewParams = [];

        $this->setViewParamsErrorMessage($reservation, $viewParams);
        $this->setViewParamsFormValues($reservation, $viewParams);

        $viewParams['tables'] = $reservationModel->getTables();

        if (isset($viewParams['errorMessage'])) {
            return $this->render('reservation.twig', $viewParams);
        }

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

    private function setViewParamsErrorMessage(Reservation $reservation, &$viewParams): void
    {
        if (!$reservation->hasName()) {
            $viewParams['errorMessage'] =  ($viewParams['errorMessage'] ?? '') .
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
