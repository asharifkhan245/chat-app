<?php

namespace App\Console\Commands;

use App\Mail\MinuteMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CronJobCommand extends Command
{
    protected $signature = 'cronjob:run';
    protected $description = 'Run cron job logic';

    public function handle()
    {
        
        Mail::to('asharifkhan245@gmail.com')->send(new MinuteMail());
    }
    
}
