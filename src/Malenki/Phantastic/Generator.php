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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use DirectoryIterator;

/**
 * Le générateur, parcourt l’arborescence et collecte les données pour ensuite 
 * en faire un rendu.
 */
class Generator
{
    protected static $arr_file = array();
    protected $str_src = array();
    protected $str_tag_cloud = null;
    protected $str_cat_list = null;
    protected $str_root_cat_list = null;

    public function __construct()
    {
        $this->str_src = Config::getInstance()->getDir()->src;
    }


    public function add(File $file)
    {
        self::$arr_file[$file->getId()] = $file;
    }

    public function get($id){
        return self::$arr_file[$id];
    }

    /**
     * Parcourt l’arborescence à partir de la source définie auparavant.
     *
     * Le parcour se fait tout en créant les bons types de documents (posts, 
     * pages ou fichiers autres) et en créant les tags et maintenant un historique.
     */
    public function getData()
    {
        $obj_iter = new RecursiveDirectoryIterator(Path::getSrc());
        foreach(new RecursiveIteratorIterator($obj_iter) as $file)
        {
            if($file->isFile())
            {
                $f = new File($file);
                if($f->isPost() || $f->isPage())
                {
                    //Avant tout, tester si ça doit être publié ou pas…
                    if(isset($f->getHeader()->published))
                    {
                        if(!$f->getHeader()->published)
                        {
                            continue;
                        }
                    }
                   
                   
                    if(isset($f->getHeader()->tags))
                    {
                        foreach($f->getHeader()->tags as $tag)
                        {
                            Tag::set($tag)->addId($f->getId());
                        }
                    }
                    


                    if($f->isPost())
                    {
                        Category::set($file->getPath())->addId($f->getId());
                        if(isset($f->getHeader()->date))
                        {
                            if(preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}|[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})$/', $f->getHeader()->date))
                            {

                                if(strlen($f->getHeader()->date) == 10)
                                {
                                    // on met un horaire arbitraire
                                    History::set($f->getHeader()->date . ' 00:00:00')->setId($f->getId());
                                }
                                else
                                {
                                    History::set($f->getHeader()->date)->setId($f->getId());
                                }
                            }
                            elseif(is_integer($f->getHeader()->date))
                            {
                                // cas de la classe YAML de Symfony qui convertit la date en timestamp
                                History::set(date('Y-m-d H:i:s', $f->getHeader()->date))->setId($f->getId());

                            }
                        }
                        else
                        {
                            History::set(date('Y-m-d H:i:s', $file->getMTime()))->setId($f->getId());
                        }
                    }
                    
                }
                elseif($f->isSample())
                {
                    Sample::set($f->getObjPath()->getBasename('.markdown'));
                }
                
                $this->add($f);

            }
        }
    }




    public function attributeDistances()
    {
        foreach(self::$arr_file as $k => $v)
        {
            if($v->isPost())
            {
                $arr_plus = array();

                if($v->hasCategory())
                {
                    foreach($v->getCategory()->getNode() as $str_node)
                    {
                        $arr_plus[] = Config::getInstance()->getCategory($str_node);
                    }
                }
                
                if(isset($v->getHeader()->tags) && count($v->getHeader()->tags))
                {
                    $arr_plus = array_merge($arr_plus, $v->getHeader()->tags);
                }

                if(isset($v->getHeader()->abstract) && strlen($v->getHeader()->abstract))
                {
                    $arr_plus[] = $v->getHeader()->abstract;
                }

                $arr_plus[] = $v->getHeader()->title;

                if(count($arr_plus))
                {
                    TfIdf::set($k, $v->getContent() . ' ' . implode(' ', $arr_plus));
                }
                else
                {
                    TfIdf::set($k, $v->getContent());
                }
            }
        }

        foreach(self::$arr_file as $k => $v)
        {
            if($v->isPost())
            {
                foreach(self::$arr_file as $kk => $vv)
                {
                    if($vv->isPost())
                    {
                        TfIdf::addDistanceFor($k, $kk, TfIdf::distance($k, $kk));
                    }
                }
            }
        }
    }



    public function renderTagCloud()
    {
        if(!Config::getInstance()->getDisableTags())
        {
            if(is_null($this->str_tag_cloud))
            {
                $t = new Template(Template::TAGS);
                $t->assign('tags', Tag::getCloud());
                $this->str_tag_cloud = $t->render();
            }
        }

        return $this->str_tag_cloud;
    }

    public function renderCatList()
    {
        if(!Config::getInstance()->getDisableCategories())
        {
            if(is_null($this->str_cat_list))
            {
                $t = new Template(Template::CATEGORIES);
                $t->assign('categories', Category::getHier());
                $this->str_cat_list = $t->render();
            }
        }

        return $this->str_cat_list;
    }
    
    public function renderRootCatList()
    {
        if(!Config::getInstance()->getDisableCategories())
        {
            $arr_prov = array();

            foreach(Category::getHier() as $c)
            {
                if(!in_array($c->getRootParent()->getName(), $arr_prov))
                {
                    $arr_prov[$c->getRootParent()->getName()] = $c->getRootParent();
                }
            }

            ksort($arr_prov);

            if(is_null($this->str_root_cat_list))
            {
                $t = new Template(Template::ROOT_CATEGORIES);
                $t->assign('root_categories', $arr_prov);
                $this->str_root_cat_list = $t->render();
            }
        }


        return $this->str_root_cat_list;
    }

    protected static function extractInfo(File $f)
    {
        $arr_out = array();

        foreach($f->getHeader() as $k => $v)
        {
            $arr_out[$k] = $v;
        }

        if(Config::getInstance()->getAuthor() && !isset($arr_out['author']))
        {
            $arr_out['author'] = Config::getInstance()->getAuthor();
        }

        $arr_cat = array();
        $arr_cat_prov = array();
        
        if($f->hasCategory())
        {
            foreach($f->getCategory()->getNode() as $str_node)
            {
                $l = new Permalink(Config::getInstance()->getPermalinkCategory());
                $arr_cat_prov[] = $str_node;
                $l->setTitle(implode('/', $arr_cat_prov));
                $arr_cat[] = array(
                    'title' => Config::getInstance()->getCategory($str_node),
                    'url'   => $l->getUrl()
                );
            }
        }

        $arr_tag = array();
        if(isset($f->getHeader()->tags) && count($f->getHeader()->tags))
        {
            $n = 0;
            $tot = count($f->getHeader()->tags);
            foreach($f->getHeader()->tags as $str)
            {
                $str_special = '';

                if($n == 0)
                {
                    $str_special = 'first';
                }
                elseif($n == $tot - 1)
                {
                    $str_special = 'last';
                }
                elseif($n == $tot - 2)
                {
                    $str_special = 'last_but_one';
                }
                else
                {
                    $str_special = '';
                }
                $l = new Permalink(Config::getInstance()->getPermalinkTag());
                $l->setTitle(Permalink::createSlug($str));
                $arr_tag[] = (object) array(
                    'title'     => $str,
                    'url'       => $l->getUrl(),
                    'position'  => $n + 1,
                    'special'   => $str_special
                );
                $n++;
            }
        }


        $str_date_key = date('Y-m-d H:i:s', $f->getDate());
        $id_prev = History::getPrevFor($str_date_key);
        $id_next = History::getNextFor($str_date_key);

        $arr_out['has_prev'] = (boolean) $id_prev;
        $arr_out['has_next'] = (boolean) $id_next;

        if($id_prev)
        {
            $obj_prev = self::$arr_file[$id_prev];
            $arr_out['prev_title'] = $obj_prev->getHeader()->title;
            $arr_out['prev_url'] = $obj_prev->getUrl();
        }

        if($id_next)
        {
            $obj_next = self::$arr_file[$id_next];
            $arr_out['next_title'] = $obj_next->getHeader()->title;
            $arr_out['next_url'] = $obj_next->getUrl();
        }

        $arr_out['content'] = $f->getContent();
        $arr_out['category'] = $f->getCategory();
        $arr_out['categories_breadcrumb'] = $arr_cat;
        $arr_out['tags_list'] = $arr_tag;
        $arr_out['url'] = $f->getUrl();
        $arr_out['date'] = $f->getDate();
        $arr_out['date_rss'] = $f->getDateRss();
        $arr_out['date_atom'] = $f->getDateAtom();

        $str_canonical = preg_replace(
            '@/+$@', '', Config::getInstance()->getBase()
        );
        $str_canonical .= $f->getUrl();

        $arr_out['canonical'] = $str_canonical;
        $arr_out['type'] = $f->isPost() ? 'post' : 'page';

        $bool_to_sitemap = true;

        if(isset($f->getHeader()->sitemap) && !$f->getHeader()->sitemap)
        {
            $bool_to_sitemap = false;
        }

        if(Config::getInstance()->hasSitemap() && $bool_to_sitemap)
        {
            if($f->isPost())
            {
                Sitemap::getInstance()->addPost($str_canonical, $f->getDate());
            }
            else
            {
                Sitemap::getInstance()->addPage($str_canonical, $f->getDate());
            }
        }

        // TODO: prendre en compte next et prev pour éviter les répétitions…
        if($f->isPost() && Config::getInstance()->getRelatedPosts())
        {
            $arr_prov = TfIdf::getNearestIdsFor(
                $f->getId(),
                Config::getInstance()->getRelatedPosts()
            );

            $arr_prov2 = array();

            $n = 0;
            $tot = count($arr_prov);
            foreach($arr_prov as $idNear)
            {
                $str_special = '';

                if($n == 0)
                {
                    $str_special = 'first';
                }
                elseif($n == $tot - 1)
                {
                    $str_special = 'last';
                }
                elseif($n == $tot - 2)
                {
                    $str_special = 'last_but_one';
                }
                else
                {
                    $str_special = '';
                }
                $objNear = self::$arr_file[$idNear];
                
                $arr_prov3 = array();

                foreach($objNear->getHeader() as $k => $v)
                {
                    $arr_prov3[$k] = $v;
                }

                $arr_prov3['url']       = $objNear->getUrl();
                $arr_prov3['date']      = $objNear->getDate();
                $arr_prov3['position']  = $n + 1;
                $arr_prov3['special']   = $str_special;

                $arr_prov2[] = (object) $arr_prov3;
                $n++;
            }

            $arr_out['nearest'] = $arr_prov2;
        }

        return (object) $arr_out;
    }

    public function render()
    {
        foreach(self::$arr_file as $f)
        {
            if(!$f->isFile())
            {
                $t = new Template($f->getHeader()->layout);

                $arr_prov = array();
                $arr_prov2 = array();

                foreach(History::getLast() as $id)
                {
                    $arr_prov[] = self::extractInfo(self::$arr_file[$id]);
                }

                foreach(History::getHist() as $h)
                {
                    $arr_prov2[] = self::extractInfo(self::$arr_file[$h->getFileId()]);
                }

                foreach(self::extractInfo($f) as $k => $v)
                {
                    $t->assign($k, $v);
                }

                $t->assign('last_posts', $arr_prov);
                $t->assign('all_last_posts', $arr_prov2);
                $t->assign('tag_cloud', $this->renderTagCloud());
                $t->assign('cat_list', $this->renderCatList());
                $t->assign('root_cat_list', $this->renderRootCatList());
                $t->assign('categories', Category::getHier());
                $t->assign('site_name', Config::getInstance()->getName());
                $t->assign('site_description', Config::getInstance()->getDescription());
                $t->assign('site_base', Config::getInstance()->getBase());
                $t->assign('site_meta', Config::getInstance()->getMeta());
                file_put_contents(Path::build($f), $t->render());
            }
            elseif(!$f->isSample())
            {
                copy($f->getSrcPath(), Path::build($f));
            }
        }
    }

    public function renderTagPages()
    {
        if(!Config::getInstance()->getDisableTags())
        {
            foreach(Tag::getCloud() as $tag)
            {
                $t = new Template(Template::TAG_PAGE);
                $t->assign('title', $tag->getName());


                $arr_prov = array();

                foreach($tag->getFileIds() as $id)
                {
                    $arr_prov[] = self::extractInfo(self::$arr_file[$id]);
                }

                $t->assign('posts', $arr_prov);
                $t->assign('tag_cloud', $this->renderTagCloud());
                $t->assign('cat_list', $this->renderCatList());
                $t->assign('root_cat_list', $this->renderRootCatList());
                $t->assign('site_name', Config::getInstance()->getName());
                $t->assign('site_description', Config::getInstance()->getDescription());
                $t->assign('site_base', Config::getInstance()->getBase());
                $t->assign('site_meta', Config::getInstance()->getMeta());

                file_put_contents(Path::build($tag), $t->render());
            }



            $t = new Template(Template::TAG_INDEX);

            $t->assign('tag_cloud', $this->renderTagCloud());
            $t->assign('cat_list', $this->renderCatList());
            $t->assign('root_cat_list', $this->renderRootCatList());
            $t->assign('site_name', Config::getInstance()->getName());
            $t->assign('site_description', Config::getInstance()->getDescription());
            $t->assign('site_base', Config::getInstance()->getBase());
            $t->assign('site_meta', Config::getInstance()->getMeta());

            file_put_contents(Path::buildForRootTag(), $t->render());

        }
    }

    public function renderCategoryPages()
    {
        if(!Config::getInstance()->getDisableCategories())
        {
            $arr_tree = Category::getTree();

            foreach(Category::getHier() as $str_slug => $obj_cat)
            {
                if($str_slug != '/') // cas particulier des articles sans catégorie
                {
                    $t = new Template(Template::CATEGORY_PAGE);
                    $t->assign('title', $obj_cat->getName());

                    $arr_prov_file = array();

                    foreach($obj_cat->getFileIds() as $id)
                    {
                        $arr_prov_file[] = self::extractInfo(self::$arr_file[$id]);
                    }

                    $arr_prov_cat = array();

                    foreach($obj_cat->getNode() as $str_node)
                    {
                        $arr_prov_cat[] = sprintf('["%s"]', $str_node);
                    }

                    $arr_prov_cat = array_keys(eval(sprintf('return $arr_tree%s;', implode('', $arr_prov_cat))));

                    $arr_prov_cat2 = array();

                    foreach($arr_prov_cat as $str)
                    {
                        $arr_prov_cat2[] = (object) array(
                            'url' => $obj_cat->getUrl() . $str,
                            'title' => Config::getInstance()->getCategory($str),
                            'slug' => $str
                        );
                    }

                    $arr_slug_full = explode('/', $str_slug); 
                    $t->assign('slug', array_pop($arr_slug_full));
                    $t->assign('posts', $arr_prov_file);
                    $t->assign('cats', $arr_prov_cat2);
                    $t->assign('tag_cloud', $this->renderTagCloud());
                    $t->assign('cat_list', $this->renderCatList());
                    $t->assign('root_cat_list', $this->renderRootCatList());
                    $t->assign('site_name', Config::getInstance()->getName());
                    $t->assign('site_description', Config::getInstance()->getDescription());
                    $t->assign('site_base', Config::getInstance()->getBase());
                    $t->assign('site_meta', Config::getInstance()->getMeta());

                    file_put_contents(Path::build($obj_cat), $t->render());
                }
            }

            $arr_dir = array();

            $obj_iter = new RecursiveDirectoryIterator(Path::getDestCategory(), RecursiveDirectoryIterator::KEY_AS_PATHNAME);
            foreach(new RecursiveIteratorIterator($obj_iter, RecursiveIteratorIterator::CHILD_FIRST) as $file)
            {
                if($file->isDir())
                {
                    if(!in_array(dirname($file->__toString()), $arr_dir))
                    {
                        $arr_dir[] = dirname($file->__toString());
                    }
                }
            }


            foreach($arr_dir as $str_dir)
            {
                $obj_dir_iter = new DirectoryIterator($str_dir);

                $bool_has_index = false;

                $arr_last = array();

                foreach($obj_dir_iter as $obj_file)
                {
                    if($obj_file->isFile() && ($obj_file->getFileName() == 'index.html'))
                    {
                        $bool_has_index = true;
                    }

                    if($obj_file->isDir() && !$obj_file->isDot())
                    {
                        $arr_last[] = (object) array(
                            'url' =>  preg_replace(sprintf('@^%s@', Path::getDest()), '', $obj_file->getPathname()), //TODO: Avoir un moyen de récupérer l’URL proprement
                            'title' => Config::getInstance()->getCategory($obj_file->getFileName()),
                            'slug' => $obj_file->getFileName()
                        );
                    }
                }

                if(!$bool_has_index)
                {
                    $str_slug_cat = preg_replace(sprintf('@^%s@', Path::getDestCategory()), '', $str_dir);
                    $t = new Template(Template::CATEGORY_PAGE);
                    if(Config::getInstance()->hasCategory($str_slug_cat))
                    {
                        $t->assign('title', Config::getInstance()->getCategory($str_slug_cat));
                    }
                    else
                    {
                        $t->assign('title', null);
                    }

                    $arr_slug_full = explode('/', $str_slug_cat);
                    //var_dump($arr_slug_full);
                    $t->assign('posts', array());
                    $t->assign('slug', array_pop($arr_slug_full));
                    $t->assign('cats', $arr_last);
                    $t->assign('tag_cloud', $this->renderTagCloud());
                    $t->assign('cat_list', $this->renderCatList());
                    $t->assign('root_cat_list', $this->renderRootCatList());
                    $t->assign('site_name', Config::getInstance()->getName());
                    $t->assign('site_description', Config::getInstance()->getDescription());
                    $t->assign('site_base', Config::getInstance()->getBase());
                    $t->assign('site_meta', Config::getInstance()->getMeta());

                    file_put_contents(Path::buildForEmptyCategory($str_dir), $t->render());
                }
            }
        }


    }
}
