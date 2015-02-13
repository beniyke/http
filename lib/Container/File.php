<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013-2015 Marius Sarca
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

namespace Opis\Http\Container;

use RuntimeException;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\Http\Mime;
use Opis\Http\ResponseContainerInterface;


class File implements ResponseContainerInterface
{

    /** @var string File path. */
    protected $filePath;

    /** @var int File size. */
    protected $fileSize;
    
    /** @var array Options. */
    protected $options;


    /**
     * Constructor.
     * 
     * @access  public
     * @param   string  $file       File path
     * @param   array   $options    Options
     */

    public function __construct($file, array $options = array())
    {
        if(file_exists($file) === false || is_readable($file) === false)
        {
            throw new RuntimeException(vsprintf("%s(): File [ %s ] is not readable.", array(__METHOD__, $file)));
        }
        $this->filePath = $file;
        $this->fileSize = filesize($file);
        $this->options = $options + array(
            'file_name'    => basename($file),
            'disposition'  => 'attachment',
            'content_type' => Mime::get($file) ?: 'application/octet-stream',
        );
    }

    /**
     * Determine the content range that should be served.
     * 
     * @access  protected
     * @param   \Opis\Http\Request  $request  Request instance
     * @return  null|false|array
     */

    protected function getRange(Request $request)
    {
        if(($range = $request->header('range')) !== null)
        {
            // Remove the "range=" part of the header value
            $range = substr($range, 6);
            // Split the range starting and ending points
            $range = explode('-', $range, 2);
            // Check that the range contains two values
            if(count($range) !== 2)
            {
                return false;
            }
            // Determine start and ending points
            $end = $range[1] === '' ? $this->fileSize - 1 : $range[1];
            if($range[0] === '')
            {
                $start = $this->fileSize - $end;
                $end   = $this->fileSize - 1;
            }
            else
            {
                $start = $range[0];
            }
            $start = (int) $start;
            $end   = (int) $end;
            // Check that the range is satisfiable
            if($start > $end || $end + 1 > $this->fileSize)
            {
                return false;
            }
            // Return the range
            return compact('start', 'end');
        }
        return null; // No range was provided
    }

    /**
     * Sends the file.
     * 
     * @access  protected
     * @param   int        $start  Starting point
     * @param   int        $end    Ending point
     */

    protected function sendFile($start, $end)
    {
        // Erase output buffers and disable output buffering
        while(ob_get_level() > 0) ob_end_clean();
        // Open the file handle
        $handle = fopen($this->filePath, 'rb');
        // Move to the correct starting position
        fseek($handle, $start);
        // Send the file contents
        $chunkSize = 4096;
        while(!feof($handle) && ($pos = ftell($handle)) <= $end && !connection_aborted())
        {
            if ($pos + $chunkSize > $end)
            {
                $chunkSize = $end - $pos + 1;
            }
            echo fread($handle, $chunkSize);
            flush();
        }
        fclose($handle);
    }

    /**
     * Sends the response.
     * 
     * @access  public
     * @param   \Opis\Http\Request
     * @param   \Opis\Http\Response
     */

    public function send(Request $request, Response $response)
    {
        // Add headers that should always be included
        $response->type($this->options['content_type']);
        $response->header('accept-ranges', 'bytes');
        $response->header('content-disposition', $this->options['disposition'] . '; filename="' . $this->options['file_name'] . '"');
        // Get the requested byte range
        $range = $this->getRange($request);
        if($range === false)
        {
            // Not an acceptable range so we'll just send an empty response
            // along with a "requested range not satisfiable" status
            $response->status(416);
            $response->sendHeaders();
        }
        else
        {
            if($range === null)
            {
                // No range was provided by the client so we'll just fake one for the sendFile method
                // and set the content-length header value to the full file size
                $range = array('start' => 0, 'end' => $this->fileSize - 1);
                $response->header('content-length', $this->fileSize);
            }
            else
            {
                // Valid range so we'll need to tell the client which range we're sending
                // and set the content-length header value to the length of the byte range
                $response->header('content-range', sprintf('%s-%s/%s', $range['start'], $range['end'], $this->fileSize));
                $response->header('content-length', $range['end'] - $range['start'] + 1);
            }
            // Send headers and the requested byte range
            $response->sendHeaders();
            $this->sendFile($range['start'], $range['end']);
        }
    }
}