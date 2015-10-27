<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\orm\collection;

use umi\dbal\builder\ISelectBuilder;
use umi\orm\exception\InvalidArgumentException;
use umi\orm\exception\NotAllowedOperationException;
use umi\orm\exception\RuntimeException;
use umi\orm\metadata\field\relation\BelongsToRelationField;
use umi\orm\metadata\field\special\MaterializedPathField;
use umi\orm\object\IHierarchicObject;
use umi\orm\object\property\calculable\ICalculableProperty;
use umi\orm\selector\ISelector;

/**
 * Базовый класс иерархической коллекции
 */
abstract class BaseHierarchicCollection extends BaseCollection implements IHierarchicCollection
{
    /**
     * {@inheritdoc}
     */
    public function getForcedFieldsToLoad()
    {
        $fieldsToLoad = parent::getForcedFieldsToLoad();
        $fieldsToLoad[IHierarchicObject::FIELD_PARENT] = $this->getRequiredField(IHierarchicObject::FIELD_PARENT);
        $fieldsToLoad[IHierarchicObject::FIELD_MPATH] = $this->getRequiredField(IHierarchicObject::FIELD_MPATH);
        $fieldsToLoad[IHierarchicObject::FIELD_SLUG] = $this->getRequiredField(IHierarchicObject::FIELD_SLUG);
        $fieldsToLoad[IHierarchicObject::FIELD_URI] = $this->getRequiredField(IHierarchicObject::FIELD_URI);

        return $fieldsToLoad;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentField()
    {
        return $this->getRequiredField(IHierarchicObject::FIELD_PARENT);
    }

    /**
     * {@inheritdoc}
     */
    public function getMPathField()
    {
        return $this->getRequiredField(IHierarchicObject::FIELD_MPATH);
    }

    /**
     * {@inheritdoc}
     */
    public function getHierarchyOrderField()
    {
        return $this->getRequiredField(IHierarchicObject::FIELD_ORDER);
    }

    /**
     * {@inheritdoc}
     */
    public function getHierarchyLevelField()
    {
        return $this->getRequiredField(IHierarchicObject::FIELD_HIERARCHY_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlugField()
    {
        return $this->getRequiredField(IHierarchicObject::FIELD_SLUG);
    }

    /**
     * {@inheritdoc}
     */
    public function getURIField()
    {
        return $this->getRequiredField(IHierarchicObject::FIELD_URI);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxOrder(IHierarchicObject $branch = null)
    {
        if ($branch && !$this->contains($branch)) {
            throw new NotAllowedOperationException($this->translate(
                'Cannot calculate max order. Branch from another collection given.'
            ));
        }

        $dataSource = $this
            ->getMetadata()
            ->getCollectionDataSource();
        $orderField = $this->getHierarchyOrderField();
        $parentField = $this->getParentField();

        $platform = $dataSource->getConnection()->getDatabasePlatform();

        /**
         * @var ISelectBuilder $select
         */
        $select = $dataSource
            ->select(':' . $orderField->getName() . ' as ' . $orderField->getName())
            ->bindExpression(
                ':' . $orderField->getName(),
                'MAX(' . $platform->quoteIdentifier($orderField->getColumnName()) . ')'
            );

        if ($branch) {
            $select
                ->where()
                ->expr($parentField->getColumnName(), '=', ':' . $parentField->getName())
                ->bindValue(':' . $parentField->getName(), $branch->getId(), $parentField->getDataType());
        } else {
            $select
                ->where()
                ->expr($parentField->getColumnName(), 'IS', ':' . $parentField->getName())
                ->bindNull(':' . $parentField->getName());
        }

        return (int) $select
            ->execute()
            ->fetchColumn();

    }

    /**
     * {@inheritdoc}
     */
    public function selectChildren(
        IHierarchicObject $object = null,
        $orderBy = IHierarchicObject::FIELD_ORDER,
        $direction = ISelector::ORDER_ASC
    )
    {
        return $this->select()
            ->where(IHierarchicObject::FIELD_PARENT)->equals($object)
            ->orderBy(IHierarchicObject::FIELD_HIERARCHY_LEVEL)
            ->orderBy($orderBy, $direction);
    }

    /**
     * {@inheritdoc}
     */
    public function selectDescendants(
        IHierarchicObject $object = null,
        $depth = null,
        $orderBy = IHierarchicObject::FIELD_ORDER,
        $direction = ISelector::ORDER_ASC
    )
    {
        if (!is_null($depth)) {
            $depth = (int) $depth;
        }

        if (!is_null($depth) && $depth <= 0) {
            throw new InvalidArgumentException($this->translate(
                'Cannot select descendants. Invalid argument "depth" value.'
            ));
        }

        if (!is_null($object) && $depth === 1) {
            return $this->selectChildren($object);
        }

        $selector = $this->select();

        if ($object) {
            $selector
                ->where(IHierarchicObject::FIELD_MPATH)
                ->like($object->getMaterializedPath() . MaterializedPathField::MPATH_SEPARATOR . '%');
        }

        if ($depth) {
            if ($object) {
                $selector
                    ->where(IHierarchicObject::FIELD_HIERARCHY_LEVEL)
                    ->equalsOrLess($object->getLevel() + $depth);
            } else {
                $selector
                    ->where(IHierarchicObject::FIELD_HIERARCHY_LEVEL)
                    ->equalsOrLess($depth);
            }
        }

        $selector->orderBy(IHierarchicObject::FIELD_HIERARCHY_LEVEL);
        $selector->orderBy($orderBy, $direction);

        return $selector;
    }

    /**
     * {@inheritdoc}
     */
    public function selectAncestry(IHierarchicObject $object)
    {
        $mpath = substr($object->getMaterializedPath(), strlen(MaterializedPathField::MPATH_START_SYMBOL));

        $ids = explode(MaterializedPathField::MPATH_SEPARATOR, $mpath);
        if (count($ids) < 2) {
            return $this->emptySelect();
        }
        array_pop($ids);

        $selector = $this->select();
        $selector
            ->where(IHierarchicObject::FIELD_IDENTIFY)
            ->in($ids);

        return $selector;
    }

    /**
     * {@inheritdoc}
     */
    public function move(
        IHierarchicObject $object,
        IHierarchicObject $branch = null,
        IHierarchicObject $previousSibling = null
    )
    {
        if (!$this
            ->getObjectPersister()
            ->getIsPersisted()
        ) {
            throw new RuntimeException($this->translate(
                'Cannot move object. Not all objects are persisted. Commit transaction first.'
            ));
        }

        if (!$this->contains($object)) {
            throw new RuntimeException($this->translate(
                'Cannot move object from another hierarchy.'
            ));
        }

        if ($branch) {
            if (!$this->contains($branch)) {
                throw new RuntimeException($this->translate(
                    'Cannot move object to branch from another hierarchy.'
                ));
            }

            /**
             * @var BelongsToRelationField $parentField
             */
            $parentField = $object
                ->getProperty(IHierarchicObject::FIELD_PARENT)
                ->getField();
            $parentTargetCollectionName = $parentField->getTargetCollectionName();

            if ($this->getName() != $parentTargetCollectionName
                && $branch->getCollectionName() != $parentTargetCollectionName
            ) {
                throw new RuntimeException($this->translate(
                    'Cannot move object. Branch collection does not match object parent collection.'
                ));
            }

            $objectMpath = $object->getMaterializedPath();

            if (strpos($branch->getMaterializedPath(), $objectMpath) === 0) {
                throw new RuntimeException($this->translate(
                    'Cannot move parent object under its child.'
                ));
            }
        }

        if ($previousSibling) {
            if (!$this->contains($previousSibling)) {
                throw new RuntimeException($this->translate(
                    'Cannot move object after sibling from another hierarchy.'
                ));
            }

            if ($previousSibling->getParent() !== $branch) {
                throw new RuntimeException($this->translate(
                    'Cannot move object. Sibling should be direct child of the given parent.'
                ));
            }
        }

        if ($object->getParent() !== $branch) {

            $baseUri = $branch ? $branch->getURI() . '/' : '//';
            $newUri = $baseUri . $object->getSlug();

            $slugConflict = $this->select()
                ->where(IHierarchicObject::FIELD_URI)->equals($newUri);

            if ($slugConflict->getTotal()) {
                throw new RuntimeException($this->translate(
                    'Cannot move object with id "{id}". Slug {slug} is not unique.',
                    ['id' => $object->getId(), 'slug' => $object->getSlug()]
                ));
            }
        }

        $this->modifyMovedObjects($object, $branch, $previousSibling);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function changeSlug(IHierarchicObject $object, $slug)
    {
        $branch = $object->getParent();

        $baseUri = $branch ? $branch->getURI() . '/' : '//';
        $newUri = $baseUri . $slug;

        $slugConflict = $this->select()
            ->where(IHierarchicObject::FIELD_URI)->equals($newUri)
            ->where(IHierarchicObject::FIELD_IDENTIFY)->notEquals($object->getId());

        if ($slugConflict->getTotal()) {
            throw new RuntimeException($this->translate(
                'Cannot change slug for object with id "{id}". Slug {slug} is not unique.',
                ['id' => $object->getId(), 'slug' => $object->getSlug()]
            ));
        }

        $object->getProperty(IHierarchicObject::FIELD_SLUG)->setValue($slug);

        /**
         * @var ICalculableProperty $uriProperty
         */
        $uriProperty = $object->getProperty(IHierarchicObject::FIELD_URI);
        $uriProperty->recalculate();

        foreach ($this->selectDescendants($object) as $descendant) {
            $uriProperty = $descendant->getProperty(IHierarchicObject::FIELD_URI);
            $uriProperty->recalculate();
        }

        return $this;
    }

    /**
     * Запускает транзакцию по перемещению объекта.
     * @param IHierarchicObject $object перемещаемый объект
     * @param IHierarchicObject|null $branch ветка, в которую будет перемещен объект
     * @param IHierarchicObject|null $previousSibling объект, предшествующий перемещаемому
     * @throws RuntimeException если не удалось переместить объекты
     */
    protected function modifyMovedObjects(
        IHierarchicObject $object,
        IHierarchicObject $branch = null,
        IHierarchicObject $previousSibling = null
    )
    {

        $order = $previousSibling ? $previousSibling->getOrder() + 1 : 1;
        $object->getProperty(IHierarchicObject::FIELD_ORDER)->setValue($order);

        $children = $this->selectChildren($branch)
            ->where(IHierarchicObject::FIELD_ORDER)->equalsOrMore($order)
            ->where(IHierarchicObject::FIELD_IDENTIFY)->notEquals($object->getId());
        /**
         * @var IHierarchicObject $child
         */
        foreach ($children as $child) {
            $child->getProperty(IHierarchicObject::FIELD_ORDER)->setValue($child->getOrder() + 1);
        }

        if ($branch !== $object->getParent()) {
            $object->getProperty(IHierarchicObject::FIELD_PARENT)->setValue($branch);

            /**
             * @var ICalculableProperty $mpathProperty
             * @var ICalculableProperty $levelProperty
             * @var ICalculableProperty $uriProperty
             */
            $mpathProperty = $object->getProperty(IHierarchicObject::FIELD_MPATH);
            $levelProperty = $object->getProperty(IHierarchicObject::FIELD_HIERARCHY_LEVEL);
            $uriProperty = $object->getProperty(IHierarchicObject::FIELD_URI);

            $mpathProperty->recalculate();
            $levelProperty->recalculate();
            $uriProperty->recalculate();

            foreach ($this->selectDescendants($object) as $descendant) {
                $mpathProperty = $descendant->getProperty(IHierarchicObject::FIELD_MPATH);
                $levelProperty = $descendant->getProperty(IHierarchicObject::FIELD_HIERARCHY_LEVEL);
                $uriProperty = $descendant->getProperty(IHierarchicObject::FIELD_URI);

                $mpathProperty->recalculate();
                $levelProperty->recalculate();
                $uriProperty->recalculate();
            }
        }
    }
}
