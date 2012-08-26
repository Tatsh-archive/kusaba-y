<?php
class NotFoundController extends MoorActionController {
  public function index() {
    sResponse::sendNotFoundHeader();
    fJSON::sendHeader();
    print fJSON::encode('Not found page not set yet.');
    for ($i = 0; $i < 10000; $i++) {
      print ' ';
    }
  }
}
