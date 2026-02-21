<?php
declare(strict_types=1);

/**
 * Console Layout
 *
 * @package TVC
 */

if (!defined('ABSPATH')) {
	exit;
}

namespace TVC\Panels;

/**
 * ConsoleLayout class
 */
class ConsoleLayout {

	/**
	 * Render console layout
	 *
	 * @param string   $title           Page title.
	 * @param array    $menu_items      Menu items array.
	 * @param string   $active_section  Active section slug.
	 * @param callable $content_callback Content callback function.
	 * @return void
	 */
	public static function render(string $title, array $menu_items, string $active_section, callable $content_callback): void {
		?>
		<div class="tvc-console">
			<header class="tvc-console-header">
				<h1><?php echo esc_html($title); ?></h1>
			</header>

			<div class="tvc-console-body">
				<aside class="tvc-console-sidebar">
					<ul>
						<?php
						foreach ($menu_items as $item) {
							if (!isset($item['label']) || !isset($item['slug'])) {
								continue;
							}
							$label    = sanitize_text_field($item['label']);
							$slug     = sanitize_text_field($item['slug']);
							$is_active = ($item['slug'] === $active_section) ? ' data-active="1"' : '';
							?>
							<li>
								<a href="?section=<?php echo esc_attr($slug); ?>"<?php echo $is_active; ?>>
									<?php echo esc_html($label); ?>
								</a>
							</li>
							<?php
						}
						?>
					</ul>
				</aside>

				<main class="tvc-console-content">
					<?php
					if (is_callable($content_callback)) {
						call_user_func($content_callback);
					}
					?>
				</main>
			</div>
		</div>
		<?php
	}
}

