<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\SitecheckService;

class DashboardController extends Controller
{
    public function index() {
    	$service = new SitecheckService;
    	$latest = $service->latest();
    	return view('dashboard')->with('latest', $latest);
    }
}
