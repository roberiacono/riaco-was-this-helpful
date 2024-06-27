/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from "@wordpress/block-editor";

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import "./editor.scss";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	//console.log("attributes", attributes);

	//attributes.helpfulBox;

	return (
		<>
			{/* <div dangerouslySetInnerHTML={{ __html: attributes.helpfulBox }} /> */}
			{/* <div {...useBlockProps()}>
				
				<div id="ri-wth-helpful-feedback" class="ri-wth-helpful-feedback">
					<div class="ri-wth-text">Was this ueful?</div>
					<div class="ri-wth-buttons-container">
						<button
							id="ri-wth-helpful-yes"
							class="helpful-yes"
							data-post_id="' . get_the_ID() . '"
							data-nonce="' . $nonce . '"
						>
							Si
						</button>
						<button
							id="ri-wth-helpful-no"
							class="helpful-no"
							data-post_id="' . get_the_ID() . '"
							data-nonce="' . $nonce . '"
						>
							no
						</button>
					</div>
				</div>
			</div> */}
			<p>DEBUG</p>
		</>
	);
}
