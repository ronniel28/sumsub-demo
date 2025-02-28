<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\SumsubController;
use Illuminate\Support\Facades\Route;


// Route::post('/applicants', [ApplicantController::class, 'store'])->name('applicant.store');

Auth::routes();

Route::get('/', function () {
    return view('homepage');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::prefix('portal')->middleware('role:Admin')->group(function () {
        //Admin routes
    });

    Route::middleware('role:User')->group(function () {
        //User routes
    });

    //Common routes
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile')->middleware('auth');

    Route::get('/submit-id', [ApplicantController::class, 'index'])->name('verify.index');
    Route::post('/generate-websdk-link', [SumsubController::class, 'generateWebSdkLink']);
    Route::post('/generate-token', [SumsubController::class, 'generateToken']);
    Route::get('applicant-data', [SumsubController::class, 'getApplicantData'])->name('applicant.data');
});