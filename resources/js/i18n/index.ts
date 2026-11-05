export type Copy = {
    skip: string;
    menu: string;
    close: string;
    language: string;
    appearance: string;
    lightMode: string;
    darkMode: string;
    useLight: string;
    useDark: string;
    philosophy: string;
    start: string;
    explore: string;
    demonstration: string;
    read: string;
    scroll: string;
    pause: string;
    play: string;
    all: string;
    emptyHome: string;
    notFoundTitle: string;
    notFoundBody: string;
    forbidden: string;
    server: string;
    maintenance: string;
    backHome: string;
    preview: string;
    exitPreview: string;
    features: string;
    technologies: string;
    challenge: string;
    solution: string;
    results: string;
    noResults: string;
    noArticles: string;
    noProjects: string;
    published: string;
    contact: {
        name: string;
        email: string;
        phone: string;
        company: string;
        type: string;
        message: string;
        consent: string;
        submit: string;
        sending: string;
        honeypot: string;
    };
    types: Record<string, string>;
    admin: {
        dashboard: string;
        pages: string;
        services: string;
        projects: string;
        articles: string;
        faqs: string;
        media: string;
        inquiries: string;
        navigation: string;
        settings: string;
        users: string;
        technologies: string;
        activity: string;
        save: string;
        publish: string;
        schedule: string;
        delete: string;
        confirmDelete: string;
        create: string;
        edit: string;
        search: string;
        draft: string;
        published: string;
        scheduled: string;
        visible: string;
        hidden: string;
        logout: string;
        login: string;
        email: string;
        password: string;
        remember: string;
        signIn: string;
        role: string;
        active: string;
        name: string;
        slug: string;
        title: string;
        excerpt: string;
        summary: string;
        body: string;
        seoTitle: string;
        seoDescription: string;
        cover: string;
        none: string;
        add: string;
        layout: string;
        theme: string;
        light: string;
        dark: string;
        restore: string;
        preview: string;
        featured: string;
        demonstration: string;
        verified: string;
        category: string;
        tags: string;
        notes: string;
        assign: string;
        export: string;
        replace: string;
        alt: string;
        upload: string;
        empty: string;
        unpublished: string;
        passwordConfirm: string;
        heading: string;
        description: string;
        button: string;
        url: string;
        items: string;
        question: string;
        answer: string;
        placement: string;
        status: string;
        filter: string;
        message: string;
        phone: string;
        company: string;
        back: string;
        newCount: string;
        reorder: string;
        up: string;
        down: string;
        arabic: string;
        english: string;
        legalName: string;
        notifyEmail: string;
        address: string;
        maintenance: string;
        analytics: string;
        footer: string;
        social: string;
        colors: string;
        logo: string;
        favicon: string;
        challenges: string;
        solutions: string;
        features: string;
        results: string;
        gallery: string;
        isVisible: string;
    };
};

export const en: Copy = {
    skip: 'Skip to content',
    menu: 'Menu',
    close: 'Close',
    language: 'Language',
    appearance: 'Appearance',
    lightMode: 'Light',
    darkMode: 'Dark',
    useLight: 'Switch to light mode',
    useDark: 'Switch to dark mode',
    philosophy: 'Beyond the Code',
    start: 'Start a project',
    explore: 'Explore services',
    demonstration: 'Demonstration',
    read: 'Read',
    scroll: 'Scroll',
    pause: 'Pause motion',
    play: 'Play motion',
    all: 'All',
    emptyHome: 'The homepage has not been published yet.',
    notFoundTitle: 'This page is not on the map.',
    notFoundBody: 'The address may have changed, or the page is still a draft.',
    forbidden: 'You do not have permission to open this.',
    server: 'Something failed on the server. The detail stays in the log.',
    maintenance: 'RACODE is briefly unavailable while the site is being updated.',
    backHome: 'Back to the homepage',
    preview: 'Previewing a draft',
    exitPreview: 'Leave preview',
    features: 'Features',
    technologies: 'Technologies',
    challenge: 'Scenario',
    solution: 'Direction',
    results: 'Verified results',
    noResults: 'No verified results are recorded for this demonstration.',
    noArticles: 'No published notes yet.',
    noProjects: 'No published demonstrations yet.',
    published: 'Published',
    contact: {
        name: 'Name',
        email: 'Email',
        phone: 'Phone',
        company: 'Company',
        type: 'Project type',
        message: 'Message',
        consent: 'I agree to RACODE storing this message so the request can be handled.',
        submit: 'Send the message',
        sending: 'Sending',
        honeypot: 'Leave this field empty',
    },
    types: {
        web: 'Web',
        mobile: 'Mobile',
        custom_software: 'Custom software',
        ecommerce: 'E-commerce',
        ai: 'AI and automation',
        api: 'API integration',
        qa: 'Testing and QA',
        support: 'Deployment and support',
        other: 'Something else',
    },
    admin: {
        dashboard: 'Overview',
        pages: 'Pages',
        services: 'Services',
        projects: 'Projects',
        articles: 'Notes',
        faqs: 'Questions',
        media: 'Media',
        inquiries: 'Inquiries',
        navigation: 'Navigation',
        settings: 'Settings',
        users: 'Users',
        technologies: 'Technologies',
        activity: 'Activity',
        save: 'Save draft',
        publish: 'Publish',
        schedule: 'Schedule',
        delete: 'Delete',
        confirmDelete: 'Confirm delete',
        create: 'Create',
        edit: 'Edit',
        search: 'Search',
        draft: 'Draft',
        published: 'Published',
        scheduled: 'Scheduled',
        visible: 'Visible',
        hidden: 'Hidden',
        logout: 'Sign out',
        login: 'Sign in',
        email: 'Email',
        password: 'Password',
        remember: 'Remember this browser',
        signIn: 'Enter the studio',
        role: 'Role',
        active: 'Active',
        name: 'Name',
        slug: 'Slug',
        title: 'Title',
        excerpt: 'Intro',
        summary: 'Summary',
        body: 'Body',
        seoTitle: 'SEO title',
        seoDescription: 'SEO description',
        cover: 'Cover',
        none: 'None',
        add: 'Add',
        layout: 'Layout',
        theme: 'Tone',
        light: 'Light',
        dark: 'Dark',
        restore: 'Restore',
        preview: 'Preview',
        featured: 'Featured',
        demonstration: 'Demonstration',
        verified: 'Results are verified',
        category: 'Category',
        tags: 'Tags',
        notes: 'Internal notes',
        assign: 'Assign',
        export: 'Export',
        replace: 'Replace file',
        alt: 'Alt text',
        upload: 'Upload',
        empty: 'Nothing here yet.',
        unpublished: 'Unpublished changes',
        passwordConfirm: 'Confirm password',
        heading: 'Heading',
        description: 'Description',
        button: 'Button',
        url: 'Link',
        items: 'Items',
        question: 'Question',
        answer: 'Answer',
        placement: 'Placement',
        status: 'Status',
        filter: 'Filter',
        message: 'Message',
        phone: 'Phone',
        company: 'Company',
        back: 'Back',
        newCount: 'New inquiries',
        reorder: 'Order',
        up: 'Move up',
        down: 'Move down',
        arabic: 'Arabic',
        english: 'English',
        legalName: 'Legal name',
        notifyEmail: 'Notification email',
        address: 'Address',
        maintenance: 'Maintenance mode',
        analytics: 'Analytics',
        footer: 'Footer text',
        social: 'Social links',
        colors: 'Brand colors',
        logo: 'Logo',
        favicon: 'Favicon',
        challenges: 'Challenges',
        solutions: 'Solutions',
        features: 'Features',
        results: 'Results',
        gallery: 'Gallery',
        isVisible: 'Visible on the site',
    },
};

export const ar: Copy = {
    skip: 'تجاوز إلى المحتوى',
    menu: 'القائمة',
    close: 'إغلاق',
    language: 'اللغة',
    appearance: 'المظهر',
    lightMode: 'فاتح',
    darkMode: 'داكن',
    useLight: 'التبديل إلى الوضع الفاتح',
    useDark: 'التبديل إلى الوضع الداكن',
    philosophy: 'Beyond the Code',
    start: 'ابدأ مشروعك',
    explore: 'استكشف خدماتنا',
    demonstration: 'عرض توضيحي',
    read: 'اقرأ',
    scroll: 'مرر للأسفل',
    pause: 'إيقاف الحركة',
    play: 'تشغيل الحركة',
    all: 'الكل',
    emptyHome: 'الصفحة الرئيسية لم تُنشر بعد.',
    notFoundTitle: 'هذه الصفحة ليست على الخريطة.',
    notFoundBody: 'قد يكون العنوان قد تغيّر، أو أن الصفحة ما زالت مسودة.',
    forbidden: 'ليست لديك صلاحية لفتح هذا.',
    server: 'حدث خلل في الخادم. التفاصيل تبقى في السجل.',
    maintenance: 'راكود غير متاحة لحظات بينما يُحدَّث الموقع.',
    backHome: 'العودة إلى الرئيسية',
    preview: 'معاينة مسودة',
    exitPreview: 'مغادرة المعاينة',
    features: 'الملامح',
    technologies: 'التقنيات',
    challenge: 'السيناريو',
    solution: 'الاتجاه',
    results: 'نتائج موثّقة',
    noResults: 'لا توجد نتائج موثّقة لهذا العرض التوضيحي.',
    noArticles: 'لا توجد ملاحظات منشورة بعد.',
    noProjects: 'لا توجد عروض منشورة بعد.',
    published: 'تاريخ النشر',
    contact: {
        name: 'الاسم',
        email: 'البريد الإلكتروني',
        phone: 'الهاتف',
        company: 'الشركة',
        type: 'نوع المشروع',
        message: 'الرسالة',
        consent: 'أوافق على حفظ راكود لهذه الرسالة لمعالجة الطلب.',
        submit: 'أرسل الرسالة',
        sending: 'جارٍ الإرسال',
        honeypot: 'اترك هذا الحقل فارغًا',
    },
    types: {
        web: 'ويب',
        mobile: 'جوال',
        custom_software: 'برمجيات مخصصة',
        ecommerce: 'تجارة إلكترونية',
        ai: 'ذكاء اصطناعي وأتمتة',
        api: 'تكامل واجهات',
        qa: 'اختبار وجودة',
        support: 'إطلاق ودعم',
        other: 'شيء آخر',
    },
    admin: {
        dashboard: 'نظرة عامة',
        pages: 'الصفحات',
        services: 'الخدمات',
        projects: 'المشاريع',
        articles: 'الملاحظات',
        faqs: 'الأسئلة',
        media: 'الوسائط',
        inquiries: 'الرسائل',
        navigation: 'التصفح',
        settings: 'الإعدادات',
        users: 'المستخدمون',
        technologies: 'التقنيات',
        activity: 'النشاط',
        save: 'حفظ المسودة',
        publish: 'نشر',
        schedule: 'جدولة',
        delete: 'حذف',
        confirmDelete: 'تأكيد الحذف',
        create: 'إنشاء',
        edit: 'تحرير',
        search: 'بحث',
        draft: 'مسودة',
        published: 'منشور',
        scheduled: 'مجدول',
        visible: 'ظاهر',
        hidden: 'مخفي',
        logout: 'خروج',
        login: 'دخول',
        email: 'البريد الإلكتروني',
        password: 'كلمة المرور',
        remember: 'تذكّر هذا المتصفح',
        signIn: 'ادخل إلى الاستوديو',
        role: 'الدور',
        active: 'نشط',
        name: 'الاسم',
        slug: 'المعرّف',
        title: 'العنوان',
        excerpt: 'المقدمة',
        summary: 'الملخص',
        body: 'المتن',
        seoTitle: 'عنوان الظهور',
        seoDescription: 'وصف الظهور',
        cover: 'الغلاف',
        none: 'بدون',
        add: 'إضافة',
        layout: 'التخطيط',
        theme: 'النبرة',
        light: 'فاتح',
        dark: 'داكن',
        restore: 'استعادة',
        preview: 'معاينة',
        featured: 'مميز',
        demonstration: 'عرض توضيحي',
        verified: 'النتائج موثّقة',
        category: 'التصنيف',
        tags: 'الوسوم',
        notes: 'ملاحظات داخلية',
        assign: 'إسناد',
        export: 'تصدير',
        replace: 'استبدال الملف',
        alt: 'النص البديل',
        upload: 'رفع',
        empty: 'لا يوجد شيء هنا بعد.',
        unpublished: 'تغييرات غير منشورة',
        passwordConfirm: 'تأكيد كلمة المرور',
        heading: 'العنوان',
        description: 'الوصف',
        button: 'الزر',
        url: 'الرابط',
        items: 'العناصر',
        question: 'السؤال',
        answer: 'الإجابة',
        placement: 'الموضع',
        status: 'الحالة',
        filter: 'تصفية',
        message: 'الرسالة',
        phone: 'الهاتف',
        company: 'الشركة',
        back: 'رجوع',
        newCount: 'رسائل جديدة',
        reorder: 'الترتيب',
        up: 'تحريك لأعلى',
        down: 'تحريك لأسفل',
        arabic: 'العربية',
        english: 'الإنجليزية',
        legalName: 'الاسم النظامي',
        notifyEmail: 'بريد الإشعار',
        address: 'العنوان',
        maintenance: 'وضع الصيانة',
        analytics: 'القياس',
        footer: 'نص التذييل',
        social: 'روابط التواصل',
        colors: 'ألوان العلامة',
        logo: 'الشعار',
        favicon: 'أيقونة المتصفح',
        challenges: 'التحديات',
        solutions: 'الحلول',
        features: 'الملامح',
        results: 'النتائج',
        gallery: 'المعرض',
        isVisible: 'ظاهر في الموقع',
    },
};

export function copy(locale: string): Copy {
    return locale === 'en' ? en : ar;
}
