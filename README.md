# Container Images for ATK

This repository builds and publishes the following images:

 - atk4/build - build image used in our workflows. Will have tags 7.2, 7.3, 8.0 etc
 - atk4/site - a core image for running an ATK site. Does not include any PHP dependencies.

## Running Locally

Run `make rebuild` to regenerate Dockerfiles and to rebuild all docker images.