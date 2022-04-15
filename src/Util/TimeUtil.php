<?php

namespace Mosparo\Util;

use DateTimeInterface;
use DateTime;

class TimeUtil
{
    /**
     * Returns the difference between two DateTime objects in seconds
     *
     * @param DateTimeInterface $startTime
     * @param DateTimeInterface $endTime
     * @return string
     */
    public static function getDifferenceInSeconds(DateTimeInterface $startTime, DateTimeInterface $endTime): int
    {
        $diff = $startTime->diff($endTime);

        // Get the number of seconds is not possible from the DateInterval object, so we have to use a trick
        $seconds = DateTime::createFromFormat('U', 0)->add($diff)->format('U');

        return intval($seconds);
    }
}