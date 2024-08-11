<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CheckoutReminderNotification extends Notification implements ShouldBroadcast

{
    use Queueable;
    public $booking;


    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    /*
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }
*/
    /**
     * Get the array representation of the notification.
     *
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable): BroadcastMessage {
        return new BroadcastMessage([
            'booking_id' => $this->booking->id,
            'checkout_date' => $this->booking->check_out_date,
            'message' => 'Reminder: Please complete your payment before the checkout date.',
        ]);
    }
    /**
     * Get the array representation of the notification for storage in the database.
     *
     * @return array
     */
    public function toDatabase($notifiable): array {
        return [
            'booking_id' => $this->booking->id,
            'checkout_date' => $this->booking->check_out_date,
            'message' => 'Reminder: Please complete your payment before the checkout date.',
        ];
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
