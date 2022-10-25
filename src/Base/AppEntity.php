<?php

namespace App\Base;

use App\Error\InternalException;
use Cake\ORM\Entity;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use JeremyHarris\LazyLoad\ORM\LazyLoadEntityTrait;

abstract class AppEntity extends Entity
{
    use LazyLoadEntityTrait;

    public function __construct(array $properties = [], array $options = []) {
        parent::__construct($properties, $options);

        // for supporting new class creation of entity
        if (!$this->getSource()) {
            [$plugin, $table] = namespaceSplit(get_class($this));

            [$plugin,] = explode('\\', $plugin);
            $alias = $table;
            $alias = Inflector::pluralize($alias);
            if ('App' != $plugin && 'Cake' != $plugin) {
                $alias = $plugin . '.' . $alias;
            }

            $this->setSource($alias);
        }
    }

    /**
     * @throws InternalException
     */
    public function save() {
        if (!$this->getSource()) {
            throw new InternalException('Dispatcher results can not be saved ' . get_class($this));
        }
        $repository = $this->getSource();
        $table      = TableRegistry::getTableLocator()->get($repository);
        try {
            $table->saveOrFail($this);
        }
        catch (\Exception|\PDOException|PersistenceFailedException $e) {
            throw new InternalException($e->getMessage(), 500, $e);
        }
    }
}
