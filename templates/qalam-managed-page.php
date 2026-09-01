<?php
/** Qalam-owned wrapper for Tutor commerce/registration WordPress pages. */
defined( 'ABSPATH' ) || exit;

if ( function_exists( 'qalam_240_render_shell_header' ) ) {
    qalam_240_render_shell_header();
} else {
    get_header();
}
?>
<section class="q-ref-managed-page q-ref-container" data-qalam-reveal>
    <?php while ( have_posts() ) : the_post(); ?>
        <article <?php post_class( 'q-ref-managed-page-content' ); ?>><?php the_content(); ?></article>
    <?php endwhile; ?>
</section>
<?php
if ( function_exists( 'qalam_240_render_shell_footer' ) ) {
    qalam_240_render_shell_footer();
} else {
    get_footer();
}
