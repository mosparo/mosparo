<?php

namespace Mosparo\Util;

class DateRangeUtil
{
    const DATE_RANGE_14D = '14D';
    const DATE_RANGE_1M = '1M';
    const DATE_RANGE_2M = '2M';
    const DATE_RANGE_3M = '3M';
    const DATE_RANGE_6M = '6M';
    const DATE_RANGE_1Y = '1Y';
    const DATE_RANGE_2Y = '2Y';
    const DATE_RANGE_FOREVER = 'FOREVER';

    public static function getChoiceOptions(bool $allowForever = true, $maxLevel = null): array
    {
        $options = [
            'dateRange.14d' => self::DATE_RANGE_14D,
            'dateRange.1m' => self::DATE_RANGE_1M,
            'dateRange.2m' => self::DATE_RANGE_2M,
            'dateRange.3m' => self::DATE_RANGE_3M,
            'dateRange.6m' => self::DATE_RANGE_6M,
            'dateRange.1y' => self::DATE_RANGE_1Y,
            'dateRange.2y' => self::DATE_RANGE_2Y,
        ];

        if ($allowForever) {
            $options['dateRange.forever'] = self::DATE_RANGE_FOREVER;
        }

        if ($maxLevel !== null) {
            $newOptions = [];
            foreach ($options as $key => $value) {
                $newOptions[$key] = $value;

                if ($value === $maxLevel) {
                    break;
                }
            }

            $options = $newOptions;
        }

        return $options;
    }

    public static function isValidRange(string $dateRange, bool $allowForever = true, $maxLevel = null): bool
    {
        if (!$dateRange) {
            return false;
        }

        $options = self::getChoiceOptions($allowForever, $maxLevel);

        return in_array($dateRange, $options);
    }

    public static function getStartDateForRange(string $dateRange): \DateTime
    {
        if (!self::isValidRange($dateRange, false)) {
            $dateRange = self::DATE_RANGE_14D;
        }

        $date = (new \DateTime())
            ->sub(new \DateInterval('P' . $dateRange))
            ->setTime(0, 0);

        return $date;
    }
}