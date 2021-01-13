<?php

declare(strict_types=1);

namespace Keboola\Extractor\GoogleFit\GoogleFitApi;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Keboola\Google\ClientBundle\Google\RestApi as GoogleApi;

class Client
{

    protected const FIT_BASE_URL = 'https://www.googleapis.com/fitness/v1';

    protected GoogleApi $api;

    public function __construct(GoogleApi $api)
    {
        $this->api = $api;
    }

    public function getApi(): GoogleApi
    {
        return $this->api;
    }

    public function getDataSources(): array
    {
        $response = $this->request(
            self::FIT_BASE_URL . '/users/me/dataSources'
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getHourlySteps($dataSourceId, $dataTypeName): array
    {
        // https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate
        $body = json_encode(
            (object) [
                'aggregateBy' =>
                    [
                        0 =>
                            (object) [
                                'dataTypeName' => $dataTypeName,
                                'dataSourceId' => $dataSourceId,
                            ],
                    ],
                'bucketByTime' =>
                    (object) [
                        'durationMillis' => 1000 * 60 * 60,
                    ],
                'startTimeMillis' => (new DateTimeImmutable('last week'))
                        ->setTimezone(new DateTimeZone('UTC'))
                        ->setTime(0, 0)->getTimestamp() * 1000,
                'endTimeMillis' => (new DateTimeImmutable())->getTimestamp() * 1000,
            ]
        );
        $response = $this->request(
            self::FIT_BASE_URL . '/users/me/dataset:aggregate',
            'POST',
            [
                'Content-Type' => 'application/json',
            ],
            [
                'body' => $body,
            ],
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    public function request(
        string $url,
        string $method = 'GET',
        array $addHeaders = [],
        array $options = []
    ): Response {
        try {
            return $this->api->request($url, $method, $addHeaders, $options);
        } catch (RequestException $e) {
            if ($e->getResponse() !== null) {
                throw new \Exception('Api error: ' . $e->getResponse()->getBody()->getContents(), $e->getCode(), $e);
            }

            throw $e;
        }
    }

    public static function convertNanosToDatetime(string $timeInNanos): DateTimeImmutable
    {
        return (new DateTimeImmutable())
            ->setTimestamp((int) ($timeInNanos / 1e9))
            ->setTimezone(new DateTimeZone('UTC'));
    }
}
