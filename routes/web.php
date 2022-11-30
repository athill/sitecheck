<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'DashboardController@index');

// Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');





//// email testing
// Route::get('/email/notify', function() {
// 	$service = new App\Service\SiteCheckService;
// 	$notifications = $service->notifications($service->getConfig());
// 	return new App\Mail\Notify($notifications);
// });


// Route::get('/email/summary', function() {
// 	$service = new App\Services\SitecheckService;
// 	$checks = $service->summary();
// 	return new App\Mail\Summary($checks);
// });
