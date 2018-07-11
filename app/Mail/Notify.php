<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;


class Notify extends Mailable
{
    use Queueable, SerializesModels;

    public $sites;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($sites=[])
    {
        $this->sites = $sites;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notify')
            ->subject('Changes in site statuses');
    }
}
