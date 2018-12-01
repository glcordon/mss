<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Location;
use Setting;
use MetaTag;
use LaravelLocalization;
use Theme;

class HomeController extends Controller
{

    protected $category_id;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function postIndex(Request $request) {
        $url = http_build_query($request->except('_token'));
        return redirect('/home?'.$url);
    }

    public function redirect()
    {
        return redirect('/');
    }

    public function index(Request $request)
    {

        return app('App\Http\Controllers\BrowseController')->listings($request);
        /*
        if(!setting('custom_homepage')) {
            return app('App\Http\Controllers\BrowseController')->listings($request);
        }

        $current_locale = LaravelLocalization::getCurrentLocale();
        $data['widgets'] = Widget::where('locale', $current_locale)->orderBy('position', 'ASC')->get();
        $data['show_search'] = false;

        MetaTag::set('title', Setting::get('home_title'));
        MetaTag::set('description', Setting::get('home_description'));

        return view('home.index', $data);*/
		
    }

}
