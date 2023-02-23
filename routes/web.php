<?php

use Illuminate\Support\Facades\Route;

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
Route::any( '/{path?}', function(){
    return view( 'index' );
} )->where('path', '.*');

Route::any( '/login', function(){
    return view( 'index' );
} )->where('path', '.*');

Route::any( '/vendor/login', function(){
    return view( 'index' );
} )->where('path', '.*');


Route::any( '/register', function(){
    return view( 'index' );
} )->where('path', '.*');




