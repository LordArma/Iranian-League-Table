// Legacy widget on the classic Widgets screen: color pickers, presets, save, front end.
// Needs a theme with sidebar-1, the Classic Widgets plugin, and a League Table widget in sidebar-1.
const { chromium } = require(process.env.PW_PATH || 'playwright');
const BASE = process.env.ILT_BASE || 'http://localhost:8089';
const shots = process.argv[2];
const results = [];
const check = (name, ok, extra = '') => { results.push(ok); console.log((ok ? '  ok   ' : '  FAIL ') + name + (extra ? ' — ' + extra : '')); };
(async () => {
  const browser = await chromium.launch();
  const page = await (await browser.newContext({ viewport: { width: 1300, height: 1000 } })).newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message));
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', 'iltadmin');
  await page.fill('#user_pass', 'ilt-test-pass-123');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
  await page.goto(BASE + '/wp-admin/widgets.php');
  const widget = page.locator('#widgets-right .widget[id*="iranianleaguetable_widget"]').first();
  await widget.locator('.widget-top').click();
  await widget.locator('.widget-inside').waitFor();
  check('color pickers initialized', await widget.locator('.wp-picker-container').count() === 5);
  check('form keeps saved league', await widget.locator('select[name*="[league]"]').inputValue() === 'azadegan');
  await widget.locator('.ilt-widget-preset').selectOption('persepolis');
  check('preset fills colors', await widget.locator('input.ilt-widget-color[data-ilt-key="title_backcolor"]').inputValue() === '#c8102e');
  await page.screenshot({ path: shots + '/widget-form.png' });
  await widget.locator('input[type=submit].widget-control-save').click();
  await page.waitForFunction((id) => !document.querySelector('#' + id + ' .spinner.is-active'), await widget.getAttribute('id'));
  await page.waitForTimeout(1000);
  const front = await (await browser.newContext()).newPage();
  await front.goto(BASE + '/');
  const style = await front.locator('.widget .ilt').first().getAttribute('style').catch(() => '');
  check('front-end widget uses saved preset', /--ilt-head-bg:#c8102e;/.test(style || ''), style);
  check('no JS errors', errs.length === 0, errs.join(' | '));
  await browser.close();
  const failed = results.filter((r) => !r).length;
  console.log(failed ? `\n${failed} FAILED` : '\nAll widget checks passed');
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
