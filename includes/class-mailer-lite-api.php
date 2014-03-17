<?php
	
class Mailer_Lite_Api {
	
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;
	
	private $api_base_url = null;
	
	private function __construct() {
		$this->api_base_url = 'https://app.mailerlite.com/api/v1/';
	}
	
	public function get_lists ( $api_key ) {
		return $this->get_data( $this->api_base_url . 'lists/?apiKey=' . $api_key );
	}
	
	public function get_list_info( $api_key, $list_id ) {
		//return array( 'error' => array( 'code' => 'url', 'message' => $this->api_base_url . 'lists/' . $list_id . '/?apiKey=' . $api_key ) );
		return $this->get_data( $this->api_base_url . 'lists/' . $list_id . '/?apiKey=' . $api_key );
	}
	
	public function get_subscriber_details( $api_key, $email ) {
		return $this->get_data( $this->api_base_url . 'subscribers/?apiKey=' . $api_key . '&email=' . urlencode($email) );
	}
	
	public function add_subscriber( $api_key, $list_id, $email ) {
		return $this->post_data( $this->api_base_url . 'subscribers/' . $list_id . '/', array( 'apiKey' => $api_key, 'email' => $email ) );
	}
	
	public function remove_subscriber( $api_key, $list_id, $email ) {
		return $this->delete_data( $this->api_base_url . 'subscribers/' . $list_id . '/?apiKey=' . $api_key . '&email=' . urlencode($email) );
	}
	
	private function get_data( $url ) {
		$response = wp_remote_get( $url );
		return $this->process_http_request($response);
	}
	
	private function post_data( $url, $data ) {
		$response = wp_remote_post( $url, array( 'body' => $data ) );
		return $this->process_http_request($response);
	}
	
	private function delete_data( $url ) {
		$response = wp_remote_post( $url, array( 'method' => 'DELETE' ) );
		return $this->process_http_request($response);
	}
	
	private function process_http_request($response) {
		if ( is_wp_error( $response ) ) {
		   return array(
				'error' => array(
					'code'    => $response->get_error_code(),
					'message' => $response->get_error_message()
				)
		   );
		}
		
		if ( $response['response']['code'] != 200 ) {
			return array(
				'error' => array(
					'code'    => $response['response']['code'],
					'message' => $response['response']['message']
				)
			);
		}
		
		try {
            // Note that we decode the body's response since it's the actual JSON feed
            $json = json_decode( $response['body'], true );
            return $json;
        } catch ( Exception $ex ) {
			return array(
				'error' => array(
					'code'    => 0,
					'message' => 'json error'
				)
			);
        } // end try/catch
	}
		
	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}
