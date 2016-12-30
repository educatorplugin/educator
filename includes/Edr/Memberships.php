<?php

class Edr_Memberships {
	/**
	 * @var Edr_Memberships
	 */
	protected static $instance = null;

	/**
	 * @var string
	 */
	public $post_type = EDR_PT_MEMBERSHIP;

	/**
	 * @var string
	 */
	protected $tbl_members;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$tables = edr_db_tables();
		$this->tbl_members = $tables['members'];
	}

	/**
	 * Get instance.
	 *
	 * @return Edr_Memberships
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get available membership payment periods.
	 *
	 * @return array
	 */
	public function get_periods() {
		return array(
			'onetime' => __( 'Onetime Fee', 'educator' ),
			'days'    => __( 'Day(s)', 'educator' ),
			'months'  => __( 'Month(s)', 'educator' ),
			'years'   => __( 'Year(s)', 'educator' ),
		);
	}

	/**
	 * Get available membership statuses.
	 *
	 * @return array
	 */
	public function get_statuses() {
		return array(
			'expired' => __( 'Expired', 'educator' ),
			'active'  => __( 'Active', 'educator' ),
		);
	}

	/**
	 * Get all memberships.
	 *
	 * @return array
	 */
	public function get_memberships() {
		return get_posts( array(
			'post_type'      => $this->post_type,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
	}

	/**
	 * Get one membership.
	 *
	 * @param int $id
	 * @return false|WP_Post
	 */
	public function get_membership( $id ) {
		return get_post( $id );
	}

	/**
	 * Get users who signed up for a membership.
	 *
	 * @param array $args
	 * @return WP_User_Query
	 */
	public function get_members( $args ) {
		global $wpdb;
		$user_query = new WP_User_Query();
		$user_query->prepare_query( $args );
		$user_query->query_from .= " INNER JOIN {$this->tbl_members} edr_m ON edr_m.user_id = {$wpdb->users}.ID";
		$user_query->query();

		return $user_query;
	}

	/**
	 * Get membership price.
	 *
	 * @param int $membership_id
	 * @return float
	 */
	public function get_price( $membership_id ) {
		return (float) get_post_meta( $membership_id, '_edr_price', true );
	}

	/**
	 * Get membership period.
	 *
	 * @param int $membership_id
	 * @return string
	 */
	public function get_period( $membership_id ) {
		return get_post_meta( $membership_id, '_edr_period', true );
	}

	/**
	 * Get membership duration.
	 *
	 * @param int $membership_id
	 * @return int
	 */
	public function get_duration( $membership_id ) {
		return (int) get_post_meta( $membership_id, '_edr_duration', true );
	}

	/**
	 * Get membership categories.
	 *
	 * @param int $membership_id
	 * @return array
	 */
	public function get_categories( $membership_id ) {
		$categories = get_post_meta( $membership_id, '_edr_categories', true );

		return ( $categories ) ? $categories : array();
	}

	/**
	 * Get membership payment URL.
	 *
	 * @param int $membership_id
	 * @return string
	 */
	public function get_payment_url( $membership_id ) {
		$payment_page_url = get_permalink( edr_get_page_id( 'payment' ) );

		return edr_get_endpoint_url( 'edr-object', $membership_id, $payment_page_url );
	}

	/**
	 * Calculate expiration date.
	 *
	 * @param int $duration
	 * @param string $period
	 * @param string $from_ts
	 * @return string
	 */
	public function calculate_expiration_date( $duration, $period, $from_ts = 0 ) {
		if ( empty( $from_ts ) ) {
			$from_ts = time();
		}

		$ts = 0;

		switch ( $period ) {
			case 'days':
				$ts = strtotime( '+ ' . $duration . ' days', $from_ts );
				break;

			case 'months':
				$cur_date = explode( '-', date( 'Y-n-j', $from_ts ) );
				$next_month = $cur_date[1] + $duration;
				$next_year = $cur_date[0];
				$next_day = $cur_date[2];

				if ( $next_month > 12 ) {
					$next_month -= 12;
					$next_year += 1;
				}

				$cur_month_days = date( 't', $from_ts );
				$next_month_days = date( 't', strtotime( "$next_year-$next_month-1" ) );

				if ( $cur_date[2] == $cur_month_days || $next_day > $next_month_days ) {
					// If today is the last day of the month or the next day
					// is bigger than the number of days in the next month,
					// set the next day to the last day of the next month.
					$next_day = $next_month_days;
				}

				$ts = strtotime( "$next_year-$next_month-$next_day 23:59:59" );
				break;

			case 'years':
				$cur_date = explode( '-', date( 'Y-n-j', $from_ts ) );
				
				$next_year = $cur_date[0] + $duration;
				$next_month = $cur_date[1];
				$next_day = $cur_date[2];

				$cur_month_days = date( 't', $from_ts );
				$next_month_days = date( 't', strtotime( "$next_year-$next_month-1" ) );

				if ( $cur_date[2] == $cur_month_days || $next_day > $next_month_days ) {
					// Account for February, where the number of days differs if it's leap year.
					$next_day = $next_month_days;
				}

				$ts = strtotime( "$next_year-$next_month-$next_day 23:59:59" );
				break;
		}

		return $ts;
	}

	/**
	 * Modify expiration date given duration (e.g., 3 months, 1 year, etc).
	 *
	 * @param int $duration
	 * @param string $period
	 * @param string $direction - or +
	 * @param int $from_ts
	 * @return int Timstamp.
	 */
	public function modify_expiration_date( $duration, $period, $direction = '+', $from_ts = 0 ) {
		if ( empty( $from_ts ) ) {
			$from_ts = time();
		}

		$ts = 0;

		switch ( $period ) {
			case 'days':
				$ts = strtotime( $direction . ' ' . $duration . ' days', $from_ts );

				break;

			case 'months':
				$from_date = explode( '-', date( 'Y-n-j', $from_ts ) );
				$to_month = ( '-' == $direction ) ? $from_date[1] - $duration : $from_date[1] + $duration;
				$to_year = $from_date[0];
				$to_day = $from_date[2];

				if ( $to_month < 1 ) {
					$to_month += 12;
					$to_year -= 1;
				} elseif ( $to_month > 12 ) {
					$to_month -= 12;
					$to_year += 1;
				}

				$from_month_days = date( 't', $from_ts );
				$to_month_days = date( 't', strtotime( "$to_year-$to_month-1" ) );

				if ( $from_date[2] == $from_month_days || $to_day > $to_month_days ) {
					// If today is the last day of the month or the next day
					// is bigger than the number of days in the next month,
					// set the next day to the last day of the next month.
					$to_day = $to_month_days;
				}

				$ts = strtotime( "$to_year-$to_month-$to_day 23:59:59" );

				break;

			case 'years':
				$from_date = explode( '-', date( 'Y-n-j', $from_ts ) );

				$to_year = ( '-' == $direction ) ? $from_date[0] - $duration : $from_date[0] + $duration;
				$to_month = $from_date[1];
				$to_day = $from_date[2];

				$from_month_days = date( 't', $from_ts );
				$to_month_days = date( 't', strtotime( "$to_year-$to_month-1" ) );

				if ( $from_date[2] == $from_month_days || $to_day > $to_month_days ) {
					// Account for February, where the number of days differs if it's a leap year.
					$to_day = $to_month_days;
				}

				$ts = strtotime( "$to_year-$to_month-$to_day 23:59:59" );

				break;
		}

		return $ts;
	}

	/**
	 * Get user's membership data.
	 *
	 * @param string $by
	 * @param int $id
	 * @return null|array
	 */
	public function get_user_membership_by( $by, $id ) {
		global $wpdb;
		$id = (int) $id;

		if ( $id < 1 ) {
			return null;
		}

		$column = '';
		$member_id = null;

		switch ( $by ) {
			case 'id':
				$column = 'ID';
				$member_id = $id;
				break;
			case 'user_id':
				$column = 'user_id';
				$member_id = wp_cache_get( $id, 'edr_members_user_ids' );
				break;
		}

		if ( false !== $member_id ) {
			$user_membership = wp_cache_get( $member_id, 'edr_members' );

			if ( false !== $user_membership ) {
				return $user_membership;
			}
		}

		$user_membership = null;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->tbl_members WHERE `$column` = %d", $id ) );

		if ( $row ) {
			$user_membership = array(
				'ID'            => (int) $row->ID,
				'user_id'       => (int) $row->user_id,
				'membership_id' => (int) $row->membership_id,
				'status'        => $row->status,
				'expiration'    => ( '0000-00-00 00:00:00' != $row->expiration ) ? strtotime( $row->expiration ) : 0,
			);
			wp_cache_set( $row->user_id, $row->ID, 'edr_members_user_ids' );
			wp_cache_set( $row->ID, $user_membership, 'edr_members' );
		} elseif ( 'user_id' == $by ) {
			wp_cache_set( $id, 0, 'edr_members_user_ids' );
			wp_cache_set( 0, null, 'edr_members' );
		}

		return $user_membership;
	}

	/**
	 * Update the user's membership data.
	 *
	 * @param array $input
	 * @return int
	 */
	public function update_user_membership( $input ) {
		global $wpdb;
		$data = array();
		$format = array();

		if ( isset( $input['user_id'] ) ) {
			$data['user_id'] = $input['user_id'];
			$format[] = '%d';
		}

		if ( isset( $input['membership_id'] ) ) {
			$data['membership_id'] = $input['membership_id'];
			$format[] = '%d';
		}

		if ( isset( $input['status'] ) ) {
			$data['status'] = $input['status'];
			$format[] = '%s';
		}

		if ( isset( $input['expiration'] ) && is_numeric( $input['expiration'] ) ) {
			$data['expiration'] = ( $input['expiration'] > 0 )
				? date( 'Y-m-d H:i:s', $input['expiration'] )
				: '0000-00-00 00:00:00';
			$format[] = '%s';
		}

		if ( isset( $input['ID'] ) && is_numeric( $input['ID'] ) && $input['ID'] > 0 ) {
			$where = array( 'ID' => $input['ID'] );
			$where_format = array( '%d' );
			$affected_rows = $wpdb->update( $this->tbl_members, $data, $where, $format, $where_format );

			if ( false !== $affected_rows ) {
				$data['ID'] = $input['ID'];
			}
		} else {
			$affected_rows = $wpdb->insert( $this->tbl_members, $data, $format );

			if ( false !== $affected_rows ) {
				$data['ID'] = $wpdb->insert_id;
			}
		}

		if ( ! empty( $data['ID'] ) ) {
			wp_cache_delete( $data['ID'], 'edr_members' );
		}

		wp_cache_delete( 0, 'edr_members' );

		return $data['ID'];
	}

	/**
	 * Setup membership for a user.
	 *
	 * @param int $user_id,
	 * @param int $membership_id
	 * @return false|int
	 */
	public function setup_membership( $user_id, $membership_id ) {
		$membership = get_post( $membership_id );

		if ( ! $membership ) {
			return false;
		}

		$user_membership = $this->get_user_membership_by( 'user_id', $user_id );
		$period = $this->get_period( $membership_id );
		$expiration = 0;

		// Pause the course entries that originated from current membership,
		// if new membership differs.
		if ( ! $user_membership || $membership_id != $user_membership['membership_id'] ) {
			$this->update_membership_entries( $user_id, 'paused' );
		}

		if ( 'onetime' != $period ) {
			$from_ts = 0;

			if ( $user_membership && 'expired' != $user_membership['status'] && $membership_id == $user_membership['membership_id'] ) {
				// Extend membership.
				$from_ts = $user_membership['expiration'];
			}

			$duration = $this->get_duration( $membership_id );
			$expiration = $this->calculate_expiration_date( $duration, $period, $from_ts );
		}

		$data = array(
			'ID'            => ( $user_membership ) ? $user_membership['ID'] : null,
			'user_id'       => $user_id,
			'membership_id' => $membership->ID,
			'status'        => ( 'paused' != $user_membership['status'] ) ? 'active' : $user_membership['status'],
			'expiration'    => ( $expiration > 0 ) ? $expiration : 0,
		);

		return $this->update_user_membership( $data );
	}

	/**
	 * Check if a membership expired.
	 *
	 * @param array $user_membership User's membership data.
	 * @return boolean
	 */
	public function has_expired( $user_membership ) {
		if ( ! $user_membership || 'active' != $user_membership['status'] ) {
			return true;
		}

		$period = $this->get_period( $user_membership['membership_id'] );

		if ( 'onetime' == $period ) {
			return false;
		}

		return ( time() > $user_membership['expiration'] );
	}

	/**
	 * Get post ids a membership gives access to.
	 *
	 * @param int $membership_id
	 * @return array
	 */
	public function get_membership_post_ids( $membership_id ) {
		global $wpdb;
		$ids = wp_cache_get( $membership_id, 'edr_membership_post_ids' );

		if ( ! $ids ) {
			$categories = $this->get_categories( $membership_id );

			if ( ! empty( $categories ) ) {
				$categories_sql = implode( ',', array_map( 'intval', $categories ) );
				$taxonomy = EDR_TX_CATEGORY;
				$ids = $wpdb->get_col(
					"SELECT p.ID FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID
					INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
					WHERE tt.term_id IN ($categories_sql) AND tt.taxonomy='$taxonomy'"
				);
			} else {
				$ids = array();
			}

			wp_cache_set( $membership_id, $ids, 'edr_membership_post_ids' );
		}

		return $ids;
	}

	/**
	 * Check if a user can join a given course.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return bool
	 */
	public function can_join_course( $course_id, $user_id ) {
		$user_membership = $this->get_user_membership_by( 'user_id', $user_id );

		if ( $this->has_expired( $user_membership ) ) {
			return false;
		}

		$post_ids = $this->get_membership_post_ids( $user_membership['membership_id'] );

		return ( ! empty( $post_ids ) && in_array( $course_id, $post_ids ) );
	}

	/**
	 * Update membership entries' status.
	 *
	 * @param int $user_id
	 * @param string $status
	 */
	public function update_membership_entries( $user_id, $status ) {
		global $wpdb;
		$tables = edr_db_tables();

		$wpdb->update(
			$tables['entries'],
			array(
				'entry_status' => $status,
			),
			array(
				'user_id'      => $user_id,
				'entry_origin' => 'membership',
				'entry_status' => 'inprogress',
			),
			array( '%s' ),
			array( '%d', '%s', '%s' )
		);
	}
}
