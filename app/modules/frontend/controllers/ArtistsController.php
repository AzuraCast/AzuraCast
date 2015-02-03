<?php
namespace Modules\Frontend\Controllers;

use \Entity\Artist;
use \Entity\ArtistType;

class ArtistsController extends BaseController
{
    public function indexAction()
    {
        $type_names = ArtistType::getTypeNames();
        $this->view->type_names = $type_names;

        if ($this->hasParam('type'))
        {
            $this->view->type = $type = $this->getParam('type');
            $this->view->type_name = $type_names[$type];

            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at WHERE (a.is_approved = 1 AND at.id = :type) ORDER BY a.name ASC')
                ->setParameter('type', $type);
        }
        else if ($this->hasParam('q'))
        {
            $this->view->q = $q = $this->getParam('q');
            $this->view->type_name = 'Search Results';

            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at WHERE (a.is_approved = 1) AND (a.name LIKE :q) ORDER BY a.name ASC')
                ->setParameter('q', '%'.addcslashes($query, "%_").'%');
        }

        if ($query)
        {
            $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1));
            $this->render('list');
            return;
        }
    }

    public function reviewAction()
    {

    }

    /*
    public function viewAction()
    {
        $id = (int)$this->getParam('id');

        $record = Artist::find($id);

        if (!($record instanceof Artist))
            throw new \DF\Exception\DisplayOnly('Artist Not Found');

        // Pull overall news.
        $news = $this->em->createQuery('SELECT n FROM Entity\News n WHERE n.type = :type AND n.author_id = :artist_id ORDER BY n.timestamp DESC')
            ->setParameter('type', 'artist')
            ->setParameter('artist_id', $record->id)
            ->getArrayResult();

        $news_categories = array();
        foreach($news as $article)
        {
            $news_categories['all']++;
            $news_categories[$article['source']]++;
        }

        $categories_raw = Artist::getSocialTypes();
        $categories = array(
            'all'   => 'All Items ('.$news_categories['all'].')',
        );
        foreach($categories_raw as $cat_key => $cat_info)
        {
            if (isset($news_categories[$cat_key]))
                $categories[$cat_key] = $cat_info['name'].' ('.$news_categories[$cat_key].')';
        }

        $this->view->news = $news;

        $this->view->categories = $categories;
        $this->view->artist = $record;
    }
    */

    public function submitAction()
    {
        return $this->redirectFromHere(array('action' => 'profile'));
    }

    public function profileAction()
    {
        $this->acl->checkPermission('is logged in');
        $user = $this->auth->getLoggedInUser();

        $form_config = $this->module_config['admin']->forms->artist->toArray();
        unset($form_config['groups']['admin']);

        $form = new \DF\Form($form_config);

        if ($user->artist instanceof Artist)
        {
            $record = $user->artist;
            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            $files = $form->processFiles('artists');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            if (!($record instanceof Artist))
            {
                // Check for existing artist with same name.
                $record = Artist::findAbandonedByName($data['name']);

                if ($record instanceof Artist)
                {
                    $record->user = $user;
                }
                else
                {
                    $record = new Artist;
                    $record->is_approved = false;
                    $record->user = $user;
                }
            }

            $record->fromArray($data);
            $record->save();
            
            $this->alert('<b>Your artist profile has been submitted!</b><br>After review, your profile will be listed on the Ponyville Live network artist directory. Thank you for your submission.', 'green');
            $this->redirectFromHere(array('action' => 'index'));
            return;
        }

        if ($user->is_artist)
            $this->view->setVar('title', 'Update Artist Profile');
        else
            $this->view->setVar('title', 'Submit an Artist Profile');
        
        $this->renderForm($form);
    }
}