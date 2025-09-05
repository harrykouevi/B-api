<?php
/*
 * File name: PaymentService.php
 * Last modified: 2025.03.06 at 11:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Types;


enum PaymentType: string {
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}


