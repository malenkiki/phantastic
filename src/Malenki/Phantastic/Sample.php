<?php
/*
Copyright (c) 2013 Michel Petit <petit.michel@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Malenki\Phantastic;

/**
 * Petit bloc de texte pouvant être appelé n’importe où dans un template.
 *
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com>
 */
class Sample
{
    protected static $arr_samples = array();
    protected $str_name;
    protected $str_content;

    public function __construct($str_name)
    {
        // TODO: Ajouter une vérification sur le nom…
        $this->str_name = $str_name;
        $this->render();

    }

    public static function has($str_name)
    {
        return array_key_exists($str_name, self::$arr_samples);
    }

    public static function isEmpty()
    {
        return count(self::$arr_samples) == 0;
    }

    public static function set($str_name)
    {
        $sample = new self($str_name);
        $sample->addToCol();

        return self::$arr_samples[$sample->getName()];
    }

    public function addToCol()
    {
        if (!isset(self::$arr_samples[$this->getName()])) {
            self::$arr_samples[$this->getName()] = $this;
        }
    }

    protected function render()
    {
        $str_text = file_get_contents(
            sprintf(
                '%s%s.markdown',
                Path::getSrcSample(),
                $this->str_name
            )
        );

        $markdown = new \dflydev\markdown\MarkdownExtraParser();

        $this->str_content = $markdown->transformMarkdown($str_text);
    }

    public static function get($str_name)
    {
        return self::$arr_samples[$str_name];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->str_name;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->str_content;
    }

}
