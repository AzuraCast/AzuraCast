module.exports = {
    less: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'scss/temp/',
            dest: 'scss/',
            src: ['{,*/}*.scss'],
            rename: function(dest, src) {
                if (src !== 'material-design-iconic-font.scss') {
                    src = "_" + src;
                }
                return dest + src;
            }
        }]
    },
    action: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/action/svg/production/',
            dest: 'svg/google/action/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    alert: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/alert/svg/production/',
            dest: 'svg/google/alert/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    av: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/av/svg/production/',
            dest: 'svg/google/av/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    communication: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/communication/svg/production/',
            dest: 'svg/google/communication/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    content: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/content/svg/production/',
            dest: 'svg/google/content/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    device: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/device/svg/production/',
            dest: 'svg/google/device/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    editor: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/editor/svg/production/',
            dest: 'svg/google/editor/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    file: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/file/svg/production/',
            dest: 'svg/google/file/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    hardware: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/hardware/svg/production/',
            dest: 'svg/google/hardware/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    image: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/image/svg/production/',
            dest: 'svg/google/image/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    maps: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/maps/svg/production/',
            dest: 'svg/google/maps/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    navigation: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/navigation/svg/production/',
            dest: 'svg/google/navigation/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    notification: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/notification/svg/production/',
            dest: 'svg/google/notification/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    social: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/social/svg/production/',
            dest: 'svg/google/social/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    },
    toggle: {
        files: [{
            expand: true,
            dot: true,
            filter: 'isFile',
            flatten: true,
            cwd: 'node_modules/material-design-icons/toggle/svg/production/',
            dest: 'svg/google/toggle/',
            src: ['{,*/}*_24px.svg'],
            rename: function(dest, src) {
                src = src.replace('ic_','');
                src = src.replace('_24px','');
                return dest + src.replace(/[_ ]/g,'-');
            }
        }]
    }
};