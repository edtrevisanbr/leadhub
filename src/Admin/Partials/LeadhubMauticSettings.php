<?php

namespace Src\Admin\Partials;

class LeadhubMauticSettings{

    public $settings_updated = false;

    /**
     * Render functions
     */
    public function render()
    {
        $this->leadhub_mautic_integration_setting();
    }

    /** 
     * Display Setting page 
     */
    public function leadhub_mautic_integration_setting(){
        $errors=array();
        if (isset($_POST['submit']) && check_admin_referer('leadhub_mautic_integration_settings')){
            $required_fields=array(
                'leadhub_mautic_base_url'=> 'Mautic Base URL',
                'leadhub_mautic_public_key'=> 'Mautic Public Key',
                'leadhub_mautic_secret_key'=> 'Mautic Secret Key'
            );
            foreach ($required_fields as $field=> $label){
                if (empty($_POST[$field])){
                    $errors[]=$label . ' é obrigatório.';
                }
            }
            if (empty($errors)){
                $leadhub_mautic_public_key=sanitize_text_field($_POST['leadhub_mautic_public_key']);
                $leadhub_mautic_secret_key=sanitize_text_field($_POST['leadhub_mautic_secret_key']);
                $leadhub_mautic_drop_data=isset($_POST['leadhub_mautic_drop_data']) ? intval($_POST['leadhub_mautic_drop_data']) : '0';
                
                $publicKey  = get_option( 'leadhub_mautic_public_key' );
                $secretKey  = get_option( 'leadhub_mautic_secret_key' );
                
                update_option('leadhub_mautic_base_url', esc_url(trim($_POST['leadhub_mautic_base_url'], '/')));
                update_option('leadhub_mautic_auth_type', 'OAuth2');
                update_option('leadhub_mautic_public_key', $leadhub_mautic_public_key);
                update_option('leadhub_mautic_secret_key', $leadhub_mautic_secret_key);
                update_option('leadhub_mautic_drop_data', $leadhub_mautic_drop_data);
                
                if ($publicKey != $leadhub_mautic_public_key || $secretKey != $leadhub_mautic_secret_key) {
                    update_option('leadhub_mautic_access_token_data', '');
                    if (session_status() == PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                }
                
                
                $this->settings_updated=true;
            }
        }
    
        // Exibir mensagens de erro, se houver
        if (!empty($errors)) {
            echo '<div class="notice notice-error is-dismissible">';
            foreach ($errors as $error) {
                echo '<p>' . $error . '</p>';
            }
            echo '</div>';
        }
    
        if (isset($_GET['settings-updated'])) {
            echo '<div id="message" class="updated is-dismissible"><p>Dados salvos com</p></div>';
        }
        ?>
            <div class="wrap-leadhub-settings">
            
                <h2>Leadhub Mautic Settings</h2><br>
                <form method="post" action="" id="leadhub_mautic_settings_form">
                    <?php wp_nonce_field('leadhub_mautic_integration_settings'); ?>
                    <div class="leadhub-section">
                        <label for="leadhub_mautic_auth_type" class="leadhub-label">Auth Type</label>
                        <div class="leadhub-input">
                            <input type="hidden" name="leadhub_mautic_auth_type" value="OAuth2"/>
                            OAuth2
                        </div>
                    </div>
                    <div class="leadhub-section">
                        <label for="leadhub_mautic_base_url" class="leadhub-label">Mautic Base URL</label>
                        <div class="leadhub-input">
                            <input type="text" name="leadhub_mautic_base_url" value="<?php echo esc_url(get_option('leadhub_mautic_base_url')); ?>"/>
                        </div>
                    </div>
                    <div class="leadhub-section">
                        <label for="leadhub_mautic_public_key" class="leadhub-label">Mautic Public Key</label>
                        <div class="leadhub-input">
                            <input type="text" name="leadhub_mautic_public_key" value="<?php echo esc_attr(get_option('leadhub_mautic_public_key')); ?>"/>
                        </div>
                    </div>
                    <div class="leadhub-section">
                        <label for="leadhub_mautic_secret_key" class="leadhub-label">Mautic Secret Key</label>
                        <div class="leadhub-input">
                            <input type="text" name="leadhub_mautic_secret_key" value="<?php echo esc_attr(get_option('leadhub_mautic_secret_key')); ?>"/>
                        </div>
                    </div>
                    <div class="leadhub-section">
                        <label for="leadhub_mautic_drop_data" class="leadhub-label">Deletar options do banco de dados ao desinstalar</label>
                        <div class="leadhub-input">
                            <input name="leadhub_mautic_drop_data" type="checkbox" value="1" <?php checked('1', intval(get_option('leadhub_mautic_drop_data'))); ?>/>
                        </div>
                    </div>
                    <?php submit_button('Salvar Configurações'); ?>
                </form>
            </div>

             <?php
                if ($this->settings_updated) {
                echo '<div id="message" class="updated is-dismissible"><p>Dados salvos com sucesso!</p></div>';
            }
            ?>
            
            <hr class="leadhub-section--separator">

            <div class="leadhub-section">
                <input type="button" onclick="location.href='?page=leadhub-mautic-test-connection'" class="button button-primary" value="Testar Conexão">
            </div>


        <?php

    
}


}