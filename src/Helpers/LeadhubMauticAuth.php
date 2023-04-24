<?php

namespace Src\Helpers;

use Mautic\Auth\ApiAuth;
use Mautic\Exception\IncorrectParametersReturnedException;
use Src\Helpers\LeadhubErrorLog;

class LeadhubMauticAuth
{

    private function get_callback_url($page) {
        $valid_pages = ['leadhub-mautic-test-connection', 'leadhub-contacts-log'];

        if (in_array($page, $valid_pages)) {
            return admin_url('admin.php?page=' . $page);
        } else {
            return admin_url('admin.php?page=leadhub-mautic-settings');
        }
    }
    
    public function get_auth($page='') {
        LeadhubErrorLog::leadhub_error_log("Valor de \$page: " . $page);
    {
        $baseUrl = get_option('leadhub_mautic_base_url');
        $version = get_option('leadhub_mautic_auth_type');
        $publicKey = get_option('leadhub_mautic_public_key');
        $secretKey = get_option('leadhub_mautic_secret_key');
        $callback = $this->get_callback_url($page);

        if (!empty($baseUrl) && $version === 'OAuth2' && !empty($publicKey) && !empty($secretKey)) {
            $settings = array(
                'baseUrl' => $baseUrl,
                'version' => $version,
                'clientKey' => $publicKey,
                'clientSecret' => $secretKey,
                'callback' => $callback,
            );

            LeadhubErrorLog::leadhub_error_log("Iniciando a autenticação com as configurações");
            $auth = ApiAuth::initiate($settings);
            $accessTokenData = get_option('leadhub_mautic_access_token_data');
            LeadhubErrorLog::leadhub_error_log('Pegando options do banco de dados');

            if (isset($accessTokenData) && !empty($accessTokenData)) {
                $auth->setAccessTokenDetails(json_decode($accessTokenData, true));
                LeadhubErrorLog::leadhub_error_log("Detalhes do token de acesso configurados: " . json_encode($accessTokenData));
            }

            try {
                LeadhubErrorLog::leadhub_error_log('Antes de chamar validateAccessToken');

                if ($auth->validateAccessToken()) {
                    LeadhubErrorLog::leadhub_error_log("Validação do token de acesso bem-sucedida");
                    $accessTokenData = $auth->getAccessTokenData();
                    update_option('leadhub_mautic_access_token_data', json_encode($accessTokenData));
                    $auth->accessTokenUpdated();
                    LeadhubErrorLog::leadhub_error_log('Atenticação retornada com sucesso');
                    return $auth;
                } else {
                    LeadhubErrorLog::leadhub_error_log('Falha ao chamar validateAccessToken');
                }
            } catch (IncorrectParametersReturnedException $e) {
                error_log("Mautic Auth Error: " . $e->getMessage());
                LeadhubErrorLog::leadhub_error_log("Exceção de parâmetros incorretos capturada: " . $e->getMessage());
                return null;
            } catch (\Exception $e) {
                error_log("Mautic Auth Error (Outra Exceção): " . $e->getMessage());
                LeadhubErrorLog::leadhub_error_log("Outra exceção capturada: " . $e->getMessage());
                return null;
            }
        }

        return null;
    }
}
}