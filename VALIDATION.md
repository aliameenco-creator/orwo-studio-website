# Validation — 0.1.0 (2026-10-15)

Local sandbox only (WordPress + SQLite on http://127.0.0.1:8893, fresh install, mail intercepted).
`python tools/check_orwo_studio_theme.py`: **323/323 checks passed**.

- One-click setup on a fresh install: 308/308 images in the Media Library, all 18 pages and 27 articles created,
  categories and covers assigned, Home set as front page, WordPress privacy draft taken over, "Hello world!" trashed.
- Every page, article and the header/footer parse as valid blocks in the real block editor.
- All page routes at 1920 / 768 / 390 / 360 / 320 px (true mobile emulation below 600 px), all articles at
  1920 and 390 px, two category archives: HTTP 200, exactly one H1, no horizontal overflow, no broken images,
  no JavaScript errors, current-year footer.
- Phone menu after scrolling (all 17 original items on screen, Escape closes), gallery lightbox (next, Escape),
  no video player loaded before click, click-to-play loads the selected video.
- Contact form: required fields + consent validation, in-page submission, success text, notification handed to
  WordPress for hello@orwo.studio, enquiry stored with all fields; invalid email, missing consent, minimum-time and
  honeypot guards.
- Setup rerun keeps page/article counts and editor changes.
- 301 redirects: /home, /film-studio, /_blog, /bnb, /blog/categories, unknown category, /c/apps/google_map, ?p= article.
- Reduced motion: hero headline stays static.

Not verifiable locally: real email delivery on the host; the muted YouTube background video (Playwright's Chromium
cannot play YouTube H.264, so the poster frame is shown); the Google map tiles.
