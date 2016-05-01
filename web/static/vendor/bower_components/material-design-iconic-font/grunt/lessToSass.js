module.exports = {
    convert: {
        files: [{
            expand: true,
            cwd: 'less',
            src: ['*.less'],
            ext: '.scss',
            dest: 'scss/temp'
        }]
    }
};