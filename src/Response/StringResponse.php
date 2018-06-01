<?php
/* ============================================================================
 * Copyright © 2013-2018 Opis
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

namespace Opis\Http\Response;

use Opis\Http\{
    Response, Stream
};

class StringResponse extends Response
{
    /**
     * StringResponse constructor.
     * @param string $body
     * @param int $status
     * @param array|null $headers
     */
    public function __construct(string $body, int $status = 200, array $headers = null)
    {

        $headers['Content-Length'] = strlen($body) . '';
        $stream = new Stream("php://temp", "wb+");
        $stream->write($body);
        $stream->rewind();

        parent::__construct($stream, $status, $headers);
    }
}