<?php
namespace coksnuss\cli\layoutbuilder;

use Yii;
use yii\helpers\Console;

// TODO: new attribute for strategy (overflow: hidden, linebreak etc..)
// TODO: Support border (decrease width/height by 2, or better: introduce padding property (also may introduce margin))
class GuiFrame extends GuiElement
{
    const LAYOUT_HORIZONTAL = 'horizontal';
    const LAYOUT_VERTICAL   = 'vertical';

    /**
     * @var string Either 'horizontal' or 'vertical'.
     */
    public $layout = 'horizontal';
    /**
     * @var boolean Whether to draw a window border.
     */
    public $drawBorder = false;

    /**
     * @var array The elements of the GUI. Each element must implement the
     * GUIElement interface. Strings will be casted to GuiString.
     */
    protected $childs = [];


    protected function beforeRender()
    {
        $this->determineOwnSize();
        $this->propagateSize();
    }

    protected function determineOwnSize()
    {
        $screenSize = Console::getScreenSize();

        if ($screenSize === false) {
            throw new \yii\base\UserException('Couldnt determine console window size! Provide fixed sizes!');
        }

        list($screenWidth, $screenHeight) = $screenSize;

        if ($this->width === null && $this->layout === self::LAYOUT_HORIZONTAL) {
            if (is_float($this->size)) {
                $this->width = intval(round($screenWidth * $this->size));
            } elseif (is_integer($this->size)) {
                $this->width = $this->size;
            } else {
                $this->width = $screenWidth;
            }
        }
        if ($this->height === null && $this->layout === self::LAYOUT_VERTICAL) {
            if (is_float($this->size)) {
                $this->height = intval(round($screenHeight * $this->size));
            } elseif (is_integer($this->size)) {
                $this->height = $this->size;
            } else {
                $this->height = $screenHeight;
            }
        }
        if ($this->width === null) {
            $this->width = $screenWidth;
        }
        if ($this->height === null) {
            $this->height = $screenHeight;
        }

        if ($this->width > $screenWidth || $this->height > $screenHeight) {
            throw new \yii\base\UserException('GUI dimensions are too large!');
        }
    }

    protected function propagateSize()
    {
        $dynamicElements = [];
        $precisionError = 0;
        $usedSize = 0;
        $availableSize = $this->layout === self::LAYOUT_HORIZONTAL ?
            $this->width :
            $this->height;

        foreach ($this->childs as $guiElement)
        {
            if ($guiElement->size === null) {
                $dynamicElements[] = $guiElement;
            } else if (is_float($guiElement->size)) {
                $sizeFloat = $availableSize * $guiElement->size;
                $size = intval($sizeFloat);
                $precisionError += $sizeFloat - $size;
                $usedSize += $size;

                if ($this->layout === self::LAYOUT_HORIZONTAL) {
                    // TODO: This overrides the width, if set
                    $guiElement->limitDimensions($size, $this->height);
                } else {
                    // TODO: This overrides the height, if set
                    $guiElement->limitDimensions($this->width, $size);
                }
            } else if (is_integer($guiElement->size)) {
                $usedSize += $$guiElement->size;

                if ($this->layout === self::LAYOUT_HORIZONTAL) {
                    // TODO: This overrides the width, if set
                    $guiElement->limitDimensions($guiElement->size, $this->height);
                } else {
                    // TODO: This overrides the height, if set
                    $guiElement->limitDimensions($this->width, $guiElement->size);
                }
            }
        }

        if ($usedSize > $availableSize) {
            throw new \yii\base\UserException('Invalid GUI sizes!');
        }

        if (!empty($dynamicElements)) {
            $availableSize -= $usedSize;
            $elementSizeFloat = $availableSize / count($dynamicElements);
            $elementSize = intval($elementSizeFloat);
            $precisionError += count($dynamicElements) * ($elementSizeFloat - $elementSize);

            $lastElement = array_pop($dynamicElements);
            foreach ($dynamicElements as $guiElement)
            {
                if ($this->layout === self::LAYOUT_HORIZONTAL) {
                    $guiElement->limitDimensions($elementSize, $this->height);
                } else {
                    $guiElement->limitDimensions($this->width, $elementSize);
                }
            }

            // Equivelent of below if-else construct
            /*$lastElement->limitDimensions(
                $this->layout === self::LAYOUT_HORIZONTAL ? $elementSize + intval($precisionError) : $this->height,
                $this->layout !== self::LAYOUT_HORIZONTAL ? $elementSize + intval($precisionError) : $this->width);*/

             if ($this->layout === self::LAYOUT_HORIZONTAL) {
                $lastElement->limitDimensions($elementSize + intval($precisionError), $this->height);
            } else {
                $lastElement->limitDimensions($this->width, $elementSize + intval($precisionError));
            }
        }
    }

    /**
     * Adds a new GUI element to the layout.
     */
    public function addElement($guiElement)
    {
        if (is_string($guiElement)) {
            $guiElement = Yii::createObject([
                'class' => GuiString::className(),
                'string' => $guiElement,
            ]);
        }

        if ($guiElement instanceof GuiElement) {
            $this->childs[] = $guiElement;
        } else {
            throw new \yii\base\UserException('addElement requires a string or a GUI element to be added');
        }
    }

    /**
     * @return string The GUI content. Will also take care of cutting too
     * lenghty content or insert line breaks if possible.
     */
    public function render()
    {
        // TODO: Trigger before/after render event (could be used to draw border in conjunction with a renderLineComplete event)
        $this->beforeRender();
        if ($this->layout === self::LAYOUT_HORIZONTAL) {
            return $this->renderHorizontal();
        } else {
            return $this->renderVertical();
        }
    }

    public function renderHorizontal()
    {
        if (empty($this->childs)) {
            return '';
        }

        $rows = 0;
        $content = '';
        $regularLineWidth = 0;
        $sizes = [];
        $childs = [];

        foreach ($this->childs as $guiElement) {
            $regularLineWidth += $guiElement->width;
            $sizes[] = $guiElement->width;
            $childs[] = explode(PHP_EOL, $guiElement->render());
        }

        array_unshift($childs, null);
        $lines = call_user_func_array('array_map', $childs);

        foreach ($lines as $line)
        {
            foreach ($line as $frameNumber => $text) {
                $content .= sprintf(sprintf('%%-%u.%us', $sizes[$frameNumber], $sizes[$frameNumber]), $text);
            }

            $content .= PHP_EOL;
            // TODO: check if lines exeeds hight of the frame and make padding
        }

        return $content;
    }

    public function renderVertical()
    {
        $content = '';
        $availableRowsFrame = $this->height;

        foreach ($this->childs as $guiElement)
        {
            if ($availableRowsFrame === 0) {
                break;
            }

            $lines = explode(PHP_EOL, $guiElement->render());
            $availableRowsElement = min($guiElement->height, $availableRowsFrame);

            foreach ($lines as $line) {
                if ($availableRowsElement === 0) {
                    break;
                }

                $content .= sprintf(sprintf('%%-%u.%us', $this->width, $this->width), $line) . PHP_EOL;

                --$availableRowsElement;
                --$availableRowsFrame;
            }
        }

        return $content;
    }
}
