<?php

namespace TaskManagement\Controller;

use ZendExtension\Mvc\Controller\AbstractHATEOASRestfulController;
//use Zend\View\Model\JsonModel;
use Zend\Validator\ValidatorInterface;

class TasksController extends AbstractHATEOASRestfulController
{
    protected static $collectionOptions = array ('GET','POST');
    protected static $resourceOptions = array ('DELETE','GET');
	protected $taskService;
	
	// INIZIALIZZAZIONE
	   // - Inizializzo/valorizzo $project con l'entità reale
	
	// GET - singolo perchè con un parametro di ID
    /* public function get($id)
    {
        $response = array();
        
        $this->response->setStatusCode(200);

        return new JsonModel($response);
    }
    
    // GET - prende tutto perchè senza parametri
    public function getList()
    {
       	$response = array("1"=>"CIAO TASK");
       	
       	$this->response->setStatusCode(200);
       	
        return new JsonModel($response);
    }*/
    
    /**
     * @method POST
     * @link http://oraproject/task-management/task
     * @param array $data['projectID'] Parent project ID of the new task
     * @param array $data['subject'] Task subject
     * @return \Zend\View\Model\JsonModel
     * @author Giannotti Fabio
     */
    public function create($data)
    {        
        if (!isset($data['projectID']))
        {            
            // HTTP STATUS CODE 400: Bad Request
            $this->response->setStatusCode(400);
            
            return $this->response;
        }
        
        if (!isset($data['subject']))
        {           
            // HTTP STATUS CODE 400: Bad Request
            $this->response->setStatusCode(400);
            
            return $this->response;
        }
        
        $projectID = $data['projectID'];
        
        // TODO: inserire validazione sui parametri usando zend_validator
        // - projectID: diverso da vuoto
        // - projectID: diverso da null

        /*$valid = new Zend\Validator\NotEmpty();
        if (!$valid->isValid($projectID))
        {
            // HTTP STATUS CODE 406: Not Acceptable
            $this->response->setStatusCode(406);
            
            return $this->response;
        }*/

        // TODO: inserire validazione sui parametri usando zend_validator
        // - projectID: diverso da vuoto
        // - projectID: diverso da null
        
        $subject = $data['subject'];
        // TODO: inserire validazione sui parametri usando zend_validator
        // - subject: trim()
        // - subject: lunghezza (al momento non definita)
        // - subject: diverso da vuoto
        
        
	    // TODO: recuperare il project entity dalla variabile definita nel 
	    // controller e controllare che sia un'entità valida
	    //$project = $this->projectService->findProject($projectID);
	    $project = "PARENT_PROJECT_INVENTATO_TODO";
       	$this->getTaskService()->createNewTask($projectID, $subject);
        
        // HTTP STATUS CODE 201: Created
    	$this->response->setStatusCode(201);
    	
    	return $this->response;
    }
    
        
    
    
    /*
    // PUT
    public function update($id, $data)
    {   	
      	$response = array();

        return new JsonModel($response);
    }
    
    // DELETE - singolo perchè definiamo un ID
    public function delete($id)
    {
        $response = array();

        return new JsonModel($response);
    }
    */
    
    protected function getCollectionOptions()
    {
        return self::$collectionOptions;
    }
    
    protected function getResourceOptions()
    {
        return self::$resourceOptions;
    }
    
    protected function getJsonModelClass()
    {
        return $this->jsonModelClass;
    }
    
    protected function getTaskService() 
    {
        if (!isset($this->taskService)) 
        {
            $serviceLocator = $this->getServiceLocator();
            $this->taskService = $serviceLocator->get('TaskManagement\TaskService');
        }
        
        return $this->taskService;
    }
}