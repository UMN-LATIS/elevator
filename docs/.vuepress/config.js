module.exports = {
    plugins: [
        'vuepress-plugin-export',
        [
            '@vuepress/blog',
            {
                directories: [{
                    // Unique ID of current classification
                    id: 'post',
                    // Target directory
                    dirname: '_posts',
                    // Path of the `entry page` (or `list page`)
                    path: '/updates/',
                    layout: 'BlogLayout'
                }, ],
            },
        ]
    ],
    title: 'Elevator Documentation',
    base: '/',
    themeConfig: {
        // Assumes GitHub. Can also be a full GitLab url.
        sidebarDepth: 2,
        repo: 'umn-latis/elevator',
        // Customising the header labele
        // Defaults to "GitHub"/"GitLab"/"Bitbucket" depending on `themeConfig.repo`
        repoLabel: 'Contribute!',
        // Optional options for generating "Edit this page" link
        // if your docs are not at the root of the repo:
        docsDir: 'docs',
        // if your docs are in a specific branch (defaults to 'master'):
        docsBranch: 'develop',
        // defaults to false, set to true to enable
        editLinks: true,
        // custom text for edit link. Defaults to "Edit this page"
        editLinkText: 'Help us improve this page!',
        nav: [{
                text: 'Home',
                link: '/'
            },
            {
                text: 'Help',
                link: '/help/'
            },
            {
                text: 'Updates',
                link: '/updates/'
            },
            {
                text: 'Features',
                link: '/features/'
            },
        ],
        sidebar: [
            // {
            //     title: 'Overview',   // required
            //     // path: '/foo/',      // optional, link of the title, which should be an absolute path and must exist
            //     collapsable: false, // optional, defaults to true
            //     sidebarDepth: 1,    // optional, defaults to 1
            //     children: [
            //         '/'
            //     ]
            // },
            // {
            //     title: 'Help',   // required
            //     // path: '/foo/',      // optional, link of the title, which should be an absolute path and must exist
            //     collapsable: true, // optional, defaults to true
            //     sidebarDepth: 1,    // optional, defaults to 1
            //     children: [
            //         '/'
            //     ]
            // },
            {
                title: 'Using Elevator', // required
                // path: '/foo/',      // optional, link of the title, which should be an absolute path and must exist
                collapsable: false, // optional, defaults to true
                sidebarDepth: 2, // optional, defaults to 1
                children: [
                    '/searching-and-browsing',
                    '/using-drawers',
                    '/embedding-assets'
                ]
            },
            {
                title: 'Curating an Elevator Instance', // required
                // path: '/foo/',      // optional, link of the title, which should be an absolute path and must exist
                collapsable: false, // optional, defaults to true
                sidebarDepth: 2, // optional, defaults to 1
                children: [
                    '/curating',
                    '/3dmodels'
                ]
            },
            {
                title: 'Managing an Elevator Instance', // required
                // path: '/foo/',      // optional, link of the title, which should be an absolute path and must exist
                collapsable: false, // optional, defaults to true
                sidebarDepth: 2, // optional, defaults to 1
                children: [
                    '/terms',
                    "/templates",
                    "/field-types",
                    "/permissions",
                    "/collections",
                    "/file-groups",
                    "/importing-and-exporting",
                    "/google-analytics",
                    "/adding-custom-code"
                ]
            },
            ['/faq', 'Frequently Asked Questions']


        ],

    }
}