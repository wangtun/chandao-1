<?php
/**
* get info of task
*
* @access public
* @return array
*/
public function taskSummary($data)
{
	$info = array();
    $echartData = array();
    $result = array();

    $begin = $data->begin;
    $end = $data->end;
    $projects = $data->project;
    asort($projects);
    $projects = trim(implode(',', $projects), ',');

    $taskSumSql = "SELECT `project`,COUNT(`id`) AS taskSum FROM zt_task WHERE `project` IN(" .$projects . ") AND deleted='0' AND parent = 0 GROUP BY `project`";
    $develTaskStatusSum = $this->dao->select(" `project`,`status`,COUNT( `id` ) AS taskSum")->from(TABLE_TASK)
        ->where('project')->in("$projects")
        ->andWhere('type')->in("fos,devel,sdk,web,ios,android,script")
        ->andWhere('deleted')->eq('0')
        ->andWhere('parent')->eq(0)
        ->groupBy('project,status')
        ->fetchGroup('project','status');
    $testStatusSum = $this->dao->select(" `project`,`status`,COUNT( `id` ) AS taskSum")->from(TABLE_TASK)
        ->where('project')->in("$projects")
        ->andWhere('type')->in("test")
        ->andWhere('deleted')->eq('0')
        ->andWhere('parent')->eq(0)
        ->groupBy('project,status')
        ->fetchGroup('project','status');
    $storyTaskStatusSum = $this->dao->select(" `project`,`status`,COUNT( `id` ) AS taskSum")->from(TABLE_TASK)
        ->where('project')->in("$projects")
        ->andWhere('type')->in("ra")
        ->andWhere('deleted')->eq('0')
        ->andWhere('parent')->eq(0)
        ->groupBy('project,status')
        ->fetchGroup('project','status');
    $delayedTaskSumSql = "SELECT `project`,COUNT(`id`) AS taskSum FROM zt_task WHERE `project` IN (" .$projects . ") AND curdate()>deadline AND `status` not IN ('done','closed','cancel') AND deadline != '0000-00-00' AND deleted='0' AND parent = 0 GROUP BY `project`";

    $taskSum = $this->dao->query($taskSumSql)->fetchAll();
    $delayedTaskSum = $this->dao->query($delayedTaskSumSql)->fetchAll();
    $projectInfo = $this->dao->select("id,name,code")->from(TABLE_PROJECT)->where('id')->in($projects)->fetchAll();

    $echartData['testTaskStatusCount'] = $this->taskStatusCount("'test'", $projects);
    $echartData['develTaskStatusCount'] = $this->taskStatusCount("'fos', 'devel', 'sdk', 'web', 'ios', 'android', 'script'", $projects);
    $echartData['storyTaskStatusCount'] = $this->taskStatusCount("'ra'", $projects);

    //对数据进行处理
    $taskSum = $this->transform($taskSum);
    $delayedTaskSum = $this->transform($delayedTaskSum);

    $undoneTaskCount = $this->undoneTaskCount($projects, $begin, $end);

    $undoneTaskByType = $this->dao->select('project,type,COUNT(id) as taskSum')->from(TABLE_TASK)
        ->where('project')->in($projects)
        ->andWhere('deleted')->eq('0')
        ->andWhere('parent')->eq(0)
        ->andWhere('type')->in('fos,devel,sdk,web,ios,android,script')
        ->andWhere('status')->in('wait,doing,pause')
        ->groupBy('project,type')
        ->fetchGroup('project','type');
    $finishedTasksPerDay = $this->finishedTasksPerDay($projects, $begin, $end);
    
    $projects = explode(',', $projects);
    $newDevelTaskStatusSum  = $this->taskStatusCountOfProject($projects,$develTaskStatusSum);
    $newTestStatusSum       = $this->taskStatusCountOfProject($projects,$testStatusSum);
    $newStoryTaskStatusSum  = $this->taskStatusCountOfProject($projects,$storyTaskStatusSum);

    $projectSum =count($projects);

    for($i=0;$i<$projectSum;$i++)
    {
        $info[$projects[$i]] = new stdClass();
        $info[$projects[$i]]->projectInfo = $projectInfo[$i];
        $info[$projects[$i]]->taskSum = isset($taskSum[$projects[$i]])?$taskSum[$projects[$i]]:0;

        $info[$projects[$i]]->develTaskSum = $newDevelTaskStatusSum[$projects[$i]]['undone'] + $newDevelTaskStatusSum[$projects[$i]]['doneTask'];
        $info[$projects[$i]]->newDevelTaskStatusSum = isset($newDevelTaskStatusSum[$projects[$i]])?$newDevelTaskStatusSum[$projects[$i]]:0;

        $info[$projects[$i]]->testSum = $newTestStatusSum[$projects[$i]]['undone'] + $newTestStatusSum[$projects[$i]]['doneTask'];
        $info[$projects[$i]]->newTestStatusSum = isset($newTestStatusSum[$projects[$i]])?$newTestStatusSum[$projects[$i]]:0;

        $info[$projects[$i]]->storyTaskSum = $newStoryTaskStatusSum[$projects[$i]]['undone'] + $newStoryTaskStatusSum[$projects[$i]]['doneTask'];
        $info[$projects[$i]]->newStoryTaskStatusSum = isset($newStoryTaskStatusSum[$projects[$i]])?$newStoryTaskStatusSum[$projects[$i]]:0;
        $info[$projects[$i]]->delayedTaskSum = isset($delayedTaskSum[$projects[$i]])?$delayedTaskSum[$projects[$i]]:0;
    }

    krsort($info);
    $result['info'] = $info;
    $result['echartData'] = $echartData;
    $result['undoneTaskCount'] = $undoneTaskCount;
    $result['undoneTaskByType'] = $undoneTaskByType;
    $result['finishedTasksPerDay'] = $finishedTasksPerDay;

    return $result;
}

/**
 * 获取每日完成的开发任务数
 * 
 * @param $projects
 * @param $begin
 * @param $end
 * @return array
 */
public function finishedTasksPerDay($projects, $begin, $end)
{
    $finishedTasksPerDay = $this->dao->select("project,DATE_FORMAT(finishedDate,'%Y-%m-%d') AS date, COUNT(id) AS value")->from(TABLE_TASK)
        ->where('project')->in($projects)
        ->andWhere('deleted')->eq('0')
        ->andWhere('parent')->eq(0)
        ->andWhere('type')->in('fos,devel,sdk,web,ios,android,script')
        ->groupBy('finishedDate')
        ->having("date != '0000-00-00' AND date>='$begin' AND date<='$end'")
        ->orderBy('project,finishedDate')
        ->fetchGroup('date','project');
    
    $begin = strtotime($begin);
    $end = strtotime($end);
    $dataOfFinishedTaskPerDay = array();
    $projects = explode(',', $projects);
    $dateTime = $begin;
    while ($dateTime <= $end)
    {
        $key = date('Y-m-d', $dateTime);
        $date[$key] = $key;
        foreach ($projects as $val)
        {
            $dataOfFinishedTaskPerDay[$key][$val] = isset($finishedTasksPerDay[$key][$val])?$finishedTasksPerDay[$key][$val]->value:0;
        }
        $dateTime = strtotime('+1 day',$dateTime);
    }
    
    return $dataOfFinishedTaskPerDay;
}

/**
 * 获取未完成任务的统计数据
 * 
 * @param $projects
 * @param $begin
 * @param $end
 * @return stdClass
 */
public function undoneTaskCount($projects, $begin, $end)
{
    $undoneTaskCount = $this->dao->select('*')->from(TABLE_UNDONETASKREPORT)
        ->where('project')->in($projects)
        ->andWhere('date')->between($begin,$end)
        ->fetchGroup('date','project');
    $projects = explode(',', $projects);

    foreach ($undoneTaskCount as $date => $value)
    {
        foreach ($projects as $val)
        {
            if (!isset($value[$val]))
            {
                $undoneTaskCount[$date][$val] = new stdClass();
                $undoneTaskCount[$date][$val]->project = $val;
                $undoneTaskCount[$date][$val]->date = $date;
                $undoneTaskCount[$date][$val]->undoneStoryTaskCount = 0;
                $undoneTaskCount[$date][$val]->undoneDevelTaskCount = 0;
                $undoneTaskCount[$date][$val]->undoneTestTaskCount  = 0;
            }
        }
    }

    ksort($undoneTaskCount);
    return $undoneTaskCount;
}

/**
 * Process the data
 * 
 * @param array  $arr
 * @return array
*/
public function transform($arr)
{
	$result = array();
	foreach ($arr as $value) {
		$result[$value->project] = $value->taskSum;
	}
	return $result;
}

/**
 * task status count
 *
 * @param string $type
 * @param string $projects
 * @return array
 */
public function taskStatusCount($type, $projects)
{
    $result = array();

    $taskCountSql = "SELECT COUNT(`id`) AS taskSum FROM zt_task WHERE deleted='0' AND parent = 0  AND `project` IN (" .$projects . ") AND `status`!='cancel' AND `type` IN (" . $type . ")";
    $doneTaskCountSql = "SELECT COUNT(id) AS  taskSum FROM zt_task WHERE deleted='0' AND parent = 0  AND `project` IN (" .$projects . ") AND `status`!='cancel' AND `status` IN ('done','closed') AND `type` IN (" . $type . ")";
    $taskCount = $this->dao->query($taskCountSql)->fetch();
    $doneTaskCount = $this->dao->query($doneTaskCountSql)->fetch();

    $result['taskCount'] = $taskCount->taskSum;
    $result['doneTaskCount'] = $doneTaskCount->taskSum;
    $result['undoneTaskCount'] = $taskCount->taskSum - $doneTaskCount->taskSum;
    $result['doneTaskCountPercent'] = round($doneTaskCount->taskSum/$taskCount->taskSum, 2)*100 . '%';
    $result['undoneTaskCountPercent'] = round($result['undoneTaskCount']/$taskCount->taskSum, 2)*100 . '%';
    return $result;
}

/**
 * 按任务状态对项目的任务进行统计
 *
 * @param  $projects
 * @param $data
 * @return array
 */
public function taskStatusCountOfProject ($projects, $data)
{
    $newData = array();

    foreach ($projects as $project)
    {
        $newData[$project]['wait']= isset($data[$project]['wait']->taskSum)?$data[$project]['wait']->taskSum:0;
        $newData[$project]['doing']= isset($data[$project]['doing']->taskSum)?$data[$project]['doing']->taskSum:0;
        $newData[$project]['done']= isset($data[$project]['done']->taskSum)?$data[$project]['done']->taskSum:0;
        $newData[$project]['pause']= isset($data[$project]['pause']->taskSum)?$data[$project]['pause']->taskSum:0;
        $newData[$project]['cancel']= isset($data[$project]['cancel']->taskSum)?$data[$project]['cancel']->taskSum:0;
        $newData[$project]['closed']= isset($data[$project]['closed']->taskSum)?$data[$project]['closed']->taskSum:0;
        $newData[$project]['doneTask'] = $newData[$project]['done'] + $newData[$project]['closed'];
        $newData[$project]['undone'] = $newData[$project]['wait'] + $newData[$project]['doing'] + $newData[$project]['pause'];
    }

    return $newData;
}