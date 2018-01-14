<?php


namespace Panlatent\Aurxy;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TransferException;
use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /*
         * Transmit client request to target server.
         */
        $options = [
            'timeout'         => 30.0,
            'connect_timeout' => 5.0,
        ];
        try {
            $response = Bridge::send($request, $options);
            echo "done {$response->getBody()->getSize()} byte.\n";
        } catch (BadResponseException $exception) {
            $response = $exception->getResponse();
        } catch (TransferException $exception) {
            echo "failed {$exception->getMessage()}\n";
            $response = (new BadGatewayResponseFactory($request, $exception))->createResponse();
        }

        return $response;
    }
}