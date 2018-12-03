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
}
