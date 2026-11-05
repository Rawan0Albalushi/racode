<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth', 'active', 'admin.locale'])
    ->name('admin.')
    ->group(function () {
        Route::post('/locale', [AuthenticatedSessionController::class, 'locale'])->name('locale');
        Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

        Route::middleware('permission:pages.view')->group(function () {
            Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
            Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        });
        Route::middleware('permission:pages.create')->group(function () {
            Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
            Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        });
        Route::middleware('permission:pages.update')->group(function () {
            Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
            Route::post('/pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');
            Route::post('/pages/{page}/sections', [SectionController::class, 'store'])->name('sections.store');
            Route::put('/pages/{page}/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
            Route::delete('/pages/{page}/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');
            Route::post('/pages/{page}/sections/reorder', [SectionController::class, 'reorder'])->name('sections.reorder');
        });
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->middleware('permission:pages.delete')->name('pages.destroy');
        Route::post('/pages/{page}/publish', [PageController::class, 'publish'])->middleware('permission:pages.publish')->name('pages.publish');
        Route::post('/pages/{page}/schedule', [PageController::class, 'schedule'])->middleware('permission:pages.publish')->name('pages.schedule');

        Route::middleware('permission:services.view')->group(function () {
            Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
            Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        });
        Route::middleware('permission:services.create')->group(function () {
            Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
            Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        });
        Route::put('/services/{service}', [ServiceController::class, 'update'])->middleware('permission:services.update')->name('services.update');
        Route::post('/services/reorder', [ServiceController::class, 'reorder'])->middleware('permission:services.update')->name('services.reorder');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->middleware('permission:services.delete')->name('services.destroy');
        Route::post('/services/{service}/publish', [ServiceController::class, 'publish'])->middleware('permission:services.publish')->name('services.publish');
        Route::post('/services/{service}/schedule', [ServiceController::class, 'schedule'])->middleware('permission:services.publish')->name('services.schedule');

        Route::middleware('permission:projects.view')->group(function () {
            Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
            Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        });
        Route::middleware('permission:projects.create')->group(function () {
            Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
            Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        });
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->middleware('permission:projects.update')->name('projects.update');
        Route::post('/project-categories', [ProjectController::class, 'storeCategory'])->middleware('permission:projects.update')->name('project-categories.store');
        Route::delete('/project-categories/{category}', [ProjectController::class, 'destroyCategory'])->middleware('permission:projects.delete')->name('project-categories.destroy');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->middleware('permission:projects.delete')->name('projects.destroy');
        Route::post('/projects/{project}/publish', [ProjectController::class, 'publish'])->middleware('permission:projects.publish')->name('projects.publish');
        Route::post('/projects/{project}/schedule', [ProjectController::class, 'schedule'])->middleware('permission:projects.publish')->name('projects.schedule');

        Route::middleware('permission:articles.view')->group(function () {
            Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
            Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
        });
        Route::middleware('permission:articles.create')->group(function () {
            Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
            Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
        });
        Route::put('/articles/{article}', [ArticleController::class, 'update'])->middleware('permission:articles.update')->name('articles.update');
        Route::post('/article-categories', [ArticleController::class, 'storeCategory'])->middleware('permission:articles.update')->name('article-categories.store');
        Route::post('/tags', [ArticleController::class, 'storeTag'])->middleware('permission:articles.update')->name('tags.store');
        Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->middleware('permission:articles.delete')->name('articles.destroy');
        Route::post('/articles/{article}/publish', [ArticleController::class, 'publish'])->middleware('permission:articles.publish')->name('articles.publish');
        Route::post('/articles/{article}/schedule', [ArticleController::class, 'schedule'])->middleware('permission:articles.publish')->name('articles.schedule');

        Route::middleware('permission:faqs.view')->group(function () {
            Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
            Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
        });
        Route::middleware('permission:faqs.create')->group(function () {
            Route::get('/faqs/create', [FaqController::class, 'create'])->name('faqs.create');
            Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
        });
        Route::put('/faqs/{faq}', [FaqController::class, 'update'])->middleware('permission:faqs.update')->name('faqs.update');
        Route::post('/faqs/reorder', [FaqController::class, 'reorder'])->middleware('permission:faqs.update')->name('faqs.reorder');
        Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->middleware('permission:faqs.delete')->name('faqs.destroy');
        Route::post('/faqs/{faq}/publish', [FaqController::class, 'publish'])->middleware('permission:faqs.publish')->name('faqs.publish');

        Route::get('/technologies', [TechnologyController::class, 'index'])->middleware('permission:technologies.view')->name('technologies.index');
        Route::post('/technologies', [TechnologyController::class, 'store'])->middleware('permission:technologies.update')->name('technologies.store');
        Route::put('/technologies/{technology}', [TechnologyController::class, 'update'])->middleware('permission:technologies.update')->name('technologies.update');
        Route::delete('/technologies/{technology}', [TechnologyController::class, 'destroy'])->middleware('permission:technologies.update')->name('technologies.destroy');
        Route::post('/technologies/reorder', [TechnologyController::class, 'reorder'])->middleware('permission:technologies.update')->name('technologies.reorder');

        Route::get('/media', [MediaController::class, 'index'])->middleware('permission:media.view')->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->middleware('permission:media.upload')->name('media.store');
        Route::put('/media/{medium}', [MediaController::class, 'update'])->middleware('permission:media.upload')->name('media.update');
        Route::post('/media/{medium}/replace', [MediaController::class, 'replace'])->middleware('permission:media.upload')->name('media.replace');
        Route::delete('/media/{medium}', [MediaController::class, 'destroy'])->middleware('permission:media.delete')->name('media.destroy');

        Route::middleware('permission:inquiries.view')->group(function () {
            Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
            Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
        });
        Route::put('/inquiries/{inquiry}', [InquiryController::class, 'update'])->middleware('permission:inquiries.update')->name('inquiries.update');
        Route::post('/inquiries/{inquiry}/notes', [InquiryController::class, 'note'])->middleware('permission:inquiries.update')->name('inquiries.notes');
        Route::get('/inquiries-export', [InquiryController::class, 'export'])->middleware('permission:inquiries.export')->name('inquiries.export');

        Route::get('/navigation', [NavigationController::class, 'edit'])->name('navigation.edit');
        Route::put('/navigation', [NavigationController::class, 'update'])->middleware('permission:navigation.update')->name('navigation.update');

        Route::get('/settings', [SettingController::class, 'edit'])->middleware('permission:settings.update')->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');

        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        });
        Route::middleware('permission:users.create')->group(function () {
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
        });
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

        Route::get('/activity', [ActivityController::class, 'index'])->middleware('permission:activity.view')->name('activity.index');
        Route::post('/revisions/{revision}/restore', [RevisionController::class, 'restore'])->middleware('permission:revisions.restore')->name('revisions.restore');
    });
