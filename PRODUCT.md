# Product

<!-- impeccable:product-schema 1 -->

Assumptions below are inferred from the repository and the request to implement the redesign directly, without a separate product interview. They are labeled where the code does not state them outright.

## Platform

web

## Users

Public visitors are people considering a custom software studio: they read the offer in Arabic or English and send a project brief. Admin users are RACODE staff who publish pages, services, demonstrations, articles, FAQs, media, navigation, inquiries, and settings. Assumption: the public visitor is usually evaluating the studio from a desk, on a phone or a laptop, and needs to trust the work before writing.

## Product Purpose

RACODE (راكود) is the bilingual public site and content studio for an Omani technology practice still in development. It explains the services, shows shaped demonstrations that are not client work, and collects a contact request. Success is a visitor who understands what the studio builds and can send a brief, and an editor who can publish that story in both languages without a developer.

## Positioning

The site says the studio builds what lies beyond the code: business understanding, design, engineering, and testing in one sequence. Seeded projects stay labeled as demonstrations. The site does not invent client names, awards, testimonials, or completed-project statistics.

## Operating Context

Arabic is the default locale (`/ar`); English is `/en`. The root path follows a locale cookie, otherwise Arabic. Editors work in `/admin`. Contact mail is stored to handle the request and is not sold. Scheduled publishing and a queue exist for later releases.

## Capabilities and Constraints

Public routes: home, about, services, work, technologies, insights, contact, privacy, terms, and CMS pages. Admin covers pages (including a section builder), services, projects, articles, FAQs, technologies, media, inquiries, navigation, settings, users, and activity. Both directions (RTL and LTR) are required. Existing routes, forms, permissions, and content fields stay in place. Brand raster files live in `public/brand/`. Contact address on file is `hello@racode.om` until an editor replaces it. No phone number is published.

## Brand Commitments

The name is RACODE / راكود. The public line already in the product is “Beyond the Code,” and the homepage promise in content is “Ambitious ideas. Limitless solutions.” The user asked for a new visual identity and did not pin the previous burgundy palette, so color and type are not a brand lock. Do not invent customers, prices, or results.

## Evidence on Hand

Seeded bilingual copy, service and technology records, demonstration projects, FAQ answers, and the images `public/brand/racode-hero.jpg`, `racode-plate-a.jpg`, `racode-plate-b.jpg`, and `racode-plate-c.jpg`. Those images are atmospheric plates, not photographs of a product or a place.

## Product Principles

- Say what the studio builds, and mark demonstrations as demonstrations.
- Arabic and English carry the same information, each in its own direction.
- An editor’s controls stay obvious: save, publish, schedule, delete.
- Do not decorate claims the studio has not earned.

## Accessibility & Inclusion

Keyboard access, visible focus, and a skip link are already part of the public chrome. Arabic and English must both remain readable, including mixed-direction fields in the admin.
