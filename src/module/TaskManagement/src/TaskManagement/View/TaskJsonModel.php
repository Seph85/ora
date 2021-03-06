<?php
namespace TaskManagement\View;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use Ora\ReadModel\Task;
use Ora\ReadModel\Estimation;
use Ora\ReadModel\TaskMember;
use Zend\Mvc\Controller\Plugin\Url;
use Ora\User\User;

class TaskJsonModel extends JsonModel
{
	/**
	 * 
	 * @var Url
	 */
	private $url;
	/**
	 * 
	 * @var User
	 */
	private $user;
	
	public function __construct(Url $url, User $user) {
		$this->url = $url;
		$this->user = $user;
	}
	
	public function serialize()
	{
		$resource = $this->getVariable('resource');

        if(is_array($resource)) {
			$representation['tasks'] = [];
			foreach ($resource as $r) {
				$representation['tasks'][] = $this->serializeOne($r);
				if($this->isAllowed('create')) {
					$representation['_links']['create'] = $this->url->fromRoute('tasks');
				}
			}
		} else {
			$representation = $this->serializeOne($resource);
		}
		return Json::encode($representation);
	}

	private function serializeOne(Task $task) {
		$links = ['self' => $this->url->fromRoute('tasks', ['id' => $task->getId()])];
		if($this->isAllowed('edit', $task)) {
			$links['edit'] = $this->url->fromRoute('tasks', ['id' => $task->getId()]); 
		}
		if($this->isAllowed('delete', $task)) {
			$links['delete'] = $this->url->fromRoute('tasks', ['id' => $task->getId()]); 
		}
		if($this->isAllowed('join', $task)) {
			$links['join'] = $this->url->fromRoute('tasks', ['id' => $task->getId(), 'controller' => 'members']); 
		}
		if($this->isAllowed('unjoin', $task)) {
			$links['unjoin'] = $this->url->fromRoute('tasks', ['id' => $task->getId(), 'controller' => 'members']); 
		}
		if($this->isAllowed('estimate', $task)) {
			$links['estimate'] = $this->url->fromRoute('tasks', ['id' => $task->getId(), 'controller' => 'estimations']); 
		}
		if($this->isAllowed('execute', $task)) {
			$links['execute'] = $this->url->fromRoute('tasks', ['id' => $task->getId(), 'controller' => 'transitions']); 
		}
		if($this->isAllowed('complete', $task)) {
			$links['complete'] = $this->url->fromRoute('tasks', ['id' => $task->getId(), 'controller' => 'transitions']); 
		}
		if($this->isAllowed('accept', $task)) {
			$links['accept'] = $this->url->fromRoute('tasks', ['id' => $task->getId(), 'controller' => 'transitions']); 
		}
		if($this->isAllowed('assignShares', $task)) {
			$links['assignShares'] = $this->url->fromRoute('tasks', ['id' => $task->getId(), 'controller' => 'shares']); 
		}
		$rv = [
			'id' => $task->getId (),
			'createdAt' => date_format($task->getCreatedAt(), 'c'),
			'status' => $task->getStatus(),
			'members' => $this->getMembersArray($task),
			'createdBy' => is_null ( $task->getCreatedBy () ) ? "" : $task->getCreatedBy ()->getFirstname () . " " . $task->getCreatedBy ()->getLastname (),
			'subject' => $task->getSubject (),
			'type' => $task->getType (),
			'_links' => $links,
		];
		
		if($task->getStatus() >= Task::STATUS_ONGOING) {
			$rv['estimation'] = $task->getEstimation();
		}

		return $rv;
	}
	
    private function getMembersArray(Task $task){
		$members = array();
		foreach ($task->getMembers() as $tm) {
			$member = $tm->getMember();
			$m = [
	            'firstname' => $member->getFirstname(),
	            'lastname' => $member->getLastname(),
				'role' => $tm->getRole(),
				'_links' => [
					'self' => $this->url->fromRoute('users', ['id' => $member->getId()]),  
				],
			];
            $m['estimation'] = $this->getEstimation($tm);
			if($this->user->getId() != $member->getId() && isset($m['estimation'])) {
				$m['estimation']['value'] = -2;
			}
			$members[] = $m;
		}
		return $members;
    }
    
    private function getEstimation(TaskMember $tm) {
    	$estimation = $tm->getEstimation();
    	return is_null($estimation->getValue()) ? null : [
			'value' => $estimation->getValue(),
    		'createdAt' => date_format($estimation->getCreatedAt(), 'c'),
    	];
    }
    
    private function isAllowed($action, Task $task = null) {
    	if(is_null($task)) {
    		return true; // placeholder
    	}
    	switch ($action) {
    		case 'edit':
    		case 'delete':
    			if($task->getStatus() != Task::STATUS_ONGOING) {
    				return false;
    			}
    			return true;
    		case 'join':
    			if($task->getStatus() != Task::STATUS_ONGOING) {
    				return false;
    			}
    			if(is_null($task->getMember($this->user))) {
    				return true;
    			}
    			return false;
    		case 'unjoin':
    			if($task->getStatus() != Task::STATUS_ONGOING) {
    				return false;
    			}
    			if(is_null($task->getMember($this->user))) {
    				return false;
    			}
    			return true;
    		case 'estimate':
    			if(!in_array($task->getStatus(), [Task::STATUS_ONGOING, Task::STATUS_COMPLETED])) {
    				return false;
    			}
    			$member = $task->getMember($this->user);
    	    	if(is_null($member)) {
    				return false;
    			}
    			return true;
    		case 'execute':
    			if(!in_array($task->getStatus(), [Task::STATUS_OPEN, Task::STATUS_COMPLETED])) {
    				return false;
    			}
    			$member = $task->getMember($this->user);
    			if(is_null($member)) {
    				return false;
    			}
    			if($member->getRole() == TaskMember::ROLE_OWNER) {
    				return true;
    			}
    			return false;
    		case 'complete':
    			if(!in_array($task->getStatus(), [Task::STATUS_ONGOING, Task::STATUS_ACCEPTED])) {
    				return false;
    			}
//     			if(is_null($task->getEstimation())) {
//     				return false;
//     			}
    			$member = $task->getMember($this->user);
    			if(is_null($member)) {
    				return false;
    			}
    			if($member->getRole() == TaskMember::ROLE_OWNER) {
    				return true;
    			}
    			return false;
    		case 'accept':
    			if($task->getStatus() != Task::STATUS_COMPLETED) {
    				return false;
    			}
    			if(is_null($task->getEstimation())) {
    				return false;
    			}
    			$member = $task->getMember($this->user);
    			if(is_null($member)) {
    				return false;
    			}
    			if($member->getRole() == TaskMember::ROLE_OWNER) {
    				return true;
    			}
    			return false;
    		case 'assignShares':
    			if($task->getStatus() != Task::STATUS_ACCEPTED) {
    				return false;
    			}
    			if(is_null($task->getMember($this->user))) {
    				return false;
    			}
    			return true;
    		default:
    			return false;
    	}
    	
    }
}
