<?php declare(strict_types=1);

namespace App\Entity;

enum EventIntervals: int
{
    case NonRecurring = 1; // TODO: remove but make column nullable
    case Daily = 2;
    case Weekly = 3;
    case BiMonthly = 4;
    case Monthly = 5;
    case Yearly = 6;
}
