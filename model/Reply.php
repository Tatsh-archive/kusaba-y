<?php
class Reply extends Thread {
  protected function configure() {
    fORMDate::configureDateCreatedColumn($this, 'date_created');
    fORMDate::configureDateUpdatedColumn($this, 'date_updated');
    fORMDate::configureTimezoneColumn($this, 'date_updated', 'timezone');
  }

  public function getId() {
    return $this->getReplyId();
  }

  public function encodeId() {
    return $this->encodeReplyId();
  }

  public function encodeReplyURL() {
    throw new fProgrammerException('"%s->%s" is not implemented because Reply object do not have replies.', __CLASS__, __FUNCTION__);
  }
}
