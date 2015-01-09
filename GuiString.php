<?php
namespace coksnuss\cli\layoutbuilder;

class GuiString extends GuiElement
{
    public $string;

    protected function asText()
    {
        return $this->string;
        //return sprintf(sprintf('%%-%u.%us', $this->width, $this->width), $this->string);
    }
}
