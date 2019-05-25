<?php

namespace Webjump\BraspagPagador\Gateway\Transaction\Auth\Command;

use Webjump\BraspagPagador\Api\CommandInterface;
use Webjump\Braspag\Pagador\Transaction\FacadeInterface as BraspagApi;
use Webjump\Braspag\Pagador\Transaction\Api\Auth\Token\RequestInterface;

/**
 * Class TokenCommand
 * @package Webjump\BraspagPagador\Gateway\Transaction\Auth\Command
 */
class TokenCommand implements CommandInterface
{
    protected $api;

    public function __construct(BraspagApi $api)
    {
        $this->api = $api;
    }

	public function execute(RequestInterface $request)
	{
		return $this->api->getToken($request);
	}
}