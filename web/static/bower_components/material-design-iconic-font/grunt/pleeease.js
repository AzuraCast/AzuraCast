module.exports = {
    prod: {
        options: {
            autoprefixer: {'browsers': ["> 1%", "last 2 versions"]},
            filters: {'oldIE': false},
            opacity: true,
            pseudoElements: true,
            minifier: false
        },
        files: {
            'dist/css/material-design-iconic-font.css': 'dist/css/material-design-iconic-font.css'
        }
    },
    dev: {
        options: {
            autoprefixer: {'browsers': ["> 1%", "last 2 versions"]},
            filters: {'oldIE': false},
            opacity: true,
            pseudoElements: true,
            minifier: false
        },
        files: {
            'test/css/material-design-iconic-font.css': 'test/css/material-design-iconic-font.css'
        }
    },
    'prod-min': {
        options: {
            autoprefixer: {'browsers': ["> 1%", "last 2 versions"]},
            filters: {'oldIE': false},
            opacity: true,
            pseudoElements: true,
            minifier: {preserveHacks: true, removeAllComments: true}
        },
        files: {
            'dist/css/material-design-iconic-font.min.css': 'dist/css/material-design-iconic-font.css'
        }
    }
};