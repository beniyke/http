<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Http\Error;

use Opis\Http\Request;
use Opis\Http\Response;
use Opis\Http\ResponseContainerInterface;

class HttpError implements ResponseContainerInterface
{
    
    protected $statusCode;
    
    protected $message;
    
    protected $headers;
    
    public function __construct($statusCode, $message = '', array $headers = array())
    {
        $this->statusCode = $statusCode;
        $this->message = $message;
        $this->headers = $headers;
    }
    
    public function send(Request $request, Response $response)
    {
        foreach($this->headers as $name => $value)
        {
            $response->header($name, $value);
        }
        
        $response->status($this->statusCode);
        $response->body($this->message);
        $response->send();
    }
}