<?php
namespace View\Pages;

use Model\Model;
use Site\Site;
use View\FancyPage;

class News extends FancyPage
{
  function __construct()
  {
    parent::__construct("b-stats news", "", 0);

    $this->appendToBody("<h2>News</h2>");

    $articles = Model::get()->getAllNewsArticles();

    foreach ($articles as $article) {
      $date = date("Y-m-d g:i a", $article['time']);
      $content = nl2br($article['content']);
      $this->appendToBody(
          Site::parseHtmlFragment("article.html",
              ['_author_', '_id_', '_title_', '_content_', '_date_'],
              [$article['username'], $article['article_id'], $article['title'], $content, $date]));
    }
  }
}