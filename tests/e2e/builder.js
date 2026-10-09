// Builder end-to-end check against http://localhost:8089 (usage: node builder.js <shotsdir>)
const { chromium } = require(process.env.PW_PATH || 'playwright');
const BASE = process.env.ILT_BASE || 'http://localhost:8089';
const shots = process.argv[2];
const results = [];
const check = (name, ok, extra = '') => { results.push([ok, name]); console.log((ok ? '  ok   ' : '  FAIL ') + name + (extra ? ' — ' + extra : '')); };

async function login(page, user) {
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', user);
  await page.fill('#user_pass', 'ilt-test-pass-123');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
}
const shortcode = (page) => page.inputValue('#ilt-shortcode');
async function previewHas(page, selector) {
  return page.waitForFunction((sel) => {
    const r = document.querySelector('#ilt-preview').shadowRoot;
    return r && r.querySelector(sel);
  }, selector, { timeout: 15000 }).then(() => true, () => false);
}
async function waitShortcode(page, expected) {
  return page.waitForFunction((exp) => document.querySelector('#ilt-shortcode').value === exp, expected, { timeout: 5000 }).then(() => true, () => false);
}

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
  await ctx.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: BASE });
  const page = await ctx.newPage();
  const jsErrors = [];
  page.on('pageerror', (e) => jsErrors.push(e.message));
  page.on('console', (m) => { if (m.type() === 'error' && !m.text().startsWith('Failed to load resource')) jsErrors.push(m.text()); });

  await login(page, 'iltadmin');
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-builder');
  check('builder loads with default preview', await previewHas(page, 'table.ilt-standing'));
  check('default shortcode', (await shortcode(page)) === '[iran_league]', await shortcode(page));
  await page.screenshot({ path: shots + '/builder-default.png', fullPage: true });

  await page.click('.ilt-card-choice:has(input[value="azadegan"])');
  await page.click('.ilt-segmented label:has(input[value="advanced"])');
  check('league + mode shortcode', await waitShortcode(page, '[iran_league league="azadegan" mode="advanced"]'), await shortcode(page));
  check('preview updates to advanced azadegan', await previewHas(page, '.ilt--advanced.ilt--azadegan'));

  await page.selectOption('#ilt-preset', 'dark');
  check('preset fills colors', (await shortcode(page)).includes('title_backcolor="#000000"') && (await shortcode(page)).includes('odd_color="#1e1e1e"'), await shortcode(page));
  check('preview uses preset', await previewHas(page, '.ilt[style*="--ilt-odd:#1e1e1e"]'));

  await page.click('label.ilt-toggle:has([data-ilt-key="logo"])');
  check('logo toggle', (await shortcode(page)).includes('logo="false"'));
  check('preview hides logos', await page.waitForFunction(() => { const r = document.querySelector('#ilt-preview').shadowRoot; return r.querySelector('table') && !r.querySelector('img.ilt-logo'); }, null, { timeout: 15000 }).then(() => true, () => false));

  await page.fill('#ilt-field-title_font', '200');
  await page.locator('#ilt-field-title_font').blur();
  check('number clamps to max', (await page.inputValue('#ilt-field-title_font')) === '64' && (await shortcode(page)).includes('title_font="64"'));
  await page.screenshot({ path: shots + '/builder-dark.png', fullPage: true });

  await page.click('#ilt-copy');
  await page.waitForTimeout(300);
  const clip = await page.evaluate(() => navigator.clipboard.readText()).catch((e) => 'ERR ' + e.message);
  check('copy puts shortcode on clipboard', clip === (await shortcode(page)), clip);
  check('copied toast', (await page.textContent('#ilt-toast')).length > 0);

  await page.click('#ilt-reset');
  check('reset to defaults', await waitShortcode(page, '[iran_league]'));

  const ex6 = '[iran_league league="bartar" mode="advanced" title_backcolor="#212121" title_color="#ffffff" text_color="#212121" odd_color="#ffffff" even_color="#eeeeee" logo_size="15" logo="true" title_font="12" text_font="13" farsi_numbers="false"]';
  await page.fill('#ilt-import', 'Some text ' + ex6 + ' more');
  await page.click('#ilt-import-button');
  check('import parses README example 6', await waitShortcode(page, '[iran_league mode="advanced" farsi_numbers="false" title_color="#ffffff" title_font="12" text_font="13"]'), await shortcode(page));
  check('import set the toggle', !(await page.isChecked('[data-ilt-key="farsi_numbers"]')));

  await page.fill('#ilt-import', 'no shortcode here');
  await page.click('#ilt-import-button');
  await page.waitForTimeout(800);
  check('import error shown', (await page.textContent('#ilt-toast')).length > 0 && await page.$eval('#ilt-toast', (e) => e.classList.contains('is-error')));

  // Save as table.
  await page.fill('#ilt-table-title', 'E2E sidebar table');
  await page.click('#ilt-save');
  const saved = await page.waitForFunction(() => /^\[iran_league id="\d+"\]$/.test(document.querySelector('#ilt-shortcode').value), null, { timeout: 8000 }).then(() => true, () => false);
  const sc = await shortcode(page);
  check('save as table gives short shortcode', saved, sc);
  const id = (sc.match(/id="(\d+)"/) || [])[1];
  check('URL switched to edit mode', page.url().includes('table=' + id));
  check('editing notice shown', await page.isVisible('.ilt-editing'));

  await page.click('.ilt-segmented label:has(input[value="basic"])');
  check('override shortcode while unsaved', await waitShortcode(page, `[iran_league id="${id}" mode="basic"]`), await shortcode(page));
  check('unsaved notice', await page.isVisible('.ilt-editing__unsaved'));
  await page.click('#ilt-save');
  check('update table', await waitShortcode(page, `[iran_league id="${id}"]`), await shortcode(page));
  check('unsaved notice cleared', !(await page.isVisible('.ilt-editing__unsaved')));

  // Reload in edit mode.
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-builder&table=' + id);
  check('edit page loads saved values', await waitShortcode(page, `[iran_league id="${id}"]`) && (await page.isChecked('input[name="mode"][value="basic"]')));

  // Saved tables list.
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-tables');
  check('saved tables list shows table', (await page.textContent('#the-list')).includes('E2E sidebar table'));
  await page.screenshot({ path: shots + '/tables.png', fullPage: true });
  await page.click(`#the-list tr:has-text("E2E sidebar table") .ilt-copy`);
  await page.waitForTimeout(300);
  check('list copy button', (await page.evaluate(() => navigator.clipboard.readText())) === `[iran_league id="${id}"]`);
  await page.hover('#the-list tr:has-text("E2E sidebar table")');
  await Promise.all([page.waitForNavigation(), page.click('#the-list tr:has-text("E2E sidebar table") .row-actions .duplicate a')]);
  check('duplicate', (await page.textContent('#the-list')).includes('E2E sidebar table (copy)'));
  page.once('dialog', (d) => d.accept());
  await page.hover('#the-list tr:has-text("(copy)")');
  await Promise.all([page.waitForNavigation(), page.click('#the-list tr:has-text("(copy)") .row-actions .delete a')]);
  check('delete', !(await page.textContent('#the-list')).includes('(copy)'));

  // Front end uses the saved table.
  const front = await (await ctx.request.get(BASE + '/?ilt_e2e=1')).text();
  check('front page reachable', front.length > 0);

  // Settings.
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-settings');
  await page.fill('#ilt-cache-ttl', '10');
  await Promise.all([page.waitForNavigation(), page.click('#submit')]);
  check('settings saved', (await page.inputValue('#ilt-cache-ttl')) === '10');
  await Promise.all([page.waitForNavigation(), page.click('.ilt-status tr:has-text("League One") button')]);
  check('refresh now', page.url().includes('ilt_notice=refreshed') && await page.isVisible('.notice-success'), page.url());
  await page.screenshot({ path: shots + '/settings.png', fullPage: true });

  // Help page lives in the League Table menu only.
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-help');
  check('help page loads', (await page.locator('.ilt-help-attributes').count()) === 1);
  check('no help page under Settings', !(await page.content()).includes('options-general.php?page=league-table'));

  // REST security.
  const nonce = await page.evaluate(async () => { const r = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce'); return r.text(); });
  const noNonce = await page.evaluate(async () => (await fetch('/wp-json/ilt/v1/preview', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ values: {} }) })).status);
  check('REST without nonce rejected', noNonce === 401 || noNonce === 403, String(noNonce));
  const withNonce = await page.evaluate(async (n) => (await fetch('/wp-json/ilt/v1/preview', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': n }, body: JSON.stringify({ values: { league: '../../x', title_color: 'red;"x' } }) })).json(), nonce);
  check('REST preview sanitizes', withNonce.values && withNonce.values.league === 'persiangulf' && withNonce.values.title_color === '#eeeeee');

  const sub = await browser.newContext();
  const sp = await sub.newPage();
  await login(sp, 'iltsub');
  const subNonce = await sp.evaluate(async () => (await fetch('/wp-admin/admin-ajax.php?action=rest-nonce')).text());
  const subStatus = await sp.evaluate(async (n) => (await fetch('/wp-json/ilt/v1/preview', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': n }, body: JSON.stringify({ values: {} }) })).status, subNonce);
  check('subscriber gets 403 on preview', subStatus === 403, String(subStatus));
  const subTable = await sp.evaluate(async ([n, id]) => (await fetch('/wp-json/ilt/v1/tables/' + id, { method: 'DELETE', headers: { 'X-WP-Nonce': n } })).status, [subNonce, id]);
  check('subscriber cannot delete table', subTable === 403, String(subTable));
  await sp.goto(BASE + '/wp-admin/admin.php?page=ilt-builder');
  check('subscriber cannot open builder', (await sp.content()).includes('not allowed'));
  await sub.close();

  check('no JS errors', jsErrors.length === 0, jsErrors.join(' | '));
  await browser.close();
  const failed = results.filter((r) => !r[0]).length;
  console.log(failed ? `\n${failed} FAILED` : '\nAll E2E checks passed');
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
