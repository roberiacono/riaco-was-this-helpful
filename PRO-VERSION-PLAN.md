# PRO Version Plan

Status: draft, 2026-07-17

## Architecture

PRO ships as a **separate add-on plugin**, not a fork:

- `uninstall.php` already checks `class_exists( 'RIWTH_Was_This_Helpful_Pro' )` and skips data deletion when true — PRO must define that class.
- PRO should attach via the free plugin's existing filters/actions rather than modifying core files: `riwth_display_on_fields`, `riwth_feedback_box_atts`, `riwth_insert_feedback_data`, `riwth_insert_feedback_format`, `riwth_localize_script_data`, `riwth_could_display_box`, `riwth_should_display_box`, `riwth_ajax_feedback_sent_return`, etc. (see CLAUDE.md "Developer Hooks").
- README.txt already teases one PRO feature publicly: custom post type support in "Display on".

## Candidate Features

### Reach / display control
- **Custom post type support** (already publicly teased) — extend `riwth_display_on` beyond built-in post types via `riwth_display_on_fields`.
- Conditional display by category/tag/taxonomy, user role, or logged-in status.
- Multiple box styles/templates beyond the free plugin's single style.

### Richer feedback
- Optional "why not helpful?" follow-up (free text or predefined reasons) shown after a No vote.
- Star rating / emoji reactions as alternatives to thumbs up/down.
- Per-vote comment field.

### Insight / reporting
- Trend charts (positive vs. negative over time), most/least helpful content report.
- Email digest or instant alert on negative feedback.
- Richer Feedback Records filtering (date range, post type, vote type) beyond the free CSV export.

### Integrations
- Webhook / Zapier / Slack notification on new feedback.
- GA4 event push.
- REST API endpoints for headless/external dashboards.

### Polish / trust
- Remove "Made with ♥" admin footer branding (`RIWTH_Admin_Pages_Footer`) — cheap to gate (hooked via `in_admin_footer` / `admin_footer_text`, scoped to the plugin's own 3 admin screens), appeals to agencies white-labeling client sites. Caveat: this footer also carries the WP.org review-ask, so gating it trims the review funnel from your most satisfied (paying) users — treat as a minor bundled perk, not a headline feature.
- Server-side duplicate-vote tracking by user ID (cross-device) instead of cookie-only.

## Recommendation

Ship v1 PRO with 3-4 features max, prioritizing structurally new data collection over settings toggles:
1. Custom post type support (already promised publicly)
2. "Why not helpful?" follow-up reason field
3. Analytics/reporting view (trend + most/least helpful)
4. (stretch) Negative-feedback email alerts

Open questions to resolve before locking scope:
- Pricing/tiering model (single tier vs. agency/multi-site tier)
- Whether reporting needs new DB columns (e.g. reason field) requiring a `RIWTH_DB_VERSION` bump and upgrade path
- Whether PRO reuses the free plugin's AJAX/nonce flow or adds its own
