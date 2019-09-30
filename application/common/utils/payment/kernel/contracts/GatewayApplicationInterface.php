<?php

namespace app\common\utils\payment\kernel\contracts;

use app\common\utils\payment\kernel\supports\collection;
use think\Response;

interface GatewayApplicationInterface
{
    /**
     * To pay.
     *
     * @param string $gateway
     * @param array $params
     *
     * @return string|Collection
     */
    public function pay($gateway, $params);

    /**
     * Query an order.
     *
     * @param string|array $order
     *
     * @return Collection
     */
    public function find($order);

    /**
     * Refund an order.
     *
     * @param array $order
     *
     * @return Collection
     */
    public function refund($order);

    /**
     * Cancel an order.
     *
     * @param string|array $order
     *
     * @return Collection
     */
    public function cancel($order);

    /**
     * Close an order.
     *
     * @param string|array $order
     *
     * @return Collection
     */
    public function close($order);

    /**
     * Verify a request.
     *
     * @return Collection
     */
    public function verify();

    /**
     * Echo success to server.
     *
     * @return Response
     */
    public function success();

    /**
     * Echo fail to server.
     *
     * @return Response
     */
    public function fail();
}
