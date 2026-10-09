# End-to-end checks (Playwright)

Run against a disposable WordPress with the plugin active, e.g. via `npx wp-env start` (see `.wp-env.json`)
or any local install. The scripts expect:

- users `iltadmin` (administrator) and `iltsub` (subscriber), password `ilt-test-pass-123`
- the Classic Editor plugin active (for `persian-and-modal.js`)
- `ILT_BASE` = site URL (default `http://localhost:8089`)

```bash
npm i -D playwright && npx playwright install chromium
node tests/e2e/builder.js /tmp/ilt-shots            # Builder, saved tables, settings, REST permissions
node tests/e2e/persian-and-modal.js /tmp/ilt-shots  # screenshots + classic editor "Insert into post"
```

`persian-and-modal.js` switches nothing by itself; set the admin user's locale to `fa_IR` first to get Persian screenshots.
