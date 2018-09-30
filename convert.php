<?php

$evo_prefix = 'evo';
$wp_prefix = 'wp';

$db = 'evo';
include('db.php');

$query = 'SELECT
            post_ID,
            post_datecreated,
            post_content,
            post_title,
            post_urltitle,
            post_canonical_slug_id,
            post_main_cat_ID,
            cat_name
            from ' . $evo_prefix . '_items__item
            INNER JOIN ' . $evo_prefix . '_categories
            ON ' . $evo_prefix . '_items__item.post_main_cat_id = ' . $evo_prefix . '_categories.cat_ID
            WHERE post_status = "published"
            limit 10
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

/*$query = 'SELECT
            blog_ID,
            blog_shortname,
            blog_name,
            blog_tagline,
            blog_description,
            blog_access_type,
            blog_urlname
            from ' . $evo_prefix . '_blogs
            ;';

$evo_blogs = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);*/

$query = 'SELECT
            cat_ID,
            cat_name,
            cat_blog_id
            from ' . $evo_prefix . '_categories
            ;';

$evo_categories = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$pdo = null;

$db = 'wordpress';
include 'db.php';

foreach ($evo_categories as $category) {
    $sql = 'INSERT INTO
            ' . $wp_prefix . '_terms (
            name,
            term_group
            ) VALUES (
            :name,
            "0"
            );';

    $query = $pdo->prepare($sql);

    $data = array(
        'name' => $category['cat_name'],
    );

    $query->execute($data);

    $sql = 'INSERT INTO
            ' . $wp_prefix . '_term_taxonomy (
            term_id,
            taxonomy
            ) VALUES (
            :term_id,
            "category"
            );';

    $query = $pdo->prepare($sql);

    $data = array(
        'term_id' => $category['cat_ID'],
    );

    $query->execute($data);
}

foreach ($evo_posts as $post) {
    $sql = 'INSERT INTO
            ' . $wp_prefix . '_posts (
            ID,
            post_author,
            post_date,
            post_content,
            post_title,
            post_status,
            post_name,
            post_type,
            comment_count
            ) VALUES (
            :ID,
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
        'ID' => $post['post_ID'],
        'post_date' => $post['post_datecreated'],
        'post_content' => htmlspecialchars_decode($post['post_content']),
        'post_title' => $post['post_title'],
        'post_name' => $post['post_urltitle'],
        'comment_count' => '0'
    );
    
    $query->execute($data);

    $sql = 'INSERT INTO
            ' . $wp_prefix . '_term_relationships (
            object_id,
            term_taxonomy_id
            ) VALUES (
            :object_id,
            :term_taxonomy_id
            );';

    $query = $pdo->prepare($sql);

    $data = array(
        'object_id' => $post['post_ID'],
        'term_taxonomy_id' => $post['post_main_cat_ID']
    );

    $query->execute($data);
}