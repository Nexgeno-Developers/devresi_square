<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;

class WebsiteController
{
	// public function __construct() {
    //     // Staff Permission Check
    //     $this->middleware(['permission:header_setup'])->only('header');
    //     $this->middleware(['permission:footer_setup'])->only('footer');
    //     $this->middleware(['permission:view_all_website_pages'])->only('pages');
    //     $this->middleware(['permission:website_appearance'])->only('appearance');
    // }

	public function header(Request $request)
	{
		return view('backend.website_settings.header');
	}
	public function footer(Request $request)
	{
		return view('backend.website_settings.footer');
	}

	public function appearance(Request $request)
	{
		return view('backend.website_settings.appearance');
	}
}