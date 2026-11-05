<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Allow: /',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $paths = ['/', '/about', '/services', '/work', '/technologies', '/insights', '/contact', '/privacy', '/terms'];

        foreach (Service::query()->visible()->get() as $service) {
            $paths[] = '/services/'.$service->slug;
        }

        foreach (Project::query()->visible()->get() as $project) {
            $paths[] = '/work/'.$project->slug;
        }

        foreach (Article::query()->visible()->get() as $article) {
            $paths[] = '/insights/'.$article->slug;
        }

        foreach (Page::query()->visible()->where('template', 'document')->where('is_system', false)->get() as $page) {
            $paths[] = '/p/'.$page->slug;
        }

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        foreach (array_unique($paths) as $path) {
            foreach (['ar', 'en'] as $locale) {
                $loc = htmlspecialchars(url('/'.$locale.($path === '/' ? '' : $path)), ENT_XML1);
                $xml[] = '<url><loc>'.$loc.'</loc>';

                foreach (['ar', 'en'] as $alternate) {
                    $href = htmlspecialchars(url('/'.$alternate.($path === '/' ? '' : $path)), ENT_XML1);
                    $xml[] = '<xhtml:link rel="alternate" hreflang="'.$alternate.'" href="'.$href.'"/>';
                }

                $xml[] = '</url>';
            }
        }

        $xml[] = '</urlset>';

        return response(implode('', $xml), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
