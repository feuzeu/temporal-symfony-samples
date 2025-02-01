<?php

namespace App\Temporal\Compiler;

use App\Temporal\Attribute\ChildWorkflowOptions;
use App\Temporal\Factory\ChildWorkflowFactory;
use App\Temporal\Runtime\Runtime;
use Lagdo\Symfony\Facades\AbstractFacade;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Temporal\Workflow\WorkflowInterface;

use function count;

class ChildWorkflowStubCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        // Register the classes that are tagged as workflow.
        $workflows = $container->findTaggedServiceIds('temporal.service.workflow.child');
        foreach($workflows as $workflowClassName => $_)
        {
            $workflowClass = new ReflectionClass($workflowClassName);
            if($workflowClass->isSubclassOf(AbstractFacade::class))
            {
                // A facade doesn't need to be registered in the service container.
                $container->removeDefinition($workflowClassName);

                if(($workflowInterface = $this->getInterfaceFromFacade($workflowClass)) !== null)
                {
                    // The class is a facade on WorkflowInterface. Register a child workflow stub.
                    $this->registerChildWorkflowStub($container, $workflowInterface);
                }
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
    private function registerChildWorkflowStub(ContainerBuilder $container, ReflectionClass $workflowInterface): void
    {
        $workflow = $workflowInterface->getName();
        $optionsKey = $this->getOptionsKey($container, $workflowInterface);
        $definition = (new Definition($workflow))
            ->setFactory(ChildWorkflowFactory::class . '::childWorkflowStub')
            ->setArgument('$workflow', $workflow)
            ->setArgument('$options', new Reference($optionsKey))
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
    private function getOptionsKey(ContainerBuilder $container, ReflectionClass $workflowInterface): string
    {
        $attributes = $workflowInterface->getAttributes(ChildWorkflowOptions::class);

        return count($attributes) > 0 ? $attributes[0]->newInstance()->serviceId :
            $container->getParameter('childWorkflowDefaultOptions');
    }
}
