<?php

use App\Models\Title;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', function () {
    $urls = Title::query()->where('is_published', true)->get()
        ->map(fn (Title $title) => sprintf(
            '<url><loc>%s</loc><lastmod>%s</lastmod></url>',
            e(url(($title->type === 'series' ? '/series/' : '/movies/').$title->slug.'-'.$title->id)),
            $title->updated_at->toAtomString(),
        ))->implode('');

    return response(
        '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        .'<url><loc>'.e(url('/')).'</loc></url>'.$urls.'</urlset>',
        200,
        ['Content-Type' => 'application/xml'],
    );
});

Route::view('/reset-password/{token}', 'welcome')->name('password.reset');

Route::get('/{section}/{slugId}', function (string $section, string $slugId) {
    abort_unless(preg_match('/-(\d+)$/', $slugId, $matches), 404);
    $title = Title::query()->whereKey($matches[1])->where('is_published', true)->firstOrFail();
    abort_unless(($section === 'series') === ($title->type === 'series'), 404);

    return view('welcome', [
        'title' => $title,
        'pageTitle' => $title->name.' — Kadriva',
        'description' => str($title->description)->limit(155),
        'image' => $title->backdrop_url ?: $title->poster_url,
    ]);
})->whereIn('section', ['movies', 'series']);

Route::view('/{path?}', 'welcome')
    ->where('path', '^(?!admin(?:/|$)|api(?:/|$)|up$).*$');
