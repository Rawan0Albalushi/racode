<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function __construct(private PublicContent $content) {}

    public function home(Request $request): Response
    {
        return Inertia::render('website/Home', $this->content->home($this->preview($request, 'pages.update')));
    }

    public function about(Request $request): Response
    {
        return Inertia::render('website/Document', $this->content->document('document', 'about', $this->preview($request, 'pages.update')));
    }

    public function privacy(Request $request): Response
    {
        return Inertia::render('website/Document', $this->content->document('document', 'privacy', $this->preview($request, 'pages.update')));
    }

    public function terms(Request $request): Response
    {
        return Inertia::render('website/Document', $this->content->document('document', 'terms', $this->preview($request, 'pages.update')));
    }

    public function page(Request $request, string $locale, string $page): Response
    {
        return Inertia::render('website/Document', $this->content->document('document', $page, $this->preview($request, 'pages.update'), true));
    }

    public function services(Request $request): Response
    {
        return Inertia::render('website/Services/Index', $this->content->servicesIndex($this->preview($request, 'services.update')));
    }

    public function service(Request $request, string $locale, string $service): Response
    {
        return Inertia::render('website/Services/Show', $this->content->service($service, $this->preview($request, 'services.update')));
    }

    public function work(Request $request): Response
    {
        return Inertia::render('website/Work/Index', $this->content->projectsIndex($request->string('category')->toString() ?: null, $this->preview($request, 'projects.update')));
    }

    public function project(Request $request, string $locale, string $project): Response
    {
        return Inertia::render('website/Work/Show', $this->content->project($project, $this->preview($request, 'projects.update')));
    }

    public function technologies(Request $request): Response
    {
        return Inertia::render('website/Technologies', $this->content->technologies($this->preview($request, 'technologies.update')));
    }

    public function insights(Request $request): Response
    {
        return Inertia::render('website/Insights/Index', $this->content->articlesIndex($request->string('category')->toString() ?: null, $this->preview($request, 'articles.update')));
    }

    public function article(Request $request, string $locale, string $article): Response
    {
        return Inertia::render('website/Insights/Show', $this->content->article($article, $this->preview($request, 'articles.update')));
    }

    public function contact(Request $request): Response
    {
        return Inertia::render('website/Contact', $this->content->contact($this->preview($request, 'pages.update')));
    }

    private function preview(Request $request, string $permission): bool
    {
        return $request->boolean('preview') && (bool) $request->user()?->can($permission);
    }
}
