<?php

namespace Reservations\Controllers;

use Reservations\Exceptions\NotFoundException;
use Reservations\Exceptions\ScheduleException;
use Reservations\Models\ReservationModel;
use Reservations\Models\ScheduleModel;
use Reservations\Models\LoginModel;
use Reservations\Domain\ScheduleDay;
use Reservations\Utils\TimeConverter;
use Reservations\Core\FilteredMap;

class ManagementController extends AbstractController
{   
    /**
     * Gets and displays all reservations.
     * 
     * @return string reservations, rendered html
     */
    public function getReservations(): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));
        // Gets all reservations from the DB
        $reservations = $reservationModel->getAll();
        $params = [
            'reservations' => $reservations,
            'loggedIn' => true
        ];

        return $this->render('manageReservations.twig', $params);
    }

    /**
     * Gets and displays a single reservation by id.
     * 
     * @param integer $id an id of the reservation
     * @return string single reservation, rendered html
     */
    public function getReservationById(int $id): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive'));
        // Tries to get reservation by id from the DB
        // returns with error message on fail
        try {
            $reservation = $reservationModel->get($id);
        } catch (NotFoundException $e) {
            $params = ['errorMessage' => 'Брони с запрашиваемым id не существует.'];
            return $this->render('error.twig', $params);
        }
        
        $params = [
            'reservation' => $reservation,
            'loggedIn' => true
        ];

        return $this->render('manageSingleReservation.twig', $params);
    }

    /**
     * Tries to login a user by checking if email is present in the DB, 
     * <br>and verifies hash against the input password if so.
     * 
     * @return string reservations, html rendered by getReservations()
     */
    public function login(): string
    {
        // Returns if trying to access it without submitting a form
        if (!$this->request->isPost()) {
            return $this->render('login.twig', []);
        }

        $params = $this->request->getParams();
        // Checks for email and password in parameters
        // returns with error message on fail
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
        // Tries to get hash by email from the DB
        // returns with error message on fail
        try {
            $hash = $loginModel->getHash($email);
        } catch (NotFoundException $e) {
            $params = ['errorMessage' => 'Неверный email.'];
            return $this->render('login.twig', $params);
        }
        // Verifies the password and sets the cookie on success
        // returns with error message on fail
        if (password_verify($params->getString('password'), $hash)) {
            setcookie('user', $params->getString('email'));
        } else {
            $params = ['errorMessage' => 'Неверный пароль.'];
            return $this->render('login.twig', $params);
        }
        // Renders the reservations page
        return $this->getReservations();
    }

    /**
     * Logs out user by expiring the user cookie.
     * 
     * @return string login form, html rendered by login()
     */
    public function logout(): string
    {
        // Expires the cookie by setting the expiration time
        // before current time
        setcookie('user', '', time() - 1, "/");
        // Renders a login page
        return $this->login();
    }

    /**
     * Tries to archive an identified reservation
     * <br>by copying it in the archive DB and deleting it from the current DB.
     * 
     * @param integer $id an id of the reservation
     * @return string reservations, html rendered by getReservations()
     */
    public function archive(int $id): string
    {
        $reservationModel = new ReservationModel($this->db, $this->di->get('archive')); 
        # TO-DO: exception handling
        $reservationModel->archive($id);
        // Renders the reservations page
        return $this->getReservations();
    }

    /**
     * Gets and displays a whole week's schedule.
     * 
     * @return string schedule input form
     */
    public function getSchedule(): string
    {
        $scheduleModel = new ScheduleModel($this->db);
        // Gets schedule from the DB
        # TO-DO: exception handling
        $schedule = $scheduleModel->getWeek();
        // Sets string representation of day of the week
        // i.e. 1 => 'Monday', 7 => 'Sunday'
        foreach ($schedule as $scheduleDay) {
            $scheduleDay->setName();
            $scheduleDay->setConvertedTime();
        }

        $params = [
            'schedule' => $schedule,
            'loggedIn' => true
        ];

        return $this->render('manageSchedule.twig', $params);
    }

    /**
     * Gets and displays the odd schedule.
     * 
     * @return string odd schedule input form
     */
    public function getOddSchedule(): string
    {
        $scheduleModel = new ScheduleModel($this->db);
        // Gets odd schedule from the DB
        try {
            $schedule = $scheduleModel->getOddSchedule();
        } catch (ScheduleException $e) {
            $schedule = [];
        }

        foreach ($schedule as $scheduleDay) {
            $scheduleDay->setName();
            $scheduleDay->setConvertedTime();
        }

        $params = [
            'schedule' => $schedule,
            'loggedIn' => true
        ];

        return $this->render('manageOddSchedule.twig', $params);
    }    
    
    /**
     * Tries to add a day to the odd schedule.
     * 
     * @return string rendered odd schedule by getOddSchedule()
     */
    public function addOddScheduleDay(): string
    {
        // Returns if trying to access it without submitting a form
        if (!$this->request->isPost()) {
            return $this->render('manageSchedule.twig', []);
        }

        $params = $this->request->getParams();

        $openAt = $params->getString('openAt');
        $openAt = TimeConverter::getIndex($openAt);

            
        $duration = $params->getString('duration');
        $duration = TimeConverter::getIndex($duration) - $openAt;

        $scheduleModel = new ScheduleModel($this->db);
        # TO-DO: exception handling
        $scheduleModel->scheduleOddDay(
            $params->getString('day'),
            $openAt,
            $duration
        );
        // Renders the odd schedule page
        return $this->getOddSchedule();
    }

    /**
     * Tries to delete a day from the odd schedule.
     * 
     * @return string rendered odd schedule by getOddSchedule()
     */
    public function deleteOddScheduleDay(string $date): string
    {
        # I couldn't get the string request parameter to work
        # so I had to use an int representation
        # TO-DO: rewrite this hack
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        $date = $year . '-' . $month . '-' . $day;
        
        $scheduleModel = new ScheduleModel($this->db);
        # TO-DO: exception handling
        $scheduleModel->deleteOddScheduleDay($date);
        // Renders the odd schedule page
        return $this->getOddSchedule();
    }

    /**
     * Tries to update the schedule.
     * 
     * @return string rendered schedule by getSchedule()
     */
    public function setSchedule(): string
    {
        // Returns if trying to access it without submitting a form
        if (!$this->request->isPost()) {
            return $this->render('manageSchedule.twig', []);
        }

        $params = $this->request->getParams();
        // Uses a helper method, gets a schedule for a week 
        // containing ScheduleDay objects
        $schedule = $this->constructScheduleFromParams($params);

        $scheduleModel = new ScheduleModel($this->db);
        # TO-DO: exception handling
        $scheduleModel->updateSchedule($schedule);
        // Renders the schedule page
        return $this->getSchedule();
    }

    /**
     * A helper method for setSchedule()
     * 
     * Takes parameters, constructs ScheduleDay data type and pushes it 
     * <br>into an array representing a week. 
     *  
     * @param FilteredMap POST request parameters
     * @return array full week schedule
     */
    private function constructScheduleFromParams(FilteredMap $params): array
    {
        $schedule = [];
        // This method uses a single ScheduleDay object 
        // and pushes copies of it with freshly set values
        // into the returned array
        $scheduleDay = new ScheduleDay();
        for ($i = 1; $i < 8; ++$i) { 
            $openAt = $params->getString('openAt' . $i);
            $openAt = TimeConverter::getIndex($openAt);
            // Converts time input from [openAt; closedAt] to [openAt; duration]
            $duration = $params->getString('duration' . $i);
            $duration = TimeConverter::getIndex($duration) - $openAt;
            // Sets fields of ScheduleDay
            $scheduleDay->setId($i)->setOpen_At($openAt)->setDuration($duration);
            // Gets a copy and pushes it in the array
            $schedule[] = clone $scheduleDay;
        }

        return $schedule;
    }
}
