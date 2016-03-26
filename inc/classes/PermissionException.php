<?php

class PermissionException extends Exception {
  public $required;
  public $has;
  public function __construct(int $perm, int $req) {
    parent::__construct("Inadequate permissions. Your privilege: $perm, required: $req");
    $this->required = $req;
    $this->has = $perm;
  }
}
