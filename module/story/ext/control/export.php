<?php
helper::import(dirname(dirname(dirname(__FILE__))) . "/control.php");
class mystory extends story
{
    /**
     * get data to export
     *
     * @param  int $productID
     * @param  string $orderBy
     * @access public
     * @return void
     */
    public function export($productID, $orderBy, $projectID=0, $type='', $param=0)
    {
        /* format the fields of every story in order to export data. */
        if($_POST)
        {
            $this->loadModel('file');
            $this->loadModel('branch');
            $storyLang   = $this->lang->story;
            $storyConfig = $this->config->story;

            /* Create field lists. */
            $fields = $this->post->exportFields ? $this->post->exportFields : explode(',', $storyConfig->list->exportFields);
            foreach($fields as $key => $fieldName)
            {
                $fieldName = trim($fieldName);
                $fields[$fieldName] = isset($storyLang->$fieldName) ? $storyLang->$fieldName : $fieldName;
                unset($fields[$key]);
            }

            /* Get stories. */
            $stories = array();
            if($this->session->storyOnlyCondition)
            {
                if ($type == 'toReleased')
                {
                    $limitDate = date("Y-m-d",strtotime("+10 day"));
                    $stories = $this->dao->select('*')->from(TABLE_STORY)
                        ->where('deleted')->eq(0)
                        ->andWhere('specialPlan')->lt($limitDate)->andWhere('stage')->notin('released,wait,planned')->andWhere('specialPlan')->ne('0000-00-00')
                        ->andWhere("IF (`status` = 'closed',closedReason = 'done',2>1)")
                        ->orderBy($orderBy)
                        ->fetchAll('id');
                }
                elseif($type == 'released')
                {
                    $storyIDs = $this->dao->select('stories')->from(TABLE_RELEASE)->where('id')->eq($param)->fetch('stories');
                    $stories = $this->dao->select('*')->from(TABLE_STORY)
                        ->where('deleted')->eq(0)
                        ->andWhere('id')->in(trim($storyIDs, ','))
                        ->orderBy($orderBy)
                        ->fetchAll('id');
                }
                else
                {
                    $stories = $this->dao->select('*')->from(TABLE_STORY)->where($this->session->storyQueryCondition)
                        ->beginIF($this->post->exportType == 'selected')->andWhere('id')->in($this->cookie->checkedItem)->fi()
                        ->orderBy($orderBy)->fetchAll('id');
                }

            }
            else
            {
                $stmt = $this->dbh->query($this->session->storyQueryCondition . ($this->post->exportType == 'selected' ? " AND t2.id IN({$this->cookie->checkedItem})" : '') . " ORDER BY " . strtr($orderBy, '_', ' '));
                while($row = $stmt->fetch()) $stories[$row->id] = $row;
            }

            /* Get users, products and projects. */
            $users    = $this->loadModel('user')->getPairs('noletter');
            $products = $this->loadModel('product')->getPairs('nocode');

            /* Get related objects id lists. */
            $relatedProductIdList = array();
            $relatedModuleIdList  = array();
            $relatedStoryIdList   = array();
            $relatedPlanIdList    = array();
            //2670 需求导出列表中，需要增加需求的期望发版时间字段
            
            $relatedCustomPlanIdList    = array();
            $relatedBranchIdList  = array();
            $relatedStoryIDs      = array();

            foreach($stories as $story)
            {
                $relatedProductIdList[$story->product] = $story->product;
                $relatedModuleIdList[$story->module]   = $story->module;
                $relatedPlanIdList[$story->plan]       = $story->plan;
                //2670 需求导出列表中，需要增加需求的期望发版时间字段
                $relatedCustomPlanIdList[$story->customPlan]       = $story->customPlan;
                
                $relatedBranchIdList[$story->branch]   = $story->branch;
                $relatedStoryIDs[$story->id]           = $story->id;

                /* Process related stories. */
                $relatedStories = $story->childStories . ',' . $story->linkStories . ',' . $story->duplicateStory;
                $relatedStories = explode(',', $relatedStories);
                foreach($relatedStories as $storyID)
                {
                    if($storyID) $relatedStoryIdList[$storyID] = trim($storyID);
                }
            }

            $storyTasks = $this->loadModel('task')->getStoryTaskCounts($relatedStoryIDs);
            $storyBugs  = $this->loadModel('bug')->getStoryBugCounts($relatedStoryIDs);
            $storyCases = $this->loadModel('testcase')->getStoryCaseCounts($relatedStoryIDs);

            /* Get related objects title or names. */
            $productsType   = $this->dao->select('id, type')->from(TABLE_PRODUCT)->where('id')->in($relatedProductIdList)->fetchPairs();
            $relatedModules = $this->dao->select('id, name')->from(TABLE_MODULE)->where('id')->in($relatedModuleIdList)->fetchPairs();
            $relatedPlans   = $this->dao->select('id, title')->from(TABLE_PRODUCTPLAN)->where('id')->in(join(',', $relatedPlanIdList))->fetchPairs();
            //2670 需求导出列表中，需要增加需求的期望发版时间字段
            $relatedCustomPlans   = $this->dao->select('id, title')->from(TABLE_PRODUCTPLAN)->where('id')->in(join(',', $relatedCustomPlanIdList))->fetchPairs();
            
            $relatedStories = $this->dao->select('id,title,openedBy')->from(TABLE_STORY) ->where('id')->in($relatedStoryIdList)->fetchAll('id');
            foreach ($relatedStories as $relatedStory)
            {
                $relatedStories[$relatedStory->id] = '#' . $relatedStory->id . ':' . $relatedStory->title . ':' . $users[$relatedStory->openedBy];
            }
            $relatedFiles   = $this->dao->select('id, objectID, pathname, title')->from(TABLE_FILE)->where('objectType')->eq('story')->andWhere('objectID')->in(@array_keys($stories))->andWhere('extra')->ne('editor')->fetchGroup('objectID');
            $relatedSpecs   = $this->dao->select('*')->from(TABLE_STORYSPEC)->where('`story`')->in(@array_keys($stories))->orderBy('version desc')->fetchGroup('story');
            $relatedBranch  = array('0' => $this->lang->branch->all) + $this->dao->select('id, name')->from(TABLE_BRANCH)->where('id')->in($relatedBranchIdList)->fetchPairs();

            foreach($stories as $story)
            {
                $story->spec   = '';
                $story->verify = '';
                if(isset($relatedSpecs[$story->id]))
                {
                    $storySpec     = $relatedSpecs[$story->id][0];
                    $story->title  = $storySpec->title;
                    $story->spec   = $storySpec->spec;
                    $story->verify = $storySpec->verify;
                }

                if($this->post->fileType == 'csv')
                {
                    $story->spec = htmlspecialchars_decode($story->spec);
                    $story->spec = str_replace("<br />", "\n", $story->spec);
                    $story->spec = str_replace('"', '""', $story->spec);
                    $story->spec = str_replace('&nbsp;', ' ', $story->spec);

                    $story->verify = htmlspecialchars_decode($story->verify);
                    $story->verify = str_replace("<br />", "\n", $story->verify);
                    $story->verify = str_replace('"', '""', $story->verify);
                    $story->verify = str_replace('&nbsp;', ' ', $story->verify);
                }
                /* fill some field with useful value. */
                if(isset($products[$story->product]))       $story->product = $products[$story->product] . "(#$story->product)";
                if(isset($relatedModules[$story->module]))  $story->module  = $relatedModules[$story->module] . "(#$story->module)";
                if(isset($relatedBranch[$story->branch]))   $story->branch  = $relatedBranch[$story->branch] . "(#$story->branch)";
                if(isset($story->plan))
                {
                    $plans = '';
                    foreach(explode(',', $story->plan) as $planID)
                    {
                        if(empty($planID)) continue;
                        if(isset($relatedPlans[$planID]))$plans .= $relatedPlans[$planID] . "(#$planID) ";
                    }
                    $story->plan = $plans;
                }

                //2670 需求导出列表中，需要增加需求的期望发版时间字段
                if(isset($story->customPlan))
                {
                    $customPlans = '';
                    foreach(explode(',', $story->customPlan) as $planID)
                    {
                        if(empty($planID)) continue;
                        if(isset($relatedCustomPlans[$planID]))$customPlans .= $relatedCustomPlans[$planID] . "(#$planID) ";
                    }
                    $story->customPlan = $customPlans;
                }

                if(isset($relatedStories[$story->duplicateStory])) $story->duplicateStory = $relatedStories[$story->duplicateStory];

                if(isset($storyLang->priList[$story->pri]))             $story->pri          = $storyLang->priList[$story->pri];
                if(isset($storyLang->statusList[$story->status]))       $story->status       = $storyLang->statusList[$story->status];
                if(isset($storyLang->stageList[$story->stage]))         $story->stage        = $storyLang->stageList[$story->stage];
                if(isset($storyLang->reasonList[$story->closedReason])) $story->closedReason = $storyLang->reasonList[$story->closedReason];
                if(isset($storyLang->sourceList[$story->source]))       $story->source       = $storyLang->sourceList[$story->source];
                if(isset($storyLang->sourceList[$story->sourceNote]))   $story->sourceNote   = $storyLang->sourceList[$story->sourceNote];

                if(isset($users[$story->openedBy]))     $story->openedBy     = $users[$story->openedBy];
                if(isset($users[$story->assignedTo]))   $story->assignedTo   = $users[$story->assignedTo];
                if(isset($users[$story->lastEditedBy])) $story->lastEditedBy = $users[$story->lastEditedBy];
                if(isset($users[$story->closedBy]))     $story->closedBy     = $users[$story->closedBy];

                if(isset($storyTasks[$story->id]))     $story->taskCountAB = $storyTasks[$story->id];
                if(isset($storyBugs[$story->id]))      $story->bugCountAB  = $storyBugs[$story->id];
                if(isset($storyCases[$story->id]))     $story->caseCountAB = $storyCases[$story->id];

                $story->openedDate     = substr($story->openedDate, 0, 10);
                $story->assignedDate   = substr($story->assignedDate, 0, 10);
                $story->lastEditedDate = substr($story->lastEditedDate, 0, 10);
                $story->closedDate     = substr($story->closedDate, 0, 10);

                if($story->linkStories)
                {
                    $tmpLinkStories = array();
                    $linkStoriesIdList = explode(',', $story->linkStories);
                    foreach($linkStoriesIdList as $linkStoryID)
                    {
                        $linkStoryID = trim($linkStoryID);
                        $tmpLinkStories[] = isset($relatedStories[$linkStoryID]) ? $relatedStories[$linkStoryID] : $linkStoryID;

                    }
                    $story->linkStories = join("; \n", $tmpLinkStories);
                }

                if($story->childStories)
                {
                    $tmpChildStories = array();
                    $childStoriesIdList = explode(',', $story->childStories);
                    foreach($childStoriesIdList as $childStoryID)
                    {
                        $childStoryID = trim($childStoryID);
                        $tmpChildStories[] = isset($relatedStories[$childStoryID]) ? $relatedStories[$childStoryID] : $childStoryID;

                    }
                    $story->childStories = join("; \n", $tmpChildStories);
                }

                /* Set related files. */
                $story->files = '';
                if(isset($relatedFiles[$story->id]))
                {
                    foreach($relatedFiles[$story->id] as $file)
                    {
                        $fileURL = common::getSysURL() . $this->file->webPath . $this->file->getRealPathName($file->pathname);
                        $story->files .= html::a($fileURL, $file->title, '_blank') . '<br />';
                    }
                }

                $story->mailto = trim(trim($story->mailto), ',');
                $mailtos = explode(',', $story->mailto);
                $story->mailto = '';
                foreach($mailtos as $mailto)
                {
                    $mailto = trim($mailto);
                    if(isset($users[$mailto])) $story->mailto .= $users[$mailto] . ',';
                }

                $story->reviewedBy = trim(trim($story->reviewedBy), ',');
                $reviewedBys = explode(',', $story->reviewedBy);
                $story->reviewedBy = '';
                foreach($reviewedBys as $reviewedBy)
                {
                    $reviewedBy = trim($reviewedBy);
                    if(isset($users[$reviewedBy])) $story->reviewedBy .= $users[$reviewedBy] . ',';
                }

            }

            if(!(in_array('platform', $productsType) or in_array('branch', $productsType))) unset($fields['branch']);// If products's type are normal, unset branch field.

            $this->post->set('fields', $fields);
            $this->post->set('rows', $stories);
            $this->post->set('kind', 'story');
            $this->fetch('file', 'export2' . $this->post->fileType, $_POST);
        }

        $this->view->allExportFields = $this->config->story->list->exportFields;
        $this->view->customExport    = true;
        $this->display();
    }
}
