<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
}
