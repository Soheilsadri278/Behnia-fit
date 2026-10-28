<?php
/**
 * قالب پیش‌فرض — وردپرس وجود این فایل را الزامی می‌داند.
 * صفحهٔ اصلی از front-page.php رندر می‌شود.
 *
 * @package behnia-fit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="main">
	<div class="container" style="padding-block:4rem">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class(); ?>>
					<h1><?php the_title(); ?></h1>
					<?php the_content(); ?>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<h1>محتوایی پیدا نشد</h1>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
