<?php

namespace Currency\Model\Entity;

use App\Lib\BigNumber;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;
use Engine\ServiceApi\Entities\Engine\Body\RequestSEPAPAYINBody;

/**
 * Instruction Entity
 *
 * @property int        $id
 * @property string     $source
 * @property FrozenDate $date
 * @property string     $base
 * @property string     $target
 * @property BigNumber  $rate
 */
class Rate extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*'  => true,
        'id' => false,
    ];
}
