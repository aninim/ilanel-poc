# ILANEL WordPress + WooCommerce — Planning

## Status

**Phase:** Active pre-launch build and studio handover.

**Site:** `https://ilanel.dads42.com` is the current working site. The final
production address will be `https://ilanel.com` after cutover.

**Plan of record:** `docs/LAUNCH-PLAN.md`.

## Current handover work

- [x] Consolidate studio decisions and inputs in `docs/STUDIO-TASKS.md`.
- [x] Draft the studio's routine WordPress/WooCommerce update manual.
- [ ] Oren reviews the manual before it is sent to the studio.
- [ ] Configure reliable WordPress transactional email through an existing
  ILANEL Google Workspace mailbox using the free WP Mail SMTP setup; send an
  external test email and verify WordPress password-reset and WooCommerce
  order emails before launch. Do not buy another Workspace user or Cloudways
  email add-on unless testing shows it is necessary.
- [ ] Build the dedicated **ILANEL Studio Assistant**: a narrow agent + skill
  + authenticated MCP for approved studio tasks that the normal WordPress UI
  does not expose.
- [ ] Give the Studio Assistant task-specific operations for product hero and
  story image sets, finish swatches, News story galleries, and other approved
  custom metadata—each with preview, confirmation, and an audit trail.
- [ ] Run a short studio screen-share after the admin UI and production URL
  are final.

## Important self-service boundary

The studio will have two controlled update paths:

1. **WordPress admin** for normal WooCommerce prices, product text,
   product-card images, product specification fields, and WordPress News
   posts.
2. **ILANEL Studio Assistant** for approved task workflows that are not
   editable in the standard WP UI, beginning with the custom multi-image
   hero/story galleries and finish swatches.

Do not describe the second path as live until its skill, MCP permissions,
preview/confirmation flow, and audit trail have been built and tested. Until
then, Oren/site administration performs those custom-field changes manually.

## Session log

### 2026-08-21 — Studio handover manual and email follow-up

**Completed:**

- Drafted the studio WordPress/WooCommerce update manual in Markdown and
  generated matching HTML and PDF review copies.
- Defined the operating boundary between ordinary WordPress administration,
  the planned ILANEL Studio Assistant, and Oren/site-administrator work.
- Confirmed `ilanel.com` already uses Google Workspace and selected a
  zero-additional-subscription approach for WordPress transactional email:
  reuse an existing controlled ILANEL mailbox with free WP Mail SMTP setup.
- Added the email setup and verification work to this task list.
- Scheduled a Google Calendar reminder for Monday, 24 August 2026 at 09:00
  Europe/Bucharest.

**Next:**

- Oren reviews and approves or revises the studio manual before it is shared
  as shipping documentation.
- On the reminder date, choose the controlled ILANEL sender mailbox, configure
  WP Mail SMTP, then test an external message, WordPress password reset, and
  WooCommerce order email.
- After the manual/admin UI is settled, scope and build the Studio Assistant's
  authenticated, task-specific MCP operations.

**Review state:** The planning file and three manual artifacts are preserved in
Git as review drafts. They are not approved for studio distribution yet.
