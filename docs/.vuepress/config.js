module.exports = {
    title: 'AzuraCast',
    description: 'Simple, Self-Hosted Web Radio',
    dest: 'public',
    ga: 'UA-120542341-1',
    head: [
        ['link', { rel: 'icon', href: `/img/logo.png` }],
        ['meta', { name: 'theme-color', content: '#2196F3' }],
        ['meta', { name: 'apple-mobile-web-app-capable', content: 'yes' }],
        ['meta', { name: 'apple-mobile-web-app-status-bar-style', content: 'black' }],
        ['link', { rel: 'apple-touch-icon', href: `/icons/apple-icon-152x152.png` }],
        ['meta', { name: 'msapplication-TileImage', content: '/icons/ms-icon-144x144.png' }],
        ['meta', { name: 'msapplication-TileColor', content: '#2196F3' }]
      ],
    themeConfig: {
        repo: 'azuracast/azuracast',
        nav: [
          { text: 'Home', link: '/' },
          { text: 'About', link: '/about' },
          { text: 'Install', link: '/install' },
          { text: 'Donate', link: '/donate' }
        ],
        docsRepo: 'https://gitlab.com/azuracast/azuracast.com',
        docsDir: 'docs',
        docsBranch: 'master',
        sidebar: [
            '/about',
            '/screenshots',
            '/demo',
            '/install',
            '/api',
            '/cli',
            '/docker_sh',
            '/mascot',
            '/developing',
            '/donate'
        ]
    }
}