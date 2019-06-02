<?php

namespace ObjectStorage\Adapter;

use Bergen\Client\ClientBuilder;
use Bergen\Client\ClientOptions;
use Bergen\Client\Error\UnexpectedResponseError;
use Bergen\Client\V1\V1StorageClient;
use GuzzleHttp\RequestOptions;

class BergenAdapter implements BuildableAdapterInterface, StorageAdapterInterface
{
    /**
     * @var \Bergen\Client\V1\V1StorageClient
     */
    private $client;

    public static function build(array $config)
    {
        if (!array_key_exists('host', $config)) {
            throw new InvalidArgumentException(
                'Unable to build BergenAdapter: missing "host" from configuration.'
            );
        }
        $clientOpts = [
            ClientOptions::API_HOST => trim($config['host']),
        ];
        if (isset($config['username']) && isset($config['password'])) {
            $clientOpts[RequestOptions::AUTH] = [
                trim($config['username']),
                $config['password'],
            ];
        }
        if (array_key_exists('secure', $config)) {
            $clientOpts[ClientOptions::SECURE_HTTP] = (bool) $config['secure'];
        }

        return new self(new V1StorageClient(new ClientBuilder($clientOpts)));
    }

    public function __construct(V1StorageClient $client)
    {
        $this->client = $client;
    }

    public function setData($key, $data)
    {
        try {
            $this->client->put(rawurlencode($key), $data);
        } catch (UnexpectedResponseError $e) {
            throw new AdapterException('Unable to set data.', null, $e);
        }
    }

    public function getData($key)
    {
        $response = null;
        try {
            $response = $this->client->get(rawurlencode($key));
        } catch (UnexpectedResponseError $e) {
            throw new AdapterException('Unable to get data.', null, $e);
        }

        return (string) $response->getBody();
    }

    public function deleteData($key)
    {
        try {
            $this->client->delete(rawurlencode($key));
        } catch (UnexpectedResponseError $e) {
            throw new AdapterException('Unable to delete data.', null, $e);
        }
    }
}
