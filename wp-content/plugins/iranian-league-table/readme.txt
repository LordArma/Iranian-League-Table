=== Iranian League Table ===
Contributors: lordarma
Tags: football, soccer, league table, standings, persian
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 4.5.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Persian Gulf Pro League, Azadegan and Kowsar standings in Persian, as a block, widget or shortcode, with a visual Shortcode Builder.

== Description ==

Iranian League Table shows the standings of Iranian football leagues in Persian (RTL), using data from [Varzesh3](https://www.varzesh3.com/):

* Persian Gulf Pro League (لیگ برتر خلیج فارس)
* League One / Azadegan (لیگ یک)
* Women's League / Kowsar (لیگ بانوان)

**Ways to add a table**

* **League Table block** (easiest, recommended): add it in the block editor or a block-based widget area and set the options in the sidebar. No shortcode needed.
* **Shortcode Builder** (League Table → Shortcode Builder) for the classic editor, classic widgets and page builders: pick options, see a live preview, copy the shortcode.
* **Widget** for classic widget areas.
* **Shortcode** `[iran_league]` anywhere shortcodes work.

**Features**

* Basic or advanced columns, team logos, Persian or Latin digits, your own colors and sizes, ready-made color presets (including Persian Gulf, Esteghlal, Persepolis and Damash).
* Saved tables: reuse one configuration everywhere with `[iran_league id="12"]` and restyle all pages at once.
* Highlight the top and bottom places with a legend, highlight your team, show only the top N rows or the rows around a team.
* "Last updated" line in the Persian calendar, optional links to Varzesh3, automatic dark mode.
* Fast: data and logos are cached and refreshed in the background, so visitors never wait for Varzesh3.
* Structured data (schema.org) for search engines, on by default (League Table → Settings).
* Responsive: narrow tables hide less important columns instead of scrolling.
* Fully translated into Persian.

== Installation ==

1. Upload the plugin zip in Plugins → Add New → Upload Plugin, or copy the `iranian-league-table` folder to `wp-content/plugins/`.
2. Activate it.
3. In the block editor, add the League Table block to a post or page (recommended). For the classic editor, use League Table → Shortcode Builder.

== Frequently Asked Questions ==

= Which shortcode attributes are there? =

League Table → Help lists every attribute with its default value. Common ones: `league` (persiangulf, azadegan, kowsar), `mode` (basic, advanced), `logo`, `farsi_numbers`, colors, sizes, `rows`, `around`, `highlight`, `highlight_top`, `highlight_bottom`.

= How often is the data updated? =

Every 5 minutes by default (League Table → Settings). Updates run in the background with WP-Cron. If your site disables WP-Cron, make sure a real cron job calls `wp-cron.php`.

= The table shows "temporarily unavailable" =

Your server could not reach Varzesh3 yet. Check League Table → Settings → Data status for the last error, then click "Refresh now".

= Can I change the look from my theme? =

Yes. Override the CSS custom properties on `.ilt` (for example `--ilt-head-bg`), or copy `templates/table.php` to `your-theme/iranian-league-table/table.php`.

== Screenshots ==

1. The Shortcode Builder with live preview (Persian admin).
2. The League Table block in the block editor.
3. A table with highlighted zones, a highlighted team, legend and update time, plus a short "around a team" table.
4. Settings: refresh interval, data status and diagnostics.

== Changelog ==

= 4.5.0 =
* Help and readme recommend the League Table block as the easiest way to add a table; new screenshots.
* Build tools updated (@wordpress/scripts 36, @wordpress/env 11) to fix the security alerts in development dependencies. Nothing in the plugin itself changed for this; the release zip never included them.

= 4.4.1 =
* New "Damash" color preset (blue and red).
* Structured data is now on by default. Sites that already saved Settings keep their choice.
* The old help page under Settings → League Table is removed; help is in League Table → Help.

= 4.4.0 =
* Updates from GitHub releases, text domain renamed to the plugin slug, readme.txt.

= 4.3.0 =
* Zones with legend, highlight a team, rows and around, update time, team links, dark mode, structured data.

= 4.2.0 =
* League Table block, shortcode-to-block conversion, responsive columns, contrast warnings, better widget colors.

= 4.1.0 =
* Shortcode Builder, saved tables, Settings page, classic editor button.

= 4.0.0 =
* Rewritten internals, background caching, logos in uploads, theme-overridable template.

= 3.2.1 =
* Security and reliability fixes.

== Upgrade Notice ==

= 4.5.0 =
Block editor is now the recommended way to add a table; development dependencies updated.

= 4.4.1 =
Damash color preset; structured data on by default; the old Settings → League Table help page is removed.

= 4.4.0 =
Adds automatic updates from GitHub releases.
