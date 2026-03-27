<?php

namespace app\components\erse;

interface ContractSyncSenderInterface
{
    /**
     * @return array{statusCode:int, rawBody:string, parsedBody:array|null}
     */
    public function send(string $destination, array $payload): array;
}
