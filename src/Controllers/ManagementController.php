<?php

namespace Reservations\Controllers;

use Reservations\Exceptions\NotFoundException;
use Reservations\Models\ReservationModel;
use Reservations\Models\ScheduleModel;
use Reservations\Models\LoginModel;
use Reservations\Domain\ScheduleDay;
use Reservations\Core\FilteredMap;

class ManagementController extends AbstractController
{
    public function getReservations(): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));

        $reservations = $reservationModel->getAll();
        $params = ['reservations' => $reservations];

        return $this->render('manageReservations.twig', $params);
    }

    public function getReservationById(int $id): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));

        try {
            $reservation = $reservationModel->get($id);
        } catch (NotFoundException $e) {
            $params = ['errorMessage' => 'Брони с запрашиваемым id не существует.'];
            return $this->render('error.twig', $params);
        }
        
        $params = ['reservation' => $reservation];

        return $this->render('manageSingleReservation.twig', $params);
    }

    public function login(): string
    {
        if (!$this->request->isPost()) {
            return $this->render('login.twig', []);
        }

        $params = $this->request->getParams();

        if (!$params->has('email')) {
            $params = ['errorMessage' => 'Введите email.'];
            return $this->render('login.twig', $params);
        }

        if (!$params->has('password')) {
            $params = ['errorMessage' => 'Введите пароль.'];
            return $this->render('login.twig', $params);
        }

        $email = $params->getString('email');

        $loginModel = new LoginModel($this->db);
        try {
            $hash = $loginModel->getHash($email);
        } catch (NotFoundException $e) {
            $params = ['errorMessage' => 'Неверный email.'];
            return $this->render('login.twig', $params);
        }

        if (password_verify($params->getString('password'), $hash)) {
            setcookie('user', $params->getString('email'));
        } else {
            $params = ['errorMessage' => 'Неверный пароль.'];
            return $this->render('login.twig', $params);
        }

        return $this->getReservations();
    }

    public function logout(): string
    {
        setcookie('user', '', time() - 1, "/");

        return $this->login();
    }

    public function archive(int $id): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));

        $reservationModel->archive($id);

        return $this->getReservations();
    }

    public function getSchedule(): string
    {
        $scheduleModel = new ScheduleModel($this->db);

        $schedule = $scheduleModel->getWeek();

        foreach ($schedule as $scheduleDay) {
            $scheduleDay->setName();
        }

        $params = ['schedule' => $schedule];

        return $this->render('manageSchedule.twig', $params);
    }

    public function setSchedule(): string
    {
        if (!$this->request->isPost()) {
            return $this->render('manageSchedule.twig', []);
        }

        $params = $this->request->getParams();

        $schedule = $this->constructScheduleFromParams($params);

        $scheduleModel = new ScheduleModel($this->db);

        $scheduleModel->updateSchedule($schedule);

        return $this->getSchedule();
    }

    private function constructScheduleFromParams(FilteredMap $params): array
    {
        $schedule = [];

        $scheduleDay = new ScheduleDay();

        for ($i = 1; $i < 8; ++$i) { 
            $openAt = $params->getInt('openAt' . $i);
            $duration = $params->getInt('duration' . $i) - $openAt;
            $scheduleDay->setId($i)->setOpen_At($openAt)->setDuration($duration);
            $schedule[] = $scheduleDay->getCopy();
        }

        return $schedule;
    }
}
