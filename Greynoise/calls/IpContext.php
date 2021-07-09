<?php

/**
 * IP Context call class for GreyNoise
 * https://developer.greynoise.io/reference/ip-lookup-1#noisecontextip-1
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

namespace GreyNoise\calls;

require_once(__DIR__.'/GreyNoiseCallInterface.php');

class IpContext implements \GreyNoise\calls\GreyNoiseCallInterface
{
    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $responseRaw;

    /** @var array */
    protected $responseArray;
    
    /** @var string */
    protected $httpCode;

    /** @var string */
    protected $error;

    /**
     * Constructor
     */
    public function __construct(string $apiKey)
    {
        // save api key
        $this->apiKey = $apiKey;
    }

    /**
     * Call the GreyNoise API Ip Context endpoint.
     * Returns 'true' if call successful, 'false' on error.
     */
    public function call($params): bool
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.greynoise.io/v2/noise/context/".$params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "key: ".$this->apiKey,
            ],
        ]);

        $this->responseRaw = curl_exec($curl);
        $this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->error = curl_error($curl);

        curl_close($curl);

        // return error if failed connection or not a HTTP 200 response code
        if ($this->error) { // connection error
            return false;
        }
        elseif($this->httpCode !== 200){ // HTTP error
            return false;
        }
        else {
            // echo "<pre>".var_export($this->httpCode, true)."</pre>";
            // echo "<pre>".var_export($this->response, true)."</pre>"; exit;

            // TODO: log response to db

            // convert JSON response to array
            $this->responseArray = json_decode($this->responseRaw, true);

            return true;
        }
    }

    /**
     * Return the raw (string) response.
     */
    public function getResponseRaw(): string
    {
        return $this->responseRaw;
    }

    /**
     * Return the assoc. array representation of the
     * response (json_decode).
     */
    public function getResponseArray(): array
    {
        return $this->responseArray;
    }

    /**
     * Return the HTTP code for the last response
     */
    public function getHttpCode(): string
    {
        return $this->httpCode;
    }

    /**
     * Reurn the error for last call (if set).
     */
    public function getError(): string
    {
        return $this->error;
    }
}
