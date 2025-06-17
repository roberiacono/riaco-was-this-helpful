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
  __experimentalUseColorProps as useColorProps,
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
  SelectControl,
  TextControl,
  ToggleControl,
  Dashicon,
} from "@wordpress/components";

import { RawHTML } from "@wordpress/element";

import {
  arrowRight,
  arrowLeft,
  chevronLeft,
  chevronLeftSmall,
  chevronRight,
  chevronRightSmall,
  cloud,
  cloudUpload,
  commentAuthorAvatar,
  download,
  external,
  help,
  info,
  lockOutline,
  login,
  next,
  previous,
  shuffle,
  wordpress,
} from "@wordpress/icons";

import { RichText, URLInput } from "@wordpress/block-editor";
import { Icon, thumbsUp, thumbsDown } from "@wordpress/icons";

export default function Edit({ attributes, setAttributes }) {
  const blockProps = useBlockProps();
  const { text, icon } = attributes;
  const color = attributes?.color?.backgroundColor || "#f0f";

  const colorProps = useColorProps(attributes);

  console.log("attributes", attributes);
  console.log("blockProps", blockProps);

  const ICON_OPTIONS = [
    { label: "None", value: "" },
    { label: "Thumbs Up", value: "thumbsUp" },
    { label: "Thumbs Down", value: "thumbsDown" },
    { label: "Arrow Right", value: "arrowRight" },
    { label: "Download", value: "download" },
  ];

  const ICON_MAP = {
    thumbsUp,
    thumbsDown,
    arrowRight,
    download,
  };

  return (
    <>
      <InspectorControls>
        <PanelBody
          title={__("Button Settings", "text-domain")}
          initialOpen={true}
        >
          <TextControl
            label={__("Button Text", "text-domain")}
            value={text}
            onChange={(newText) => setAttributes({ text: newText })}
          />
          <SelectControl
            label={__("Button Icon", "text-domain")}
            value={icon}
            options={ICON_OPTIONS}
            onChange={(newIcon) => setAttributes({ icon: newIcon })}
          />
          <TextControl
            label={__("Button Color (Hex)", "text-domain")}
            value={color}
            onChange={(newColor) => setAttributes({ color: newColor })}
            help={__("Enter a hex color like #0073aa", "text-domain")}
          />
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        <Button
          /* variant="primary"
          style={{
            backgroundColor: color || undefined,
          }} */
          style={{
            backgroundColor:
              attributes?.style?.elements?.button?.color?.background,
            color: attributes?.style?.elements?.color?.button?.text,
          }}
        >
          {icon && ICON_MAP[icon] && <Icon icon={ICON_MAP[icon]} />}{" "}
          {text || __("Click here", "text-domain")}
        </Button>
      </div>
    </>
  );
}
