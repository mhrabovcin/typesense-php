<?php

namespace Chtombleson\Typesense;

class Collection
{
    protected $client;
    protected $name;

    public function __construct($name, Client $client)
    {
        $this->client = $client;
        $this->name = $name;
    }

    public function sendRequest($method, $endpoint, array $data = [], $parse_json = TRUE) {
        $path = "collections/" . $this->name . "/documents";
        if (!empty($endpoint)) {
            $path = $path . '/' . $endpoint;
        }

        $response = $this->client->sendRequest($method, $path, $data);
        if ($parse_json) {
            return $this->jsonResponse($response);
        }

        return $response;
    }

    protected function jsonResponse($response) {
        return json_decode((string) $response->getBody(), TRUE);
    }

    public function indexDocument(array $data) {
        return $this->sendRequest('post', '', $data);
    }

    public function searchDocument(array $params) {
        return $this->sendRequest('get', 'search', $params);
    }

    public function retrieveDocument($id) {
        return $this->sendRequest('get', $id);
    }

    public function deleteDocument($id) {
        return $this->sendRequest('delete', $id);
    }

    public function retrieveCollection() {
        return $this->client->sendRequest('get', 'collections/' . $this->name);
    }

    public function exportDocuments() {
        return $this->sendRequest('get', 'export');
    }

    public function importDocuments($jsonl) {
        // TODO
    }
}
