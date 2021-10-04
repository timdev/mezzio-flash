<?php

declare(strict_types=1);

namespace Mezzio\Flash;

use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_a;

class FlashMessageMiddleware implements MiddlewareInterface
{
    public const FLASH_ATTRIBUTE = 'flash';

    /** @var string */
    private $attributeKey;

    /**
     * @var callable
     * @psalm-var callable(SessionInterface, string): FlashMessagesInterface
     */
    private $flashMessageFactory;

    /** @var string */
    private $sessionKey;

    public function __construct(
        string $flashMessagesClass = FlashMessages::class,
        string $sessionKey = FlashMessagesInterface::FLASH_NEXT,
        string $attributeKey = self::FLASH_ATTRIBUTE
    ) {
        if (! is_a($flashMessagesClass, SessionInterface::class)) {
            throw Exception\InvalidFlashMessagesImplementationException::forClass($flashMessagesClass);
        }

        $this->flashMessageFactory = [$flashMessagesClass, 'createFromSession'];
        $this->sessionKey          = $sessionKey;
        $this->attributeKey        = $attributeKey;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE, false);
        if (! $session instanceof SessionInterface) {
            throw Exception\MissingSessionException::forMiddleware($this);
        }

        $flashMessages = ($this->flashMessageFactory)($session, $this->sessionKey);

        return $handler->handle($request->withAttribute($this->attributeKey, $flashMessages));
    }
}
