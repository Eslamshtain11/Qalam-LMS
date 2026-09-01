=== Qalam LMS ===
Contributors: Qalam Electronic Services
Tags: lms, education, courses, quizzes, rtl, arabic
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.29.4
License: GPLv2 or later

Qalam LMS is an Arabic-first learning platform for courses, lessons, quizzes,
assignments, certificates, live sessions, reports, commerce, subscriptions,
and multi-instructor education websites.

== Product model ==

* One unified Qalam LMS package with bundled advanced features.
* Standalone Qalam administration dashboard for platform owners and managers.
* Role-aware operational settings without granting WordPress administrator access.
* Qalam Design Studio reserved for the service operator.
* Academy and individual-teacher public design systems.
* RTL, responsive layouts, and light/dark presentation modes.
* Mandatory Qalam footer attribution managed by the product runtime.

== Security ==

Qalam LMS keeps WordPress capabilities separated from platform capabilities,
preserves secret settings during updates, validates privileged actions, and
ships without QA runtime probes, development tests, private keys, or donor QA
credentials in the installable package.

== Upgrade notice ==

0.29.1 adds custom-day subscriptions, the complete 45-feature catalog,
platform profiles and atomic AI question quota usage on top of the Qalam Cloud connector,
maintenance-only activation, encrypted site credentials, offline grace, and
durable event delivery while preserving platform data and content.

== Changelog ==

= 0.29.4 =
* Prevents optional Tutor and Tutor Pro admin assets from crashing the standalone Qalam settings screen.
* Shows the signed remaining AI question balance inside the Qalam dashboard header for authorized platform administrators.

= 0.29.3 =
* Adds an authenticated Cloud control channel for immediate suspension and reactivation synchronization.
* Shows a branded support message while the subscription is suspended.
* Supports Cloud-side AI credit additions and safe deductions without reducing below used or reserved questions.

= 0.29.2 =
* Makes every Cloud feature entitlement independently enforceable and reconciles the signed manifest into real local add-on states.
* Shows a clear remaining AI question balance and keeps atomic reserve/commit/release accounting.
* Adds Cloud-side AI credit top-ups and uses each bundled add-on's real image asset in the Cloud catalog.

= 0.29.1 =

* Added custom subscription durations, individual/academy policies and complete catalog sync.
* Added AI quota reservation, commit and release integration.

= 0.29.0 =
* Added Qalam Cloud activation and hourly entitlement synchronization.
* Added signed HMAC event delivery with idempotency and replay protection.
* Added encrypted site-secret storage and a signed local entitlement cache.
* Added a 72-hour connectivity grace period and branded subscription suspension.
* Restricted Cloud maintenance to Qalam operator capabilities; owners and managers cannot access it.

= 0.28.5 =
* Removed invented public grade/year placeholders when no real course taxonomy exists.
* Public teacher, subject, grade-track and picker sections now hide when there is no real data to show.
* Removed operator/developer instructions from student-facing empty states and About media placeholders.
* Removed placeholder honor-board content until real achievement data is available.
* Stopped inventing teacher biography copy when no biography was supplied.

= 0.28.2 =
* Fixed academy mobile hero image crop and sizing.
* Fixed academy feature cards stacking/readability on phones.
* Fixed individual hero contrast in dark mode and portrait framing.
* Replaced the legacy Tutor WordPress admin sidebar SVG with a Qalam-owned menu mark.

= 0.28.1 =
* Production baseline closure.
* Removed QA runtime probes and generated runtime-report artifacts.
* Removed QA-domain links from the distributed runtime.
* Preserved role matrix, settings isolation, academy/individual design systems,
  and the verified public/admin surfaces from 0.28.0.
