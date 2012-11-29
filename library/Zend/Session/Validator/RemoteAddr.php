<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Session
 */

namespace Zend\Session\Validator;

use Zend\Session\Validator\ValidatorInterface as SessionValidator;

/**
 * @category   Zend
 * @package    Zend_Session
 * @subpackage Validator
 */
class RemoteAddr implements SessionValidator
{
    /**
     * Internal data.
     *
     * @var string
     */
    protected $data;

    /**
     * Whether to use proxy addresses or not.
     *
     * As default this setting is disabled - IP address is mostly needed to increase
     * security. HTTP_* are not reliable since can easily be spoofed. It can be enabled
     * just for more flexibility, but if user uses proxy to connect to trusted services
     * it's his/her own risk, only reliable field for IP address is $_SERVER['REMOTE_ADDR'].
     *
     * @var bool
     */
    protected static $useProxy = false;

    /**
     * List of trusted proxy IP addresses
     *
     * @var array
     */
    protected static $trustedProxies = array();

    /**
     * HTTP header to introspect for proxies
     *
     * @var string
     */
    protected static $proxyHeader = 'HTTP_X_FORWARDED_FOR';

    /**
     * Constructor
     * get the current user IP and store it in the session as 'valid data'
     */
    public function __construct($data = null)
    {
        if (empty($data)) {
            $data = $this->getIpAddress();
        }
        $this->data = $data;
    }

    /**
     * isValid() - this method will determine if the current user IP matches the
     * IP we stored when we initialized this variable.
     *
     * @return bool
     */
    public function isValid()
    {
        return ($this->getIpAddress() === $this->getData());
    }

    /**
     * Changes proxy handling setting.
     *
     * This must be static method, since validators are recovered automatically
     * at session read, so this is the only way to switch setting.
     *
     * @param bool  $useProxy Whether to check also proxied IP addresses.
     * @return void
     */
    public static function setUseProxy($useProxy = true)
    {
        static::$useProxy = $useProxy;
    }

    /**
     * Checks proxy handling setting.
     *
     * @return bool Current setting value.
     */
    public static function getUseProxy()
    {
        return static::$useProxy;
    }

    /**
     * Set list of trusted proxy addresses
     *
     * @param  array $trustedProxies
     * @return void
     */
    public static function setTrustedProxies(array $trustedProxies)
    {
        static::$trustedProxies = $trustedProxies;
    }

    /**
     * Set the header to introspect for proxy IPs
     *
     * @param  string $header
     * @return void
     */
    public static function setProxyHeader($header = 'X-Forwarded-For')
    {
        static::$proxyHeader = static::normalizeProxyHeader($header);
    }

    /**
     * Returns client IP address.
     *
     * @return string IP address.
     */
    protected function getIpAddress()
    {
        $ip = $this->getIpAddressFromProxy();
        if ($ip) {
            return $ip;
        }

        // direct IP address
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    /**
     * Attempt to get the IP address for a proxied client
     *
     * @return false|string
     */
    protected function getIpAddressFromProxy()
    {
        if (!static::$useProxy) {
            return false;
        }

        $header = static::$proxyHeader;

        if (!isset($_SERVER[$header]) || empty($_SERVER[$header])) {
            return false;
        }

        // Extract IPs
        $ips = explode(',', $_SERVER[$header]);
        // trim, so we can compare against trusted proxies properly
        $ips = array_map('trim', $ips);
        // remove trusted proxy IPs
        $ips = array_diff($ips, static::$trustedProxies);

        // Any left?
        if (empty($ips)) {
            return false;
        }

        // Return right-most
        $ip  = array_pop($ips);
        return $ip;
    }

    /**
     * Retrieve token for validating call
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return validator name
     *
     * @return string
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * Normalize a header string
     *
     * Normalizes a header string to a format that is compatible with
     * $_SERVER
     *
     * @param  string $header
     * @return string
     */
    protected static function normalizeProxyHeader($header)
    {
        $header = strtoupper($header);
        $header = str_replace('-', '_', $header);
        if (0 !== strpos($header, 'HTTP_')) {
            $header = 'HTTP_' . $header;
        }
        return $header;
    }
}
