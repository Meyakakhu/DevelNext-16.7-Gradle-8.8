<?php
namespace ide\action\types\game;

use game\Jumping;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\ObjectArgumentEditor;
use ide\editors\common\ObjectListEditorItem;
use ide\formats\form\elements\FormFormElement;
use ide\formats\form\elements\GamePaneFormElement;
use ide\formats\form\elements\SpriteViewFormElement;
use php\lib\str;

class SetGravityActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return self::GROUP_GAME;
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_MOVING;
    }

    function getHelpText()
    {
        return 'Это действие работает только для объектов с поведениями "Объект игровой сцены" и "Игровая сцена"!';
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'x' => 'float',
            'y' => 'float',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'Объект',
            'x' => 'Гравитация по X (горизонтальная)',
            'y' => 'Гравитация по Y (вертикальная)'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender', 'editor' => function () {
                return new ObjectArgumentEditor([
                    'objectFilter' => function (ObjectListEditorItem $item) {
                        return $item->element instanceof SpriteViewFormElement
                        || $item->element instanceof FormFormElement || $item->element == null
                            || $item->element instanceof GamePaneFormElement;
                    }
                ]);
            }],
            'x' => ['def' => 0],
            'y' => ['def' => 0],
        ];
    }

    function getTagName()
    {
        return "setGravity";
    }

    function getTitle(Action $action = null)
    {
        return "Изменить гравитацию";
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            return str::format("Изменить гравитацию %s объекта на (x: %s, y: %s)", $action->get('object'), $action->get('x'), $action->get('y'));
        } else {
            return "Изменить гравитацию объекта";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/gravity16.png';
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "{$action->get('object')}->phys->gravity = [{$action->get('x')}, {$action->get('y')}]";
    }
}