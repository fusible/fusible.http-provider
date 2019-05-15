<?php
/**
 * Fusible Http
 *
 * PHP version 7
 *
 * Copyright (C) 2019 Jake Johns
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 *
 * @category  Provider
 * @package   Fusible\Http
 * @author    Jake Johns <jake@jakejohns.net>
 * @copyright 2019 Jake Johns
 * @license   http://jnj.mit-license.org/2019 MIT License
 * @link      http://jakejohns.net
 */


namespace Fusible\HttpProvider;

use Http\Discovery\Psr17FactoryDiscovery;
use Interop\Container\ServiceProviderInterface;
use InvalidArgumentException;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Container
 *
 * @category Provider
 * @package  Fusible\HttpProvider
 * @author   Jake Johns <jake@jakejohns.net>
 * @license  https://jnj.mit-license.org/ MIT License
 * @link     https://jakejohns.net
 *
 * @see ServiceProviderInterface
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HttpProvider implements ServiceProviderInterface
{
    /**
     * HTTP Discovery methods
     *
     * @var array
     *
     * @access protected
     */
    protected $discover = [
        RequestFactoryInterface::class       => 'findRequestFactory',
        ResponseFactoryInterface::class      => 'findResponseFactory',
        ServerRequestFactoryInterface::class => 'findServerRequestFactory',
        StreamFactoryInterface::class        => 'findStreamFactory',
        UploadedFileFactoryInterface::class  => 'findUploadedFileFactory',
        UriFactoryInterface::class           => 'findUrlFactory'
    ];

    /**
     * Factories
     *
     * @var array
     *
     * @access protected
     */
    protected $factories = [];

    /**
     * __construct
     *
     * @param string $request       ResponseFactoryInterface class
     * @param string $response      ResponseFactoryInterface class
     * @param string $serverRequest ServerRequestFactoryInterface class
     * @param string $stream        StreamFactoryInterface class
     * @param string $upload        UploadedFileFactoryInterface class
     * @param string $uri           UriFactoryInterface class
     *
     * @access public
     */
    public function __construct(
        string $request = null,
        string $response = null,
        string $serverRequest = null,
        string $stream = null,
        string $upload = null,
        string $uri = null
    ) {

        $this->definePsr17(
            [
                RequestFactoryInterface::class       => $request,
                ResponseFactoryInterface::class      => $response,
                ServerRequestFactoryInterface::class => $serverRequest,
                StreamFactoryInterface::class        => $stream,
                UploadedFileFactoryInterface::class  => $upload,
                UriFactoryInterface::class           => $uri
            ]
        );

        $this->factories[ServerRequestCreatorInterface::class] = [
            $this, 'newServerRequestCreator'
        ];
    }

    /**
     * GetFactories
     *
     * @return array
     *
     * @access public
     */
    public function getFactories() : array
    {
        return $this->factories;
    }

    /**
     * GetExtensions
     *
     * @return array
     *
     * @access public
     */
    public function getExtensions()
    {
        return [];
    }

    /**
     * Define PSR17 Factories
     *
     * @param array $specs interface => instance
     *
     * @return void
     *
     * @access protected
     */
    protected function definePsr17(array $specs)
    {
        foreach ($specs as $interface => $implementation) {

            if ($implementation) {
                $this->assertImplementation($implementation, $interface);
            }

            $this->factories[$interface] = $implementation
                ? $this->newFactory($implementation)
                : $this->discover($interface);
        }
    }

    /**
     * Get discovery method for an interface
     *
     * @param string $interface to discover
     *
     * @return callable
     *
     * @access protected
     */
    protected function discover(string $interface) : callable
    {
        return [Psr17FactoryDiscovery::class, $this->discover[$interface]];
    }

    /**
     * New Factory
     *
     * @param string $implementation class name for implementation
     *
     * @return callable
     *
     * @access protected
     */
    protected function newFactory(string $implementation) : callable
    {
        return function () use ($implementation) {
            return new $implementation;
        };
    }

    /**
     * Assert that a class implemnets an interface
     *
     * @param string $implementation Class name
     * @param string $interface      Interface name
     *
     * @return void
     * @throws InvalidArgumentException if class doesnt implenment interface
     *
     * @access protected
     */
    protected function assertImplementation(
        string $implementation, string $interface
    ) {
        $implements = class_implements($implementation);
        if (! isset($implements[$interface])) {
            $msg = "$implementation must implement $interface";
            throw new InvalidArgumentException($msg);
        }
    }

    /**
     * Create new ServerRequestCreator
     *
     * @param Container $container Container
     *
     * @return ServerRequestCreatorInterface
     *
     * @access public
     */
    public function newServerRequestCreator(
        Container $container
    ) : ServerRequestCreatorInterface {
        return new ServerRequestCreator(
            $container->get(ServerRequestFactoryInterface::class),
            $container->get(UriFactoryInterface::class),
            $container->get(UploadedFileFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
        );
    }
}
