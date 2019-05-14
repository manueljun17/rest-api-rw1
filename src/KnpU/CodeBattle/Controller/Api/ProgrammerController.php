<?php

namespace KnpU\CodeBattle\Controller\Api;

use KnpU\CodeBattle\Controller\BaseController;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use KnpU\CodeBattle\Model\Programmer;

class ProgrammerController extends BaseController
{
    protected function addRoutes(ControllerCollection $controllers)
    {
        $controllers->post('/api/programmers', array($this, 'newAction'));
        
        $controllers->get('/api/programmers/{nickname}', array($this, 'showAction'))
        ->bind('api_programmers_show');

        $controllers->get('/api/programmers', array($this, 'listAction'));

        $controllers->put('/api/programmers/{nickname}', array($this, 'updateAction'));
    }

    public function newAction(Request $request)
	{
	    $programmer = new Programmer();
	    $this->handleRequest($request, $programmer);
	    $this->save($programmer);

	    $data = $this->serializeProgrammer($programmer);
	    $response = new JsonResponse($data, 201);
	    $programmerUrl = $this->generateUrl(
	        'api_programmers_show',
	        ['nickname' => $programmer->nickname]
	    );
	    $response->headers->set('Location', $programmerUrl);

	    return $response;
	}	 	

	public function showAction($nickname)
	{
		$programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);
	    
		if (!$programmer) {
	        $this->throw404('Crap! This programmer has deserted! We\'ll send a search party');
	    }

    	$data = $this->serializeProgrammer($programmer);

	    $response = new Response(json_encode($data), 200);
	    $response->headers->set('Content-Type', 'application/json');

	    return $response;
	}

	public function listAction()
	{
	    $programmers = $this->getProgrammerRepository()->findAll();
		$data = array('programmers' => array());
		foreach ($programmers as $programmer) {
		    $data['programmers'][] = $this->serializeProgrammer($programmer);
		}

		$response = new Response(json_encode($data), 200);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function updateAction($nickname, Request $request)
	{
	    $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);

	    if (!$programmer) {
	        $this->throw404();
	    }

	    // throw new \Exception('This is scary!');
	    
	    $this->handleRequest($request, $programmer);
    	$this->save($programmer);

	    $data = $this->serializeProgrammer($programmer);

	    $response = new JsonResponse($data, 200);

	    return $response;
	}	 	

	private function serializeProgrammer(Programmer $programmer)
	{
	    return array(
	        'nickname' => $programmer->nickname,
	        'avatarNumber' => $programmer->avatarNumber,
	        'powerLevel' => $programmer->powerLevel,
	        'tagLine' => $programmer->tagLine,
	    );
	}

	private function handleRequest(Request $request, Programmer $programmer)
	{
	    $data = json_decode($request->getContent(), true);
	    $isNew = !$programmer->id;

	    if ($data === null) {
	        throw new \Exception(sprintf('Invalid JSON: '.$request->getContent()));
	    }

	    // determine which properties should be changeable on this request
	    $apiProperties = array('avatarNumber', 'tagLine');
	    if ($isNew) {
	        $apiProperties[] = 'nickname';
	    }
	    // update the properties
	    foreach ($apiProperties as $property) {
	        $val = isset($data[$property]) ? $data[$property] : null;
	        $programmer->$property = $val;
	    }

	    $programmer->userId = $this->findUserByUsername('weaverryan')->id;
	}
}
