<?php
use Illuminate\Support\Facades\Route;
use App\Events\MessagePosted;

Route::get('/', fn () => view('chat'));
Route::get('/broadcast', function () {
    $msg = request('msg', 'hello-'.now()->format('H:i:s'));
    MessagePosted::dispatch($msg);
    return response()->json(['ok' => true, 'broadcast' => $msg]);
});
