const { registerBlockVariation } = wp.blocks;
const { createElement } = wp.element;

const icon = createElement(
  "svg",
  { width: "24", height: "24", viewBox: "0 0 24 24" },
  createElement("path", {
    fill: "currentColor",
    d: "M12 2L2 22h20L12 2z",
  })
);

const customIcon = `<span class="test"><svg width="24" height="24" viewBox="0 0 24 24">
        <path fill="currentColor" d="M12 2L2 22h20L12 2z"/>
    </svg></span>`;

registerBlockVariation("core/buttons", {
  name: "icon-button-wrapper",
  title: "Icon Button",
  description: "A button with default text and styling.",
  icon: "admin-links",
  innerBlocks: [
    [
      "core/button",
      {
        text: `Click Me ${customIcon}`,
        className: "has-icon-before",
      },
    ],
  ],
});
