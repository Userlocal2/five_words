<?php


namespace App\Base;


use App\Logic\MessageEntitiesHandler;
use Sepa\Logic\XML\Entity\BaseMessageXMLEntity;

abstract class BaseMessagesConfig
{

    public $techMessages = [];

    public $financeTypes = [];

    public $rTypes = []; // return, or revert destination of message

    public $Msg;

    public abstract function getRltEntity(string $type, BaseMessageXMLEntity $entity): array;

    /**
     * @param MessageEntitiesHandler $msh
     *
     * @return mixed
     */
    public abstract function update(MessageEntitiesHandler $msh);
}