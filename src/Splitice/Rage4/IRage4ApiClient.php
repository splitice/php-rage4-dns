<?php

namespace Splitice\Rage4;


/**
 * Client for the Rage4 API web service
 *
 * @package Splitice\Rage4
 *
 */
interface IRage4ApiClient
{
    /**
     * @param array $query Query string data
     * @return string an encoded query string
     */
    public function buildQueryString(array $query);

    /**
     * @param string $method
     * @param array $query_data
     * @throws Rage4Exception
     * @return any
     */
    public function executeApi($method, array $query_data = array());
}