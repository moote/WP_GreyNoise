<?php

/**
 * This singleton class handles all GreyNoise API calls, using the appropriate endpoint class.
 * https://developer.greynoise.io/reference/ip-lookup-1
 * 
 * @author  Rich Conaway
 * 
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

namespace GreyNoise;

require_once(__DIR__.'/calls/IpContext.php');

use GreyNoise\calls\IpContext;

class GreyNoise
{
    /** @var \GreyNoise\GreyNoise */
    protected static $instance;

    /** @var string */
    protected $apiKey;

    /** @var \GreyNoise\calls\IpContext */
    protected $ipContext;

    const VALIDATE_IP_ADDRESS = "8.8.8.8";

    /**
     * Singleton instantiation function
     * 
     * @param string $apiKey GN API key string
	 * @return \GreyNoise\GreyNoise|NULL
     */
    public static function getInstance(string $apiKey): ?\GreyNoise\GreyNoise
    {
        if(!self::$instance){
            // instantiate new instance
            self::$instance = new \GreyNoise\GreyNoise($apiKey);

            // validate api key
            if(!self::$instance->validateApiKey()){
                // api key not valid, return a null instance
                self::$instance = NULL;
            }
        }

        return self::$instance;
    }

    /**
     * Constructor
     * 
     * @param string $apiKey GN API key string
     */
    public function __construct(string $apiKey)
    {
        // store api key
        $this->apiKey = $apiKey;
    }

    /**
     * Validate the API key by making a call to IP context
     * with a known IP address.
     * If the call is successfull, API key is valid.
     * 
     * @return bool
     */
    public function validateApiKey(): bool
    {
        // init call class
        $this->initIpContext();

        // make call
        if($this->ipContext->call(self::VALIDATE_IP_ADDRESS)){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Initialise the IpContext class if not already set
     */
    protected function initIpContext()
    {
        // init call class
        if(!$this->ipContext){
            $this->ipContext = new IpContext($this->apiKey);
        }
    }

    /**
     * Calls the GN API via IpContext class with specified IP address.
     * If the IP is malicious, formatted results are returned. On error 
     * or non-malicious, NULL is returned
     * 
     * @return array|NULL
     */
    public function callIpContext(string $ipAddress): ?array
    {
        // init call class
        $this->initIpContext();
        
        // make call
        if($this->ipContext->call($ipAddress)){
            // handle success; return var array
            // get response array
            $responseArray = $this->ipContext->getResponseArray();

            // return formatted data
            return [
                'seen' => $responseArray['seen'],
                'classification' => !$responseArray['seen'] ? 'unseen' : $responseArray['classification'],
                'cve' => is_array($responseArray['cve']) ? implode(',', $responseArray['cve']) : NULL,
                'country' => $responseArray['metadata']['country'],
                'org' => $responseArray['metadata']['organization'],
                'raw_response' => $this->ipContext->getResponseRaw()
            ];
        }
        else{
            // handle error
            return NULL;
        }
    }
}