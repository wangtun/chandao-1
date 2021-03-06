<?php
include '../../control.php';
/**
 * Created by PhpStorm.
 * User: 月下亭中人
 * Date: 2017/12/29
 * Time: 10:27
 */
class myStory extends story
{
    /**
     * Create a story.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  int    $moduleID
     * @param  int    $storyID
     * @param  int    $projectID
     * @param  int    $bugID
     * @param  int    $planID
     * @param  int    $todoID
     * @param  string $extra for example feedbackID=0
     * @access public
     * @return void
     */
    public function create($productID = 0, $branch = 0, $moduleID = 0, $storyID = 0, $projectID = 0, $bugID = 0, $planID = 0, $todoID = 0, $extra = '')
    {
        /* Whether there is a object to transfer story, for example feedback. */
        $extra = str_replace(array(',', ' '), array('&', ''), $extra);
        parse_str($extra, $output);
        foreach($output as $paramKey => $paramValue)
        {
            if(isset($this->config->story->fromObjects[$paramKey]))
            {
                $fromObjectIDKey  = $paramKey;
                $fromObjectID     = $paramValue;
                $fromObjectName   = $this->config->story->fromObjects[$fromObjectIDKey]['name'];
                $fromObjectAction = $this->config->story->fromObjects[$fromObjectIDKey]['action'];
                break;
            }
        }

        /* If there is a object to transfer story, get it by getById function and set objectID,object in views. */
        if(isset($fromObjectID))
        {
            $fromObject = $this->loadModel($fromObjectName)->getById($fromObjectID);
            if(!$fromObject) die(js::error($this->lang->notFound) . js::locate('back', 'parent'));

            $this->view->$fromObjectIDKey = $fromObjectID;
            $this->view->$fromObjectName  = $fromObject;
        }

        if(!empty($_POST))
        {
            $response['result']  = 'success';
            $response['message'] = '';

            $storyResult = $this->story->create($projectID, $bugID, $from = isset($fromObjectIDKey) ? $fromObjectIDKey : '');
            if(!$storyResult or dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            $storyID = $storyResult['id'];
            if($storyResult['status'] == 'exists')
            {
                $response['message'] = sprintf($this->lang->duplicate, $this->lang->story->common);
                if($projectID == 0)
                {
                    $response['locate'] = $this->createLink('story', 'view', "storyID={$storyID}");
                }
                else
                {
                    $response['locate'] = $this->createLink('project', 'story', "projectID=$projectID");
                }
                $this->send($response);
            }

            $action = $bugID == 0 ? 'Opened' : 'Frombug';
            $extra  = $bugID == 0 ? '' : $bugID;
            /* Record related action, for example FromFeedback. */
            if(isset($fromObjectID))
            {
                $action = $fromObjectAction;
                $extra  = $fromObjectID;
            }
            $actionID = $this->action->create('story', $storyID, $action, '', $extra);

            if($todoID > 0)
            {
                $this->dao->update(TABLE_TODO)->set('status')->eq('done')->where('id')->eq($todoID)->exec();
                $this->action->create('todo', $todoID, 'finished', '', "STORY:$storyID");
            }

            if($this->post->newStory)
            {
                $response['message'] = $this->lang->story->successSaved . $this->lang->story->newStory;
                $response['locate']  = $this->createLink('story', 'create', "productID=$productID&branch=$branch&moduleID=$moduleID&story=0&projectID=$projectID&bugID=$bugID");
                $this->send($response);
            }

            $response['locate'] = $this->createLink('project', 'story', "projectID=$projectID");
            if($projectID == 0) $response['locate'] = $this->createLink('story', 'view', "storyID=$storyID");
            $this->send($response);
        }

        /* Set products, users and module. */
        if($projectID != 0)
        {
            //4019 项目内增加对需求新增和关联的权限控制
            $project = $this->loadModel('project')->getById($projectID);
            if ($project->lockStory == '1')
            {
                echo(js::error('该项目需求已经锁定，如需新增或变更需求请联系项目管理组'));
                die(js::locate('back'));
            }

            $products = $this->product->getProductsByProject($projectID);
            $product  = $this->product->getById(($productID and array_key_exists($productID, $products)) ? $productID : key($products));
        }
        else
        {
            $products = $this->product->getPairs('noclosed');
            $product  = $this->product->getById($productID ? $productID : key($products));
            if(!isset($products[$product->id])) $products[$product->id] = $product->name;
        }

        $users = $this->user->getPairs('pdfirst|noclosed|nodeleted');
        $moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'story', 0, $branch);
        if(empty($moduleOptionMenu)) die(js::locate(helper::createLink('tree', 'browse', "productID=$productID&view=story")));

        /* Set menu. */
        $this->product->setMenu($products, $product->id, $branch);

        /* Init vars. */
        $source     = '';
        $sourceNote = '';
        $pri        = 0;
        $estimate   = '';
        $title      = '';
        $spec       = '';
        $verify     = '';
        $keywords   = '';
        $mailto     = '';
        $color      = '';

        if($storyID > 0)
        {
            $story      = $this->story->getByID($storyID);
            $planID     = $story->plan;
            $source     = $story->source;
            $sourceNote = $story->sourceNote;
            $color      = $story->color;
            $pri        = $story->pri;
            $productID  = $story->product;
            $moduleID   = $story->module;
            $estimate   = $story->estimate;
            $title      = $story->title;
            $spec       = htmlspecialchars($story->spec);
            $verify     = htmlspecialchars($story->verify);
            $keywords   = $story->keywords;
            $mailto     = $story->mailto;
        }

        if($bugID > 0)
        {
            $oldBug    = $this->loadModel('bug')->getById($bugID);
            $productID = $oldBug->product;
            $source    = 'bug';
            $title     = $oldBug->title;
            $keywords  = $oldBug->keywords;
            $spec      = $oldBug->steps;
            $pri       = $oldBug->pri;
            if(strpos($oldBug->mailto, $oldBug->openedBy) === false)
            {
                $mailto = $oldBug->mailto . $oldBug->openedBy . ',';
            }
            else
            {
                $mailto = $oldBug->mailto;
            }
        }

        //3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”
        $customStoryCollectPool = $this->dao->select('value')->from(TABLE_CONFIG)->where('`key`')->eq('customStoryCollectPool')->fetch('value');
        $customProducts = $this->dao->select('id, name')->from(TABLE_PRODUCT)
            ->where('id')->in($customStoryCollectPool)
            ->andWhere('deleted')->eq('0')
            ->fetchPairs();
        $this->view->customStoryCollectPool = explode(',', $customStoryCollectPool);
        $this->view->customProducts = $customProducts + array('' => '');

        if($todoID > 0)
        {
            $todo   = $this->loadModel('todo')->getById($todoID);
            $source = 'todo';
            $title  = $todo->name;
            $spec   = $todo->desc;
            $pri    = $todo->pri;
        }

        /* Replace the value of story that needs to be replaced with the value of the object that is transferred to story. */
        if(isset($fromObject))
        {
            if(isset($this->config->story->fromObjects[$fromObjectIDKey]['source']))
            {
                $sourceField = $this->config->story->fromObjects[$fromObjectIDKey]['source'];
                $sourceUser  = $this->loadModel('user')->getById($fromObject->{$sourceField});
                $source      = $sourceUser->role;
                $sourceNote  = $sourceUser->realname;
            }
            else
            {
                $source      = $fromObjectName;
                $sourceNote  = $fromObjectID;
            }

            foreach($this->config->story->fromObjects[$fromObjectIDKey]['fields'] as $storyField => $fromObjectField)
            {
                $$storyField = $fromObject->{$fromObjectField};
            }
        }

        /* Set Custom*/
        foreach(explode(',', $this->config->story->list->customCreateFields) as $field) $customFields[$field] = $this->lang->story->$field;
        $this->view->customFields = $customFields;
        $this->view->showFields   = $this->config->story->custom->createFields;

        $this->view->title            = $product->name . $this->lang->colon . $this->lang->story->create;
        $this->view->position[]       = html::a($this->createLink('product', 'browse', "product=$productID&branch=$branch"), $product->name);
        $this->view->position[]       = $this->lang->story->common;
        $this->view->position[]       = $this->lang->story->create;
        $this->view->products         = $products;
        $this->view->users            = $users;
        $this->view->moduleID         = $moduleID;
        $this->view->moduleOptionMenu = $moduleOptionMenu;
        $this->view->plans            = $this->loadModel('productplan')->getPairs($productID, $branch, 'unexpired');
        $this->view->planID           = $planID;
        $this->view->source           = $source;
        $this->view->sourceNote       = $sourceNote;
        $this->view->color            = $color;
        $this->view->pri              = $pri;
        $this->view->branch           = $branch;
        $this->view->branches         = $product->type != 'normal' ? $this->loadModel('branch')->getPairs($productID) : array();
        $this->view->productID        = $productID;
        $this->view->product          = $product;
        $this->view->projectID        = $projectID;
        $this->view->estimate         = $estimate;
        $this->view->storyTitle       = $title;
        $this->view->spec             = $spec;
        $this->view->verify           = $verify;
        $this->view->keywords         = $keywords;
        $this->view->mailto           = $mailto;
        $this->view->needReview       = ($this->app->user->account == $product->PO || $projectID > 0 || $this->config->story->needReview == 0) ? "checked='checked'" : "";

        $this->display();
    }
}