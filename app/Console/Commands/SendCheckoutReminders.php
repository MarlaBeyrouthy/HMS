<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Events\BookingCheckoutReminder;
use Carbon\Carbon;


class SendCheckoutReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send checkout reminders to users with upcoming checkouts.';

    public function handle()
    {
        $bookings = Booking::where('check_out_date', Carbon::now()->format('Y-m-d'))
                           ->where('payment_status', 'Pre_payment')
                           ->get();

        foreach ($bookings as $booking) {
            event(new BookingCheckoutReminder($booking));
        }

        $this->info('Checkout reminders have been sent.');
    }
}

