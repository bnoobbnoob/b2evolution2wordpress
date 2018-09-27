<?php
include 'db.php';

$evo_prefix = 'evo';
$wp_prefix = 'wp';

$query = 'SELECT
            post_ID,
            post_datecreated,
            post_content,
            post_title,
            post_urltitle,
            post_canonical_slug_id,
            post_main_cat_ID
            from ' . $evo_prefix . '_items__item
            WHERE post_status = "published"
            ;';

$evo_posts = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$query = 'SELECT
            comment_ID,
            comment_post_id,
            comment_author_id,
            comment_author,
            comment_author_email,
            comment_author_url,
            comment_date,
            comment_content
            from ' . $evo_prefix . '_comments
            WHERE comment_status = "published"
            ;';

$evo_comments = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$query = 'SELECT
            blog_ID,
            blog_shortname,
            blog_name,
            blog_tagline,
            blog_description,
            blog_access_type,
            blog_urlname
            from ' . $evo_prefix . '_blogs
            ;';

$evo_blogs = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$query = 'SELECT
            cat_ID,
            cat_name,
            cat_blog_id
            from ' . $evo_prefix . '_categories
            ;';

$evo_categories = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

foreach ($evo_posts as $post) {
    $sql = 'INSERT INTO
            ' . $wp_prefix . '_posts (
            post_id,
            post_author,
            post_date,
            post_content,
            post_title,
            post_status,
            post_name,
            post_type,
            comment_count
            ) VALUES (
            :post_id,
            "1",
            :post_date,
            :post_content,
            :post_title,
            "publish",
            :post_name,
            "post",
            :comment_count
            );';
    
    $query = $pdo->prepare($sql);
    
    $data = array(
        'post_id' => $post['post_ID'],
        'post_date' => $post['post_datecreated'],
        'post_content' => htmlspecialchars_decode($post['content']),
        'post_title' => $post['post_title'],
        'post_name' => $post['post_urltitle'],
        'comment_count' => '0'
    );
    
    $pdo->execute($data);
}

//to do: figure out wp categories relation stuff