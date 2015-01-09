<?php
namespace coksnuss\cli\layoutbuilder;

use Yii;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use yii\base\UserException;

class GuiFrame extends GuiElement
{
    /**
     * @var string Either 'horizontal' or 'vertical'.
     */
    public $layout = 'horizontal';

    /**
     * @var array The elements of the frame.
     */
    protected $childs = [];


    /**
     * @inheritdoc
     */
    protected function asText()
    {
        $this->determineOwnSize();

        if ($this->layout === self::LAYOUT_HORIZONTAL) {
            return $this->renderHorizontal();
        } else {
            return $this->renderVertical();
        }
    }

    /**
     * Adds a new GUI element to the frame.
     *
     * @param GuiElement|string $guiElement The GUI element to be added. Strings
     * will automatically be casted to GuiString instances.
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
            $this->on(self::EVENT_FRAME_RESIZE, [$guiElement, 'resize']);
            $this->childs[] = $guiElement;
        } else {
            throw new UserException('addElement requires a string or a GUI element to be added');
        }
    }

    /**
     * @inheritdoc
     */
    public function resize($event)
    {
        if (parent::resize($event)) {
            $this->trigger(self::EVENT_FRAME_RESIZE, $this->createResizeEvent());

            return true;
        }

        return false;
    }

    /**
     * Determines and set the size of the frame, if the attributes were not set
     * before.
     *
     * TODO: Create dummy parent frame with size of console and fire the resize
     * event.
     */
    protected function determineOwnSize()
    {
        // True for the outermost frame only
        if ($this->width == null || $this->height === null)
        {
            // TODO: allow to define fixed size in which the frame is beeing rendered within.
            $screenSize = Console::getScreenSize();

            if ($screenSize === false) {
                throw new UserException('Couldnt determine console window size!');
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
                throw new UserException('GUI dimensions are too large!');
            }

            $this->trigger(self::EVENT_FRAME_RESIZE, $this->createResizeEvent());
        }
    }

    /**
     * @return GuiResizeEvent The resize event for this frame with prefilled
     * attributes.
     */
    private function createResizeEvent()
    {
        // TODO: check for valid data (fluid <= 1.0 etc...)
        $frameWidth    = $this->getInnerWidth();
        $frameHeight   = $this->getInnerHeight();
        $availableSize = $this->layout === self::LAYOUT_HORIZONTAL ?
            $frameWidth :
            $frameHeight;

        $sizes = array_reduce(
            $this->childs,
            function ($carry, $item) use ($availableSize) {
                if ($item->size === null) {
                    ++$carry['dynamic'];
                } elseif (is_float($item->size)) {
                    $fluidSizeFloat = $availableSize * $item->size;
                    $fluidSize = intval($fluidSizeFloat);

                    $carry['fixed'] += $fluidSize;
                    $carry['precisionError'] += $fluidSizeFloat - $fluidSize;
                } elseif (is_integer($item->size)) {
                    $carry['fixed'] += $item->size;
                }

                return $carry;
            },
            ['dynamic' => 0, 'fixed' => 0, 'precisionError' => 0.0]
        );

        $dynamicSize = $sizes['dynamic'] === 0 ? null : ($frameWidth - $sizes['fixed']) / $sizes['dynamic'];
        $singlePrecisionError = $dynamicSize - intval($dynamicSize);
        $sizes['precisionError'] += $singlePrecisionError * $sizes['dynamic'];

        return new GuiResizeEvent([
            'dynamicChilds' => $sizes['dynamic'],
            'dynamicSize' => $dynamicSize,
            'precisionError' => intval($sizes['precisionError']),
        ]);
    }

    /**
     * TODO: Divide further into subfunctions
     * @return string The GUI content for the horizontal layout
     */
    protected function renderHorizontal()
    {
        $content = [];

        if (!empty($this->childs)) {
            $sizes = [];
            $childs = [];

            foreach ($this->childs as $guiElement) {
                $sizes[] = $guiElement->width;
                $childs[] = explode(PHP_EOL, $guiElement->render());
            }

            array_unshift($childs, null);
            $lines = call_user_func_array('array_map', $childs);

            foreach ($lines as $line)
            {
                $text = '';

                if (is_array($line)) {
                    foreach ($line as $frameNumber => $frameLine) {
                        $text .= sprintf(sprintf('%%-%u.%us', $sizes[$frameNumber], $sizes[$frameNumber]), $frameLine);
                    }
                } else {
                    $text = $line;
                }

                $content[] = $text;
                // TODO: check if lines exeeds hight of the frame and make padding
            }
        }

        return implode(PHP_EOL, $this->surround($content));
    }

    /**
     * TODO: Divide further into subfunctions
     * @return string The GUI content for the vertical layout
     */
    protected function renderVertical()
    {
        $content = [];
        $frameContentWidth = intval($this->getInnerWidth());
        $availableRowsFrame = intval($this->getInnerHeight());

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

                $content[] = sprintf(sprintf('%%-%u.%us', $frameContentWidth, $frameContentWidth), $line);

                --$availableRowsElement;
                --$availableRowsFrame;
            }
        }

        return implode(PHP_EOL, $this->surround($content));
    }

    /**
     * Expects the raw content which is printed inside the frame and applies
     * margin / border / padding to it.
     *
     * @return string The content, surrounded by its frame decoration.
     */
    private function surround($content)
    {
        // Should be ASCII signs, class is not ready to support UTF8 yet.
        static $border = [
            'top_left'     => '+', // '┌',
            'top_right'    => '+', // '┐',
            'horizontal'   => '-', // '─',
            'vertical'     => '|', // '│',
            'bottom_left'  => '+', // '└',
            'bottom_right' => '+', // '┘',
        ];
        list($mT, $mR, $mB, $mL) = $this->getMargin();
        list($pT, $pR, $pB, $pL) = $this->getPadding();
        list($bT, $bR, $bB, $bL) = $this->getBorder();
        $borderWidth = $this->getWidth() - ($mL + $mR);
        // $borderHeight = $this->getHeight() - ($mT + $mB); // TODO: Fill height
        $prepend = [];
        $append = [];

        // Margin top
        for ($i = 0; $i != $mT; ++$i) $prepend[] = '';

        // Border top
        for ($i = 0; $i != $bT; ++$i) {
            if ($borderWidth == 1) {
                $prepend[] = $border['horizontal'];
            } elseif ($borderWidth >= 2) {
                $prepend[] =
                    $border['top_left'] .
                    str_repeat($border['horizontal'], $borderWidth - 2) .
                    $border['top_right'];
            }
        }

        // Padding top
        for ($i = 0; $i != $pT; ++$i) $prepend[] = '';

        // Left & Right margin/border/pading
        $contentWidth = $this->getInnerWidth();
        foreach ($content as &$line) {
            $newLine = '';

            for ($i = 0; $i != $mL; ++$i) $newLine .= ' ';
            for ($i = 0; $i != $bL; ++$i) $newLine .= $border['vertical'];
            for ($i = 0; $i != $pL; ++$i) $newLine .= ' ';

            $newLine .= sprintf(sprintf('%%-%d.%ds', $contentWidth, $contentWidth), $line);

            for ($i = 0; $i != $pR; ++$i) $newLine .= ' ';
            for ($i = 0; $i != $bR; ++$i) $newLine .= $border['vertical'];
            for ($i = 0; $i != $mR; ++$i) $newLine .= ' ';

            $line = $newLine;
        }

        // Padding bottom
        for ($i = 0; $i != $pB; ++$i) $append[] = '';

        // Border bottom
        for ($i = 0; $i != $bB; ++$i) {
            if ($borderWidth == 1) {
                $append[] = $border['horizontal'];
            } elseif ($borderWidth >= 2) {
                $append[] =
                    $border['bottom_left'] .
                    str_repeat($border['horizontal'], $borderWidth - 2) .
                    $border['bottom_right'];
            }
        }

        // Margin bottom
        for ($i = 0; $i != $mB; ++$i) $append[] = '';

        return ArrayHelper::merge($prepend, $content, $append);
    }
}
