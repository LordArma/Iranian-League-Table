<?php
/**
 * Legacy widget.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- The class name is kept for the_widget() calls in themes.

/**
 * Widget showing a league table. Keeps the `iranianleaguetable_widget` id and instance keys,
 * so widgets saved by any earlier version keep working.
 */
class Iranian_League_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'iranianleaguetable_widget',
			esc_html__( 'Iranian League Table', 'iranian-league-table' ),
			array(
				'description'                 => esc_html__( 'Widget to display Iranian leagues table', 'iranian-league-table' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme markup.

		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		if ( '' !== (string) $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme markup.
		}

		echo ILT_Renderer::render_league( ILT_Attributes::from_widget( $instance ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template.

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme markup.
	}

	/**
	 * Back-end widget form, generated from the attribute schema.
	 *
	 * @param array $instance Previously saved values from database.
	 * @return string
	 */
	public function form( $instance ) {
		$title  = isset( $instance['title'] ) ? $instance['title'] : esc_html__( 'Persian Gulf League', 'iranian-league-table' );
		$values = ILT_Attributes::from_widget( (array) $instance );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'iranian-league-table' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
		$preset_shown = false;
		foreach ( ILT_Attributes::schema() as $key => $field ) {
			if ( 'color' === $field['type'] && ! $preset_shown ) {
				$preset_shown = true;
				$presets      = array();
				foreach ( ILT_Presets::all() as $preset_id => $preset ) {
					$presets[ $preset_id ] = $preset['values'];
				}
				echo '<p><label for="' . esc_attr( $this->get_field_id( 'ilt_preset' ) ) . '">' . esc_html__( 'Theme preset', 'iranian-league-table' ) . '</label> ';
				// No name attribute: the preset only fills the color fields below and is not saved itself.
				echo '<select class="widefat ilt-widget-preset" id="' . esc_attr( $this->get_field_id( 'ilt_preset' ) ) . '" data-presets="' . esc_attr( wp_json_encode( $presets ) ) . '">';
				echo '<option value="">' . esc_html__( '— Choose a preset —', 'iranian-league-table' ) . '</option>';
				foreach ( ILT_Presets::all() as $preset_id => $preset ) {
					echo '<option value="' . esc_attr( $preset_id ) . '">' . esc_html( $preset['label'] ) . '</option>';
				}
				echo '</select></p>';
			}
			$id    = $this->get_field_id( $field['widget_key'] );
			$name  = $this->get_field_name( $field['widget_key'] );
			$value = $values[ $key ];
			echo '<p>';
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label> ';
			switch ( $field['type'] ) {
				case 'league':
				case 'enum':
					echo '<select class="widefat" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
					foreach ( $field['choices'] as $choice => $label ) {
						echo '<option value="' . esc_attr( $choice ) . '"' . selected( $value, $choice, false ) . '>' . esc_html( $label ) . '</option>';
					}
					echo '</select>';
					break;
				case 'bool':
					echo '<select class="widefat" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
					echo '<option value="true"' . selected( $value, true, false ) . '>' . esc_html__( 'Yes', 'iranian-league-table' ) . '</option>';
					echo '<option value="false"' . selected( $value, false, false ) . '>' . esc_html__( 'No', 'iranian-league-table' ) . '</option>';
					echo '</select>';
					break;
				case 'int':
					echo '<input class="widefat" type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . (int) $field['min'] . '" max="' . (int) $field['max'] . '">';
					break;
				case 'text':
					echo '<input class="widefat" type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" maxlength="60">';
					break;
				case 'color':
					echo '<br><input class="ilt-widget-color" type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $field['default'] ) . '" data-ilt-key="' . esc_attr( $key ) . '" dir="ltr">';
					break;
			}
			if ( '' !== $field['help'] ) {
				echo '<br><small>' . esc_html( $field['help'] ) . '</small>';
			}
			echo '</p>';
		}
		return '';
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance      = (array) $new_instance;
		$instance          = ILT_Attributes::to_widget( ILT_Attributes::from_widget( $new_instance ) );
		$instance['title'] = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		return $instance;
	}
}
