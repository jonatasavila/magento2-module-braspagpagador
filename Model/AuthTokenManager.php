<?php

/**
 * @author      Webjump Core Team <dev@webjump.com.br>
 * @copyright   2017 Webjump (http://www.webjump.com.br)
 * @license     http://www.webjump.com.br  Copyright
 *
 * @link        http://www.webjump.com.br
 */

namespace Webjump\BraspagPagador\Model;

use Webjump\Braspag\Pagador\Transaction\Resource\Auth\Token\Response as AuthTokenResponse;
use Webjump\BraspagPagador\Api\AuthTokenManagerInterface;
use Webjump\Braspag\Pagador\Transaction\BraspagFacade;
use Webjump\BraspagPagador\Gateway\Transaction\Auth\Resource\Token\RequestInterface as AuthTokenRequest;
use Webjump\Braspag\Pagador\Transaction\FacadeInterface as BraspagApi;
use Webjump\BraspagPagador\Gateway\Transaction\Auth\Command\TokenCommand;
use Webjump\BraspagPagador\Gateway\Transaction\Auth\Resource\Token\BuilderInterface;
use Magento\Framework\DataObject;

class AuthTokenManager implements AuthTokenManagerInterface
{
    protected $request;
    protected $tokenCommand;
    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $builder;
    protected $dataObject;
    protected $tokenObject;

    /**
     * AuthTokenManager constructor.
     * @param AuthTokenRequest $request
     * @param BraspagApi $api
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        AuthTokenRequest $request,
        TokenCommand $tokenCommand,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        BuilderInterface $builder,
        DataObject $dataObject
    ){
        $this->setTokenCommand($tokenCommand);
        $this->setRequest($request);
        $this->setCookieManager($cookieManager);
        $this->setCookieMetadataFactory($cookieMetadataFactory);
        $this->setBuilder($builder);
        $this->setDataObject($dataObject);
    }

    /**
     * @return AuthTokenRequest
     */
    protected function getRequest(): AuthTokenRequest
    {
        return $this->request;
    }

    /**
     * @param AuthTokenRequest $request
     */
    protected function setRequest(AuthTokenRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @return TokenCommand
     */
    protected function getTokenCommand(): TokenCommand
    {
        return $this->tokenCommand;
    }

    /**
     * @param TokenCommand $tokenCommand
     */
    protected function setTokenCommand(TokenCommand $tokenCommand)
    {
        $this->tokenCommand = $tokenCommand;
    }

    /**
     * @return \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected function getCookieManager(): \Magento\Framework\Stdlib\CookieManagerInterface
    {
        return $this->cookieManager;
    }

    /**
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     */
    protected function setCookieManager(\Magento\Framework\Stdlib\CookieManagerInterface $cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    /**
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected function getCookieMetadataFactory(): \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
    {
        return $this->cookieMetadataFactory;
    }

    /**
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     */
    protected function setCookieMetadataFactory(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory)
    {
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @return BuilderInterface
     */
    protected function getBuilder(): BuilderInterface
    {
        return $this->builder;
    }

    /**
     * @param BuilderInterface $builder
     */
    protected function setBuilder(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return DataObject
     */
    protected function getDataObject(): DataObject
    {
        return $this->dataObject;
    }

    /**
     * @param DataObject $dataObject
     */
    protected function setDataObject(DataObject $dataObject)
    {
        $this->dataObject = $dataObject;
    }

    /**
     * @return mixed
     */
    protected function getTokenObject()
    {
        return $this->tokenObject;
    }

    /**
     * @param mixed $tokenObject
     */
    protected function setTokenObject($tokenObject)
    {
        $this->tokenObject = $tokenObject;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function getToken()
    {
        if (!$this->validateSavedTokenLife()) {
            $response = $this->getTokenCommand()->execute($this->getRequest());
            $this->setTokenObject($this->getBuilder()->build($response));
            $this->registerToken();
        }

        return $this->getSavedToken();
    }

    /**
     * @param AuthTokenResponse $authenticationResponse
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    protected function registerToken()
    {
        $cookieMetadata = $this->getCookieMetadataFactory()->createPublicCookieMetadata()
            ->setHttpOnly(true)
            ->setDuration($this->getTokenObject()->getExpiresIn())
            ->setPath('/');

        $this->getCookieManager()->setPublicCookie(
            AuthTokenRequest::BPMPI_ACCESS_TOKEN_COOKIE_NAME,
            $this->getTokenObject()->getToken(),
            $cookieMetadata
        );

        return $this;
    }

    protected function validateSavedTokenLife()
    {
        return (bool) $this->getCookieManager()->getCookie(AuthTokenRequest::BPMPI_ACCESS_TOKEN_COOKIE_NAME, false);
    }

    protected function getSavedToken()
    {
        if ($this->getTokenObject() instanceof DataObject) {
            return [['token' => $this->getTokenObject()->getToken()]];
        }

        return [['token' => 
            $this->getCookieManager()
                ->getCookie(AuthTokenRequest::BPMPI_ACCESS_TOKEN_COOKIE_NAME, null)
        ]];
    }
}