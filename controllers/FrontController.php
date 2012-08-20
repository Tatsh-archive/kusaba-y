<?php
class FrontController extends MoorActionController {
  public function index() {
    sTemplate::render(array('content' => '<p>Will be the front page</p>', 'title' => ''));
  }
}
