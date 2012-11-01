<?php
class FrontController extends MoorActionController {
  public function index() {
    $categories = fRecordSet::build(Category, array(), array('name' => 'ASC'));

    $content = sTemplate::buffer('category-list-front', array('categories' => $categories));
    sTemplate::render(array('content' => $content, 'title' => ''));
  }
}
