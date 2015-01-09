<?php
namespace coksnuss\cli\layoutbuilder;

use yii\base\UserException;

abstract class GuiElement extends \yii\base\Component
{
    const LAYOUT_HORIZONTAL = 'horizontal';
    const LAYOUT_VERTICAL   = 'vertical';

    const EVENT_FRAME_RESIZE = 'gui-frameResize';

    /**
     * @var integer|float If given as integer this is the size of the GUI(element)
     * in terms of characters. If given as float between 0 and 1 this is the size
     * of the element in percentage relative to the window or container size. If
     * the layout is equal to 'horizontal' this determines the width of the
     * container, if the layout is 'vertical' this determines the height.
     * If not given the element size is distributed equally within the remaining
     * space.
     */
    public $size; // TODO: Check if its possible to move it back somehow to GUIFrame because the term of 'layout' is introduced there!
    /**
     * @var string Defines how to render this element if the content exeeds the
     * available space. Possible values are:
     *   - 'hidden': Cuts the content if there is no available space left.
     *   - 'break': Insert line breaks until the available height is used up.
     */
    public $overflow = 'hidden';

    // TODO: Preceed those attributes with _? PSR-2 conventions says SHOULDNT, but it conflicts with getter/setter.
    /**
     * @var integer The total available length of this element in characters.
     */
    protected $width;
    /**
     * @var integer The total available height of this element in characters.
     */
    protected $height;
    /**
     * @var array The (top, right, bottom, left) margin of this element.
     */
    protected $margin = [0, 0, 0, 0];
    /**
     * @var array The (top, right, bottom, left) padding of this element.
     */
    protected $padding = [0, 0, 0, 0];
    /**
     * @var array The (top, right, bottom, left) border of this element.
     */
    protected $border = [0, 0, 0, 0];


    /**
     * Returns the textual representation of this element.
     * Ensure to not exeed the available space or the content will be cut off.
     *
     * @return string The textual representation of this element.
     */
    abstract protected function asText();

    /**
     * Renders the GUI element.
     *
     * @return string The ASCII representation of the element.
     */
    public function render()
    {
        // Allows to implement beforeRender() / afterRender() events & methods.
        return $this->asText();
    }

    /**
     * Gets called when the frame which this element belongs to is beeing
     * resized.
     *
     * @param GuiResizeEvent $event
     * @return boolean Whether the width and/or height has changed.
     */
    public function resize($event)
    {
        $frame         = $event->sender;
        $frameWidth    = $frame->getInnerWidth();
        $frameHeight   = $frame->getInnerHeight();
        $availableSize = $frame->layout === self::LAYOUT_HORIZONTAL ?
            $frameWidth :
            $frameHeight;

        if ($this->size === null) {
            $event->dynamicChildCount++;
            $dynamicSize = $event->dynamicChildCount == $event->dynamicChilds ?
                $event->dynamicSize + $event->precisionError :
                $event->dynamicSize;

            $this->limitDimensions(
                $frame->layout === self::LAYOUT_HORIZONTAL ? $dynamicSize : $frameWidth,
                $frame->layout !== self::LAYOUT_HORIZONTAL ? $dynamicSize : $frameHeight
            );
        } elseif (is_float($this->size)) {
            $size = intval($availableSize * $this->size);

            $this->limitDimensions(
                $frame->layout === self::LAYOUT_HORIZONTAL ? $size : $frameWidth,
                $frame->layout !== self::LAYOUT_HORIZONTAL ? $size : $frameHeight
            );
        } elseif (is_integer($this->size)) {
            $this->limitDimensions(
                $frame->layout === self::LAYOUT_HORIZONTAL ? $this->size : $frameWidth,
                $frame->layout !== self::LAYOUT_HORIZONTAL ? $this->size : $frameHeight
            );
        }

        return true; // TODO: Whether the size has changed.
    }

    /**
     * Limits the size of this element.
     *
     * @param integer $width The width in characters that this element is allowed to be spanned.
     * @param integer $height The height in characters that this element is allowed to be spanned.
     */
    protected function limitDimensions($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @return integer The total width of this element in characters.
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return integer The total height of this element in characters.
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return integer The available width of this element in characters.
     */
    public function getInnerWidth()
    {
        return max(0, $this->width - (
            $this->getMargin('left') +
            $this->getBorder('left') +
            $this->getPadding('left') +
            $this->getPadding('right') +
            $this->getBorder('right') +
            $this->getMargin('right')
        ));
    }

    /**
     * @return integer The available height of this element in characters.
     */
    public function getInnerHeight()
    {
        return max(0, $this->height - (
            $this->getMargin('top') +
            $this->getBorder('top') +
            $this->getPadding('top') +
            $this->getPadding('bottom') +
            $this->getBorder('bottom') +
            $this->getMargin('bottom')
        ));
    }

    /**
     * Gets the margin.
     *
     * @param string $position Refer to quadrupleGetter() on how to specify this
     * parameter. If not given the whole quadruble is beeing returned.
     */
    public function getMargin($position = null)
    {
        return $this->quadrupleGetter($position, 'margin');
    }

    /**
     * Sets the margin of this element.
     *
     * @param mixed $margin Refer to quadrupleSetter() on how to specify this
     * parameter.
     */
    public function setMargin($margin)
    {
        $this->quadrupleSetter($margin, 'margin');
    }

    /**
     * Gets the padding.
     *
     * @param string $position Refer to quadrupleGetter() on how to specify this
     * parameter. If not given the whole quadruble is beeing returned.
     */
    public function getPadding($position = null)
    {
        return $this->quadrupleGetter($position, 'padding');
    }

    /**
     * Sets the padding of this element.
     *
     * @param mixed $margin Refer to quadrupleSetter() on how to specify this
     * parameter.
     */
    public function setPadding($padding)
    {
        $this->quadrupleSetter($padding, 'padding');
    }

    /**
     * Gets the border.
     *
     * @param string $position Refer to quadrupleGetter() on how to specify this
     * parameter. If not given the whole quadruble is beeing returned.
     */
    public function getBorder($position = null)
    {
        return $this->quadrupleGetter($position, 'border');
    }

    /**
     * Sets the border of this element.
     *
     * @param mixed $margin Refer to quadrupleSetter() on how to specify this
     * parameter.
     */
    public function setBorder($border)
    {
        $this->quadrupleSetter($border, 'border');
    }

    /**
     * Gets a spcecific margin/padding/border value
     *
     * @param string $postion One of top, right, bottom or left.
     * @param string $property The property name which holds the quadruple.
     * @return mixed The value of the quadruple at the specified position.
     */
    protected function quadrupleGetter($position, $property)
    {
        switch ($position)
        {
            case 'top': return $this->{$property}[0];
            case 'right': return $this->{$property}[1];
            case 'bottom': return $this->{$property}[2];
            case 'left': return $this->{$property}[3];
        }

        return $this->$property;
    }

    /**
     * Sets the margin/padding/border
     *
     * @param mixed $value Either a single value, which will then be used for
     * all four elements, or an array consisting of four elements.
     * @param string $property The property to be set.
     */
    protected function quadrupleSetter($value, $property)
    {
        if (!is_array($value)) {
            $value = [$value, $value, $value, $value];
        }

        $this->$property = $value;
    }
}
