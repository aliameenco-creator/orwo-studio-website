# ORWO Studios WordPress theme

Editable block theme for ORWO Studios (https://www.orwo.studio/), the film and TV studio in Baton Rouge, Louisiana.
All copy, photos, posters, video IDs, testimonials, partner logos, menus and the contact form come from the archived
Strikingly site (`exports/www.orwo.studio-49eea8759a/`); only the presentation is new.

## Design

- Dark, cinematic layout in the ORWO charcoal/white palette with the site's original cyan accent (`#26c9ff`).
- Rounded everywhere: inset 34px panels, 24px cards, pill buttons and a floating glass header.
- Playfair Display (the original title font) with Manrope for text, bundled locally (SIL OFL).
- Motion: word-by-word hero headline, muted background showreel on desktop, scroll reveals, photo and logo marquees,
  testimonial strip, production rail with arrows/drag, stat count-up, film-can spin, lightbox for photos and videos.
  Everything respects "reduced motion" and works without JavaScript.

## Install

1. Upload `orwo-studio.zip` under Appearance → Themes → Add New → Upload, then activate.
2. Appearance → ORWO Studios Setup → **Complete ORWO Studios setup**. This imports all images into the Media Library,
   then creates the pages, blog articles, categories, header and footer at the original addresses.
3. Settings → General → "ORWO Studios form recipient" (default hello@orwo.studio). Send one real test message.

## What is editable where

| Content | Where |
| --- | --- |
| Every page (Home included) | Pages (block editor) |
| Blog articles and categories | Posts |
| Header, menu, footer | Appearance → Editor → Patterns/Template parts |
| Form labels and thank-you text | Settings → ORWO Studios Form |
| Form messages | Enquiries |
| Map address | Shortcode block `[orwost_map address="…"]` on the page |

Theme files only provide the design (CSS/JS), templates and the form/map/social shortcodes.

## Original URLs

Pages keep their original slugs (`/tax-credit/`, `/mackie-s-bar-and-grill/`, `/pages/cookie-policy/` …), articles live at
`/blog/<slug>/` and categories at `/blog/categories/<slug>/`. Platform-only addresses redirect with 301:
`/home` and `/film-studio` → `/`, `/_blog` and `/blog/categories` → `/blog/`, `/bnb` → `/hotel/`,
`/c/apps/google_map` → `/contact/`.

## Build and test (developers)

```powershell
python tools/build_orwo_studio_theme.py                 # regenerate content from the archive + dist/orwo-studio-theme.zip
python tools/prepare_orwo_studio_wordpress.py --fresh   # fresh local WordPress on 127.0.0.1:8893
.tools/php/php.exe -S 127.0.0.1:8893 -t .tools/orwostudio-wordpress tools/orwo_studio_wordpress_router.php
python tools/check_orwo_studio_theme.py                 # full QA (setup, blocks, 5 widths, forms, redirects)
```

Updates: numbered releases of https://github.com/aliameenco-creator/orwo-studio-website (asset `orwo-studio.zip`,
SHA-256 verified) appear under Appearance → ORWO Studios Updates and the normal WordPress theme updates screen.
