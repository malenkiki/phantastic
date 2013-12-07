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

class Template 
{
    const TAG_PAGE = 'tag-page';
    const TAG_INDEX = 'tag-index';

    const CATEGORY_PAGE = 'category-page';
    
    const TAGS = 'tags';
    const CATEGORIES = 'categories';
    const ROOT_CATEGORIES = 'root-categories';

    protected $arr_data = array();
    protected $str_tmpl = null;

    public function __construct($tmpl)
    {
        $this->str_tmpl = $tmpl;
    }

    public function setContent($str)
    {
        $this->assign('content', $str);
    }

    public function assign($key, $value)
    {
        $this->arr_data[$key] = $value;
    }

    protected function partial($str)
    {
        $data = (object) $this->arr_data;
        require(
            sprintf(
                '%s%s.phtml',
                Config::getInstance()->getDir()->template,
                $str
            )
        );
    }


    public function hasText($str_key)
    {
        return Sample::has($str_key);
    }

    public function text($str_key)
    {
        // TODO: Je ne lève pas d’erreur, mais dans le futur, un message de log va apparaitre.
        if(Sample::has($str_key))
        {
            return Sample::get($str_key)->getContent();
        }
        else
        {
            return '';
        }
    }

    public function truncate($str, $int_length, $str_end = '…')
    {
        if(mb_strlen($str, 'UTF-8') > $int_length)
        {
            $str = strip_tags($str);
            // TODO: Convertir les entités HTML en caractère brut…
            $int_length = $int_length - mb_strlen($str_end, 'UTF-8');
            return mb_substr($str, 0, $int_length, 'UTF-8') . $str_end;
        }
        else
        {
            return $str;
        }
    }

    public function render()
    {
        $data = (object) $this->arr_data;

        $str_file = sprintf(
            '%s%s.phtml',
            Config::getInstance()->getDir()->template,
            $this->str_tmpl
        );

        if(!file_exists($str_file))
        {
            throw new \Exception(sprintf('File %s does not exist! Rendering abort.', $str_file));
        }

        ob_start();
        require($str_file);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
