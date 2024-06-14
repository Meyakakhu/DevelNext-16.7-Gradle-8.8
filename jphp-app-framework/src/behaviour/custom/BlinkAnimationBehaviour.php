<?php
namespace behaviour\custom;

use action\Animation;
use php\gui\framework\behaviour\custom\AnimationBehaviour;
use php\gui\framework\ScriptEvent;
use php\gui\UXNode;
use php\gui\UXWindow;
use script\TimerScript;
use timer\AccurateTimer;

/**
 * Class BlinkAnimationBehaviour
 * @package behaviour\custom
 *
 * @packages framework
 */
class BlinkAnimationBehaviour extends AnimationBehaviour
{
    /**
     * @var bool
     */
    public $animated = true;

    /**
     * @var float
     */
    public $minOpacity = 0.3;

    /**
     * @var float
     */
    public $maxOpacity = 1.0;

    /**
     * @param mixed $target
     */
    protected function applyImpl($target)
    {
        if (!($target instanceof UXNode) && !($target instanceof UXWindow)) {
            return;
        }

        if ($this->animated) {
            $this->_fadeInCallback();
        } else {
            $this->timer($this->duration, function (ScriptEvent $e) use ($target) {
                $e->sender->interval = $this->duration;

                if ($this->enabled || !$target->visible) {
                    if ($this->minOpacity <= 0.0000001) {
                        $target->toggle();
                    } else {
                        $target->visible = true;

                        if ($target->opacity > $this->minOpacity) {
                            $target->opacity = $this->minOpacity;
                        } else {
                            $target->opacity = $this->maxOpacity;
                        }
                    }
                }
            });
        }
    }

    protected function _fadeOutCallback()
    {
        Animation::fadeTo($this->_target, $this->duration, $this->maxOpacity, function () {
            $this->_fadeInCallback();
        });
    }

    protected function _fadeInCallback()
    {
        if ($this->enabled) {
            Animation::fadeTo($this->_target, $this->duration, $this->minOpacity, function () {
                $this->_fadeOutCallback();
            });
        } else {
            AccurateTimer::executeAfter($this->duration, function () {
                $this->_fadeOutCallback();
            });
        }
    }

    public function getCode()
    {
        return 'blinkAnim';
    }
}