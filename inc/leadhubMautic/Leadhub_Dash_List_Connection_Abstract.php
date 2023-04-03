<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

namespace Inc\LeadhubMautic;
use Inc\LibMautic\Leadhub_Dash_Api_Mautic;

abstract class Leadhub_Dash_List_Connection_Abstract {

	protected $_credentials = array();
	protected $_key = null;
	protected $api;
	protected $_api;
	protected $_error = '';
	public $_custom_fields_transient = '';
	protected $_mapped_custom_fields = array();

	public function __construct($apiInstance) {
		$this->api = $apiInstance;
	    $this->_api = Leadhub_Dash_Api_Mautic::getInstance();
	}
	  
	public static function getType() {
		return 'autoresponder';
	}

	public static function getEmailMergeTag() {
		return '[email]';
	}

	public function getApi() {
		if ( ! isset( $this->_api ) ) {
			$this->_api = $this->_apiInstance();
		}

		return $this->_api;
	}

	public function getCredentials() {
		return $this->_credentials;
	}

	public function setCredentials( $connectionDetails ) {
		$this->_credentials = $connectionDetails;

		return $this;
	}

	public function getKey() {
		return $this->_key;
	}

	public function setKey( $key ) {
		$this->_key = $key;

		return $this;
	}

	public function isConnected() {
		return ! empty( $this->_credentials );
	}

	public function isRelated() {
		return false;
	}

	public function param( $field, $default = null ) {
		return isset( $this->_credentials[ $field ] ) ? $this->_credentials[ $field ] : $default;
	}

	public function setParam( $field, $value ) {
		$this->_credentials[ $field ] = $value;

		return $this;
	}

	public function error( $message ) {
		if ( wp_doing_ajax() ) {
			return $message;
		}

		return $this->_message( 'error', $message );
	}

	public function success( $message ) {
		if ( wp_doing_ajax() ) {
			return true;
		}

		return $this->_message( 'success', $message );
	}

	public function save() {
		Leadhub_Dash_List_Manager::save( $this );

		return $this;
	}

	public function beforeDisconnect() {

		delete_transient( $this->_custom_fields_transient );

		return $this;
	}

	public function getApiError() {
		return $this->_error;
	}

	public abstract function getTitle();

	public abstract function outputSetupForm();

	public abstract function readCredentials();

	public abstract function testConnection();

	public abstract function addSubscriber( $list_identifier, $arguments );

	public function deleteSubscriber( $email, $arguments = array() ) {
		return false;
	}

	public function getLists( $use_cache = true ) {
		if ( ! $this->isConnected() ) {
			$this->_error = $this->getTitle() . ' is not connected';
			return false;
		}
	
		$cache = get_option( 'Leadhub_auto_responder_lists', array() );
		if ( ! $use_cache || ! isset( $cache[ $this->getKey() ] ) ) {
			$lists = $this->_getLists();
			if ( $lists !== false ) {
				$cache[ $this->getKey() ] = $lists;
				update_option( 'Leadhub_auto_responder_lists', $cache );
			}
		} else {
			$lists = $cache[ $this->getKey() ];
		}
	
		return $lists;
	}
	

	public function getGroups( $list_id ) {
		if ( ! $this->isConnected() ) {
			$this->_error = $this->getTitle() . ' is not connected';
			return false;
		}
	
		$params['list_id'] = $list_id;
	
		return $this->_getGroups( $params );
	}
	
	public function getListSubtitle() {
		return '';
	}

	public function get_automator_autoresponder_fields() {
		return array();
	}

	public function set_custom_autoresponder_fields( $fields, $field, $action_data ) {
		return $fields;
	}

	public function get_automator_autoresponder_tag_fields() {
		return array( 'tag_input' );
	}

	public function getWarnings() {
		return array();
	}

	public function get_extra_settings( $params = array() ) {
		do_action( 'tvd_autoresponder_render_extra_editor_settings_' . $this->getKey() );

		return array();
	}

	public function renderExtraEditorSettings( $params = array() ) {
		do_action( 'tvd_autoresponder_render_extra_editor_settings_' . $this->getKey() );

		return;
	}

	public function renderBeforeListsSettings( $params = array() ) {
		return;
	}

	public function customSuccessMessage() {
		return '';
	}

	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {

		$cache_data = get_transient( $this->_custom_fields_transient );

		return $cache_data ? $cache_data : array();
	}

	public function get_api_extra( $func, $params ) {

		$extra = array();

		if ( method_exists( $this, $func ) ) {
			$extra = call_user_func_array( array( $this, $func ), array( $params ) );
		}

		return array(
			'extra'             => $extra,
			'api_custom_fields' => $this->get_api_custom_fields( $params ),
		);
	}

	protected function _directFormHtml( $filename, $data = array() ) {
		include dirname( dirname( dirname( __FILE__ ) ) ) . '/views/setup/' . $filename . '.php';
	}

	protected function _message( $type, $message ) {
		Leadhub_Dash_List_Manager::message( $type, $message );

		return $this;
	}

	protected function _getNameParts( $full_name ) {
		if ( empty( $full_name ) ) {
			return array( '', '' );
		}
		$parts = explode( ' ', $full_name );

		if ( count( $parts ) == 1 ) {
			return array(
				$parts[0],
				'',
			);
		}
		$last_name  = array_pop( $parts );
		$first_name = implode( ' ', $parts );

		return array(
			sanitize_text_field( $first_name ),
			sanitize_text_field( $last_name ),
		);
	}

	protected function _getNameFromEmail( $email ) {

		if ( empty( $email ) || ! is_string( $email ) || false === strpos( $email, '@' ) ) {
			return array( '', '' );
		}

		$email_name = str_replace( array( '.', '_', '-', '+', '=' ), ' ', strstr( $email, '@', true ) );

		list( $first_name, $last_name ) = $this->_getNameParts( $email_name );

		if ( empty( $first_name ) ) {
			$first_name = $email_name;
		}

		if ( empty( $last_name ) ) {
			$last_name = $first_name;
		}

		return array(
			$first_name,
			$last_name,
		);
	}

	protected abstract function _apiInstance();
	protected abstract function _getLists();

	public function hasForms() {
		return false;
	}

	protected function _getForms() {
		return array();
	}

	public function getForms() {
		if ( ! $this->isConnected() ) {
			$this->_error = $this->getTitle() . ' is not connected';
			return false;
		}
	
		return $this->_getForms();
	}
	
	public function apiVideosUrls() {

		$return    = array();
		$transient = get_transient( 'ttw_api_urls' );

		if ( ! empty( $transient ) && is_array( $transient ) ) {
			$return = (array) $transient;
		}

		return $return;
	}

	public function display_video_link() {

		$api_slug   = strtolower( str_replace( array( ' ', '-' ), '', $this->getKey() ) );
		$video_urls = $this->apiVideosUrls();
		if ( ! array_key_exists( $api_slug, $video_urls ) ) {
			return '';
		}

		return include dirname( dirname( dirname( __FILE__ ) ) ) . '/views/includes/video-link.php';
	}

	public function get_version() {

		$credentials = (array) $this->getCredentials();

		if ( ! empty( $credentials['version'] ) ) {
			return $credentials['version'];
		}

		return false;
	}

	protected function normalize_custom_field( $data ) {

		return array(
			'id'    => $data['id'], //unique identifier
			'name'  => $data['name'], //should be name="" attribute for an input
			'type'  => $data['type'], //type for e.g. [url, text]
			'label' => $data['label'], //label to display for users
		);
	}

	public function get_email_param() {
		return $this->param( 'email', get_option( 'admin_email' ) );
	}

	public function api_log_error( $list_identifier, $data, $error ) {

		if ( ! $list_identifier || ! $data || ! $error ) {
			return false;
		}

		global $wpdb;

		$return        = false;
		$api_log_table = $wpdb->prefix . 'tcb_api_error_log';
		$table_exists  = ! ! $wpdb->get_var( $wpdb->prepare( 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $api_log_table ) );

		if ( $table_exists ) {
			$log_data = array(
				'date'          => date( 'Y-m-d H:i:s' ),
				'error_message' => tve_sanitize_data_recursive( $error ),
				'api_data'      => serialize( tve_sanitize_data_recursive( $data ) ),
				'connection'    => $this->getKey(),
				'list_id'       => maybe_serialize( tve_sanitize_data_recursive( $list_identifier ) ),
			);

			$return = (bool) $wpdb->insert( $api_log_table, $log_data );
		}

		return $return;
	}

	public function getMappedCustomFields() {

		return $this->_mapped_custom_fields;
	}

	protected function _get_cached_custom_fields() {

		return get_transient( $this->_custom_fields_transient );
	}

	protected function _save_custom_fields( $custom_fields = array() ) {

		if ( empty( $custom_fields ) ) {
			return false;
		}

		$custom_fields = tve_sanitize_data_recursive( $custom_fields );

		return set_transient( $this->_custom_fields_transient, $custom_fields, WEEK_IN_SECONDS );
	}

	public function processField( $field ) {
		if ( is_array( $field ) ) {
			$field = join( ", ", $field );
		}

		return stripslashes( $field );
	}

	public function hasTags() {

		return false;
	}

	public function getTagsKey() {

		return $this->_key . '_tags';
	}

	public function getFormsKey() {
		return $this->_key . '_form';
	}

	public function getOptinKey() {

		return $this->_key . '_optin';
	}

	protected function getMappedFieldsIDs() {

		$mapped_fields = array_map(
			function ( $field ) {
				return $field['id'];
			},
			$this->_mapped_custom_fields
		);

		array_push( $mapped_fields, 'user_consent' );

		return $mapped_fields;
	}

	public function pushTags( $tags, $data = array() ) {

		if ( ! $this->hasTags() && ( ! is_array( $tags ) || ! is_string( $tags ) ) ) {
			return $data;
		}

		$_key = $this->getTagsKey();

		if ( ! isset( $data[ $_key ] ) ) {
			$data[ $_key ] = '';
		}

		if ( is_array( $tags ) ) {
			$tags = implode( ', ', $tags );
		}

		$data[ $_key ] = empty( $data[ $_key ] )
			? $tags
			: $data[ $_key ] . ', ' . $tags;

		$data[ $_key ] = trim( $data[ $_key ] );

		return $data;
	}

	public function canEdit() {
		return true;
	}

	public function canDelete() {
		return true;
	}

	public function canTest() {
		return true;
	}

	public function getDataForSetup() {
		return array();
	}

	public function getWebhookdata( $request ) {
		return array();
	}

	public function updateTags( $email, $tags = '', $extra = array() ) {

		$args            = $this->getArgsForTagsUpdate( $email, $tags, $extra );
		$list_identifier = ! empty( $args['list_identifier'] ) ? $args['list_identifier'] : null;

		unset( $args['list_identifier'] );

		return $this->addSubscriber( $list_identifier, $args );
	}

	public function getArgsForTagsUpdate( $email, $tags = '', $extra = array() ) {

		$tags_key = $this->getTagsKey();

		$return = array(
			'email'   => $email,
			$tags_key => $tags,
		);

		foreach ( $extra as $key => $value ) {
			$return[ $key ] = $value;
		}

		return $return;
	}

	public function addCustomFields( $email, $custom_fields = array(), $extra = array() ) {

		return 0;
	}

	protected function _prepareCustomFieldsForApi( $custom_fields = array(), $list_identifier = null ) {

		return array();
	}

	public function getAvailableCustomFields( $data = array() ) {

		return method_exists( $this, 'getAllCustomFields' ) ? $this->getAllCustomFields( true ) : array();
	}

	protected function post( $key, $default = null ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}

		return map_deep( $_POST[ $key ], 'sanitize_text_field' );
	}
}

