<?php

namespace Phasten;

class XMLBuilder extends ObjectTreeBuilder
{
    public function buildNode(ReflectionMember $member): NodeInterface
    {
        $name = $member->getName();
        $value = $member->getValue();

        if (is_numeric($name) && is_object($value)) {
            $name = get_class($value);
        }

        $attributes = $member->getAnnotations();

        if (isset($attributes[XMLAttribute::class])) {

            $attribute = $attributes[XMLAttribute::class];

            if (!$attribute->name) {
                $attribute->name = $name;
            }

            if (null !== $value) {
                $attribute->value = $value;
            }

            return $attribute;
        }
        

        // encode value
        if (isset($attributes[XMLEncode::class])) {
            // use supplied encoder
            $encoder = $attributes[XMLEncode::class];
            $value = $encoder->encode($value);
        }

        if (isset($attributes[XMLElement::class])) {
            /**
             * @var XMLElement
             */
            $element = $attributes[XMLElement::class];

            if (!$element->name) {
                $element->name = $name;
            }

            $element->value = $value;

        } else {
            $element = new XMLElement($name, $value);
        }

        //$element->value = $element->getIndex();

        return $element;
    }

    public function addChildNode(NodeInterface $child): void
    {   
        /**
         * @var XMLElement
         */
        $parent = $this->getParentNode();

        if ($child instanceof XMLAttribute) {
            $parent->addAttribute($child);
        } else {
            $parent->addChild($child);
        }
    }
}