<?php
/*
 * File name: SalonTest.php
 * Last modified: 2022.10.16 at 11:45:57
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2022
 */

namespace Models;

use App\Models\Salon;
use Carbon\Carbon;
use Tests\TestCase;

class SalonTest extends TestCase
{

    public function testGetAvailableAttribute()
    {
        $salon = Salon::find(17);
        $this->assertTrue($salon->available);
        $this->assertTrue($salon->accepted);
        $this->assertTrue($salon->openingHours()->isOpenAt(new Carbon('2021-02-05 12:00:00')));
    }

    public function testOpeningHours()
    {
        $salon = Salon::find(3);
        $open = $salon->openingHours()->isOpenAt(new Carbon('2021-02-05 12:00:00'));
        $this->assertTrue($open);
    }

    public function testWeekCalendar()
    {
        $salon = Salon::find(3);
        $dates = $salon->weekCalendarRange(Carbon::createFromFormat('Y-m-d', "2022-05-09"), 0);
        $this->assertIsArray($dates);
    }

    public function testSalonReview()
    {
        $salon = Salon::find(7);
        $reviews = $salon->salonReviews()->get();
        $this->assertTrue(true);
    }

    public function testGetTotalReviewsAttribute()
    {
        $salon = Salon::find(17);
        $total = $salon->getTotalReviewsAttribute();
        $this->assertEquals(0, $total);
    }


    public function testGetHasValidSubscriptionAttribute()
    {
        $salon = Salon::find(15);
        $this->assertTrue($salon->has_valid_subscription);
    }
}
