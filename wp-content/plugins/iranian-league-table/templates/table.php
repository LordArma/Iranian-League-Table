<?php
/**
 * League standings table.
 *
 * Copy this file to {your-theme}/iranian-league-table/table.php to customize the markup.
 * Escape everything you output.
 *
 * @package Iranian_League_Table
 *
 * @var array $ilt {
 *     @type string $league        Canonical league slug.
 *     @type string $title         League name.
 *     @type bool   $advanced      Show wins/draws/losses/goals/difference columns.
 *     @type bool   $show_logo     Show team logos.
 *     @type int    $logo_size     Logo size in px.
 *     @type string $wrapper_class CSS classes for the wrapper.
 *     @type string $style         CSS custom properties for the wrapper.
 *     @type array  $rows          Each: rank, name, link, logo, class, played, wins, draws, losses, goals, diff, points
 *                                 (display strings; `link` and `logo` may be empty; `class` holds ilt-row--top/bottom/highlight).
 *     @type array  $legend        Zone legend items: [ class => top|bottom, label ].
 *     @type string $updated       "Last updated" date text, or ''.
 *     @type string $source        Varzesh3 URL for the source link, or ''.
 *     @type array  $values        Normalized display options.
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="<?php echo esc_attr( $ilt['wrapper_class'] ); ?>" dir="rtl" style="<?php echo esc_attr( $ilt['style'] ); ?>">
	<div class="il-table-holder">
		<table class="ilt-standing">
			<caption class="ilt-caption"><?php echo esc_html( $ilt['title'] ); ?></caption>
			<thead>
				<tr>
					<th scope="col" class="ilt-col-rank">رتبه</th>
					<th scope="col" class="ilt-col-team">تیم</th>
					<th scope="col" class="ilt-col-played">بازی</th>
					<?php if ( $ilt['advanced'] ) : ?>
						<th scope="col" class="in-detailed ilt-col-wins">برد</th>
						<th scope="col" class="in-detailed ilt-col-draws">مساوی</th>
						<th scope="col" class="in-detailed ilt-col-losses">باخت</th>
						<th scope="col" class="in-detailed ilt-col-goals">گل‌ها</th>
						<th scope="col" class="in-detailed ilt-col-diff">تفاضل</th>
					<?php endif; ?>
					<th scope="col" class="ilt-col-points">امتیاز</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ilt['rows'] as $ilt_row ) : ?>
					<tr<?php echo '' !== $ilt_row['class'] ? ' class="' . esc_attr( $ilt_row['class'] ) . '"' : ''; ?>>
						<td class="ilt-rank"><?php echo esc_html( $ilt_row['rank'] ); ?></td>
						<th scope="row" class="ilt-team">
							<?php if ( $ilt['show_logo'] && '' !== $ilt_row['logo'] ) : ?>
								<img class="ilt-logo" src="<?php echo esc_url( $ilt_row['logo'] ); ?>" alt="<?php echo esc_attr( $ilt_row['name'] ); ?>" width="<?php echo (int) $ilt['logo_size']; ?>" height="<?php echo (int) $ilt['logo_size']; ?>" loading="lazy" decoding="async">
							<?php endif; ?>
							<?php if ( '' !== $ilt_row['link'] ) : ?>
								<a class="ilt-team-name" href="<?php echo esc_url( $ilt_row['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $ilt_row['name'] ); ?></a>
							<?php else : ?>
								<span class="ilt-team-name"><?php echo esc_html( $ilt_row['name'] ); ?></span>
							<?php endif; ?>
						</th>
						<td class="ilt-played"><?php echo esc_html( $ilt_row['played'] ); ?></td>
						<?php if ( $ilt['advanced'] ) : ?>
							<td class="in-detailed ilt-wins"><?php echo esc_html( $ilt_row['wins'] ); ?></td>
							<td class="in-detailed ilt-draws"><?php echo esc_html( $ilt_row['draws'] ); ?></td>
							<td class="in-detailed ilt-losses"><?php echo esc_html( $ilt_row['losses'] ); ?></td>
							<td class="in-detailed ilt-goals"><span dir="ltr"><?php echo esc_html( $ilt_row['goals'] ); ?></span></td>
							<td class="in-detailed ilt-diff"><span dir="ltr"><?php echo esc_html( $ilt_row['diff'] ); ?></span></td>
						<?php endif; ?>
						<td class="ilt-points"><?php echo esc_html( $ilt_row['points'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ( $ilt['legend'] ) : ?>
		<ul class="ilt-legend">
			<?php foreach ( $ilt['legend'] as $ilt_item ) : ?>
				<li class="ilt-legend__item ilt-legend__item--<?php echo esc_attr( $ilt_item['class'] ); ?>"><span class="ilt-legend__swatch" aria-hidden="true"></span><?php echo esc_html( $ilt_item['label'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( '' !== $ilt['updated'] || '' !== $ilt['source'] ) : ?>
		<p class="ilt-footer">
			<?php if ( '' !== $ilt['updated'] ) : ?>
				<span class="ilt-updated">به‌روزرسانی: <?php echo esc_html( $ilt['updated'] ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $ilt['source'] ) : ?>
				<span class="ilt-source">منبع: <a href="<?php echo esc_url( $ilt['source'] ); ?>" target="_blank" rel="noopener">ورزش سه</a></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
