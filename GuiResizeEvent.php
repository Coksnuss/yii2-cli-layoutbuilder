<?php
namespace coksnuss\cli\layoutbuilder;

/**
 * This event is passed to all childs of a frame whose size has been changed.
 */
class GuiResizeEvent extends \yii\base\Event
{
    /**
     * @var integer The number of childs with dynamic size that this frame
     * consists of.
     */
    public $dynamicChilds; // TODO: Not required if each frame just takes 1 char of the precision error until nothing is left
    /**
     * @var integer The number of childs with dynamic size that already
     * processed this event.
     */
    public $dynamicChildCount = 0; // TODO: Not required if each frame just takes 1 char of the precision error until nothing is left
    /**
     * @var integer The size of childs with dynamic size.
     */
    public $dynamicSize;
    /**
     * @var integer The precision error size which can be added to a single
     * frame.
     */
    public $precisionError;
}
