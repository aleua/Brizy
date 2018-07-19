<?php

class Brizy_Editor_BlockScreenshot {

	const AJAX_SAVE_BLOCK_SCREENSHOT = 'brizy_save_block_screenshot';

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

		$this->load_block_screenshot();
		$this->save_block_screenshot();

		if ( isset( $_GET['blockId'] ) ) {
			$this->load_block_screenshot();
		} else {
			if ( isset( $_REQUEST['hash'] ) && wp_verify_nonce( $_REQUEST['hash'], Brizy_Editor_API::nonce ) ) {
				add_action( 'wp_ajax_' . self::AJAX_SAVE_BLOCK_SCREENSHOT, array( $this, 'save_block_screenshot' ) );
			}
		}
	}

	public function save_block_screenshot() {

		$block_id = $_POST['block_id'];
		$dir = 'saved-blocks';

		if ( false !== strpos( 'saved', $block_id ) ) {
			$dir = 'saved-blocks';
		} elseif ( false !== strpos( 'global', $block_id ) ) {
			$dir = 'global-blocks';
		} elseif ( ! empty( $_POST['post_id'] ) ) {
			$dir = abs( $_POST['post_id'] );
		}

		$path = Brizy_Editor_UploadsDir::get_path() . implode( DIRECTORY_SEPARATOR, array( $dir, 'assets', 'thumbnails' ) );

		if ( ! is_writable( $path ) ) {
			// return error;
		}

		if ( preg_match( '/^data:image\/(\w+);base64,/', $data, $type ) ) {
			$data = substr( $data, strpos( $data, ',' ) + 1 );
			$type = strtolower( $type[1] ); // jpg, png, gif

			if ( ! in_array( $type, array( 'jpg', 'jpeg', 'gif', 'png' ) ) ) {
				// return error;
			}

			$data = base64_decode( $data );

			if ( false === $data ) {
				// return error;
			}
		} else {
			// return error;
		}

		file_put_contents( "img.{$type}", $data );
	}

	public function load_block_screenshot() {

	}
}