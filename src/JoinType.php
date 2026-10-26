<?php

declare(strict_types=1);

namespace HeliomarPM\LinqPHP;

enum JoinType: string
{
    case INNER = 'INNER';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case FULL = 'FULL';
}
