<?php

// Forbid direct access.
if ( ! defined( 'ABSPATH' ) ) exit();

// Load the WP_List_Table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Payments list table.
 */
class Edr_Admin_PaymentsTable extends WP_List_Table {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'ajax' => false,
		) );

		$this->process_bulk_action();
	}

	/**
	 * Display the filters form.
	 */
	public function display_payment_filters() {
		$types = edr_get_payment_types();
		$statuses = edr_get_payment_statuses();
		$payment_type = isset( $_GET['payment_type'] ) ? $_GET['payment_type'] : '';
		$payment_status = isset( $_GET['status'] ) ? $_GET['status'] : 'pending';
		?>
		<div class="edr-table-nav top">
			<form class="edr-admin-filters" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
				<input type="hidden" name="page" value="edr_admin_payments">
				<div class="block">
					<label for="search-payment-id"><?php echo _x( 'ID', 'ID of an item', 'educator' ); ?></label>
					<input type="text" id="search-payment-id" name="id" value="<?php if ( ! empty( $_GET['id'] ) ) echo intval( $_GET['id'] ); ?>">
				</div>
				<div class="block">
					<label for="search-payment-type"><?php _e( 'Payment Type', 'educator' ); ?></label>
					<select id="search-payment-type" name="payment_type">
						<option value="all"><?php _e( 'All', 'educator' ); ?></option>
						<?php
							foreach ( $types as $t_value => $t_name ) {
								$selected = ( $t_value == $payment_type ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $t_value ) . '"' . $selected . '>' . esc_html( $t_name ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<label for="search-payment-status"><?php _e( 'Status', 'educator' ); ?></label>
					<select id="search-payment-status" name="status">
						<option value="all"><?php _e( 'All', 'educator' ); ?></option>
						<?php
							foreach ( $statuses as $key => $value ) {
								$selected = ( $key == $payment_status ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
							}
						?>
					</select>
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
			'cb'           => '<input type="checkbox">',
			'ID'           => _x( 'ID', 'ID of an item', 'educator' ),
			'item'         => __( 'Item', 'educator' ),
			'payment_type' => __( 'Payment Type', 'educator' ),
			'username'     => __( 'User', 'educator' ),
			'amount'       => __( 'Amount', 'educator' ),
			'method'       => __( 'Method', 'educator' ),
			'status'       => __( 'Status', 'educator' ),
			'date'         => __( 'Date', 'educator' ),
		);

		return $columns;
	}

	/**
	 * Column: cb.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="payment[]" value="' . intval( $item->ID ) . '">';
	}

	/**
	 * Column: ID.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_ID( $item ) {
		$html = intval( $item->ID );

		$base_url = admin_url( 'admin.php?page=edr_admin_payments' );
		$edit_url = admin_url( 'admin.php?page=edr_admin_payments&edr-action=edit-payment&payment_id=' . $item->ID );
		$delete_url = wp_nonce_url( add_query_arg( array( 'edr-action' => 'delete-payment', 'payment_id' => $item->ID ), $base_url ), 'edr_delete_payment' );

		$actions = array();
		$actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'educator' ) . '</a>';
		$actions['delete'] = '<a href="' . esc_url( $delete_url ) . '" class="delete-payment">' . __( 'Delete', 'educator' ) . '</a>';

		$html .= $this->row_actions( $actions );

		return $html;
	}

	/**
	 * Column: item.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_item( $item ) {
		$object_title = '';
		$post = get_post( $item->object_id );

		if ( $post ) {
			$object_title = $post->post_title;
		}

		return esc_html( $object_title );
	}

	/**
	 * Column: payment_type.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_payment_type( $item ) {
		return esc_html( $item->payment_type );
	}

	/**
	 * Column: username.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_username( $item ) {
		$full_name = '';
		$user = get_user_by( 'id', $item->user_id );

		if ( $user ) {
			$full_name = $user->first_name;
			$full_name .= ( $user->last_name ) ? ' ' . $user->last_name : '';

			if ( ! $full_name ) {
				$full_name = $user->display_name;
			}
		}

		return esc_html( $full_name );
	}

	/**
	 * Column: amount.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_amount( $item ) {
		return sanitize_title( $item->currency ) . ' ' . number_format( $item->amount, 2 );
	}

	/**
	 * Column: method.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_method( $item ) {
		return sanitize_title( $item->payment_gateway );
	}

	/**
	 * Column: status.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_status( $item ) {
		return sanitize_title( $item->payment_status );
	}

	/**
	 * Column: date.
	 *
	 * @param Edr_Payment $item
	 * @return string
	 */
	public function column_date( $item ) {
		return date( 'j M, Y H:i', strtotime( $item->payment_date ) );
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'educator' ),
		);

		return $actions;
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
		$nonce_action = 'bulk-' . $this->_args['plural'];

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $nonce_action ) ) {
			return;
		}

		$ids = isset( $_POST['payment'] ) ? $_POST['payment'] : null;

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return;
		}

		$action = $this->current_action();

		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$payment = edr_get_payment( $id );

				if ( $payment->ID ) {
					$payment->delete();
				}
			}
		}
	}

	/**
	 * Prepare items.
	 * Fetch and setup payments(items).
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$statuses = edr_get_payment_statuses();
		$args = array(
			'per_page' => $this->get_items_per_page( 'payments_per_page', 10 ),
			'page'     => $this->get_pagenum(),
		);

		$id = ! empty( $_GET['id'] ) ? $_GET['id'] : 0;
		$status = ! empty( $_GET['status'] ) ? $_GET['status'] : 'pending';
		$type = ! empty( $_GET['payment_type'] ) ? $_GET['payment_type'] : '';

		if ( $id ) {
			$args['payment_id'] = $id;
		}

		if ( array_key_exists( $status, $statuses ) ) {
			$args['payment_status'] = array( $status );
		}

		if ( $type && 'all' != $type ) {
			$args['payment_type'] = $type;
		}

		$payments = Edr_Payments::get_instance()->get_payments( $args );

		if ( ! empty( $payments ) ) {
			$this->set_pagination_args( array(
				'total_items' => $payments['num_items'],
				'per_page'    => $args['per_page'],
			) );

			$this->items = $payments['rows'];
		}
	}
}
