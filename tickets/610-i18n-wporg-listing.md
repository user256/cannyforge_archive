# Ticket 610: i18n completeness + wp.org listing assets

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Strings correctly use the `cannyforge-archive` text domain, but there is no
generated `.pot` file, so translators have nothing to work from, and nothing
in QA verifies new strings keep the right domain. Separately, the wp.org
listing itself is part of "amazing": the directory page needs banner/icon/
screenshot assets and a readme whose FAQ answers what reviewers and users ask
first (what does it do to my pagination? what data goes to Google? how do I
undo it?). `assets/branding/` exists but the wp.org `/assets` convention
(repo-root assets dir in SVN, not shipped in the plugin) is not yet prepared.

## Goal

The plugin is translation-ready with the domain enforced by tooling, and the
wp.org listing assets and readme are submission-complete.

## Acceptance criteria

- [ ] `composer i18n` (new script) runs WP-CLI `wp i18n make-pot` producing
      `languages/cannyforge-archive.pot`; the script is part of the release
      procedure documented in README.
- [ ] PHPCS `WordPress.WP.I18n` sniff is configured with
      `text_domain=cannyforge-archive` in `phpcs.xml.dist` so a wrong/missing
      domain fails `composer cs`.
- [ ] Translator comments (`/* translators: ... */`) added for every string
      with placeholders.
- [ ] JS strings in `assets/js/*.js` that surface to users are localised via
      `wp_set_script_translations` / `wp_localize_script` rather than
      hardcoded English.
- [ ] wp.org listing assets prepared to spec: banner 1544×500 & 772×250,
      icon 256×256 & 128×128, ≥ 3 screenshots (settings page, archive page,
      shortened pagination) with matching `== Screenshots ==` captions in
      readme.txt.
- [ ] readme.txt FAQ covers: pagination reversibility, exactly what data is
      sent to Google and when (extending the Sprint 5 disclosure), and cache
      behaviour.

## Out of scope

- Actually producing translations; the deliverable is the infrastructure.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none
- **External:** WP-CLI available locally/CI (composer-installable)

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
