<?php
namespace Src\Admin\Partials;

use Mautic\MauticApi;
use Src\Helpers\LeadhubMauticAuth;
use Src\Helpers\LeadhubErrorLog;

ob_start(); // Inicie o buffer de saída

class LeadhubContactsLog {
    
    /**
     * Render functions
     */
    public function render() {
        self::leadhub_mautic_integration_display_contacts();
    }
    
    public static function leadhub_mautic_integration_display_contacts() {
        $baseUrl = get_option('leadhub_mautic_base_url');
        
        // Utilize a classe LeadhubMauticAuth para obter o objeto de autenticação
        $leadhubMauticAuth = new LeadhubMauticAuth();
        $auth = $leadhubMauticAuth->get_auth(); // Agora, 'leadhub-mautic-settings' é usado como valor padrão
        LeadhubErrorLog::leadhub_error_log('Atenticação');
        
        if ($auth){
            // Verifica se o botão foi clicado
            if (isset($_POST['listar_contatos'])) {
                $contactApi = MauticApi::getContext("contacts", $auth, $baseUrl . '/api/');
                LeadhubErrorLog::leadhub_error_log('Get Context');
                $contacts = $contactApi->getList(['orderBy' => ['id' => 'ASC'], 'page' => 1, 'limit' => 30]);
                $contacts = $contacts['contacts'];
                LeadhubErrorLog::leadhub_error_log('Lista de contatos');
                
                if (count($contacts) > 0) {
                    echo "<h2>Lista de Contatos no Mautic</h2>";
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Nome</th><th>Email</th></tr>";
                    
                    foreach ($contacts as $contact) {
                        if (isset($contact['fields']['core']['email']['value'])) {
                            echo "<tr>";
                            echo "<td>{$contact['id']}</td>";
                            echo "<td>{$contact['fields']['core']['firstname']['value']}{$contact['fields']['core']['lastname']['value']}</td>";
                            echo "<td>{$contact['fields']['core']['email']['value']}</td>";
                            echo "</tr>";
                        }
                    }
                    
                    echo "</table>";
                    
                    // Adiciona a paginação, somente se a chave 'total' existir
                    if (isset($contacts['total'])) {
                        $totalPages = ceil($contacts['total'] / 30);
                        
                        if ($totalPages > 1) {
                            echo "<div>";
                            
                            for ($i = 1; $i <= $totalPages; $i++) {
                                echo "<a href='?page=$i'>$i</a> ";
                            }
                            
                            echo "</div>";
                        }
                    }
                } else {
                    echo "Nenhum contato encontrado.";
                }
            }
            
            // Adiciona o botão para listar os contatos
            echo "<form method='post'>";
            echo "<button type='submit' name='listar_contatos'>Listar Contatos</button>";
            echo "</form>";
            
        } else {
            echo "Não foi possível conectar ao Mautic. Verifique suas configurações.";
        }
    }
}
