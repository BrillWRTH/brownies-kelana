<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderLifecycleNotification extends Notification
{
    use Queueable;

    /**
     * Membuat instance notifikasi email siklus hidup pesanan Brownies Kelana.
     */
    public function __construct(
        public Order $order,
        public string $mailSubject,
        public string $headline,
        public string $bodyMessage,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null
    ) {}

    /**
     * Menentukan kanal pengiriman notifikasi standar melalui email.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Menyusun isi email notifikasi pesanan dalam bahasa Indonesia.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->mailSubject)
            ->greeting('Halo Kak '.$this->order->customer_name.',')
            ->line($this->headline)
            ->line($this->bodyMessage)
            ->line('Nomor Pesanan: '.$this->order->order_number)
            ->line('Total Pembayaran: Rp '.number_format((int) $this->order->total_amount, 0, ',', '.'))
            ->line('Status Pesanan: '.$this->order->orderStatusLabel().' ('.$this->order->paymentStatusLabel().')');

        if ($this->order->scheduled_date) {
            $mail->line(
                'Jadwal '.($this->order->delivery_type === 'pickup' ? 'Pengambilan' : 'Pengiriman').': '
                .$this->order->scheduled_date->locale('id')->isoFormat('dddd, D MMMM YYYY')
                .' · '.$this->order->scheduled_time_slot
            );
        }

        if ($this->actionLabel && $this->actionUrl) {
            $mail->action($this->actionLabel, $this->actionUrl);
        }

        return $mail->salutation('Salam hangat, Dapur Brownies Kelana');
    }
}
