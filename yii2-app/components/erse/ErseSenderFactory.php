<?php

namespace app\components\erse;

use yii\base\Component;
use yii\di\Instance;

class ErseSenderFactory extends Component
{
    public bool $mock = false;

    /** @var ContractSyncSenderInterface|array|string */
    public $httpSender = 'erseHttpSender';

    /** @var ContractSyncSenderInterface|array|string */
    public $mockSender = 'erseMockSender';

    public function init(): void
    {
        parent::init();
        $this->httpSender = Instance::ensure($this->httpSender, ContractSyncSenderInterface::class);
        $this->mockSender = Instance::ensure($this->mockSender, ContractSyncSenderInterface::class);
    }

    public function create(): ContractSyncSenderInterface
    {
        return $this->mock ? $this->mockSender : $this->httpSender;
    }
}
