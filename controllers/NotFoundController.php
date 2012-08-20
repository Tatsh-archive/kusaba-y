<?php
class NotFoundController extends MoorActionController {
  public function index() {
    sResponse::sendNotFoundHeader();
  }
}
