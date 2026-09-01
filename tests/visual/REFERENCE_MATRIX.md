# Qalam Reference Visual Calibration Matrix

## Academy reference — bassthalk.com
- `/` — homepage / hero / teacher chooser / suggested courses / subjects / benefits.
- `/login` — login surface.
- `/register` — multi-step registration surface.
- `/teacher/{id}` — teacher profile / subjects / courses.
- `/subject/{id}` — subject page / teachers / courses.
- `/parent_dashboard` — parent tracking surface.
- `/store_locator` — store locator surface where applicable.

## Individual reference — ahmed-elgohary.com
- `/` — personal teacher hero / about / suggested courses / grade years / honor / benefits / testimonials / CTA.
- `/years/{id}` — grade-year course listing.
- `/course/{id}` — course detail.
- login/register surfaces where public.

## Required capture states
- Desktop: 1440x900 light + dark.
- Tablet: 1024x1366 light + dark.
- Mobile: 390x844 light + dark.
- Mobile nav: closed + open.
- Hero: initial + after reveal animation.
- Hover/focus states for nav, CTA, course cards.

## Comparison gates
- DOM hierarchy equivalent for the surface intent (not reference branding/content).
- Spacing/size deltas <= 2 px for calibrated components where reference measurements are available.
- Typography line-height/wrapping matches reference behavior at each breakpoint.
- No layout shift when opening mobile navigation.
- WCAG-readable contrast in both light and dark modes.
- Animations reproduce timing/easing behavior where reference animation can be measured.
