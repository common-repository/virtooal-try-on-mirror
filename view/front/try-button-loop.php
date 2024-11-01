<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
};
echo '
<div class="virtooal-tryon-btn-wrapper">
	<button class="button virtooal-tryon-btn virtooal-tryon-btn-catalog virtooal-try-on-mirror" style="display: none;" data-virtooal_id="' . $product_id . '">
		' . $tryon_text . '
	</button>
</div>';

