<?php
/*
 * This file is part of Phantastic.
 *
 * Phantastic is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Phantastic is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Phantastic.  If not, see <http://www.gnu.org/licenses/>.
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
