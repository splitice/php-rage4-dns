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
     * @param array<string, mixed> $query Query string data
     * @return string an encoded query string
     */
    public function buildQueryString(array $query);

    /**
     * @param string $method
     * @param array<string, mixed> $query_data
     * @throws Rage4Exception
     * @return array<int|string, mixed>
     */
    public function executeApi($method, array $query_data = array());
}
