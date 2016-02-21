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
    // Notice that the key in the args object is "id", which is the same as specified in the path for this function
    $id = (int)$args["id"];

    // Simulate database with simple if-statements
    if ($id == 1) {
        return array("id" => 1, "name" => "John Doe", "age" => 21);
    } else if ($id == 2) {
        return array("id" => 2, "name" => "Jane Doe", "age" => 23);
    }
    return null;
};

$getAllUsers = function ($args) {
    return array(
        array("id" => 1, "name" => "John Doe", "age" => 21),
        array("id" => 2, "name" => "Jane Doe", "age" => 23)
    );
};

$addUser = function ($args, $body) {
    $id = $body->{"id"};
    return array("id" => $id);
};

//
// Register resources
//

$restful->register(array(
    get("/{id}", $getUser),
    get("/", $getAllUsers),
    post("/", $addUser)
));
