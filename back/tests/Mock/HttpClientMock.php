<?php

namespace App\Tests\Mock;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpClientMock implements HttpClientInterface
{
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // Mock pour les requêtes CAPTCHA
        if (str_contains($url, 'recaptcha')) {
            return new ResponseMock(['success' => true]);
        }

        return new ResponseMock([]);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        throw new \BadMethodCallException('Not implemented in mock');
    }

    public function withOptions(array $options): static
    {
        return $this;
    }
}

class ResponseMock implements ResponseInterface
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getStatusCode(): int
    {
        return 200;
    }

    public function getHeaders(bool $throw = true): array
    {
        return [];
    }

    public function getContent(bool $throw = true): string
    {
        return json_encode($this->data);
    }

    public function toArray(bool $throw = true): array
    {
        return $this->data;
    }

    public function cancel(): void
    {
    }

    public function getInfo(string $type = null)
    {
        return null;
    }
}
