# Database

The schema is created by the migrations in `database/`. Local development can use SQLite. Production should use MySQL, as documented in `.env.example`.

## Identity

| Table | Role |
| --- | --- |
| `users` | Administrators. `locale` and `is_active` are added beside the Laravel defaults. |
| Spatie permission tables | Roles and permissions. |
| `notifications` | Database notifications for new inquiries. |

## Content

| Table | Role |
| --- | --- |
| `pages`, `page_sections` | Documents and the homepage section builder. |
| `services`, `service_technology` | Services and the technologies attached to them. |
| `project_categories`, `projects`, `project_technology`, `project_media` | Demonstrations and case-study fields. |
| `technologies` | The technology list. Names stay in Latin script. |
| `article_categories`, `articles`, `tags`, `article_tag` | Insights. |
| `faqs` | Bilingual questions, placed on the homepage or the general list. |
| `navigation_items` | Header and footer links. |
| `site_settings` | Brand, contact, SEO, maintenance, and analytics, one JSON value per key. |

Publishing columns on the main content tables are `status`, `published_at`, `scheduled_for`, `published_payload`, `scheduled_payload`, and `has_unpublished_changes`. A slug change on published content is held in `pending_slug` until publish.

`projects.is_demonstration` defaults to true. `results_verified` must be true before result text is stored.

## Operations

| Table | Role |
| --- | --- |
| `media`, `media_usages` | Library files and the records that reference them. |
| `inquiries`, `inquiry_notes` | Contact submissions, status, assignment, and internal notes. |
| `content_revisions` | The last 30 snapshots for a record. |
| `activity_logs` | Administrative actions. Password fields are stripped from the stored properties. |

## Seed data

`DatabaseSeeder` creates roles, one super admin, site settings, technologies, eight services, three demonstration projects, two articles, six FAQs, the system pages, homepage sections, and navigation. It stops after roles when `APP_ENV=testing`.

Re-running the seeder updates existing pages, services, and projects by slug. FAQs are replaced. It does not invent clients, awards, or delivery statistics.
