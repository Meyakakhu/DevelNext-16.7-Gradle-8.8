<?php
namespace ide\action\types\game;

use game\Jumping;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\str;

class JumpToRandActionType extends AbstractSimpleActionType
{
    function getGroup()
    {
        return self::GROUP_GAME;
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_MOVING;
    }

    function attributes()
    {
        return [
            'object' => 'object',
            'gridX' => 'integer',
            'gridY' => 'integer',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'Объект',
            'gridX' => 'Grid X (горизонтальное выравнивание)',
            'gridY' => 'Grid Y (вертикальное выравнивание)',
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
            'gridX' => ['def' => '1'],
            'gridY' => ['def' => '1'],
        ];
    }

    function getTagName()
    {
        return "jumpingToRand";
    }

    function getTitle(Action $action = null)
    {
        return "Прыгнуть в случайное место";
    }

    function getDescription(Action $action = null)
    {
        if ($action) {
            $gridX = $action->get('gridX');
            $gridY = $action->get('gridY');

            if ($gridX <= 1 && $gridY <= 1) {
                return str::format("Переместить %s объект к случайно выбранной позиции", $action->get('object'));
            } else {
                return str::format("Переместить %s объект к случайно выбранной позиции, с выравниванием (x: %s, y: %s)", $action->get('object'), $gridX, $gridY);
            }
        } else {
            return "Переместить объект к случайно выбранной позиции";
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/jumpToRand16.png';
    }

    function imports(Action $action = null)
    {
        return [
            Jumping::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $gridX = $action->get('gridX');
        $gridY = $action->get('gridY');
        $object = $action->get('object');

        if ($gridX <= 1 && $gridY <= 1) {
            return "Jumping::toRand({$object})";
        } else {
            return "Jumping::toRand({$object}, $gridX, $gridY)";
        }
    }
}