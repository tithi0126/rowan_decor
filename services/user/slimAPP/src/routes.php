<?php

// get all todos
    $app->get('/todos', function ($request, $response, $args) {
         $sth = $this->db->prepare("SELECT * FROM vendor_reg ");
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos);
    });
 
    // Retrieve todo with id 
    $app->get('/todo/[{id}]', function ($request, $response, $args) {
        $order_current_date=date("d/m/Y");
         $sth = $this->db->prepare("SELECT count(CASE WHEN stats ='Confirmed' THEN 1 END) As confirmed, count(CASE WHEN stats ='Delivered' THEN 1 END) As delivered,count(CASE WHEN stats ='enable' THEN 1 END) As enable ,count(CASE WHEN stats ='Pending' THEN 1 END) As pending ,count(CASE WHEN stats ='Cancelled' THEN 1 END) As cancelled FROM `ordr` WHERE v_id= :id and STR_TO_DATE(SUBSTRING(ordr.collect_date,1,10), '%d/%m/%Y' ) = STR_TO_DATE( '".$order_current_date."', '%d/%m/%Y' )");
         
        $sth->bindParam("id", $args['id']);
        $sth->execute();
        $todos = $sth->fetchObject();
        return $this->response->withJson($todos);
    });
 
 
    // Search for todo with given search teram in their name
    $app->get('/todos/search/[{query}]', function ($request, $response, $args) {
         $sth = $this->db->prepare("SELECT * FROM tasks WHERE UPPER(task) LIKE :query ORDER BY task");
        $query = "%".$args['query']."%";
        $sth->bindParam("query", $query);
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos);
    });
 
    // Add a new todo
    $app->post('/todo', function ($request, $response) {
        $input = $request->getParsedBody();
        $sql = "INSERT INTO tasks (task) VALUES (:task)";
         $sth = $this->db->prepare($sql);
        $sth->bindParam("task", $input['task']);
        $sth->execute();
        $input['id'] = $this->db->lastInsertId();
        return $this->response->withJson($input);
    });
        
 
    // DELETE a todo with given id
    $app->delete('/todo/[{id}]', function ($request, $response, $args) {
         $sth = $this->db->prepare("DELETE FROM tasks WHERE id=:id");
        $sth->bindParam("id", $args['id']);
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos);
    });
 
    // Update todo with given id
    $app->put('/todo/[{id}]', function ($request, $response, $args) {
        $input = $request->getParsedBody();
        $sql = "UPDATE tasks SET task=:task WHERE id=:id";
         $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $args['id']);
        $sth->bindParam("task", $input['task']);
        $sth->execute();
        $input['id'] = $args['id'];
        return $this->response->withJson($input);
    });