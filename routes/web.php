<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PublicTemplateController;
use App\Http\Middleware\EnsureMfaEnabled;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/guidelines', [PublicTemplateController::class, 'index'])->name('guidelines');
Route::get('/templates/{id}', [PublicTemplateController::class, 'download'])->whereNumber('id')->middleware('throttle:30,1')->name('templates.download');
Route::view('/faq', 'faq')->name('faq');
Route::get('/login', fn () => redirect('/hei/login'))->name('login');
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->middleware('throttle:10,1');
Route::post('/invitations/{token}', [InvitationController::class, 'accept'])->middleware('throttle:5,1')->name('invitations.accept');
Route::get('/documents/{publicId}', DocumentController::class)->middleware(['auth', EnsureMfaEnabled::class])->name('documents.download');
Route::get('/exports/{export}', ExportController::class)->middleware(['auth', EnsureMfaEnabled::class])->name('exports.download');

Route::get('/documents/{publicId}/preview', [DocumentController::class, 'preview'])->middleware(['auth', EnsureMfaEnabled::class])->name('documents.preview');
