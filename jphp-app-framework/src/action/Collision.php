<?php
namespace action;
use php\time\Time;
use php\gui\animation\UXAnimationTimer;

/**
 * Class Collision
 * --RU--
 * Утилитный класс для обработки столкновений.
 * @package action
 *
 * @packages framework
 */
class Collision
{
    /**
     * --RU--
     * Отскок исходя из нормали столкновения.
     *
     * Например: Collision::bounce($this->object, $event->normal)
     *
     * @param $object
     * @param array $normal [x, y]
     * @param float|int $bounciness
     */
    static function bounce($object, array $normal, $bounciness = 1.0)
    {
        $lastTriggerKey = __CLASS__ . "#bounce";

        $velocity = $object->phys->velocity;

        $speed = $object->phys->speed;

        $velocity = [
            $velocity[0] - (1 + $bounciness) * $speed * $normal[0],
            $velocity[1] - (1 + $bounciness) * $speed * $normal[1]
        ];

        if (abs($velocity[0]) < 0.1 && abs($velocity[1]) < 0.1) {
            $object->phys->active = false;
            $object->phys->velocity = [0, 0];
        } else {
            $lastTrigger = $object->data($lastTriggerKey);

            if ($lastTrigger > Time::millis() - UXAnimationTimer::FRAME_INTERVAL_MS * 1.5) {
                return;
            }

            $object->phys->velocity = $velocity;
            $object->phys->speed = $speed * $bounciness;
        }

        $object->data($lastTriggerKey, Time::millis());
    }
}