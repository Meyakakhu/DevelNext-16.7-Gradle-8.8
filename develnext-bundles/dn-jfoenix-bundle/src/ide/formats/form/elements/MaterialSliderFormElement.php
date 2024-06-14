<?php
namespace ide\formats\form\elements;

use ide\formats\form\AbstractFormElement;
use php\gui\event\UXMouseEvent;
use php\gui\UXMaterialButton;
use php\gui\UXMaterialSlider;
use php\gui\UXNode;
use php\gui\UXRating;
use php\gui\UXToggleSwitch;

class MaterialSliderFormElement extends SliderFormElement
{
    public function getGroup()
    {
        return 'Material UI';
    }

    public function getName()
    {
        return 'Material ' . parent::getName();
    }

    public function getElementClass()
    {
        return UXMaterialSlider::class;
    }

    public function isOrigin($any)
    {
        return $any instanceof UXMaterialSlider;
    }

    /**
     * @return UXNode
     */
    public function createElement()
    {
        return new UXMaterialSlider();
    }
}