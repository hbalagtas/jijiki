<?php

namespace App\Http\Controllers;

use App\Ad;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Cache;
use \Feeds;
use \HtmlDomParser;


class Ads extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function parsefeed($feed)
    {
        // start parsing feed  
        $feed_key = md5($feed);  
        $feed = \Crypt::decrypt($feed);
        \Log::info('Parsing feed' . $feed_key);
        if (Cache::has($feed_key)){
            $feed = Cache::get($feed_key);            
            echo "Cached<br>";
        }else{
            $feed = Feeds::make($feed);
            Cache::put($feed_key, $feed, 9); 
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
            \Log::info($id);
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

                echo "Added " . $id . " {$price}<br/>";
            } else {
                echo "Item already on database " . $id . "<br/>";
            }
            
        }
    }
}
