<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
	$user = new App\User;
    return view('welcome');
});

Route::get('parsefeed/{feed}', 
	['as' => 'parsefeed', 'uses' => 'Ads@parsefeed']);

Route::get('/parsefeed-test', function() {
	if (Cache::has('myfeed')){
		$feed = Cache::get('myfeed');
		echo "cached<br>";
	}else{
		$feed = Feeds::make('http://www.kijiji.ca/rss-srp-bikes/kitchener-waterloo/c644l1700212');
		Cache::put('myfeed', $feed, 9);	
		echo "NOT cached<br>";
	}
	$data = array(
			'title' => $feed->get_title(),
			'permalink' => $feed->get_permalink(),
			'items' => $feed->get_items()
		);

	$parser = new HtmlDomParser;
	foreach ($data['items'] as $item){
		$tokens = explode('/', $item->get_link());
		$id = end($tokens);
		if (!App\Ad::find($id)){	
			$price = '';		
			$title = $item->get_title();
			$description = $item->get_description() . "<br/>=================<br/>";
			$link = $item->get_link();

			foreach($html->find('tr td') as $address ) {
                		if (stristr($address->plaintext, 'view map') ){
                        		$ad_loc = str_replace('View map','', $address->plaintext);
                		}
        		}
			$ad_link = '<a href="http://maps.google.com/?q='.$ad_loc.'">'.$ad_loc.'</a>';
			$description .= "<br/>==================<br/>" . $ad_link;				
			$html = $parser->file_get_html($link);
			foreach($html->find('span[itemprop=price]') as $span) {		
				$price = $span->plaintext;
			}
			foreach($html->find('div[id=ImageThumbnails] img') as $img) {
				$src = str_replace('$_14', '$_27', $img->src);
				$description .= "<img src='{$src}'> <br/>";
			}

			$ad = new App\Ad;
			$ad->id = $id;
			$ad->title = $title;
			$ad->description = $description;
			$ad->price = $price;
			$ad->link = $link;
			$ad->save();

			echo "Added " . $id . " {$price}<br/>";
		} else {
			echo "Item already on database " . $id . "<br/>";
		}
		
	}
	#return false;
});

Route::get('htmlparser', function() {
	$parser = new HtmlDomParser;
	$url = "http://www.kijiji.ca/v-bike-frames-parts/kitchener-waterloo/cat-eye-astrale-8-bike-computer-with-cadence/1149312122";
	$html = $parser->file_get_html($url);

	foreach($html->find('tr td') as $address ) {
		if (stristr($address->plaintext, 'view map') ){
			$ad_loc = str_replace('View map','', $address->plaintext);
		}
	}
	#echo $html->plaintext;	
	foreach($html->find('span[itemprop=price]') as $span) {		
		$price = $span->plaintext;
	}

	echo $price;
	echo 'Address' . $ad_loc;

	foreach($html->find('div[id=ImageThumbnails] img') as $img) {
		$src = str_replace('$_14', '$_27', $img->src);
		echo "<img src='{$src}'>";
	}
});

Route::get('mailtest', function(){
	$ads = App\Ad::whereEmailed(false)->get();            

    foreach($ads as $ad){
        $data['ad'] = $ad;

        $ret = Mail::send(['html' => 'emails.ad'], $data, function($message) use ($data)
        {
            $message->to('hbalagtas@live.com', 'Herbert Balagtas')->subject('Jijiki Alert: ' . html_entity_decode(html_entity_decode($data['ad']->title)));
            $message->from('jijiki@hbalagtas.linuxd.org', 'Jijiki Alert');
        });
        var_dump($ret);
        #$ad->emailed = true;
        $ad->save();
    }

});

Route::get('rssreader', function() {
	$rss = 'http://www.kijiji.ca/rss-srp-bikes/ontario/fat-bike/k0c644l9004';

	$feed = Feeds::make($rss);
	$items = $feed->get_items();
	$parser = new HtmlDomParser;
	foreach( $items as $item ){
		$tokens = explode('/', $item->get_link());
		$id = end($tokens);
		

		$price = '';        
		$title = $item->get_title();
		$description = $item->get_description() . "<br/>=================<br/>";
		$link = $item->get_link();

		$html = $parser->file_get_html($link);
		$ad_loc = $html->find('span[class^=address]')[0]->plaintext;
		

		$ad_link = '<a href="http://maps.google.com/?q='.$ad_loc.'">'.$ad_loc.'</a>';
                        $description = "Location: " . $ad_link . "<br/>" . $description;
        $price = $html->find('span[class^=currentPrice]')[0]->plaintext;

        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $html->find('div[class^=heroImage]')[0]->innertext, $match);
        $src = $match[0][0];
        $description .= "<img src='{$src}'> <br/>";

        $ad = new App\Ad;
        $ad->id = $id;
        $ad->title = $title;
        $ad->description = $description;
        $ad->price = $price;
        $ad->link = $link;

        return $ad;
	}
});