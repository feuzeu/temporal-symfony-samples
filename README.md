# Temporal samples for Symfony

This repo provides sample applications and Docker Compose config to easily get started with [Temporal durable workflows](https://temporal.io/) and Symfony. 

> 2026-02-28: This repository is now archived. See [https://github.com/lagdo/temporal-php](https://github.com/lagdo/temporal-php).

## The applications

There are 4 applications in the `apps` subdir.
- An API to interact (start, query, signal) with the workflows: `apps\workflow-api`.
- A first worker to execute Temporal workflow functions: `apps\workflow-worker`.
- A second worker to execute Temporal activity functions: `apps\activity-worker`.
- An `all-in-one` application which runs the API and a worker which executes both the workflow and activity functions in a single container.

The workers are powered by the [RoadRunner](https://roadrunner.dev/) application server.
The workflow workers and activity workers are configured to listen on two separate queues on the Temporal server.

The API runs either with [Nginx Unit](https://unit.nginx.org/), [FrankenPHP](https://frankenphp.dev/), `Nginx+PHP-FPM` or [RoadRunner](https://roadrunner.dev/).

The workflow examples are taken from the [Temporal PHP SDK sampes](https://github.com/temporalio/samples-php), and modified to adapt to the Symfony applications.

## Documentation

1. [The Symfony applications](https://github.com/feuzeu/temporal-symfony-samples/wiki/1.-The-Symfony-applications)
2. [How the Symfony integration works](https://github.com/feuzeu/temporal-symfony-samples/wiki/2.-How-the-Symfony-integration-works)
3. [Adding a new function](https://github.com/feuzeu/temporal-symfony-samples/wiki/3.-Adding-a-new-function)
4. [How it is implemented](https://github.com/feuzeu/temporal-symfony-samples/wiki/4.-How-it-is-implemented) (coming soon)

## Credits

Temporal PHP SDK samples
- https://github.com/temporalio/samples-php

Other Temporal Symfony packages
- https://github.com/highcoreorg/temporal-bundle
- https://github.com/VantaFinance/temporal-bundle
- https://github.com/buyanov/symfony-temporal-worker

Docker Nginx Unit PHP
- https://github.com/N0rthernL1ghts/unit-php

Docker PHP
- https://github.com/pabloripoll/docker-php-8.3-service

