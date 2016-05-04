module.exports = {
    // Development settings
    dev: {
        options: {
            paths: ["less"],
            sourceMap: true,
            sourceMapRootpath: "/",
            sourceMapFilename: "test/css/material-design-iconic-font.css.map",
            sourceMapURL: "material-design-iconic-font.css.map"
        },
        files: {
            "test/css/material-design-iconic-font.css": "less/material-design-iconic-font.less"
        }
    },
    // Production settings
    prod: {
        options: {
            paths: ["less"],
            sourceMap: false
        },
        files: {
            "dist/css/material-design-iconic-font.css": "less/material-design-iconic-font.less"
        }
    }
};