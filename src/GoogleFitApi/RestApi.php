<?php

declare(strict_types=1);

namespace Keboola\Extractor\GoogleFit\GoogleFitApi;

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

class RestApi extends \Keboola\Google\ClientBundle\Google\RestApi
{

    protected function getClient(string $baseUri = \Keboola\Google\ClientBundle\Google\RestApi::API_URI): Client
    {
        $handlerStack = HandlerStack::create(new CurlHandler());

        $handlerStack->push(
            new CacheMiddleware(
                new GreedyCacheStrategy(
                    new DoctrineCacheStorage(
                        new FilesystemCache('/code/cache')
                    ),
                    60 * 60 * 24 * 365 // the TTL in seconds
                    //new KeyValueHttpHeader(['Authorization']) // Optional - specify the headers that can change the
                    // cache key
                )
            ),
            'cache'
        );

        $handlerStack->push(
            self::createRetryMiddleware(
                $this->createRetryDecider($this->maxBackoffs),
                $this->createRetryCallback(),
                $this->delayFn
            )
        );

        return new Client(
            [
                'base_uri' => $baseUri,
                'handler' => $handlerStack,
            ]
        );
    }
}
