// Phase 5 features on a real page: zones + legend, highlight, around, footer, team links, dark mode, JSON-LD.
const { chromium } = require(process.env.PW_PATH || 'playwright');
const BASE = process.env.ILT_BASE || 'http://localhost:8089';
const shots = process.argv[2];
const results = [];
const check = (name, ok, extra = '') => { results.push(ok); console.log((ok ? '  ok   ' : '  FAIL ') + name + (extra ? ' — ' + extra : '')); };
(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1000, height: 1300 } });
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message));
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', 'iltadmin');
  await page.fill('#user_pass', 'ilt-test-pass-123');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

  // Enable structured data.
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-settings');
  await page.check('input[name="ilt_settings[structured_data]"]');
  await Promise.all([page.waitForNavigation(), page.click('#submit')]);
  check('structured data setting saved', await page.isChecked('input[name="ilt_settings[structured_data]"]'));

  // Builder shows the new fields and keeps them in the shortcode.
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-builder');
  await page.fill('#ilt-field-highlight_top', '2');
  await page.fill('#ilt-field-highlight_bottom', '2');
  await page.fill('#ilt-field-highlight', 'پرسپولیس');
  await page.click('label.ilt-toggle:has([data-ilt-key="show_updated"])');
  await page.click('label.ilt-toggle:has([data-ilt-key="team_links"])');
  await page.click('label.ilt-toggle:has([data-ilt-key="show_source"])');
  await page.click('label.ilt-toggle:has([data-ilt-key="dark_mode"])');
  await page.click('.ilt-segmented label:has(input[value="advanced"])');
  const expected = '[iran_league mode="advanced" highlight="پرسپولیس" team_links="true" show_updated="true" show_source="true" highlight_top="2" highlight_bottom="2" dark_mode="true"]';
  const got = await page.waitForFunction((e) => document.querySelector('#ilt-shortcode').value === e, expected, { timeout: 8000 }).then(() => true, () => false);
  check('builder shortcode with new options', got, await page.inputValue('#ilt-shortcode'));
  check('builder preview shows legend', await page.waitForFunction(() => { const r = document.querySelector('#ilt-preview').shadowRoot; return r && r.querySelector('.ilt-legend') && r.querySelector('.ilt-row--highlight'); }, null, { timeout: 15000 }).then(() => true, () => false));
  await page.screenshot({ path: shots + '/p5-builder.png', fullPage: true });

  // Publish a page with the shortcode + an "around" table.
  const link = await page.evaluate(async (content) => {
    const r = await wp.apiFetch({ path: '/wp/v2/pages', method: 'POST', data: { title: 'Phase 5', status: 'publish', content } });
    return r.link;
  }, expected + '\n\n[iran_league around="سپاهان" farsi_numbers="false"]').catch(async () => {
    await page.goto(BASE + '/wp-admin/post-new.php');
    await page.waitForFunction(() => window.wp && wp.apiFetch);
    return page.evaluate(async (content) => (await wp.apiFetch({ path: '/wp/v2/pages', method: 'POST', data: { title: 'Phase 5', status: 'publish', content } })).link, expected + '\n\n[iran_league around="سپاهان" farsi_numbers="false"]');
  });
  const anon = await (await browser.newContext({ viewport: { width: 1000, height: 1300 } })).newPage();
  await anon.goto(link);
  const t1 = anon.locator('.ilt').nth(0);
  check('top/bottom zone rows', await t1.locator('tr.ilt-row--top').count() === 2 && await t1.locator('tr.ilt-row--bottom').count() === 2);
  check('highlighted team', (await t1.locator('tr.ilt-row--highlight .ilt-team-name').innerText()).trim() === 'پرسپولیس');
  check('legend with two items', await t1.locator('.ilt-legend li').count() === 2);
  check('updated footer in Persian calendar', /به‌روزرسانی: [۰-۹]+ (فروردین|اردیبهشت|خرداد|تیر|مرداد|شهریور|مهر|آبان|آذر|دی|بهمن|اسفند) [۰-۹]{4}/.test(await t1.locator('.ilt-updated').innerText()), await t1.locator('.ilt-updated').innerText());
  check('team links to varzesh3', (await t1.locator('a.ilt-team-name').first().getAttribute('href')).startsWith('https://www.varzesh3.com/'));
  check('source link', (await t1.locator('.ilt-source a').getAttribute('href')).startsWith('https://www.varzesh3.com/'));
  const t2 = anon.locator('.ilt').nth(1);
  check('around shows 5 rows with Sepahan in the middle', await t2.locator('tbody tr').count() === 5 && (await t2.locator('tbody tr').nth(2).innerText()).includes('سپاهان'));
  check('JSON-LD present', await anon.locator('script[type="application/ld+json"]').count() >= 1);
  const light = await t1.evaluate((el) => getComputedStyle(el.querySelector('thead')).backgroundColor);
  await anon.screenshot({ path: shots + '/p5-light.png', fullPage: true });
  await anon.emulateMedia({ colorScheme: 'dark' });
  const dark = await t1.evaluate((el) => getComputedStyle(el.querySelector('tbody tr')).backgroundColor);
  check('dark mode switches colors', dark === 'rgb(30, 30, 30)' || dark === 'rgb(23, 59, 36)', light + ' → ' + dark);
  const legendColor = await t1.evaluate((el) => getComputedStyle(el.querySelector('.ilt-legend')).color);
  check('dark mode keeps legend readable on the theme background', legendColor === light || !/rgb\((2[0-9]{2}), (2[0-9]{2}), (2[0-9]{2})\)/.test(legendColor), legendColor);
  await anon.screenshot({ path: shots + '/p5-dark.png', fullPage: true });

  check('no JS errors', errs.length === 0, errs.join(' | '));
  await browser.close();
  const failed = results.filter((r) => !r).length;
  console.log(failed ? `\n${failed} FAILED` : '\nAll Phase 5 checks passed');
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
