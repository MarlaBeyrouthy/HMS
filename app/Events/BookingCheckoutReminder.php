<?php

namespace App\Events;

use App\Models\Booking;
use App\Notifications\CheckoutReminderNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class BookingCheckoutReminder implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;

    /**
     * Create a new event instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        Notification::send($booking->user, new CheckoutReminderNotification($booking));

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('booking.'.$this->booking->user_id);
       // return new Channel('booking');

    }

    public function broadcastWith()
    {
        return [
            'booking_id' => $this->booking->id,
            'checkout_date' => $this->booking->check_out_date,
            'message' => 'Reminder: Please complete your payment before the checkout date.',
        ];
    }

}
