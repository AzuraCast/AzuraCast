module.exports = {
    // Options
    options: {
        limit: 3
    },
    // Development tasks
    devFirst: [
        'clean:dev'
    ],
    devSecond: [
        'less:dev'
    ],
    devThird: [
        'pleeease:dev'
    ],
    // Production tasks
    prodFirst: [
        'clean:prod'
    ],
    prodSecond: [
        'less:prod'
    ],
    prodThird: [
        'pleeease:prod',
        'pleeease:prod-min',
        'less-sass'
    ]
};