<?php

namespace Reservations\Utils;

use Reservations\Utils\IndexTimeMap;
use Reservations\Exceptions\TimeConversionException;

class TimeConverter
{
    // $map
    use IndexTimeMap;

    public static function getTime(int $index): string
    {
        if ($index < 0 || $index > 47) {
            throw new TimeConversionException('Index should stay in [0, 47] interval');
        }

        return TimeConverter::$map[$index];
    }

    public static function getIndex(string $time): int
    {
        if (!preg_match("@\d{1,2}:\d{2}@AD", $time)) {
            throw new TimeConversionException('Time string should be of H:MM or HH:MM format.');
        }

        $index = array_search($time, TimeConverter::$map);

        if ($index === false) {
            throw new TimeConversionException('Time should belong to 0:00-23:30 interval.');
        }

        return $index;
    }

    public static function convertIndexArray($inputArray): array 
    {
        $array = [];

        for ($i = 0; $i < count($inputArray); ++$i) {
            $array[$i] = TimeConverter::getTime($inputArray[$i]);
        }

        return $array;
    }
}
