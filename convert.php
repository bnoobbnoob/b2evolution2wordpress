<?php

//get ALL the ressources
set_time_limit(0);
ini_set('memory_limit', '1024M');

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
    break;
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
            taxonomy,
            count
            ) VALUES (
            :term_id,
            "category",
            23
            );';

    $query = $pdo->prepare($sql);

    $data = array(
        'term_id' => $category['cat_ID'],
    );

    $query->execute($data);
}

foreach ($evo_posts as $post) {
    break;
    $sql = 'INSERT INTO
            ' . $wp_prefix . '_posts (
            ID,
            post_author,
            post_date,
            post_date_gmt,
            post_content,
            post_title,
            post_status,
            comment_status,
            ping_status,
            post_name,
            post_type
            ) VALUES (
            :ID,
            "1",
            :post_date,
            :post_content,
            :post_title,
            "publish",
            "open",
            "open",
            :post_name,
            "post"
            );';
    
    $query = $pdo->prepare($sql);
    
    $data = array(
        'ID' => $post['post_ID'],
        'post_date' => $post['post_datecreated'],
        'post_date_gmt' => $post['post_datecreated'],
        'post_content' => htmlspecialchars_decode($post['post_content']),
        'post_title' => $post['post_title'],
        'post_name' => $post['post_urltitle']
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

foreach ($evo_comments as $comment) {
    break;
    $sql = 'INSERT INTO
            ' . $wp_prefix . '_comments (
            comment_post_id,
            comment_author,
            comment_author_email,
            comment_author_url,
            comment_date,
            comment_date_gmt,
            comment_content,
            comment_approved,
            user_id
            ) VALUES (
            :comment_post_id,
            :comment_author,
            :comment_author_email,
            :comment_author_url,
            :comment_date,
            :comment_content,
            1,
            :user_id
            );';
    
    $query = $pdo->prepare($sql);
    
    $data = array(
        'comment_post_id' => $comment['comment_post_id'],
        'comment_author' => $comment['comment_author'],
        'comment_author_email' => $comment['comment_author_email'],
        'comment_author_url' => $comment['comment_author_url'],
        'comment_date' => $comment['comment_date'],
        'comment_date_gmt' => $comment['comment_date'],
        'comment_content' => htmlspecialchars_decode($comment['comment_content'])
    );
    
    //wordpress NEEDS a user id
    if (!empty($comment['comment_author_id']))
        $data['user_id'] = $comment['comment_author_id'];
    else
        $data['user_id'] = '0';
    
    //wordpress NEEDS an author name
    if (!empty($comment['comment_author']))
        $data['comment_author'] = $comment['comment_author'];
    else
        $data['comment_author'] = 'Anonym';
    
    //wordpress NEEDS an author email
    if (!empty($comment['comment_author_email']))
        $data['comment_author_email'] = $comment['comment_author_email'];
    else
        $data['comment_author_email'] = 'Anonym';
    
    //wordpress NEEDS an author url
    if (!empty($comment['comment_author_url']))
        $data['comment_author_url'] = $comment['comment_author_url'];
    else
        $data['comment_author_url'] = 'Anonym';
    
    $query->execute($data);
}

//fix comment count for posts

$query = 'SELECT
            comment_post_ID,
            COUNT(comment_post_ID) AS comment_count
            FROM wp_comments 
            GROUP BY comment_post_ID
            ;';

$comment_count = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

foreach ($comment_count as $count) {
    $sql = 'UPDATE
            ' . $wp_prefix . '_posts
            SET
            comment_count = :comment_count
            WHERE
            ID = :ID
            ;';
    
    $query = $pdo->prepare($sql);
    
    $data = array(
        'comment_count' => $count['comment_count'],
        'ID' => $count['comment_post_ID']
    );
    
    $query->execute($data);
}

//fix post count for comments

$query = 'SELECT
            term_taxonomy_id,
            COUNT(term_taxonomy_id) AS category_count
            FROM wp_term_relationships
            GROUP BY term_taxonomy_id
            ;';

$category_count = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

foreach ($category_count as $count) {
    $sql = 'UPDATE
            ' . $wp_prefix . '_term_taxonomy
            SET
            count = :count
            WHERE
            term_taxonomy_id = :term_taxonomy_id
            ;';
    
    $query = $pdo->prepare($sql);
    
    $data = array(
        'count' => $count['category_count'],
        'term_taxonomy_id' => $count['term_taxonomy_id']
    );
}