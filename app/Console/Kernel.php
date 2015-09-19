<?php

namespace App\Console;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /*$schedule->command('inspire')
                 ->hourly();*/
        $schedule->call(function () {
                    $params = [];
                    $request = Request::create('parsefeed', 'GET', $params);
                    return Route::dispatch($request)->getContent();
                })->everyTenMinutes();

        $schedule->call(function () {
            $ads = \App\Ad::whereEmailed(false)->get();            

            foreach($ads as $ad){
                $data['ad'] = $ad;

                Mail::send('emails.ad', $data, function($message) use ($data)
                {
                    $message->to('hbalagtas@uwaterloo.ca', 'Herbert Balagtas')->subject('Jijiki Alert: ' . $data['ad']->title);
                    $message->from('jijiki@uwaterloo.ca', 'Jijiki Alert');
                });

                $ad->emailed = true;
                $ad->save();
            }

        })->everyFiveMinutes();
    }
}
