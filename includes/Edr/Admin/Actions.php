<?php

class Edr_Admin_Actions {
	/**
	 * Edit course entry.
	 */
	public static function edit_entry() {
		$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
		$entry = edr_get_entry( $entry_id );

		if ( count( $_POST ) ) {
			// Verify nonce.
			check_admin_referer( 'edr_edit_entry_' . $entry_id );
			
			$errors = new WP_Error();
			$edr_courses = Edr_Courses::get_instance();
			$current_user_id = get_current_user_id();
			$who = '';

			// Check capabilities.
			if ( current_user_can( 'manage_educator' ) ) {
				$who = 'admin';
			} elseif ( $entry->course_id && current_user_can( 'edit_' . EDR_PT_COURSE, $entry->course_id ) ) {
				$who = 'lecturer';
			}

			if ( empty( $who ) ) {
				return;
			}

			// Payment ID.
			if ( 'admin' == $who && isset( $_POST['payment_id'] ) ) {
				if ( empty( $_POST['payment_id'] ) ) {
					$entry->payment_id = 0;
				} else {
					$payment = edr_get_payment( $_POST['payment_id'] );

					if ( $payment->ID ) {
						$entry->payment_id = $payment->ID;
					}
				}
			}

			// Origin.
			if ( 'admin' == $who && isset( $_POST['entry_origin'] ) && array_key_exists( $_POST['entry_origin'], edr_get_entry_origins() ) ) {
				$entry->entry_origin = $_POST['entry_origin'];
			}

			// Membership ID.
			if ( 'admin' == $who && isset( $_POST['membership_id'] ) && 'membership' == $entry->entry_origin ) {
				$entry->object_id = intval( $_POST['membership_id'] );
			}

			// Student ID.
			if ( 'admin' == $who && isset( $_POST['student_id'] ) ) {
				if ( ! empty( $_POST['student_id'] ) ) {
					$entry->user_id = intval( $_POST['student_id'] );
				} else {
					$errors->add( 'no_student', __( 'Please select a student.', 'educator' ) );
				}
			}

			// Course ID.
			if ( 'admin' == $who && isset( $_POST['course_id'] ) ) {
				if ( ! empty( $_POST['course_id'] ) ) {
					$entry->course_id = intval( $_POST['course_id'] );
				} else {
					$errors->add( 'no_course', __( 'Please select a course.', 'educator' ) );
				}
			}		

			// Entry status.
			$prev_status = '';

			if ( isset( $_POST['entry_status'] ) && array_key_exists( $_POST['entry_status'], edr_get_entry_statuses() ) ) {
				if ( $entry->ID && $entry->entry_status != $_POST['entry_status'] ) {
					$prev_status = $entry->entry_status;
				}

				$entry->entry_status = $_POST['entry_status'];
			}

			// Grade.
			if ( isset( $_POST['grade'] ) && is_numeric( $_POST['grade'] ) ) {
				$entry->grade = $_POST['grade'];
			}

			// Entry date.
			if ( 'admin' == $who ) {
				if ( isset( $_POST['entry_date'] ) ) {
					$entry->entry_date = sanitize_text_field( $_POST['entry_date'] );
				} elseif ( empty( $entry->entry_date ) ) {
					$entry->entry_date = date( 'Y-m-d H:i:s' );
				}
			}

			// Check the course prerequisites.
			if ( ! isset( $_POST['ignore_prerequisites'] ) && ! $edr_courses->check_course_prerequisites( $entry->course_id, $entry->user_id ) ) {
				$prerequisites_html = '';
				$prerequisites = $edr_courses->get_course_prerequisites( $entry->course_id );
				$courses = get_posts( array(
					'post_type'   => EDR_PT_COURSE,
					'post_status' => 'publish',
					'include'     => $prerequisites,
				) );

				if ( ! empty( $courses ) ) {
					foreach ( $courses as $course ) {
						$prerequisites_html .= '<br><a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a>';
					}
				}

				$errors->add( 'prerequisites', sprintf( __( 'You have to complete the prerequisites for this course: %s', 'educator' ), $prerequisites_html ) );
			}

			if ( $errors->get_error_code() ) {
				edr_internal_message( 'edit_entry_errors', $errors );
			} elseif ( $entry->save() ) {
				if ( $prev_status ) {
					/**
					 * Do something on entry status change.
					 *
					 * @param Edr_Entry $entry
					 * @param string $prev_status
					 */
					do_action( 'edr_entry_status_change', $entry, $prev_status );
				}

				/**
				 * Do something when an entry is being saved.
				 *
				 * @param Edr_Entry $entry
				 */
				do_action( 'edr_entry_save', $entry );

				wp_redirect( admin_url( 'admin.php?page=edr_admin_entries&edr-action=edit-entry&entry_id=' . $entry->ID . '&edr-message=saved' ) );

				exit();
			}
		}
	}

	/**
	 * Edit payment action.
	 */
	public static function edit_payment() {
		$payment_id = isset( $_GET['payment_id'] ) ? intval( $_GET['payment_id'] ) : 0;
		$payment = edr_get_payment( $payment_id );
		$errors = array();

		if ( count( $_POST ) ) {
			// Verify nonce.
			check_admin_referer( 'edr_edit_payment_' . $payment_id );

			// Capability check.
			if ( ! current_user_can( 'manage_educator' ) ) {
				return;
			}

			// Payment type.
			if ( empty( $payment->ID ) && isset( $_POST['payment_type'] )
				&& array_key_exists( $_POST['payment_type'], edr_get_payment_types() ) ) {
				$payment->payment_type = $_POST['payment_type'];
			}

			// Student ID.
			if ( empty( $payment->user_id ) ) {
				if ( isset( $_POST['student_id'] ) && is_numeric( $_POST['student_id'] ) ) {
					$payment->user_id = $_POST['student_id'];
				} else {
					$errors[] = 'empty_student_id';
				}
			}

			// Object ID.
			if ( empty( $payment->object_id ) ) {
				if ( isset( $_POST['object_id'] ) && is_numeric( $_POST['object_id'] ) ) {
					$payment->object_id = $_POST['object_id'];
				} else {
					$errors[] = 'empty_object_id';
				}
			}

			// Tax.
			if ( isset( $_POST['tax'] ) && is_numeric( $_POST['tax'] ) ) {
				$payment->tax = $_POST['tax'];
			}

			// Amount.
			if ( isset( $_POST['amount'] ) && is_numeric( $_POST['amount'] ) ) {
				$payment->amount = $_POST['amount'];
			}

			if ( isset( $_POST['currency'] ) ) {
				$payment->currency = sanitize_text_field( $_POST['currency'] );
			}

			// Transaction ID.
			if ( isset( $_POST['txn_id'] ) ) {
				$payment->txn_id = sanitize_text_field( $_POST['txn_id'] );
			}

			// Payment status.
			if ( isset( $_POST['payment_status'] ) && array_key_exists( $_POST['payment_status'], edr_get_payment_statuses() ) ) {
				$payment->payment_status = $_POST['payment_status'];
			}

			// Payment gateway.
			if ( isset( $_POST['payment_gateway'] ) ) {
				$payment->payment_gateway = sanitize_title( $_POST['payment_gateway'] );
			}

			// First Name.
			if ( isset( $_POST['first_name'] ) ) {
				$payment->first_name = sanitize_text_field( $_POST['first_name'] );
			}

			// Last Name.
			if ( isset( $_POST['last_name'] ) ) {
				$payment->last_name = sanitize_text_field( $_POST['last_name'] );
			}

			// Address.
			if ( isset( $_POST['address'] ) ) {
				$payment->address = sanitize_text_field( $_POST['address'] );
			}

			// Address Line 2.
			if ( isset( $_POST['address_2'] ) ) {
				$payment->address_2 = sanitize_text_field( $_POST['address_2'] );
			}

			// City.
			if ( isset( $_POST['city'] ) ) {
				$payment->city = sanitize_text_field( $_POST['city'] );
			}

			// Postcode.
			if ( isset( $_POST['postcode'] ) ) {
				$payment->postcode = sanitize_text_field( $_POST['postcode'] );
			}

			// State / Province.
			if ( isset( $_POST['state'] ) ) {
				$payment->state = sanitize_text_field( $_POST['state'] );
			}

			// Country.
			if ( isset( $_POST['country'] ) ) {
				$payment->country = sanitize_text_field( $_POST['country'] );
			}

			if ( ! empty( $errors ) ) {
				edr_internal_message( 'edit_payment_errors', $errors );
				return;
			}

			if ( $payment->save() ) {
				// Update payment meta.
				if ( isset( $_POST['line_id'] ) && is_array( $_POST['line_id'] ) ) {
					foreach ( $_POST['line_id'] as $key => $line_id ) {
						if ( ! is_numeric( $line_id ) ) {
							continue;
						}

						$line = new stdClass();
						$line->ID        = $line_id;
						$line->object_id = isset( $_POST['line_object_id'][ $key ] ) ? intval( $_POST['line_object_id'][ $key ] ) : 0;
						$line->line_type = isset( $_POST['line_type'][ $key ] ) ? sanitize_text_field( $_POST['line_type'][ $key ] ) : '';
						$line->amount    = isset( $_POST['line_amount'][ $key ] ) ? sanitize_text_field( $_POST['line_amount'][ $key ] ) : 0.0;
						$line->tax       = isset( $_POST['line_tax'][ $key ] ) ? sanitize_text_field( $_POST['line_tax'][ $key ] ) : 0.0;
						$line->name      = isset( $_POST['line_name'][ $key ] ) ? sanitize_text_field( $_POST['line_name'][ $key ] ) : '';

						$payment->update_line( $line );
					}
				}

				$edr_entries = Edr_Entries::get_instance();
				$entry_saved = true;

				// Create entry for the student.
				// Implemented for the "course" payment type.
				if ( isset( $_POST['create_entry'] ) && ! $edr_entries->get_entry( array( 'payment_id' => $payment->ID ) ) ) {
					$entry = edr_get_entry();
					$entry->course_id = $payment->object_id;
					$entry->user_id = $payment->user_id;
					$entry->payment_id = $payment->ID;
					$entry->entry_status = 'inprogress';
					$entry->entry_date = date( 'Y-m-d H:i:s' );
					$entry_saved = $entry->save();

					if ( $entry_saved ) {
						// Send notification email to the student.
						$student = get_user_by( 'id', $payment->user_id );
						$course = get_post( $payment->object_id, OBJECT, 'display' );

						if ( $student && $course ) {
							edr_send_notification(
								$student->user_email,
								'student_registered',
								array(
									'course_title' => $course->post_title,
								),
								array(
									'student_name'   => $student->display_name,
									'course_title'   => $course->post_title,
									'course_excerpt' => $course->post_excerpt,
								)
							);
						}
					}
				}

				// Setup membership for the student.
				if ( isset( $_POST['setup_membership'] ) && 'membership' == $payment->payment_type ) {
					$ms = Edr_Memberships::get_instance();

					// Setup membership.
					$ms->setup_membership( $payment->user_id, $payment->object_id );

					// Send notification email.
					$student = get_user_by( 'id', $payment->user_id );
					$membership = $ms->get_membership( $payment->object_id );

					if ( $student && $membership ) {
						$price = $ms->get_price( $membership->ID );
						$duration = $ms->get_duration( $membership->ID );
						$period = $ms->get_period( $membership->ID );
						$user_membership = $ms->get_user_membership_by( 'user_id', $student->ID );
						$expiration = ( $user_membership ) ? $user_membership['expiration'] : 0;

						edr_send_notification(
							$student->user_email,
							'membership_register',
							array(),
							array(
								'student_name' => $student->display_name,
								'membership'   => $membership->post_title,
								'expiration'   => ( $expiration ) ? date_i18n( get_option( 'date_format' ), $expiration ) : __( 'None', 'educator' ),
								'price'        => edr_format_membership_price( $price, $duration, $period, false ),
							)
						);
					}
				}

				if ( $entry_saved ) {
					wp_redirect( admin_url( 'admin.php?page=edr_admin_payments&edr-action=edit-payment&payment_id=' . $payment->ID . '&edr-message=saved' ) );
					exit;
				}
			}
		}
	}

	/**
	 * Edit member action.
	 */
	public static function edit_member() {
		if ( count( $_POST ) ) {
			check_admin_referer( 'edr_edit_member' );

			if ( ! current_user_can( 'manage_educator' ) ) {
				return;
			}

			$edr_memberships = Edr_Memberships::get_instance();
			$membership_statuses = $edr_memberships->get_statuses();
			$date_regex = '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';
			$errors = new WP_Error();
			$member_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : null;
			$user_membership = ( $member_id ) ? $edr_memberships->get_user_membership_by( 'user_id', $member_id ) : null;
			$data = array();

			// User id.
			if ( ! $user_membership || ! $user_membership['user_id'] ) {
				if ( ! empty( $_POST['user_id'] ) ) {
					$data['user_id'] = $_POST['user_id'];
				} else {
					$errors->add( 'user_id_empty', __( 'Please select a user', 'educator' ) );
				}
			}

			// Membership id.
			if ( ! empty( $_POST['membership_id'] ) ) {
				$data['membership_id'] = $_POST['membership_id'];
			} else {
				$errors->add( 'membership_id_empty', __( 'Please select a membership', 'educator' ) );
			}

			// Status.
			if ( isset( $_POST['membership_status'] ) && array_key_exists( $_POST['membership_status'], $membership_statuses ) ) {
				$data['status'] = $_POST['membership_status'];
			}

			// Expiration.
			if ( isset( $_POST['expiration'] ) ) {
				if ( preg_match( $date_regex, $_POST['expiration'] ) ) {
					$data['expiration'] = strtotime( $_POST['expiration'] );
				} else {
					$data['expiration'] = 0;
				}
			}

			if ( isset( $data['user_id'] ) ) {
				$tmp_user_membership = $edr_memberships->get_user_membership_by( 'user_id', $data['user_id'] );

				if ( $tmp_user_membership ) {
					$errors->add( 'user_id_exists', __( 'The membership for this student already exists.', 'educator' ) );
				}
			}

			if ( $errors->get_error_code() ) {
				edr_internal_message( 'edit_member_errors', $errors );
			} else {
				if ( $user_membership ) {
					$data['ID'] = $user_membership['ID'];

					if ( 'expired' == $data['status'] ) {
						$edr_memberships->update_membership_entries( $user_membership['user_id'], 'paused' );
					}
				}

				$data['ID'] = $edr_memberships->update_user_membership( $data );

				if ( $data['ID'] ) {
					wp_redirect( admin_url( 'admin.php?page=edr_admin_members&edr-action=edit-member&id=' . intval( $member_id ) . '&edr-message=saved' ) );
					exit();
				}
			}
		}
	}

	/**
	 * Edit payment gateway action.
	 */
	public static function edit_payment_gateway() {
		if ( ! isset( $_POST['gateway_id'] ) ) {
			return;
		}
		
		$gateway_id = sanitize_title( $_POST['gateway_id'] );

		// Verify nonce.
		check_admin_referer( 'edr_payments_settings' );

		// Get available gateways.
		$gateways = Edr_Main::get_instance()->get_gateways();

		// Does the requested gateway exist?
		if ( ! isset( $gateways[ $gateway_id ] ) ) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'manage_educator' ) ) {
			return;
		}

		$saved = $gateways[ $gateway_id ]->save_admin_options();
		$message = '';

		if ( true === $saved ) {
			$message = 'saved';
		} else {
			$message = 'not_saved';
		}

		wp_redirect( admin_url( 'admin.php?page=edr_admin_settings&tab=payment&gateway_id=' . $gateway_id . '&edr-message=' . $message ) );
	}

	/**
	 * Delete an entry.
	 */
	public static function delete_entry() {
		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_delete_entry' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_educator' ) ) {
			return;
		}

		// Get entry.
		$entry_id = isset( $_GET['entry_id'] ) ? intval( $_GET['entry_id'] ) : null;

		if ( ! $entry_id ) {
			return;
		}

		$entry = edr_get_entry( $entry_id );

		// Delete entry if it was found.
		if ( $entry->ID && $entry->delete() ) {
			wp_redirect( admin_url( 'admin.php?page=edr_admin_entries&edr-message=entry_deleted' ) );

			exit();
		}
	}

	/**
	 * Delete a payment.
	 */
	public static function delete_payment() {
		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_delete_payment' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_educator' ) ) {
			return;
		}

		// Get entry.
		$payment_id = isset( $_GET['payment_id'] ) ? intval( $_GET['payment_id'] ) : null;

		if ( ! $payment_id ) {
			return;
		}

		$payment = edr_get_payment( $payment_id );

		// Delete payment if it was found.
		if ( $payment->ID && $payment->delete() ) {
			wp_redirect( admin_url( 'admin.php?page=edr_admin_payments&edr-message=payment_deleted' ) );

			exit();
		}
	}
}
