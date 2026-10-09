<?php
/**
 * Saved Tables admin list.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lists saved tables with their shortcode, usage count and Edit / Duplicate / Delete actions.
 */
final class ILT_Tables_List extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'ilt-table',
				'plural'   => 'ilt-tables',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Renders the page.
	 */
	public static function render_page() {
		$list = new self();
		$list->prepare_items();
		?>
		<div class="wrap ilt-admin">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Saved Tables', 'iranian-league-table' ); ?></h1>
			<a href="<?php echo esc_url( ILT_Admin::url( ILT_Admin::MENU_SLUG ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Table', 'iranian-league-table' ); ?></a>
			<hr class="wp-header-end">
			<?php ILT_Admin::render_notice(); ?>
			<p class="description"><?php esc_html_e( 'Change a saved table and every page that uses its shortcode is restyled at once.', 'iranian-league-table' ); ?></p>
			<?php $list->display(); ?>
			<span class="ilt-toast" id="ilt-toast" role="status" aria-live="polite"></span>
		</div>
		<?php
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'title'     => __( 'Name', 'iranian-league-table' ),
			'league'    => __( 'League', 'iranian-league-table' ),
			'shortcode' => __( 'Shortcode', 'iranian-league-table' ),
			'usage'     => __( 'Used in', 'iranian-league-table' ),
		);
	}

	/**
	 * Loads items.
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), array(), 'title' );
		$this->items           = ILT_Tables::all();
	}

	/**
	 * Empty state.
	 */
	public function no_items() {
		esc_html_e( 'No saved tables yet. Build one in the Shortcode Builder and click "Save as table".', 'iranian-league-table' );
	}

	/**
	 * Name column with row actions.
	 *
	 * @param array $item Saved table.
	 * @return string
	 */
	public function column_title( $item ) {
		$edit    = ILT_Admin::url( ILT_Admin::MENU_SLUG, array( 'table' => $item['id'] ) );
		$actions = array();
		if ( current_user_can( 'edit_post', $item['id'] ) ) {
			$actions['edit']      = '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'iranian-league-table' ) . '</a>';
			$actions['duplicate'] = '<a href="' . esc_url( self::action_url( 'duplicate', $item['id'] ) ) . '">' . esc_html__( 'Duplicate', 'iranian-league-table' ) . '</a>';
		}
		if ( current_user_can( 'delete_post', $item['id'] ) ) {
			$actions['delete'] = '<a class="ilt-delete" href="' . esc_url( self::action_url( 'delete', $item['id'] ) ) . '">' . esc_html__( 'Delete', 'iranian-league-table' ) . '</a>';
		}
		return '<strong><a class="row-title" href="' . esc_url( $edit ) . '">' . esc_html( $item['title'] ) . '</a></strong>' . $this->row_actions( $actions );
	}

	/**
	 * League column.
	 *
	 * @param array $item Saved table.
	 * @return string
	 */
	public function column_league( $item ) {
		$mode = 'advanced' === $item['values']['mode'] ? __( 'Advanced', 'iranian-league-table' ) : __( 'Basic', 'iranian-league-table' );
		return esc_html( ILT_Leagues::label( $item['values']['league'] ) ) . '<br><small>' . esc_html( $mode ) . '</small>';
	}

	/**
	 * Shortcode column with a copy button.
	 *
	 * @param array $item Saved table.
	 * @return string
	 */
	public function column_shortcode( $item ) {
		$code = ILT_Attributes::to_shortcode( $item['values'], $item['id'], $item['values'] );
		return '<code dir="ltr" class="ilt-shortcode">' . esc_html( $code ) . '</code> <button type="button" class="button button-small ilt-copy" data-shortcode="' . esc_attr( $code ) . '">' . esc_html__( 'Copy', 'iranian-league-table' ) . '</button>';
	}

	/**
	 * Usage column.
	 *
	 * @param array $item Saved table.
	 * @return string
	 */
	public function column_usage( $item ) {
		$usage = ILT_Tables::usage();
		$count = isset( $usage[ $item['id'] ] ) ? (int) $usage[ $item['id'] ] : 0;
		/* translators: %s: number of posts. */
		return esc_html( sprintf( _n( '%s post', '%s posts', $count, 'iranian-league-table' ), number_format_i18n( $count ) ) );
	}

	/**
	 * Nonce-protected action URL.
	 *
	 * @param string $action duplicate|delete.
	 * @param int    $id     Saved table id.
	 * @return string
	 */
	private static function action_url( $action, $id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'ilt_table_action',
					'do'     => $action,
					'id'     => $id,
				),
				admin_url( 'admin-post.php' )
			),
			'ilt_table_' . $action . '_' . $id
		);
	}

	/**
	 * Handles admin-post Duplicate / Delete.
	 */
	public static function handle_action() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified with check_admin_referer() below.
		$do = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		// phpcs:enable
		if ( ! in_array( $do, array( 'duplicate', 'delete' ), true ) || ! $id ) {
			wp_die( esc_html__( 'Invalid request.', 'iranian-league-table' ), '', array( 'response' => 400 ) );
		}
		check_admin_referer( 'ilt_table_' . $do . '_' . $id );

		$notice = 'failed';
		if ( 'duplicate' === $do && current_user_can( 'edit_post', $id ) && current_user_can( 'edit_posts' ) ) {
			$notice = is_wp_error( ILT_Tables::duplicate( $id ) ) ? 'failed' : 'duplicated';
		} elseif ( 'delete' === $do && current_user_can( 'delete_post', $id ) ) {
			$notice = ILT_Tables::delete( $id ) ? 'deleted' : 'failed';
		} else {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'iranian-league-table' ), '', array( 'response' => 403 ) );
		}
		wp_safe_redirect( ILT_Admin::url( ILT_Admin::TABLES_SLUG, array( 'ilt_notice' => $notice ) ) );
		exit;
	}
}
