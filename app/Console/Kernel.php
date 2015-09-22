<?php

namespace App\Console;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Routing\Router;

use App\Ad;
use \Cache;
use \Feeds;
use \HtmlDomParser;

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

        $schedule->call(function(){
            $feeds = ['http://www.kijiji.ca/rss-srp-bikes/kitchener-waterloo/c644l1700212',
                    'http://www.kijiji.ca/rss-srp-kitchener-waterloo/l1700212?ad=offering&price-type=free'];
            foreach ($feeds as $feed) {            
                \Log::info('Refreshing feeds: ' . $feed);

                /*$feed_key = md5($feed);  

                \Log::info('Parsing feed' . $feed_key);
                if (Cache::has($feed_key)){
                    $feed = Cache::get($feed_key);            
                    echo "Cached<br>";
                }else{
                    $feed = Feeds::make($feed);
                    Cache::put($feed_key, $feed, 9); 
                    echo "NOT cached<br>";
                }*/

                $feed = Feeds::make($feed);
                $data = array(
                        'title' => $feed->get_title(),
                        'permalink' => $feed->get_permalink(),
                        'items' => $feed->get_items()
                    );

                $parser = new HtmlDomParser;
                foreach ($data['items'] as $item){
                    $tokens = explode('/', $item->get_link());
                    $id = end($tokens);
                   
                    if (!Ad::find($id)){    
                        $price = '';        
                        $title = $item->get_title();
                        $description = $item->get_description() . "<br/>=================<br/>";
                        $link = $item->get_link();
                                    
                        $html = $parser->file_get_html($link);
                        foreach($html->find('span[itemprop=price]') as $span) {     
                            $price = $span->plaintext;
                        }
                        foreach($html->find('div[id=ImageThumbnails] img') as $img) {
                            $src = str_replace('$_14', '$_27', $img->src);
                            $description .= "<img src='{$src}'> <br/>";
                        }

                        $ad = new Ad;
                        $ad->id = $id;
                        $ad->title = $title;
                        $ad->description = $description;
                        $ad->price = $price;
                        $ad->link = $link;
                        $ad->save();

                        \Log::info("Added " . $id . " {$price}");
                    } else {
                        #\Log::info("Item already on database " . $id);
                    }
                    
                }
            }
        })->everyFiveMinutes();
        /*$schedule->call(function () {
                    $params = ['feed' => \Crypt::encrypt('http://www.kijiji.ca/rss-srp-bikes/kitchener-waterloo/c644l1700212')];
                    $request = Request::create('parsefeed', 'GET', $params);
                    \Log::info('Refreshing feeds: ' . $params['feed']);                    

                    return \Route::dispatch($request)->getContent();
                })->everyMinute();*/

        $schedule->call(function () {
            \Log::info('Checking for new ads');
            $ads = Ad::whereEmailed(false)->get();            

            foreach($ads as $ad){
                $data['ad'] = $ad;
                $blocked_keywords = "[scrap|removal|membership]";
                if (preg_match($blocked_keywords, strtolower($ad->title)) == 0){
                    \Log::info('Emailing new ad: ' . $ad->title);

                    $ret = \Mail::send(['html' => 'emails.ad'], $data, function($message) use ($data)
                            {
                                $message->to('hbalagtas@live.com', 'Herbert Balagtas')->subject('Jijiki Alert: ' . $data['ad']->price .' - ' . html_entity_decode(html_entity_decode($data['ad']->title)));
                                $message->from('jijiki@hbalagtas.linuxd.org', 'Jijiki Alert');
                            });
                } else {
                    \Log::info('Skipping spam ad: ' . $ad->title);
                }
                $ad->emailed = true;
                $ad->save();
            }

        })->everyTenMinutes();
    }

    public function parsefeeds()
    {
        
    }
}
