<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->view('emails.order_placed') // আমাদের সাধারণ ব্লেড ভিউ ফাইল
            ->subject('Your Order #' . $this->order->id . ' has been placed!')
            ->with([
                'orderId' => $this->order->id,
                'totalAmount' => $this->order->total_price,
                'customerName' => $this->order->user->name ?? 'Customer',
                'orderItems' => $this->order->items
            ]);
    }
}
