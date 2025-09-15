<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use cccdl\DougongPay\Exception\DougongException;

trait Request
{
    public function getRequest(): array
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $this->url, ['query' => $this->params, 'headers' => $this->header]);
            $res = $response->getBody()->getContents();
        } catch (ClientException $e) {
            $res = $e->getResponse()->getBody()->getContents();
        } catch (RequestException $e) {
            throw new DougongException('请求失败: ' . $e->getMessage());
        }

        return json_decode($res, true);
    }

    public function postRequest(): array
    {
        try {
            $client = new Client();
            $response = $client->request('POST', $this->url, ['json' => $this->params, 'headers' => $this->header]);
            $res = $response->getBody()->getContents();
        } catch (ClientException $e) {
            $res = $e->getResponse()->getBody()->getContents();
        } catch (RequestException $e) {
            throw new DougongException('请求失败: ' . $e->getMessage());
        }

        return json_decode($res, true);
    }
}