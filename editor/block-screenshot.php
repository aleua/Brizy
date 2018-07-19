<?php

class Brizy_Editor_BlockScreenshot {

	const AJAX_SAVE_BLOCK_SCREENSHOT = 'brizy_save_block_screenshot';
	const BLOCK_TYPES = array( 'global', 'saved' );

	/**
	 * Brizy_Block_Screenshot constructor.
	 */
	public function __construct() {
		$this->initialize();
	}

	private function initialize() {

		if ( ! Brizy_Editor::is_user_allowed() ) {
			return;
		}

		if ( isset( $_GET['block_id'] ) ) {
			$this->load_block_screenshot();
		} else {
			if ( isset( $_REQUEST['hash'] ) && wp_verify_nonce( $_REQUEST['hash'], Brizy_Editor_API::nonce ) ) {
				add_action( 'wp_ajax_' . self::AJAX_SAVE_BLOCK_SCREENSHOT, array( $this, 'save_block_screenshot' ) );
			}
		}
	}

	public function save_block_screenshot() {

		if ( empty( $_POST['block_type'] ) || in_array( $_POST['block_type'], self::BLOCK_TYPES ) || empty( $_POST['img'] ) || empty( $_POST['block_id'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'There are no all required POST variables', 'brizy' ) ) );
		}

		$img_base64 = '';

		if ( preg_match( '/^data:image\/(\w+);base64,/', $_POST['img'], $img_type ) ) {
			$base64 = $_POST['img'];
			$img_base64 = substr( $base64, strpos( $base64, ',' ) + 1 );
			$img_type = strtolower( $img_type[1] ); // jpg, png, gif

			if ( ! in_array( $img_type, array( 'jpg', 'jpeg', 'gif', 'png' ) ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Wrong type of block screenshot.', 'brizy' ) ) );
			}

			$base64 = base64_decode( $base64 );

			if ( false === $base64 ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Failed to decode block screenshot base64.', 'brizy' ) ) );
			}
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'There is no base64.', 'brizy' ) ) );
		}

		$path = $this->get_dir( $_POST );

		if ( ! file_exists( $path ) ) {
			if ( ! mkdir( $path, 755, true ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Can not be created the folder blocks. Please check permissions of the folder uploads.', 'brizy' ) ) );
			}
		}

		$img_path = $path . DIRECTORY_SEPARATOR . sanitize_file_name( "{$_POST['block_id']}.{$img_type}" );

		if ( ! is_writable( $img_path ) || ! file_put_contents( $img_path, $img_base64 ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Can not be created the block screenshot.', 'brizy' ) ) );
		}

		wp_send_json_success();
	}

	public function load_block_screenshot() {

		if ( empty( $_GET['block_id'] ) || empty( $_GET['block_type'] ) || in_array( $_GET['block_type'], self::BLOCK_TYPES ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'There are no all required GET variables', 'brizy' ) ) );
		}

		$path = $this->get_dir( $_GET ) . sanitize_file_name( "{$_GET['block_id']}.jpg" );

		if ( ! file_exists( $path ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'The screenshot of the block does not exist.', 'brizy' ) ) );
		}

		wp_send_json_success( array( 'path' => $path ) );
	}

	private function get_dir( $arr ) {
		$block_type = $arr['block_type'];

		if ( 'saved' !== $block_type ) {
			$dir = 'saved-blocks';
		} elseif ( 'global' !== $block_type ) {
			$dir = 'global-blocks';
		} else {
			if ( empty( $arr['post_id'] ) || ! is_numeric( $arr['post_id'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Wrong ID of the post.', 'brizy' ) ) );
			}

			$dir = $arr['post_id'];
		}

		return Brizy_Editor_UploadsDir::get_path() . implode( DIRECTORY_SEPARATOR, array( $dir, 'assets', 'block-thumbnails' ) ) . DIRECTORY_SEPARATOR . $dir;
	}
}