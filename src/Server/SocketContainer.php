<?php

namespace Panlatent\Aurxy\Server;

use InvalidArgumentException;

/**
 * Socket Container is a multi socket resource factory class.
 *
 * It can generate IPv4, IPv6, UNIX socket and can bind a port
 * to one or all of the local addresses.
 *
 * e.g. listen: 8080
 *      listen: localhost:8080
 *      listen: 192.168.1.2:1234
 *
 * @package Panlatent\Aurxy\Server
 */
class SocketContainer implements \Iterator, \Countable
{
    /**
     * @var array
     */
    private $allowAddresses = [];
    /**
     * @var int
     */
    private $maxPort = 65535;
    /**
     * @var int
     */
    private $minPort = 1025;
    /**
     * @var array
     */
    private $builders = [];

    /**
     * SocketFactory constructor.
     *
     * @param string[] $listens
     */
    public function __construct($listens)
    {
        $this->allowAddresses = $this->getINetAddressesByNetworkInterface();
        foreach ($listens as $listen) {
            $this->builders = array_merge($this->builders, $this->parser($listen));
        }
        $this->doctor();
    }

    /**
     * @param array $allowAddresses
     */
    public function setAllowAddresses(array $allowAddresses)
    {
        $this->allowAddresses = $allowAddresses;
    }

    /**
     * @param int $maxPort
     */
    public function setMaxPort(int $maxPort)
    {
        $this->maxPort = $maxPort;
    }

    /**
     * @param int $minPort
     */
    public function setMinPort(int $minPort)
    {
        $this->minPort = $minPort;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->builders);
    }

    /**
     * @return resource
     */
    public function current()
    {
        $builder = current($this->builders);
        if (! is_resource($builder)) {
            $socket = $this->create($builder);
            $this->prepare($socket);
            $this->enable($socket, $builder);
            $this->builders[key($this->builders)] = $socket;
            $builder = $socket;
        }

        return $builder;
    }

    public function next()
    {
        next($this->builders);
    }

    public function key()
    {
        return key($this->builders);
    }

    public function valid()
    {
        return key($this->builders) !== null;
    }

    public function rewind()
    {
        reset($this->builders);
    }

    /**
     * Check builder rules.
     */
    public function doctor()
    {
        foreach ($this->builders as $key => $builder) {
            if (isset($builder['port']) &&
                ($builder['port'] < $this->minPort || $builder['port'] > $this->maxPort)) {
                throw new InvalidArgumentException("Port number out	of range: #{$builder['port']}");
            }

        }
        /*
         * Check repeat addresses. like lo0: 127.0.0.1 and localhost.
         */
        $map = [];
        $this->builders = array_filter($this->builders, function ($builder) use(&$map) {
            if ($builder['type'] == 'unix') {
                $address = 'unix://' . $builder['address'];
            } else {
                $address = $builder['address'] . ':' . $builder['port'];
            }
            if (in_array($address, $map)) {
                return false;
            }
            $map[] = $address;

            return true;
        });
    }

    /**
     * Create socket resource via builders.
     *
     * @param array $builder
     * @return resource
     */
    public function create($builder)
    {
        switch ($builder['type']) {
            case 'ipv4':
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                break;
            case 'ipv6':
                $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
                break;
            case 'unix':
                $socket = socket_create(AF_UNIX, SOCK_STREAM, SOL_TCP);
                break;
            default:
                throw new InvalidArgumentException("Invalid build type value: {$builder['type']}");
        }

        return $socket;
    }

    /**
     * Sets socket options.
     *
     * @param resource $socket
     */
    public function prepare($socket)
    {
        socket_set_nonblock($socket);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
    }

    /**
     * Enable sockets working.
     *
     * @param resource $socket
     * @param array    $builder
     */
    public function enable($socket, $builder)
    {
        switch ($builder['type']) {
            case 'ipv4': // no break
            case 'ipv6':
                if (! @socket_bind($socket, $builder['address'], $builder['port'])) {
                    throw new SocketException("unable to bind address {$builder['address']}:{$builder['port']}");
                }
                echo " => socket bind {$builder['address']}:{$builder['port']}\n";
                socket_listen($socket);
                break;
            case 'unix':
                if (! @socket_bind($socket, $builder['address'])) {
                    throw new SocketException("unable to bind address {$builder['address']}");
                }
                echo " => socket bind {$builder['address']}\n";
                break;
        }
    }

    /**
     * @param string $listen
     * @return array
     */
    private function parser($listen)
    {
        $builders = [];
        if (ctype_digit($listen)) { // Bind port to all address
            foreach ($this->allowAddresses as $address) {
                $builders[] = ['address' => $address, 'port' => $listen, 'type' => 'ipv4'];
            }
        } elseif (strncmp($listen, 'unix:', 5) === 0) { // Create Unix socket
            $builders[] = ['address' => substr($listen, 5), 'type' => 'unix'];
        } else {
            if (false === strpos($listen, ':')) {
                throw new InvalidArgumentException();
            }
            list($address, $port) = explode(':', $listen);
            $version = $this->getINetAddressVersion($address);
            if ($version == AF_INET) {
                $builders[] = ['address' => $address, 'port' => $port, 'type' => 'ipv4'];
            } elseif ($version == AF_INET6) {
                $builders[] = ['address' => $address, 'port' => $port, 'type' => 'ipv6'];
            } else {
                if (false === ($hosts = gethostbynamel($address))) {
                    throw new InvalidArgumentException("Not found host address: $address");
                }
                $state = count($builders);
                foreach ($hosts as $host) {
                    if (in_array($host, $this->allowAddresses)) {
                        $builders[] = ['address' => $host, 'port' => $port, 'type' => 'ipv4'];
                    }
                }
                if ($state == count($builders)) {
                    throw new InvalidArgumentException("Not found local host address: $address");
                }
            }
        }

        return $builders;
    }

    /**
     * @param string $address
     * @return bool|int
     */
    private function getINetAddressVersion($address)
    {
        if (preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $address)) {
            return AF_INET;
        } elseif (preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $address)) {
            return AF_INET6;
        }

        return false;
    }

    /**
     * @return array
     */
    private function getINetAddressesByNetworkInterface()
    {
        exec('ifconfig', $out);
        $out = implode("\n", $out);
        preg_match_all('#inet\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).*\s+netmask#', $out, $match);

        return $match[1];
    }
}