<?php

namespace App\Temporal\Compiler;

use App\Temporal\Attribute\WorkflowOptions;
use App\Temporal\Factory\WorkflowFactory;
use Lagdo\Symfony\Facades\AbstractFacade;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Workflow\WorkflowInterface;

use function count;

class WorkflowCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        // Process the classes that are tagged as workflow.
        $workflows = $container->findTaggedServiceIds('temporal.service.workflow');
        foreach($workflows as $workflow => $_)
        {
            $workflowClass = new ReflectionClass($workflow);
            if(!$workflowClass->isSubclassOf(AbstractFacade::class))
            {
                continue;
            }
    
            // A facade doesn't need to be registered in the service container.
            $container->removeDefinition($workflow);

            if(($workflowInterface = $this->getInterfaceFromFacade($workflowClass)) !== null)
            {
                // The class is a facade. Register a workflow stub.
                $this->registerWorkflowStub($container, $workflowInterface);
            }
        }
    }

    /**
     * @param ReflectionClass $workflowClass
     *
     * @return ReflectionClass|null
     */
    private function getInterfaceFromFacade(ReflectionClass $workflowClass): ?ReflectionClass
    {
        try
        {
            // Call the protected "getServiceIdentifier()" method of the facade to get the service id.
            $serviceIdentifierMethod = $workflowClass->getMethod('getServiceIdentifier');
            $serviceIdentifierMethod->setAccessible(true);
            $workflowInterfaceName = $serviceIdentifierMethod->invoke(null);
            $workflowInterface = new ReflectionClass($workflowInterfaceName);

            return count($workflowInterface->getAttributes(WorkflowInterface::class)) === 0 ?
                null : $workflowInterface;
        }
        catch(ReflectionException $_)
        {
            return null;
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param ReflectionClass $workflowInterface
     *
     * @return void
     */
    private function registerWorkflowStub(ContainerBuilder $container, ReflectionClass $workflowInterface): void
    {
        $workflow = $workflowInterface->getName();
        // The key for the options in the DI container
        $optionsKey = $this->getOptionsKey($container, $workflowInterface);
        $definition = (new Definition($workflow))
            ->setFactory(WorkflowFactory::class . '::workflowStub')
            ->setArgument('$workflow', $workflow)
            ->setArgument('$options', new Reference($optionsKey))
            ->setArgument('$workflowClient', new Reference(WorkflowClientInterface::class))
            ->setShared(false) // A new instance must be returned each time.
            ->setPublic(true); // The facade needs the service to be public.
        $container->setDefinition($workflow, $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param ReflectionClass $workflowInterface
     *
     * @return string
     */
    public function getOptionsKey(ContainerBuilder $container, ReflectionClass $workflowInterface): string
    {
        $attributes = $workflowInterface->getAttributes(WorkflowOptions::class);

        return count($attributes) > 0 ? $attributes[0]->newInstance()->serviceId :
            $container->getParameter('workflowDefaultOptions');
    }
}
