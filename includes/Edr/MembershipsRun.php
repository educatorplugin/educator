<?php

class Edr_MembershipsRun {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_user_membership' ) );
		add_action( 'edr_expired_memberships', array( __CLASS__, 'process_expired_memberships' ) );
		add_action( 'edr_membership_notifications', array( __CLASS__, 'send_expiration_notifications' ) );
		add_action( 'deleted_user', array( __CLASS__, 'on_deleted_user' ) );

		if ( is_admin() ) {
			add_filter( 'manage_' . EDR_PT_MEMBERSHIP . '_posts_columns', array( __CLASS__, 'memberships_columns' ) );
			add_filter( 'manage_' . EDR_PT_MEMBERSHIP . '_posts_custom_column', array( __CLASS__, 'memberships_column_output' ), 10, 2 );
			add_action( 'pre_get_posts', array( __CLASS__, 'memberships_menu_order' ) );
		}
	}

	/**
	 * Check if the current user's membership expired.
	 */
	public static function check_user_membership() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$ms = Edr_Memberships::get_instance();
		$um = $ms->get_user_membership_by( 'user_id', $user_id );

		if ( ! $um ) {
			return;
		}

		if ( 0 == $um['expiration'] ) {
			// A membership with onetime fee doesn't have expiration date.
			return;
		}

		if ( 'active' == $um['status'] && time() > $um['expiration'] ) {
			$um['status'] = 'expired';
			$ms->update_user_membership( $um );
			$ms->update_membership_entries( $user_id, 'paused' );
		}
	}

	/**
	 * Stop memberships where the expiration date is smaller then the current date.
	 */
	public static function process_expired_memberships() {
		global $wpdb;
		$tables = edr_db_tables();
		$user_ids = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT user_id
			FROM   {$tables['members']}
			WHERE  `expiration` <> '0000-00-00 00:00:00'
			       AND `expiration` < %s
			       AND `status` = 'active'
			",
			date( 'Y-m-d H:i:s' )
		) );

		if ( ! empty( $user_ids ) ) {
			$user_ids_sql = implode( ',', $user_ids );
			$wpdb->query(
				"
				UPDATE {$tables['members']}
				SET    `status` = 'expired'
				WHERE  user_id IN ($user_ids_sql)
				"
			);
			$wpdb->query(
				"
				UPDATE {$tables['entries']}
				SET    `entry_status` = 'paused'
				WHERE  user_id IN ($user_ids_sql)
				       AND `entry_origin` = 'membership'
				       AND `entry_status` = 'inprogress'
				"
			);
		}
	}

	/**
	 * Send the membership expiration emails to users.
	 *
	 * @return int -1 if no email has to be sent, otherwise number of emails.
	 */
	public static function send_expiration_notifications() {
		global $wpdb;
		$days_notify = edr_get_option( 'memberships', 'days_notify' );

		if ( null === $days_notify ) {
			$days_notify = 5;
		} else {
			$days_notify = intval( $days_notify );
		}

		$expires_date = date( 'Y-m-d', strtotime( '+ ' . $days_notify . ' days' ) );
		$tables = edr_db_tables();
		$users = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT     u.ID, u.user_email, u.display_name, m.expiration, m.membership_id
			FROM       {$tables['members']} m
			INNER JOIN $wpdb->users u ON u.ID = m.user_id
			WHERE      m.`expiration` LIKE %s
			           AND m.`status` = 'active'
			",
			$expires_date . '%'
		) );

		if ( empty( $users ) ) {
			return -1;
		}

		$membership_ids = array();

		foreach ( $users as $user ) {
			if ( ! in_array( $user->membership_id, $membership_ids ) ) {
				$membership_ids[] = $user->membership_id;
			}
		}

		$memberships = get_posts( array(
			'post_type'      => EDR_PT_MEMBERSHIP,
			'include'        => $membership_ids,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		$num_sent = 0;

		if ( $memberships ) {
			foreach ( $memberships as $key => $membership ) {
				$memberships[ $membership->ID ] = $membership;
				unset( $memberships[ $key ] );
			}
			
			foreach ( $users as $user ) {
				edr_send_notification(
					$user->user_email,
					'membership_renew',
					array(),
					array(
						'student_name'           => $user->display_name,
						'membership'             => isset( $memberships[ $user->membership_id ] ) ? $memberships[ $user->membership_id ]->post_title : '',
						'expiration'             => date_i18n( get_option( 'date_format' ), strtotime( $user->expiration ) ),
						'membership_payment_url' => edr_get_endpoint_url( 'edr-object', $user->membership_id, get_permalink( edr_get_page_id( 'payment' ) ) ),
					)
				);

				$num_sent += 1;
			}
		}

		return $num_sent;
	}

	/**
	 * Delete membership data when a user is deleted.
	 *
	 * @param int $user_id
	 */
	public static function on_deleted_user( $user_id ) {
		global $wpdb;
		$tables = edr_db_tables();
		$wpdb->delete( $tables['members'], array( 'user_id' => $user_id ), array( '%d' ) );
	}

	/**
	 * Add the price column to the memberships list in the admin panel.
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function memberships_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' == $key ) {
				$new_columns['price'] = __( 'Price', 'educator' );
			}
		}

		return $new_columns;
	}

	/**
	 * Output the price column on the memberships admin page.
	 *
	 * @param string $column_name
	 * @param int $post_id
	 */
	public static function memberships_column_output( $column_name, $post_id ) {
		if ( 'price' == $column_name ) {
			$ms = Edr_Memberships::get_instance();
			$price = $ms->get_price( $post_id );
			$duration = $ms->get_duration( $post_id );
			$period = $ms->get_period( $post_id );

			echo edr_format_membership_price( $price, $duration, $period );
		}
	}

	/**
	 * Order memberships by menu_order of the memberships admin page.
	 *
	 * @param WP_Query $query
	 */
	public static function memberships_menu_order( $query ) {
		if ( $query->is_main_query() && EDR_PT_MEMBERSHIP == $query->query['post_type'] ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}
}
