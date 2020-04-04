<?php

namespace Chtombleson\Typesense;

use GuzzleHttp\Client as HttpClient;

class Client
{
    protected $host;
    protected $port;
    protected $protocol;
    protected $api_key;
    protected $base_path;

    public function __construct(array $config)
    {
        if (empty($config['host']) || empty($config['api_key'])) {
            throw new \RuntimeException('No host or api_key set.');
        }

        $this->setHost($config['host']);
        $this->setApiKey($config['api_key']);

        if (empty($config['port'])) {
            $this->setPort(8180);
        } else {
            $this->setPort($config['port']);
        }

        if (empty($config['protocol'])) {
            $this->setProtocol('http');
        } else {
            $this->setProtocol($config['protocol']);
        }

        if (!empty($config['base_path'])) {
            $this->base_path = $config['base_path'];
        } else {
            $this->base_path = '/';
        }
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function setProtocol($protocol)
    {
        if (!in_array($protocol, ['http', 'https'])) {
            throw new \RuntimeException('Protocol call must be either http or https.');
        }

        $this->protocol = $protocol;

        return $this;
    }

    public function getApiKey()
    {
        return $this->api_key;
    }

    public function setApiKey($api_key)
    {
        if (empty($api_key)) {
            throw new \RuntimeException('Api key cannot be empty.');
        }

        $this->api_key = $api_key;

        return $this;
    }

    public function getCollection($collection)
    {
        return new Collection($collection, $this);
    }

    public function getAllCollections()
    {
        $collections = [];
        $response = $this->sendRequest('GET', 'collections');

        if ($response->getStatusCode() == 200) {
            $json = json_decode((string) $response->getBody(), true);

            foreach ($json['collections'] as $collection) {
                $collections[] = $this->getCollection($collection['name']);
            }
        }

        return $collections;
    }

    public function createCollection(array $config)
    {
        if (empty($config['name']) || empty($config['fields']) || empty($config['default_sorting_field'])) {
            throw new \RuntimeException('Collection config requires the following: name, fields & default_sorting_field.');
        }

        $response = $this->sendRequest('POST', 'collections', $config);

        if ($response->getStatusCode() == 201) {
            return $this->getCollection($config['name']);
        }
    }

    public function deleteCollection($collection)
    {
        $response = $this->sendRequest('DELETE', 'collections/' . $collection);

        if ($response->getStatusCode() == 200) {
            return true;
        }

        return false;
    }

    public function isHealthy()
    {
        $response = $this->sendRequest('GET', 'health');

        if ($response->getStatusCode() == 200) {
            $json = json_decode((string) $response->getBody(), true);

            return $json['ok'];
        }

        return false;
    }

    protected function getBaseUri()
    {
        return $this->getProtocol() . '://' . $this->getHost() . ':' . $this->getPort() . $this->base_path;
    }

    public function sendRequest($method, $endpoint, array $data = [])
    {
        $client = new HttpClient([
            'base_uri' => $this->getBaseUri(),
            'headers' => [
                'X-TYPESENSE-API-KEY' => $this->getApiKey(),
            ],
        ]);

        switch (strtolower($method)) {
            case 'get':
                if (empty($data)) {
                    return $client->request(strtoupper($method), $endpoint);
                } else {
                    return $client->request(strtoupper($method), $endpoint, ['query' => $data]);
                }
                break;

            case 'delete':
                return $client->request(strtoupper($method), $endpoint);
                break;

            case 'post':
                return $client->request(
                    strtoupper($method),
                    $endpoint,
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode($data),
                    ]
                );
                break;

            default:
                throw new \RuntimeException('Request method ' . $method . ' is not supported.');
        }
    }
}
