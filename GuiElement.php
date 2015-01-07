<?php
namespace coksnuss\cli\layoutbuilder;

abstract class GuiElement extends \yii\base\Object
{
    /**
     * @var integer The total available length of this element in characters.
     * This is determined automatically but can be overridden if required.
     */
    public $width; // TODO: Make those protected?
    /**
     * @var integer The total available height of this element in characters.
     * This is determined automatically but can be overridden if required.
     */
    public $height; // TODO: Make those protected?
    /**
     * @var integer|float If given as integer this is the size of the GUI(element)
     * in terms of characters. If given as float between 0 and 1 this is the size
     * of the element in percentage relative to the window or container size. If
     * the layout is equal to 'horizontal' this determines the width of the
     * container, if the layout is 'vertical' this determines the height.
     * If the width and/or height attribute is given, this is ignored.
     * If not given the element size is distributed equally within the remaining
     * space.
     */
    public $size; // TODO: Check if its possible to move it back somehow to GUIFrame because the term of 'layout' is introduced there!

    /**
     * Renders the GUI element.
     */
    abstract public function render();

    /**
     * Limits the size of this element.
     *
     * @param integer $width The width in characters that this element is allowed to be spanned.
     * @param integer $height The height in characters that this element is allowed to be spanned.
     */
    public function limitDimensions($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }
}
