yii2-cli-layoutbuilder
=================

Allows to render complex ASCII GUI's on console.

What this extension does
------------------------

This extension can be used to render ASCII based GUI's on console (i.e. without
the help of ncurses or similar libraries). It allows to define a GUI in terms of
nested frames which can be nested in horizontal or vertical fashion. Such a
frame can have a fixed or dynamic size (in terms of console window
width/height).


Installation
------------

This extension needs to be included via composer by issuing the following
console command within the root directory of your yii2 project:

~~~
composer require --prefer-dist "coksnuss/yii2-cli-layoutbuilder"
~~~

Thats it. Check the new extension folder to see which classes are available and
how to use or extend from them.
