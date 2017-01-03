<?php

class Edr_PostTypes {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 8 ); // Run before the plugin update.
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 8 ); // Run before the plugin update.
		add_filter( 'the_content', array( __CLASS__, 'protect_content' ), 90 );
		add_filter( 'pre_option_rss_use_excerpt', array( __CLASS__, 'use_only_excerpt_in_feed' ), 90 );
		add_filter( 'comment_feed_where', array( __CLASS__, 'hide_comments_in_feed' ), 10, 2 );
		add_filter( 'comments_clauses', array( __CLASS__, 'protect_comments' ), 10, 2 );
		add_filter( 'comments_template', array( __CLASS__, 'protect_comments_template' ) );

		/*
		Modify the adjacent post's where clause to account for the behavior of lesson post type.
		Lessons are assigned to a particular course, so we don't want to display the prev/next
		links to lessons from other courses on the single lesson page.
		*/
		add_filter( 'get_previous_post_join', array( __CLASS__, 'get_adjacent_lesson_join' ), 10, 5 );
		add_filter( 'get_previous_post_where', array( __CLASS__, 'get_previous_lesson_where' ), 10, 5 );
		add_filter( 'get_previous_post_sort', array( __CLASS__, 'get_previous_lesson_sort' ), 10, 2 );
		add_filter( 'get_next_post_join', array( __CLASS__, 'get_adjacent_lesson_join' ), 10, 5 );
		add_filter( 'get_next_post_where', array( __CLASS__, 'get_next_lesson_where' ), 10, 5 );
		add_filter( 'get_next_post_sort', array( __CLASS__, 'get_next_lesson_sort' ), 10, 2 );
	}

	/**
	 * Register post types.
	 */
	public static function register_post_types() {
		$permalink_settings = get_option( 'edr_permalinks' );

		// Courses.
		$course_slug = ( $permalink_settings && ! empty( $permalink_settings['course_base'] ) )
			? $permalink_settings['course_base']
			: _x( 'courses', 'course slug', 'educator' );
		$courses_archive_slug = ( $permalink_settings && ! empty( $permalink_settings['courses_archive_base'] ) )
			? $permalink_settings['courses_archive_base']
			: _x( 'courses', 'courses archive slug', 'educator' );

		register_post_type(
			EDR_PT_COURSE,
			apply_filters( 'edr_cpt_course', array(
				'labels'              => array(
					'name'          => __( 'Courses', 'educator' ),
					'singular_name' => __( 'Course', 'educator' ),
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'capability_type'     => EDR_PT_COURSE,
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => $courses_archive_slug,
				'rewrite'             => array( 'slug' => $course_slug ),
				'query_var'           => 'course',
				'can_export'          => true,
			) )
		);

		// Lessons.
		$supports = array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' );

		if ( 1 == edr_get_option( 'learning', 'lesson_comments' ) ) {
			$supports[] = 'comments';
		}

		register_post_type(
			EDR_PT_LESSON,
			apply_filters( 'edr_cpt_lesson', array(
				'labels'              => array(
					'name'          => __( 'Lessons', 'educator' ),
					'singular_name' => __( 'Lesson', 'educator' ),
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'capability_type'     => EDR_PT_LESSON,
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => $supports,
				'has_archive'         => ( ! empty( $permalink_settings['lessons_archive_base'] ) ) ? $permalink_settings['lessons_archive_base'] : _x( 'lessons', 'lesson slug', 'educator' ),
				'rewrite'             => array(
					'slug' => ( ! empty( $permalink_settings['lesson_base'] ) ) ? $permalink_settings['lesson_base'] : _x( 'lessons', 'lessons archive slug', 'educator' ),
				),
				'query_var'           => 'lesson',
				'can_export'          => true,
			) )
		);

		// Memberships.
		register_post_type(
			EDR_PT_MEMBERSHIP,
			apply_filters( 'edr_cpt_membership', array(
				'label'               => __( 'Membership Levels', 'educator' ),
				'labels'              => array(
					'name'               => __( 'Membership Levels', 'educator' ),
					'singular_name'      => __( 'Membership Level', 'educator' ),
					'add_new_item'       => __( 'Add New Membership Level', 'educator' ),
					'edit_item'          => __( 'Edit Membership Level', 'educator' ),
					'new_item'           => __( 'New Membership Level', 'educator' ),
					'view_item'          => __( 'View Membership Level', 'educator' ),
					'search_items'       => __( 'Search Membership Levels', 'educator' ),
					'not_found'          => __( 'No membership levels found', 'educator' ),
					'not_found_in_trash' => __( 'No membership levels found in Trash', 'educator' ),
				),
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edr_admin_settings',
				'exclude_from_search' => true,
				'capability_type'     => EDR_PT_MEMBERSHIP,
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'membership' ),
				'query_var'           => 'membership',
				'can_export'          => true,
			) )
		);
	}

	/**
	 * Register taxonomies.
	 */
	public static function register_taxonomies() {
		$permalink_settings = get_option( 'edr_permalinks' );
		
		// Course categories.
		register_taxonomy(
			EDR_TX_CATEGORY,
			EDR_PT_COURSE,
			apply_filters( 'edr_ctx_category', array(
				'label'             => __( 'Course Categories', 'educator' ),
				'public'            => true,
				'show_ui'           => true,
				'show_in_nav_menus' => true,
				'hierarchical'      => true,
				'rewrite'           => array(
					'slug' => ( ! empty( $permalink_settings['category_base'] ) ) ? $permalink_settings['category_base'] : _x( 'course-category', 'slug', 'educator' ),
				),
				'capabilities'      => array(
					'assign_terms' => 'edit_' . EDR_PT_COURSE . 's',
				),
			) )
		);
	}

	/**
	 * Hides lesson content from unauthenticated users.
	 * Returns "access denied" notice on the single lesson page.
	 * Returns empty string in other cases.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function protect_content( $content ) {
		$post = get_post();

		if ( ! empty( $post ) && EDR_PT_LESSON == $post->post_type ) {
			$edr_access = Edr_Access::get_instance();

			if ( ! $edr_access->can_study_lesson( $post->ID ) ) {
				if ( is_singular() && in_the_loop() ) {
					$edr_courses = Edr_Courses::get_instance();
					$course_id = $edr_courses->get_course_id( $post->ID );
					$course_url = get_permalink( $course_id );
					$course_title = get_the_title( $course_id );
					$content = '<div class="edr-no-access-notice">';
					$content .= sprintf( __( 'Please register for %s to view this lesson.', 'educator' ),
						'<a href="' . esc_url( $course_url ) . '">' . $course_title . '</a>' );
					$content .= '</div>';
				} else {
					$content = '';
				}
			}
		}

		return $content;
	}

	/**
	 * Display only excerpt in feed.
	 * Don't display the content.
	 * Pages: ?post_type=xx&feed=xx
	 *
	 * @param int $option_value
	 * @return int
	 */
	public static function use_only_excerpt_in_feed( $use_excerpt ) {
		if ( EDR_PT_LESSON == get_post_type() ) {
			$use_excerpt = 1;
		}

		return $use_excerpt;
	}

	/**
	 * Hide lesson comments in comments feed.
	 * Pages: ?feed=comments-rss2, ?feed=rss2&p=xx, ?lesson=xx&feed=rss2
	 *
	 * @param string $limits
	 * @param WP_Query $query
	 * @return string
	 */
	public static function hide_comments_in_feed( $where, $query ) {
		global $wpdb;

		if ( ! $query->is_singular ) {
			// General comments feed.
			$lesson_post_type = EDR_PT_LESSON;
			$where .= " AND {$wpdb->posts}.post_type <> '$lesson_post_type'";
		} elseif ( EDR_PT_LESSON == $query->get( 'post_type' ) ) {
			// Comments feed on the single lesson page.
			$edr_access = Edr_Access::get_instance();

			if ( ! $edr_access->can_study_lesson( $query->posts[0]->ID ) ) {
				$where .= ' AND 1 = 0';
			}
		}

		return $where;
	}

	/**
	 * Exclude lesson comments from the comment query.
	 *
	 * @param array $pieces
	 * @param WP_Comment_Query $comment_query
	 * @return array
	 */
	public static function protect_comments( $pieces, $comment_query ) {
		global $wpdb;

		if ( false !== strpos( $pieces['join'], " $wpdb->posts ON $wpdb->posts" ) ) {
			$pieces['where'] .= " AND $wpdb->posts.post_type <> '" . EDR_PT_LESSON . "'";
		}
		
		return $pieces;
	}

	/**
	 * Protect comments on the single lesson page.
	 *
	 * @param string $template
	 * @return string
	 */
	public static function protect_comments_template( $template ) {
		$post = get_post();

		if ( $post->post_type != EDR_PT_LESSON ) {
			return $template;
		}

		$edr_access = Edr_Access::get_instance();

		if ( $edr_access->can_study_lesson( $post->ID ) ) {
			return $template;
		}

		return EDR_PLUGIN_DIR . 'templates/comments-no-access.php';
	}

	/**
	 * Filter the adjacent lesson join clause.
	 *
	 * @param string $join
	 * @param boolean $in_same_term
	 * @param array|string $excluded_terms
	 * @param string $taxonomy
	 * @param WP_Post $post
	 * @return string
	 */
	public static function get_adjacent_lesson_join( $join, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		if ( EDR_PT_LESSON == $post->post_type ) {
			global $wpdb;
			$join .= " INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id";
		}

		return $join;
	}

	/**
	 * Get the adjacent lesson's where clause.
	 *
	 * @param WP_Post $post
	 * @param boolean $previous
	 * @return string
	 */
	protected static function get_adjacent_lesson_where( $post, $previous = true ) {
		global $wpdb;
		$cmp = $previous ? '<' : '>';
		$course_id = Edr_Courses::get_instance()->get_course_id( $post->ID );
		$where = $wpdb->prepare( " WHERE p.post_type = %s AND p.post_status = 'publish'
			AND p.menu_order $cmp %d AND pm.meta_key = '_edr_course_id' AND pm.meta_value = %d",
			EDR_PT_LESSON, $post->menu_order, $course_id );

		return $where;
	}

	/**
	 * Get the adjacent lesson's order and limit clauses.
	 *
	 * @param WP_Post $post
	 * @param boolean $previous
	 * @return string
	 */
	protected static function get_adjacent_lesson_sort( $post, $previous = true ) {
		$order = $previous ? 'DESC' : 'ASC';
		$sql = "ORDER BY p.menu_order $order LIMIT 1";

		return $sql;
	}

	/**
	 * Filter the previous lesson where clause.
	 *
	 * @param string $where
	 * @param boolean $in_same_term
	 * @param array|string $excluded_terms
	 * @param string $taxonomy
	 * @param WP_Post $post
	 * @return string
	 */
	public static function get_previous_lesson_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		return EDR_PT_LESSON == $post->post_type ? self::get_adjacent_lesson_where( $post, true ) : $where;
	}

	/**
	 * Filter the previous lesson order and limit clauses.
	 *
	 * @param string $sql
	 * @param WP_Post $post
	 * @return string
	 */
	public static function get_previous_lesson_sort( $sql, $post ) {
		return EDR_PT_LESSON == $post->post_type ? self::get_adjacent_lesson_sort( $post, true ) : $sql;
	}

	/**
	 * Filter the next lesson where clause.
	 *
	 * @param string $where
	 * @param boolean $in_same_term
	 * @param array|string $excluded_terms
	 * @param string $taxonomy
	 * @param WP_Post $post
	 * @return string
	 */
	public static function get_next_lesson_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		return EDR_PT_LESSON == $post->post_type ? self::get_adjacent_lesson_where( $post, false ) : $where;
	}

	/**
	 * Filter the next lesson order and limit clauses.
	 *
	 * @param string $sql
	 * @param WP_Post $post
	 * @return string
	 */
	public static function get_next_lesson_sort( $sql, $post ) {
		return EDR_PT_LESSON == $post->post_type ? self::get_adjacent_lesson_sort( $post, false ) : $sql;
	}
}
