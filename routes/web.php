<?php

use App\Http\Controllers\PublicArchiveController;
use App\Http\Controllers\PublicArticleController;
use App\Http\Controllers\PublicAuthorController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicSearchController;
use App\Http\Controllers\PublicSeoController;
use App\Http\Controllers\PublicStaticPageController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [PublicSeoController::class, 'robots'])
    ->name('robots');

Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])
    ->name('sitemap');

Route::get('/sitemap-news.xml', [PublicSeoController::class, 'newsSitemap'])
    ->name('sitemap.news');

Route::get('/sitemap-image.xml', [PublicSeoController::class, 'imageSitemap'])
    ->name('sitemap.image');

Route::get('/sitemap-video.xml', [PublicSeoController::class, 'videoSitemap'])
    ->name('sitemap.video');

Route::get('/', PublicHomeController::class)
    ->name('home');

Route::get('/authors/{slug}', [PublicAuthorController::class, 'show'])
    ->name('authors.show');

Route::get('/search', PublicSearchController::class)
    ->middleware('throttle:search')
    ->name('search');

Route::get('/pages/{slug}', [PublicStaticPageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('static.show');

Route::get('/archive', [PublicArchiveController::class, 'index'])
    ->name('archive.index');

Route::get('/archive/{year}', [PublicArchiveController::class, 'year'])
    ->whereNumber('year')
    ->name('archive.year');

Route::get('/archive/{year}/{month}', [PublicArchiveController::class, 'month'])
    ->whereNumber('year')
    ->whereNumber('month')
    ->name('archive.month');

Route::get('/archive/{year}/{month}/{day}', [PublicArchiveController::class, 'date'])
    ->whereNumber('year')
    ->whereNumber('month')
    ->whereNumber('day')
    ->name('archive.date');

Route::get('/articles/{slug}', [PublicArticleController::class, 'show'])
    ->name('articles.show');
