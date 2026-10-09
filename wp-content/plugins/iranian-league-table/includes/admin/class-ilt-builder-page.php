<?php
/**
 * Shortcode Builder page.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form generated from the attribute schema, live preview, generated shortcode, import and saving.
 */
final class ILT_Builder_Page {

	/**
	 * Saved table being edited (?table=N), if the user may edit it.
	 *
	 * @return array|null
	 */
	public static function current_table() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only selection of what to edit; capability checked below.
		$id = isset( $_GET['table'] ) ? absint( $_GET['table'] ) : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			return null;
		}
		return ILT_Tables::get( $id );
	}

	/**
	 * Data for assets/js/builder.js.
	 *
	 * @return array
	 */
	public static function script_data() {
		$table = self::current_table();
		return array(
			'schema'   => ILT_Attributes::for_js(),
			'defaults' => ILT_Attributes::defaults(),
			'presets'  => ILT_Presets::all(),
			'table'    => $table,
			'modal'    => ILT_Admin::is_modal(),
			'styleUrl' => add_query_arg( 'ver', ILT_VERSION, plugins_url( 'assets/css/style.css', ILT_PLUGIN_FILE ) ),
			'rest'     => array(
				'root'  => esc_url_raw( rest_url( ILT_Rest::NAMESPACE_V1 . '/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			),
			'urls'     => array(
				'builder' => ILT_Admin::url( ILT_Admin::MENU_SLUG ),
			),
			'i18n'     => array(
				'loading'       => __( 'Loading preview…', 'iranian-league-table' ),
				'previewError'  => __( 'The preview could not be loaded.', 'iranian-league-table' ),
				'copied'        => __( 'Copied!', 'iranian-league-table' ),
				'copyFailed'    => __( 'Copy failed. Select the shortcode and copy it manually.', 'iranian-league-table' ),
				'saved'         => __( 'Table saved.', 'iranian-league-table' ),
				'saveFailed'    => __( 'The table could not be saved.', 'iranian-league-table' ),
				'imported'      => __( 'Shortcode imported.', 'iranian-league-table' ),
				'importFailed'  => __( 'The shortcode could not be imported.', 'iranian-league-table' ),
				'resetDone'     => __( 'Options reset to defaults.', 'iranian-league-table' ),
				'presetApplied' => __( 'Preset applied.', 'iranian-league-table' ),
				'unsaved'       => __( 'You have changes that are not saved to this table yet. The shortcode below applies them only where you paste it; click "Update table" to change every page that uses this table.', 'iranian-league-table' ),
				/* translators: %s: saved table name. */
				'editing'       => __( 'Editing saved table: %s', 'iranian-league-table' ),
				'saveAs'        => __( 'Save as table', 'iranian-league-table' ),
				'update'        => __( 'Update table', 'iranian-league-table' ),
				'namePrompt'    => __( 'Table name', 'iranian-league-table' ),
				'insertMissing' => __( 'Could not reach the editor. Copy the shortcode instead.', 'iranian-league-table' ),
				/* translators: 1: table part (Header, Odd rows, Even rows), 2: contrast ratio such as 2.8. */
				'lowContrast'   => __( '%1$s: text may be hard to read (contrast %2$s:1; at least 4.5:1 is recommended).', 'iranian-league-table' ),
				'header'        => __( 'Header', 'iranian-league-table' ),
				'oddRows'       => __( 'Odd rows', 'iranian-league-table' ),
				'evenRows'      => __( 'Even rows', 'iranian-league-table' ),
			),
		);
	}

	/**
	 * Renders the page.
	 */
	public static function render() {
		$table    = self::current_table();
		$values   = $table ? $table['values'] : ILT_Attributes::defaults();
		$schema   = ILT_Attributes::schema();
		$sections = array(
			'content' => __( 'Content', 'iranian-league-table' ),
			'zones'   => __( 'Zones', 'iranian-league-table' ),
			'colors'  => __( 'Colors', 'iranian-league-table' ),
			'sizes'   => __( 'Typography & sizes', 'iranian-league-table' ),
		);
		?>
		<div class="wrap ilt-admin ilt-builder-wrap">
			<h1><?php esc_html_e( 'Shortcode Builder', 'iranian-league-table' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Choose the options, check the preview, then copy the shortcode into a post, page or text widget. Save it as a table to reuse it everywhere with a short shortcode.', 'iranian-league-table' ); ?></p>

			<div class="notice notice-info inline ilt-editing" <?php echo $table ? '' : 'hidden'; ?>>
				<p class="ilt-editing__text">
					<?php
					if ( $table ) {
						/* translators: %s: saved table name. */
						echo esc_html( sprintf( __( 'Editing saved table: %s', 'iranian-league-table' ), $table['title'] ) );
					}
					?>
				</p>
				<p class="ilt-editing__unsaved" hidden></p>
			</div>

			<div class="ilt-builder">
				<form class="ilt-builder__form" id="ilt-builder-form" novalidate>
					<?php foreach ( $sections as $section => $section_label ) : ?>
						<details class="ilt-section" open>
							<summary><?php echo esc_html( $section_label ); ?></summary>
							<div class="ilt-section__body">
								<?php if ( 'colors' === $section ) : ?>
									<div class="ilt-field">
										<label class="ilt-field__label" for="ilt-preset"><?php esc_html_e( 'Theme preset', 'iranian-league-table' ); ?></label>
										<select id="ilt-preset">
											<option value=""><?php esc_html_e( '— Choose a preset —', 'iranian-league-table' ); ?></option>
											<?php foreach ( ILT_Presets::all() as $id => $preset ) : ?>
												<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $preset['label'] ); ?></option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'Fills all color fields at once. You can still adjust each color afterwards.', 'iranian-league-table' ); ?></p>
									</div>
								<?php endif; ?>
								<?php
								foreach ( $schema as $key => $field ) {
									if ( $field['section'] === $section ) {
										self::render_field( $key, $field, $values[ $key ] );
									}
								}
								?>
								<?php if ( 'colors' === $section ) : ?>
									<div class="ilt-contrast notice notice-warning inline" id="ilt-contrast" role="status" aria-live="polite" hidden></div>
								<?php endif; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</form>

				<section class="ilt-builder__preview" aria-labelledby="ilt-preview-title">
					<h2 id="ilt-preview-title"><?php esc_html_e( 'Preview', 'iranian-league-table' ); ?></h2>
					<div class="ilt-preview" id="ilt-preview" aria-live="polite" aria-busy="true"></div>
					<p class="description"><?php esc_html_e( 'The preview uses the same code and live data as your site. Your theme\'s fonts may make it look slightly different.', 'iranian-league-table' ); ?></p>
				</section>
			</div>

			<section class="ilt-output" aria-labelledby="ilt-output-title">
				<h2 id="ilt-output-title"><label for="ilt-shortcode"><?php esc_html_e( 'Shortcode', 'iranian-league-table' ); ?></label></h2>
				<div class="ilt-output__bar">
				<textarea id="ilt-shortcode" class="code ilt-output__code" dir="ltr" rows="2" readonly></textarea>
				<div class="ilt-output__actions">
					<?php if ( ILT_Admin::is_modal() ) : ?>
						<button type="button" class="button button-primary" id="ilt-insert"><?php esc_html_e( 'Insert into post', 'iranian-league-table' ); ?></button>
					<?php endif; ?>
					<button type="button" class="button <?php echo ILT_Admin::is_modal() ? '' : 'button-primary'; ?>" id="ilt-copy"><?php esc_html_e( 'Copy shortcode', 'iranian-league-table' ); ?></button>
					<button type="button" class="button" id="ilt-reset"><?php esc_html_e( 'Reset to defaults', 'iranian-league-table' ); ?></button>
					<span class="ilt-toast" id="ilt-toast" role="status" aria-live="polite"></span>
				</div>
				</div>

				<div class="ilt-output__grid">
					<div class="ilt-card">
						<h3><?php esc_html_e( 'Save as a reusable table', 'iranian-league-table' ); ?></h3>
						<p class="description"><?php esc_html_e( 'A saved table gets a short shortcode like [iran_league id="12"]. Change the saved table later and every page that uses it is restyled at once.', 'iranian-league-table' ); ?></p>
						<p>
							<label for="ilt-table-title"><?php esc_html_e( 'Table name', 'iranian-league-table' ); ?></label><br>
							<input type="text" id="ilt-table-title" class="regular-text" value="<?php echo esc_attr( $table ? $table['title'] : '' ); ?>">
						</p>
						<p>
							<button type="button" class="button button-secondary" id="ilt-save"><?php echo $table ? esc_html__( 'Update table', 'iranian-league-table' ) : esc_html__( 'Save as table', 'iranian-league-table' ); ?></button>
							<button type="button" class="button-link" id="ilt-save-new" <?php echo $table ? '' : 'hidden'; ?>><?php esc_html_e( 'Save as a new table', 'iranian-league-table' ); ?></button>
							<a class="button-link" href="<?php echo esc_url( ILT_Admin::url( ILT_Admin::TABLES_SLUG ) ); ?>"><?php esc_html_e( 'All saved tables', 'iranian-league-table' ); ?></a>
						</p>
					</div>
					<div class="ilt-card">
						<h3><label for="ilt-import"><?php esc_html_e( 'Edit an existing shortcode', 'iranian-league-table' ); ?></label></h3>
						<p class="description"><?php esc_html_e( 'Paste a shortcode from one of your pages to load its options into the form.', 'iranian-league-table' ); ?></p>
						<textarea id="ilt-import" class="code large-text" dir="ltr" rows="2" placeholder='[iran_league league="azadegan" mode="advanced"]'></textarea>
						<p><button type="button" class="button" id="ilt-import-button"><?php esc_html_e( 'Import', 'iranian-league-table' ); ?></button></p>
					</div>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Renders one form field.
	 *
	 * @param string $key   Attribute name.
	 * @param array  $field Schema entry.
	 * @param mixed  $value Current value.
	 */
	private static function render_field( $key, $field, $value ) {
		$id = 'ilt-field-' . $key;
		echo '<div class="ilt-field ilt-field--' . esc_attr( $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'league':
				echo '<fieldset><legend class="ilt-field__label">' . esc_html( $field['label'] ) . '</legend><div class="ilt-cards">';
				foreach ( $field['choices'] as $choice => $label ) {
					printf(
						'<label class="ilt-card-choice"><input type="radio" name="%1$s" value="%2$s" data-ilt-key="%1$s"%3$s> <span>%4$s</span></label>',
						esc_attr( $key ),
						esc_attr( $choice ),
						checked( $value, $choice, false ),
						esc_html( $label )
					);
				}
				echo '</div></fieldset>';
				break;

			case 'enum':
				echo '<fieldset><legend class="ilt-field__label">' . esc_html( $field['label'] ) . '</legend><div class="ilt-segmented">';
				foreach ( $field['choices'] as $choice => $label ) {
					printf(
						'<label><input type="radio" name="%1$s" value="%2$s" data-ilt-key="%1$s"%3$s> <span>%4$s</span></label>',
						esc_attr( $key ),
						esc_attr( $choice ),
						checked( $value, $choice, false ),
						esc_html( $label )
					);
				}
				echo '</div>';
				if ( '' !== $field['help'] ) {
					echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
				}
				echo '</fieldset>';
				break;

			case 'bool':
				printf(
					'<label class="ilt-toggle" for="%1$s"><input type="checkbox" class="ilt-toggle__input" role="switch" id="%1$s" data-ilt-key="%2$s"%3$s><span class="ilt-toggle__track" aria-hidden="true"></span><span class="ilt-toggle__label">%4$s</span></label>',
					esc_attr( $id ),
					esc_attr( $key ),
					checked( $value, true, false ),
					esc_html( $field['label'] )
				);
				if ( '' !== $field['help'] ) {
					echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
				}
				break;

			case 'int':
				printf(
					'<label class="ilt-field__label" for="%1$s">%2$s</label><div class="ilt-range"><input type="range" min="%3$d" max="%4$d" step="1" value="%5$d" data-ilt-range="%6$s" aria-hidden="true" tabindex="-1"><input type="number" class="small-text" id="%1$s" min="%3$d" max="%4$d" step="1" value="%5$d" data-ilt-key="%6$s"></div>',
					esc_attr( $id ),
					esc_html( $field['label'] ),
					(int) $field['min'],
					(int) $field['max'],
					(int) $value,
					esc_attr( $key )
				);
				if ( '' !== $field['help'] ) {
					echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
				}
				break;

			case 'text':
				printf(
					'<label class="ilt-field__label" for="%1$s">%2$s</label><input type="text" class="regular-text" id="%1$s" value="%3$s" data-ilt-key="%4$s" maxlength="60">',
					esc_attr( $id ),
					esc_html( $field['label'] ),
					esc_attr( $value ),
					esc_attr( $key )
				);
				if ( '' !== $field['help'] ) {
					echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
				}
				break;

			case 'color':
				printf(
					'<label class="ilt-field__label" for="%1$s">%2$s</label><input type="text" class="ilt-color" id="%1$s" value="%3$s" data-ilt-key="%4$s" data-default-color="%5$s" dir="ltr">',
					esc_attr( $id ),
					esc_html( $field['label'] ),
					esc_attr( $value ),
					esc_attr( $key ),
					esc_attr( $field['default'] )
				);
				break;
		}
		echo '</div>';
	}
}
