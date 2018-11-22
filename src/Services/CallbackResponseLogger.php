<?php

namespace App\Services;

use Psr\Http\Message\ResponseInterface;
use webignition\InternetMediaType\Parameter\Parser\AttributeParserException;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;
use webignition\InternetMediaType\Parser\SubtypeParserException;
use webignition\InternetMediaType\Parser\TypeParserException;

class CallbackResponseLogger
{
    /**
     * @var string
     */
    private $logPath;

    /**
     * @var ContentTypeParser
     */
    private $contentTypeParser;

    public function __construct(string $logPath, ContentTypeParser $contentTypeParser)
    {
        $this->logPath = $logPath;
        $this->contentTypeParser = $contentTypeParser;
    }

    public function log(string $requestId, ResponseInterface $response)
    {
        $contentType = 'text/plain';
        $logFileExtension = 'txt';

        try {
            $contentTypeObject = $this->contentTypeParser->parse($response->getHeaderLine('content-type'));
            $contentType = $contentTypeObject->getTypeSubtypeString();

            if ('text/plain' !== $contentType) {
                $logFileExtension = $contentTypeObject->getSubtype();
            }
        } catch (AttributeParserException $e) {
        } catch (SubtypeParserException $e) {
        } catch (TypeParserException $e) {
        }

        $body = $response->getBody()->getContents();
        $logData = $body;

        if ('application/json' === $contentType) {
            $data = json_decode($body, true);

            $dataUrl = $data['url'] ?? null;
            $isHttpBinResponse = $dataUrl && substr_count($dataUrl, '//httpbin');

            $logData = $isHttpBinResponse
                ? $data['data']
                : json_encode($data);
        }

        $logFile = sprintf(
            '%s/%s.%s',
            $this->logPath,
            $requestId,
            $logFileExtension
        );

        file_put_contents($logFile, $logData);
    }
}
