<?php foreach ($news as $news_item): ?>

    <h2><?php echo $news_item['title'] ?></h2>
    <div id="main">
        <?php echo substr($news_item['text'],0,64) ?>
    </div>
    <p><a href="http://$DOMAIN/index.php/news/view/<?php echo $news_item['slug'] ?>">View article</a></p>

<?php endforeach ?>
