<?php
namespace coksnuss\cli\layoutbuilder;

class GuiString extends GuiElement
{
    public $string;

    public function render()
    {
        return sprintf(sprintf('%%-%u.%us', $this->width, $this->width), $this->string);
    }
}
