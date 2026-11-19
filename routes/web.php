<?php

use App\Services\CareerGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'guide');

Route::post('/chat', function (Request $request, CareerGuide $guide) {
    $input = $request->validate(['message' => ['required', 'string', 'max:600']]);
    $state = $request->session()->get('guide', []);
    $reply = $guide->reply($input['message'], $state);
    $transcript = $request->session()->get('guide_transcript', []);
    $transcript[] = ['author' => 'user', 'text' => $input['message']];
    $transcript[] = ['author' => 'bot', 'text' => $reply];
    $request->session()->put('guide', $state);
    $request->session()->put('guide_transcript', array_slice($transcript, -40));

    return response()->json(['reply' => $reply]);
})->block(10, 10);

Route::delete('/chat', function (Request $request) {
    $request->session()->forget(['guide', 'guide_transcript']);

    return response()->noContent();
})->block(10, 10);
