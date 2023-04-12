<?php
namespace Src\Admin\Partials;

require_once __DIR__ . '/../../../vendor/autoload.php';
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

class LeadhubMauticSettings{

    public $settings_updated = false;

    public function render(){
        echo "<h2>Leadhub Mautic Settings</h2>";
        $this->leadhub_mautic_integration_setting();
        $message = null;
        if ($this->settings_updated){
            echo '<div id="message" class="updated is-dismissible"><p>Dados salvos com sucesso!</p></div>';
        }
    }


    /** * Display Setting page */
    public function leadhub_mautic_integration_setting()
    {
        if (isset($_POST['submit']) && check_admin_referer('leadhub_mautic_integration_settings')) {
            $publicKey = get_option('leadhub_mautic_public_key');
            $secretKey = get_option('leadhub_mautic_secret_key');
            $leadhub_mautic_public_key = sanitize_text_field($_POST['leadhub_mautic_public_key']);
            $leadhub_mautic_secret_key = sanitize_text_field($_POST['leadhub_mautic_secret_key']);
            if ($publicKey != $leadhub_mautic_public_key || $secretKey != $leadhub_mautic_secret_key) {
                update_option('leadhub_mautic_access_token_data', '');
                session_destroy();
            }
            $leadhub_mautic_drop_data = isset($_POST['leadhub_mautic_drop_data']) ? intval($_POST['leadhub_mautic_drop_data']) : '0';
            update_option('leadhub_mautic_base_url', esc_url(trim($_POST['leadhub_mautic_base_url'], '/')));
            update_option('leadhub_mautic_auth_type', 'OAuth2');
            update_option('leadhub_mautic_public_key', $leadhub_mautic_public_key);
            update_option('leadhub_mautic_secret_key', $leadhub_mautic_secret_key);
            update_option('leadhub_mautic_drop_data', $leadhub_mautic_drop_data);
            $this->settings_updated = true;
        }

        if (isset($_GET['settings-updated'])) {
            echo '<div id="message" class="updated is-dismissible"><p>Settings saved successfully.</p></div>';
        }
        ?>
        <div class="wrap">
            <form method="post" action="" id="leadhub_mautic_settings_form">
                <?php wp_nonce_field('leadhub_mautic_integration_settings'); ?>
                    <tr valign="top">
                        <th scope="row">Auth Type</th>
                        <td>
                            <input type="hidden" name="leadhub_mautic_auth_type" value="OAuth2"/>
                            OAuth 2
                        </td>
                    </tr>

                     <tr valign="top">
                        <th scope="row">Mautic Base URL</th>
                        <td>
                            <input type="text" name="leadhub_mautic_base_url" value="<?php echo esc_url(get_option('leadhub_mautic_base_url')); ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Mautic Public Key</th>
                        <td>
                            <input type="text" name="leadhub_mautic_public_key" value="<?php echo esc_attr(get_option('leadhub_mautic_public_key')); ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Mautic Secret Key</th>
                        <td>
                            <input type="text" name="leadhub_mautic_secret_key" value="<?php echo esc_attr(get_option('leadhub_mautic_secret_key')); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Drop database & options on uninstall</th>
                        <td>
                            <input name="leadhub_mautic_drop_data" type="checkbox" value="1" <?php checked('1', intval(get_option('leadhub_mautic_drop_data'))); ?>/>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                <input type="button" id="test_api_connection" class="button button-secondary" value="Test API Connection" />
            </form>
        </div>
        <?php
    }

    /**
 * Test API connection
 * @param string $page
 * @return bool
 */  
public function test_api_connection() {
        $baseUrl = $_POST['baseUrl'];
        $publicKey = $_POST['publicKey'];
        $secretKey = $_POST['secretKey'];
    
        $result = $this->leadhub_mautic_integration_get_forms_from_server($baseUrl, $publicKey, $secretKey);
    
        if ($result) {
            wp_send_json_success(array('message' => 'A conexão API com o Mautic foi bem sucedida'));
        } else {
            wp_send_json_error(array('message' => 'Não foi possível estabelecer uma conexão API com o Mautic'));
        }
    
        wp_die();
    }
    

/**
 * Get form from Mautic server using Mautic API
 * @param string $page
 * @return bool
 */
function leadhub_mautic_integration_get_forms_from_server($baseUrl, $publicKey, $secretKey, $page='leadhub-mautic-integration-setting') {
    $baseUrl = get_option('leadhub_mautic_base_url');
    $version = get_option('leadhub_mautic_auth_type');
    $publicKey = get_option('leadhub_mautic_public_key');
    $secretKey = get_option('leadhub_mautic_secret_key');

    if (!empty($baseUrl) && !empty($version) && !empty($publicKey) && !empty($secretKey)) {
        $callback = admin_url('admin.php?page=' . $page);

        $settings = array(
            'baseUrl' => $baseUrl,
            'version' => $version,
            'clientKey' => $publicKey,
            'clientSecret' => $secretKey,
            'callback' => $callback
        );

        $auth = ApiAuth::initiate($settings);
        $accessTokenData = get_option('leadhub_mautic_access_token_data');

        if (isset($accessTokenData) && !empty($accessTokenData)) {
            $auth->setAccessTokenDetails(json_decode($accessTokenData, true));
        }

        if ($auth->validateAccessToken()) {
            $accessTokenData = $auth->getAccessTokenData();
            update_option('leadhub_mautic_access_token_data', json_encode($accessTokenData));
            $auth->accessTokenUpdated();
    
            $message = '<div id="message" class="updated is-dismissible"><p>A conexão API com o Mautic foi bem sucedida</p></div>';
        } else {
            $message = '<div id="message" class="error is-dismissible"><p>Não foi possível estabelecer uma conexão API com o Mautic</p></div>';
        }
    
        return true;
    }

    return false;
}


}
