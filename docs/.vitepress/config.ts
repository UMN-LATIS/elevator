import { defineConfig } from "vitepress";

export default defineConfig({
  title: "Elevator Documentation",
  themeConfig: {
    nav: [
      { text: "Home", link: "/" },
      { text: "Features", link: "/features" },
      {
        text: "Releases",
        link: "https://github.com/UMN-LATIS/elevator/releases",
      },
      { text: "Help", link: "/help" },
    ],
    sidebar: [
      {
        text: "Using Elevator",
        items: [
          { text: "Searching and Browsing", link: "/searching-and-browsing" },
          { text: "Working with Drawers", link: "/using-drawers" },
          { text: "Embedding Elevator Assets", link: "/embedding-assets" },
        ],
      },
      {
        text: "Curating an Elevator Instance",
        items: [
          { text: "Adding and Editing Assets", link: "/curating" },
          { text: "3D Models", link: "/3dmodels" },
        ],
      },
      {
        text: "Managing an Elevator Instance",
        items: [
          { text: "Terms and Topics", link: "/terms" },
          { text: "Working with Templates", link: "/templates" },
          { text: "Types of Fields", link: "/field-types" },
          { text: "Permissions", link: "/permissions" },
          { text: "Collections", link: "/collections" },
          { text: "File Handler Groups", link: "/file-groups" },
          {
            text: "Bulk Importing and Exporing",
            link: "/importing-and-exporting",
          },
          { text: "Google Analytics", link: "/google-analytics" },
          { text: "Adding Custom Code", link: "/adding-custom-code" },
        ],
      },
      { text: "Frequently Asked Questions", link: "/faq" },
    ],
    outline: [2, 3],
    search: { provider: "local" },
    socialLinks: [
      { icon: "github", link: "https://github.com/UMN-LATIS/elevator" },
    ],
    editLink: {
      pattern: "https://github.com/UMN-LATIS/elevator/edit/develop/docs/:path",
      text: "Help us improve this page!",
    },
    footer: { message: "Developed by the University of Minnesota" },
  },
});
