<?php

namespace Phasten;

use RecursiveIteratorIterator;

class ObjectTreeBuilder extends RecursiveIteratorIterator
{
    #region props
    const IS_PROPERTY = 8;

    const IS_METHOD = 16;

    public int $flags = ReflectionMember::IS_PUBLIC | self::IS_PROPERTY;

    private object $object;

    private array $objects = [];

    private NodeInterface $root;

    private ?NodeInterface $parent = null;

    private ?NodeInterface $node = null;
    #endregion

    public function __construct(object $object, ?string $name = null)
    {
        parent::__construct(new RecursiveObjectIterator($object), self::SELF_FIRST);

        $rootMember = ReflectionMember::fromReflectionClass(Ref::getClass($object), $object, [], $name);
        $root = $this->buildNode($rootMember);

        $this->object = $object;
        $this->root = $root;
    }

    public function getObject(): object
    {
        return $this->object;
    }

    public function getRootNode(): NodeInterface
    {
        return $this->root;
    }

    public function getParentNode(): NodeInterface
    {
        return $this->parent ?? $this->getRootNode();
    }

    public function getNode(): NodeInterface
    {
        return $this->node ?? $this->getParentNode();
    }

    public function buildNode(ReflectionMember $member): NodeInterface
    {
        return new ReflectionMemberNode($member);
    }

    public function addChildNode(NodeInterface $node): void
    {
        $this->getParentNode()->addChild($node);
    }

    public function callHasChildren(): bool
    {
        $hasChildren = parent::callHasChildren();

        if ($hasChildren) {

             /**
             * @var ReflectionMember
             */
            $current = parent::current();

            $object = (object)$current->getValue();

            $id = spl_object_id($object);

            if (array_key_exists($id, $this->objects)) {
                throw new RecursionException("Member {$current->getName()}");
            }

            $this->objects[$id] = $this->node;
        }

        return $hasChildren;
    }

    public function beginChildren()
    {
        $this->parent = $this->getNode();
    }

    public function endChildren()
    {
        $this->parent = $this->getParentNode()->getParent();
    }

    public function current()
    {   
        /**
         * @var ReflectionMember
         */
        $current = parent::current();

        $node = $this->buildNode($current);

        $this->node = $node;
        
        $this->addChildNode($node);

        return $current;
    }
}
