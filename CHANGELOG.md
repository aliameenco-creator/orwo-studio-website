# Changelog

## 0.1.3 — 2026-10-16

Blog categories made simple and manageable (owner feedback: two "film" chips, too many chips, chips hidden off-screen).

- Categories (Posts → Categories) are the blog filter: "Film Production" and "Film". The duplicate lowercase
  "film" is merged into "Film" (its old address /blog/categories/film-1991038/ redirects to /blog/categories/film/).
- The other 19 Strikingly labels (analogue, celluloid, ferrania, cannes …) are now **topics** (Posts → Tags), shown as
  "Topics" on each article and still reachable at their original /blog/categories/<name>/ addresses.
- Filter bar: chips wrap onto new lines (nothing hidden off-screen). If more than 7 categories are ever added, the
  rest move into a "More" dropdown, so the bar stays tidy. On a topic page the topic shows as an active chip.
- Rule for editors: use a category for a broad section of the blog, a tag for any detail.

**After updating the theme:** Appearance → ORWO Studios Setup → tick "Blog categories" → **Apply selected updates**.

## 0.1.2 — 2026-10-16

Phone polish and an editable menu (owner feedback with phone screenshots).

- Phone home hero: logo and tagline left-aligned, only the "Production Services" button; the outline "Contact Us"
  button and the "Play video" pill are gone. The muted showreel now also starts on phones, a few seconds after
  the page loads (not with reduced motion or data saver; the still frame stays until the video really plays).
- Phone menu: the blue "Contact Us" button sits at the end of the hamburger menu (compact spacing so it fits on
  screen); no Contact button in the phone header. The footer buttons no longer wrap on phones.
- Smoother swiping on phones: cards in swipe rows no longer fade in mid-swipe, hover lifts/zooms are off on touch
  screens, glass blur removed from the sound-stage cards on touch devices, testimonial auto-advance stops as soon
  as the visitor touches the cards.
- Testimonial cards size to their own quote (no large empty space under short quotes).
- Menu: Lamplighter Suites removed. The header now uses a normal WordPress menu, "Main menu" under
  Appearance → Menus (add, rename, reorder or remove items there).

**After updating the theme:** Appearance → ORWO Studios Setup → tick "Main menu" (and FAQ if not done yet) →
**Apply selected updates**.

## 0.1.1 — 2026-10-16

- Fixed: dropdown menu links (Services, Hotel) were dark grey on the dark menu; they are now light, with cyan on hover.
- Desktop header shows Contact once: the plain "Contact" menu link is hidden beside the "Contact Us" button
  (it stays in the phone/tablet menu, where the button is not shown).
- FAQ page filled: the site's own "Frequently Asked Questions" heading and intro, plus 10 questions answered with facts
  and wording already published on orwo.studio (location, sound stages, standing sets, tax credit, services, equipment,
  accommodation, events, props, booking). Owner review recommended. FAQ structured data (FAQPage) added for search.
- New: Appearance → ORWO Studios Setup lists pages whose design changed in a theme update and updates the selected ones
  (unedited pages pre-ticked; the previous version is kept under Revisions).

**After updating the theme:** open Appearance → ORWO Studios Setup and click "Update selected pages" to apply the new FAQ.

## 0.1.0 — 2026-10-15

First version of the ORWO Studios WordPress theme (prepared locally, not yet released or installed).

- 18 editable pages at the original addresses: Home, Tax Credit, Production Services, Standing Sets, Event Space,
  Prop Shop, Equipment Rental, ORWO Studios Hotel, Mackie's Bar & Grill, Projects, News, Contact, About Jake Seal,
  FAQ, Blog, Cookie Policy, Terms & Conditions, Privacy Policy.
- 27 original blog articles at `/blog/<slug>/` with 22 original categories at `/blog/categories/<slug>/`,
  dates, cover images and SEO titles/descriptions.
- 308 images (originals where the archive has them) imported into the Media Library by the setup button.
- Original contact form on every page that had one (Name, Email, Phone, Message, Terms/Privacy consent,
  "Submit", "Thanks for your submission!"), saved under Enquiries and emailed to hello@orwo.studio.
- Original menu with Services and Hotel dropdowns (incl. Sales & Distribution, Buy ORWO film, Lamplighter Suites) plus Blog.
- Rounded cinematic design with motion; phone layouts use two columns or swipe rails for short content.
- Copy corrections (spacing only, wording unchanged): "S ervices" → "Services" (Production Services heading),
  "T he Magnificent Seven" → "The Magnificent Seven" (Hotel intro).
- Removed platform filler: Lorem ipsum, the hidden Strikingly FAQ placeholder questions, stock "Buy Now" buttons
  linking to strikingly.com, Unsplash stock slide backgrounds, cookie banner text.
