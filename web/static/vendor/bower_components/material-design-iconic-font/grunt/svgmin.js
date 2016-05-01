module.exports = {
    test: {
        options: {
            plugins: [
                { removeViewBox: false }
            ]
        },
        files: [{
            expand: true,    // Enable dynamic expansion.
            cwd: 'svg/2.1',  // Src matches are relative to this path.
            src: ['*.svg'],  // Actual pattern(s) to match.
            dest: 'svg/new', // Destination path prefix.
            ext: '.svg'      // Dest filepaths will have this extension.
        }]
    }
};