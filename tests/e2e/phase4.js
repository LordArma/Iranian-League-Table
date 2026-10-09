// Block, transforms, editor notice, responsive columns, contrast warning, widget color pickers.
// usage: node tests/e2e/phase4.js <shotsdir>   (Classic Editor must be inactive; theme with a sidebar for the widget part)
const { chromium } = require(process.env.PW_PATH || 'playwright');
const BASE = process.env.ILT_BASE || 'http://localhost:8089';
const shots = process.argv[2];
const results = [];
const check = (name, ok, extra = '') => { results.push(ok); console.log((ok ? '  ok   ' : '  FAIL ') + name + (extra ? ' — ' + extra : '')); };

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message));
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', 'iltadmin');
  await page.fill('#user_pass', 'ilt-test-pass-123');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

  // ---- Block editor ----
  await page.goto(BASE + '/wp-admin/post-new.php');
  await page.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.blocks.getBlockType('iranian-league-table/standings'), null, { timeout: 30000 });
  // Close the welcome guide if shown.
  await page.evaluate(() => { try { wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false); } catch (e) {} });
  check('block type registered', await page.evaluate(() => !!wp.blocks.getBlockType('iranian-league-table/standings')));
  check('block title translated/registered', await page.evaluate(() => wp.blocks.getBlockType('iranian-league-table/standings').title), '');

  await page.evaluate(() => {
    const b = wp.blocks.createBlock('iranian-league-table/standings', { options: { league: 'azadegan', mode: 'advanced' } });
    wp.data.dispatch('core/block-editor').insertBlocks(b);
    wp.data.dispatch('core/block-editor').selectBlock(b.clientId);
  });
  const frame = page.frameLocator('iframe[name="editor-canvas"]');
  const ssr = await frame.locator('.wp-block-iranian-league-table-standings table.ilt-standing').first().waitFor({ timeout: 20000 }).then(() => true, () => false);
  check('ServerSideRender shows the table in the editor', ssr);
  check('editor preview is advanced azadegan', ssr && await frame.locator('.ilt--advanced.ilt--azadegan').count() > 0);
  await page.waitForTimeout(500);
  const panelText = await page.locator('.block-editor-block-inspector').innerText().catch(() => '');
  check('inspector shows schema controls', /Saved table/i.test(panelText) && /Display mode/i.test(panelText) && /Show team logos/i.test(panelText), panelText.slice(0, 80).replace(/\n/g, ' | '));
  await page.screenshot({ path: shots + '/block-editor.png' });

  // Toggle via the inspector: logos off.
  await page.locator('.block-editor-block-inspector').getByLabel('Show team logos').click();
  const attrs = await page.evaluate(() => wp.data.select('core/block-editor').getSelectedBlock().attributes);
  check('toggle stores minimal options', JSON.stringify(attrs.options) === JSON.stringify({ league: 'azadegan', mode: 'advanced', logo: false }), JSON.stringify(attrs.options));

  // Transforms.
  const fromShortcodeBlock = await page.evaluate(() => {
    const sc = wp.blocks.createBlock('core/shortcode', { text: '[iran_league league="آزادگان" mode="detailed" farsi_numbers="no" title_font="99"]' });
    const out = wp.blocks.switchToBlockType(sc, 'iranian-league-table/standings');
    return out && out[0] && out[0].attributes;
  });
  check('core/shortcode → block', fromShortcodeBlock && JSON.stringify(fromShortcodeBlock.options) === JSON.stringify({ league: 'azadegan', mode: 'advanced', farsi_numbers: false, title_font: 64 }), JSON.stringify(fromShortcodeBlock));
  const fromRaw = await page.evaluate(() => wp.blocks.rawHandler({ HTML: '<p>[iran_league league="kowsar" id="0"]</p>' }).map((b) => [b.name, b.attributes.options]));
  check('pasted shortcode → block', fromRaw.length === 1 && fromRaw[0][0] === 'iranian-league-table/standings' && fromRaw[0][1].league === 'kowsar', JSON.stringify(fromRaw));

  // Save and view on the front end.
  await page.evaluate(() => wp.data.dispatch('core/editor').editPost({ title: 'Block test', status: 'publish' }));
  await page.evaluate(() => wp.data.dispatch('core/editor').savePost());
  await page.waitForFunction(() => !wp.data.select('core/editor').isSavingPost() && wp.data.select('core/editor').getCurrentPost().status === 'publish', null, { timeout: 20000 });
  const link = await page.evaluate(() => wp.data.select('core/editor').getPermalink());
  await page.goto(link);
  check('front end renders the block', await page.locator('.wp-block-iranian-league-table-standings .ilt--advanced.ilt--azadegan').count() === 1);
  check('front end: logos hidden', await page.locator('.wp-block-iranian-league-table-standings img.ilt-logo').count() === 0);
  check('block style loaded', await page.locator('#ilt-style-css').count() === 1);

  // ---- Responsive container queries ----
  const narrow = await browser.newContext({ viewport: { width: 380, height: 900 } });
  const np = await narrow.newPage();
  await np.goto(link);
  const hidden = await np.evaluate(() => {
    const t = document.querySelector('.ilt');
    const vis = (sel) => getComputedStyle(t.querySelector(sel)).display !== 'none';
    return { width: t.getBoundingClientRect().width, wins: vis('.ilt-wins'), goals: vis('.ilt-goals'), points: vis('.ilt-points'), scroll: t.querySelector('.il-table-holder').scrollWidth > t.querySelector('.il-table-holder').clientWidth };
  });
  check('narrow: W/D/L hidden, points visible, no sideways scroll', !hidden.wins && hidden.points && !hidden.scroll, JSON.stringify(hidden));
  await np.screenshot({ path: shots + '/narrow.png', fullPage: false });
  await narrow.close();

  // ---- Unknown league notice ----
  await page.goto(BASE + '/wp-admin/post-new.php');
  await page.waitForFunction(() => window.wp && wp.apiFetch, null, { timeout: 30000 });
  const pid = await page.evaluate(async () => {
    const r = await wp.apiFetch({ path: '/wp/v2/posts', method: 'POST', data: { title: 'Unknown league', status: 'publish', content: '[iran_league league="xyz"]' } });
    return r.link;
  }).catch(() => null);
  const nonceLink = pid;
  await page.goto(nonceLink);
  check('editor sees unknown-league notice', await page.locator('.ilt-editor-notice').count() === 1);
  const anon = await (await browser.newContext()).newPage();
  await anon.goto(nonceLink);
  check('visitor does not see notice but sees table', await anon.locator('.ilt-editor-notice').count() === 0 && await anon.locator('.ilt--persiangulf').count() === 1);

  // ---- Contrast warning ----
  await page.goto(BASE + '/wp-admin/admin.php?page=ilt-builder');
  await page.waitForSelector('#ilt-contrast', { state: 'attached' });
  check('no warning for defaults', await page.locator('#ilt-contrast').isHidden());
  await page.fill('#ilt-import', '[iran_league title_backcolor="#eeeeee" title_color="#ffffff"]');
  await page.click('#ilt-import-button');
  await page.waitForTimeout(1500);
  const warn = (await page.locator('#ilt-contrast').innerText().catch(() => '')).trim();
  check('low contrast header warned', await page.locator('#ilt-contrast').isVisible() && /Header/.test(warn) && /1\.\d:1/.test(warn), warn);

  check('no JS errors', errs.length === 0, errs.join(' | '));
  await browser.close();
  const failed = results.filter((r) => !r).length;
  console.log(failed ? `\n${failed} FAILED` : '\nAll Phase 4 checks passed');
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
