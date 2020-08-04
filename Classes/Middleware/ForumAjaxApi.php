<?php
declare(strict_types=1);
namespace Mittwald\Typo3Forum\Middleware;

/*
 *  (c) 2020 punkt.de GmbH - Karlsruhe, Germany - https://punkt.de
 *  All rights reserved.
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Lightweight alternative to regular frontend requests; used when $_GET[eID] is set.
 * In the future, logic from the EidUtility will be moved to this class, however in most cases
 * a custom PSR-15 middleware will be better suited for whatever job the eID functionality does currently.
 *
 * @internal
 */
class ForumAjaxApi implements MiddlewareInterface
{
	/**
	 * @var string
	 */
	private $extensionKey = 'Typo3Forum';

    /**
     * @var DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var Context
     */
    protected $context;

	/**
	 * An instance of the extbase bootstrapping class.
	 * @var Bootstrap
	 */
	private $extbaseBootstap = null;

	/**
	 * An instance of the extbase object manager.
	 * @var ObjectManagerInterface
	 */
	private $objectManager = null;

    public function __construct(Context $context, DispatcherInterface $dispatcher)
    {
        $this->context = $context;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatches the request to the corresponding eID class or eID script
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ajaxApi = $request->getParsedBody()['forumajaxapi'] ?? $request->getQueryParams()['forumajaxapi'] ?? null;

        if ($ajaxApi === null) {
            return $handler->handle($request);
        }

        // Remove any output produced until now
        ob_clean();

        $target = $GLOBALS['TYPO3_CONF_VARS']['FE']['ajaxApi_include'][$ajaxApi] ?? null;
        if (empty($target)) {
            return (new HtmlResponse('1596543502'))->withStatus(500);
        }

        if (!$this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            return (new HtmlResponse('1596543538'))->withStatus(401);
        }

        $request = $request->withAttribute('target', $target);
        try {
            $response = $this->dispatcher->dispatch($request);
        } catch (\Exception $exception) {
            return (new HtmlResponse((string)$exception->getCode()))->withStatus(500);
        }
        return $response ?? new NullResponse();
    }

}
