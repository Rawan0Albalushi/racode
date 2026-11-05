<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\SiteController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SitemapController::class, 'robots']);
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap']);

Route::get('/', function () {
    $preferred = request()->cookie('locale');
    $locale = in_array($preferred, config('racode.locales'), true) ? $preferred : 'ar';

    return redirect('/'.$locale);
});

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

require __DIR__.'/admin.php';

Route::prefix('{locale}')
    ->whereIn('locale', config('racode.locales'))
    ->middleware('locale')
    ->group(function () {
        Route::get('/', [SiteController::class, 'home'])->name('home');
        Route::get('/about', [SiteController::class, 'about'])->name('about');
        Route::get('/services', [SiteController::class, 'services'])->name('services.index');
        Route::get('/services/{service}', [SiteController::class, 'service'])->name('services.show');
        Route::get('/work', [SiteController::class, 'work'])->name('work.index');
        Route::get('/work/{project}', [SiteController::class, 'project'])->name('work.show');
        Route::get('/technologies', [SiteController::class, 'technologies'])->name('technologies');
        Route::get('/insights', [SiteController::class, 'insights'])->name('insights.index');
        Route::get('/insights/{article}', [SiteController::class, 'article'])->name('insights.show');
        Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
        Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
        Route::get('/privacy', [SiteController::class, 'privacy'])->name('privacy');
        Route::get('/terms', [SiteController::class, 'terms'])->name('terms');
        Route::get('/p/{page}', [SiteController::class, 'page'])->name('pages.show');
    });
