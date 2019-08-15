# booking-test
Тестовое задание.

# БД
Код для создания БД лежит в файле <code>MySQL create.txt</code>.

Реализация включает в себя 2 базы данных - текущую (booking) и архив (booking_archive).
Архив содержит в себе удалённые администраторов заявки на бронь. (копии из таблицы booking_archive.reservations)

Диаграмма booking:<br>
![DB Diagram](https://i.imgur.com/tkJ0WFu.png)

В БД время (и длительность брони/работы) представлено в виде ступеней по полчаса, начиная с 0:00. 
Т.е. интервал 00:00 - 23:30 представлен интервалом [0, 47].

# routes
Пути заданы в config/routes.json.
<pre>
<strong>"/" == "/reservation"</strong> - форма для бронирования.

<strong>"/reserve"</strong>            - запросить бронирование, при ошибке редиректит обратно. 

<strong>"getTimeJS/:id/:date"</strong> - выдаёт JSON, содержащий доступное для брони время для конкретного стола и даты.

<strong>"/management"</strong>         - список броней, прикрыт логином.

<strong>"/management/:id"</strong>     - конкретная бронь, прикрыт логином. 

<strong>"/archive/:id"</strong>        - удалить (т.е. поместить в архив) конкретную бронь, прикрыт логином.

<strong>"/schedule"</strong>           - расписание на неделю, прикрыт логином.

<strong>"/setSchedule"</strong>        - установить расписание на неделю, прикрыт логином.

<strong>"/login"</strong>              - сверить хэш введённого пароля с хэшем в базе, получить cookie при удаче.

<strong>"/logout"</strong>             - забрать cookie.

<strong>"/register"</strong>           - быстрая регистрация.
</pre>
