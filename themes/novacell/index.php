<?php get_header(); ?>
<main id="main-content" tabindex="-1" class="nc-shell nc-generic-page"><h1><?php echo esc_html( 'en' === nc_theme_lang() ? 'NOVACELL' : 'NOVACELL' ); ?></h1>
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
<article><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
<?php endwhile; the_posts_pagination(); endif; ?></main>
<?php get_footer(); ?>
