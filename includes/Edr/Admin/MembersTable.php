<?php

// Forbid direct access.
if ( ! defined( 'ABSPATH' ) ) exit();

// Load the WP_List_Table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Members list table.
 */
class Edr_Admin_MembersTable extends WP_List_Table {
	/**
	 * @var array
	 */
	protected $user_memberships = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'ajax' => false,
		) );
	}

	/**
	 * Cache user memberships data.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_membership( $user_id ) {
		if ( ! array_key_exists( $user_id, $this->user_memberships ) ) {
			$edr_memberships = Edr_Memberships::get_instance();
			$this->user_memberships[ $user_id ] = $edr_memberships->get_user_membership_by( 'user_id', $user_id );
		}

		return $this->user_memberships[ $user_id ];
	}

	/**
	 * Display the filters form.
	 */
	public function display_member_filters() {
		?>
		<div class="edr-table-nav top">
			<form class="edr-admin-filters" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
				<input type="hidden" name="page" value="edr_admin_members">
				<div class="block">
					<label for="search-member-name"><?php _e( 'Search Members', 'educator' ); ?></label>
					<input type="text" id="search-member-name" name="member_name" value="<?php if ( ! empty( $_GET['member_name'] ) ) echo esc_attr( $_GET['member_name'] ); ?>">
				</div>
				<div class="block">
					<input type="submit" class="button" value="<?php _e( 'Search', 'educator' ); ?>">
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Define columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'username'         => __( 'User', 'educator' ),
			'name'             => __( 'Name', 'educator' ),
			'membership_level' => __( 'Membership Level', 'educator' ),
			'status'           => __( 'Status', 'educator' ),
			'expiration_date'  => __( 'Expiration Date', 'educator' ),
		);

		return $columns;
	}

	/**
	 * Column: username.
	 *
	 * @param WP_User $user
	 * @return string
	 */
	public function column_username( $user ) {
		$full_name = $user->first_name;
		$full_name .= ( $user->last_name ) ? ' ' . $user->last_name : '';

		if ( ! $full_name ) {
			$full_name = $user->display_name;
		}

		$edit_url = admin_url( 'admin.php?page=edr_admin_members&edr-action=edit-member&id=' . $user->ID );

		$actions = array();
		$actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'educator' ) . '</a>';

		return '<strong>' . esc_html( $full_name ) . '</strong>' . $this->row_actions( $actions );
	}

	/**
	 * Column: name.
	 *
	 * @param WP_User $user
	 * @return string
	 */
	public function column_name( $user ) {
		return esc_html( $user->display_name );
	}

	/**
	 * Column: membership_level.
	 *
	 * @param WP_User $user
	 * @return string
	 */
	public function column_membership_level( $user ) {
		$user_membership = $this->get_user_membership( $user->ID );

		if ( $user_membership['membership_id'] ) {
			return get_the_title( $user_membership['membership_id'] );
		}

		return '';
	}

	/**
	 * Column: status.
	 *
	 * @param WP_User $user
	 * @return string
	 */
	public function column_status( $user ) {
		$statuses = Edr_Memberships::get_instance()->get_statuses();
		$user_membership = $this->get_user_membership( $user->ID );

		if ( array_key_exists( $user_membership['status'], $statuses ) ) {
			return esc_html( $statuses[ $user_membership['status'] ] );
		}

		return '';
	}

	/**
	 * Column: expiration_date.
	 *
	 * @param WP_User $user
	 * @return string
	 */
	public function column_expiration_date( $user ) {
		$user_membership = $this->get_user_membership( $user->ID );

		if ( ! empty( $user_membership['expiration'] ) ) {
			return esc_html( date_i18n( get_option( 'date_format' ), $user_membership['expiration'] ) );
		}

		return __( 'None', 'educator' );
	}

	/**
	 * Prepare items.
	 * Fetch and setup members.
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$args = array();
		$args['number'] = $this->get_items_per_page( 'members_per_page', 10 );
		$args['offset'] = ( $this->get_pagenum() - 1 ) * $args['number'];

		if ( ! empty( $_GET['member_name'] ) ) {
			$args['search'] = '*' . $_GET['member_name'] . '*';
		}

		$edr_memberships = Edr_Memberships::get_instance();
		$user_query = $edr_memberships->get_members( $args );

		if ( ! empty( $user_query->results ) ) {
			$this->set_pagination_args( array(
				'total_items' => $user_query->total_users,
				'per_page'    => $args['number'],
			) );

			$this->items = $user_query->results;
		}
	}
}
