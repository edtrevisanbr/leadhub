<?php
namespace Src\Admin\Partials;

use Mautic\MauticApi;
use Src\Helpers\LeadhubMauticAuth;

class LeadhubMauticTestConnection {
    
    public function render() {
        $this->start_session();
        $this->leadhub_mautic_integration_test_api_connection();
        $this->leadhub_mautic_integration_test_connection();
    }

    private function start_session() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('wp-leadhub-mautic');
            session_start();
        }
    }

    function leadhub_mautic_integration_test_connection()
    {
        ?>
        <div class="wrap-leadhub-settings">
            <h3>Teste a conexão API com o Mautic</h3>
            <form method="post" action="">
                <input type="hidden" name="page" value="leadhub-mautic-integration-test-connection">
                <input type="submit" name="test_connection" class="button button-primary" value="Testar Conexão">
            </form>
        </div>
        <?php
        if (isset($_POST['test_connection'])) {
            $connection_result = $this->leadhub_mautic_integration_test_api_connection();
            if ($connection_result === "Conexão bem-sucedida e pelo menos 1 contato encontrado.") {
                echo '<div class="notice notice-success is-dismissible"><p>' . $connection_result . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . $connection_result . '</p></div>';
            }
        }
    }

   
    function leadhub_mautic_integration_test_api_connection() {
        $page = 'leadhub-mautic-test-connection';
        $auth = (new LeadhubMauticAuth())->get_auth($page);

        if ($auth === null) {
            return "Erro: Parâmetros incorretos retornados durante a solicitação do token de acesso. Verifique suas configurações do Mautic e tente novamente.";
        }

        $baseUrl = get_option('leadhub_mautic_base_url');
        $version = get_option('leadhub_mautic_auth_type');
        $publicKey = get_option('leadhub_mautic_public_key');
        $secretKey = get_option('leadhub_mautic_secret_key');
    
        if (!empty($baseUrl) && $version === 'OAuth2' && !empty($publicKey) && !empty($secretKey)) {
            // Utilize a classe LeadhubMauticAuth para obter o objeto de autenticação
            $leadhubMauticAuth = new LeadhubMauticAuth();
            $auth = $leadhubMauticAuth->get_auth($page);
    
            if ($auth->validateAccessToken()) {
                $accessTokenData = $auth->getAccessTokenData();
                update_option('leadhub_mautic_access_token_data', json_encode($accessTokenData));
                $auth->accessTokenUpdated();
    
                $contactApi = MauticApi::getContext("contacts", $auth, $baseUrl . '/api/');
                $contacts = $contactApi->getList('', 0, 1);
                $contacts = $contacts['contacts'];
    
                if (isset($contacts['contacts']) && count($contacts['contacts']) > 0) {
                    return "Conexão bem-sucedida e pelo menos 1 contato encontrado.";
                } else {
                    return "Conexão bem-sucedida, mas nenhum contato foi encontrado.";
                }
            } else {
                return "Falha na validação do token de acesso. Por favor, verifique suas configurações do Mautic.";
            }
        } else {
            return "Por favor, verifique suas configurações do Mautic.";
        }
    }
    
   
}
