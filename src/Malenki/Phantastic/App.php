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
 * Le moteur de l’application.
 *
 * Mise en place des paramètre, détection des options choisies par 
 * l’utilisateur, et lancement du processus. 
 * 
 * @author Michel Petit <petit.michel@gmail.com> 
 * @license MIT
 */
class App
{
    /**
     * Mise en place des options et de leur message d’aide. 
     * 
     * @access public
     * @return void
     */
    public function setOpt()
    {
        $opt = \Malenki\Argile\Options::getInstance();
        $opt->flexible();
        $opt->addUsage('-s SOURCE_DIR -d DEST_DIR [--server]');
        $opt->addUsage('-s SOURCE_DIR -d DEST_DIR -b BASE_URL [--server]');
        $opt->addUsage('-c [CONFIG_FILE] [--server]');
        $opt->description('A static blog generator like Jekyll in Ruby world but with tags, categories, blocs in native.');
        $opt->version('Phantastic version 0.3');

        $opt->newValue('source')
            ->required()
            ->short('s')
            ->long('source')
            ->help('The source directory must contain file to parse.', 'DIR')
        ;

        $opt->newValue('destination')
            ->required()
            ->short('d')
            ->long('destination')
            ->help('The destination directory will contain generated or copied files.', 'DIR')
        ;

        $opt->newValue('baseurl')
            ->required()
            ->short('b')
            ->long('baseurl')
            ->help('Base URL used for final result, i.e. production site version. This value is not used while "server" option is in use.', 'URL')
        ;

        $opt->newValue('language')
            ->required()
            ->short('l')
            ->long('language')
            ->help('Main language used to write posts of the site. Language must be given into 2 letters code format, like, for example, "FR" for franch, "EN" for english, and so on.', 'LANG')
        ;

        $opt->newValue('config')
            ->short('c')
            ->long('config')
            ->help('Configuration file that defines many different values as an YAML file. If FILE is not given, a default "config.yaml" file is read if it is found, otherwise, an error appears.', 'FILE')
        ;

        
        $opt->newSwitch('minimize')
            ->long('minimize')
            ->help('Reduce size of generated files.')
        ;
        

        $opt->newValue('timezone')
            ->long('timezone')
            ->help('Time zone TZ to use to have date, so, TZ could be "Europe/Paris" for example. Default value used is "UTC".', 'TZ')
        ;
        
        $opt->newValue('server')
            ->long('server')
            ->help('Do rendering and run test web server at the address ADR:PORT. If this address is not given, then "localhost:8080" will be used. If option "baseurl" is set, then it will be ignored in this case.', 'ADR:PORT')
        ;

        $opt->newValue('related_posts')
            ->long('related-posts')
            ->help('Give to each post N posts having relation with its content. This can be time consuming feature. By default to 0 if you do not give a positive value.', 'N')
        ;
        

        $opt->newSwitch('sitemap')
            ->long('sitemap')
            ->help('Create XML sitemap.')
        ;

        
        $opt->newSwitch('disabletags')
            ->long('disable-tags')
            ->help('Disable tag rendering: pages and tag cloud.')
        ;

        $opt->newSwitch('disablecategories')
            ->long('disable-categories')
            ->help('Disable categories rendering.')
        ;

    }



    /**
     * Récupère les options passées au programme et met en place la 
     * configuration. 
     * 
     * @access public
     * @return void
     */
    public function getOpt()
    {
        // OK, on interpète ce qu’on a en ligne de commande et on détermine quoi faire…
        $opt = \Malenki\Argile\Options::getInstance();
        $opt->parse();



        if($opt->has('config'))
        {
            $str_config_file = 'config.yaml';

            if($opt->get('config'))
            {
                $str_config_file = $opt->get('config');
            }


            if(is_readable($str_config_file))
            {
                Config::getInstanceWithConfigFile($str_config_file);
            }

        }
        else
        {
            if($opt->has('timezone'))
            {
                Config::getInstance()->setTimezone($opt->get('timezone'));
            }
            
            if($opt->has('related_posts'))
            {
                Config::getInstance()->setTimezone($opt->get('related_posts'));
            }
            
            
            if($opt->has('server'))
            {
                Config::getInstance()->setServer($opt->get('server'));
            }
            
            if($opt->has('language'))
            {
                Config::getInstance()->setServer($opt->get('language'));
            }
            
            if($opt->has('sitemap'))
            {
                Config::getInstance()->setSitemap();
            }

            if($opt->has('disabletags'))
            {
                Config::getInstance()->setDisableTags();
            }

            if($opt->has('disablecats'))
            {
                Config::getInstance()->setDisableCategories();
            }
        }
    }




    /**
     * Lance le générateur, le serveur… Bref, le cœur du programme ! 
     * 
     * @access public
     * @return void
     */
    public function run()
    {
        if(phpversion() >= '5.5.0')
        {
            cli_set_process_title('fictif');
        }
        $opt = \Malenki\Argile\Options::getInstance();

        if($opt->has('help') || $opt->has('version'))
        {
            exit(0);
        }

        date_default_timezone_set(Config::getInstance()->getTimezone());

        if(Config::getInstance()->getLanguage())
        {
            $str_lang = Config::getInstance()->getLanguage();

            // Available language are here.
            // TODO: ca, cz, da, de, el, en, eo, es, eu, fi, hu, it, nl, no, pt, ro, ru, sv
            if($str_lang == 'fr')
            {
                setlocale(LC_ALL, 'fr_FR.utf8','fra', 'fr_FR.utf-8', 'fr_FR');
            }
        }

        $g = new Generator();
        $g->getData();

        if(Config::getInstance()->getRelatedPosts())
        {
            if(Config::getInstance()->getLanguage())
            {
                $str_file_stop_words = sprintf(
                    '%sdata/%s.txt',
                    Path::getAppRoot(),
                    Config::getInstance()->getLanguage()
                );

                if(file_exists($str_file_stop_words))
                {
                    TfIdf::loadStopWordsFile($str_file_stop_words);
                }
            }
            $g->attributeDistances();
        }
        
        $g->render();

        if(!Tag::isEmpty())
        {
            $g->renderTagPages();
        }

        if(!Category::isEmpty())
        {
            $g->renderCategoryPages();
        }

        if(count(Sitemap::getInstance()))
        {
            Sitemap::getInstance()->render();
        }


        //debug, test…
        //var_dump(History::getLast());
        //var_dump(Category::getTree());
        //var_dump(Category::getFileIdsAtLevel());


        if(Config::getInstance()->serverAvailable())
        {
            if(Server::canRun())
            {
                printf("Serveur de test lancé à l’adresse http://%s. Pour quitter, pressez « Contrôle-C »\n", Config::getInstance()->getServer());
                $s = new Server();
                $s->setHost(Config::getInstance()->getServer());
                $s->run();
            }
            else
            {
                //TODO: Utiliser la futur classe de Log pour ce message
                printf("Impossible de lancer un serveur sur cette 
                    configuration. Mettez à jour PHP ou installez Python. 
                    Sinon désactivez l’option de lancement d’un serveur dans 
                    l’appel à Phantastic ou dans votre fichier de 
                    configuration\n");
            }
        }
    }
}

