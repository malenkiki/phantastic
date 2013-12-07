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
 * Permet la création d’un fichier sitemap.xml.
 *
 * Chaque post prend une priorité de 1, les autres pages restent à leur valeur 
 * par défaut.
 *
 * @copyright 2013 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com> 
 */
class Sitemap implements \Countable
{
    const FILE_NAME = 'sitemap.xml';
    
    const POST_PRIORITY = '1';
    
    const PAGE_PRIORITY = '0.5'; // string to keep always the english format

    
    
    protected static $obj_instance = null;



    protected $arr_pages = array();
    
    protected $arr_posts = array();

    
    
    public static function getInstance()
    {
        if(is_null(self::$obj_instance))
        {
            self::$obj_instance = new self();
        }

        return self::$obj_instance;
    }




    public function addPage($url, $date = null)
    {
        $page = new \stdClass();
        $page->url = $url;
        $page->date = $date;
        $page->priority = self::PAGE_PRIORITY;

        $this->arr_pages[md5($url)] = $page;
    }



    public function addPost($url, $date = null)
    {
        $post = new \stdClass();
        $post->url = $url;
        $post->date = $date;
        $post->priority = self::POST_PRIORITY;

        $this->arr_posts[md5($url)] = $post;
    }


    public function count()
    {
        return count($this->arr_pages) + count($this->arr_posts);
    }


    public function render()
    {
        if($this->count())
        {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $urlset = $dom->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');

            if(count($this->arr_posts))
            {
                foreach($this->arr_posts as $post)
                {
                    $url = $dom->createElement('url');

                    $loc = $dom->createElement('loc', $post->url);
                    $priority = $dom->createElement('priority', $post->priority);

                    $url->appendChild($loc);
                    $url->appendChild($priority);

                    if($post->date)
                    {
                        $lastmod = $dom->createElement('lastmod', date('Y-m-d', $post->date));
                        $url->appendChild($lastmod);
                    }

                    $urlset->appendChild($url);
                }
            }
            
            if(count($this->arr_pages))
            {
                foreach($this->arr_pages as $page)
                {
                    $url = $dom->createElement('url');

                    $loc = $dom->createElement('loc', $page->url);
                    $priority = $dom->createElement('priority', $page->priority);

                    $url->appendChild($loc);
                    $url->appendChild($priority);

                    if($page->date)
                    {
                        $lastmod = $dom->createElement('lastmod', date('Y-m-d', $page->date));
                        $url->appendChild($lastmod);
                    }

                    $urlset->appendChild($url);
                }
            }

            $dom->appendChild($urlset);

            $dom->formatOutput = true;
            $dom->save(Path::getDest() . self::FILE_NAME);
            //TODO robots.txt
        }
    }
}
