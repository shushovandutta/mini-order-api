<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\OrderPlacedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // কাস্টমারের ইমেইলে ওল্ড-স্টাইল মেইলটি পাঠানো
        if ($this->order->user && $this->order->user->email) {
            Mail::to($this->order->user->email)->send(new OrderPlacedMail($this->order));
        }
    }
}