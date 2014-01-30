<?php
include("inc/config.php");

$page = new Page("b-stats news","",0);

$page->appendToBody("<h2>News</h2>");

$articles = Model::getAllNewsArticles();

$articleHtml = file_get_contents(Site::dir."/htmls/article.html");
foreach($articles as $article){
    $date = date("Y-m-d g:i a",$article['time']);
    $content = nl2br($article['content']);
    $page->appendToBody(str_replace(['_author_','_id_','_title_','_content_','_date_'],[$article['username'],$article['article_id'],$article['title'],$content,$date],$articleHtml));
}

echo $page->display();