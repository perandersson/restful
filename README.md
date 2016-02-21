# Tiny RESTful library for PHP

This library is used to help build RESTful services in PHP.

Example can be found in the `example` directory.

## Requirements

PHP 7.x

## Example

Run the example (`example/users.php`) by deploying the example and lib directory on any PHP server or by
using an IDE that has a built-in server.

You can now access he following resources (using curl or any other tool that supports REST):

1. Get all users: `curl -v http://<host>:<port>/example/users.php`
2. Get one user using the user's id': `curl -v http://<host>:<port>/example/users.php/1`
3. Add one user: `curl -v -X POST --data "{\"id\":\"3\", \"name\":\"Name here\", \"age\":123}" http://<host>:<port>/example/users.php`

I've not integrated this example with a database, so no users are actually saved. That is now up to you how to solve.