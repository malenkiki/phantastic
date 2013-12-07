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
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com> 
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
        $opt = Malenki\Argile\Options::getInstance();
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
            ->help('Le dossier contenant les fichiers à traiter.', 'DIR')
        ;

        $opt->newValue('destination')
            ->required()
            ->short('d')
            ->long('destination')
            ->help('Le dossier dans lequel seront créés les fichiers.', 'DIR')
        ;

        $opt->newValue('baseurl')
            ->required()
            ->short('b')
            ->long('baseurl')
            ->help('URL de base utilisé pour le site généré. Cette valeur n’est pas utilisée si l’option « server » est choisie.', 'URL')
        ;

        $opt->newValue('language')
            ->required()
            ->short('l')
            ->long('language')
            ->help('Langue principale de rédaction du site. La langue doit être précisée au format 2 lettres, exemple : « FR » pour français, « EN » pour anglais, etc.', 'LANG')
        ;

        $opt->newValue('config')
            ->setShort('c')
            ->setLong('config')
            ->setHelp('Fichier de configuration contenant différentes valeurs sous forme d’un fichier YAML. Si FICHIER n’est pas spécifié, alors un fichier « config.yaml » sera lu par défaut, mais s’il n’existe pas, déclenchera une erreur.', 'FILE')
        ;

        
        $opt->newSwitch('minimize')
            ->long('minimize')
            ->help('Réduit la taille des fichiers générés.')
        ;
        

        $opt->newValue('timezone')
            ->long('timezone')
            ->help('Fuseau horaire TZ à utiliser pour les dates, comme par exemple « Europe/Paris ». La valeur utilisée par défaut est « UTC ».', 'TZ')
        ;
        
        $opt->newValue('server')
            ->long('server')
            ->help('Fait un rendu et lance un serveur web de test à l’adresse ADR:PORT. Si l’adresse n’est pas précisée, alors « localhost:8080 » sera prise. Si l’option « baseurl » est précisée, elle sera ignorée.', 'ADR:PORT')
        ;

        $opt->newValue('related_posts')
            ->long('related-posts')
            ->help('Attribue pour chaque post N posts en relation avec son contenu. Ceci peut être gourmand en calcul. Par défaut à zéro si vous ne lui donnez pas une valeur positive.', 'N')
        ;
        

        $opt->newSwitch('sitemap')
            ->long('sitemap')
            ->help('Génère un sitemap XML du site.')
        ;

        
        $opt->newSwitch('disabletags')
            ->long('disable-tags')
            ->help('Désactive le rendu des tags, que ce soit leurs pages dédiées ou le nuage de tags.')
        ;

        $opt->newSwitch('disablecategories')
            ->long('disable-categories')
            ->help('Désactive le rendu des catégories.')
        ;

        Options::getInstance()->setHelp('Affiche ce message d’aide.');
        Options::getInstance()->setVersion('Affiche la version de Phantastic.');
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
        $opt = Malenki\Argile\Options::getInstance();
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

