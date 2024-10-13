# Temporal samples for Symfony

This repo provides sample applications and Docker Compose config to easily get started with [Temporal durable workflows](https://temporal.io/) and Symfony.

## The Symfony applications

There are 3 applications in the `temporal-apps` subdir.
- An API to interact (start, query, signal) with the workflows: `temporal-apps\workflow-api`.
- A first worker that only execute Temporal workflow functions: `temporal-apps\workflow-worker`.
- A second worker that only execute Temporal activity functions: `temporal-apps\activity-worker`.

The workers are powered by the [RoadRunner](https://roadrunner.dev/) application server.
The workflow workers and activity workers are configured to listen on two separate queues on the Temporal server.

The API run either with [Nginx Unit](https://unit.nginx.org/), [FrankenPHP](https://frankenphp.dev/) or `Nginx+PHP-FPM`.

The workflow examples are taken from the [Temporal PHP SDK sampes](https://github.com/temporalio/samples-php), and modified to adapt to the Symfony framework.

### Configuration

There are two config files for Temporal in each Symfony app, `config/temporal/runtime.yaml` for the Temporal SDK runtime, and `config/temporal/services.yaml` for the Temporal worflows.

Other options are set in the `environment` section of the containers in the `docker/temporal-apps/docker-compose.yml` file.

### Running the samples

The `docker/temporal-server/docker-compose.yml` file will start the Temporal server.
It is the same as in the [Temporal PHP SDK sampes](https://github.com/temporalio/samples-php), but without the PHP application container.
It needs to be started before running the Symfony applications.

The `docker/temporal-apps/docker-compose.yml` file will start the 3 Symfony applications, which need to connect to the Temporal server, using the address or hostname set in the `environment` section in the docker-compose file.

Before starting the applications, first build the containers, then install the PHP packages in each container with `Composer`..

```bash
cd docker/temporal-apps/

docker-compose build
docker-compose run --rm --user temporal activity-worker composer install
docker-compose run --rm --user temporal workflow-worker composer install
docker-compose run --rm --user temporal workflow-api composer install

docker-compose up -d
```

By default, the `workflow-api` app will be started in the `Nginx Unit` container.
The other application servers (PHP-FPM and FrankenPHP) can be enabled by uncommenting their definition in the `docker-compose.yml` file.

Each application server is configured to be available on a separate port:
- Nginx Unit: http://localhost:9300
- FrankenPHP: http://localhost:9301
- Nginx+PHP-FPM: http://localhost:9302

### Swagger

The `workflow-api` app also provides a [Swagger](https://swagger.io/) powered webpage to call its endpoints, which are listed in the `temporal-apps/workflow-api/config/packages/nelmio_api_doc.yaml` file.

The page will be available at [http://localhost:9300/api/doc](http://localhost:9300/api/doc).

## How it works

Implementing a workflow in these Symfony applications requires to define interfaces, classes and [facades](https://github.com/lagdo/symfony-facades), resp. for workflows and activities, together with their respective options.

The [facades](https://github.com/lagdo/symfony-facades) are required here because we are in a case where dependency injection simply doesn't work.

### Workflows and activities

The interfaces and classes for the workflows and activities are the basis when working with Temporal.
Many examples of these can be found in the [Temporal PHP SDK sampes](https://github.com/temporalio/samples-php) repo.

In the Symfony applications, the workflow and activity classes are located in the `src\Workflow\Service\Workflow` and `src\Workflow\Service\Activity` subdirs. Of course, their namespaces are changed accordingly.

See the `Summary` section below for how to deploy the workflows and activities code in the Symfony apps.

The workflow and activity classes must be tagged resp. with `temporal.service.workflow` and `temporal.service.activity` in the Symfony service container, so they are automatically registered to the Temporal server.

### Stubs and facades

When a workflow or an activity function is called, the Temporal library actually uses a proxy class to forward the call to its server, which will in turn forward the same call to an available worker.
These proxy classes are called `stubs`.

It can be supposed that they implement the interfaces of the workflows and activities they are proxying, althougth they actually do not. That's why the Symfony dependency injection cannot be used to inject a stub where a workflow or an activity interface is required.

As a consequence, a [facade](https://github.com/lagdo/symfony-facades) is used anytime a call to a workflow or an activity function needs to be made.
That means:
- When a workflow is started or called in the `workflow-api` app.
- When an activity is called or a child workflow started in the `workflow-worker` app.

In summary, a facade will use a workflow or activity interface as service identifier, and forward its calls to a Temporal stub that it has picked in the Symfony service container.

The workflow and activity facades are aloso located in the `src\Workflow\Service\Workflow` and `src\Workflow\Service\Activity` subdirs. 
They must also be tagged resp. with `temporal.service.workflow` and `temporal.service.activity` in the Symfony service container, so the corresponding stubs are automatically registered.

### Options and attributes

With Temporal, the options of a workflow or an activity function are defined when making the call to the server.
In this case, that also means when making a call to a stub using a facade.

In the Symfony apps, the workflow and activity options are defined in the `config/temporal/services.yaml` config file.

```yaml
    moneyBatchWorkflowOptions:
        class: 'Temporal\Client\WorkflowOptions'
        factory: ['App\Temporal\Factory\WorkflowFactory', 'moneyBatchOptions']
```

The options are then applied to a stub (or a facade) using an attribute on the corresponding interface.

```php
namespace App\Workflow\Service\Workflow\MoneyBatch;

use App\Temporal\Attribute\WorkflowOptions;
use Temporal\Workflow\WorkflowInterface;

#[WorkflowInterface]
#[WorkflowOptions(serviceId: "moneyBatchWorkflowOptions")]
interface MoneyBatchWorkflowInterface
{
    //
}
```

Three classes are defined for attributes:
- `App\Temporal\Attribute\WorkflowOptions` for workflow options
- `App\Temporal\Attribute\ActivityOptions` for activity options
- `App\Temporal\Attribute\ChildWorkflowOptions` for child workflow options.

### Summary

In summary, here's the steps to implement a new function. We'll take the `MoneyBatch` as example.

#### For the workflow

1. Add the workflow interface and class in the `workflow-worker` app.

In the `temporal-apps\workflow-worker\src\Workflow\Service\Workflow\MoneyBatch\MoneyBatchWorkflowInterface.php` file,

```php
namespace App\Workflow\Service\Workflow\MoneyBatch;

use Temporal\Workflow\WorkflowInterface;

#[WorkflowInterface]
interface MoneyBatchWorkflowInterface
{
}
```

In the `temporal-apps\workflow-worker\src\Workflow\Service\Workflow\MoneyBatch\MoneyBatchWorkflow.php` file,

```php
namespace App\Workflow\Service\Workflow\MoneyBatch;

class MoneyBatchWorkflow implements MoneyBatchWorkflowInterface
{
}
```

2. For a main workflow,

- Define the workflow options and add an attribute to the workflow interface in the `workflow-api` app.

In the `temporal-apps\workflow-api\config\temporal\services.yaml` file,

```yaml
services:
    moneyBatchWorkflowOptions:
        class: 'Temporal\Client\WorkflowOptions'
        factory: ['App\Temporal\Factory\WorkflowFactory', 'moneyBatchOptions']
```

In the `temporal-apps\workflow-api\src\Workflow\Service\Workflow\MoneyBatch\MoneyBatchWorkflowInterface.php` file,

```php
namespace App\Workflow\Service\Workflow\MoneyBatch;

use App\Temporal\Attribute\WorkflowOptions;
use Temporal\Workflow\WorkflowInterface;

#[WorkflowInterface]
#[WorkflowOptions(serviceId: "moneyBatchWorkflowOptions")]
interface MoneyBatchWorkflowInterface
{
}
```
- Add the workflow facade in the `workflow-api` app.

In the `temporal-apps\workflow-api\src\Workflow\Service\Workflow\MoneyBatch\MoneyBatchWorkflowFacade.php` file,

```php
namespace App\Workflow\Service\Workflow\MoneyBatch;

use App\Temporal\Factory\WorkflowClientTrait;
use Lagdo\Symfony\Facades\AbstractFacade;

class MoneyBatchWorkflowFacade extends AbstractFacade
{
    use WorkflowClientTrait;

    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return MoneyBatchWorkflowInterface::class;
    }
}
```

The `WorkflowClientTrait` trait provides additional helper functions to start a new workflow and get a running workflow.
See the `temporal-apps\workflow-api\src\Controller\MoneyBatchController.php` file for examples.

3. For a child workflow,

- Define the child workflow options and add an attribute to the workflow interface in the `workflow-worker` app.

In the `temporal-apps\workflow-worker\config\temporal\services.yaml` file,

```yaml
services:
    defaultChildWorkflowOptions:
        class: 'Temporal\Workflow\ChildWorkflowOptions'
        factory: ['App\Temporal\Factory\WorkflowFactory', 'defaultOptions']
```

In the `temporal-apps\workflow-worker\src\Workflow\Service\Workflow\Child\ChildWorkflowInterface.php` file,

```php
namespace App\Workflow\Service\Workflow\Child;

use App\Temporal\Attribute\ChildWorkflowOptions;
use Temporal\Workflow\WorkflowInterface;

#[WorkflowInterface]
#[ChildWorkflowOptions(serviceId: "defaultChildWorkflowOptions")]
interface ChildWorkflowInterface
{
}
```

- Add the child workflow facade in the `workflow-worker` app.

In the `temporal-apps\workflow-worker\src\Workflow\Service\Workflow\Child\ChildWorkflowFacade.php` file,

```php
namespace App\Workflow\Service\Workflow\Child;

use Lagdo\Symfony\Facades\AbstractFacade;

class ChildWorkflowFacade extends AbstractFacade
{
    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return ChildWorkflowInterface::class;
    }
}
```

#### For the activity

1. Add the activity interface and class in the `activity-worker` app.

In the `temporal-apps\activity-worker\src\Workflow\Service\Activity\MoneyBatch\AccountActivityInterface.php` file,

```php
namespace App\Workflow\Service\Activity\MoneyBatch;

use Temporal\Activity\ActivityInterface;

#[ActivityInterface(prefix: "MoneyBatch.")]
interface AccountActivityInterface
{
}
```

In the `temporal-apps\activity-worker\src\Workflow\Service\Activity\MoneyBatch\AccountActivity.php` file,

```php
namespace App\Workflow\Service\Activity\MoneyBatch;

class AccountActivity implements AccountActivityInterface
{
}
```

2. Define the activity options and add an attribute to the activity interface in the `workflow-worker` app.

In the `temporal-apps\workflow-worker\config\temporal\services.yaml` file,

```yaml
services:
    moneyBatchActivityOptions:
        class: 'Temporal\Activity\ActivityOptions'
        factory: ['App\Temporal\Factory\ActivityFactory', 'moneyBatchOptions']
```

In the `temporal-apps\workflow-worker\src\Workflow\Service\Activity\MoneyBatch\AccountActivityInterface.php` file,

```php
namespace App\Workflow\Service\Activity\MoneyBatch;

use App\Temporal\Attribute\ActivityOptions;
use Temporal\Activity\ActivityInterface;

#[ActivityInterface(prefix: "MoneyBatch.")]
#[ActivityOptions(serviceId: "moneyBatchActivityOptions")]
interface AccountActivityInterface
{
}
```

3. Add the activity facade in the `workflow-worker` app.

In the `temporal-apps\workflow-worker\src\Workflow\Service\Activity\MoneyBatch\AccountActivityFacade.php` file,

```php
namespace App\Workflow\Service\Activity\MoneyBatch;

use Lagdo\Symfony\Facades\AbstractFacade;

class AccountActivityFacade extends AbstractFacade
{
    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return AccountActivityInterface::class;
    }
}
```

## How it is implemented

### Configuration

### Runtimes

### Compiler passes

### Factories

### Attributes

### PHP application servers

### Credits

https://github.com/pabloripoll/docker-php-8.3-service
