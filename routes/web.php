<?php

use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    return view('notfound');
});

Route::get('/home', function () {
    return redirect("https://tuchop.com");
});
