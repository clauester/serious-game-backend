<?php

require_once __DIR__ . '/../Repository/GroupRepository.php';

class GroupService {

    private $repo;

    public function __construct() {
        $this->repo = new GroupRepository();
    }

    public function createGroup($code, $name, $description) {
        return $this->repo->createGroup($code, $name, $description);
    }

    public function getAllGroups() {
        return $this->repo->getAllGroups();
    }

}
