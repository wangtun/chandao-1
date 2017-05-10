<?php
helper::import('H:\zentao\chandao\module\project\model.php');
class extprojectModel extends projectModel 
{
/**
 * Build task search form.
 *
 * @param  int    $projectID
 * @param  array  $projects
 * @param  int    $queryID
 * @param  string $actionURL
 * @access public
 * @return void
 */
public function buildTaskSearchForm($projectID, $projects, $queryID, $actionURL)
{
    $this->config->project->search['actionURL'] = $actionURL;
    $this->config->project->search['queryID']   = $queryID;
    //搜索框实现多项目下任务的搜索16-20
    //$this->config->project->search['params']['project']['values'] = array(''=>'', $projectID => $projects[$projectID], 'all' => $this->lang->project->allProject);
    $this->config->project->search['params']['project']['values'] = array(''=>'', 'all' => $this->lang->project->allProject);
    $this->config->project->search['params']['project']['values'] = $this->config->project->search['params']['project']['values'] +  $projects;

    $this->config->project->search['params']['module']['values']  = $this->loadModel('tree')->getTaskOptionMenu($projectID, $startModuleID = 0);

    $this->loadModel('search')->setSearchParams($this->config->project->search);
}
/**
 * The control file of project module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2012 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     business(商业软件) 
 * @author      Yangyang Shi <shiyangyang@cnezsoft.com>
 * @package     project 
 * @version     $Id$
 * @link        http://www.zentao.net
 */
public function createRelationOfTasks($projectID)
{
    $this->loadExtension('gantt')->createRelationOfTasks($projectID);
}

public function editRelationOfTasks($projectID)
{
    $this->loadExtension('gantt')->editRelationOfTasks($projectID);
}

public function getRelationsOfTasks($projectID)
{
    return $this->loadExtension('gantt')->getRelationsOfTasks($projectID);
}

public function getDataForGantt($projectID, $type)
{
    return $this->loadExtension('gantt')->getDataForGantt($projectID, $type);
}

public function deleteRelation($id)
{
    $this->loadExtension('gantt')->deleteRelation($id);
}
//**//
}