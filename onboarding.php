<?php
defined('ABSPATH') or die;

if ( ! class_exists( 'Contentify_AI_Onboarding_Class' ) ) {
	class Contentify_AI_Onboarding_Class {
		public static function get_instance() {
			if (self::$instance == null) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private static $instance = null;

		private function __clone() {}

		public function __wakeup() {}

		private function __construct() {
			register_activation_hook( CONTENTIFY_AI_FILE, array( $this, 'activation' ) );

			add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
			add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'wp_ajax_caiobf_get_all', array( $this, 'ajax_get_all' ) );
			add_action( 'wp_ajax_caiobf_optimize', array( $this, 'ajax_optimize' ) );
			add_action( 'wp_ajax_caiobf_update_key', array( $this, 'ajax_update_key' ) );
		}

		public function activation() {
			update_option( 'contentify_ai_activated', 1 );
		}

		public function maybe_redirect() {
			if ( $this->should_proceed_ob() ) {
				delete_option( 'contentify_ai_activated' );
				wp_redirect( menu_page_url( 'contentify-ai-ob', false ), 302 );
				die;
			}
		}

		public function add_menu_item() {
			add_submenu_page(
				null,
				__( 'Contentify AI Onboarding', 'contentify-ai' ),
				__( 'Contentify AI Onboarding', 'contentify-ai' ),
				'manage_options',
				'contentify-ai-ob',
				array( $this, 'onboarding_page' )
			);
		}

		public function onboarding_page() {
			require __DIR__ . '/ob-form.php';
		}

		public function admin_enqueue_scripts( $hn ) {
			if ( $hn != 'admin_page_contentify-ai-ob' ) {
				return;
			}
			wp_enqueue_style( 'contentify-ai-ob', plugins_url( 'css/onboarding.css', CONTENTIFY_AI_FILE ), array(), CONTENTIFY_AI_VER, 'all' );
			wp_enqueue_script( 'contentify-ai-ob', plugins_url( 'js/onboarding.js', CONTENTIFY_AI_FILE ), array( 'jquery' ), CONTENTIFY_AI_VER, true );
			wp_localize_script( 'contentify-ai-ob', 'CAIOBData', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php?action=caiobf_get_all' ),
				'ajaxUrl2' => admin_url( 'admin-ajax.php?action=caiobf_optimize' ),
				'ajaxUrl3' => admin_url( 'admin-ajax.php?action=caiobf_update_key' ),
				'redirectUrl' => menu_page_url( 'contentify_settings',false ),
				'pageTpl' => '<div class="caiobf-uo-page caiobf-uo-page-included" data-id="%id%" data-type="%type%" data-title="%title%"><div class="caiobf-uo-page-title">%title%</div><div class="caiobf-uo-page-type">%type%</div><div class="caiobf-uo-page-actions"><a class="caiobf-btn caiobf-btn-exclude" href="#">' . __( 'Exclude', 'contentify-ai' ) . '</a><a class="caiobf-btn caiobf-btn-include" href="#">' . __( 'Include', 'contentify-ai' ) . '</a></div></div>',
				'itemTpl' => '<div class="caiobf-uo-item"><div class="caiobf-uo-item-title">%title%</div><div class="caiobf-uo-item-type">%type%</div><div class="caiobf-uo-item-status">%status%</div></div>'
			) );
		}

		public function ajax_get_all() {
			if ( empty( $_POST[CONTENTIFY_AI_NONCE_NAME] ) || ! wp_verify_nonce( $_POST[CONTENTIFY_AI_NONCE_NAME], CONTENTIFY_AI_NONCE_BN ) ) {
				wp_send_json( array() );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json( array() );
			}

			if ( ! isset( $_POST['caiobf_api_key'] ) || empty( $_POST['caiobf_api_key'] ) ) {
				wp_send_json( array() );
			}

			update_option( 'contentify_api_key', $_POST['caiobf_api_key'] );

			global $wpdb;

			$posts = $wpdb->get_results( "SELECT ID AS id, post_title AS title, post_type AS type FROM $wpdb->posts WHERE post_type IN ( 'page', 'post', 'product' ) AND post_status = 'publish' AND ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_yoast_wpseo_focuskw' AND meta_key != '' )" );

			$cats = $wpdb->get_results( "SELECT t.term_id AS id, t.name AS title, 'category' AS type  FROM $wpdb->terms t LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'category' AND t.term_id NOT IN (SELECT object_id FROM {$wpdb->prefix}yoast_indexable WHERE object_type = 'term' AND object_sub_type = 'category' AND primary_focus_keyword != '')" );

			wp_send_json( array_merge( $posts, $cats ) );
		}

		public function ajax_optimize() {
			if ( empty( $_POST[CONTENTIFY_AI_NONCE_NAME] ) || ! wp_verify_nonce( $_POST[CONTENTIFY_AI_NONCE_NAME], CONTENTIFY_AI_NONCE_BN ) ) {
				wp_send_json( 'Error (1)' );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json( 'Error (2)' );
			}

			if ( ! isset( $_POST['type'] ) || empty( $_POST['type'] ) ) {
				wp_send_json( __( 'Error (3)', 'contentify-ai' ) );
			}

			if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
				wp_send_json( __( 'Error (4)', 'contentify-ai' ) );
			}

			if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
				wp_send_json( __( 'Error (5)', 'contentify-ai' ) );
			}

			if ( $_POST['type'] == 'category' ) {
				$url = get_category_link( $_POST['id'] );
			} else {
				$url = get_permalink( $_POST['id'] );
			}

			if ( ! $url ) {
				wp_send_json( __( 'Error (6)', 'contentify-ai' ) );
			}

			$response = wp_remote_post( CONTENTIFY_AI_API_BASE_URL . 'seo-optimize', array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type' => 'application/json',
					'API-Key' => get_option( 'contentify_api_key' )
				),
				'body' => json_encode( array(
					'url' => $url
				) )
			) );

			if ( is_wp_error( $response ) ) {
				wp_send_json( __( 'Error (7)', 'contentify-ai' ) );
			}

			$id = intval( $_POST['id'] );
			$obj = json_decode( $response['body'] );

			if ( ! is_object( $obj ) ) {
				wp_send_json( __( 'Error (8)', 'contentify-ai' ) );
			}

			if ( ! property_exists( $obj, 'optimization' ) || ! property_exists( $obj->optimization, 'keywords' ) || ! property_exists( $obj->optimization, 'title' ) || ! property_exists( $obj->optimization, 'description' ) ) {
				wp_send_json( __( 'Error (9)', 'contentify-ai' ) );
			}

			global $wpdb;
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}yoast_indexable SET title = %s, description = %s, primary_focus_keyword = %s WHERE object_type = %s AND object_id = %d", $obj->optimization->title, $obj->optimization->description, $obj->optimization->keywords, $_POST['type'] == 'category' ? 'term' : 'post', $id ) );

			if ( $_POST['type'] == 'category' ) {
				$tax_meta = get_option( 'wpseo_taxonomy_meta' );
				if ( ! is_array( $tax_meta ) ) {
					$tax_meta = array();
				}
				if ( ! isset( $tax_meta['category'] ) ) {
					$tax_meta['category'] = array();
				}
				if ( ! isset( $tax_meta['category'][$id] ) ) {
					$tax_meta['category'][$id] = array();
				}
				$tax_meta['category'][$id]['wpseo_focuskw'] = $obj->optimization->keywords;
				$tax_meta['category'][$id]['wpseo_title'] = $obj->optimization->title;
				$tax_meta['category'][$id]['wpseo_desc'] = $obj->optimization->description;
				update_option( 'wpseo_taxonomy_meta', $tax_meta );
			} else {
				wp_update_post( array(
					'ID' => ( int ) $_POST['id'],
					'meta_input' => array(
						'_yoast_wpseo_focuskw' => $obj->optimization->keywords,
						'_yoast_wpseo_title' => $obj->optimization->title,
						'_yoast_wpseo_metadesc' => $obj->optimization->description
					)
				) );
			}

			wp_send_json( sprintf( __( '%1$sDone!%2$s', 'contentify-ai' ), '<span style="color:green">', '</span>' ) );
		}

		public function ajax_update_key() {
			if ( empty( $_POST[CONTENTIFY_AI_NONCE_NAME] ) || ! wp_verify_nonce( $_POST[CONTENTIFY_AI_NONCE_NAME], CONTENTIFY_AI_NONCE_BN ) ) {
				wp_send_json( array() );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json( array() );
			}

			if ( ! isset( $_POST['caiobf_api_key'] ) || empty( $_POST['caiobf_api_key'] ) ) {
				wp_send_json( array() );
			}

			update_option( 'contentify_api_key', $_POST['caiobf_api_key'] );

			wp_send_json_success();
		}

		private function should_proceed_ob() {
			return get_option( 'contentify_ai_activated' ) == 1;
		}

		private function is_yoast_installed() {
			return is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' );
		}
	}

	Contentify_AI_Onboarding_Class::get_instance();
}