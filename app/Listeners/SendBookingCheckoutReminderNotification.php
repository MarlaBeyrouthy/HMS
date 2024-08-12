<?php

namespace App\Listeners;

use App\Events\BookingCheckoutReminder;
use App\Notifications\CheckoutReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendBookingCheckoutReminderNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingCheckoutReminder $event)
    {
        // Get the booking from the event
        $booking = $event->booking;

        // Send the notification to the user associated with the booking
        Notification::send($booking->user, new CheckoutReminderNotification($booking));
    }
}
