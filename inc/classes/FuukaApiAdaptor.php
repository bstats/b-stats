<?php

class FuukaApiAdaptor {
  public static function run(array $path):IPage {
    if(count($path) > 4 && $path[2] == 'api' && $path[3] == 'chan' ) {
      if($path[4] !== 'run' && method_exists(self::class, $path[4])) {
        try {
          return new JsonPage(self::{$path[4]}($path));
        } catch (Exception $ex) {
          return new JsonPage(["error"=>$ex->getMessage()]);
        }
      } else {
        return new JsonPage(["error"=>"Fuuka adaptor method not implemented."]);
      }
    } else {
      return new JsonPage(["error"=>"Malformed request."]);
    }
  }
   
  public static function post(array $path):array {
    $model = Model::get();
    $board = $model->getBoard(get('board'));
    $num = (int)get('num');
    $post = $model->getPost($board, $num);
    return self::fuukaFormat($post);
  }
   
  private static function fuukaFormat(Post $post):array {
    $fuukaData = [
      'doc_id' => $post->getDocId(),
      'fourchan_date' => $post->getChanTime(),
      'timestamp' => $post->getTime(),
      'name' => $post->name,
      'name_processed' => $post->getName(),
      'email' => $post->email,
      'email_processed' => $post->getEmail(),
      'trip' => $post->trip,
      'trip_processed' => $post->getTripcode(),
      'poster_hash_processed' => $post->getID(),
      'poster_hash' => $post->id,
      'comment_sanitized' => Yotsuba::toPlainText($post->getComment()),
      'comment' => $post->getComment(),
      'comment_processed' => $post->getComment(),
      'title' => $post->sub,
      'title_processed' => $post->getSubject()];
    if($post->hasImage()){
      $fuukadata['media'] = [
        'op' => $post->getThreadId() == $post->getNo() ? 1 : 0,
        'preview_w' => $post->getThumbWidth(),
        'preview_h' => $post->getThumbHeight(),
        'media_filename' => $post->getFullFilename(),
        'media_filename_processed' => $post->getFullFilename(),
        'media_w' => $post->getWidth(),
        'media_h' => $post->getHeight(),
        'media_size' => $post->getFilesize(),
        'media_hash' => base64_encode($post->getMD5Bin()),
        'media_orig' => $post->getTim().$post->getExtension(),
        'media' => $post->getTim().$post->getExtension(),
        'preview_reply' => $post->getTim()."s.jpg",
        'remote_media_link' => $post->getImgUrl(),
        'media_link' => $post->getImgUrl(),
        'thumb_link' => $post->getThumbUrl()];
    }
    else{
        $fuukadata['media'] = null;
    }
    return $fuukaData;
  }
}
