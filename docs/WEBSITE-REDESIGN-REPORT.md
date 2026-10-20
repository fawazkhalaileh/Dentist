# Website redesign: final report

The public website at http://20.216.17.199 was redesigned in four releases on 20 October 2026, following `docs/website-redesign-brief.md`. Every release was approved by the clinic owner before going live, and each deploy was preceded by a verified database backup.

| Release | What went live | Commit |
|---|---|---|
| 1 | Design foundation, header, menus, footer, homepage, Meet the Doctor page | `35b5fd2` |
| 1a | Doctor's name set (now spelled Dr. Mohammad Amara everywhere, matching the clinic name and Instagram) | `6c10c50` |
| 2 | About, Treatments (overview and detail pages), Smile Gallery, Blog (list and articles), Contact, Booking, FAQ, Privacy, Terms, Testimonials, Dentists | `7d6135c` |
| 3 | Accessibility, responsiveness, speed and search-engine fixes (this report) | `631f592` |

## Pages redesigned

Home, About Us, Meet Dr. Mohammad Amara, Treatments, every treatment page, Smile Gallery, Blog, blog categories and search, every article, Contact, Book an Appointment, FAQ, Testimonials, Dentists, Privacy and Terms. All of them work in English and Arabic (right-to-left).

## Assets used

| Asset | Where |
|---|---|
| Official "amara DENTAL" logo (media 14) | Header (mark beside the wordmark), light footer version, favicons, social preview and structured data. Only the white background was removed; nothing was redrawn or distorted. |
| Reception photo (media 7) | Homepage hero, About, clinic gallery, appointment banner, social preview |
| Treatment room photo (media 5) | Homepage, Treatments, treatment pages, About, blog images, treatments menu |
| Dr. Mohammad Amara at reception (media 10) | Homepage doctor section, Meet the Doctor page, About |
| Before/after photos (media 3, 4, 6, 8, 9, 11, 12, 13, 15 to 22) | **Not shown yet:** no patient consent is recorded. Neutral placeholders are shown instead. |

## Components added

Logo, responsive photo, icon set, page banner, breadcrumbs, section heading, treatment card, treatment page layout, blog card, article layout, before/after slider, questions and answers list, and appointment banner. See "Public website design" in `docs/ARCHITECTURE.md`.

## Backend preserved

- No database schema changes and no migrations.
- The appointment request form and the contact form keep the same fields, validation, spam protection and rate limits. Only their look changed.
- The CMS, clinic dashboard, patient portal, logins and roles are unchanged. The dashboard keeps its own stylesheet.
- Nothing was written to the production database. Sample content exists only in code, is labelled on screen, is hidden from search engines and disappears when real content is published.
- Controller changes are read-only: sample fallbacks, blog search and related articles.

## Placeholders awaiting the clinic's approval

1. **Doctor biography, background and qualifications:** placeholder text, with "Awaiting verified information" boxes.
2. **8 sample treatments with information pages.** They are replaced when the clinic publishes its own services (7 drafts are already in the CMS).
3. **6 sample articles with demo dates.** They are replaced when the first real article is published.
4. **3 sample testimonials,** clearly labelled as not real.
5. **About Us story:** placeholder until the About page (a draft exists) is published.
6. **Welcome section and "why choose us" copy** on the homepage.
7. **Contact details:** "Phone number coming soon" until a phone number (and WhatsApp) is entered in the clinic details.

## Missing assets

- Patient consent for each before/after pair, so they can be published in the Smile Gallery.
- More clinic photos (exterior or entrance, waiting area, equipment close-ups, team) to vary the imagery.
- A portrait-format photo of the doctor (the current one is landscape and cropped).
- Phone number, WhatsApp number and a Google Maps embed link (the map appears automatically when an embed link is entered).
- Optional: treatment photos for each service.

## Quality checks

- **Accessibility:** axe-core (WCAG 2.0, 2.1 and 2.2 AA plus best practice) across 20 page/language combinations at desktop and phone width. **No violations.** Fixes made: darker grey for small text, a keyboard-scrollable testimonial slider, and a focus trap in the mobile menu.
- **Keyboard:** a skip link, visible focus everywhere, menus open with Enter and close with Escape (focus returns to the button), the before/after slider works with arrow keys, and the gallery viewer moves with arrow keys.
- **Reduced motion:** reveal animations and transitions switch off when the visitor asks for reduced motion.
- **Responsive:** no horizontal scrolling on any page at 320, 360, 375, 390, 768, 1024, 1280, 1440 and 1920 px, in English and Arabic.
- **Speed:**
  - The website stylesheet went from 47 KB to 11 KB (compressed) by splitting it from the dashboard.
  - Fonts are self-hosted, and only the current language's fonts load.
  - Images are WebP in several sizes and lazy-loaded below the fold, with the main image prioritised.
  - The script is 3 KB.
  - The homepage reaches its largest image in about 0.4 s on the local test server.
- **Search engines:**
  - Every page has a title, a description, a canonical address, language alternates, social preview tags and exactly one main heading.
  - Structured data: `Dentist`, `BreadcrumbList`, `FAQPage` and `BlogPosting`.
  - The sitemap includes the doctor page and excludes sample pages.
- **Tests:** 353 automated tests pass, including 11 for the website design.

## Integration notes

- Browser caching rules for build files (1 year) and images (30 days) are in `public/.htaccess`. Apache's `headers` module was enabled on the server on 20 October 2026 to apply them.
- Arabic interface text is a careful first draft. A native speaker should review it before a wider launch.

## Recommendations

1. Confirm consent and publish the before/after pairs. They are the strongest feature of the site.
2. Publish the 7 draft services and the About page in the CMS, editing the wording as needed.
3. Add the phone and WhatsApp numbers, plus a Google Maps embed link.
4. Supply verified details for the doctor's biography, and a portrait photo.
5. Collect real reviews with permission and publish them as testimonials.
6. Set up a domain name with HTTPS before promoting the site. Social sharing and search ranking both depend on it.
