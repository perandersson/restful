<?php
require_once "../lib/restful.inc.php";

//
// Create a new restful instance that's based on the incoming request
//

$restful = Restful::fromHttpRequest();

//
// Create functions for each resource
//

$getUser = function ($args) {
    // Notice that the key on the args object is "id". It is the same as specified as part of the path for this function
    $id = (int)$args["id"];

    // Simulate database with simple if-statements
    $result = null;
    if ($id == 1) {
        $result = array("id" => 1, "name" => "John Doe", "age" => 21);
    } else if ($id == 2) {
        $result = array("id" => 2, "name" => "Jane Doe", "age" => 23);
    }

    if ($result != null)
        return ok($result);
    return not_found();
};

$getAllUsers = function ($args) {
    return ok(array(
        array("id" => 1, "name" => "John Doe", "age" => 21),
        array("id" => 2, "name" => "Jane Doe", "age" => 23)
    ));
};

$addUser = function ($args, $body) {
    // The following JSON format is assumed:
    // { "id" : 1, "name" : "name here", "age": 123 }

    $id = $body->{"id"};
    $name = $body->{"name"};
    $age = $body->{"age"};
    return created(array("id" => $id, "name" => $name, "age" => $age));
};

$removeUser = function($args) {
    $id = (int)$args["id"];
    // Delete user and then return the deleted object
    return ok(array("id" => $id, "name" => "John Doe", "age" => 21));
};

//
// Register resources
//

$restful->register(array(
    get("/{id}", $getUser),
    get("/", $getAllUsers),
    post("/", $addUser),
    delete("/{id}", $removeUser)
));
