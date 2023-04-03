<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Api 
{
    protected $endpoint;
    protected $baseUrl;
    private $auth;
    private $logger;

    public function __construct(Leadhub_Dash_Api_Mautic_AuthInterface $auth, $baseUrl = '') 
    {
        $this->auth = $auth;
        $this->setBaseUrl($baseUrl);
    }

    public function getLogger() 
    {
        // Se o logger não tiver sido definido, use NullLogger
        if (!($this->logger instanceof LoggerInterface)) 
        {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger) 
    {
        $this->logger = $logger;
        return $this;
    }

    public function setBaseUrl($url) 
    {
        if (substr($url, -1) !== '/') 
        {
            $url .= '/';
        }
        if (substr($url, -4) !== 'api/') 
        {
            $url .= 'api/';
        }
        $this->baseUrl = $url;
        return $this;
    }

    protected function actionNotSupported($action) 
    {
        return [
            'error' => [
                'code' => 500,
                'message' => "$action não é suportado no momento."
            ]
        ];
    }

    public function makeRequest($endpoint, array $parameters = [], $method = 'GET') 
    {
        $url = $this->baseUrl . $endpoint;
        if (strpos($url, 'http') === false) 
        {
            return [
                'error' => [
                    'code' => 500,
                    'message' => sprintf(
                        'A URL está incompleta. Por favor, utilize %s, defina a URL base como terceiro argumento em MauticApi::getContext(), ou faça de $endpoint uma URL completa.',
                        __CLASS__ . 'setBaseUrl()'
                    )
                ]
            ];
        }
        try 
        {
            $response = $this->auth->makeRequest($url, $parameters, $method);
            if (!is_array($response)) 
            {
                // assume um erro
                return [
                    'error' => [
                        'code' => 500,
                        'message' => $response
                    ]
                ];
            }
            if (isset($response['error'], $response['error_description'])) 
            {
                $message = $response['error'] . ': ' . $response['error_description'];
                throw new Leadhub_Dash_Api_Mautic_IncorrectParametersReturnedException($message, $code = 403);
            }
        } 
        catch (Exception $e) 
        {
            return [
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ]
            ];
        }
        // retorna a resposta se nenhuma condição de erro for atendida
        return $response;
    }

    public function get($id) 
    {
        return $this->makeRequest("{$this->endpoint}/$id");
    }

public function getList($search = '', $start = 0, $limit = 0, $orderBy = '', $orderByDir = 'ASC', $publishedOnly = false) 
{
    $parameters = [];
    $args = ['search', 'start', 'limit', 'orderBy', 'orderByDir', 'publishedOnly'];

    foreach ($args as $arg) 
    {
        if (!empty($$arg)) 
        {
            $parameters[$arg] = $$arg;
        }
    }
    return $this->makeRequest($this->endpoint, $parameters);
}

public function getPublishedList($search = '', $start = 0, $limit = 0, $orderBy = '', $orderByDir = 'ASC') 
{
    return $this->getList($search, $start, $limit, $orderBy, $orderByDir, true);
}

public function create(array $parameters) 
{
    return $this->makeRequest($this->endpoint . '/new', $parameters, 'POST');
}

public function edit($id, array $parameters, $createIfNotExists = false) 
{
    $method = $createIfNotExists ? 'PUT' : 'PATCH';
    return $this->makeRequest($this->endpoint . '/' . $id . '/edit', $parameters, $method);
}

public function delete($id) 
{
    return $this->makeRequest($this->endpoint . '/' . $id . '/delete', [], 'DELETE');
}
}