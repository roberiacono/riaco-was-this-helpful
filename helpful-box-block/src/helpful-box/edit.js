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
import {
  InnerBlocks,
  InspectorControls,
  useBlockProps,
} from "@wordpress/block-editor";

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

import {
  Button,
  PanelBody,
  TextControl,
  ToggleControl,
} from "@wordpress/components";

import { Icon, thumbsUp, thumbsDown } from "@wordpress/icons";

export default function Edit({ attributes, setAttributes }) {
  //console.log("attributes", attributes);
  const { boxText } = attributes;
  return (
    <>
      {/*  <InspectorControls>
        <PanelBody title={__("Settings", "helpful-box-block")}>
          
        </PanelBody>
      </InspectorControls> */}
      <div {...useBlockProps()}>
        <InnerBlocks
          allowedBlocks={[
            "core/paragraph",
            "core/heading",
            "core/button",
            "core/group",
          ]}
          template={[
            [
              "core/group",
              {},
              [
                [
                  "core/paragraph",
                  {
                    placeholder: "Enter feedback text…",
                    content: "Hi",
                  },
                ],
                [
                  "core/buttons",
                  {
                    layout: {
                      type: "flex",
                      justifyContent: "center",
                    },
                  },
                  [
                    [
                      "core/button",
                      {
                        text: "Helpful",
                        align: "center",
                        className: "riwth-helpful-yes",
                      },
                    ],
                    [
                      "core/button",
                      {
                        text: "Not Helpful",
                        align: "center",
                        className: "riwth-helpful-no",
                      },
                    ],
                  ],
                ],
              ],
            ],
          ]}
          templateLock="insert"

          //templateLock={insert}
        />
      </div>
    </>
  );
}
