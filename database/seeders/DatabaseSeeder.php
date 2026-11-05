<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Faq;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Services\ContentPublisher;
use App\Services\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        if (app()->environment('testing')) {
            return;
        }

        $admin = $this->admin();
        $this->settings();
        $technologies = $this->technologies();
        $this->services($admin, $technologies);
        $this->projects($admin, $technologies);
        $this->articles($admin);
        $this->faqs($admin);
        $this->pages($admin);
        $this->navigation();
        app(SettingsRepository::class)->forget();
    }

    private function admin(): User
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@racode.om');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || $password === '') {
            if (! app()->environment('local')) {
                $this->command?->warn('Set ADMIN_EMAIL and ADMIN_PASSWORD before seeding a super admin.');

                return User::query()->firstOrFail();
            }

            $password = Str::password(20);
            $this->command?->warn("Local super admin {$email}");
            $this->command?->warn("Password: {$password}");
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'RACODE Admin',
                'password' => $password,
                'locale' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $user->syncRoles(['super-admin']);

        return $user;
    }

    private function settings(): void
    {
        $groups = [
            'brand' => config('racode.colors'),
            'company' => ['legal_name' => 'RACODE'],
            'contact' => [
                'email' => 'hello@racode.om',
                'phone' => '',
                'notify_email' => '',
                'address' => ['ar' => 'سلطنة عُمان', 'en' => 'Sultanate of Oman'],
            ],
            'social' => ['linkedin' => null, 'instagram' => null, 'x' => null, 'github' => null, 'youtube' => null],
            'footer' => [
                'ar' => 'راكود علامة تقنية عُمانية قيد التطوير. نصمّم ونبني منتجات رقمية بهدوء ودقة.',
                'en' => 'RACODE is an Omani technology studio in development. We design and build digital products with care and precision.',
            ],
            'seo' => [
                'title' => ['ar' => 'راكود', 'en' => 'RACODE'],
                'description' => [
                    'ar' => 'أفكار طموحة. حلول بلا حدود. هندسة برمجية وتصميم من سلطنة عُمان.',
                    'en' => 'Ambitious ideas. Limitless solutions. Software engineering and design from the Sultanate of Oman.',
                ],
            ],
            'maintenance' => ['enabled' => false],
            'analytics' => ['measurement_id' => '', 'plausible_domain' => ''],
            'logo' => ['media_id' => null],
            'favicon' => ['media_id' => null],
        ];

        foreach ($groups as $key => $value) {
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * @return array<string, Technology>
     */
    private function technologies(): array
    {
        $items = [
            ['react', 'React', 'واجهة تفاعلية للمواقع والمنتجات.'],
            ['laravel', 'Laravel', 'إطار عمل للأنظمة وواجهات الإدارة.'],
            ['flutter', 'Flutter', 'تطبيقات جوال بهيئة واحدة.'],
            ['mysql', 'MySQL', 'قاعدة بيانات علائقية للمحتوى والعمليات.'],
            ['php', 'PHP', 'لغة الخادم التي تُشغّل راكود.'],
            ['javascript', 'JavaScript', 'سلوك الواجهة والتكاملات.'],
            ['typescript', 'TypeScript', 'عقود أوضح بين أجزاء المنتج.'],
            ['python', 'Python', 'أتمتة وتجارب ذكاء اصطناعي عند الحاجة.'],
            ['docker', 'Docker', 'بيئات تشغيل قابلة للتكرار.'],
            ['firebase', 'Firebase', 'خدمات جوال وإشعارات عند ملاءمتها.'],
            ['rest-apis', 'REST APIs', 'تكامل واضح بين الأنظمة.'],
            ['git', 'Git', 'تاريخ التغيير والمراجعة.'],
        ];

        $map = [];

        foreach ($items as $index => [$slug, $name, $ar]) {
            $map[$slug] = Technology::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => ['ar' => $ar, 'en' => $name.' as used in RACODE product work.'],
                'sort_order' => $index,
                'is_visible' => true,
            ]);
        }

        return $map;
    }

    /**
     * @param  array<string, Technology>  $technologies
     */
    private function services(User $admin, array $technologies): void
    {
        $rows = [
            ['web-development', 'تطوير المواقع', 'Web development', 'مواقع ومنتجات ويب سريعة، ثنائية اللغة، وقابلة للنمو.', 'Fast bilingual websites and web products built to grow.', ['laravel', 'react', 'typescript']],
            ['mobile-applications', 'تطوير تطبيقات الجوال', 'Mobile application development', 'تطبيقات واضحة على الجوال، من التدفق الأول حتى الإصدار.', 'Clear mobile applications, from the first flow through release.', ['flutter', 'firebase']],
            ['custom-software', 'الأنظمة البرمجية المخصصة', 'Custom software systems', 'أنظمة داخلية تُبنى حول طريقة عمل الفريق، لا حول قالب جاهز.', 'Internal systems shaped around how a team actually works.', ['laravel', 'mysql', 'php']],
            ['ecommerce', 'حلول التجارة الإلكترونية', 'E-commerce solutions', 'متاجر ثنائية اللغة مع كتالوج وطلب ودفع يمكن التحقق منه.', 'Bilingual stores with a catalog, checkout, and payment that can be verified.', ['laravel', 'mysql', 'react']],
            ['ai-automation', 'دمج الذكاء الاصطناعي والأتمتة', 'AI integration and automation', 'أتمتة مفيدة ومراجَعة، حيث يساعد النموذج العمل بدل أن يستبدله.', 'Useful, reviewed automation where a model assists the work.', ['python', 'rest-apis']],
            ['api-integrations', 'تكامل واجهات البرمجة', 'API integrations', 'ربط الأنظمة الحالية بحدود واضحة ومراقبة للأخطاء.', 'Connecting existing systems with clear boundaries and error handling.', ['rest-apis', 'laravel']],
            ['software-testing', 'اختبار البرمجيات وضمان الجودة', 'Software testing and QA', 'نفحص التفاصيل قبل أن يصل إليها المستخدم.', 'We test the details before a user has to.', ['git']],
            ['deployment-support', 'الإطلاق والدعم التقني', 'Deployment and technical support', 'إطلاق محسوب، ثم متابعة لما يحدث بعد أن يعمل المنتج.', 'A measured launch, then attention to what happens after the product is live.', ['docker', 'git']],
        ];

        foreach ($rows as $index => [$slug, $ar, $en, $arSummary, $enSummary, $tech]) {
            $service = Service::query()->updateOrCreate(['slug' => $slug], [
                'name' => ['ar' => $ar, 'en' => $en],
                'summary' => ['ar' => $arSummary, 'en' => $enSummary],
                'description' => [[
                    'type' => 'paragraph',
                    'text' => ['ar' => $arSummary.' نبدأ بفهم العملية، ثم نثبت النطاق، ثم نبني على مراحل يمكن مراجعتها.', 'en' => $enSummary.' We start by understanding the operation, fix the scope, then build in stages that can be reviewed.'],
                ]],
                'features' => [
                    ['ar' => 'نطاق مكتوب قبل البناء', 'en' => 'Written scope before building'],
                    ['ar' => 'عربية وإنجليزية من البداية', 'en' => 'Arabic and English from the start'],
                    ['ar' => 'تسليم يمكن تشغيله ومراجعته', 'en' => 'A build that can be run and reviewed'],
                ],
                'seo' => $this->seo($ar, $en, $arSummary, $enSummary),
                'status' => 'draft',
                'sort_order' => $index,
            ]);
            $service->technologies()->sync(collect($tech)->map(fn ($key) => $technologies[$key]->id)->all());
            app(ContentPublisher::class)->publish($service->refresh(), $admin);
        }
    }

    /**
     * @param  array<string, Technology>  $technologies
     */
    private function projects(User $admin, array $technologies): void
    {
        $categories = [
            'web' => ProjectCategory::query()->updateOrCreate(['slug' => 'web'], ['name' => ['ar' => 'ويب', 'en' => 'Web'], 'sort_order' => 0]),
            'mobile' => ProjectCategory::query()->updateOrCreate(['slug' => 'mobile'], ['name' => ['ar' => 'جوال', 'en' => 'Mobile'], 'sort_order' => 1]),
            'systems' => ProjectCategory::query()->updateOrCreate(['slug' => 'systems'], ['name' => ['ar' => 'أنظمة', 'en' => 'Systems'], 'sort_order' => 2]),
        ];

        $rows = [
            [
                'operations-desk', 'systems', 'مكتب العمليات', 'Operations desk',
                'عرض توضيحي لنظام داخلي يتابع الطلبات دون ادعاء أنه مشروع عميل.',
                'A demonstration of an internal desk that tracks requests. It is not a client project.',
                ['laravel', 'mysql', 'react'],
            ],
            [
                'bilingual-storefront', 'web', 'واجهة متجر ثنائية اللغة', 'Bilingual storefront',
                'عرض توضيحي لكيفية ترتيب الكتالوج والدفع بين العربية والإنجليزية.',
                'A demonstration of how a catalog and checkout can move between Arabic and English.',
                ['react', 'laravel', 'typescript'],
            ],
            [
                'field-visit-flow', 'mobile', 'تدفق زيارة ميدانية', 'Field visit flow',
                'عرض توضيحي لمسار جوال قصير: زيارة، ملاحظة، ثم مزامنة.',
                'A demonstration of a short mobile path: visit, note, then sync.',
                ['flutter', 'firebase'],
            ],
        ];

        foreach ($rows as $index => [$slug, $category, $ar, $en, $arSummary, $enSummary, $tech]) {
            $project = Project::query()->updateOrCreate(['slug' => $slug], [
                'project_category_id' => $categories[$category]->id,
                'title' => ['ar' => $ar, 'en' => $en],
                'summary' => ['ar' => $arSummary, 'en' => $enSummary],
                'description' => [[
                    'type' => 'paragraph',
                    'text' => [
                        'ar' => 'هذا سيناريو توضيحي من راكود لعرض شكل دراسة الحالة. لا يمثل عميلًا أو نتيجة مؤكدة.',
                        'en' => 'This is a RACODE demonstration of how a case study is structured. It does not represent a client or a verified result.',
                    ],
                ]],
                'challenges' => [[
                    'title' => ['ar' => 'السيناريو', 'en' => 'The scenario'],
                    'body' => ['ar' => 'العمل موزع بين رسائل وملفات، ويصعب معرفة ما اكتمل.', 'en' => 'The work is split across messages and files, so completion is hard to see.'],
                ]],
                'solutions' => [[
                    'title' => ['ar' => 'الاتجاه المقترح', 'en' => 'The proposed direction'],
                    'body' => ['ar' => 'مسار واحد للحالة، مع لغة واضحة وحالات ظاهرة.', 'en' => 'One path for the case, with plain language and visible states.'],
                ]],
                'features' => [
                    ['ar' => 'حالات واضحة', 'en' => 'Visible states'],
                    ['ar' => 'عربية وإنجليزية', 'en' => 'Arabic and English'],
                ],
                'results' => [],
                'results_verified' => false,
                'is_demonstration' => true,
                'is_featured' => $index === 0,
                'seo' => $this->seo($ar, $en, $arSummary, $enSummary),
                'status' => 'draft',
                'sort_order' => $index,
            ]);
            $project->technologies()->sync(collect($tech)->map(fn ($key) => $technologies[$key]->id)->all());
            app(ContentPublisher::class)->publish($project->refresh(), $admin);
        }
    }

    private function articles(User $admin): void
    {
        $category = ArticleCategory::query()->updateOrCreate(['slug' => 'engineering'], [
            'name' => ['ar' => 'هندسة', 'en' => 'Engineering'],
            'sort_order' => 0,
        ]);
        $tag = Tag::query()->updateOrCreate(['slug' => 'practice'], [
            'name' => ['ar' => 'ممارسة', 'en' => 'Practice'],
        ]);

        $articles = [
            [
                'bilingual-product-structure',
                'كيف يُبنى منتج ثنائي اللغة',
                'How a bilingual product is structured',
                'اللغة ليست طبقة أخيرة. هي قرار في المسار والبيانات والواجهة.',
                'Language is not a final coat. It is a decision in routing, data, and interface.',
            ],
            [
                'testing-before-release',
                'ماذا يعني الاختبار قبل الإطلاق',
                'What testing means before release',
                'الاختبار هنا فحص للمسارات الحساسة، لا رقمًا دعائيًا.',
                'Testing here means checking the sensitive paths, not a promotional number.',
            ],
        ];

        foreach ($articles as $index => [$slug, $ar, $en, $arExcerpt, $enExcerpt]) {
            $article = Article::query()->updateOrCreate(['slug' => $slug], [
                'article_category_id' => $category->id,
                'author_id' => $admin->id,
                'title' => ['ar' => $ar, 'en' => $en],
                'excerpt' => ['ar' => $arExcerpt, 'en' => $enExcerpt],
                'body' => [
                    ['type' => 'paragraph', 'text' => ['ar' => $arExcerpt, 'en' => $enExcerpt]],
                    ['type' => 'heading', 'level' => 2, 'text' => ['ar' => 'ما نثبّته مبكرًا', 'en' => 'What we settle early']],
                    ['type' => 'list', 'text' => ['ar' => '', 'en' => ''], 'items' => [
                        ['ar' => 'اتجاه القراءة والمسافات', 'en' => 'Reading direction and spacing'],
                        ['ar' => 'المحتوى الذي يملكه الفريق', 'en' => 'Content the team can edit'],
                        ['ar' => 'ما يجب أن يفشل بأدب', 'en' => 'What should fail politely'],
                    ]],
                ],
                'seo' => $this->seo($ar, $en, $arExcerpt, $enExcerpt),
                'is_featured' => $index === 0,
                'status' => 'draft',
            ]);
            $article->tags()->sync([$tag->id]);
            app(ContentPublisher::class)->publish($article->refresh(), $admin);
        }
    }

    private function faqs(User $admin): void
    {
        Faq::query()->delete();
        $rows = [
            ['هل راكود شركة عاملة منذ سنوات؟', 'Is RACODE a long-established company?', 'راكود علامة تقنية عُمانية قيد التطوير. لا نعرض أرقام عملاء أو جوائز لم تحدث.', 'RACODE is an Omani technology studio in development. We do not display client counts or awards that have not happened.', 'home'],
            ['ما الذي يمكن البدء به؟', 'What can we start with?', 'يمكن البدء بموقع، أو تطبيق، أو نظام مخصص، أو تكامل، بعد فهم العملية والنطاق.', 'We can start with a website, an application, a custom system, or an integration, after the operation and scope are understood.', 'home'],
            ['هل تعملون بالعربية والإنجليزية؟', 'Do you work in Arabic and English?', 'نعم. الواجهة والمحتوى يُبنيان للغتين، مع اتجاه قراءة مناسب لكل لغة.', 'Yes. The interface and content are built for both languages, with a reading direction that fits each one.', 'home'],
            ['هل تعرضون أعمال عملاء حقيقية؟', 'Do you show real client work?', 'الأعمال المعروضة حاليًا توضيحية وموسومة بذلك. لا نخترع لقطات أو أسماء عملاء.', 'The work shown now is illustrative and labeled as such. We do not invent screenshots or client names.', 'home'],
            ['كيف يصل طلب التواصل؟', 'How does a contact request arrive?', 'يُحفظ في قاعدة البيانات ويظهر في لوحة الإدارة. يصل البريد فقط إذا كان الإرسال مهيأً.', 'It is stored in the database and appears in the admin. Email is sent only when mail delivery is configured.', 'general'],
            ['من يستطيع تعديل الموقع؟', 'Who can edit the site?', 'المحتوى يُدار من لوحة الإدارة حسب الدور: مدير أعلى، مدير محتوى، محرر، أو مشاهد.', 'Content is managed in the admin according to role: super admin, content manager, editor, or viewer.', 'general'],
        ];

        foreach ($rows as $index => [$qAr, $qEn, $aAr, $aEn, $placement]) {
            $faq = Faq::query()->create([
                'question' => ['ar' => $qAr, 'en' => $qEn],
                'answer' => ['ar' => $aAr, 'en' => $aEn],
                'placement' => $placement,
                'sort_order' => $index,
                'is_visible' => true,
                'status' => 'draft',
            ]);
            app(ContentPublisher::class)->publish($faq, $admin);
        }
    }

    private function pages(User $admin): void
    {
        $home = Page::query()->updateOrCreate(['slug' => 'home'], [
            'template' => 'homepage',
            'is_system' => true,
            'status' => 'draft',
            'sort_order' => 0,
            'title' => ['ar' => 'راكود', 'en' => 'RACODE'],
            'excerpt' => ['ar' => 'أفكار طموحة. حلول بلا حدود.', 'en' => 'Ambitious Ideas. Limitless Solutions.'],
            'body' => [],
            'seo' => $this->seo('راكود', 'RACODE', 'أفكار طموحة. حلول بلا حدود.', 'Ambitious ideas. Limitless solutions.'),
        ]);
        $home->sections()->delete();
        $this->homeSections($home);
        app(ContentPublisher::class)->publish($home->refresh(), $admin);

        $documents = [
            ['about', 'من نحن', 'About us', 'لا نكتب الكود فقط. نبني ما وراءه.', 'We do not only write code. We build what lies beyond it.', 'راكود علامة تقنية عُمانية قيد التطوير، تهدف إلى تقديم حلول برمجية مخصصة تجمع بين فهم الأعمال، والتصميم، والهندسة، والاختبار، لتقديم منتجات رقمية عملية وقابلة للتطور.', 'RACODE is an Omani technology studio in development. It aims to deliver custom software that joins business understanding, design, engineering, and testing, so digital products stay practical and able to evolve.'],
            ['privacy', 'سياسة الخصوصية', 'Privacy policy', 'كيف نتعامل مع الرسائل التي تصلنا.', 'How we handle messages sent to us.', 'نحفظ بيانات نموذج التواصل لمعالجة الطلب: الاسم، والبريد، والهاتف، والشركة إن وُجدت، ونوع المشروع، والرسالة. لا نبيع هذه البيانات. يمكن طلب مراجعتها عبر بريد التواصل الظاهر في الموقع بعد تحديثه من لوحة الإدارة.', 'We store contact-form details to handle the request: name, email, phone, company when provided, project type, and message. We do not sell this data. A review can be requested through the contact email shown on the site once it is set in the admin.'],
            ['terms', 'الشروط والأحكام', 'Terms and conditions', 'استخدام هذا الموقع.', 'Using this website.', 'المحتوى التعريفي على هذا الموقع يصف خدمات راكود قيد التطوير. العروض التوضيحية ليست أعمال عملاء. أي اتفاق لتنفيذ مشروع يُكتب بشكل مستقل ولا يُستنتج من صفحات الموقع وحدها.', 'The introductory content on this website describes RACODE services while the studio is in development. Demonstrations are not client work. An agreement to deliver a project is written separately and is not implied by these pages alone.'],
        ];

        foreach ($documents as $index => [$slug, $ar, $en, $arExcerpt, $enExcerpt, $arBody, $enBody]) {
            $page = Page::query()->updateOrCreate(['slug' => $slug], [
                'template' => 'document',
                'is_system' => true,
                'status' => 'draft',
                'sort_order' => $index + 1,
                'title' => ['ar' => $ar, 'en' => $en],
                'excerpt' => ['ar' => $arExcerpt, 'en' => $enExcerpt],
                'body' => [['type' => 'paragraph', 'text' => ['ar' => $arBody, 'en' => $enBody]]],
                'seo' => $this->seo($ar, $en, $arExcerpt, $enExcerpt),
            ]);
            app(ContentPublisher::class)->publish($page, $admin);
        }

        $indexes = [
            ['services', 'services_index', 'الخدمات', 'Services', 'ثماني مسارات للعمل، من الموقع حتى الدعم بعد الإطلاق.', 'Eight ways of working, from a website through support after launch.'],
            ['work', 'work_index', 'أعمال مختارة', 'Selected work', 'دراسات شكلية توضيحية. ليست مشاريع عملاء.', 'Shaped demonstrations. Not client projects.'],
            ['technologies', 'technologies_index', 'التقنيات', 'Technologies', 'الأدوات التي نستخدمها عندما تخدم المنتج.', 'Tools we use when they serve the product.'],
            ['insights', 'insights_index', 'ملاحظات', 'Insights', 'كتابة قصيرة عن طريقة البناء.', 'Short writing about how we build.'],
            ['contact', 'contact', 'ابدأ مشروعك', 'Start a project', 'أرسل الفكرة كما هي. نعود إليك بعد قراءتها.', 'Send the idea as it is. We read it before we reply.'],
        ];

        foreach ($indexes as $index => [$slug, $template, $ar, $en, $arExcerpt, $enExcerpt]) {
            $page = Page::query()->updateOrCreate(['slug' => $slug], [
                'template' => $template,
                'is_system' => true,
                'status' => 'draft',
                'sort_order' => $index + 10,
                'title' => ['ar' => $ar, 'en' => $en],
                'excerpt' => ['ar' => $arExcerpt, 'en' => $enExcerpt],
                'body' => [],
                'seo' => $this->seo($ar, $en, $arExcerpt, $enExcerpt),
            ]);
            app(ContentPublisher::class)->publish($page, $admin);
        }
    }

    private function homeSections(Page $page): void
    {
        $sections = [
            ['hero', 'cinematic', 'dark', 'أفكار طموحة. حلول بلا حدود.', 'Ambitious Ideas. Limitless Solutions.', 'من أول سطر برمجي إلى إطلاق منتجك، نطوّر حلولًا رقمية تجمع بين التصميم الذكي والهندسة البرمجية الدقيقة.', 'From the first line of code to the launch of your product, we develop digital solutions that join thoughtful design with precise software engineering.', [], 'ابدأ مشروعك', 'Start a project', '/contact', 'استكشف خدماتنا', 'Explore our services', '/services'],
            ['about', 'editorial', 'light', 'لا نكتب الكود فقط. نبني ما وراءه.', 'We do not only write code. We build what lies beyond it.', 'راكود علامة تقنية عُمانية قيد التطوير، تهدف إلى تقديم حلول برمجية مخصصة تجمع بين فهم الأعمال، والتصميم، والهندسة، والاختبار، لتقديم منتجات رقمية عملية وقابلة للتطور.', 'RACODE is an Omani technology studio in development. It aims to deliver custom software that joins business understanding, design, engineering, and testing, so products stay practical and able to evolve.', [], 'عن راكود', 'About RACODE', '/about', '', '', null],
            ['services', 'index', 'dark', 'ماذا نبني', 'What we build', 'مسارات واضحة بدل شبكة بطاقات متشابهة.', 'Clear paths, rather than a grid of identical cards.', [], 'كل الخدمات', 'All services', '/services', '', '', null],
            ['selected_work', 'showcase', 'light', 'أعمال مختارة', 'Selected work', 'معروضات توضيحية لشكل العمل. ليست مشاريع منجزة لعملاء.', 'Demonstrations of the shape of the work. Not completed client projects.', [], 'معرض الأعمال', 'Work archive', '/work', '', '', null],
            ['process', 'rail', 'dark', 'طريقتنا', 'Our process', 'تسلسل واحد، من الفهم حتى ما بعد الإطلاق.', 'One sequence, from understanding through what happens after launch.', [
                ['title' => ['ar' => 'الاستكشاف والتحليل', 'en' => 'Discovery and analysis'], 'body' => ['ar' => 'نفهم العملية والقيود قبل أن نعد بحل.', 'en' => 'We understand the operation and the constraints before promising a solution.']],
                ['title' => ['ar' => 'التخطيط والتصميم', 'en' => 'Planning and design'], 'body' => ['ar' => 'نثبت المسار واللغة والشكل الذي سيُستخدم فعلًا.', 'en' => 'We settle the path, the language, and the form people will actually use.']],
                ['title' => ['ar' => 'التطوير', 'en' => 'Development'], 'body' => ['ar' => 'نبني على مراحل يمكن تجربتها، لا دفعة واحدة مبهمة.', 'en' => 'We build in stages that can be tried, not one opaque delivery.']],
                ['title' => ['ar' => 'الاختبار وضمان الجودة', 'en' => 'Testing and quality assurance'], 'body' => ['ar' => 'نفحص التفاصيل قبل أن يفحصها المستخدم.', 'en' => 'We test the details before a user has to.']],
                ['title' => ['ar' => 'الإطلاق والتحسين', 'en' => 'Launch and improvement'], 'body' => ['ar' => 'الإطلاق بداية الملاحظة، لا نهاية الاهتمام.', 'en' => 'Launch is the start of observation, not the end of attention.']],
            ], '', '', null, '', '', null],
            ['ai_innovation', 'diagram', 'light', 'ذكاء اصطناعي يعمل لصالح أعمالك.', 'Artificial intelligence that works for the business.', 'نربطه بخطوة مفهومة: إدخال، مراجعة، ثم أثر يمكن رؤيته.', 'We connect it to a step a person can understand: input, review, then an effect that can be seen.', [
                ['title' => ['ar' => 'يفهم السياق', 'en' => 'Reads the context'], 'body' => ['ar' => 'المادة العملية، لا شعار عام.', 'en' => 'The operational material, not a generic slogan.']],
                ['title' => ['ar' => 'يقترح', 'en' => 'Proposes'], 'body' => ['ar' => 'مسودة أو تصنيف يمكن رفضه.', 'en' => 'A draft or a classification that can be rejected.']],
                ['title' => ['ar' => 'يُراجع', 'en' => 'Is reviewed'], 'body' => ['ar' => 'يبقى القرار مع الفريق.', 'en' => 'The decision stays with the team.']],
                ['title' => ['ar' => 'يُسجَّل', 'en' => 'Is recorded'], 'body' => ['ar' => 'الأثر يبقى قابلًا للتتبع.', 'en' => 'The effect stays traceable.']],
            ], '', '', null, '', '', null],
            ['engineering', 'statement', 'dark', 'نختبر التفاصيل قبل أن يختبرها المستخدم.', 'We test the details before a user has to.', 'الجودة هنا ليست شارة. هي مراجعة للمسارات، واللغات، والحالات التي ينكسر فيها المنتج.', 'Quality here is not a badge. It is a review of the paths, the languages, and the states where a product breaks.', [], '', '', null, '', '', null],
            ['technologies', 'marquee', 'light', 'التقنيات', 'Technologies', 'نختار الأداة لأن المنتج يحتاجها.', 'A tool is chosen because the product needs it.', [], 'كل التقنيات', 'All technologies', '/technologies', '', '', null],
            ['faq', 'accordion', 'dark', 'أسئلة', 'Questions', 'إجابات مباشرة عن وضع راكود الحالي.', 'Direct answers about where RACODE is now.', [], '', '', null, '', '', null],
            ['contact_cta', 'banner', 'light', 'لديك فكرة؟ لنحوّلها إلى شيء استثنائي.', 'Have an idea? Let us turn it into something exceptional.', 'ابدأ برسالة قصيرة. الباقي نرتبه معًا.', 'Start with a short message. We will arrange the rest together.', [], 'ابدأ مشروعك', 'Start a project', '/contact', '', '', null],
        ];

        foreach ($sections as $index => [$type, $layout, $theme, $arH, $enH, $arD, $enD, $items, $arP, $enP, $pUrl, $arS, $enS, $sUrl]) {
            $page->sections()->create([
                'type' => $type,
                'layout' => $layout,
                'theme' => $theme,
                'is_visible' => true,
                'sort_order' => $index,
                'content' => [
                    'heading' => ['ar' => $arH, 'en' => $enH],
                    'description' => ['ar' => $arD, 'en' => $enD],
                    'primary_button' => ['label' => ['ar' => $arP, 'en' => $enP], 'url' => $pUrl],
                    'secondary_button' => ['label' => ['ar' => $arS, 'en' => $enS], 'url' => $sUrl],
                    'image_id' => null,
                    'items' => $items,
                ],
            ]);
        }
    }

    private function navigation(): void
    {
        NavigationItem::query()->delete();
        $header = [
            ['/', 'الرئيسية', 'Home'],
            ['/about', 'عن راكود', 'About'],
            ['/services', 'الخدمات', 'Services'],
            ['/work', 'الأعمال', 'Work'],
            ['/technologies', 'التقنيات', 'Technologies'],
            ['/insights', 'ملاحظات', 'Insights'],
            ['/contact', 'تواصل', 'Contact'],
        ];
        $footer = [
            ['/services', 'الخدمات', 'Services'],
            ['/work', 'الأعمال', 'Work'],
            ['/contact', 'تواصل', 'Contact'],
            ['/privacy', 'الخصوصية', 'Privacy'],
            ['/terms', 'الشروط', 'Terms'],
        ];

        foreach (['header' => $header, 'footer' => $footer] as $location => $items) {
            foreach ($items as $index => [$path, $ar, $en]) {
                NavigationItem::query()->create([
                    'location' => $location,
                    'label' => ['ar' => $ar, 'en' => $en],
                    'link_type' => 'path',
                    'path' => $path,
                    'sort_order' => $index,
                    'is_visible' => true,
                    'open_in_new_tab' => false,
                ]);
            }
        }
    }

    /**
     * @return array{title: array{ar: string, en: string}, description: array{ar: string, en: string}}
     */
    private function seo(string $ar, string $en, string $arDescription, string $enDescription): array
    {
        return [
            'title' => ['ar' => $ar, 'en' => $en],
            'description' => ['ar' => $arDescription, 'en' => $enDescription],
        ];
    }
}
