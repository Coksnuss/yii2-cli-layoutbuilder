<?php
namespace coksnuss\cli\layoutbuilder;

class GuiProgressBar extends GuiElement
{
    public $prefix;
    public $started;
    public $progress;
    public $total;

    protected function asText()
    {
        return 'Not implemented';
    }
}
