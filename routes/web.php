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

// Auth::routes();
Route::get('/', function () {
    return redirect('/admin');
});

Route::group(['prefix' => '/payment/paypal/webhook'], function () {
    Route::post('/deposit', 'TransactionController@depositPaypalWebHook');
    Route::post('/withdraw', 'TransactionController@withdrawPaypalWebHook');
    Route::get('/return', 'TransactionController@paypalReturn');
    Route::get('/cancel', 'TransactionController@paypalCancel');
});

Route::get('/term-privacy', 'HomeController@getTermAndPrivacy');
Route::get('/contact-us', 'HomeController@getContactUs');

Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)');
