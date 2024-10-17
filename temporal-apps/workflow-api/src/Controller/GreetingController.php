<?php

namespace App\Controller;

use App\Workflow\Library\WorkflowClient;
use App\Workflow\Service\Workflow\SimpleActivity\GreetingWorkflowFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/greeting', name: 'api_simple_activity_')]
class GreetingController extends AbstractController
{
    #[Route(
        '/workflows',
        name: 'start_workflow',
        methods: [Request::METHOD_POST],
    )]
    public function startWorkflow(Request $request): JsonResponse
    {
        $jsonArguments = $request->getPayload()->all();
        $workflowArguments = $jsonArguments["args"] ?? [];
        // TODO: validate the input data here

        $workflowExecution = GreetingWorkflowFacade::startWorkflow(...$workflowArguments);

        return $this->json([
            'workflow' => $workflowExecution->getID(),
            'run' => $workflowExecution->getRunID(),
        ]);
    }

    #[Route(
        '/workflows/{workflowId}/{runId}/events',
        name: 'get_workflow_events',
        methods: [Request::METHOD_GET]
    )]
    public function getEvents(WorkflowClient $workflowClient, string $workflowId, string $runId): JsonResponse
    {
        return $this->json([
            'events' => $workflowClient->getWorkflowEvents($workflowId, $runId),
        ]);
    }
}
