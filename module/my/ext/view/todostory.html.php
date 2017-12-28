<?php
/**
 * The story view file of dashboard module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     dashboard
 * @version     $Id: story.html.php 5116 2013-07-12 06:37:48Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include '../../../common/view/header.html.php';?>
<?php include '../../../common/view/tablesorter.html.php';?>
<div id='featurebar'>
  <nav class='nav'>
    <?php
    echo "<li id='toTestStoryTab'>" . html::a(inlink('todoStory', "type=toTestStory"),  $lang->my->todoStoryMenu->toTestStory) . "</li>";
    echo "<li id='toReleaseStoryTab'>"   . html::a(inlink('todoStory', "type=toReleaseStory"),    $lang->my->todoStoryMenu->toReleaseStory)   . "</li>";
    ?>
  </nav>
</div>
<form method='post' id='myStoryForm'>
<table class='table table-condensed table-hover table-striped tablesorter table-fixed table-selectable'>
  <?php $vars = "type=$type&orderBy=%s&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID"; ?>
  <thead>
    <tr class='text-center'>
      <th class='w-id {sorter:false}'>    <?php common::printOrderLink('id',           $orderBy, $vars, $lang->idAB);?></th>
      <th class='w-pri {sorter:false}'>   <?php common::printOrderLink('pri',          $orderBy, $vars, $lang->priAB);?></th>
      <th class='w-200px {sorter:false}'> <?php common::printOrderLink('productTitle', $orderBy, $vars, $lang->story->product);?></th>
      <th class="{sorter:false}">                 <?php common::printOrderLink('title',        $orderBy, $vars, $lang->story->title);?></th>
      <th class='w-date {sorter:false}'> <?php common::printOrderLink('testDate',         $orderBy, $vars, $lang->story->testDate);?></th>
      <th class='w-100px {sorter:false}'> <?php common::printOrderLink('specialPlan',         $orderBy, $vars, $lang->story->specialPlan);?></th>
        <th class='w-status {sorter:false}'><?php common::printOrderLink('status',       $orderBy, $vars, $lang->statusAB);?></th>
        <th class='w-100px {sorter:false}'> <?php common::printOrderLink('stage',        $orderBy, $vars, $lang->story->stageAB);?></th>
      <th class='w-user {sorter:false}'>  <?php common::printOrderLink('openedBy',     $orderBy, $vars, $lang->openedByAB);?></th>
      <th class='w-user {sorter:false}'>  <?php common::printOrderLink('assignedTo',     $orderBy, $vars, $lang->story->assignedTo);?></th>
      <th class='w-30px'>  <?php echo $lang->story->taskCountAB;?></th>
      <th class='w-110px {sorter:false}'><?php echo $lang->actions;?></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $canBatchEdit  = common::hasPriv('story', 'batchEdit');
    $canBatchClose = (common::hasPriv('story', 'batchClose') && strtolower($type) != 'closedbyme');
    ?>
    <?php foreach($stories as $key => $story):?>
    <?php
        if ($story->projectID)
        {
            $storyLink = $this->createLink('story', 'view', "id=$story->id&version=$story->version&from=project&param=$story->projectID");
        }else
        {
            $storyLink = $this->createLink('story', 'view', "id=$story->id");
        }
    ?>

    <tr class='text-center'>
      <td class='cell-id'>
        <?php if($canBatchEdit or $canBatchClose):?>
        <input type='checkbox' name='storyIDList[<?php echo $story->id;?>]' value='<?php echo $story->id;?>' /> 
        <?php endif;?>
        <?php echo html::a($storyLink, sprintf('%03d', $story->id));?>
      </td>
      <td><span class='<?php echo 'pri' . zget($lang->story->priList, $story->pri, $story->pri);?>'><?php echo zget($lang->story->priList, $story->pri, $story->pri);?></span></td>
      <td><?php echo $story->productTitle;?></td>
      <td class='text-left nobr'><?php echo html::a($storyLink, $story->title, null, "style='color: $story->color'");?></td>
      <td class="<?php if($story->testWaring and $type =='toTestStory') echo 'delayed';?>"><?php echo $story->testDate;?></td>
      <td class="<?php if($story->releaseWaring and $type =='toReleaseStory') echo 'delayed';?>"><?php echo $story->specialPlan;?></td>
        <td class='story-<?php echo $story->status;?>'><?php echo $lang->story->statusList[$story->status];?></td>
        <td><?php echo $lang->story->stageList[$story->stage];?></td>
      <td><?php echo $users[$story->openedBy];?></td>
      <td><?php echo $users[$story->assignedTo];?></td>
      <td><?php  $tasksLink = helper::createLink('story', 'tasks', "storyID=$story->id");
          $storyTasks[$story->id] > 0 ? print(html::a($tasksLink, $storyTasks[$story->id], '', 'class="iframe"')) : print(0);
          ?></td>

      <td class='text-right'>
        <?php
        common::printIcon('story', 'change',     "storyID=$story->id", $story, 'list', 'random');
        common::printIcon('story', 'review',     "storyID=$story->id", $story, 'list', 'search');
        common::printIcon('story', 'close',      "storyID=$story->id", $story, 'list', 'off', '', 'iframe', true);
        common::printIcon('story', 'edit',       "storyID=$story->id", $story, 'list', 'pencil');
        common::printIcon('story', 'createCase', "productID=$story->product&moduleID=0&from=&param=0&storyID=$story->id", '', 'list', 'sitemap');
        ?>
      </td>
    </tr>
    <?php endforeach;?>
  </tbody>
  <tfoot>
  <tr>
    <td colspan='12'>
      <?php if(count($stories)):?>
      <div class='table-actions clearfix'>
        <?php echo html::selectButton();?>
        <div class='btn-group dropup'>
          <?php
          $actionLink = $this->createLink('story', 'batchEdit');
          $misc       = $canBatchEdit ? "onclick=\"setFormAction('$actionLink')\"" : "disabled='disabled'";
          echo html::commonButton($lang->edit, $misc);
          ?>
          <button type='button' class='btn dropdown-toggle' data-toggle='dropdown'><span class='caret'></span></button>
          <ul class='dropdown-menu'>
            <?php
            $class = "class='disabled'";
            $actionLink = $this->createLink('story', 'batchClose');
            $misc = ($canBatchClose and $type != 'closedBy') ? "onclick=\"setFormAction('$actionLink')\"" : $class;
            if($misc) echo "<li>" . html::a('javascript:;', $lang->close, '', $misc) . "</li>";

            if(common::hasPriv('story', 'batchReview'))
            {
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->review, '', "id='reviewItem'");
                echo "<ul class='dropdown-menu'>";
                unset($lang->story->reviewResultList['']);
                unset($lang->story->reviewResultList['revert']);
                foreach($lang->story->reviewResultList as $key => $result)
                {
                    $actionLink = $this->createLink('story', 'batchReview', "result=$key");
                    if($key == 'reject')
                    {
                        echo "<li class='dropdown-submenu'>";
                        echo html::a('#', $result, '', "id='rejectItem'");
                        echo "<ul class='dropdown-menu'>";
                        unset($lang->story->reasonList['']);
                        unset($lang->story->reasonList['subdivided']);
                        unset($lang->story->reasonList['duplicate']);

                        foreach($lang->story->reasonList as $key => $reason)
                        {
                            $actionLink = $this->createLink('story', 'batchReview', "result=reject&reason=$key");
                            echo "<li>";
                            echo html::a('#', $reason, '', "onclick=\"setFormAction('$actionLink','hiddenwin')\"");
                            echo "</li>";
                        }
                        echo '</ul></li>';
                    }
                    else
                    {
                      echo '<li>' . html::a('#', $result, '', "onclick=\"setFormAction('$actionLink','hiddenwin')\"") . '</li>';
                    }
                }
                echo '</ul></li>';
            }
            else
            {
                echo '<li>' . html::a('javascript:;', $lang->story->review,  '', $class) . '</li>';
            }

            if(common::hasPriv('story', 'batchAssignTo'))
            {
                  $withSearch = count($users) > 10;
                  $actionLink = $this->createLink('story', 'batchAssignTo');
                  echo html::select('assignedTo', $users, '', 'class="hidden"');
                  echo "<li class='dropdown-submenu'>";
                  echo html::a('javascript::', $lang->story->assignedTo, '', 'id="assignItem"');
                  echo "<div class='dropdown-menu" . ($withSearch ? ' with-search':'') . "'>";
                  echo '<ul class="dropdown-list">';
                  foreach ($users as $key => $value)
                  {
                      if(empty($key) or $key == 'closed') continue;
                      echo "<li class='option' data-key='$key'>" . html::a("javascript:$(\".table-actions #assignedTo\").val(\"$key\");setFormAction(\"$actionLink\")", $value, '', '') . '</li>';
                  }
                  echo "</ul>";
                  if($withSearch) echo "<div class='menu-search'><div class='input-group input-group-sm'><input type='text' class='form-control' placeholder=''><span class='input-group-addon'><i class='icon-search'></i></span></div></div>";
                  echo "</div></li>";
            }
            else
            {
                echo '<li>' . html::a('javascript:;', $lang->story->assignedTo, '', $class) . '</li>';
            }
            ?>
          </ul>
        </div>
      </div>
      <?php endif;?>
      <?php $pager->show();?>
    </td>
  </tr>
  </tfoot>
</table>
</form>
<script language='javascript'>$("#<?php echo $type;?>Tab").addClass('active');</script>
<?php include '../../../common/view/footer.html.php';?>