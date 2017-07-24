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
        //                    'http://www.kijiji.ca/rss-srp-bikes/kitchener-area/c644l1700209',

        $schedule->call(function(){
            $feeds = ['http://www.kijiji.ca/rss-srp-bikes/kitchener-waterloo/c644l1700212',
                    'http://www.kijiji.ca/rss-srp-road-bike/kitchener-waterloo/c648l1700212',                    
                    'http://www.kijiji.ca/rss-srp-kitchener-waterloo/l1700212?ad=offering&price-type=free',
                    'http://www.kijiji.ca/rss-srp-bikes/ontario/fat-bike/k0c644l9004'
		];
            foreach ($feeds as $feed) {            
                \Log::info('Refreshing feeds: ' . $feed);                

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
			            /*foreach($html->find('tr td') as $address ) {
                                if (stristr($address->plaintext, 'view map') ){
                                        $ad_loc = str_replace('View map','', $address->plaintext);
                                }
                        }*/
                        if ( count($html->find('span[class^=address]')) >= 1 ){
                            $ad_loc = $html->find('span[class^=address]')[0]->plaintext;
                        } else {
                            $ad_loc = 'NA';
                        }
                        
                        $ad_link = '<a href="http://maps.google.com/?q='.$ad_loc.'">'.$ad_loc.'</a>';
                        $description = "Location: " . $ad_link . "<br/>" . $description;
                                    
                        /*foreach($html->find('span[itemprop=price]') as $span) {     
                            $price = $span->plaintext;
                        }*/
                        if ( count( $html->find('span[class^=currentPrice]') ) >= 1 ){
                            $price = $html->find('span[class^=currentPrice]')[0]->plaintext;
                        } else {
                            $price = 'NA';
                        }
                        

                        /*foreach($html->find('div[id=ImageThumbnails] img') as $img) {
                            $src = str_replace('$_14', '$_27', $img->src);
                            $description .= "<img src='{$src}'> <br/>";
                        }*/
                        if ( count($html->find('div[class^=heroImage]')) >= 1 ){
                            preg_match('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $html->find('div[class^=heroImage]')[0]->innertext, $match);
                            $src = str_replace('&#x27', '', $match[0]);                            
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
        })->everyFiveMinutes()
        ->after(function () {
            \Log::info('Checking for new ads');
            $ads = Ad::whereEmailed(false)->get();            

            foreach($ads as $ad){
                $data['ad'] = $ad;
                $blocked_keywords = "[scrap|removal|membership|bmx|vintage|uber|scentsy|solar|boxes|computer repair|firewood|free ride|taxi|dish network|laptop repair|skids|outrageous|kickboxing|directv|inl3d|satellite|cancel|mattress|junk|ebike|delivery|trade|anxiety|channels|piano|e-bike|oil|similac|4000|epicure]";
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

        });
        
    }
}
